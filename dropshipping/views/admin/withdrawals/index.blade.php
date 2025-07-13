@extends('core::base.layouts.master')

@section('title')
{{ translate('Withdrawal Management') }}
@endsection

@section('custom_css')
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .stats-card h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: bold;
    }

    .stats-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending {
        background-color: #ffc107;
        color: #000;
    }

    .status-approved {
        background-color: #28a745;
        color: #fff;
    }

    .status-rejected {
        background-color: #dc3545;
        color: #fff;
    }

    .status-processed {
        background-color: #17a2b8;
        color: #fff;
    }
</style>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>{{ translate('Dropshipping Withdrawal Management') }}</h4>
                <p class="text-muted">{{ translate('Manage withdrawal requests from tenants') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <h3>{{ $stats['total_requests'] }}</h3>
            <p>{{ translate('Total Requests') }}</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <h3>{{ $stats['pending_requests'] }}</h3>
            <p>{{ translate('Pending Requests') }}</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <h3>{{ currencyExchange($stats['total_amount']) }}</h3>
            <p>{{ translate('Total Processed Amount') }}</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <h3>{{ currencyExchange($stats['pending_amount']) }}</h3>
            <p>{{ translate('Pending Amount') }}</p>
        </div>
    </div>
</div>

<!-- Withdrawal Requests Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>{{ translate('Withdrawal Requests') }}</h5>
                <div class="d-flex gap-2">
                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('admin.dropshipping.withdrawals.index') }}" class="d-flex gap-2">
                        <select name="status" class="form-control form-control-sm" style="width: auto;">
                            <option value="">{{ translate('All Status') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ translate('Approved') }}</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ translate('Rejected') }}</option>
                            <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>{{ translate('Processed') }}</option>
                        </select>
                        @if($tenants->count() > 0)
                        <select name="tenant_id" class="form-control form-control-sm" style="width: auto;">
                            <option value="">{{ translate('All Tenants') }}</option>
                            @foreach($tenants as $tenant_id)
                            <option value="{{ $tenant_id }}" {{ request('tenant_id') == $tenant_id ? 'selected' : '' }}>
                                {{ translate('Tenant') }} {{ $tenant_id }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Filter') }}</button>
                        <a href="{{ route('admin.dropshipping.withdrawals.index') }}" class="btn btn-sm btn-secondary">{{ translate('Reset') }}</a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if($withdrawals->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th>{{ translate('Tenant') }}</th>
                                <th>{{ translate('Amount') }}</th>
                                <th>{{ translate('Method') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Requested') }}</th>
                                <th>{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $withdrawal)
                            <tr>
                                <td>#{{ $withdrawal->id }}</td>
                                <td>
                                    <strong>{{ translate('Tenant') }} {{ $withdrawal->tenant_id }}</strong>
                                    @if($withdrawal->requestedBy)
                                    <br><small class="text-muted">{{ $withdrawal->requestedBy->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ currencyExchange($withdrawal->amount) }}</strong>
                                    @if($withdrawal->admin_fee > 0)
                                    <br><small class="text-muted">{{ translate('Fee') }}: {{ currencyExchange($withdrawal->admin_fee) }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ ucfirst($withdrawal->payment_method) }}
                                    @if($withdrawal->payment_details)
                                    <br><small class="text-muted">{{ Str::limit($withdrawal->payment_details, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $withdrawal->status }}">
                                        {{ ucfirst($withdrawal->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $withdrawal->created_at->format('M d, Y') }}
                                    <br><small class="text-muted">{{ $withdrawal->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                            {{ translate('Actions') }}
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.dropshipping.withdrawals.show', $withdrawal->id) }}">
                                                <i class="fas fa-eye"></i> {{ translate('View Details') }}
                                            </a>
                                            @if($withdrawal->status == 'pending')
                                            <form method="POST" action="{{ route('admin.dropshipping.withdrawals.approve', $withdrawal->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-success" onclick="return confirm('{{ translate('Are you sure you want to approve this withdrawal?') }}')">
                                                    <i class="fas fa-check"></i> {{ translate('Approve') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.dropshipping.withdrawals.reject', $withdrawal->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('{{ translate('Are you sure you want to reject this withdrawal?') }}')">
                                                    <i class="fas fa-times"></i> {{ translate('Reject') }}
                                                </button>
                                            </form>
                                            @elseif($withdrawal->status == 'approved')
                                            <form method="POST" action="{{ route('admin.dropshipping.withdrawals.process', $withdrawal->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-info" onclick="return confirm('{{ translate('Mark this withdrawal as processed?') }}')">
                                                    <i class="fas fa-check-double"></i> {{ translate('Mark Processed') }}
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $withdrawals->links() }}
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                    <h5>{{ translate('No Withdrawal Requests Found') }}</h5>
                    <p class="text-muted">{{ translate('There are no withdrawal requests at the moment.') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions (if needed) -->
@if($withdrawals->where('status', 'pending')->count() > 0)
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6>{{ translate('Bulk Actions') }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dropshipping.withdrawals.bulk.action') }}" id="bulkActionForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label>{{ translate('Select Action') }}</label>
                            <select name="action" class="form-control" required>
                                <option value="">{{ translate('Choose Action') }}</option>
                                <option value="approve">{{ translate('Approve Selected') }}</option>
                                <option value="reject">{{ translate('Reject Selected') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>{{ translate('Withdrawal IDs (comma separated)') }}</label>
                            <input type="text" name="withdrawal_ids" class="form-control" placeholder="1,2,3,4..." required>
                            <small class="text-muted">{{ translate('Enter the withdrawal request IDs you want to process') }}</small>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('{{ translate('Are you sure you want to perform this bulk action?') }}')">
                                {{ translate('Execute') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('custom_scripts')
<script>
    // Auto-refresh page every 30 seconds for pending requests
    @if(request('status') == 'pending' || !request('status'))
    setTimeout(function() {
        if (document.hidden === false) {
            window.location.reload();
        }
    }, 30000);
    @endif
</script>
@endsection