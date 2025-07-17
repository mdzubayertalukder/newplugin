<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AISettingsController extends Controller
{
    public function index()
    {
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key')->toArray();
        return view('plugin/dropshipping::admin.settings.ai', compact('settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ai_service' => 'required|string|in:google,openai',
            'google_ai_studio_api_key' => 'nullable|string',
            'google_ai_studio_api_endpoint' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'google_search_api_key' => 'nullable|string',
            'google_search_engine_id' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            DB::table('dropshipping_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    public function testOpenAI(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        $service = new \Plugin\Dropshipping\Services\OpenAIService();
        $service->setApiKey($request->api_key);

        // Use a very simple prompt for testing
        $testPrompt = 'Please respond with a simple JSON object: {"test": "success", "message": "API is working"}';
        $response = $service->researchProduct($testPrompt);

        if ($response['success'] && isset($response['data'])) {
            return response()->json([
                'success' => true,
                'message' => 'OpenAI API connection successful!',
                'response' => is_array($response['data']) ? json_encode($response['data']) : $response['data']
            ]);
        } else {
            \Illuminate\Support\Facades\Log::error('OpenAI Test Failed', ['response' => $response]);
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'OpenAI API test failed. Check logs for details.',
                'full_response' => $response
            ]);
        }
    }

    public function testGoogleAI(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        try {
            $service = new \Plugin\Dropshipping\Services\GoogleAIStudioService();
            $service->setApiKey($request->api_key);

            // Use a very simple prompt for testing
            $testPrompt = 'Please respond with a simple JSON object: {"test": "success", "message": "API is working"}';
            $response = $service->researchProduct($testPrompt);

            if ($response['success'] && isset($response['data'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google AI Studio API connection successful!',
                    'response' => is_array($response['data']) ? json_encode($response['data']) : $response['data']
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Google AI Test Failed', ['response' => $response]);
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Google AI Studio API test failed. Check logs for details.',
                    'full_response' => $response
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google AI Studio API test failed: ' . $e->getMessage()
            ]);
        }
    }

    public function testGoogleSearch(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'search_engine_id' => 'required|string',
        ]);

        try {
            $service = new \Plugin\Dropshipping\Services\GoogleSearchService();
            $service->setApiKey($request->api_key);
            $service->setSearchEngineId($request->search_engine_id);

            // Test with a simple search query
            $testQuery = 'smartphone price Bangladesh';
            $response = $service->searchProduct($testQuery);

            if ($response['success'] && isset($response['data'])) {
                $searchData = $response['data'];
                $totalItems = 0;
                $sampleResults = [];
                
                // Extract items from search results
                if (isset($searchData['search_results'])) {
                    foreach ($searchData['search_results'] as $siteResults) {
                        if (isset($siteResults['items'])) {
                            $totalItems += count($siteResults['items']);
                            // Get sample results from first site
                            if (empty($sampleResults) && !empty($siteResults['items'])) {
                                $sampleResults = array_slice($siteResults['items'], 0, 2);
                            }
                        }
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Google Custom Search API connection successful!',
                    'response' => [
                        'total_items_found' => $totalItems,
                        'has_competitor_analysis' => isset($searchData['competitor_analysis']),
                        'has_price_analysis' => isset($searchData['price_analysis']),
                        'sample_results' => $sampleResults
                    ]
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Google Search Test Failed', ['response' => $response]);
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Google Custom Search API test failed. Check logs for details.',
                    'full_response' => $response
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google Custom Search API test failed: ' . $e->getMessage()
            ]);
        }
    }
}
