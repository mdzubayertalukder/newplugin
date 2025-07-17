@extends('core::base.layouts.master')

@section('title', 'Search Cache Details')

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Search Cache Details</h3>
                    <div>
                        <a href="{{ route('admin.dropshipping.search-cache.edit', $cacheEntry->id) }}" 
                           class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" 
                                class="btn btn-{{ $cacheEntry->is_active ? 'secondary' : 'success' }} btn-sm"
                                onclick="toggleStatus({{ $cacheEntry->id }}, {{ $cacheEntry->is_active ? 0 : 1 }})">
                            <i class="fas fa-{{ $cacheEntry->is_active ? 'pause' : 'play' }}"></i>
                            {{ $cacheEntry->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button type="button" 
                                class="btn btn-danger btn-sm"
                                onclick="deleteEntry({{ $cacheEntry->id }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <a href="{{ route('admin.dropshipping.search-cache.index') }}" 
                           class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td>{{ $cacheEntry->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Search Query:</strong></td>
                                            <td><span class="badge badge-primary">{{ $cacheEntry->search_query }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Search Hash:</strong></td>
                                            <td><code>{{ $cacheEntry->search_hash }}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge badge-{{ $cacheEntry->is_active ? 'success' : 'secondary' }}">
                                                    {{ $cacheEntry->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Websites:</strong></td>
                                            <td><span class="badge badge-info">{{ $cacheEntry->total_websites ?? 0 }}</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Usage Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Usage Count:</strong></td>
                                            <td><span class="badge badge-secondary">{{ $cacheEntry->usage_count ?? 0 }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($cacheEntry->created_at)->format('M d, Y H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($cacheEntry->updated_at)->format('M d, Y H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Used:</strong></td>
                                            <td>
                                                @if($cacheEntry->last_used_at)
                                                    {{ \Carbon\Carbon::parse($cacheEntry->last_used_at)->format('M d, Y H:i:s') }}
                                                    <br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($cacheEntry->last_used_at)->diffForHumans() }}</small>
                                                @else
                                                    <span class="text-muted">Never used</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Summary -->
                    @if($cacheEntry->search_summary)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Search Summary</h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $summary = json_decode($cacheEntry->search_summary, true);
                                @endphp
                                @if($summary && is_array($summary))
                                    <div class="row">
                                        @foreach($summary as $key => $value)
                                            <div class="col-md-4 mb-3">
                                                <div class="border rounded p-3">
                                                    <h6 class="text-muted mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</h6>
                                                    <p class="mb-0">{{ is_array($value) ? implode(', ', $value) : $value }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <pre>{{ $cacheEntry->search_summary }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Search Results -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Search Results</h5>
                            <button type="button" class="btn btn-sm btn-info" onclick="toggleRawJson()">
                                <i class="fas fa-code"></i> Toggle Raw JSON
                            </button>
                        </div>
                        <div class="card-body">
                            @php
                                $results = json_decode($cacheEntry->search_results, true);
                            @endphp
                            
                            <!-- Formatted Results -->
                            <div id="formattedResults">
                                @if($results && is_array($results))
                                    @if(isset($results['websites']) && is_array($results['websites']))
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Website</th>
                                                        <th>Title</th>
                                                        <th>Description</th>
                                                        <th>Link</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($results['websites'] as $index => $website)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>
                                                                @if(isset($website['domain']))
                                                                    <strong>{{ $website['domain'] }}</strong>
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($website['title']))
                                                                    {{ Str::limit($website['title'], 50) }}
                                                                @else
                                                                    <span class="text-muted">No title</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($website['description']))
                                                                    {{ Str::limit($website['description'], 80) }}
                                                                @else
                                                                    <span class="text-muted">No description</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($website['link']))
                                                                    <a href="{{ $website['link'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-external-link-alt"></i> Visit
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">No link</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Search results do not contain expected website data structure.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Invalid JSON format in search results.
                                    </div>
                                @endif
                            </div>

                            <!-- Raw JSON -->
                            <div id="rawJson" style="display: none;">
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($results, JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRawJson() {
    const formatted = document.getElementById('formattedResults');
    const raw = document.getElementById('rawJson');
    
    if (raw.style.display === 'none') {
        formatted.style.display = 'none';
        raw.style.display = 'block';
    } else {
        formatted.style.display = 'block';
        raw.style.display = 'none';
    }
}

function toggleStatus(id, newStatus) {
    if (!confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this cache entry?')) {
        return;
    }
    
    fetch("{{ route('admin.dropshipping.search-cache.toggle.status', '') }}/" + id + "/toggle-status", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Toggle Status Error:', error);
        alert('An error occurred while updating status');
    });
}

function deleteEntry(id) {
    if (!confirm('Are you sure you want to delete this cache entry? This action cannot be undone.')) {
        return;
    }
    
    fetch("{{ route('admin.dropshipping.search-cache.destroy', '') }}/" + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "{{ route('admin.dropshipping.search-cache.index') }}";
        } else {
            alert('Failed to delete entry: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Delete Error:', error);
        alert('An error occurred while deleting the entry');
    });
}
</script>
@endsection