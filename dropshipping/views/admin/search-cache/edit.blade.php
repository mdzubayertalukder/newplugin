@extends('core::base.layouts.master')

@section('title', 'Edit Search Cache Entry')

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Edit Search Cache Entry</h3>
                    <div>
                        <a href="{{ route('admin.dropshipping.search-cache.show', $cacheEntry->id) }}" 
                           class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="{{ route('admin.dropshipping.search-cache.index') }}" 
                           class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.dropshipping.search-cache.update', $cacheEntry->id) }}" 
                          method="POST" id="editCacheForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <!-- Basic Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="search_query">Search Query <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control @error('search_query') is-invalid @enderror" 
                                                   id="search_query" 
                                                   name="search_query" 
                                                   value="{{ old('search_query', $cacheEntry->search_query) }}" 
                                                   required>
                                            @error('search_query')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">The search query that users will search for</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="total_websites">Total Websites</label>
                                            <input type="number" 
                                                   class="form-control @error('total_websites') is-invalid @enderror" 
                                                   id="total_websites" 
                                                   name="total_websites" 
                                                   value="{{ old('total_websites', $cacheEntry->total_websites) }}" 
                                                   min="0">
                                            @error('total_websites')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Number of websites in the search results</small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input" 
                                                       id="is_active" 
                                                       name="is_active" 
                                                       value="1" 
                                                       {{ old('is_active', $cacheEntry->is_active) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="is_active">
                                                    Active Status
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">Enable or disable this cache entry</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Read-only Information -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">System Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Search Hash</label>
                                            <input type="text" class="form-control" value="{{ $cacheEntry->search_hash }}" readonly>
                                            <small class="form-text text-muted">MD5 hash used for cache lookup (read-only)</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Usage Count</label>
                                            <input type="text" class="form-control" value="{{ $cacheEntry->usage_count ?? 0 }}" readonly>
                                            <small class="form-text text-muted">Number of times this cache has been used</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Last Used</label>
                                            <input type="text" class="form-control" 
                                                   value="{{ $cacheEntry->last_used_at ? \Carbon\Carbon::parse($cacheEntry->last_used_at)->format('M d, Y H:i:s') : 'Never used' }}" 
                                                   readonly>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label>Created</label>
                                            <input type="text" class="form-control" 
                                                   value="{{ \Carbon\Carbon::parse($cacheEntry->created_at)->format('M d, Y H:i:s') }}" 
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <!-- Search Summary -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Search Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="search_summary">Summary (JSON Format)</label>
                                            <textarea class="form-control @error('search_summary') is-invalid @enderror" 
                                                      id="search_summary" 
                                                      name="search_summary" 
                                                      rows="8" 
                                                      placeholder='{"key": "value", "description": "Brief summary of search results"}'>{{ old('search_summary', $cacheEntry->search_summary) }}</textarea>
                                            @error('search_summary')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Optional JSON summary of search results. Leave empty if not needed.
                                            </small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-info" onclick="formatJson('search_summary')">
                                            <i class="fas fa-code"></i> Format JSON
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="validateJson('search_summary')">
                                            <i class="fas fa-check"></i> Validate JSON
                                        </button>
                                    </div>
                                </div>

                                <!-- Search Results -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Search Results <span class="text-danger">*</span></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="search_results">Results (JSON Format)</label>
                                            <textarea class="form-control @error('search_results') is-invalid @enderror" 
                                                      id="search_results" 
                                                      name="search_results" 
                                                      rows="15" 
                                                      required 
                                                      placeholder='{"websites": [{"title": "Website Title", "link": "https://example.com", "description": "Description", "domain": "example.com"}]}'>{{ old('search_results', $cacheEntry->search_results) }}</textarea>
                                            @error('search_results')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                JSON format containing search results with websites array
                                            </small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-info" onclick="formatJson('search_results')">
                                            <i class="fas fa-code"></i> Format JSON
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="validateJson('search_results')">
                                            <i class="fas fa-check"></i> Validate JSON
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Update Cache Entry
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" onclick="resetForm()">
                                            <i class="fas fa-undo"></i> Reset Changes
                                        </button>
                                        <a href="{{ route('admin.dropshipping.search-cache.show', $cacheEntry->id) }}" 
                                           class="btn btn-info ml-2">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <a href="{{ route('admin.dropshipping.search-cache.index') }}" 
                                           class="btn btn-secondary ml-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatJson(textareaId) {
    const textarea = document.getElementById(textareaId);
    try {
        const parsed = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(parsed, null, 2);
        showAlert('success', 'JSON formatted successfully!');
    } catch (e) {
        showAlert('danger', 'Invalid JSON format: ' + e.message);
    }
}

function validateJson(textareaId) {
    const textarea = document.getElementById(textareaId);
    try {
        JSON.parse(textarea.value);
        showAlert('success', 'JSON is valid!');
    } catch (e) {
        showAlert('danger', 'Invalid JSON format: ' + e.message);
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will restore the original values.')) {
        document.getElementById('editCacheForm').reset();
        // Restore original values
        document.getElementById('search_query').value = '{{ $cacheEntry->search_query }}';
        document.getElementById('total_websites').value = '{{ $cacheEntry->total_websites }}';
        document.getElementById('is_active').checked = {{ $cacheEntry->is_active ? 'true' : 'false' }};
        document.getElementById('search_summary').value = `{{ addslashes($cacheEntry->search_summary) }}`;
        document.getElementById('search_results').value = `{{ addslashes($cacheEntry->search_results) }}`;
        showAlert('info', 'Form has been reset to original values.');
    }
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.temp-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show temp-alert`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    // Insert at the top of the card body
    const cardBody = document.querySelector('.card-body');
    cardBody.insertBefore(alertDiv, cardBody.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Form validation before submit
document.getElementById('editCacheForm').addEventListener('submit', function(e) {
    const searchResults = document.getElementById('search_results').value;
    const searchSummary = document.getElementById('search_summary').value;
    
    // Validate search results JSON
    try {
        const resultsData = JSON.parse(searchResults);
        if (!resultsData.websites || !Array.isArray(resultsData.websites)) {
            e.preventDefault();
            showAlert('danger', 'Search results must contain a "websites" array.');
            return false;
        }
    } catch (error) {
        e.preventDefault();
        showAlert('danger', 'Invalid JSON format in search results: ' + error.message);
        return false;
    }
    
    // Validate search summary JSON if provided
    if (searchSummary.trim()) {
        try {
            JSON.parse(searchSummary);
        } catch (error) {
            e.preventDefault();
            showAlert('danger', 'Invalid JSON format in search summary: ' + error.message);
            return false;
        }
    }
    
    return true;
});

// Auto-update total websites count based on search results
document.getElementById('search_results').addEventListener('blur', function() {
    try {
        const resultsData = JSON.parse(this.value);
        if (resultsData.websites && Array.isArray(resultsData.websites)) {
            document.getElementById('total_websites').value = resultsData.websites.length;
        }
    } catch (e) {
        // Ignore JSON parsing errors during typing
    }
});
</script>
@endsection