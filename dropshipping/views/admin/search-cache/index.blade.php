@extends('core::base.layouts.master')

@section('title', 'Search Cache Management')

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Search Cache Management</h3>
                    <div>
                        <button type="button" class="btn btn-info btn-sm" onclick="loadStatistics()">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="clearAllCache()">
                            <i class="fas fa-trash"></i> Clear All Cache
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search and Filter Form -->
                    <form method="GET" action="{{ route('admin.dropshipping.search-cache.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by query..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="per_page" class="form-control">
                                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10 per page</option>
                                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 per page</option>
                                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 per page</option>
                                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 per page</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div id="statisticsCards" class="row mb-4" style="display: none;">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Entries</h5>
                                    <h3 id="totalEntries">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Active Entries</h5>
                                    <h3 id="activeEntries">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Usage</h5>
                                    <h3 id="totalUsage">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Most Used</h5>
                                    <p id="mostUsedQuery" class="mb-0">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Entries Table -->
                    @if($cacheEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Search Query</th>
                                        <th>Total Websites</th>
                                        <th>Usage Count</th>
                                        <th>Status</th>
                                        <th>Last Used</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cacheEntries as $entry)
                                        <tr>
                                            <td>{{ $entry->id }}</td>
                                            <td>
                                                <strong>{{ $entry->search_query }}</strong>
                                                <br>
                                                <small class="text-muted">Hash: {{ substr($entry->search_hash, 0, 8) }}...</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $entry->total_websites ?? 0 }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $entry->usage_count ?? 0 }}</span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm {{ $entry->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                        onclick="toggleStatus({{ $entry->id }}, {{ $entry->is_active ? 0 : 1 }})">
                                                    {{ $entry->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </td>
                                            <td>
                                                @if($entry->last_used_at)
                                                    {{ \Carbon\Carbon::parse($entry->last_used_at)->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('M d, Y H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.dropshipping.search-cache.show', $entry->id) }}" 
                                                       class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.dropshipping.search-cache.edit', $entry->id) }}" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="deleteEntry({{ $entry->id }})" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $cacheEntries->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No search cache entries found</h5>
                            <p class="text-muted">Cache entries will appear here when users perform product searches.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadStatistics() {
    const statsDiv = document.getElementById('statisticsCards');
    const isVisible = statsDiv.style.display !== 'none';
    
    if (isVisible) {
        statsDiv.style.display = 'none';
        return;
    }
    
    // Show loading state
    statsDiv.style.display = 'block';
    document.getElementById('totalEntries').textContent = '...';
    document.getElementById('activeEntries').textContent = '...';
    document.getElementById('totalUsage').textContent = '...';
    document.getElementById('mostUsedQuery').textContent = '...';
    
    fetch("{{ route('admin.dropshipping.search-cache.stats') }}", {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('totalEntries').textContent = data.data.total_entries;
            document.getElementById('activeEntries').textContent = data.data.active_entries;
            document.getElementById('totalUsage').textContent = data.data.total_usage;
            document.getElementById('mostUsedQuery').textContent = data.data.most_used_query || 'None';
        } else {
            alert('Failed to load statistics: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Statistics Error:', error);
        alert('An error occurred while loading statistics');
    });
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
            location.reload();
        } else {
            alert('Failed to delete entry: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Delete Error:', error);
        alert('An error occurred while deleting the entry');
    });
}

function clearAllCache() {
    if (!confirm('Are you sure you want to clear ALL cache entries? This action cannot be undone.')) {
        return;
    }
    
    fetch("{{ route('admin.dropshipping.search-cache.clear.all') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('All cache entries have been cleared successfully.');
            location.reload();
        } else {
            alert('Failed to clear cache: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Clear All Error:', error);
        alert('An error occurred while clearing cache');
    });
}
</script>
@endsection