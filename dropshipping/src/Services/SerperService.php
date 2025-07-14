<?php

namespace Plugin\Dropshipping\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SerperService
{
    private $apiKey;
    private $baseUrl = 'https://google.serper.dev';
    private $timeout = 30;

    public function __construct()
    {
        $this->apiKey = $this->getSerperApiKey();
    }

    /**
     * Get Serper API key from settings
     */
    private function getSerperApiKey()
    {
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        return $settings['serper_api_key'] ?? null;
    }

    /**
     * Check if Serper API is configured and enabled
     */
    public function isEnabled()
    {
        return !empty($this->apiKey);
    }

    /**
     * Search for product information and competitors
     */
    public function searchProduct($productName, $limit = 10)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Serper API not configured'];
        }

        $cacheKey = 'serper_product_search_' . md5($productName . $limit);
        
        return Cache::remember($cacheKey, 300, function () use ($productName, $limit) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'X-API-KEY' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->baseUrl . '/search', [
                        'q' => $productName,
                        'type' => 'search',
                        'num' => $limit,
                        'autocorrect' => true,
                        'page' => 1,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'data' => $this->processSearchResults($data),
                        'raw' => $data
                    ];
                }

                Log::error('Serper API search failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'API request failed: ' . $response->status()
                ];

            } catch (\Exception $e) {
                Log::error('Serper API search exception', [
                    'message' => $e->getMessage(),
                    'product' => $productName
                ]);

                return [
                    'success' => false,
                    'message' => 'Search failed: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Search for shopping/product prices
     */
    public function searchShopping($productName, $limit = 10)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Serper API not configured'];
        }

        $cacheKey = 'serper_shopping_search_' . md5($productName . $limit);
        
        return Cache::remember($cacheKey, 300, function () use ($productName, $limit) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'X-API-KEY' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->baseUrl . '/search', [
                        'q' => $productName,
                        'type' => 'shopping',
                        'num' => $limit,
                        'autocorrect' => true,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'data' => $this->processShoppingResults($data),
                        'raw' => $data
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Shopping API request failed: ' . $response->status()
                ];

            } catch (\Exception $e) {
                Log::error('Serper shopping search exception', [
                    'message' => $e->getMessage(),
                    'product' => $productName
                ]);

                return [
                    'success' => false,
                    'message' => 'Shopping search failed: ' . $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get comprehensive product research data
     */
    public function getProductResearch($productName, $includeImages = true)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Serper API not configured'];
        }

        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        $limit = (int) ($settings['research_results_limit'] ?? 10);

        $results = [
            'product_name' => $productName,
            'search_results' => [],
            'shopping_results' => [],
            'price_analysis' => [],
            'seo_insights' => [],
            'competitor_websites' => [],
            'suggested_titles' => [],
            'meta_descriptions' => [],
            'dropshipping_analysis' => [],
            'product_images' => [],
            'detailed_competitors' => []
        ];

        // Get regular search results
        $searchResults = $this->searchProduct($productName, $limit);
        if ($searchResults['success']) {
            $results['search_results'] = $searchResults['data'];
            $results['seo_insights'] = $this->extractSeoInsights($searchResults['data']);
            $results['competitor_websites'] = $this->extractCompetitorWebsites($searchResults['data']);
        }

        // Get shopping results for price comparison
        $shoppingResults = $this->searchShopping($productName, $limit);
        if ($shoppingResults['success']) {
            $results['shopping_results'] = $shoppingResults['data'];
            $results['price_analysis'] = $this->analyzePrices($shoppingResults['data']);
        }

        // Generate SEO suggestions
        $results['suggested_titles'] = $this->generateTitleSuggestions($productName, $results['search_results']);
        $results['meta_descriptions'] = $this->generateMetaDescriptions($productName, $results['search_results']);

        // Extract product images
        if ($includeImages) {
            $results['product_images'] = $this->extractProductImages($results['search_results'], $results['shopping_results']);
        }

        // Generate dropshipping analysis
        $results['dropshipping_analysis'] = $this->analyzeDropshippingViability($results['price_analysis'], $results['competitor_websites'], $results['shopping_results']);
        
        // Get detailed competitor information
        $results['detailed_competitors'] = $this->getDetailedCompetitors($results['shopping_results']);

        return [
            'success' => true,
            'data' => $results
        ];
    }

    /**
     * Process regular search results
     */
    private function processSearchResults($data)
    {
        $results = [];
        
        if (isset($data['organic'])) {
            foreach ($data['organic'] as $result) {
                $results[] = [
                    'title' => $result['title'] ?? '',
                    'link' => $result['link'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'domain' => $this->extractDomain($result['link'] ?? ''),
                    'position' => $result['position'] ?? 0,
                ];
            }
        }

        return $results;
    }

    /**
     * Process shopping search results
     */
    private function processShoppingResults($data)
    {
        $results = [];
        
        if (isset($data['shopping'])) {
            foreach ($data['shopping'] as $result) {
                $price = $this->extractPrice($result['price'] ?? '');
                $results[] = [
                    'title' => $result['title'] ?? '',
                    'link' => $result['link'] ?? '',
                    'price' => $price,
                    'price_formatted' => $result['price'] ?? '',
                    'source' => $result['source'] ?? '',
                    'image' => $result['imageUrl'] ?? '',
                    'rating' => $result['rating'] ?? null,
                    'reviews' => $result['reviews'] ?? null,
                ];
            }
        }

        return $results;
    }

    /**
     * Analyze price data from shopping results
     */
    private function analyzePrices($shoppingResults)
    {
        if (empty($shoppingResults)) {
            return [];
        }

        $prices = array_filter(array_map(function($item) {
            return $item['price'] ?? null;
        }, $shoppingResults));

        if (empty($prices)) {
            return [];
        }

        sort($prices);

        return [
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => round(array_sum($prices) / count($prices), 2),
            'median_price' => $this->getMedian($prices),
            'price_range' => max($prices) - min($prices),
            'total_sources' => count($prices),
            'price_distribution' => $this->getPriceDistribution($prices),
        ];
    }

    /**
     * Extract SEO insights from search results
     */
    private function extractSeoInsights($searchResults)
    {
        $insights = [
            'common_keywords' => [],
            'title_patterns' => [],
            'description_patterns' => [],
            'competitor_analysis' => []
        ];

        $allTitles = [];
        $allSnippets = [];

        foreach ($searchResults as $result) {
            $allTitles[] = $result['title'];
            $allSnippets[] = $result['snippet'];
        }

        // Extract common keywords
        $insights['common_keywords'] = $this->extractCommonKeywords($allTitles, $allSnippets);
        
        // Analyze title patterns
        $insights['title_patterns'] = $this->analyzeTitlePatterns($allTitles);
        
        // Analyze description patterns
        $insights['description_patterns'] = $this->analyzeDescriptionPatterns($allSnippets);

        return $insights;
    }

    /**
     * Extract competitor websites
     */
    private function extractCompetitorWebsites($searchResults)
    {
        $websites = [];
        
        foreach ($searchResults as $result) {
            $domain = $result['domain'];
            if (!isset($websites[$domain])) {
                $websites[$domain] = [
                    'domain' => $domain,
                    'count' => 0,
                    'titles' => [],
                    'links' => []
                ];
            }
            
            $websites[$domain]['count']++;
            $websites[$domain]['titles'][] = $result['title'];
            $websites[$domain]['links'][] = $result['link'];
        }

        // Sort by frequency
        uasort($websites, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_values($websites);
    }

    /**
     * Generate title suggestions based on research
     */
    private function generateTitleSuggestions($productName, $searchResults)
    {
        $suggestions = [];
        
        // Extract common words and patterns from successful titles
        $commonWords = $this->extractCommonWords(array_column($searchResults, 'title'));
        
        // Generate variations
        $suggestions[] = $productName; // Original
        $suggestions[] = "Best " . $productName . " 2024";
        $suggestions[] = $productName . " - Premium Quality";
        $suggestions[] = "Professional " . $productName;
        $suggestions[] = $productName . " | Top Rated";
        
        // Add suggestions based on common patterns
        foreach ($commonWords as $word) {
            if (stripos($productName, $word) === false) {
                $suggestions[] = $productName . " " . $word;
                $suggestions[] = $word . " " . $productName;
            }
        }

        return array_unique(array_slice($suggestions, 0, 10));
    }

    /**
     * Generate meta description suggestions
     */
    private function generateMetaDescriptions($productName, $searchResults)
    {
        $descriptions = [];
        
        // Extract common phrases from snippets
        $snippets = array_column($searchResults, 'snippet');
        $commonPhrases = $this->extractCommonPhrases($snippets);
        
        // Generate base descriptions
        $descriptions[] = "Shop high-quality {$productName} at competitive prices. Fast shipping and excellent customer service guaranteed.";
        $descriptions[] = "Find the best {$productName} deals online. Compare prices, read reviews, and buy with confidence.";
        $descriptions[] = "Premium {$productName} available now. Top-rated products with fast delivery and money-back guarantee.";
        
        // Add descriptions based on research
        foreach ($commonPhrases as $phrase) {
            $descriptions[] = "Discover {$productName} - {$phrase}. Shop now for the best deals and fast shipping.";
        }

        return array_unique(array_slice($descriptions, 0, 8));
    }

    /**
     * Helper method to extract domain from URL
     */
    private function extractDomain($url)
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Helper method to extract numeric price
     */
    private function extractPrice($priceString)
    {
        $price = preg_replace('/[^\d.]/', '', $priceString);
        return $price ? (float) $price : null;
    }

    /**
     * Helper method to calculate median
     */
    private function getMedian($numbers)
    {
        $count = count($numbers);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $numbers[$middle];
        } else {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        }
    }

    /**
     * Helper method for price distribution
     */
    private function getPriceDistribution($prices)
    {
        $min = min($prices);
        $max = max($prices);
        $range = $max - $min;
        
        if ($range == 0) return ['single_price' => $min];
        
        $buckets = 5;
        $bucketSize = $range / $buckets;
        $distribution = [];
        
        for ($i = 0; $i < $buckets; $i++) {
            $start = $min + ($i * $bucketSize);
            $end = $start + $bucketSize;
            $count = 0;
            
            foreach ($prices as $price) {
                if ($price >= $start && ($price < $end || $i == $buckets - 1)) {
                    $count++;
                }
            }
            
            $distribution[] = [
                'range' => '$' . round($start, 2) . ' - $' . round($end, 2),
                'count' => $count
            ];
        }
        
        return $distribution;
    }

    /**
     * Extract common keywords from text arrays
     */
    private function extractCommonKeywords($titles, $snippets)
    {
        $allText = implode(' ', array_merge($titles, $snippets));
        $words = preg_split('/\s+/', strtolower($allText));
        
        // Remove common stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those'];
        
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        return array_slice(array_keys($wordCount), 0, 10);
    }

    /**
     * Extract common words from title array
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
        
        return array_slice(array_keys($wordCount), 0, 5);
    }

    /**
     * Extract common phrases from snippets
     */
    private function extractCommonPhrases($snippets)
    {
        $phrases = [];
        
        foreach ($snippets as $snippet) {
            // Extract phrases between 3-8 words
            $sentences = preg_split('/[.!?]/', $snippet);
            foreach ($sentences as $sentence) {
                $words = preg_split('/\s+/', trim($sentence));
                if (count($words) >= 3 && count($words) <= 8) {
                    $phrases[] = trim($sentence);
                }
            }
        }
        
        // Get most common phrases
        $phraseCount = array_count_values($phrases);
        arsort($phraseCount);
        
        return array_slice(array_keys($phraseCount), 0, 5);
    }

    /**
     * Analyze title patterns
     */
    private function analyzeTitlePatterns($titles)
    {
        $patterns = [
            'pipe_separator' => 0,
            'dash_separator' => 0,
            'contains_year' => 0,
            'quality_words' => 0,
            'long_titles' => 0
        ];
        
        foreach ($titles as $title) {
            if (strpos($title, '|') !== false) $patterns['pipe_separator']++;
            if (strpos($title, '-') !== false) $patterns['dash_separator']++;
            if (preg_match('/\d{4}/', $title)) $patterns['contains_year']++;
            if (preg_match('/best|top|premium|quality/i', $title)) $patterns['quality_words']++;
            if (strlen($title) > 60) $patterns['long_titles']++;
        }
        
        return $patterns;
    }

    /**
     * Analyze description patterns
     */
    private function analyzeDescriptionPatterns($snippets)
    {
        $patterns = [
            'shipping_mentions' => 0,
            'price_mentions' => 0,
            'review_mentions' => 0,
            'guarantee_mentions' => 0
        ];
        
        foreach ($snippets as $snippet) {
            if (preg_match('/free shipping|fast delivery/i', $snippet)) $patterns['shipping_mentions']++;
            if (preg_match('/price|deal|discount|sale/i', $snippet)) $patterns['price_mentions']++;
            if (preg_match('/review|rating|star/i', $snippet)) $patterns['review_mentions']++;
            if (preg_match('/guarantee|warranty|return/i', $snippet)) $patterns['guarantee_mentions']++;
        }
        
        return $patterns;
    }

    /**
     * Extract product images from search and shopping results
     */
    private function extractProductImages($searchResults, $shoppingResults)
    {
        $images = [];
        
        // Extract from shopping results first (usually better quality)
        foreach ($shoppingResults as $result) {
            if (!empty($result['image'])) {
                $images[] = [
                    'url' => $result['image'],
                    'source' => $result['source'] ?? 'Unknown',
                    'title' => $result['title'] ?? '',
                    'price' => $result['price_formatted'] ?? 'N/A'
                ];
            }
        }
        
        // Extract from search results if we need more images
        foreach ($searchResults as $result) {
            if (!empty($result['image']) && count($images) < 10) {
                $images[] = [
                    'url' => $result['image'],
                    'source' => $result['domain'] ?? 'Unknown',
                    'title' => $result['title'] ?? '',
                    'price' => 'N/A'
                ];
            }
        }
        
        return array_slice($images, 0, 8); // Limit to 8 images
    }

    /**
     * Analyze dropshipping viability
     */
    private function analyzeDropshippingViability($priceAnalysis, $competitorWebsites, $shoppingResults)
    {
        $analysis = [
            'viability_score' => 0,
            'viability_level' => 'Unknown',
            'competition_level' => 'Unknown',
            'profit_potential' => 'Unknown',
            'market_saturation' => 'Unknown',
            'recommendations' => [],
            'pros' => [],
            'cons' => [],
            'suggested_markup' => '50-100%',
            'target_audience' => 'General',
            'seasonal_trends' => 'Stable'
        ];

        if (empty($priceAnalysis) || empty($shoppingResults)) {
            $analysis['recommendations'][] = 'Not enough price data to analyze dropshipping viability';
            return $analysis;
        }

        $score = 0;
        $competitorCount = count($competitorWebsites);
        $priceRange = $priceAnalysis['price_range'] ?? 0;
        $avgPrice = $priceAnalysis['avg_price'] ?? 0;
        $minPrice = $priceAnalysis['min_price'] ?? 0;

        // Analyze competition level
        if ($competitorCount <= 5) {
            $analysis['competition_level'] = 'Low';
            $score += 30;
            $analysis['pros'][] = 'Low competition - easier market entry';
        } elseif ($competitorCount <= 15) {
            $analysis['competition_level'] = 'Medium';
            $score += 20;
            $analysis['pros'][] = 'Moderate competition - proven market demand';
        } else {
            $analysis['competition_level'] = 'High';
            $score += 10;
            $analysis['cons'][] = 'High competition - difficult to stand out';
        }

        // Analyze price range and profit potential
        if ($avgPrice >= 50) {
            $score += 25;
            $analysis['profit_potential'] = 'High';
            $analysis['pros'][] = 'High-value product - good profit margins possible';
            $analysis['suggested_markup'] = '50-80%';
        } elseif ($avgPrice >= 20) {
            $score += 20;
            $analysis['profit_potential'] = 'Medium';
            $analysis['pros'][] = 'Medium-value product - decent profit margins';
            $analysis['suggested_markup'] = '80-120%';
        } else {
            $score += 10;
            $analysis['profit_potential'] = 'Low';
            $analysis['cons'][] = 'Low-value product - thin profit margins';
            $analysis['suggested_markup'] = '100-200%';
        }

        // Analyze price variance
        if ($priceRange > ($avgPrice * 0.5)) {
            $score += 15;
            $analysis['pros'][] = 'High price variance - room for competitive pricing';
        } else {
            $analysis['cons'][] = 'Low price variance - limited pricing flexibility';
        }

        // Market saturation analysis
        $uniqueDomains = array_unique(array_column($shoppingResults, 'source'));
        $saturationRatio = count($uniqueDomains) / count($shoppingResults);
        
        if ($saturationRatio > 0.7) {
            $analysis['market_saturation'] = 'Low';
            $score += 20;
            $analysis['pros'][] = 'Diverse market - not dominated by few sellers';
        } elseif ($saturationRatio > 0.4) {
            $analysis['market_saturation'] = 'Medium';
            $score += 15;
        } else {
            $analysis['market_saturation'] = 'High';
            $analysis['cons'][] = 'Market dominated by few major sellers';
        }

        // Calculate final viability
        $analysis['viability_score'] = min(100, $score);
        
        if ($score >= 80) {
            $analysis['viability_level'] = 'Excellent';
            $analysis['recommendations'][] = 'Highly recommended for dropshipping';
            $analysis['recommendations'][] = 'Focus on unique value proposition and customer service';
        } elseif ($score >= 60) {
            $analysis['viability_level'] = 'Good';
            $analysis['recommendations'][] = 'Good dropshipping opportunity with proper strategy';
            $analysis['recommendations'][] = 'Consider targeting specific niches or demographics';
        } elseif ($score >= 40) {
            $analysis['viability_level'] = 'Fair';
            $analysis['recommendations'][] = 'Proceed with caution - requires strong marketing';
            $analysis['recommendations'][] = 'Focus on building brand trust and customer loyalty';
        } else {
            $analysis['viability_level'] = 'Poor';
            $analysis['recommendations'][] = 'Not recommended for dropshipping';
            $analysis['recommendations'][] = 'Consider finding alternative products with better margins';
        }

        return $analysis;
    }

    /**
     * Get detailed competitor information
     */
    private function getDetailedCompetitors($shoppingResults)
    {
        $competitors = [];
        
        foreach ($shoppingResults as $result) {
            $domain = parse_url($result['link'] ?? '', PHP_URL_HOST) ?? $result['source'];
            
            if (!isset($competitors[$domain])) {
                $competitors[$domain] = [
                    'domain' => $domain,
                    'name' => $result['source'] ?? $domain,
                    'products' => [],
                    'price_range' => ['min' => PHP_FLOAT_MAX, 'max' => 0],
                    'avg_price' => 0,
                    'total_products' => 0,
                    'market_position' => 'Unknown',
                    'trust_indicators' => []
                ];
            }
            
            $price = $result['price'] ?? 0;
            if ($price > 0) {
                $competitors[$domain]['products'][] = [
                    'title' => $result['title'] ?? '',
                    'price' => $price,
                    'price_formatted' => $result['price_formatted'] ?? '',
                    'link' => $result['link'] ?? '',
                    'image' => $result['image'] ?? '',
                    'rating' => $result['rating'] ?? null,
                    'reviews' => $result['reviews'] ?? null
                ];
                
                $competitors[$domain]['price_range']['min'] = min($competitors[$domain]['price_range']['min'], $price);
                $competitors[$domain]['price_range']['max'] = max($competitors[$domain]['price_range']['max'], $price);
                $competitors[$domain]['total_products']++;
            }
        }
        
        // Calculate averages and determine market position
        foreach ($competitors as $domain => &$competitor) {
            if ($competitor['total_products'] > 0) {
                $totalPrice = array_sum(array_column($competitor['products'], 'price'));
                $competitor['avg_price'] = round($totalPrice / $competitor['total_products'], 2);
                
                // Determine market position based on price and presence
                if ($competitor['total_products'] >= 5) {
                    $competitor['market_position'] = 'Market Leader';
                } elseif ($competitor['total_products'] >= 3) {
                    $competitor['market_position'] = 'Major Player';
                } else {
                    $competitor['market_position'] = 'Niche Player';
                }
                
                // Add trust indicators
                if ($competitor['avg_price'] < 20) {
                    $competitor['trust_indicators'][] = 'Budget-focused';
                } elseif ($competitor['avg_price'] > 100) {
                    $competitor['trust_indicators'][] = 'Premium positioning';
                }
                
                if ($competitor['total_products'] >= 5) {
                    $competitor['trust_indicators'][] = 'Wide product range';
                }
            }
        }
        
        // Sort by market presence (total products)
        uasort($competitors, function($a, $b) {
            return $b['total_products'] - $a['total_products'];
        });
        
        return array_values($competitors);
    }
} 