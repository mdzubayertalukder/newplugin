<<<<<<< HEAD
<?php

namespace Plugin\Dropshipping\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Plugin\Dropshipping\Services\LimitService;
use Plugin\Dropshipping\Models\DropshippingResearchUsage;
use Plugin\Dropshipping\Services\GoogleAIStudioService;
use Plugin\Dropshipping\Services\OpenAIService;
use Plugin\Dropshipping\Services\GoogleSearchService;

class ProductResearchController extends Controller
{
    protected $googleAIStudioService;
    protected $openAIService;
    protected $googleSearchService;

    public function __construct()
    {
        $this->googleAIStudioService = new GoogleAIStudioService();
        $this->openAIService = new OpenAIService();
        $this->googleSearchService = new GoogleSearchService();
    }

    public function researchProduct(Request $request, $productId)
    {
        try {
            $tenantId = tenant('id');
            $canResearch = LimitService::canResearch($tenantId);
            if (!$canResearch['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $canResearch['message'],
                    'reason' => $canResearch['reason'],
                    'upgrade_message' => LimitService::getUpgradeMessage($canResearch['reason']),
                    'limit_reached' => true
                ]);
            }

            $product = DB::connection('mysql')->table('dropshipping_products')
                ->where('id', $productId)
                ->where('status', 'publish')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }

