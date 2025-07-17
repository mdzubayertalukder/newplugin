<?php

namespace Plugin\Dropshipping\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleSearchService
{
    private $apiKey;
    private $searchEngineId;
    private $timeout = 30;

    public function __construct()
    {
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        $this->apiKey = $settings['google_search_api_key'] ?? '';
        $this->searchEngineId = $settings['google_search_engine_id'] ?? '';
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setSearchEngineId($searchEngineId)
    {
        $this->searchEngineId = $searchEngineId;
    }

    public function isEnabled()
    {
        return !empty($this->apiKey) && !empty($this->searchEngineId);
    }

    /**
     * Search for product information on Bangladesh e-commerce sites
     * @param string $productName
     * @param array $options
     * @return array
     */
    public function searchProduct($productName, $options = [])
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Google Custom Search API is not configured. Please set both API key and Search Engine ID.',
                'debug' => [
                    'api_key_set' => !empty($this->apiKey),
                    'search_engine_id_set' => !empty($this->searchEngineId)
                ]
            ];
        }

        try {
            $results = [];
            $allProductData = [];
            
            // Comprehensive list of Bangladesh e-commerce and marketplace sites
            $sites = [
                // Major e-commerce platforms
                'daraz.com.bd',
                'pickaboo.com',
                'ajkerdeal.com',
                'othoba.com',
                'rokomari.com',
                'bagdoom.com',
                'chaldal.com',
                'shajgoj.com',
                'bikroy.com',
                'clickbd.com',
                
                // Electronics & tech sites
                'startech.com.bd',
                'techland.com.bd',
                'ryanscomputers.com',
                'ultratech.com.bd',
                'bdstall.com',
                'cctv-bangladesh.com',
                
                // Fashion & lifestyle
                'aarong.com',
                'yellow.com.bd',
                'kaymu.com.bd',
                'fashionandlifestyle.com',
                
                // Books & education
                'boibazar.com',
                'pathokpoint.com',
                'boimela.com',
                
                // Health & beauty
                'shwapno.com',
                'meenaclick.com',
                'osudpotro.com',
                
                // Local marketplaces
                'ekhanei.com',
                'cellbazaar.com',
                'bdshop.com',
                'shopup.com.bd'
            ];

            Log::info('Starting comprehensive product search', [
                'product' => $productName,
                'total_sites' => count($sites)
            ]);

            // Search on each specific site
            foreach ($sites as $site) {
                $siteResults = $this->searchOnSite($productName, $site, ['num' => 10]);
                if ($siteResults['success'] && !empty($siteResults['data']['items'])) {
                    $results[$site] = $siteResults['data'];
                    
                    // Extract product data from each site
                    foreach ($siteResults['data']['items'] as $item) {
                        if ($item['is_product_page'] && !empty($item['title'])) {
                            $allProductData[] = [
                                'product_name' => $this->cleanProductName($item['title']),
                                'product_link' => $item['link'],
                                'product_price' => $item['price'] ? $item['price']['amount'] : null,
                                'formatted_price' => $item['price'] ? $item['price']['formatted'] : 'Price not available',
                                'site_name' => $item['site_name'],
                                'domain' => $site,
                                'description' => $item['snippet'],
                                'relevance_score' => $item['relevance_score']
                            ];
                        }
                    }
                }
                
                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }

            // Perform multiple general searches with different query variations
            $searchQueries = [
                $productName . ' price Bangladesh',
                $productName . ' buy online Bangladesh',
                $productName . ' shop Bangladesh',
                $productName . ' store Bangladesh',
                'best ' . $productName . ' Bangladesh',
                'cheap ' . $productName . ' Bangladesh'
            ];

            foreach ($searchQueries as $query) {
                $generalResults = $this->performSearch($query, ['num' => 20]);
                if ($generalResults['success']) {
                    $results['general_' . md5($query)] = $generalResults['data'];
                    
                    // Extract additional product data
                    foreach ($generalResults['data']['items'] as $item) {
                        if ($item['is_product_page'] && !empty($item['title'])) {
                            $allProductData[] = [
                                'product_name' => $this->cleanProductName($item['title']),
                                'product_link' => $item['link'],
                                'product_price' => $item['price'] ? $item['price']['amount'] : null,
                                'formatted_price' => $item['price'] ? $item['price']['formatted'] : 'Price not available',
                                'site_name' => $item['site_name'],
                                'domain' => $item['display_link'],
                                'description' => $item['snippet'],
                                'relevance_score' => $item['relevance_score']
                            ];
                        }
                    }
                }
                
                usleep(200000); // 0.2 second delay between general searches
            }

            // Remove duplicates and sort by relevance
            $allProductData = $this->removeDuplicateProducts($allProductData);
            $allProductData = $this->sortByRelevance($allProductData);

            Log::info('Comprehensive search completed', [
                'total_results' => count($results),
                'total_products_found' => count($allProductData),
                'sites_with_results' => count(array_filter($results, function($r) { return !empty($r['items']); }))
            ]);

            return [
                'success' => true,
                'data' => [
                    'product_name' => $productName,
                    'search_results' => $results,
                    'all_products' => $allProductData,
                    'competitor_analysis' => $this->analyzeCompetitors($results),
                    'price_analysis' => $this->analyzePrices($results),
                    'market_insights' => $this->generateMarketInsights($results),
                    'comprehensive_data' => [
                        'total_sites_searched' => count($sites),
                        'sites_with_results' => count(array_filter($results, function($r) { return !empty($r['items']); })),
                        'total_products_found' => count($allProductData),
                        'price_range' => $this->calculatePriceRange($allProductData),
                        'top_sites' => $this->getTopSitesByResults($results)
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Google Search API exception', [
                'message' => $e->getMessage(),
                'product' => $productName
            ]);
            return [
                'success' => false,
                'message' => 'Search request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search for a product on a specific site
     */
    private function searchOnSite($productName, $site)
    {
        $query = $productName . ' site:' . $site;
        return $this->performSearch($query, [
            'num' => 5, // Limit results per site
            'siteSearch' => $site
        ]);
    }

    /**
     * Perform the actual Google Custom Search API call
     */
    private function performSearch($query, $params = [])
    {
        try {
            $defaultParams = [
                'key' => $this->apiKey,
                'cx' => $this->searchEngineId,
                'q' => $query,
                'num' => 10,
                'gl' => 'bd', // Country: Bangladesh
                'hl' => 'en', // Language: English
                'safe' => 'active'
            ];

            $searchParams = array_merge($defaultParams, $params);

            Log::info('Google Custom Search API Request', [
                'query' => $query,
                'params' => array_keys($searchParams)
            ]);

            $response = Http::timeout($this->timeout)
                ->get('https://www.googleapis.com/customsearch/v1', $searchParams);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Google Search API Response received', [
                    'total_results' => $data['searchInformation']['totalResults'] ?? 0,
                    'items_count' => count($data['items'] ?? [])
                ]);

                return [
                    'success' => true,
                    'data' => $this->processSearchResults($data)
                ];
            } else {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                
                Log::error('Google Search API error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'query' => $query
                ]);
                
                $errorMessage = 'Search API request failed: ' . $response->status();
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ' - ' . $errorData['error']['message'];
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'debug' => [
                        'status_code' => $response->status(),
                        'error_body' => $errorBody,
                        'query' => $query,
                        'api_key_length' => strlen($this->apiKey),
                        'search_engine_id' => $this->searchEngineId
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Google Search API call exception', [
                'message' => $e->getMessage(),
                'query' => $query
            ]);
            return [
                'success' => false,
                'message' => 'Search API call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process and clean search results
     */
    private function processSearchResults($data)
    {
        $items = $data['items'] ?? [];
        $processedResults = [];

        foreach ($items as $item) {
            $processedResults[] = [
                'title' => $item['title'] ?? '',
                'link' => $item['link'] ?? '',
                'snippet' => $item['snippet'] ?? '',
                'display_link' => $item['displayLink'] ?? '',
                'formatted_url' => $item['formattedUrl'] ?? '',
                'price' => $this->extractPrice($item['snippet'] ?? ''),
                'site_name' => $this->extractSiteName($item['displayLink'] ?? ''),
                'is_product_page' => $this->isProductPage($item['link'] ?? ''),
                'relevance_score' => $this->calculateRelevanceScore($item)
            ];
        }

        return [
            'total_results' => $data['searchInformation']['totalResults'] ?? 0,
            'search_time' => $data['searchInformation']['searchTime'] ?? 0,
            'items' => $processedResults,
            'processed_count' => count($processedResults)
        ];
    }

    /**
     * Extract price from snippet text
     */
    private function extractPrice($snippet)
    {
        // Look for Bangladesh Taka prices
        if (preg_match('/৳\s*([0-9,]+(?:\.[0-9]{2})?)/i', $snippet, $matches)) {
            return [
                'amount' => (float) str_replace(',', '', $matches[1]),
                'currency' => 'BDT',
                'formatted' => '৳' . $matches[1]
            ];
        }
        
        // Look for other price patterns
        if (preg_match('/(?:price|cost|৳|tk|taka)[\s:]*([0-9,]+(?:\.[0-9]{2})?)/i', $snippet, $matches)) {
            return [
                'amount' => (float) str_replace(',', '', $matches[1]),
                'currency' => 'BDT',
                'formatted' => '৳' . $matches[1]
            ];
        }

        return null;
    }

    /**
     * Extract site name from display link
     */
    private function extractSiteName($displayLink)
    {
        $siteNames = [
            'daraz.com.bd' => 'Daraz Bangladesh',
            'pickaboo.com' => 'Pickaboo',
            'ajkerdeal.com' => 'AjkerDeal',
            'othoba.com' => 'Othoba',
            'rokomari.com' => 'Rokomari',
            'bagdoom.com' => 'Bagdoom',
            'chaldal.com' => 'Chaldal'
        ];

        foreach ($siteNames as $domain => $name) {
            if (strpos($displayLink, $domain) !== false) {
                return $name;
            }
        }

        return $displayLink;
    }

    /**
     * Check if the link is likely a product page
     */
    private function isProductPage($link)
    {
        $productIndicators = [
            '/product/',
            '/item/',
            '/p/',
            '/products/',
            '-p-',
            '/buy/',
            '/shop/'
        ];

        foreach ($productIndicators as $indicator) {
            if (strpos($link, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate relevance score for search result
     */
    private function calculateRelevanceScore($item)
    {
        $score = 0;
        
        // Higher score for product pages
        if ($this->isProductPage($item['link'] ?? '')) {
            $score += 30;
        }
        
        // Higher score for known e-commerce sites
        $ecommerceSites = ['daraz.com.bd', 'pickaboo.com', 'ajkerdeal.com', 'othoba.com', 'rokomari.com'];
        foreach ($ecommerceSites as $site) {
            if (strpos($item['displayLink'] ?? '', $site) !== false) {
                $score += 25;
                break;
            }
        }
        
        // Higher score if price is found
        if ($this->extractPrice($item['snippet'] ?? '')) {
            $score += 20;
        }
        
        // Higher score for relevant keywords in title
        $relevantKeywords = ['buy', 'price', 'shop', 'online', 'delivery', 'bangladesh'];
        $title = strtolower($item['title'] ?? '');
        foreach ($relevantKeywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $score += 5;
            }
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Analyze competitors from search results
     */
    private function analyzeCompetitors($results)
    {
        $competitors = [];
        
        foreach ($results as $site => $siteData) {
            if ($site === 'general') continue;
            
            $items = $siteData['items'] ?? [];
            $prices = [];
            $productCount = 0;
            
            foreach ($items as $item) {
                if ($item['is_product_page'] && $item['price']) {
                    $prices[] = $item['price']['amount'];
                    $productCount++;
                }
            }
            
            if (!empty($prices)) {
                $competitors[] = [
                    'site' => $site,
                    'site_name' => $this->extractSiteName($site),
                    'product_count' => $productCount,
                    'avg_price' => round(array_sum($prices) / count($prices), 2),
                    'min_price' => min($prices),
                    'max_price' => max($prices),
                    'price_range' => '৳' . min($prices) . ' - ৳' . max($prices),
                    'market_presence' => $this->getMarketPresence($site)
                ];
            }
        }
        
        // Sort by average price
        usort($competitors, function($a, $b) {
            return $a['avg_price'] <=> $b['avg_price'];
        });
        
        return $competitors;
    }

    /**
     * Analyze prices from search results
     */
    private function analyzePrices($results)
    {
        $allPrices = [];
        
        foreach ($results as $siteData) {
            $items = $siteData['items'] ?? [];
            foreach ($items as $item) {
                if ($item['price']) {
                    $allPrices[] = $item['price']['amount'];
                }
            }
        }
        
        if (empty($allPrices)) {
            return [
                'found_prices' => false,
                'message' => 'No prices found in search results'
            ];
        }
        
        sort($allPrices);
        $count = count($allPrices);
        
        return [
            'found_prices' => true,
            'total_prices_found' => $count,
            'lowest_price' => min($allPrices),
            'highest_price' => max($allPrices),
            'average_price' => round(array_sum($allPrices) / $count, 2),
            'median_price' => $count % 2 == 0 
                ? ($allPrices[$count/2 - 1] + $allPrices[$count/2]) / 2 
                : $allPrices[floor($count/2)],
            'price_distribution' => [
                'under_500' => count(array_filter($allPrices, fn($p) => $p < 500)),
                '500_to_1000' => count(array_filter($allPrices, fn($p) => $p >= 500 && $p < 1000)),
                '1000_to_2000' => count(array_filter($allPrices, fn($p) => $p >= 1000 && $p < 2000)),
                'over_2000' => count(array_filter($allPrices, fn($p) => $p >= 2000))
            ],
            'formatted_range' => '৳' . min($allPrices) . ' - ৳' . max($allPrices)
        ];
    }

    /**
     * Generate market insights from search results
     */
    private function generateMarketInsights($results)
    {
        $totalResults = 0;
        $productPages = 0;
        $siteCoverage = [];
        
        foreach ($results as $site => $siteData) {
            $totalResults += $siteData['total_results'] ?? 0;
            $items = $siteData['items'] ?? [];
            
            foreach ($items as $item) {
                if ($item['is_product_page']) {
                    $productPages++;
                }
            }
            
            if ($site !== 'general') {
                $siteCoverage[$site] = count($items);
            }
        }
        
        return [
            'market_availability' => $totalResults > 100 ? 'High' : ($totalResults > 20 ? 'Medium' : 'Low'),
            'total_search_results' => $totalResults,
            'product_pages_found' => $productPages,
            'site_coverage' => $siteCoverage,
            'market_saturation' => $this->calculateMarketSaturation($totalResults, $productPages),
            'competition_level' => $this->calculateCompetitionLevel($siteCoverage),
            'search_insights' => [
                'most_active_site' => $this->getMostActiveSite($siteCoverage),
                'market_leaders' => array_keys(array_slice(arsort($siteCoverage) ? $siteCoverage : [], 0, 3, true)),
                'opportunity_sites' => $this->getOpportunitySites($siteCoverage)
            ]
        ];
    }

    /**
     * Get market presence score for a site
     */
    private function getMarketPresence($site)
    {
        $marketShares = [
            'daraz.com.bd' => 40,
            'pickaboo.com' => 18,
            'ajkerdeal.com' => 15,
            'rokomari.com' => 12,
            'othoba.com' => 8,
            'bagdoom.com' => 4,
            'chaldal.com' => 3
        ];
        
        return $marketShares[$site] ?? 5;
    }

    /**
     * Calculate market saturation level
     */
    private function calculateMarketSaturation($totalResults, $productPages)
    {
        $ratio = $productPages > 0 ? $totalResults / $productPages : 0;
        
        if ($ratio > 50) return 'High';
        if ($ratio > 20) return 'Medium';
        return 'Low';
    }

    /**
     * Calculate competition level
     */
    private function calculateCompetitionLevel($siteCoverage)
    {
        $activeSites = count(array_filter($siteCoverage, fn($count) => $count > 0));
        
        if ($activeSites >= 4) return 'High';
        if ($activeSites >= 2) return 'Medium';
        return 'Low';
    }

    /**
     * Get most active site
     */
    private function getMostActiveSite($siteCoverage)
    {
        if (empty($siteCoverage)) return null;
        
        $maxSite = array_keys($siteCoverage, max($siteCoverage))[0];
        return $this->extractSiteName($maxSite);
    }

    /**
     * Get opportunity sites (sites with low presence)
     */
    private function getOpportunitySites($siteCoverage)
    {
        $opportunities = [];
        
        foreach ($siteCoverage as $site => $count) {
            if ($count < 2) { // Low presence indicates opportunity
                $opportunities[] = $this->extractSiteName($site);
            }
        }
        
        return $opportunities;
    }

    /**
     * Clean product name by removing unnecessary text
     */
    private function cleanProductName($title)
    {
        // Remove common e-commerce suffixes and prefixes
        $cleanTitle = preg_replace('/\s*-\s*(Buy|Shop|Online|Price|Bangladesh|BD|Daraz|Pickaboo|AjkerDeal|Othoba|Rokomari).*$/i', '', $title);
        $cleanTitle = preg_replace('/^(Buy|Shop|Online)\s+/i', '', $cleanTitle);
        
        // Remove price information from title
        $cleanTitle = preg_replace('/\s*৳\s*[0-9,]+(\.[0-9]{2})?\s*/', ' ', $cleanTitle);
        $cleanTitle = preg_replace('/\s*Price:\s*[0-9,]+\s*/', ' ', $cleanTitle);
        
        // Clean up extra whitespace
        $cleanTitle = preg_replace('/\s+/', ' ', trim($cleanTitle));
        
        return $cleanTitle;
    }

    /**
     * Remove duplicate products based on name and link similarity
     */
    private function removeDuplicateProducts($products)
    {
        $uniqueProducts = [];
        $seenLinks = [];
        $seenNames = [];
        
        foreach ($products as $product) {
            $link = $product['product_link'];
            $name = strtolower(trim($product['product_name']));
            
            // Skip if exact link already exists
            if (in_array($link, $seenLinks)) {
                continue;
            }
            
            // Check for similar product names (80% similarity)
            $isDuplicate = false;
            foreach ($seenNames as $seenName) {
                $similarity = 0;
                similar_text($name, $seenName, $similarity);
                if ($similarity > 80) {
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $uniqueProducts[] = $product;
                $seenLinks[] = $link;
                $seenNames[] = $name;
            }
        }
        
        return $uniqueProducts;
    }

    /**
     * Sort products by relevance score
     */
    private function sortByRelevance($products)
    {
        usort($products, function($a, $b) {
            // First sort by relevance score (descending)
            if ($a['relevance_score'] !== $b['relevance_score']) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            }
            
            // Then by price availability (products with prices first)
            $aHasPrice = !is_null($a['product_price']);
            $bHasPrice = !is_null($b['product_price']);
            
            if ($aHasPrice !== $bHasPrice) {
                return $bHasPrice <=> $aHasPrice;
            }
            
            // Finally by price (ascending for products with prices)
            if ($aHasPrice && $bHasPrice) {
                return $a['product_price'] <=> $b['product_price'];
            }
            
            return 0;
        });
        
        return $products;
    }

    /**
     * Calculate price range from product data
     */
    private function calculatePriceRange($products)
    {
        $prices = array_filter(array_column($products, 'product_price'), function($price) {
            return !is_null($price) && $price > 0;
        });
        
        if (empty($prices)) {
            return [
                'min' => null,
                'max' => null,
                'average' => null,
                'formatted' => 'Price information not available'
            ];
        }
        
        $min = min($prices);
        $max = max($prices);
        $average = round(array_sum($prices) / count($prices), 2);
        
        return [
            'min' => $min,
            'max' => $max,
            'average' => $average,
            'formatted' => '৳' . number_format($min) . ' - ৳' . number_format($max),
            'average_formatted' => '৳' . number_format($average)
        ];
    }

    /**
     * Get top sites by number of results
     */
    private function getTopSitesByResults($results)
    {
        $siteStats = [];
        
        foreach ($results as $site => $siteData) {
            if (strpos($site, 'general_') === 0) {
                continue; // Skip general search results
            }
            
            $items = $siteData['items'] ?? [];
            $productCount = 0;
            $priceCount = 0;
            
            foreach ($items as $item) {
                if ($item['is_product_page']) {
                    $productCount++;
                    if ($item['price']) {
                        $priceCount++;
                    }
                }
            }
            
            if ($productCount > 0) {
                $siteStats[] = [
                    'site' => $site,
                    'site_name' => $this->extractSiteName($site),
                    'total_results' => $siteData['total_results'] ?? 0,
                    'product_pages' => $productCount,
                    'products_with_prices' => $priceCount,
                    'price_coverage' => $productCount > 0 ? round(($priceCount / $productCount) * 100, 1) : 0
                ];
            }
        }
        
        // Sort by product pages found (descending)
        usort($siteStats, function($a, $b) {
            return $b['product_pages'] <=> $a['product_pages'];
        });
        
        return array_slice($siteStats, 0, 10); // Return top 10 sites
    }
}