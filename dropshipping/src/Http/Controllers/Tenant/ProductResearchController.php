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