@extends('core::base.layouts.master')

@section('title')
{{__('Request Withdrawal')}}
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

    .fee-calculation {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .form-group label {
        font-weight: bold;
        color: #495057;
    }

    .withdrawal-form {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h4 class="page-title">{{__('Request Withdrawal')}}</h4>
                <div class="page-header-actions">
                    <a href="{{ route('user.dropshipping.withdrawals.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{__('Back to Withdrawals')}}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Balance -->
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="balance-card">
                <h5><i class="fas fa-wallet"></i> {{__('Available Balance')}}</h5>
                <h2>${{ number_format($balance->available_balance, 2) }}</h2>
                <small>{{__('Ready for withdrawal')}}</small>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="balance-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                <h5><i class="fas fa-cut"></i> {{__('Minimum Amount')}}</h5>
                <h2>${{ number_format($settings->minimum_withdrawal_amount, 2) }}</h2>
                <small>{{__('Required minimum')}}</small>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="balance-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5><i class="fas fa-clock"></i> {{__('Processing Time')}}</h5>
                <h2>{{ $settings->withdrawal_processing_days }}</h2>
                <small>{{__('Business days')}}</small>
            </div>
        </div>
    </div>

    <!-- Withdrawal Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="withdrawal-form">
                <h5 class="mb-4">{{__('Withdrawal Request Form')}}</h5>

                <form action="{{ route('user.dropshipping.withdrawals.store') }}" method="POST" id="withdrawalForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">{{__('Withdrawal Amount')}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        id="amount"
                                        name="amount"
                                        value="{{ old('amount') }}"
                                        min="{{ $settings->minimum_withdrawal_amount }}"
                                        max="{{ $balance->available_balance }}"
                                        step="0.01"
                                        placeholder="0.00"
                                        onkeyup="calculateFee()">
                                </div>
                                @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{__('Min: $')}}{{ number_format($settings->minimum_withdrawal_amount, 2) }} |
                                    {{__('Max: $')}}{{ number_format($balance->available_balance, 2) }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_method">{{__('Payment Method')}} <span class="text-danger">*</span></label>
                                <select class="form-control @error('payment_method') is-invalid @enderror"
                                    id="payment_method"
                                    name="payment_method">
                                    <option value="">{{__('Select Payment Method')}}</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>
                                        {{__('Bank Transfer')}}
                                    </option>
                                    <option value="bkash" {{ old('payment_method') == 'bkash' ? 'selected' : '' }}>
                                        {{__('bKash')}}
                                    </option>
                                    <option value="nogod" {{ old('payment_method') == 'nogod' ? 'selected' : '' }}>
                                        {{__('Nogod')}}
                                    </option>
                                    <option value="rocket" {{ old('payment_method') == 'rocket' ? 'selected' : '' }}>
                                        {{__('Rocket')}}
                                    </option>
                                    <option value="paypal" {{ old('payment_method') == 'paypal' ? 'selected' : '' }}>
                                        {{__('PayPal')}}
                                    </option>
                                </select>
                                @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Details -->
                    <div id="bankDetails" style="display: none;">
                        <h6 class="mt-4 mb-3">{{__('Bank Account Details')}}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_name">{{__('Bank Name')}} <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('bank_name') is-invalid @enderror"
                                        id="bank_name"
                                        name="bank_name"
                                        value="{{ old('bank_name') }}">
                                    @error('bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_holder_name">{{__('Account Holder Name')}} <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('account_holder_name') is-invalid @enderror"
                                        id="account_holder_name"
                                        name="account_holder_name"
                                        value="{{ old('account_holder_name') }}">
                                    @error('account_holder_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_number">{{__('Account Number')}} <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('account_number') is-invalid @enderror"
                                        id="account_number"
                                        name="account_number"
                                        value="{{ old('account_number') }}">
                                    @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="routing_number">{{__('Routing Number')}}</label>
                                    <input type="text"
                                        class="form-control @error('routing_number') is-invalid @enderror"
                                        id="routing_number"
                                        name="routing_number"
                                        value="{{ old('routing_number') }}">
                                    @error('routing_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PayPal Details -->
                    <div id="paypalDetails" style="display: none;">
                        <h6 class="mt-4 mb-3">{{__('PayPal Details')}}</h6>
                        <div class="form-group">
                            <label for="paypal_email">{{__('PayPal Email')}} <span class="text-danger">*</span></label>
                            <input type="email"
                                class="form-control @error('paypal_email') is-invalid @enderror"
                                id="paypal_email"
                                name="paypal_email"
                                value="{{ old('paypal_email') }}">
                            @error('paypal_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- bKash Details -->
                    <div id="bkashDetails" style="display: none;">
                        <h6 class="mt-4 mb-3">{{__('bKash Details')}}</h6>
                        <div class="form-group">
                            <label for="bkash_number">{{__('bKash Account Number')}} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+88</span>
                                </div>
                                <input type="text"
                                    class="form-control @error('bkash_number') is-invalid @enderror"
                                    id="bkash_number"
                                    name="mobile_number"
                                    value="{{ old('mobile_number') }}"
                                    placeholder="01XXXXXXXXX">
                            </div>
                            @error('mobile_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="bkash_account_name">{{__('Account Holder Name')}} <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('account_holder_name') is-invalid @enderror"
                                id="bkash_account_name"
                                name="account_holder_name"
                                value="{{ old('account_holder_name') }}">
                            @error('account_holder_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Nogod Details -->
                    <div id="nogodDetails" style="display: none;">
                        <h6 class="mt-4 mb-3">{{__('Nogod Details')}}</h6>
                        <div class="form-group">
                            <label for="nogod_number">{{__('Nogod Account Number')}} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+88</span>
                                </div>
                                <input type="text"
                                    class="form-control @error('nogod_number') is-invalid @enderror"
                                    id="nogod_number"
                                    name="mobile_number"
                                    value="{{ old('mobile_number') }}"
                                    placeholder="01XXXXXXXXX">
                            </div>
                            @error('mobile_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="nogod_account_name">{{__('Account Holder Name')}} <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('account_holder_name') is-invalid @enderror"
                                id="nogod_account_name"
                                name="account_holder_name"
                                value="{{ old('account_holder_name') }}">
                            @error('account_holder_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Rocket Details -->
                    <div id="rocketDetails" style="display: none;">
                        <h6 class="mt-4 mb-3">{{__('Rocket Details')}}</h6>
                        <div class="form-group">
                            <label for="rocket_number">{{__('Rocket Account Number')}} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+88</span>
                                </div>
                                <input type="text"
                                    class="form-control @error('rocket_number') is-invalid @enderror"
                                    id="rocket_number"
                                    name="mobile_number"
                                    value="{{ old('mobile_number') }}"
                                    placeholder="01XXXXXXXXX">
                            </div>
                            @error('mobile_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="rocket_account_name">{{__('Account Holder Name')}} <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('account_holder_name') is-invalid @enderror"
                                id="rocket_account_name"
                                name="account_holder_name"
                                value="{{ old('account_holder_name') }}">
                            @error('account_holder_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Fee Calculation -->
                    <div class="fee-calculation" id="feeCalculation" style="display: none;">
                        <h6>{{__('Fee Calculation')}}</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted">{{__('Amount Requested')}}</small>
                                <div id="requestedAmount">$0.00</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">{{__('Processing Fee')}}</small>
                                <div id="processingFee">$0.00</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">{{__('You Will Receive')}}</small>
                                <div id="finalAmount"><strong>$0.00</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label for="notes">{{__('Additional Notes')}}</label>
                        <textarea class="form-control"
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="{{__('Any additional information or special instructions...')}}">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> {{__('Submit Withdrawal Request')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        // Show/hide payment method details
        $('#payment_method').change(function() {
            const method = $(this).val();
            $('#bankDetails, #paypalDetails, #bkashDetails, #nogodDetails, #rocketDetails').hide();

            if (method === 'bank_transfer') {
                $('#bankDetails').show();
            } else if (method === 'paypal') {
                $('#paypalDetails').show();
            } else if (method === 'bkash') {
                $('#bkashDetails').show();
            } else if (method === 'nogod') {
                $('#nogodDetails').show();
            } else if (method === 'rocket') {
                $('#rocketDetails').show();
            }
        });

        // Trigger change event on page load to show correct details
        $('#payment_method').trigger('change');
    });

    function calculateFee() {
        const amount = parseFloat($('#amount').val()) || 0;

        if (amount > 0) {
            const feePercentage = parseFloat('{{ $settings->withdrawal_fee_percentage }}') || 0;
            const feeFixed = parseFloat('{{ $settings->withdrawal_fee_fixed }}') || 0;

            const percentageFee = (amount * feePercentage) / 100;
            const totalFee = percentageFee + feeFixed;
            const finalAmount = amount - totalFee;

            $('#requestedAmount').text('$' + amount.toFixed(2));
            $('#processingFee').text('$' + totalFee.toFixed(2));
            $('#finalAmount').html('<strong>$' + finalAmount.toFixed(2) + '</strong>');
            $('#feeCalculation').show();
        } else {
            $('#feeCalculation').hide();
        }
    }
</script>
@endsection