            // Get settings with proper database connection
            try {
                $settings = DB::connection('mysql')->table('dropshipping_settings')->pluck('value', 'key');
                Log::info('Dropshipping Settings in ProductResearchController', $settings->toArray());
            } catch (\Exception $e) {
                Log::error('Failed to retrieve dropshipping settings', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $tenantId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve configuration settings.'
                ]);
            }

            $aiService = $settings['ai_service'] ?? 'openai'; // Default to OpenAI since user has it configured
            Log::info('AI Service Configuration:', [
                'selected_service' => $aiService,
                'openai_key_set' => !empty($settings['openai_api_key'] ?? ''),
                'google_key_set' => !empty($settings['google_ai_studio_api_key'] ?? ''),
                'product_id' => $productId,
                'product_name' => $product->name
            ]);

            // Get product cost information if available
            $productCost = $product->regular_price ?? null;
            $suggestedPrice = $product->sale_price ?? $product->regular_price ?? null;
            
            // Get real market data using Google Search API if configured
            $searchData = $this->getSearchData($product->name, $settings);
            
            $prompt = $this->buildResearchPrompt($product->name, $productCost, $suggestedPrice, $searchData);
            $aiResponse = null;
            $serviceUsed = null;

            // Try OpenAI first if configured
            if ($aiService === 'openai' || (!empty($settings['openai_api_key']) && $aiService !== 'google')) {
                $apiKey = $settings['openai_api_key'] ?? '';
                Log::info('Attempting to use OpenAI service', [
                    'key_length' => strlen($apiKey),
                    'key_prefix' => substr($apiKey, 0, 7) . '...'
                ]);
                
                if (!empty($apiKey)) {
                    $this->openAIService->setApiKey($apiKey);
                    $isEnabled = $this->openAIService->isEnabled();
                    Log::info('OpenAI service status', ['is_enabled' => $isEnabled]);
                    
                    if ($isEnabled) {
                        $aiResponse = $this->openAIService->researchProduct($prompt);
                        $serviceUsed = 'openai';
                        Log::info('OpenAI response received', [
                            'success' => $aiResponse['success'] ?? false,
                            'has_data' => isset($aiResponse['data']),
                            'message' => $aiResponse['message'] ?? 'No message'
                        ]);
                    } else {
                        Log::warning('OpenAI service is not enabled - API key empty or invalid');
                    }
                } else {
                    Log::warning('OpenAI API key is empty');
                }
            }

            // Fallback to Google AI if OpenAI failed or not configured
            if (is_null($aiResponse) && ($aiService === 'google' || !empty($settings['google_ai_studio_api_key']))) {
                $apiKey = $settings['google_ai_studio_api_key'] ?? '';
                Log::info('Attempting to use Google AI service as fallback', [
                    'key_length' => strlen($apiKey)
                ]);
                
                if (!empty($apiKey)) {
                    $this->googleAIStudioService->setApiKey($apiKey);
                    $isEnabled = $this->googleAIStudioService->isEnabled();
                    Log::info('Google AI service status', ['is_enabled' => $isEnabled]);
                    
                    if ($isEnabled) {
                        $aiResponse = $this->googleAIStudioService->researchProduct($prompt);
                        $serviceUsed = 'google';
                        Log::info('Google AI response received', [
                            'success' => $aiResponse['success'] ?? false,
                            'has_data' => isset($aiResponse['data'])
                        ]);
                    } else {
                        Log::warning('Google AI service is not enabled');
                    }
                } else {
                    Log::warning('Google AI API key is empty');
                }
            }

            // Check if we got a response
            if (is_null($aiResponse)) {
                Log::error('No AI service response received', [
                    'ai_service_setting' => $aiService,
                    'openai_key_available' => !empty($settings['openai_api_key']),
                    'google_key_available' => !empty($settings['google_ai_studio_api_key'])
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No AI service is configured or enabled. Please check your API keys in admin settings.'
                ]);
            }

            Log::info('AI Response processing', [
                'service_used' => $serviceUsed,
                'response_success' => $aiResponse['success'] ?? false,
                'response_keys' => array_keys($aiResponse ?? [])
            ]);

            $success = $aiResponse['success'];
            $errorMessage = $success ? null : ($aiResponse['message'] ?? 'Unknown error');

            LimitService::recordResearchUsage(
                $tenantId,
                $productId,
                $product->name,
                'full_research',
                1,
                $success,
                $errorMessage,
                $success ? $aiResponse['data'] : null
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch research data: ' . $errorMessage
                ]);
            }

            $aiData = $aiResponse['data'];

            return response()->json([
                'success' => true,
                'data' => $aiData
            ]);
        } catch (\Exception $e) {
            Log::error('Product research failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Research failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getSearchData($productName, $settings)
    {
        try {
            $googleSearchApiKey = $settings['google_search_api_key'] ?? '';
            $googleSearchEngineId = $settings['google_search_engine_id'] ?? '';
            
            if (empty($googleSearchApiKey) || empty($googleSearchEngineId)) {
                Log::info('Google Search API not configured, skipping real search data');
                return null;
            }
            
            $this->googleSearchService->setApiKey($googleSearchApiKey);
            $this->googleSearchService->setSearchEngineId($googleSearchEngineId);
            
            // Use the enhanced searchProduct method that searches across all Bangladesh websites
            $searchResponse = $this->googleSearchService->searchProduct($productName);
            
            if ($searchResponse['success'] && isset($searchResponse['data'])) {
                $searchData = $searchResponse['data'];
                
                Log::info('Enhanced Google Search API returned comprehensive market data', [
                    'total_products_found' => count($searchData['all_products'] ?? []),
                    'sites_searched' => $searchData['comprehensive_data']['total_sites_searched'] ?? 0,
                    'sites_with_results' => $searchData['comprehensive_data']['sites_with_results'] ?? 0,
                    'has_competitor_analysis' => isset($searchData['competitor_analysis']),
                    'has_price_analysis' => isset($searchData['price_analysis']),
                    'has_market_insights' => isset($searchData['market_insights'])
                ]);
                
                // Return comprehensive search data for the AI prompt
                return [
                    'all_products' => $searchData['all_products'] ?? [],
                    'competitor_analysis' => $searchData['competitor_analysis'] ?? [],
                    'price_analysis' => $searchData['price_analysis'] ?? [],
                    'market_insights' => $searchData['market_insights'] ?? [],
                    'comprehensive_data' => $searchData['comprehensive_data'] ?? [],
                    'top_sites' => $searchData['comprehensive_data']['top_sites'] ?? []
                ];
            } else {
                Log::warning('Enhanced Google Search API failed', [
                    'message' => $searchResponse['message'] ?? 'Unknown error'
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Enhanced Google Search API error', [
                'error' => $e->getMessage(),
                'product' => $productName
            ]);
            return null;
        }
    }

    private function buildResearchPrompt($productName, $productCost = null, $suggestedPrice = null, $searchData = null)
    {
        // Clean product name for URL encoding
        $cleanProductName = urlencode(str_replace([' ', '-', '_'], '+', $productName));
        
        // Build cost context if available
        $costContext = '';
        if ($productCost && $suggestedPrice) {
            $costContext = "
ACTUAL PRODUCT COST INFORMATION:
- Your Product Cost: ৳{$productCost}
- Your Suggested Sale Price: ৳{$suggestedPrice}
- Your Target Profit Margin: " . round((($suggestedPrice - $productCost) / $suggestedPrice) * 100, 1) . "%

Use this actual cost information to provide realistic profit calculations and pricing recommendations.";
        }
        
        // Build comprehensive search data context if available
        $searchContext = '';
        if ($searchData && is_array($searchData)) {
            $searchContext = "\n\nCOMPREHENSIVE REAL MARKET DATA FROM GOOGLE SEARCH API:\n";
            $searchContext .= "This data was collected from " . ($searchData['comprehensive_data']['total_sites_searched'] ?? 'multiple') . " Bangladesh websites.\n\n";
            
            // Add product listings
            if (!empty($searchData['all_products'])) {
                $searchContext .= "ACTUAL PRODUCT LISTINGS FOUND:\n";
                foreach (array_slice($searchData['all_products'], 0, 15) as $index => $product) {
                    $searchContext .= ($index + 1) . ". " . ($product['product_name'] ?? 'No name') . "\n";
                    $searchContext .= "   URL: " . ($product['product_link'] ?? 'No link') . "\n";
                    $searchContext .= "   Price: " . ($product['formatted_price'] ?? 'Price not available') . "\n";
                    $searchContext .= "   Site: " . ($product['site_name'] ?? $product['domain'] ?? 'Unknown site') . "\n";
                    if (!empty($product['description'])) {
                        $searchContext .= "   Description: " . substr($product['description'], 0, 120) . "...\n";
                    }
                    $searchContext .= "\n";
                }
            }
            
            // Add competitor analysis data
            if (!empty($searchData['competitor_analysis'])) {
                $searchContext .= "\nCOMPETITOR ANALYSIS FROM REAL DATA:\n";
                foreach ($searchData['competitor_analysis'] as $competitor) {
                    $searchContext .= "- " . ($competitor['site_name'] ?? $competitor['site']) . "\n";
                    $searchContext .= "  Products found: " . ($competitor['product_count'] ?? 0) . "\n";
                    $searchContext .= "  Price range: " . ($competitor['price_range'] ?? 'Not available') . "\n";
                    $searchContext .= "  Average price: ৳" . ($competitor['avg_price'] ?? 'N/A') . "\n";
                    $searchContext .= "  Market presence: " . ($competitor['market_presence'] ?? 'Unknown') . "%\n\n";
                }
            }
            
            // Add price analysis data
            if (!empty($searchData['price_analysis']) && $searchData['price_analysis']['found_prices']) {
                $priceData = $searchData['price_analysis'];
                $searchContext .= "\nPRICE ANALYSIS FROM REAL MARKET DATA:\n";
                $searchContext .= "- Total prices found: " . ($priceData['total_prices_found'] ?? 0) . "\n";
                $searchContext .= "- Lowest price: ৳" . ($priceData['lowest_price'] ?? 'N/A') . "\n";
                $searchContext .= "- Highest price: ৳" . ($priceData['highest_price'] ?? 'N/A') . "\n";
                $searchContext .= "- Average price: ৳" . ($priceData['average_price'] ?? 'N/A') . "\n";
                $searchContext .= "- Median price: ৳" . ($priceData['median_price'] ?? 'N/A') . "\n";
                $searchContext .= "- Price range: " . ($priceData['formatted_range'] ?? 'N/A') . "\n";
                
                if (!empty($priceData['price_distribution'])) {
                    $searchContext .= "- Price distribution:\n";
                    $searchContext .= "  Under ৳500: " . ($priceData['price_distribution']['under_500'] ?? 0) . " products\n";
                    $searchContext .= "  ৳500-1000: " . ($priceData['price_distribution']['500_to_1000'] ?? 0) . " products\n";
                    $searchContext .= "  ৳1000-2000: " . ($priceData['price_distribution']['1000_to_2000'] ?? 0) . " products\n";
                    $searchContext .= "  Over ৳2000: " . ($priceData['price_distribution']['over_2000'] ?? 0) . " products\n";
                }
            }
            
            // Add market insights
            if (!empty($searchData['market_insights'])) {
                $insights = $searchData['market_insights'];
                $searchContext .= "\nMARKET INSIGHTS FROM REAL DATA:\n";
                $searchContext .= "- Market availability: " . ($insights['market_availability'] ?? 'Unknown') . "\n";
                $searchContext .= "- Total search results: " . ($insights['total_search_results'] ?? 0) . "\n";
                $searchContext .= "- Product pages found: " . ($insights['product_pages_found'] ?? 0) . "\n";
                $searchContext .= "- Market saturation: " . ($insights['market_saturation'] ?? 'Unknown') . "\n";
                $searchContext .= "- Competition level: " . ($insights['competition_level'] ?? 'Unknown') . "\n";
                
                if (!empty($insights['search_insights']['most_active_site'])) {
                    $searchContext .= "- Most active site: " . $insights['search_insights']['most_active_site'] . "\n";
                }
            }
            
            // Add top performing sites
            if (!empty($searchData['top_sites'])) {
                $searchContext .= "\nTOP PERFORMING SITES:\n";
                foreach (array_slice($searchData['top_sites'], 0, 5) as $site) {
                    $searchContext .= "- " . ($site['site_name'] ?? $site['site']) . "\n";
                    $searchContext .= "  Product pages: " . ($site['product_pages'] ?? 0) . "\n";
                    $searchContext .= "  Products with prices: " . ($site['products_with_prices'] ?? 0) . "\n";
                    $searchContext .= "  Price coverage: " . ($site['price_coverage'] ?? 0) . "%\n\n";
                }
            }
            
            $searchContext .= "\nIMPORTANT: This is REAL market data from actual Bangladesh e-commerce websites. Use this comprehensive data to provide accurate competitor analysis, pricing insights, market positioning, and business recommendations. All prices and information above are from live searches and should be incorporated into your analysis for maximum accuracy.";
        }
        
        return <<<EOT
As a world-class e-commerce and dropshipping market research AI expert specializing in the Bangladesh market, provide an extremely comprehensive analysis for the product: '$productName'.

$costContext
$searchContext

MARKET FOCUS: This analysis is specifically for the Bangladesh market. Your target customers are Bangladeshi consumers who:
- Prefer local payment methods (bKash, Nagad, Cash on Delivery)
- Are price-sensitive but value quality
- Trust local brands and reviews
- Shop primarily on mobile devices
- Prefer Bangla language customer support

CRITICAL REQUIREMENTS:
1. Focus 80% on Bangladesh-based competitors and market data
2. Provide prices in Bangladeshi Taka (৳)
3. Include actual working product search URLs for each platform
4. Give specific advice on whether this product is profitable for dropshipping in Bangladesh
5. Provide actionable selling strategies for the Bangladesh market

COMPETITOR ANALYSIS PRIORITY:
1. Daraz.com.bd (Market leader - 40% market share)
2. Pickaboo.com (Electronics specialist - 18% market share)
3. AjkerDeal.com (Value provider - 15% market share)
4. Rokomari.com (Trusted brand - 12% market share)
5. Othoba.com, Bagdoom.com, Chaldal.com (Emerging players)

Your response must be a valid JSON object with the following comprehensive structure:

{
  "product_name": "$productName",
  "market_analysis": {
    "trend_score": 85,
    "competition_level": "High",
    "profitability_score": 78,
    "viral_potential": "Medium-High",
    "market_size": "Large",
    "growth_rate": "15% annually",
    "seasonality": "Year-round with peak in Q4",
    "market_maturity": "Growing"
  },
  "competitor_domains": [
    {
      "domain": "daraz.com.bd",
      "product_link": "https://www.daraz.com.bd/catalog/?q=$cleanProductName",
      "market_share": "40%",
      "avg_price": 1250.00,
      "trust_score": 92,
      "traffic_rank": 1,
      "strengths": ["Market leader in Bangladesh", "Local payment methods", "Fast delivery"],
      "weaknesses": ["Quality inconsistency", "Customer service issues"]
    },
    {
      "domain": "pickaboo.com",
      "product_link": "https://www.pickaboo.com/search?query=$cleanProductName",
      "market_share": "18%",
      "avg_price": 1180.00,
      "trust_score": 88,
      "traffic_rank": 2,
      "strengths": ["Electronics specialist", "Warranty support", "Physical stores"],
      "weaknesses": ["Limited product range", "Higher prices"]
    },
    {
      "domain": "ajkerdeal.com",
      "product_link": "https://ajkerdeal.com/search?q=$cleanProductName",
      "market_share": "15%",
      "avg_price": 980.00,
      "trust_score": 85,
      "traffic_rank": 3,
      "strengths": ["Competitive pricing", "Local brand", "Cash on delivery"],
      "weaknesses": ["Slower delivery", "Limited premium products"]
    },
    {
      "domain": "rokomari.com",
      "product_link": "https://www.rokomari.com/search?q=$cleanProductName",
      "market_share": "12%",
      "avg_price": 1050.00,
      "trust_score": 90,
      "traffic_rank": 4,
      "strengths": ["Trusted brand", "Good customer service", "Easy returns"],
      "weaknesses": ["Limited electronics", "Slower expansion"]
    }
  ],
  "price_analysis": {
    "lowest_price": 15.99,
    "highest_price": 89.99,
    "average_price": 34.50,
    "median_price": 32.00,
    "price_gaps": [
      {
        "gap_range": "15.99 - 25.00",
        "opportunity": "Budget segment underserved",
        "potential_volume": "High",
        "competition": "Low"
      },
      {
        "gap_range": "45.00 - 60.00",
        "opportunity": "Premium segment opportunity",
        "potential_volume": "Medium",
        "competition": "Medium"
      }
    ],
    "price_distribution": {
      "under_20": "25%",
      "20_to_40": "45%",
      "40_to_60": "20%",
      "over_60": "10%"
    },
    "recommended_pricing": {
      "cost_price": 680.00,
      "suggested_retail": 1156.00,
      "profit_margin": 41.2,
      "markup_percentage": 70,
      "bangladesh_market_fit": "Good - within acceptable price range for target market",
      "competitive_position": "Competitive pricing vs major players"
    }
  },
  "dropshipping_viability": {
    "overall_score": 78,
    "recommendation": "RECOMMENDED - Good profit potential with manageable competition",
    "key_factors": {
      "profit_margin": "Good (40%+ achievable)",
      "market_demand": "High - Popular product category in Bangladesh",
      "competition_level": "Medium - Manageable with right strategy",
      "entry_barriers": "Low - Easy to start selling"
    },
    "success_probability": "75%",
    "estimated_monthly_sales": "50-150 units",
    "break_even_timeline": "2-3 months"
  },
  "selling_strategy": {
    "recommended_platforms": [
      {
        "platform": "Facebook Marketplace",
        "priority": "High",
        "reason": "Largest user base in Bangladesh, low competition fees"
      },
      {
        "platform": "Daraz Seller Center",
        "priority": "High",
        "reason": "Established marketplace with trust factor"
      },
      {
        "platform": "Instagram Business",
        "priority": "Medium",
        "reason": "Good for visual products and younger demographics"
      }
    ],
    "marketing_tactics": [
      "Use customer reviews and testimonials in Bangla",
      "Offer cash on delivery (COD) payment option",
      "Create product demo videos in Bangla",
      "Partner with local tech reviewers/influencers",
      "Highlight warranty and after-sales support"
    ],
    "pricing_strategy": "Position at ৳1,100-1,200 range to compete with Pickaboo while maintaining good margins"
  },
  "detailed_competitors": [
    {
      "name": "Daraz Bangladesh",
      "website": "daraz.com.bd",
      "product_link": "https://daraz.com.bd/products/search-$productName",
      "market_position": "Market Leader",
      "price_range": "৳800-2000",
      "unique_selling_points": ["Largest marketplace", "Local payment methods", "Fast delivery"],
      "marketing_strategy": "Digital marketing + TV ads + Influencer partnerships",
      "social_presence": "Strong on Facebook, Instagram, YouTube",
      "customer_reviews": 4.1,
      "monthly_sales_estimate": "25,000+ units",
      "key_advantages": ["Market dominance", "Logistics network", "Brand recognition"],
      "vulnerabilities": ["Quality control issues", "Customer service complaints"]
    },
    {
      "name": "Pickaboo Electronics",
      "website": "pickaboo.com",
      "product_link": "https://pickaboo.com/search?q=$productName",
      "market_position": "Electronics Specialist",
      "price_range": "৳900-2500",
      "unique_selling_points": ["Electronics expertise", "Warranty support", "Physical stores"],
      "marketing_strategy": "SEO + Content marketing + Store promotions",
      "social_presence": "Active on Facebook and YouTube",
      "customer_reviews": 4.3,
      "monthly_sales_estimate": "8,000-12,000 units",
      "key_advantages": ["Product expertise", "Trust factor", "After-sales service"],
      "vulnerabilities": ["Limited product categories", "Higher pricing"]
    },
    {
      "name": "AjkerDeal",
      "website": "ajkerdeal.com",
      "product_link": "https://ajkerdeal.com/search?q=$productName",
      "market_position": "Value Provider",
      "price_range": "৳600-1500",
      "unique_selling_points": ["Competitive pricing", "Local brand", "Cash on delivery"],
      "marketing_strategy": "Price-focused marketing + Social media",
      "social_presence": "Growing on Facebook and Instagram",
      "customer_reviews": 3.9,
      "monthly_sales_estimate": "15,000-20,000 units",
      "key_advantages": ["Affordable pricing", "Local understanding", "Payment flexibility"],
      "vulnerabilities": ["Brand perception", "Delivery speed"]
    }
  ],
  "seo_insights": {
    "primary_keywords": [
      {
        "keyword": "best $productName",
        "search_volume": 12000,
        "difficulty": "Medium",
        "cpc": 1.25,
        "top_ranking_sites": ["amazon.com", "bestbuy.com", "walmart.com"]
      },
      {
        "keyword": "$productName reviews",
        "search_volume": 8500,
        "difficulty": "Low",
        "cpc": 0.85,
        "top_ranking_sites": ["reviewsite.com", "youtube.com", "reddit.com"]
      }
    ],
    "long_tail_keywords": [
      "affordable $productName for home",
      "professional $productName with warranty",
      "$productName comparison guide 2024"
    ],
    "content_opportunities": [
      "How to choose the right $productName",
      "$productName buying guide 2024",
      "Top 10 $productName mistakes to avoid"
    ],
    "suggested_titles": [
      "Premium $productName - Professional Quality at Best Price",
      "Top-Rated $productName with Fast Shipping & Warranty",
      "Best $productName 2024 - Customer Choice Award Winner"
    ],
    "meta_descriptions": [
      "Get the best $productName with premium quality, fast shipping, and money-back guarantee. Shop now for exclusive deals!",
      "Professional-grade $productName at wholesale prices. Top customer ratings, free shipping, and expert support included."
    ]
  },
  "profit_calculator": {
    "cost_scenarios": [
      {
        "scenario": "Basic Dropshipping",
        "product_cost": 12.00,
        "shipping_cost": 3.50,
        "platform_fees": 2.10,
        "marketing_cost": 5.00,
        "total_cost": 22.60,
        "suggested_price": 35.99,
        "profit": 13.39,
        "margin_percentage": 37.2
      },
      {
        "scenario": "Premium Positioning",
        "product_cost": 12.00,
        "shipping_cost": 3.50,
        "platform_fees": 3.60,
        "marketing_cost": 8.00,
        "total_cost": 27.10,
        "suggested_price": 49.99,
        "profit": 22.89,
        "margin_percentage": 45.8
      }
    ],
    "break_even_analysis": {
      "fixed_costs_monthly": 500,
      "variable_cost_per_unit": 22.60,
      "selling_price": 35.99,
      "break_even_units": 37,
      "break_even_revenue": 1332
    }
  },
  "social_media_targeting": {
    "facebook_instagram": {
      "target_demographics": {
        "age_range": "25-45",
        "gender": "All genders, slight female skew (55%)",
        "income": "35,000-75,000",
        "education": "College educated",
        "location": "Urban and suburban areas"
      },
      "interests": ["Home improvement", "DIY projects", "Interior design", "Technology"],
      "behaviors": ["Online shoppers", "Home owners", "Tech early adopters"],
      "ad_formats": ["Carousel ads", "Video demos", "User-generated content"],
      "estimated_cpc": 0.85,
      "estimated_cpm": 12.50,
      "conversion_rate": "2.3%"
    },
    "tiktok": {
      "target_demographics": {
        "age_range": "18-35",
        "primary_interests": ["Home hacks", "Product reviews", "Lifestyle"],
        "content_style": "Quick demos, before/after, trending sounds"
      },
      "hashtag_strategy": ["#$productName", "#homeimprovement", "#productreview", "#lifehack"],
      "estimated_cpc": 0.65,
      "viral_potential": "High"
    },
    "youtube": {
      "content_opportunities": ["Unboxing videos", "Comparison reviews", "How-to tutorials"],
      "target_channels": ["Home improvement", "Product review", "Lifestyle"],
      "estimated_cpc": 0.45,
      "long_term_value": "High"
    }
  },
  "competition_metrics": {
    "market_concentration": {
      "top_3_share": "62%",
      "hhi_index": 1850,
      "market_type": "Moderately concentrated"
    },
    "entry_barriers": {
      "capital_requirements": "Low",
      "brand_loyalty": "Medium",
      "regulatory_barriers": "Low",
      "technology_barriers": "Low",
      "overall_difficulty": "Medium"
    },
    "competitive_advantages": [
      {
        "advantage": "Price competitiveness",
        "importance": "High",
        "achievability": "Medium"
      },
      {
        "advantage": "Unique features",
        "importance": "Medium",
        "achievability": "High"
      },
      {
        "advantage": "Brand building",
        "importance": "High",
        "achievability": "Low"
      }
    ],
    "market_trends": [
      "Increasing demand for eco-friendly options",
      "Growing preference for online purchasing",
      "Rising importance of customer reviews",
      "Shift towards premium quality products"
    ]
  },
  "target_audience": {
    "primary_demographics": {
      "age_range": "22-40",
      "gender": "All genders, slight male preference for tech products",
      "income_range": "৳25,000-80,000 monthly",
      "education": "HSC to Graduate level",
      "locations": ["Dhaka", "Chittagong", "Sylhet", "Khulna", "Rajshahi"],
      "occupation": ["Students", "Young professionals", "Small business owners"]
    },
    "psychographics": {
      "interests": ["Technology", "Gaming", "Music", "Social media", "Online shopping"],
      "pain_points": ["Product authenticity concerns", "After-sales service", "Delivery reliability", "Payment security"],
      "shopping_behavior": ["Price comparison across platforms", "Read reviews extensively", "Prefer COD", "Mobile-first shopping"],
      "values": ["Value for money", "Brand reputation", "Local support", "Quick delivery"],
      "preferred_languages": ["Bangla", "English"],
      "payment_preferences": ["Cash on Delivery", "bKash", "Nagad", "Bank transfer"]
    }
  },
  "market_opportunities": {
    "underserved_segments": [
      "Budget-conscious students (৳500-800 range)",
      "Premium quality seekers in tier-2 cities",
      "Corporate bulk buyers for offices"
    ],
    "geographic_opportunities": [
      "Chittagong tech market expansion",
      "Sylhet university student segment",
      "Khulna emerging middle class"
    ],
    "product_variations": [
      "Bangla voice command version",
      "Local warranty and support",
      "Bundle with mobile accessories"
    ],
    "seasonal_opportunities": [
      "Eid festival season (high demand)",
      "Back-to-school period (students)",
      "Winter season (indoor entertainment)"
    ]
  },
  "risk_analysis": {
    "market_risks": [
      "Bangladesh economic instability affecting purchasing power",
      "BDT currency fluctuation impacting import costs",
      "Political unrest disrupting e-commerce operations"
    ],
    "competition_risks": [
      "Local brands offering similar products at lower prices",
      "Established retailers with better distribution networks",
      "Customer preference for physical store purchases"
    ],
    "operational_risks": [
      "Import duty changes affecting product costs",
      "Dhaka traffic causing delivery delays",
      "Limited payment gateway options for customers",
      "Quality complaints due to lack of local warranty"
    ],
    "mitigation_strategies": [
      "Build strong customer service in Bengali language",
      "Partner with local delivery services",
      "Offer flexible payment options (bKash, Nagad, cash on delivery)",
      "Maintain buffer stock to avoid supply disruptions"
    ]
  }
}

IMPORTANT: Provide realistic, accurate data based on actual market knowledge. Use real website domains, realistic prices, and genuine market insights. All numerical values should be realistic estimates based on current market conditions. This data will be used for actual business decisions, so accuracy is crucial.

Return only the valid JSON object with no additional text or explanations.
EOT;
    }

    public function priceComparison(Request $request, $productId)
    {
        // Use the same research data for price comparison
        return $this->researchProduct($request, $productId);
    }

    public function seoAnalysis(Request $request, $productId)
    {
        // Use the same research data for SEO analysis
        return $this->researchProduct($request, $productId);
    }

    public function competitorAnalysis(Request $request, $productId)
    {
        // Use the same research data for competitor analysis
        return $this->researchProduct($request, $productId);
    }

    public function searchProduct(Request $request)
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required.'
            ]);
        }

        try {
            $tenantId = tenant('id');
            $canResearch = LimitService::canResearch($tenantId);
            if (!$canResearch['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $canResearch['message'],
                    'reason' => $canResearch['reason'],
                    'upgrade_message' => LimitService::getUpgradeMessage($canResearch['reason']),
                    'limit_reached' => true
                ]);
            }

            // Normalize query for consistent caching
            $normalizedQuery = strtolower(trim($query));
            $searchHash = md5($normalizedQuery);
            
            // Check cache first
            $cachedResult = $this->getCachedSearchResult($searchHash);
            if ($cachedResult) {
                Log::info('Search result found in cache', [
                    'query' => $query,
                    'hash' => $searchHash,
                    'cache_id' => $cachedResult['id']
                ]);
                
                // Update cache usage statistics
                $this->updateCacheUsage($cachedResult['id']);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'query' => $query,
                        'total_websites' => $cachedResult['total_websites'],
                        'websites' => json_decode($cachedResult['search_results'], true),
                        'search_summary' => json_decode($cachedResult['search_summary'], true),
                        'cached' => true,
                        'last_updated' => $cachedResult['updated_at']
                    ]
                ]);
            }

            // Cache miss - proceed with API call
            Log::info('Cache miss - calling Google Search API', [
                'query' => $query,
                'hash' => $searchHash
            ]);

            // Get settings for Google Search API
            $settings = DB::connection('mysql')->table('dropshipping_settings')->pluck('value', 'key');
            
            // Get comprehensive search data using Google Search API
            $searchData = $this->getSearchData($query, $settings);
            
            if (!$searchData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Search API is not configured or failed to get results.'
                ]);
            }

            // Extract top 50 websites with product links
            $websites = $this->extractTop50Websites($searchData);
            
            // Prepare search summary
            $searchSummary = [
                'total_sites_searched' => $searchData['comprehensive_data']['total_sites_searched'] ?? 0,
                'sites_with_results' => $searchData['comprehensive_data']['sites_with_results'] ?? 0,
                'total_products_found' => count($searchData['all_products'] ?? [])
            ];
            
            // Cache the results
            $this->cacheSearchResult($normalizedQuery, $searchHash, $websites, $searchSummary);
            
            // Record usage
            LimitService::recordResearchUsage(
                $tenantId,
                null, // No specific product ID for general search
                $query,
                'product_search',
                1,
                true,
                null,
                ['websites_found' => count($websites), 'query' => $query]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'total_websites' => count($websites),
                    'websites' => $websites,
                    'search_summary' => $searchSummary,
                    'cached' => false
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Extract top 50 websites with product links from search data
     */
    private function extractTop50Websites($searchData)
    {
        $websites = [];
        $seenDomains = [];
        
        // Process all products to extract unique websites
        $allProducts = $searchData['all_products'] ?? [];
        
        foreach ($allProducts as $product) {
            $domain = $product['domain'] ?? '';
            $siteName = $product['site_name'] ?? $domain;
            $productLink = $product['product_link'] ?? '';
            
            if (empty($domain) || empty($productLink)) {
                continue;
            }
            
            // Skip if we already have this domain
            if (in_array($domain, $seenDomains)) {
                continue;
            }
            
            $seenDomains[] = $domain;
            
            $websites[] = [
                'rank' => count($websites) + 1,
                'domain' => $domain,
                'site_name' => $siteName,
                'product_link' => $productLink,
                'product_name' => $product['product_name'] ?? 'Product',
                'price' => $product['formatted_price'] ?? 'Price not available',
                'description' => isset($product['description']) ? substr($product['description'], 0, 150) . '...' : '',
                'relevance_score' => $product['relevance_score'] ?? 0,
                'is_bangladeshi' => $this->isBangladeshiSite($domain)
            ];
            
            // Stop at 50 websites
            if (count($websites) >= 50) {
                break;
            }
        }
        
        // If we don't have 50 websites from products, try to get more from competitor analysis
        if (count($websites) < 50) {
            $competitorAnalysis = $searchData['competitor_analysis'] ?? [];
            
            foreach ($competitorAnalysis as $competitor) {
                $domain = $competitor['site'] ?? '';
                $siteName = $competitor['site_name'] ?? $domain;
                
                if (empty($domain) || in_array($domain, $seenDomains)) {
                    continue;
                }
                
                $seenDomains[] = $domain;
                
                $websites[] = [
                    'rank' => count($websites) + 1,
                    'domain' => $domain,
                    'site_name' => $siteName,
                    'product_link' => "https://{$domain}/search?q=" . urlencode($searchData['product_name'] ?? ''),
                    'product_name' => 'Search Results',
                    'price' => $competitor['price_range'] ?? 'Various prices',
                    'description' => "Found {$competitor['product_count']} products on this site",
                    'relevance_score' => 80,
                    'is_bangladeshi' => $this->isBangladeshiSite($domain)
                ];
                
                if (count($websites) >= 50) {
                    break;
                }
            }
        }
        
        // Sort by relevance score and Bangladeshi sites first
        usort($websites, function($a, $b) {
            // Bangladeshi sites get priority
            if ($a['is_bangladeshi'] && !$b['is_bangladeshi']) {
                return -1;
            }
            if (!$a['is_bangladeshi'] && $b['is_bangladeshi']) {
                return 1;
            }
            
            // Then sort by relevance score
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Re-rank after sorting
        foreach ($websites as $index => &$website) {
            $website['rank'] = $index + 1;
        }
        
        return array_slice($websites, 0, 50);
    }
    
    /**
     * Check if a domain is a Bangladeshi site
     */
    private function isBangladeshiSite($domain)
    {
        $bangladeshiSites = [
            'daraz.com.bd', 'pickaboo.com', 'ajkerdeal.com', 'othoba.com',
            'rokomari.com', 'bagdoom.com', 'chaldal.com', 'shajgoj.com',
            'bikroy.com', 'clickbd.com', 'startech.com.bd', 'techland.com.bd',
            'ryanscomputers.com', 'ultratech.com.bd', 'bdstall.com',
            'aarong.com', 'yellow.com.bd', 'boibazar.com', 'pathokpoint.com',
            'shwapno.com', 'ekhanei.com', 'cellbazaar.com', 'bdshop.com'
        ];
        
        foreach ($bangladeshiSites as $bdSite) {
            if (strpos($domain, $bdSite) !== false) {
                return true;
            }
        }
        
        return strpos($domain, '.bd') !== false;
    }

    /**
     * Get cached search result by hash
     */
    private function getCachedSearchResult($searchHash)
    {
        try {
            $cached = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('search_hash', $searchHash)
                ->where('is_active', 1)
                ->first();
            
            if ($cached) {
                return [
                    'id' => $cached->id,
                    'search_results' => $cached->search_results,
                    'total_websites' => $cached->total_websites,
                    'search_summary' => $cached->search_summary,
                    'updated_at' => $cached->updated_at
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached search result', [
                'hash' => $searchHash,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache search result
     */
    private function cacheSearchResult($query, $searchHash, $websites, $searchSummary)
    {
        try {
            DB::connection('mysql')->table('dropshipping_search_cache')->updateOrInsert(
                ['search_hash' => $searchHash],
                [
                    'search_query' => $query,
                    'search_hash' => $searchHash,
                    'search_results' => json_encode($websites),
                    'total_websites' => count($websites),
                    'search_summary' => json_encode($searchSummary),
                    'is_active' => 1,
                    'last_used_at' => now(),
                    'usage_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            Log::info('Search result cached successfully', [
                'query' => $query,
                'hash' => $searchHash,
                'websites_count' => count($websites)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cache search result', [
                'query' => $query,
                'hash' => $searchHash,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update cache usage statistics
     */
    private function updateCacheUsage($cacheId)
    {
        try {
            DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $cacheId)
                ->increment('usage_count');
            
            DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $cacheId)
                ->update(['last_used_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Failed to update cache usage', [
                'cache_id' => $cacheId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
 
=======
<?php

namespace Plugin\Dropshipping\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Plugin\Dropshipping\Services\SerperService;

class ProductResearchController extends Controller
{
    protected $serperService;

    public function __construct(SerperService $serperService)
    {
        $this->serperService = $serperService;
    }

    /**
     * Get comprehensive product research data
     */
    public function researchProduct(Request $request, $productId)
    {
        try {
            // Check if Serper is enabled
            if (!$this->serperService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product research feature is not available. Please contact administrator to configure Serper.dev API.'
                ]);
            }

            // Get product details
            $product = DB::connection('mysql')->table('dropshipping_products')
                ->where('id', $productId)
                ->where('status', 'publish')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }

            // Get comprehensive research data
            $researchData = $this->serperService->getProductResearch($product->name);

            if (!$researchData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch research data: ' . $researchData['message'] ?? 'Unknown error'
                ]);
            }

            // Add product context
            $researchData['data']['original_product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->regular_price,
                'sale_price' => $product->sale_price,
                'description' => $product->short_description,
                'sku' => $product->sku
            ];

            return response()->json([
                'success' => true,
                'data' => $researchData['data']
            ]);

        } catch (\Exception $e) {
            Log::error('Product research failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Research failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get price comparison data
     */
    public function priceComparison(Request $request, $productId)
    {
        try {
            if (!$this->serperService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Price comparison feature is not available.'
                ]);
            }

            $product = DB::connection('mysql')->table('dropshipping_products')
                ->where('id', $productId)
                ->where('status', 'publish')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }

            // Get shopping results for price comparison
            $shoppingResults = $this->serperService->searchShopping($product->name, 15);

            if (!$shoppingResults['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch price data.'
                ]);
            }

            // Calculate price analysis
            $priceAnalysis = [];
            if (!empty($shoppingResults['data'])) {
                $prices = array_filter(array_column($shoppingResults['data'], 'price'));
                if (!empty($prices)) {
                    sort($prices);
                    $priceAnalysis = [
                        'min_price' => min($prices),
                        'max_price' => max($prices),
                        'avg_price' => round(array_sum($prices) / count($prices), 2),
                        'your_price' => (float) $product->regular_price,
                        'price_position' => $this->calculatePricePosition($product->regular_price, $prices),
                        'savings_potential' => max($prices) - (float) $product->regular_price,
                        'competitor_count' => count($prices)
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'shopping_results' => $shoppingResults['data'],
                    'price_analysis' => $priceAnalysis,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'current_price' => $product->regular_price,
                        'sale_price' => $product->sale_price
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Price comparison failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Price comparison failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get SEO analysis data
     */
    public function seoAnalysis(Request $request, $productId)
    {
        try {
            if (!$this->serperService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SEO analysis feature is not available.'
                ]);
            }

            $product = DB::connection('mysql')->table('dropshipping_products')
                ->where('id', $productId)
                ->where('status', 'publish')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }

            // Get search results for SEO analysis
            $searchResults = $this->serperService->searchProduct($product->name, 15);

            if (!$searchResults['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch SEO data.'
                ]);
            }

            // Analyze current product SEO
            $currentSeoAnalysis = $this->analyzeCurrentProductSeo($product);
            
            // Extract SEO insights from competitors
            $competitorSeoInsights = $this->extractSeoInsights($searchResults['data']);
            
            // Generate SEO recommendations
            $seoRecommendations = $this->generateSeoRecommendations($product, $searchResults['data']);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_seo' => $currentSeoAnalysis,
                    'competitor_insights' => $competitorSeoInsights,
                    'recommendations' => $seoRecommendations,
                    'suggested_titles' => $this->generateTitleSuggestions($product->name, $searchResults['data']),
                    'suggested_descriptions' => $this->generateDescriptionSuggestions($product->name, $searchResults['data']),
                    'keywords' => $this->extractKeywords($searchResults['data'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('SEO analysis failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SEO analysis failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get competitor analysis data
     */
    public function competitorAnalysis(Request $request, $productId)
    {
        try {
            if (!$this->serperService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Competitor analysis feature is not available.'
                ]);
            }

            $product = DB::connection('mysql')->table('dropshipping_products')
                ->where('id', $productId)
                ->where('status', 'publish')
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }

            // Get both search and shopping results
            $searchResults = $this->serperService->searchProduct($product->name, 20);
            $shoppingResults = $this->serperService->searchShopping($product->name, 20);

            $competitorData = [
                'websites' => [],
                'pricing_strategies' => [],
                'content_strategies' => [],
                'market_leaders' => []
            ];

            if ($searchResults['success'] && $shoppingResults['success']) {
                $competitorData = $this->analyzeCompetitors(
                    $searchResults['data'], 
                    $shoppingResults['data'], 
                    $product
                );
            }

            return response()->json([
                'success' => true,
                'data' => $competitorData
            ]);

        } catch (\Exception $e) {
            Log::error('Competitor analysis failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Competitor analysis failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search for products by custom query
     */
    public function searchProduct(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|max:255',
                'type' => 'in:search,shopping,both'
            ]);

            if (!$this->serperService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product search feature is not available.'
                ]);
            }

            $query = $request->input('query');
            $type = $request->input('type', 'both');
            $limit = min($request->input('limit', 10), 20);

            $results = [];

            if ($type === 'search' || $type === 'both') {
                $searchResults = $this->serperService->searchProduct($query, $limit);
                if ($searchResults['success']) {
                    $results['search_results'] = $searchResults['data'];
                }
            }

            if ($type === 'shopping' || $type === 'both') {
                $shoppingResults = $this->serperService->searchShopping($query, $limit);
                if ($shoppingResults['success']) {
                    $results['shopping_results'] = $shoppingResults['data'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => $query
            ]);

        } catch (\Exception $e) {
            Log::error('Product search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper method to calculate price position
     */
    private function calculatePricePosition($yourPrice, $competitorPrices)
    {
        sort($competitorPrices);
        $position = 0;
        
        foreach ($competitorPrices as $index => $price) {
            if ($yourPrice <= $price) {
                $position = $index + 1;
                break;
            }
        }
        
        if ($position === 0) {
            $position = count($competitorPrices) + 1;
        }
        
        $total = count($competitorPrices) + 1;
        $percentile = (($total - $position) / $total) * 100;
        
        return [
            'position' => $position,
            'total_competitors' => count($competitorPrices),
            'percentile' => round($percentile, 1),
            'is_competitive' => $percentile >= 50
        ];
    }

    /**
     * Analyze current product SEO
     */
    private function analyzeCurrentProductSeo($product)
    {
        $seo = [
            'title_length' => strlen($product->name),
            'title_score' => 0,
            'description_length' => strlen($product->short_description ?? ''),
            'description_score' => 0,
            'has_sku' => !empty($product->sku),
            'has_price' => !empty($product->regular_price),
            'recommendations' => []
        ];

        // Title analysis
        if ($seo['title_length'] < 30) {
            $seo['recommendations'][] = 'Title is too short. Consider expanding with descriptive keywords.';
            $seo['title_score'] = 3;
        } elseif ($seo['title_length'] > 60) {
            $seo['recommendations'][] = 'Title is too long. Consider shortening for better search display.';
            $seo['title_score'] = 6;
        } else {
            $seo['title_score'] = 10;
        }

        // Description analysis
        if ($seo['description_length'] < 120) {
            $seo['recommendations'][] = 'Description is too short. Add more details for better SEO.';
            $seo['description_score'] = 4;
        } elseif ($seo['description_length'] > 160) {
            $seo['recommendations'][] = 'Description is too long for meta description. Consider shortening.';
            $seo['description_score'] = 7;
        } else {
            $seo['description_score'] = 10;
        }

        // Overall score
        $seo['overall_score'] = round(($seo['title_score'] + $seo['description_score']) / 2, 1);

        return $seo;
    }

    /**
     * Extract SEO insights from competitor data
     */
    private function extractSeoInsights($searchResults)
    {
        $insights = [
            'avg_title_length' => 0,
            'common_title_patterns' => [],
            'common_keywords' => [],
            'title_strategies' => []
        ];

        if (empty($searchResults)) {
            return $insights;
        }

        $titleLengths = [];
        $allTitles = [];

        foreach ($searchResults as $result) {
            $titleLengths[] = strlen($result['title']);
            $allTitles[] = strtolower($result['title']);
        }

        $insights['avg_title_length'] = round(array_sum($titleLengths) / count($titleLengths), 1);

        // Find common patterns
        $patterns = [
            'pipe_separator' => 0,
            'dash_separator' => 0,
            'year_mentioned' => 0,
            'quality_words' => 0
        ];
        foreach ($allTitles as $title) {
            if (strpos($title, '|') !== false) $patterns['pipe_separator']++;
            if (strpos($title, '-') !== false) $patterns['dash_separator']++;
            if (preg_match('/\d{4}/', $title)) $patterns['year_mentioned']++;
            if (preg_match('/best|top|premium/i', $title)) $patterns['quality_words']++;
        }

        $insights['common_title_patterns'] = $patterns;
        $insights['common_keywords'] = $this->extractCommonWords($allTitles);

        return $insights;
    }

    /**
     * Generate SEO recommendations
     */
    private function generateSeoRecommendations($product, $searchResults)
    {
        $recommendations = [];

        // Analyze competitor title lengths
        $titleLengths = array_map(function($result) {
            return strlen($result['title']);
        }, $searchResults);

        $avgLength = array_sum($titleLengths) / count($titleLengths);
        $currentLength = strlen($product->name);

        if ($currentLength < $avgLength - 10) {
            $recommendations[] = [
                'type' => 'title',
                'priority' => 'high',
                'message' => 'Your title is shorter than competitors. Consider adding descriptive keywords.',
                'action' => 'Expand title with relevant keywords'
            ];
        }

        if ($currentLength > $avgLength + 15) {
            $recommendations[] = [
                'type' => 'title',
                'priority' => 'medium',
                'message' => 'Your title is longer than competitors. Consider shortening for better readability.',
                'action' => 'Optimize title length'
            ];
        }

        // Check for common SEO elements
        $hasYear = preg_match('/\d{4}/', $product->name);
        $hasQualityWords = preg_match('/best|top|premium|quality/i', $product->name);

        if (!$hasYear) {
            $recommendations[] = [
                'type' => 'keywords',
                'priority' => 'low',
                'message' => 'Consider adding current year to show freshness.',
                'action' => 'Add "2024" to title'
            ];
        }

        if (!$hasQualityWords) {
            $recommendations[] = [
                'type' => 'keywords',
                'priority' => 'medium',
                'message' => 'Add quality indicators like "Premium", "Best", or "Top-rated".',
                'action' => 'Include quality keywords'
            ];
        }

        return $recommendations;
    }

    /**
     * Generate title suggestions
     */
    private function generateTitleSuggestions($productName, $searchResults)
    {
        $suggestions = [];
        $commonWords = $this->extractCommonWords(array_column($searchResults, 'title'));

        $suggestions[] = $productName . " - Premium Quality 2024";
        $suggestions[] = "Best " . $productName . " | Top Rated";
        $suggestions[] = $productName . " - Professional Grade";
        $suggestions[] = "Premium " . $productName . " Collection";
        $suggestions[] = $productName . " | Fast Shipping";

        // Add suggestions based on common words
        foreach (array_slice($commonWords, 0, 3) as $word) {
            if (stripos($productName, $word) === false) {
                $suggestions[] = $productName . " " . ucfirst($word);
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Generate description suggestions
     */
    private function generateDescriptionSuggestions($productName, $searchResults)
    {
        $suggestions = [];

        $suggestions[] = "Shop premium {$productName} with fast shipping and excellent customer service. Best prices guaranteed.";
        $suggestions[] = "High-quality {$productName} available now. Compare prices, read reviews, and buy with confidence.";
        $suggestions[] = "Find the best {$productName} deals online. Top-rated products with money-back guarantee.";
        $suggestions[] = "Professional {$productName} at competitive prices. Free shipping on orders over $50.";

        return $suggestions;
    }

    /**
     * Extract keywords from search results
     */
    private function extractKeywords($searchResults)
    {
        $allText = '';
        foreach ($searchResults as $result) {
            $allText .= ' ' . $result['title'] . ' ' . $result['snippet'];
        }

        $words = preg_split('/\s+/', strtolower($allText));
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords) && preg_match('/^[a-z]+$/', $word);
        });

        $wordCount = array_count_values($words);
        arsort($wordCount);

        return array_slice(array_keys($wordCount), 0, 15);
    }

    /**
     * Extract common words from titles
     */
    private function extractCommonWords($titles)
    {
        $allWords = [];
        foreach ($titles as $title) {
            $words = preg_split('/\s+/', strtolower($title));
            $allWords = array_merge($allWords, $words);
        }

        $wordCount = array_count_values($allWords);
        arsort($wordCount);

        return array_slice(array_keys($wordCount), 0, 10);
    }

    /**
     * Analyze competitors
     */
    private function analyzeCompetitors($searchResults, $shoppingResults, $product)
    {
        $competitors = [
            'websites' => [],
            'pricing_strategies' => [],
            'content_strategies' => [],
            'market_leaders' => []
        ];

        // Analyze websites from search results
        $websiteFrequency = [];
        foreach ($searchResults as $result) {
            $domain = $result['domain'];
            if (!isset($websiteFrequency[$domain])) {
                $websiteFrequency[$domain] = 0;
            }
            $websiteFrequency[$domain]++;
        }

        arsort($websiteFrequency);
        $competitors['market_leaders'] = array_slice(array_keys($websiteFrequency), 0, 5);

        // Analyze pricing strategies from shopping results
        if (!empty($shoppingResults)) {
            $prices = array_filter(array_column($shoppingResults, 'price'));
            if (!empty($prices)) {
                sort($prices);
                $competitors['pricing_strategies'] = [
                    'lowest_price' => min($prices),
                    'highest_price' => max($prices),
                    'average_price' => round(array_sum($prices) / count($prices), 2),
                    'your_position' => $this->calculatePricePosition($product->regular_price, $prices)
                ];
            }
        }

        return $competitors;
    }
} 
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
