@extends('core::base.layouts.master')

@section('title')
{{__('Withdrawals')}}
@endsection

@section('style')
<style>
    .balance-card {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
    }

    .withdrawal-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }

    .withdrawal-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
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
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h4 class="page-title">{{__('Withdrawal Management')}}</h4>
                <div class="page-header-actions">
                    <a href="{{ route('dropshipping.order.management') }}" class="btn btn-secondary">
                        <i class="fas fa-shopping-cart"></i> {{__('Back to Orders')}}
                    </a>
                    @if($balance->available_balance > 0)
                    <a href="{{ route('user.dropshipping.withdrawals.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{__('Request Withdrawal')}}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="balance-card">
                <h5><i class="fas fa-wallet"></i> {{__('Available Balance')}}</h5>
                <h2>${{ number_format($balance->available_balance, 2) }}</h2>
                <small>{{__('Ready for withdrawal')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="balance-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                <h5><i class="fas fa-clock"></i> {{__('Pending Balance')}}</h5>
                <h2>${{ number_format($balance->pending_balance, 2) }}</h2>
                <small>{{__('From pending orders')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="balance-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5><i class="fas fa-chart-line"></i> {{__('Total Withdrawn')}}</h5>
                <h2>${{ number_format($balance->total_withdrawn, 2) }}</h2>
                <small>{{__('All-time withdrawals')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="balance-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h5><i class="fas fa-money-bill-wave"></i> {{__('Total Earnings')}}</h5>
                <h2>${{ number_format($balance->total_earnings, 2) }}</h2>
                <small>{{__('All-time earnings')}}</small>
            </div>
        </div>
    </div>

    <!-- Withdrawal Settings Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{__('Withdrawal Information')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>{{__('Minimum Amount')}}</h6>
                                <h4 class="text-primary">${{ number_format($settings->minimum_withdrawal_amount, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>{{__('Processing Time')}}</h6>
                                <h4 class="text-info">{{ $settings->withdrawal_processing_days }} {{__('days')}}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>{{__('Fee Rate')}}</h6>
                                <h4 class="text-warning">{{ $settings->withdrawal_fee_percentage }}%</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>{{__('Fixed Fee')}}</h6>
                                <h4 class="text-warning">${{ number_format($settings->withdrawal_fee_fixed, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Requests -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{__('Withdrawal Requests')}}</h5>
                    <div class="card-header-actions">
                        @if($balance->available_balance >= $settings->minimum_withdrawal_amount)
                        <a href="{{ route('user.dropshipping.withdrawals.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> {{__('New Request')}}
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($withdrawalRequests->count() > 0)
                    @foreach($withdrawalRequests as $withdrawal)
                    <div class="withdrawal-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <strong>{{ $withdrawal->request_number }}</strong>
                                    <span class="status-badge status-{{ $withdrawal->status }}">
                                        {{ ucfirst($withdrawal->status) }}
                                    </span>
                                </h6>
                                <p class="text-muted mb-2">
                                    {{__('Amount')}}: <strong>${{ number_format($withdrawal->amount, 2) }}</strong>
                                    | {{__('Requested')}}: {{ $withdrawal->requested_at ? $withdrawal->requested_at->format('M d, Y') : '' }}
                                </p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>{{__('Payment Method')}}:</strong> {{ ucfirst(str_replace('_', ' ', $withdrawal->payment_method)) }}<br>
                                            @if($withdrawal->payment_method === 'bank_transfer')
                                            <strong>{{__('Bank')}}:</strong> {{ $withdrawal->payment_details['bank_name'] ?? 'N/A' }}<br>
                                            <strong>{{__('Account')}}:</strong> {{ $withdrawal->payment_details['account_number'] ?? 'N/A' }}<br>
                                            @elseif(in_array($withdrawal->payment_method, ['bkash', 'nogod', 'rocket']))
                                            <strong>{{__('Mobile')}}:</strong> {{ $withdrawal->payment_details['mobile_number'] ?? 'N/A' }}<br>
                                            @elseif($withdrawal->payment_method === 'paypal')
                                            <strong>{{__('PayPal')}}:</strong> {{ $withdrawal->payment_details['paypal_email'] ?? 'N/A' }}<br>
                                            @endif
                                            <strong>{{__('Holder')}}:</strong> {{ $withdrawal->payment_details['account_holder_name'] ?? 'N/A' }}
                                        </small>
                                    </div>
                                    @if($withdrawal->admin_notes)
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>{{__('Admin Notes')}}:</strong><br>
                                            {{ $withdrawal->admin_notes }}
                                        </small>
                                    </div>
                                    @endif
                                    @if($withdrawal->rejection_reason)
                                    <div class="col-md-6">
                                        <small class="text-danger">
                                            <strong>{{__('Rejection Reason')}}:</strong><br>
                                            {{ $withdrawal->rejection_reason }}
                                        </small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <a href="{{ route('user.dropshipping.withdrawals.show', $withdrawal->id) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> {{__('View')}}
                                </a>
                                @if($withdrawal->status === 'pending')
                                <button class="btn btn-outline-danger btn-sm ml-1"
                                    onclick="cancelWithdrawal({{ $withdrawal->id }})">
                                    <i class="fas fa-times"></i> {{__('Cancel')}}
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $withdrawalRequests->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                        <h5>{{__('No withdrawal requests found')}}</h5>
                        <p class="text-muted">{{__('You haven\'t made any withdrawal requests yet.')}}</p>
                        @if($balance->available_balance >= $settings->minimum_withdrawal_amount)
                        <a href="{{ route('user.dropshipping.withdrawals.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> {{__('Request Your First Withdrawal')}}
                        </a>
                        @else
                        <p class="text-muted">
                            {{__('Minimum withdrawal amount is')}} ${{ number_format($settings->minimum_withdrawal_amount, 2) }}
                        </p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function cancelWithdrawal(withdrawalId) {
        if (confirm('{{__("Are you sure you want to cancel this withdrawal request?")}}')) {
            fetch('{{ url("/user/dropshipping/withdrawals") }}/' + withdrawalId + '/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('{{__("Withdrawal request cancelled successfully")}}');
                        location.reload();
                    } else {
                        alert(data.message || '{{__("Failed to cancel withdrawal request")}}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{__("An error occurred while cancelling the withdrawal request")}}');
                });
        }
    }
</script>
@endsection