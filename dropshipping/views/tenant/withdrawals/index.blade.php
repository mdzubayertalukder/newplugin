@extends('core::base.layouts.master')

@section('title')
{{__('Withdrawals')}}
@endsection

@section('main_content')
<div style="background: #f8f9fb; min-height: 100vh; padding: 20px;">
    <div style="max-width: 1400px; margin: 0 auto;">

        <!-- Header Section -->
        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0; color: #2c3e50; font-weight: 700; font-size: 28px;">
                    <i class="fas fa-money-bill-wave" style="color: #3498db; margin-right: 12px;"></i>
                    {{__('Withdrawal Management')}}
                </h2>
                <p style="margin: 8px 0 0 0; color: #7f8c8d; font-size: 16px;">{{__('Manage your earnings and withdrawal requests')}}</p>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 15px;">
                <a href="{{ route('dropshipping.order.management') }}" style="background: #95a5a6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center;">
                    <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i> {{__('Orders')}}
                </a>
                @if($balance->available_balance >= $settings->minimum_withdrawal_amount)
                <a href="{{ route('user.dropshipping.withdrawals.create') }}" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                    <i class="fas fa-plus" style="margin-right: 8px;"></i> {{__('New Withdrawal')}}
                </a>
                @endif
            </div>
        </div>

        <!-- Balance Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <!-- Available Balance -->
            <div style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(39, 174, 96, 0.3); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <i class="fas fa-wallet" style="font-size: 28px; margin-right: 12px;"></i>
                        <h6 style="margin: 0; font-weight: 600; font-size: 16px; opacity: 0.9;">{{__('Available Balance')}}</h6>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">${{ number_format($balance->available_balance, 2) }}</div>
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;">{{__('Ready for withdrawal')}}</p>
                </div>
            </div>

            <!-- Pending Balance -->
            <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(243, 156, 18, 0.3); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <i class="fas fa-clock" style="font-size: 28px; margin-right: 12px;"></i>
                        <h6 style="margin: 0; font-weight: 600; font-size: 16px; opacity: 0.9;">{{__('Pending Balance')}}</h6>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">${{ number_format($balance->pending_balance, 2) }}</div>
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;">{{__('From pending orders')}}</p>
                </div>
            </div>

            <!-- Total Withdrawn -->
            <div style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(155, 89, 182, 0.3); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <i class="fas fa-chart-line" style="font-size: 28px; margin-right: 12px;"></i>
                        <h6 style="margin: 0; font-weight: 600; font-size: 16px; opacity: 0.9;">{{__('Total Withdrawn')}}</h6>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">${{ number_format($balance->total_withdrawn, 2) }}</div>
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;">{{__('All-time withdrawals')}}</p>
                </div>
            </div>

            <!-- Total Earnings -->
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(231, 76, 60, 0.3); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <i class="fas fa-money-bill-wave" style="font-size: 28px; margin-right: 12px;"></i>
                        <h6 style="margin: 0; font-weight: 600; font-size: 16px; opacity: 0.9;">{{__('Total Earnings')}}</h6>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;">${{ number_format($balance->total_earnings, 2) }}</div>
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;">{{__('All-time earnings')}}</p>
                </div>
            </div>
        </div>

        <!-- Withdrawal Settings Info -->
        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <h4 style="margin: 0 0 25px 0; color: #2c3e50; font-weight: 600; display: flex; align-items: center;">
                <i class="fas fa-info-circle" style="color: #3498db; margin-right: 12px;"></i>
                {{__('Withdrawal Information')}}
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #3498db, #2980b9); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-dollar-sign" style="color: white; font-size: 24px;"></i>
                    </div>
                    <h6 style="margin: 0 0 8px 0; color: #7f8c8d; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">{{__('Minimum Amount')}}</h6>
                    <h3 style="margin: 0; color: #2c3e50; font-weight: 700;">${{ number_format($settings->minimum_withdrawal_amount, 2) }}</h3>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #17a2b8, #138496); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-calendar-alt" style="color: white; font-size: 24px;"></i>
                    </div>
                    <h6 style="margin: 0 0 8px 0; color: #7f8c8d; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">{{__('Processing Time')}}</h6>
                    <h3 style="margin: 0; color: #2c3e50; font-weight: 700;">{{ $settings->withdrawal_processing_days }} {{__('days')}}</h3>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #ffc107, #e0a800); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-percentage" style="color: white; font-size: 24px;"></i>
                    </div>
                    <h6 style="margin: 0 0 8px 0; color: #7f8c8d; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">{{__('Fee Rate')}}</h6>
                    <h3 style="margin: 0; color: #2c3e50; font-weight: 700;">{{ $settings->withdrawal_fee_percentage }}%</h3>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #fd7e14, #e35d00); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-coins" style="color: white; font-size: 24px;"></i>
                    </div>
                    <h6 style="margin: 0 0 8px 0; color: #7f8c8d; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">{{__('Fixed Fee')}}</h6>
                    <h3 style="margin: 0; color: #2c3e50; font-weight: 700;">${{ number_format($settings->withdrawal_fee_fixed, 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- Withdrawal Requests -->
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 30px 30px 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <h4 style="margin: 0; color: #2c3e50; font-weight: 600; display: flex; align-items: center;">
                    <i class="fas fa-list-alt" style="color: #3498db; margin-right: 12px;"></i>
                    {{__('Withdrawal Requests')}}
                </h4>
                @if($balance->available_balance >= $settings->minimum_withdrawal_amount)
                <a href="{{ route('user.dropshipping.withdrawals.create') }}" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; display: inline-flex; align-items: center; margin-top: 10px;">
                    <i class="fas fa-plus" style="margin-right: 8px;"></i> {{__('New Request')}}
                </a>
                @endif
            </div>
            <div style="padding: 30px;">
                @if($withdrawalRequests->count() > 0)
                @foreach($withdrawalRequests as $withdrawal)
                @php
                // Status colors
                $borderColor = '#f39c12'; // default pending
                $statusBg = 'background: #fff3cd; color: #856404;'; // default pending

                if($withdrawal->status === 'approved') {
                $borderColor = '#27ae60';
                $statusBg = 'background: #d4edda; color: #155724;';
                } elseif($withdrawal->status === 'rejected') {
                $borderColor = '#e74c3c';
                $statusBg = 'background: #f8d7da; color: #721c24;';
                } elseif($withdrawal->status === 'processed') {
                $borderColor = '#3498db';
                $statusBg = 'background: #d1ecf1; color: #0c5460;';
                }
                @endphp
                <div style="border: 1px solid #eee; border-radius: 12px; padding: 25px; margin-bottom: 20px; transition: all 0.3s; background: #fafbfc; position: relative; overflow: hidden;">
                    <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: {{ $borderColor }};"></div>

                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <div style="display: flex; align-items: center; margin-bottom: 15px; flex-wrap: wrap;">
                                <h5 style="margin: 0 15px 0 0; color: #2c3e50; font-weight: 700; font-size: 18px;">{{ $withdrawal->request_number }}</h5>
                                <span style="padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; {{ $statusBg }}">
                                    {{ ucfirst($withdrawal->status) }}
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <h6 style="margin: 0 0 5px 0; color: #7f8c8d; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">{{__('Amount')}}</h6>
                                    <p style="margin: 0; color: #2c3e50; font-weight: 700; font-size: 20px;">${{ number_format($withdrawal->amount, 2) }}</p>
                                </div>
                                <div>
                                    <h6 style="margin: 0 0 5px 0; color: #7f8c8d; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">{{__('Requested Date')}}</h6>
                                    <p style="margin: 0; color: #2c3e50; font-weight: 600;">{{ $withdrawal->requested_at ? $withdrawal->requested_at->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <h6 style="margin: 0 0 5px 0; color: #7f8c8d; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">{{__('Payment Method')}}</h6>
                                    <p style="margin: 0; color: #2c3e50; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $withdrawal->payment_method)) }}</p>
                                </div>
                            </div>

                            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                                <h6 style="margin: 0 0 15px 0; color: #2c3e50; font-weight: 600;">{{__('Payment Details')}}</h6>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px;">
                                    @if($withdrawal->payment_method === 'bank_transfer')
                                    <div><strong>{{__('Bank')}}:</strong> {{ $withdrawal->payment_details['bank_name'] ?? 'N/A' }}</div>
                                    <div><strong>{{__('Account')}}:</strong> {{ $withdrawal->payment_details['account_number'] ?? 'N/A' }}</div>
                                    @elseif(in_array($withdrawal->payment_method, ['bkash', 'nogod', 'rocket']))
                                    <div><strong>{{__('Mobile')}}:</strong> +88{{ $withdrawal->payment_details['mobile_number'] ?? 'N/A' }}</div>
                                    @elseif($withdrawal->payment_method === 'paypal')
                                    <div><strong>{{__('PayPal')}}:</strong> {{ $withdrawal->payment_details['paypal_email'] ?? 'N/A' }}</div>
                                    @endif
                                    <div><strong>{{__('Holder')}}:</strong> {{ $withdrawal->payment_details['account_holder_name'] ?? 'N/A' }}</div>
                                </div>
                            </div>

                            @if($withdrawal->admin_notes || $withdrawal->rejection_reason)
                            @php
                            $isRejection = !empty($withdrawal->rejection_reason);
                            $notesBg = $isRejection ? '#f8d7da' : '#d4edda';
                            $notesBorder = $isRejection ? '#dc3545' : '#28a745';
                            $notesColor = $isRejection ? '#721c24' : '#155724';
                            $notesTitle = $isRejection ? __('Rejection Reason') : __('Admin Notes');
                            $notesText = $isRejection ? $withdrawal->rejection_reason : $withdrawal->admin_notes;
                            @endphp
                            <div style="margin-top: 15px; padding: 15px; background: {{ $notesBg }}; border-radius: 8px; border-left: 4px solid {{ $notesBorder }};">
                                <h6 style="margin: 0 0 8px 0; color: {{ $notesColor }}; font-weight: 600;">
                                    {{ $notesTitle }}
                                </h6>
                                <p style="margin: 0; color: {{ $notesColor }}; font-size: 14px;">
                                    {{ $notesText }}
                                </p>
                            </div>
                            @endif
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                            <a href="{{ route('user.dropshipping.withdrawals.show', $withdrawal->id) }}" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-eye" style="margin-right: 8px;"></i> {{__('View')}}
                            </a>
                            @if($withdrawal->status === 'pending')
                            <button onclick="cancelWithdrawal({{ $withdrawal->id }})" style="background: #e74c3c; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times" style="margin-right: 8px;"></i> {{__('Cancel')}}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Pagination -->
                <div style="text-align: center; margin-top: 30px;">
                    {{ $withdrawalRequests->links() }}
                </div>
                @else
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #e0e6ed 0%, #d5dde5 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                        <i class="fas fa-money-bill-wave" style="font-size: 48px; color: #95a5a6;"></i>
                    </div>
                    <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-weight: 600;">{{__('No withdrawal requests found')}}</h4>
                    <p style="margin: 0 0 25px 0; color: #7f8c8d; font-size: 16px;">{{__('You haven\'t made any withdrawal requests yet.')}}</p>
                    @if($balance->available_balance >= $settings->minimum_withdrawal_amount)
                    <a href="{{ route('user.dropshipping.withdrawals.create') }}" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 16px; transition: all 0.3s; display: inline-flex; align-items: center; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                        <i class="fas fa-plus" style="margin-right: 10px;"></i> {{__('Request Your First Withdrawal')}}
                    </a>
                    @else
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px auto; max-width: 400px;">
                        <p style="margin: 0; color: #856404; font-weight: 600;">
                            {{__('Minimum withdrawal amount is')}} ${{ number_format($settings->minimum_withdrawal_amount, 2) }}
                        </p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Hover effects */
    a[style*="background: linear-gradient"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
    }

    button[style*="background: #e74c3c"]:hover {
        background: #c0392b !important;
        transform: translateY(-1px);
    }

    a[style*="background: #3498db"]:hover {
        background: #2980b9 !important;
        transform: translateY(-1px);
    }

    a[style*="background: #95a5a6"]:hover {
        background: #7f8c8d !important;
        transform: translateY(-1px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        div[style*="display: flex; justify-content: space-between"] {
            flex-direction: column !important;
            gap: 15px;
        }

        div[style*="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))"] {
            grid-template-columns: 1fr !important;
        }

        div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
            grid-template-columns: 1fr 1fr !important;
        }

        div[style*="flex: 1; min-width: 300px"] {
            min-width: 100% !important;
        }
    }

    @media (max-width: 480px) {
        div[style*="padding: 30px"] {
            padding: 20px !important;
        }

        div[style*="padding: 25px"] {
            padding: 20px !important;
        }

        h2[style*="font-size: 28px"] {
            font-size: 24px !important;
        }

        div[style*="font-size: 2.5rem"] {
            font-size: 2rem !important;
        }
    }
</style>
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