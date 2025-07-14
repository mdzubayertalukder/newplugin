@extends('core::base.layouts.master')

@section('title')
{{ translate('Withdrawal Details') }} #{{ $withdrawal->id }}
@endsection

@section('custom_css')
<style>
    .detail-card {
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
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

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .info-label {
        font-weight: 600;
        color: #5a5c69;
    }

    .info-value {
        color: #3a3b45;
    }
</style>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4>{{ translate('Withdrawal Request Details') }}</h4>
                    <p class="text-muted mb-0">{{ translate('Request ID') }}: #{{ $withdrawal->id }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.dropshipping.withdrawals.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{ translate('Back to List') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Basic Information -->
    <div class="col-lg-6">
        <div class="detail-card">
            <h5>{{ translate('Basic Information') }}</h5>
            <div class="info-row">
                <span class="info-label">{{ translate('Status') }}:</span>
                <span class="status-badge status-{{ $withdrawal->status }}">
                    {{ ucfirst($withdrawal->status) }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ translate('Amount') }}:</span>
                <span class="info-value">{{ currencyExchange($withdrawal->amount) }}</span>
            </div>
            @if($withdrawal->admin_fee > 0)
            <div class="info-row">
                <span class="info-label">{{ translate('Admin Fee') }}:</span>
                <span class="info-value">{{ currencyExchange($withdrawal->admin_fee) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ translate('Net Amount') }}:</span>
                <span class="info-value">{{ currencyExchange($withdrawal->amount - $withdrawal->admin_fee) }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">{{ translate('Payment Method') }}:</span>
                <span class="info-value">{{ ucfirst($withdrawal->payment_method) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ translate('Requested Date') }}:</span>
                <span class="info-value">{{ $withdrawal->created_at->format('M d, Y h:i A') }}</span>
            </div>
            @if($withdrawal->processed_at)
            <div class="info-row">
                <span class="info-label">{{ translate('Processed Date') }}:</span>
                <span class="info-value">{{ $withdrawal->processed_at->format('M d, Y h:i A') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Tenant Information -->
    <div class="col-lg-6">
        <div class="detail-card">
            <h5>{{ translate('Tenant Information') }}</h5>
            <div class="info-row">
                <span class="info-label">{{ translate('Tenant ID') }}:</span>
                <span class="info-value">{{ $withdrawal->tenant_id }}</span>
            </div>
            @if(isset($withdrawal->requested_user))
            <div class="info-row">
                <span class="info-label">{{ translate('Requested By') }}:</span>
                <span class="info-value">{{ $withdrawal->requested_user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ translate('Email') }}:</span>
                <span class="info-value">{{ $withdrawal->requested_user->email }}</span>
            </div>
            @elseif($withdrawal->requested_by)
            <div class="info-row">
                <span class="info-label">{{ translate('Requested By') }}:</span>
                <span class="info-value">{{ translate('User ID') }}: {{ $withdrawal->requested_by }}</span>
            </div>
            @endif
            @if(isset($withdrawal->processed_user))
            <div class="info-row">
                <span class="info-label">{{ translate('Processed By') }}:</span>
                <span class="info-value">{{ $withdrawal->processed_user->name }}</span>
            </div>
            @elseif($withdrawal->processed_by)
            <div class="info-row">
                <span class="info-label">{{ translate('Processed By') }}:</span>
                <span class="info-value">{{ translate('User ID') }}: {{ $withdrawal->processed_by }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Payment Details -->
@if($withdrawal->payment_details_string || $withdrawal->payment_details)
<div class="row">
    <div class="col-12">
        <div class="detail-card">
            <h5>{{ translate('Payment Details') }}</h5>
            <div class="bg-light p-3 rounded">
                @if($withdrawal->payment_details_string)
                <pre style="margin: 0; white-space: pre-wrap;">{{ $withdrawal->payment_details_string }}</pre>
                @elseif(is_array($withdrawal->payment_details))
                @foreach($withdrawal->payment_details as $key => $value)
                @if($value)
                <div><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</div>
                @endif
                @endforeach
                @else
                <pre style="margin: 0; white-space: pre-wrap;">{{ $withdrawal->payment_details }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Admin Notes -->
@if($withdrawal->admin_notes)
<div class="row">
    <div class="col-12">
        <div class="detail-card">
            <h5>{{ translate('Admin Notes') }}</h5>
            <div class="bg-light p-3 rounded">
                {{ $withdrawal->admin_notes }}
            </div>
        </div>
    </div>
</div>
@endif

<!-- Actions -->
@if($withdrawal->status == 'pending')
<div class="row">
    <div class="col-12">
        <div class="detail-card">
            <h5>{{ translate('Actions') }}</h5>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.dropshipping.withdrawals.approve', $withdrawal->id) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('{{ translate('Are you sure you want to approve this withdrawal?') }}')">
                        <i class="fas fa-check"></i> {{ translate('Approve') }}
                    </button>
                </form>

                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times"></i> {{ translate('Reject') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.dropshipping.withdrawals.reject', $withdrawal->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Reject Withdrawal') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('Rejection Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="{{ translate('Please provide a reason for rejection...') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('Reject Withdrawal') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@elseif($withdrawal->status == 'approved')
<div class="row">
    <div class="col-12">
        <div class="detail-card">
            <h5>{{ translate('Actions') }}</h5>
            <form method="POST" action="{{ route('admin.dropshipping.withdrawals.process', $withdrawal->id) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-info" onclick="return confirm('{{ translate('Mark this withdrawal as processed?') }}')">
                    <i class="fas fa-check-double"></i> {{ translate('Mark as Processed') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Balance Information -->
@if($withdrawal->tenantBalance)
<div class="row">
    <div class="col-12">
        <div class="detail-card">
            <h5>{{ translate('Tenant Balance Information') }}</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h6>{{ translate('Total Earnings') }}</h6>
                        <h4 class="text-success">{{ currencyExchange($withdrawal->tenantBalance->total_earnings) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6>{{ translate('Available Balance') }}</h6>
                        <h4 class="text-primary">{{ currencyExchange($withdrawal->tenantBalance->available_balance) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6>{{ translate('Pending Balance') }}</h6>
                        <h4 class="text-warning">{{ currencyExchange($withdrawal->tenantBalance->pending_balance) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6>{{ translate('Total Withdrawn') }}</h6>
                        <h4 class="text-info">{{ currencyExchange($withdrawal->tenantBalance->total_withdrawn) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection