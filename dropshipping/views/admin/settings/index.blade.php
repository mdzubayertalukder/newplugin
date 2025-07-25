<<<<<<< HEAD
@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Settings') }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-gear"></i> {{ translate('Dropshipping Settings') }}</h4>
            <div class="d-flex align-items-center gap-10 flex-wrap">
                <a href="{{ route('admin.dropshipping.dashboard') }}" class="btn btn-outline-primary">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Plugin Configuration') }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.dropshipping.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="default_markup_percentage" class="form-label">{{ translate('Default Markup Percentage') }}</label>
                        <input type="number" class="form-control" id="default_markup_percentage" name="default_markup_percentage" value="{{ $settings['default_markup_percentage'] ?? '20' }}" min="0" max="1000" step="0.01">
                        <small class="form-text text-muted">{{ translate('Default markup percentage for imported products') }}</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_sync_enabled" class="form-label">{{ translate('Auto Sync') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_sync_enabled" name="auto_sync_enabled" value="1" {{ ($settings['auto_sync_enabled'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_sync_enabled">
                                {{ translate('Enable automatic product synchronization') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sync_frequency" class="form-label">{{ translate('Sync Frequency (hours)') }}</label>
                        <select class="form-control" id="sync_frequency" name="sync_frequency">
                            <option value="1" {{ ($settings['sync_frequency'] ?? '24') == '1' ? 'selected' : '' }}>{{ translate('Every Hour') }}</option>
                            <option value="6" {{ ($settings['sync_frequency'] ?? '24') == '6' ? 'selected' : '' }}>{{ translate('Every 6 Hours') }}</option>
                            <option value="12" {{ ($settings['sync_frequency'] ?? '24') == '12' ? 'selected' : '' }}>{{ translate('Every 12 Hours') }}</option>
                            <option value="24" {{ ($settings['sync_frequency'] ?? '24') == '24' ? 'selected' : '' }}>{{ translate('Daily') }}</option>
                            <option value="168" {{ ($settings['sync_frequency'] ?? '24') == '168' ? 'selected' : '' }}>{{ translate('Weekly') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_products_per_sync" class="form-label">{{ translate('Max Products Per Sync') }}</label>
                        <input type="number" class="form-control" id="max_products_per_sync" name="max_products_per_sync" value="{{ $settings['max_products_per_sync'] ?? '100' }}" min="1" max="1000">
                        <small class="form-text text-muted">{{ translate('Maximum number of products to sync in one batch') }}</small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_logging" name="enable_logging" value="1" {{ ($settings['enable_logging'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_logging">
                                {{ translate('Enable detailed logging') }}
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">{{ translate('Serper.dev Integration') }}</h5>
                    <p class="text-muted small mb-4">{{ translate('Configure Serper.dev API for product research, price comparison, and SEO analysis') }}</p>

                    <div class="form-group">
                        <label for="serper_api_key" class="form-label">{{ translate('Serper.dev API Key') }}</label>
                        <input type="password" class="form-control" id="serper_api_key" name="serper_api_key" value="{{ $settings['serper_api_key'] ?? '' }}" placeholder="Enter your Serper.dev API key">
                        <small class="form-text text-muted">
                            {{ translate('Get your API key from') }} <a href="https://serper.dev" target="_blank">serper.dev</a>. 
                            {{ translate('Required for product research and competitor analysis features.') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_auto_research" name="enable_auto_research" value="1" {{ ($settings['enable_auto_research'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_auto_research">
                                {{ translate('Enable automatic product research on view details') }}
                            </label>
                        </div>
                        <small class="form-text text-muted">{{ translate('Automatically search for product information when users click "View Details"') }}</small>
                    </div>

                    <div class="form-group">
                        <label for="research_results_limit" class="form-label">{{ translate('Research Results Limit') }}</label>
                        <select class="form-control" id="research_results_limit" name="research_results_limit">
                            <option value="5" {{ ($settings['research_results_limit'] ?? '10') == '5' ? 'selected' : '' }}>5 {{ translate('results') }}</option>
                            <option value="10" {{ ($settings['research_results_limit'] ?? '10') == '10' ? 'selected' : '' }}>10 {{ translate('results') }}</option>
                            <option value="15" {{ ($settings['research_results_limit'] ?? '10') == '15' ? 'selected' : '' }}>15 {{ translate('results') }}</option>
                            <option value="20" {{ ($settings['research_results_limit'] ?? '10') == '20' ? 'selected' : '' }}>20 {{ translate('results') }}</option>
                        </select>
                        <small class="form-text text-muted">{{ translate('Maximum number of research results to fetch per product') }}</small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_price_tracking" name="enable_price_tracking" value="1" {{ ($settings['enable_price_tracking'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_price_tracking">
                                {{ translate('Enable price comparison and tracking') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_seo_analysis" name="enable_seo_analysis" value="1" {{ ($settings['enable_seo_analysis'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_seo_analysis">
                                {{ translate('Enable SEO analysis and title optimization') }}
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">{{ translate('AI Research Integration') }}</h5>
                    <p class="text-muted small mb-4">{{ translate('Configure Google AI Studio or ChatGPT API for product research, market analysis, and insights.') }}</p>

                    <div class="form-group">
                        <a href="{{ route('admin.dropshipping.ai.settings') }}" class="btn btn-primary">{{ translate('Configure AI Settings') }}</a>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="icofont-save"></i> {{ translate('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function testGoogleAIStudioApi() {
    const apiKey = document.getElementById('google_ai_studio_api_key').value;
    const endpoint = document.getElementById('google_ai_studio_api_endpoint').value;
    const resultDiv = document.getElementById('googleAITestResult');
    resultDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing API...</span>';

    fetch('/admin/dropshipping/settings/test-google-ai', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            api_key: apiKey,
            endpoint: endpoint
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<span class="text-success"><i class="icofont-check"></i> ' + data.message + '</span>';
        } else {
            resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> ' + (data.message || 'Test failed') + '</span>';
        }
    })
    .catch(err => {
        resultDiv.innerHTML = '<span class="text-danger"><i class="icofont-close"></i> ' + (err.message || 'Test failed') + '</span>';
    });
}
</script>
@endpush
=======
@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Settings') }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-gear"></i> {{ translate('Dropshipping Settings') }}</h4>
            <div class="d-flex align-items-center gap-10 flex-wrap">
                <a href="{{ route('admin.dropshipping.dashboard') }}" class="btn btn-outline-primary">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Plugin Configuration') }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.dropshipping.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="default_markup_percentage" class="form-label">{{ translate('Default Markup Percentage') }}</label>
                        <input type="number" class="form-control" id="default_markup_percentage" name="default_markup_percentage" value="{{ $settings['default_markup_percentage'] ?? '20' }}" min="0" max="1000" step="0.01">
                        <small class="form-text text-muted">{{ translate('Default markup percentage for imported products') }}</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_sync_enabled" class="form-label">{{ translate('Auto Sync') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_sync_enabled" name="auto_sync_enabled" value="1" {{ ($settings['auto_sync_enabled'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_sync_enabled">
                                {{ translate('Enable automatic product synchronization') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sync_frequency" class="form-label">{{ translate('Sync Frequency (hours)') }}</label>
                        <select class="form-control" id="sync_frequency" name="sync_frequency">
                            <option value="1" {{ ($settings['sync_frequency'] ?? '24') == '1' ? 'selected' : '' }}>{{ translate('Every Hour') }}</option>
                            <option value="6" {{ ($settings['sync_frequency'] ?? '24') == '6' ? 'selected' : '' }}>{{ translate('Every 6 Hours') }}</option>
                            <option value="12" {{ ($settings['sync_frequency'] ?? '24') == '12' ? 'selected' : '' }}>{{ translate('Every 12 Hours') }}</option>
                            <option value="24" {{ ($settings['sync_frequency'] ?? '24') == '24' ? 'selected' : '' }}>{{ translate('Daily') }}</option>
                            <option value="168" {{ ($settings['sync_frequency'] ?? '24') == '168' ? 'selected' : '' }}>{{ translate('Weekly') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_products_per_sync" class="form-label">{{ translate('Max Products Per Sync') }}</label>
                        <input type="number" class="form-control" id="max_products_per_sync" name="max_products_per_sync" value="{{ $settings['max_products_per_sync'] ?? '100' }}" min="1" max="1000">
                        <small class="form-text text-muted">{{ translate('Maximum number of products to sync in one batch') }}</small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_logging" name="enable_logging" value="1" {{ ($settings['enable_logging'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_logging">
                                {{ translate('Enable detailed logging') }}
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">{{ translate('Serper.dev Integration') }}</h5>
                    <p class="text-muted small mb-4">{{ translate('Configure Serper.dev API for product research, price comparison, and SEO analysis') }}</p>

                    <div class="form-group">
                        <label for="serper_api_key" class="form-label">{{ translate('Serper.dev API Key') }}</label>
                        <input type="password" class="form-control" id="serper_api_key" name="serper_api_key" value="{{ $settings['serper_api_key'] ?? '' }}" placeholder="Enter your Serper.dev API key">
                        <small class="form-text text-muted">
                            {{ translate('Get your API key from') }} <a href="https://serper.dev" target="_blank">serper.dev</a>. 
                            {{ translate('Required for product research and competitor analysis features.') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_auto_research" name="enable_auto_research" value="1" {{ ($settings['enable_auto_research'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_auto_research">
                                {{ translate('Enable automatic product research on view details') }}
                            </label>
                        </div>
                        <small class="form-text text-muted">{{ translate('Automatically search for product information when users click "View Details"') }}</small>
                    </div>

                    <div class="form-group">
                        <label for="research_results_limit" class="form-label">{{ translate('Research Results Limit') }}</label>
                        <select class="form-control" id="research_results_limit" name="research_results_limit">
                            <option value="5" {{ ($settings['research_results_limit'] ?? '10') == '5' ? 'selected' : '' }}>5 {{ translate('results') }}</option>
                            <option value="10" {{ ($settings['research_results_limit'] ?? '10') == '10' ? 'selected' : '' }}>10 {{ translate('results') }}</option>
                            <option value="15" {{ ($settings['research_results_limit'] ?? '10') == '15' ? 'selected' : '' }}>15 {{ translate('results') }}</option>
                            <option value="20" {{ ($settings['research_results_limit'] ?? '10') == '20' ? 'selected' : '' }}>20 {{ translate('results') }}</option>
                        </select>
                        <small class="form-text text-muted">{{ translate('Maximum number of research results to fetch per product') }}</small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_price_tracking" name="enable_price_tracking" value="1" {{ ($settings['enable_price_tracking'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_price_tracking">
                                {{ translate('Enable price comparison and tracking') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_seo_analysis" name="enable_seo_analysis" value="1" {{ ($settings['enable_seo_analysis'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_seo_analysis">
                                {{ translate('Enable SEO analysis and title optimization') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="icofont-save"></i> {{ translate('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
