<?php

namespace Plugin\Dropshipping\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $apiKey;
    private $endpoint = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-3.5-turbo';
    private $timeout = 120;

    public function __construct()
    {
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        $this->apiKey = $settings['openai_api_key'] ?? '';
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function isEnabled()
    {
        return !empty($this->apiKey);
    }

    public function researchProduct($prompt)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'OpenAI API is not configured.'
            ];
        }

        try {
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
            ];

            Log::info('OpenAI API Request', [
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($this->endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('OpenAI API Response received', [
                    'has_choices' => isset($data['choices']),
                    'choices_count' => isset($data['choices']) ? count($data['choices']) : 0
                ]);

                // Extract the content from OpenAI response
                if (isset($data['choices'][0]['message']['content'])) {
                    $content = $data['choices'][0]['message']['content'];
                    Log::info('OpenAI Content received', [
                        'content_length' => strlen($content),
                        'content_preview' => substr($content, 0, 200)
                    ]);

                    // Try to parse the JSON content
                    $parsedContent = $this->parseResearchContent($content);
                    
                    if ($parsedContent) {
                        return [
                            'success' => true,
                            'data' => $parsedContent
                        ];
                    } else {
                        Log::error('Failed to parse OpenAI response content', [
                            'content' => $content
                        ]);
                        return [
                            'success' => false,
                            'message' => 'Failed to parse AI response. The AI returned invalid JSON format.'
                        ];
                    }
                } else {
                    Log::error('OpenAI response missing expected content', [
                        'response_structure' => array_keys($data)
                    ]);
                    return [
                        'success' => false,
                        'message' => 'AI response missing expected content structure.'
                    ];
                }
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'API request failed: ' . $response->status() . ' - ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
                'prompt' => substr($prompt, 0, 200)
            ]);
            return [
                'success' => false,
                'message' => 'API request exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse and validate the research content from OpenAI
     */
    private function parseResearchContent($content)
    {
        try {
            // Clean the content - remove any markdown formatting or extra text
            $content = trim($content);
            
            // Remove markdown code blocks if present
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            // Try to decode JSON
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 500)
                ]);
                return null;
            }

            // Log the decoded data structure for debugging
            Log::info('AI Response Structure Debug', [
                'keys' => array_keys($decoded),
                'has_price_analysis' => isset($decoded['price_analysis']),
                'has_target_audience' => isset($decoded['target_audience']),
                'has_seo_insights' => isset($decoded['seo_insights']),
                'has_competitor_domains' => isset($decoded['competitor_domains']),
                'sample_data' => array_slice($decoded, 0, 3, true) // First 3 keys with data
            ]);

            // Validate required structure and transform to expected format
            return $this->transformToExpectedFormat($decoded);
            
        } catch (\Exception $e) {
            Log::error('Error parsing research content', [
                'error' => $e->getMessage(),
                'content_preview' => substr($content, 0, 200)
            ]);
            return null;
        }
    }

    /**
     * Transform the parsed JSON to the format expected by the frontend
     */
    private function transformToExpectedFormat($data)
    {
        try {
            Log::info('Starting data transformation', [
                'input_keys' => array_keys($data),
                'product_name' => $data['product_name'] ?? 'Not found'
            ]);

            // Create the comprehensive structure based on the new enhanced data
            $transformed = [
                'product_name' => $data['product_name'] ?? 'Unknown Product',
                
                // Enhanced market analysis
                'market_analysis' => $data['market_analysis'] ?? [
                    'trend_score' => 0,
                    'competition_level' => 'Unknown',
                    'profitability_score' => 0,
                    'viral_potential' => 'Unknown',
                    'market_size' => 'Unknown',
                    'growth_rate' => 'Unknown',
                    'seasonality' => 'Unknown',
                    'market_maturity' => 'Unknown'
                ],
                
                // Competitor domains with detailed analysis
                'competitor_domains' => $data['competitor_domains'] ?? [],
                
                // Enhanced price analysis with gaps
                'price_analysis' => [
                    'min_price' => $data['price_analysis']['lowest_price'] ?? $data['price_analysis']['min_price'] ?? 0,
                    'max_price' => $data['price_analysis']['highest_price'] ?? $data['price_analysis']['max_price'] ?? 0,
                    'avg_price' => $data['price_analysis']['average_price'] ?? $data['price_analysis']['avg_price'] ?? 0,
                    'median_price' => $data['price_analysis']['median_price'] ?? 0,
                    'total_sources' => count($data['competitor_domains'] ?? []),
                    'price_gaps' => $data['price_analysis']['price_gaps'] ?? [],
                    'price_distribution' => $data['price_analysis']['price_distribution'] ?? [],
                    'recommended_pricing' => $data['price_analysis']['recommended_pricing'] ?? []
                ],
                
                // Detailed competitors analysis
                'detailed_competitors' => $data['detailed_competitors'] ?? [],
                
                // Enhanced SEO insights
                'seo_insights' => [
                    'primary_keywords' => $data['seo_insights']['primary_keywords'] ?? [],
                    'long_tail_keywords' => $data['seo_insights']['long_tail_keywords'] ?? [],
                    'content_opportunities' => $data['seo_insights']['content_opportunities'] ?? [],
                    'common_keywords' => $this->extractKeywordsFromSEO($data['seo_insights'] ?? [])
                ],
                'suggested_titles' => $data['seo_insights']['suggested_titles'] ?? $this->generateSuggestedTitles($data),
                'meta_descriptions' => $data['seo_insights']['meta_descriptions'] ?? $this->generateMetaDescriptions($data),
                
                // Profit calculator
                'profit_calculator' => $data['profit_calculator'] ?? [
                    'cost_scenarios' => [],
                    'break_even_analysis' => []
                ],
                
                // Social media targeting
                'social_media_targeting' => [
                    'facebook_instagram' => $data['social_media_targeting']['facebook_instagram'] ?? $this->generateFacebookInstagramTargeting($data),
                    'tiktok' => $data['social_media_targeting']['tiktok'] ?? [],
                    'youtube' => $data['social_media_targeting']['youtube'] ?? []
                ],
                
                // Competition metrics
                'competition_metrics' => [
                    'market_concentration' => $data['competition_metrics']['market_concentration'] ?? $this->generateMarketConcentration($data),
                    'entry_barriers' => $data['competition_metrics']['entry_barriers'] ?? $this->generateEntryBarriers($data),
                    'competitive_advantages' => $data['competition_metrics']['competitive_advantages'] ?? [],
                    'market_trends' => $data['competition_metrics']['market_trends'] ?? []
                ],
                
                // Target audience (enhanced)
                'target_audience' => [
                    'primary_demographics' => [
                        'age_range' => $data['target_audience']['primary_demographics']['age_range'] ?? 'Unknown',
                        'gender' => $data['target_audience']['primary_demographics']['gender'] ?? 'Unknown',
                        'income_range' => $data['target_audience']['primary_demographics']['income_range'] ?? 'Unknown',
                        'education' => $data['target_audience']['primary_demographics']['education'] ?? 'Unknown',
                        'locations' => $data['target_audience']['primary_demographics']['locations'] ?? [],
                        'occupation' => $data['target_audience']['primary_demographics']['occupation'] ?? [],
                        'top_countries' => $data['target_audience']['primary_demographics']['locations'] ?? []
                    ],
                    'psychographics' => [
                        'interests' => $data['target_audience']['psychographics']['interests'] ?? [],
                        'pain_points' => $data['target_audience']['psychographics']['pain_points'] ?? [],
                        'shopping_behavior' => $data['target_audience']['psychographics']['shopping_behavior'] ?? [],
                        'values' => $data['target_audience']['psychographics']['values'] ?? [],
                        'preferred_languages' => $data['target_audience']['psychographics']['preferred_languages'] ?? [],
                        'payment_preferences' => $data['target_audience']['psychographics']['payment_preferences'] ?? []
                    ]
                ],
                
                // Market opportunities
                'market_opportunities' => [
                    'underserved_segments' => $data['market_opportunities']['underserved_segments'] ?? [],
                    'geographic_opportunities' => $data['market_opportunities']['geographic_opportunities'] ?? [],
                    'product_variations' => $data['market_opportunities']['product_variations'] ?? [],
                    'seasonal_opportunities' => $data['market_opportunities']['seasonal_opportunities'] ?? []
                ],
                
                // Risk analysis
                'risk_analysis' => [
                    'market_risks' => $data['risk_analysis']['market_risks'] ?? [],
                    'competition_risks' => $data['risk_analysis']['competition_risks'] ?? [],
                    'operational_risks' => $data['risk_analysis']['operational_risks'] ?? [],
                    'mitigation_strategies' => $data['risk_analysis']['mitigation_strategies'] ?? []
                ],
                
                // Dropshipping viability analysis (enhanced)
                'dropshipping_analysis' => [
                    'viability_score' => $data['market_analysis']['profitability_score'] ?? 0,
                    'viability_level' => $this->getViabilityLevel($data['market_analysis']['profitability_score'] ?? 0),
                    'competition_level' => $data['market_analysis']['competition_level'] ?? 'Unknown',
                    'profit_potential' => $this->getProfitPotential($data['price_analysis']['recommended_pricing']['profit_margin'] ?? 0),
                    'market_saturation' => $this->getMarketSaturation($data['market_analysis']['competition_level'] ?? 'Unknown'),
                    'suggested_markup' => ($data['price_analysis']['recommended_pricing']['markup_percentage'] ?? 0) . '%',
                    'pros' => $this->generateEnhancedPros($data),
                    'cons' => $this->generateEnhancedCons($data),
                    'recommendations' => $this->generateEnhancedRecommendations($data),
                    'market_size' => $data['market_analysis']['market_size'] ?? 'Unknown',
                    'growth_rate' => $data['market_analysis']['growth_rate'] ?? 'Unknown',
                    'seasonality' => $data['market_analysis']['seasonality'] ?? 'Unknown'
                ],
                
                // Shopping results (from competitor domains)
                'shopping_results' => $this->transformCompetitorDomainsToShoppingResults($data['competitor_domains'] ?? []),
                
                // Search results (from competitor domains)
                'search_results' => $this->transformToSearchResults($data['competitor_domains'] ?? []),
                
                // Competitor websites
                'competitor_websites' => $this->transformToCompetitorWebsites($data['competitor_domains'] ?? []),
                
                // Product images (placeholder for now)
                'product_images' => [],
                
                // Legacy fields for backward compatibility
                'top_10_related_products' => $this->generateRelatedProducts($data),
                'pricing_analysis' => [
                    'average_market_price' => $data['price_analysis']['average_price'] ?? 0,
                    'suggested_retail_price' => $data['price_analysis']['recommended_pricing']['suggested_retail'] ?? 0,
                    'estimated_profit_margin' => $data['price_analysis']['recommended_pricing']['profit_margin'] ?? 0
                ],
                'market_gaps' => [
                    'summary' => $this->generateMarketGapsSummary($data),
                    'opportunities' => $data['market_opportunities']['underserved_segments'] ?? []
                ]
            ];

            Log::info('Successfully transformed comprehensive OpenAI response', [
                'product_name' => $transformed['product_name'],
                'viability_score' => $transformed['dropshipping_analysis']['viability_score'],
                'competitor_domains_count' => count($transformed['competitor_domains']),
                'price_gaps_count' => count($transformed['price_analysis']['price_gaps']),
                'seo_keywords_count' => count($transformed['seo_insights']['primary_keywords'])
            ]);

            return $transformed;
            
        } catch (\Exception $e) {
            Log::error('Error transforming comprehensive data format', [
                'error' => $e->getMessage(),
                'data_keys' => array_keys($data ?? [])
            ]);
            return null;
        }
    }

    private function getViabilityLevel($score)
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Poor';
    }

    private function getProfitPotential($margin)
    {
        if ($margin >= 50) return 'High';
        if ($margin >= 30) return 'Medium';
        return 'Low';
    }

    private function getMarketSaturation($competition)
    {
        $competition = strtolower($competition);
        if (strpos($competition, 'high') !== false) return 'High';
        if (strpos($competition, 'medium') !== false) return 'Medium';
        return 'Low';
    }

    // Enhanced methods for comprehensive data
    private function generateEnhancedPros($data)
    {
        $pros = [];
        
        if (($data['market_analysis']['profitability_score'] ?? 0) > 60) {
            $pros[] = 'High profit potential with ' . ($data['market_analysis']['profitability_score'] ?? 0) . '% score';
        }
        
        if (($data['market_analysis']['trend_score'] ?? 0) > 70) {
            $pros[] = 'Trending product with growing demand (' . ($data['market_analysis']['trend_score'] ?? 0) . '% trend score)';
        }
        
        if (($data['market_analysis']['market_size'] ?? '') === 'Large') {
            $pros[] = 'Large market size with significant opportunity';
        }
        
        if (count($data['target_audience']['primary_demographics']['top_countries'] ?? []) > 3) {
            $pros[] = 'Global market appeal across multiple countries';
        }
        
        if (count($data['price_analysis']['price_gaps'] ?? []) > 0) {
            $pros[] = 'Identified price gaps for competitive advantage';
        }
        
        if (($data['social_media_targeting']['facebook_instagram']['conversion_rate'] ?? 0) > 2) {
            $pros[] = 'High social media conversion potential';
        }
        
        return $pros ?: ['Product has market potential'];
    }

    private function generateEnhancedCons($data)
    {
        $cons = [];
        
        $competition = strtolower($data['market_analysis']['competition_level'] ?? '');
        if (strpos($competition, 'high') !== false) {
            $cons[] = 'High competition in the market';
        }
        
        if (($data['market_analysis']['profitability_score'] ?? 0) < 40) {
            $cons[] = 'Lower profit margins (' . ($data['market_analysis']['profitability_score'] ?? 0) . '% score)';
        }
        
        if (($data['price_analysis']['recommended_pricing']['profit_margin'] ?? 0) < 30) {
            $cons[] = 'Tight profit margins below 30%';
        }
        
        if (($data['competition_metrics']['market_concentration']['top_3_share'] ?? 0) > 70) {
            $cons[] = 'Market dominated by top 3 players';
        }
        
        if (count($data['risk_analysis']['market_risks'] ?? []) > 2) {
            $cons[] = 'Multiple market risks identified';
        }
        
        return $cons ?: ['Standard market challenges apply'];
    }

    private function generateEnhancedRecommendations($data)
    {
        $recommendations = [];
        
        if (($data['price_analysis']['recommended_pricing']['suggested_retail'] ?? 0) > 0) {
            $recommendations[] = 'Optimal pricing at $' . ($data['price_analysis']['recommended_pricing']['suggested_retail'] ?? 0) . ' for ' . ($data['price_analysis']['recommended_pricing']['profit_margin'] ?? 0) . '% margin';
        }
        
        if (count($data['target_audience']['psychographics']['interests'] ?? []) > 0) {
            $recommendations[] = 'Target marketing towards: ' . implode(', ', array_slice($data['target_audience']['psychographics']['interests'], 0, 3));
        }
        
        if (count($data['market_opportunities']['underserved_segments'] ?? []) > 0) {
            $recommendations[] = 'Focus on underserved segment: ' . $data['market_opportunities']['underserved_segments'][0];
        }
        
        if (count($data['seo_insights']['primary_keywords'] ?? []) > 0) {
            $topKeyword = $data['seo_insights']['primary_keywords'][0]['keyword'] ?? '';
            $recommendations[] = 'Optimize for high-volume keyword: "' . $topKeyword . '"';
        }
        
        if (($data['social_media_targeting']['facebook_instagram']['estimated_cpc'] ?? 0) < 1) {
            $recommendations[] = 'Leverage low-cost Facebook/Instagram advertising (CPC: $' . ($data['social_media_targeting']['facebook_instagram']['estimated_cpc'] ?? 0) . ')';
        }
        
        return $recommendations ?: ['Monitor market trends and adjust pricing accordingly'];
    }

    private function extractKeywordsFromSEO($seoData)
    {
        $keywords = [];
        
        // Extract from primary keywords
        foreach ($seoData['primary_keywords'] ?? [] as $keywordData) {
            $keywords[] = $keywordData['keyword'] ?? '';
        }
        
        // Add long tail keywords
        $keywords = array_merge($keywords, $seoData['long_tail_keywords'] ?? []);
        
        return array_unique(array_filter($keywords));
    }

    private function transformCompetitorDomainsToShoppingResults($domains)
    {
        return array_map(function($domain) {
            return [
                'title' => 'Product from ' . ($domain['domain'] ?? 'Unknown'),
                'source' => $domain['domain'] ?? 'Unknown',
                'price' => floatval($domain['avg_price'] ?? 0),
                'price_formatted' => '৳' . number_format(floatval($domain['avg_price'] ?? 0), 2),
                'link' => $domain['product_link'] ?? ('https://' . ($domain['domain'] ?? '#')),
                'rating' => $domain['rating'] ?? null,
                'reviews' => $domain['reviews'] ?? null,
                'is_bangladeshi' => $this->isBangladeshiDomain($domain['domain'] ?? ''),
                'market_share' => $domain['market_share'] ?? 'Unknown',
                'trust_score' => $domain['trust_score'] ?? 0
            ];
        }, $domains);
    }

    private function transformToSearchResults($domains)
    {
        return array_map(function($domain) {
            return [
                'title' => 'Products from ' . ($domain['domain'] ?? 'Unknown'),
                'domain' => $domain['domain'] ?? 'Unknown',
                'link' => $domain['product_link'] ?? ('https://' . ($domain['domain'] ?? '#')),
                'is_bangladeshi' => $this->isBangladeshiDomain($domain['domain'] ?? ''),
                'market_share' => $domain['market_share'] ?? 'Unknown',
                'trust_score' => $domain['trust_score'] ?? 0,
                'traffic_rank' => $domain['traffic_rank'] ?? 999
            ];
        }, $domains);
    }

    private function transformToCompetitorWebsites($domains)
    {
        return array_map(function($domain) {
            return [
                'domain' => $domain['domain'] ?? 'Unknown',
                'count' => 1, // Placeholder
                'market_share' => $domain['market_share'] ?? 'Unknown',
                'avg_price' => $domain['avg_price'] ?? 0,
                'trust_score' => $domain['trust_score'] ?? 0
            ];
        }, $domains);
    }

    private function generateRelatedProducts($data)
    {
        // Generate related products from competitor data
        $relatedProducts = [];
        
        foreach ($data['competitor_domains'] ?? [] as $index => $domain) {
            if ($index >= 10) break; // Limit to 10
            
            $relatedProducts[] = [
                'product_name' => 'Similar Product from ' . ($domain['domain'] ?? 'Store'),
                'estimated_price' => $domain['avg_price'] ?? 0,
                'key_features' => $domain['strengths'] ?? ['Quality product', 'Good value']
            ];
        }
        
        return $relatedProducts;
    }

    private function generateMarketGapsSummary($data)
    {
        $gaps = $data['price_analysis']['price_gaps'] ?? [];
        if (empty($gaps)) {
            return 'No significant price gaps identified in current market analysis.';
        }
        
        $summary = 'Identified ' . count($gaps) . ' price gap opportunities: ';
        $gapDescriptions = [];
        
        foreach ($gaps as $gap) {
            $gapDescriptions[] = $gap['gap_range'] . ' (' . $gap['opportunity'] . ')';
        }
        
        return $summary . implode(', ', $gapDescriptions);
    }

    private function isBangladeshiDomain($domain)
    {
        $bangladeshiDomains = ['.bd', 'daraz.com.bd', 'pickaboo.com', 'rokomari.com', 'ajkerdeal.com'];
        
        foreach ($bangladeshiDomains as $bdDomain) {
            if (strpos($domain, $bdDomain) !== false) {
                return true;
            }
        }
        
        return false;
    }

    // Legacy methods for backward compatibility
    private function generatePros($data)
    {
        return $this->generateEnhancedPros($data);
    }

    private function generateCons($data)
    {
        return $this->generateEnhancedCons($data);
    }

    private function generateRecommendations($data)
    {
        return $this->generateEnhancedRecommendations($data);
    }

    private function getMinPrice($products)
    {
        if (empty($products)) return 0;
        
        $prices = array_map(function($product) {
            return floatval($product['estimated_price'] ?? 0);
        }, $products);
        
        return min(array_filter($prices));
    }

    private function getMaxPrice($products)
    {
        if (empty($products)) return 0;
        
        $prices = array_map(function($product) {
            return floatval($product['estimated_price'] ?? 0);
        }, $products);
        
        return max($prices);
    }

    private function transformToShoppingResults($products)
    {
        return array_map(function($product, $index) {
            return [
                'title' => $product['product_name'] ?? 'Product ' . ($index + 1),
                'source' => 'Market Research',
                'price' => floatval($product['estimated_price'] ?? 0),
                'price_formatted' => '$' . number_format(floatval($product['estimated_price'] ?? 0), 2),
                'link' => '#',
                'rating' => null,
                'reviews' => null,
                'is_bangladeshi' => false
            ];
        }, $products, array_keys($products));
    }

    private function generateSuggestedTitles($data)
    {
        // Use SEO insights if available, otherwise generate generic ones
        if (!empty($data['seo_insights']['suggested_titles'])) {
            return $data['seo_insights']['suggested_titles'];
        }
        
        $productName = $data['product_name'] ?? 'Product';
        return [
            "Premium {$productName} - Best Quality & Price",
            "Professional {$productName} - Fast Delivery",
            "Top Rated {$productName} - Customer Choice",
            "High Quality {$productName} - Wholesale Price",
            "Best {$productName} - Limited Time Offer",
            "Professional Grade {$productName} - Buy Now"
        ];
    }

    private function generateMetaDescriptions($data)
    {
        // Use SEO insights if available, otherwise generate generic ones
        if (!empty($data['seo_insights']['meta_descriptions'])) {
            return $data['seo_insights']['meta_descriptions'];
        }
        
        $productName = $data['product_name'] ?? 'product';
        return [
            "Get the best {$productName} at competitive prices with fast delivery and excellent customer service. Shop now!",
            "Premium quality {$productName} with money-back guarantee. Free shipping available. Order today!",
            "Professional {$productName} for all your needs. Top-rated quality with customer satisfaction guarantee.",
            "Buy high-quality {$productName} online. Best prices, fast shipping, and reliable customer support."
        ];
    }

    private function extractKeywords($data)
    {
        // Use SEO insights if available
        if (!empty($data['seo_insights'])) {
            return $this->extractKeywordsFromSEO($data['seo_insights']);
        }
        
        // Fallback to basic keyword extraction
        $keywords = ['quality', 'premium', 'best', 'price', 'delivery'];
        
        // Add keywords from interests
        if (isset($data['target_audience']['psychographics']['interests'])) {
            $keywords = array_merge($keywords, $data['target_audience']['psychographics']['interests']);
        }
        
        // Add product name words
        if (isset($data['product_name'])) {
            $nameWords = explode(' ', strtolower($data['product_name']));
            $keywords = array_merge($keywords, $nameWords);
        }
        
        return array_unique(array_filter($keywords));
    }

    private function generateFacebookInstagramTargeting($data)
    {
        $demographics = $data['target_audience']['primary_demographics'] ?? [];
        
        return [
            'age_range' => $demographics['age_range'] ?? 'N/A',
            'gender' => $demographics['gender'] ?? 'N/A',
            'income_range' => $demographics['income_range'] ?? 'N/A',
            'interests' => $data['target_audience']['psychographics']['interests'] ?? [],
            'estimated_cpc' => 0,
            'conversion_rate' => 0,
            'audience_size' => 'Unknown'
        ];
    }

    private function generateMarketConcentration($data)
    {
        $competitorCount = count($data['competitor_domains'] ?? []);
        
        return [
            'top_3_share' => 'N/A',
            'hhi_index' => 'N/A',
            'market_type' => $competitorCount > 10 ? 'Fragmented' : ($competitorCount > 5 ? 'Moderate' : 'Concentrated'),
            'competitor_count' => $competitorCount
        ];
    }

    private function generateEntryBarriers($data)
    {
        return [
            'capital_requirements' => 'N/A',
            'brand_loyalty' => 'N/A',
            'regulatory_barriers' => 'N/A',
            'technology_barriers' => 'N/A',
            'overall_difficulty' => 'N/A'
        ];
    }
}