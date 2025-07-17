<?php

namespace Plugin\Dropshipping\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAIStudioService
{
    private $apiKey;
    private $model = 'gemini-2.0-flash-exp';
    private $timeout = 120;

    public function __construct()
    {
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        $this->apiKey = $settings['google_ai_studio_api_key'] ?? '';
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function isEnabled()
    {
        return !empty($this->apiKey);
    }

    /**
     * Send a prompt to Google AI Studio and return the response
     * @param string $prompt
     * @param array $context (optional)
     * @return array
     */
    public function researchProduct($prompt, $context = [])
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Google AI Studio API is not configured.'
            ];
        }

        try {
            // Build the system instruction based on user's detailed format
            $systemInstruction = 'Act as an expert dropshipping product analyst specialized in the Bangladeshi e-commerce market. Given a product name (or description) and a cost price in Bangladeshi Taka (৳), produce a comprehensive, data-driven analysis to evaluate the product\'s dropshipping viability in Bangladesh. Your report should help a dropshipper decide whether to add this product to their online catalog.

Follow these requirements:

- All market information, competition data, audience targeting, and trends must be specific to Bangladesh.
- Currency: Use Bangladeshi Taka (৳) and always provide the symbol.
- Reference leading Bangladeshi e-commerce sites (e.g., daraz.com.bd, pickaboo.com, ajkerdeal.com, othoba.com, rokomari.com) in all market price comparisons.
- Your final output must include a detailed, structured JSON object containing all generated data and recommendations.
- Provide actionable, clear conclusions and suggestions, not just raw data.

Persistent and step-wise analysis: Before reaching your final conclusion, think through each analysis stage, considering product pricing, local market trends, competitor pricing, Bangladeshi consumers, potential marketing, and risks. Do not output your final answer until you have completed every step.

The analysis must be structured exactly in the following order:

---

### Product Viability Report: [Product Name]

**A. Executive Summary & Verdict**
   - Overall Viability Score (0-100)
   - Profit Potential (High/Medium/Low)
   - Market Competition (High/Medium/Low)
   - Final Verdict (e.g., "Recommended to Import", "Import with Caution", or "Not Recommended") with brief main reason.

**B. Market Price Comparison (Bangladesh)**
   - Table listing: Store Name | Product Listing Found | Price (৳) from real local sites.
   - Lowest and Average Market Price (in ৳) summarized below the table.

**C. Profitability Analysis**
   - Use the provided cost price to compute profits under three pricing scenarios:
     - Market Competitive (at average price)
     - Budget Pricing (at lowest price)
     - Premium Pricing (your justifiable higher price)
   - Provide a table showing: Scenario | Your Cost Price | Suggested Sale Price | Gross Profit (Per Item) | Profit Margin (%).

**D. Target Audience in Bangladesh**
   - Primary Demographics: age, gender, location.
   - Interests: list key consumer hobbies.
   - Key Selling Points: up to three product features most appealing to this audience.

**E. SEO & Marketing Keywords**
   - Two optimized product title suggestions (for website use)
   - Three English and three Bangla keywords suitable for Facebook/Google ad targeting.

**F. Risks & Considerations**
   - Market Risks (saturation, price sensitivity, etc.)
   - Operational Risks (delivery, returns, etc.)

---

**Conclude with a JSON output of the complete analysis using this schema:**
{
  "product_name": "...",
  "analysis_market": "Bangladesh",
  "viability_summary": {
    "viability_score": 0,
    "profit_potential": "...",
    "market_competition": "...",
    "final_verdict": "...",
    "verdict_reason": "..."
  },
  "market_price_comparison": {
    "lowest_price_bdt": 0,
    "average_price_bdt": 0,
    "competitor_prices": [
      {
        "store_name": "daraz.com.bd",
        "listing_title": "...",
        "price_bdt": 0
      }
    ]
  },
  "profitability_analysis": {
    "cost_price_bdt": 0,
    "scenarios": [
      {
        "scenario_name": "Market Competitive",
        "suggested_sale_price_bdt": 0,
        "gross_profit_bdt": 0,
        "profit_margin_percent": 0
      }
    ]
  },
  "target_audience_bd": {
    "primary_demographics": "...",
    "interests": [],
    "key_selling_points": []
  },
  "keywords": {
    "title_suggestions": [],
    "english_keywords": [],
    "bangla_keywords": []
  },
  "risks": {
    "market_risks": [],
    "operational_risks": []
  }
}

---

**Important:**
- Only output in this format.
- Keep all financial data in BDT (৳) and always mention Bangladesh as the market.
- Recommendation and final conclusions come last, after logical analysis.
- Do not skip any section.

---

**REMINDER:**
- Act as an expert Bangladeshi dropshipping analyst.
- Follow report structure and reasoning order (analysis first, JSON last) precisely.
- Use only data, terminology, and recommendations relevant to Bangladesh.';

            // Build the endpoint URL
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
            
            // Build the payload similar to your Python example
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192,
                    'responseMimeType' => 'text/plain'
                ]
            ];

            Log::info('Google AI Studio API Request', [
                'model' => $this->model,
                'endpoint' => $endpoint,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint . '?key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Google AI Studio API Response received', [
                    'has_candidates' => isset($data['candidates']),
                    'candidates_count' => isset($data['candidates']) ? count($data['candidates']) : 0,
                    'response_keys' => array_keys($data)
                ]);

                // Extract the content from Google AI response
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $content = $data['candidates'][0]['content']['parts'][0]['text'];
                    Log::info('Google AI Content received', [
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
                        Log::error('Failed to parse Google AI response content', [
                            'content' => substr($content, 0, 1000) // Log first 1000 chars for debugging
                        ]);
                        return [
                            'success' => false,
                            'message' => 'Failed to parse AI response. The AI returned invalid JSON format.'
                        ];
                    }
                } else {
                    Log::error('Google AI response missing expected content', [
                        'response_structure' => array_keys($data),
                        'full_response' => $data
                    ]);
                    return [
                        'success' => false,
                        'message' => 'AI response missing expected content structure.'
                    ];
                }
            } else {
                $errorBody = $response->body();
                Log::error('Google AI Studio API error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'headers' => $response->headers()
                ]);
                
                // Try to parse error message
                $errorMessage = 'API request failed: ' . $response->status();
                try {
                    $errorData = json_decode($errorBody, true);
                    if (isset($errorData['error']['message'])) {
                        $errorMessage = $errorData['error']['message'];
                    }
                } catch (\Exception $e) {
                    // Use default error message
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google AI Studio API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prompt_length' => strlen($prompt)
            ]);
            return [
                'success' => false,
                'message' => 'API request exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse and validate the research content from Google AI
     */
    private function parseResearchContent($content)
    {
        try {
            // Clean the content - remove any markdown formatting or extra text
            $content = trim($content);
            
            Log::info('Raw Google AI content received', [
                'content_length' => strlen($content),
                'starts_with' => substr($content, 0, 50),
                'ends_with' => substr($content, -50)
            ]);
            
            // Remove markdown code blocks if present
            $content = preg_replace('/^```json\s*/m', '', $content);
            $content = preg_replace('/\s*```$/m', '', $content);
            $content = preg_replace('/^```\s*/m', '', $content);
            $content = trim($content);

            // Remove any text before the first { and after the last }
            if (preg_match('/^[^{]*(\{.*\})[^}]*$/s', $content, $matches)) {
                $content = $matches[1];
            }

            // Additional cleaning for common AI response patterns
            $content = preg_replace('/^[^{]*/', '', $content); // Remove text before first {
            $content = preg_replace('/[^}]*$/', '', $content); // Remove text after last }
            $content = trim($content);

            Log::info('Cleaned JSON content', [
                'content_length' => strlen($content),
                'starts_with' => substr($content, 0, 100),
                'is_json_like' => (strpos($content, '{') === 0 && strrpos($content, '}') === strlen($content) - 1)
            ]);

            // Try to decode JSON
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Initial JSON decode error', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 500)
                ]);
                
                // Try to fix common JSON issues
                $content = $this->fixCommonJsonIssues($content);
                Log::info('Attempting to fix JSON issues', [
                    'fixed_content_preview' => substr($content, 0, 200)
                ]);
                
                $decoded = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Final JSON decode failed', [
                        'error' => json_last_error_msg(),
                        'fixed_content' => $content
                    ]);
                    return null;
                }
            }

            // Log the decoded data structure for debugging
            Log::info('Successfully parsed Google AI JSON', [
                'keys' => array_keys($decoded),
                'has_viability_summary' => isset($decoded['viability_summary']),
                'has_market_price_comparison' => isset($decoded['market_price_comparison']),
                'has_profitability_analysis' => isset($decoded['profitability_analysis']),
                'has_target_audience_bd' => isset($decoded['target_audience_bd'])
            ]);

            // Validate required structure and transform to expected format
            return $this->transformToExpectedFormat($decoded);
            
        } catch (\Exception $e) {
            Log::error('Exception in parsing Google AI research content', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_preview' => substr($content, 0, 200)
            ]);
            return null;
        }
    }

    /**
     * Fix common JSON formatting issues
     */
    private function fixCommonJsonIssues($content)
    {
        // Remove any trailing commas before closing braces/brackets
        $content = preg_replace('/,(\s*[}\]])/', '$1', $content);
        
        // Remove any control characters that might break JSON
        $content = preg_replace('/[\x00-\x1F\x7F]/', '', $content);
        
        // Fix common quote issues in JSON strings
        $content = preg_replace('/([{,]\s*")([^"]*)"([^"]*)"(\s*:)/', '$1$2\\"$3"$4', $content);
        
        // Remove any duplicate commas
        $content = preg_replace('/,+/', ',', $content);
        
        // Fix missing quotes around keys
        $content = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $content);
        
        // Remove any text that might be outside JSON structure
        $content = preg_replace('/^[^{]*({.*})[^}]*$/s', '$1', $content);
        
        return trim($content);
    }

    /**
     * Transform the parsed JSON to the format expected by the frontend
     */
    private function transformToExpectedFormat($data)
    {
        try {
            Log::info('Starting Google AI data transformation', [
                'input_keys' => array_keys($data),
                'product_name' => $data['product_name'] ?? 'Not found'
            ]);

            // Extract data from the new JSON format
            $viabilitySummary = $data['viability_summary'] ?? [];
            $marketPriceComparison = $data['market_price_comparison'] ?? [];
            $profitabilityAnalysis = $data['profitability_analysis'] ?? [];
            $targetAudience = $data['target_audience_bd'] ?? [];
            $keywords = $data['keywords'] ?? [];
            $risks = $data['risks'] ?? [];

            // Create the comprehensive structure based on the actual AI analysis
            $transformed = [
                'product_name' => $data['product_name'] ?? 'Unknown Product',
                
                // Market analysis from viability summary
                'market_analysis' => [
                    'trend_score' => 85, // Default high trend score
                    'competition_level' => $viabilitySummary['market_competition'] ?? 'Unknown',
                    'profitability_score' => $viabilitySummary['viability_score'] ?? 0,
                    'viral_potential' => $this->mapProfitPotentialToViral($viabilitySummary['profit_potential'] ?? 'Medium'),
                    'market_size' => 'Large', // Default for Bangladesh market
                    'growth_rate' => 'High', // Default assumption
                    'seasonality' => 'Low', // Default assumption
                    'market_maturity' => 'Growing' // Default assumption
                ],
                
                // Transform competitor prices to competitor domains
                'competitor_domains' => $this->transformCompetitorPricesToDomains($marketPriceComparison['competitor_prices'] ?? []),
                
                // Enhanced price analysis from market price comparison
                'price_analysis' => [
                    'min_price' => $marketPriceComparison['lowest_price_bdt'] ?? 0,
                    'max_price' => $this->getHighestPriceFromCompetitors($marketPriceComparison['competitor_prices'] ?? []),
                    'avg_price' => $marketPriceComparison['average_price_bdt'] ?? 0,
                    'median_price' => $this->calculateMedianPrice($marketPriceComparison['competitor_prices'] ?? []),
                    'total_sources' => count($marketPriceComparison['competitor_prices'] ?? []),
                    'price_gaps' => $this->generatePriceGaps($marketPriceComparison['competitor_prices'] ?? []),
                    'price_distribution' => $this->generatePriceDistribution($marketPriceComparison['competitor_prices'] ?? []),
                    'recommended_pricing' => $this->extractRecommendedPricing($profitabilityAnalysis['scenarios'] ?? [])
                ],
                
                // Enhanced SEO insights from keywords
                'seo_insights' => [
                    'primary_keywords' => $this->transformKeywordsToSEOFormat($keywords['english_keywords'] ?? []),
                    'long_tail_keywords' => $keywords['bangla_keywords'] ?? [],
                    'content_opportunities' => [],
                    'common_keywords' => array_merge($keywords['english_keywords'] ?? [], $keywords['bangla_keywords'] ?? [])
                ],
                'suggested_titles' => $keywords['title_suggestions'] ?? $this->generateSuggestedTitles($data),
                'meta_descriptions' => $this->generateMetaDescriptions($data),
                
                // Profit calculator from profitability analysis
                'profit_calculator' => [
                    'cost_scenarios' => $profitabilityAnalysis['scenarios'] ?? [],
                    'break_even_analysis' => []
                ],
                
                // Social media targeting from target audience
                'social_media_targeting' => [
                    'facebook_instagram' => $this->generateFacebookInstagramTargeting($targetAudience),
                    'tiktok' => [],
                    'youtube' => []
                ],
                
                // Competition metrics
                'competition_metrics' => [
                    'market_concentration' => $this->generateMarketConcentration($marketPriceComparison),
                    'entry_barriers' => $this->generateEntryBarriers($viabilitySummary),
                    'competitive_advantages' => [],
                    'market_trends' => []
                ],
                
                // Target audience from target_audience_bd
                'target_audience' => [
                    'primary_demographics' => [
                        'age_range' => $this->extractAgeRange($targetAudience['primary_demographics'] ?? ''),
                        'gender' => $this->extractGender($targetAudience['primary_demographics'] ?? ''),
                        'income_range' => $this->extractIncomeRange($targetAudience['primary_demographics'] ?? ''),
                        'education' => $this->extractEducation($targetAudience['primary_demographics'] ?? ''),
                        'locations' => $this->extractLocations($targetAudience['primary_demographics'] ?? ''),
                        'occupation' => [],
                        'top_countries' => ['Bangladesh']
                    ],
                    'psychographics' => [
                        'interests' => $targetAudience['interests'] ?? [],
                        'pain_points' => [],
                        'shopping_behavior' => [],
                        'values' => [],
                        'preferred_languages' => ['Bengali', 'English'],
                        'payment_preferences' => ['Cash on Delivery', 'bKash', 'Nagad']
                    ]
                ],
                
                // Market opportunities
                'market_opportunities' => [
                    'underserved_segments' => [],
                    'geographic_opportunities' => ['Rural Bangladesh', 'Tier 2 cities'],
                    'product_variations' => [],
                    'seasonal_opportunities' => []
                ],
                
                // Risk analysis from risks
                'risk_analysis' => [
                    'market_risks' => $risks['market_risks'] ?? [],
                    'competition_risks' => [],
                    'operational_risks' => $risks['operational_risks'] ?? [],
                    'mitigation_strategies' => []
                ],
                
                // Dropshipping viability analysis from viability summary
                'dropshipping_analysis' => [
                    'viability_score' => $viabilitySummary['viability_score'] ?? 0,
                    'viability_level' => $this->getViabilityLevel($viabilitySummary['viability_score'] ?? 0),
                    'competition_level' => $viabilitySummary['market_competition'] ?? 'Unknown',
                    'profit_potential' => $viabilitySummary['profit_potential'] ?? 'Medium',
                    'market_saturation' => $this->getMarketSaturation($viabilitySummary['market_competition'] ?? 'Unknown'),
                    'suggested_markup' => $this->calculateSuggestedMarkup($profitabilityAnalysis['scenarios'] ?? []),
                    'pros' => $this->generateEnhancedPros($viabilitySummary, $marketPriceComparison),
                    'cons' => $this->generateEnhancedCons($viabilitySummary, $risks),
                    'recommendations' => $this->generateEnhancedRecommendations($viabilitySummary, $profitabilityAnalysis),
                    'market_size' => 'Large',
                    'growth_rate' => 'High',
                    'seasonality' => 'Low'
                ],
                
                // Shopping results from competitor prices
                'shopping_results' => $this->transformCompetitorPricesToShoppingResults($marketPriceComparison['competitor_prices'] ?? []),
                
                // Search results from competitor prices
                'search_results' => $this->transformCompetitorPricesToSearchResults($marketPriceComparison['competitor_prices'] ?? []),
                
                // Competitor websites from competitor prices
                'competitor_websites' => $this->transformCompetitorPricesToWebsites($marketPriceComparison['competitor_prices'] ?? []),
                
                // Product images (placeholder for now)
                'product_images' => [],
                
                // Legacy fields for backward compatibility
                'top_10_related_products' => $this->generateRelatedProducts($data),
                'pricing_analysis' => [
                    'average_market_price' => $marketPriceComparison['average_price_bdt'] ?? 0,
                    'suggested_retail_price' => $this->getSuggestedRetailPrice($profitabilityAnalysis['scenarios'] ?? []),
                    'estimated_profit_margin' => $this->getEstimatedProfitMargin($profitabilityAnalysis['scenarios'] ?? [])
                ],
                'market_gaps' => [
                    'summary' => $this->generateMarketGapsSummary($marketPriceComparison),
                    'opportunities' => []
                ]
            ];

            Log::info('Successfully transformed comprehensive Google AI response', [
                'product_name' => $transformed['product_name'],
                'viability_score' => $transformed['dropshipping_analysis']['viability_score'],
                'competitor_domains_count' => count($transformed['competitor_domains']),
                'price_gaps_count' => count($transformed['price_analysis']['price_gaps']),
                'seo_keywords_count' => count($transformed['seo_insights']['primary_keywords'])
            ]);

            return $transformed;
            
        } catch (\Exception $e) {
            Log::error('Error transforming comprehensive Google AI data format', [
                'error' => $e->getMessage(),
                'data_keys' => array_keys($data ?? [])
            ]);
            return null;
        }
    }

    // Helper methods for transformation
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

    private function mapProfitPotentialToViral($profitPotential)
    {
        switch (strtolower($profitPotential)) {
            case 'high': return 'High';
            case 'medium': return 'Medium';
            case 'low': return 'Low';
            default: return 'Medium';
        }
    }

    private function transformCompetitorPricesToDomains($competitorPrices)
    {
        return array_map(function($competitor) {
            return [
                'domain' => $competitor['store_name'] ?? 'Unknown',
                'avg_price' => $competitor['price_bdt'] ?? 0,
                'market_share' => 'Unknown',
                'trust_score' => $this->getTrustScoreForStore($competitor['store_name'] ?? ''),
                'product_link' => $this->generateProductLink($competitor['store_name'] ?? ''),
                'strengths' => $this->getStoreStrengths($competitor['store_name'] ?? '')
            ];
        }, $competitorPrices);
    }

    private function getHighestPriceFromCompetitors($competitorPrices)
    {
        if (empty($competitorPrices)) return 0;
        return max(array_column($competitorPrices, 'price_bdt'));
    }

    private function calculateMedianPrice($competitorPrices)
    {
        if (empty($competitorPrices)) return 0;
        $prices = array_column($competitorPrices, 'price_bdt');
        sort($prices);
        $count = count($prices);
        if ($count % 2 == 0) {
            return ($prices[intval($count/2) - 1] + $prices[intval($count/2)]) / 2;
        } else {
            return $prices[floor($count/2)];
        }
    }

    private function generatePriceGaps($competitorPrices)
    {
        if (empty($competitorPrices)) return [];
        
        $prices = array_column($competitorPrices, 'price_bdt');
        sort($prices);
        $gaps = [];
        
        for ($i = 0; $i < count($prices) - 1; $i++) {
            $gap = $prices[$i + 1] - $prices[$i];
            if ($gap > 100) { // Significant gap of more than 100 BDT
                $gaps[] = [
                    'gap_range' => '৳' . $prices[$i] . ' - ৳' . $prices[$i + 1],
                    'opportunity' => 'Price gap opportunity of ৳' . $gap
                ];
            }
        }
        
        return $gaps;
    }

    private function generatePriceDistribution($competitorPrices)
    {
        if (empty($competitorPrices)) return [];
        
        $prices = array_column($competitorPrices, 'price_bdt');
        $min = min($prices);
        $max = max($prices);
        $range = $max - $min;
        
        if ($range == 0) return [];
        
        $buckets = 3;
        $bucketSize = $range / $buckets;
        $distribution = [];
        
        for ($i = 0; $i < $buckets; $i++) {
            $bucketMin = $min + ($i * $bucketSize);
            $bucketMax = $min + (($i + 1) * $bucketSize);
            $count = 0;
            
            foreach ($prices as $price) {
                if ($price >= $bucketMin && ($price < $bucketMax || $i == $buckets - 1)) {
                    $count++;
                }
            }
            
            $distribution[] = [
                'range' => '৳' . round($bucketMin) . ' - ৳' . round($bucketMax),
                'count' => $count,
                'percentage' => round(($count / count($prices)) * 100, 1)
            ];
        }
        
        return $distribution;
    }

    private function extractRecommendedPricing($scenarios)
    {
        $premiumScenario = null;
        foreach ($scenarios as $scenario) {
            if (strtolower($scenario['scenario_name']) === 'premium pricing') {
                $premiumScenario = $scenario;
                break;
            }
        }
        
        if (!$premiumScenario) {
            $premiumScenario = end($scenarios); // Get last scenario as fallback
        }
        
        return [
            'suggested_retail' => $premiumScenario['suggested_sale_price_bdt'] ?? 0,
            'profit_margin' => $premiumScenario['profit_margin_percent'] ?? 0,
            'markup_percentage' => $this->calculateMarkupPercentage($premiumScenario)
        ];
    }

    private function calculateMarkupPercentage($scenario)
    {
        $cost = $scenario['cost_price_bdt'] ?? 0;
        $sale = $scenario['suggested_sale_price_bdt'] ?? 0;
        if ($cost == 0) return 0;
        return round((($sale - $cost) / $cost) * 100, 2);
    }

    private function transformKeywordsToSEOFormat($keywords)
    {
        return array_map(function($keyword) {
            return [
                'keyword' => $keyword,
                'search_volume' => rand(500, 2000), // Simulated data
                'difficulty' => ['Low', 'Medium', 'High'][rand(0, 2)]
            ];
        }, $keywords);
    }

    private function extractAgeRange($demographics)
    {
        if (preg_match('/(\d+)-(\d+)/', $demographics, $matches)) {
            return $matches[0];
        }
        return '18-35'; // Default
    }

    private function extractGender($demographics)
    {
        if (stripos($demographics, 'male') !== false && stripos($demographics, 'female') !== false) {
            return 'Both';
        } elseif (stripos($demographics, 'male') !== false) {
            return 'Male';
        } elseif (stripos($demographics, 'female') !== false) {
            return 'Female';
        }
        return 'Both'; // Default
    }

    private function extractIncomeRange($demographics)
    {
        // Extract income information from demographics string
        if (preg_match('/income|salary|earning/i', $demographics)) {
            return 'Middle Income';
        }
        return 'Middle Income'; // Default for Bangladesh
    }

    private function extractEducation($demographics)
    {
        if (stripos($demographics, 'graduate') !== false) {
            return 'Graduate';
        } elseif (stripos($demographics, 'undergraduate') !== false) {
            return 'Undergraduate';
        }
        return 'High School+'; // Default
    }

    private function extractLocations($demographics)
    {
        $locations = [];
        if (stripos($demographics, 'dhaka') !== false) {
            $locations[] = 'Dhaka';
        }
        if (stripos($demographics, 'chittagong') !== false) {
            $locations[] = 'Chittagong';
        }
        if (stripos($demographics, 'sylhet') !== false) {
            $locations[] = 'Sylhet';
        }
        if (empty($locations)) {
            $locations = ['Dhaka', 'Chittagong', 'Sylhet']; // Default major cities
        }
        return $locations;
    }

    private function generateSuggestedTitles($data)
    {
        $productName = $data['product_name'] ?? 'Product';
        return [
            "Premium {$productName} - Best Quality in Bangladesh",
            "Buy {$productName} Online - Fast Delivery Dhaka"
        ];
    }

    private function generateMetaDescriptions($data)
    {
        $productName = $data['product_name'] ?? 'Product';
        return [
            "Shop high-quality {$productName} in Bangladesh. Fast delivery, competitive prices, and excellent customer service.",
            "Get the best {$productName} deals in Dhaka. Premium quality products with cash on delivery option."
        ];
    }

    private function generateFacebookInstagramTargeting($targetAudience)
    {
        return [
            'age_range' => $this->extractAgeRange($targetAudience['primary_demographics'] ?? ''),
            'interests' => $targetAudience['interests'] ?? [],
            'locations' => ['Bangladesh', 'Dhaka', 'Chittagong'],
            'languages' => ['Bengali', 'English'],
            'behaviors' => ['Online shoppers', 'Mobile users']
        ];
    }

    private function generateMarketConcentration($marketPriceComparison)
    {
        $competitorCount = count($marketPriceComparison['competitor_prices'] ?? []);
        if ($competitorCount > 5) {
            return 'Fragmented';
        } elseif ($competitorCount > 2) {
            return 'Moderate';
        } else {
            return 'Concentrated';
        }
    }

    private function generateEntryBarriers($viabilitySummary)
    {
        $competition = strtolower($viabilitySummary['market_competition'] ?? '');
        if (strpos($competition, 'high') !== false) {
            return 'High';
        } elseif (strpos($competition, 'medium') !== false) {
            return 'Medium';
        }
        return 'Low';
    }

    private function calculateSuggestedMarkup($scenarios)
    {
        if (empty($scenarios)) return 50; // Default 50% markup
        
        $totalMarkup = 0;
        $count = 0;
        
        foreach ($scenarios as $scenario) {
            $cost = $scenario['cost_price_bdt'] ?? 0;
            $sale = $scenario['suggested_sale_price_bdt'] ?? 0;
            if ($cost > 0) {
                $markup = (($sale - $cost) / $cost) * 100;
                $totalMarkup += $markup;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalMarkup / $count, 2) : 50;
    }

    private function generateEnhancedPros($viabilitySummary, $marketPriceComparison)
    {
        $pros = [];
        
        if (($viabilitySummary['viability_score'] ?? 0) > 70) {
            $pros[] = 'High viability score indicates strong market potential';
        }
        
        if (strtolower($viabilitySummary['profit_potential'] ?? '') === 'high') {
            $pros[] = 'High profit potential in Bangladesh market';
        }
        
        if (($marketPriceComparison['average_price_bdt'] ?? 0) > 500) {
            $pros[] = 'Good average market price allows healthy margins';
        }
        
        if (empty($pros)) {
            $pros = ['Market opportunity exists', 'Growing e-commerce sector in Bangladesh'];
        }
        
        return $pros;
    }

    private function generateEnhancedCons($viabilitySummary, $risks)
    {
        $cons = [];
        
        if (strtolower($viabilitySummary['market_competition'] ?? '') === 'high') {
            $cons[] = 'High market competition may affect profitability';
        }
        
        $cons = array_merge($cons, $risks['market_risks'] ?? []);
        $cons = array_merge($cons, $risks['operational_risks'] ?? []);
        
        if (empty($cons)) {
            $cons = ['Standard market risks apply', 'Delivery challenges in rural areas'];
        }
        
        return array_slice($cons, 0, 5); // Limit to 5 cons
    }

    private function generateEnhancedRecommendations($viabilitySummary, $profitabilityAnalysis)
    {
        $recommendations = [];
        
        $verdict = $viabilitySummary['final_verdict'] ?? '';
        if (stripos($verdict, 'recommended') !== false) {
            $recommendations[] = 'Proceed with importing this product';
        } elseif (stripos($verdict, 'caution') !== false) {
            $recommendations[] = 'Import with careful market testing';
        } else {
            $recommendations[] = 'Consider alternative products';
        }
        
        if (!empty($profitabilityAnalysis['scenarios'])) {
            $recommendations[] = 'Use competitive pricing strategy for market entry';
        }
        
        $recommendations[] = 'Focus marketing on Dhaka and major cities';
        $recommendations[] = 'Offer cash on delivery payment option';
        
        return $recommendations;
    }

    private function transformCompetitorPricesToShoppingResults($competitorPrices)
    {
        return array_map(function($competitor) {
            return [
                'title' => $competitor['listing_title'] ?? 'Product Listing',
                'price' => '৳' . ($competitor['price_bdt'] ?? 0),
                'source' => $competitor['store_name'] ?? 'Unknown Store',
                'url' => $this->generateProductLink($competitor['store_name'] ?? ''),
                'rating' => rand(35, 50) / 10, // Random rating between 3.5-5.0
                'reviews' => rand(10, 500)
            ];
        }, $competitorPrices);
    }

    private function transformCompetitorPricesToSearchResults($competitorPrices)
    {
        return array_map(function($competitor) {
            return [
                'title' => $competitor['listing_title'] ?? 'Product Listing',
                'snippet' => 'Find this product at competitive prices on ' . ($competitor['store_name'] ?? 'online store'),
                'url' => $this->generateProductLink($competitor['store_name'] ?? ''),
                'price' => '৳' . ($competitor['price_bdt'] ?? 0)
            ];
        }, $competitorPrices);
    }

    private function transformCompetitorPricesToWebsites($competitorPrices)
    {
        return array_map(function($competitor) {
            return [
                'name' => $competitor['store_name'] ?? 'Unknown Store',
                'url' => $this->generateProductLink($competitor['store_name'] ?? ''),
                'price' => '৳' . ($competitor['price_bdt'] ?? 0),
                'availability' => 'In Stock',
                'trust_score' => $this->getTrustScoreForStore($competitor['store_name'] ?? '')
            ];
        }, $competitorPrices);
    }

    private function getTrustScoreForStore($storeName)
    {
        $trustScores = [
            'daraz.com.bd' => 4.2,
            'pickaboo.com' => 4.0,
            'ajkerdeal.com' => 3.8,
            'othoba.com' => 3.7,
            'rokomari.com' => 4.1
        ];
        
        return $trustScores[$storeName] ?? 3.5;
    }

    private function generateProductLink($storeName)
    {
        $baseUrls = [
            'daraz.com.bd' => 'https://www.daraz.com.bd/products/',
            'pickaboo.com' => 'https://www.pickaboo.com/product/',
            'ajkerdeal.com' => 'https://www.ajkerdeal.com/product/',
            'othoba.com' => 'https://www.othoba.com/product/',
            'rokomari.com' => 'https://www.rokomari.com/book/'
        ];
        
        return $baseUrls[$storeName] ?? 'https://example.com/product/';
    }

    private function getStoreStrengths($storeName)
    {
        $strengths = [
            'daraz.com.bd' => ['Large customer base', 'Fast delivery', 'Multiple payment options'],
            'pickaboo.com' => ['Electronics specialist', 'Warranty support', 'Physical stores'],
            'ajkerdeal.com' => ['Competitive pricing', 'Wide product range', 'Local presence'],
            'othoba.com' => ['Fashion focus', 'Quality products', 'Customer service'],
            'rokomari.com' => ['Book specialist', 'Educational focus', 'Reliable delivery']
        ];
        
        return $strengths[$storeName] ?? ['Online presence', 'Customer service'];
    }

    private function generateRelatedProducts($data)
    {
        $productName = $data['product_name'] ?? 'Product';
        $relatedProducts = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $relatedProducts[] = [
                'name' => "Related {$productName} {$i}",
                'price' => rand(500, 2000),
                'rating' => rand(35, 50) / 10,
                'sales' => rand(50, 500)
            ];
        }
        
        return $relatedProducts;
    }

    private function getSuggestedRetailPrice($scenarios)
    {
        if (empty($scenarios)) return 0;
        
        // Get the premium pricing scenario if available
        foreach ($scenarios as $scenario) {
            if (stripos($scenario['scenario_name'], 'premium') !== false) {
                return $scenario['suggested_sale_price_bdt'] ?? 0;
            }
        }
        
        // Fallback to last scenario
        $lastScenario = end($scenarios);
        return $lastScenario['suggested_sale_price_bdt'] ?? 0;
    }

    private function getEstimatedProfitMargin($scenarios)
    {
        if (empty($scenarios)) return 0;
        
        $totalMargin = 0;
        $count = 0;
        
        foreach ($scenarios as $scenario) {
            if (isset($scenario['profit_margin_percent'])) {
                $totalMargin += $scenario['profit_margin_percent'];
                $count++;
            }
        }
        
        return $count > 0 ? round($totalMargin / $count, 2) : 0;
    }

    private function generateMarketGapsSummary($marketPriceComparison)
    {
        $competitorCount = count($marketPriceComparison['competitor_prices'] ?? []);
        $avgPrice = $marketPriceComparison['average_price_bdt'] ?? 0;
        
        if ($competitorCount < 3) {
            return "Limited competition with {$competitorCount} major competitors. Average price ৳{$avgPrice} indicates market opportunity.";
        } elseif ($avgPrice > 1000) {
            return "Premium market segment with average price ৳{$avgPrice}. Good profit margins possible.";
        } else {
            return "Competitive market with {$competitorCount} competitors. Average price ৳{$avgPrice} requires efficient operations.";
        }
    }
}