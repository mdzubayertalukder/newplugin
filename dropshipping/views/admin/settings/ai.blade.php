@extends('core::base.layouts.master')

@section('title', 'AI Settings')

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">AI Settings</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.dropshipping.ai.settings.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="ai_service">AI Service</label>
                            <select name="ai_service" id="ai_service" class="form-control">
                                <option value="google" {{ ($settings['ai_service'] ?? '') == 'google' ? 'selected' : '' }}>Google AI Studio</option>
                                <option value="openai" {{ ($settings['ai_service'] ?? '') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                            </select>
                        </div>

                        <div id="google-settings" class="{{ ($settings['ai_service'] ?? '') == 'openai' ? 'd-none' : '' }}">
                            <div class="form-group">
                                <label for="google_ai_studio_api_key">Google AI Studio API Key</label>
                                <input type="text" name="google_ai_studio_api_key" id="google_ai_studio_api_key" class="form-control" value="{{ $settings['google_ai_studio_api_key'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label for="google_ai_studio_api_endpoint">Google AI Studio API Endpoint</label>
                                <input type="text" name="google_ai_studio_api_endpoint" id="google_ai_studio_api_endpoint" class="form-control" value="{{ $settings['google_ai_studio_api_endpoint'] ?? '' }}">
                            </div>
                        </div>

                        <!-- Google Search API Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Google Search API Settings</h5>
                                <small class="text-muted">Configure Google Custom Search API for real market data</small>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="google_search_api_key">Google Search API Key</label>
                                    <input type="text" name="google_search_api_key" id="google_search_api_key" class="form-control" value="{{ $settings['google_search_api_key'] ?? '' }}" placeholder="AIzaSyBu0x-r_XlDBVWSjkxAIkePUpLT4hJWhc4">
                                    <small class="form-text text-muted">Get your API key from <a href="https://developers.google.com/custom-search/v1/overview" target="_blank">Google Custom Search API</a></small>
                                </div>
                                <div class="form-group">
                                    <label for="google_search_engine_id">Search Engine ID</label>
                                    <input type="text" name="google_search_engine_id" id="google_search_engine_id" class="form-control" value="{{ $settings['google_search_engine_id'] ?? '' }}" placeholder="Enter your Custom Search Engine ID">
                                    <small class="form-text text-muted">Create a custom search engine at <a href="https://cse.google.com/cse/" target="_blank">Google Custom Search</a></small>
                                </div>
                                <button type="button" class="btn btn-info btn-sm mt-2" id="testGoogleSearchButton" onclick="testGoogleSearchApi()">Test Google Search API</button>
                                <div id="googleSearchTestResult" class="mt-2"></div>
                            </div>
                        </div>

                        <div id="openai-settings" class="{{ ($settings['ai_service'] ?? '') == 'google' ? 'd-none' : '' }}">
                            <div class="form-group">
                                <label for="openai_api_key">OpenAI API Key</label>
                                <input type="text" name="openai_api_key" id="openai_api_key" class="form-control" value="{{ $settings['openai_api_key'] ?? '' }}">
                            </div>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="testOpenAIButton" onclick="testOpenAIApi()">Test OpenAI API</button>
                            <div id="openAITestResult" class="mt-2"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('ai_service').addEventListener('change', function () {
        if (this.value === 'google') {
            document.getElementById('google-settings').classList.remove('d-none');
            document.getElementById('openai-settings').classList.add('d-none');
        } else {
            document.getElementById('google-settings').classList.add('d-none');
            document.getElementById('openai-settings').classList.remove('d-none');
        }
    });

    function testOpenAIApi() {
        const apiKey = document.getElementById('openai_api_key').value;
        const resultDiv = document.getElementById('openAITestResult');
        resultDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing API...</span>';

        console.log('Attempting to test OpenAI API...');
        console.log('API Key (first 5 chars):', apiKey.substring(0, 5));

        fetch("{{ route('admin.dropshipping.ai.test.openai') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => {
            console.log('Fetch response received:', response);
            if (!response.ok) {
                // If response is not OK (e.g., 404, 500), try to read error body
                return response.json().then(err => {
                    console.error('Error response body:', err);
                    throw new Error(err.message || 'Network response was not ok.');
                }).catch(() => {
                    // If parsing JSON fails, just throw a generic error
                    throw new Error('Network response was not ok and could not parse error body.');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('OpenAI Test Data:', data);
            if (data.success) {
                resultDiv.innerHTML = '<span class="text-success"><i class="icofont-check"></i> Success! ' + (data.response ? data.response : '') + '</span>';
            } else {
                resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> Failed: ' + (data.message || 'Unknown error') + '</span>';
            }
        })
        .catch(error => {
            console.error('OpenAI Test Error:', error);
            resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> An error occurred: ' + error.message + '</span>';
        });
    }

    function testGoogleSearchApi() {
        const apiKey = document.getElementById('google_search_api_key').value;
        const searchEngineId = document.getElementById('google_search_engine_id').value;
        const resultDiv = document.getElementById('googleSearchTestResult');
        
        if (!apiKey || !searchEngineId) {
            resultDiv.innerHTML = '<span class="text-warning"><i class="icofont-warning"></i> Please enter both API Key and Search Engine ID</span>';
            return;
        }
        
        resultDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing Google Search API...</span>';

        console.log('Attempting to test Google Search API...');
        console.log('API Key (first 10 chars):', apiKey.substring(0, 10));
        console.log('Search Engine ID:', searchEngineId);

        fetch("{{ route('admin.dropshipping.ai.test.google.search') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                api_key: apiKey,
                search_engine_id: searchEngineId
            })
        })
        .then(response => {
            console.log('Google Search API test response received:', response);
            if (!response.ok) {
                return response.json().then(err => {
                    console.error('Error response body:', err);
                    throw new Error(err.message || 'Network response was not ok.');
                }).catch(() => {
                    throw new Error('Network response was not ok and could not parse error body.');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Google Search Test Data:', data);
            if (data.success) {
                resultDiv.innerHTML = '<span class="text-success"><i class="icofont-check"></i> Success! Found ' + (data.total_results || 0) + ' search results</span>';
            } else {
                resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> Failed: ' + (data.message || 'Unknown error') + '</span>';
            }
        })
        .catch(error => {
            console.error('Google Search Test Error:', error);
            resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> An error occurred: ' + error.message + '</span>';
        });
    }
</script>
@endsection
