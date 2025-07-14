@extends('core::base.layouts.master')

@section('title')
{{__('Request Withdrawal')}}
@endsection

@section('main_content')
<div style="background: #f5f5f5; min-height: 100vh; padding: 20px;">
    <div style="max-width: 800px; margin: 0 auto;">

        <!-- Header -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #333;">{{__('Request Withdrawal')}}</h3>
            <a href="{{ route('user.dropshipping.withdrawals.index') }}" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> {{__('Back')}}
            </a>
        </div>

        <!-- Balance Display -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; text-align: center;">
            <h4 style="margin: 0 0 10px 0; color: #666;">{{__('Available Balance')}}</h4>
            <div style="font-size: 2.5rem; font-weight: bold; color: #28a745; margin: 10px 0;">${{ number_format($balance->available_balance, 2) }}</div>
            <small style="color: #888;">{{__('Ready for withdrawal')}}</small>
        </div>

        <!-- Info Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <h6 style="color: #666; font-size: 0.9rem; margin-bottom: 8px; text-transform: uppercase;">{{__('Minimum Amount')}}</h6>
                <div style="font-size: 1.2rem; font-weight: 600; color: #333;">${{ number_format($settings->minimum_withdrawal_amount, 2) }}</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <h6 style="color: #666; font-size: 0.9rem; margin-bottom: 8px; text-transform: uppercase;">{{__('Processing Time')}}</h6>
                <div style="font-size: 1.2rem; font-weight: 600; color: #333;">{{ $settings->withdrawal_processing_days }} {{__('Days')}}</div>
            </div>
        </div>

        <!-- Messages -->
        @if($errors->any())
        <div style="background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
            <strong>{{__('Please fix the following errors:')}}</strong>
            <ul style="margin: 10px 0 0 0;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <!-- Withdrawal Form -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <form action="{{ route('user.dropshipping.withdrawals.store') }}" method="POST" id="withdrawalForm">
                @csrf

                <!-- Amount Section -->
                <div style="margin-bottom: 30px;">
                    <h5 style="color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee;">{{__('1. Enter Withdrawal Amount')}}</h5>

                    <div style="width: 50%; display: inline-block;">
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Amount')}} <span style="color: #dc3545;">*</span></label>
                            <div style="display: flex;">
                                <span style="background: #e9ecef; border: 2px solid #e9ecef; border-radius: 6px 0 0 6px; padding: 12px 15px; color: #333; font-weight: 600;">$</span>
                                <input type="number"
                                    style="border: 2px solid #e9ecef; border-left: none; border-radius: 0 6px 6px 0; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
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
                            <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                            @enderror
                            <small style="color: #666; font-size: 12px;">
                                {{__('Min: $')}}{{ number_format($settings->minimum_withdrawal_amount, 2) }} |
                                {{__('Max: $')}}{{ number_format($balance->available_balance, 2) }}
                            </small>
                        </div>
                    </div>

                    <!-- Fee Calculation -->
                    <div id="feeCalculation" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; display: none;">
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <span>{{__('Amount Requested')}}</span>
                            <span id="requestedAmount">$0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                            <span>{{__('Processing Fee')}}</span>
                            <span id="processingFee">$0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 15px 0 0 0; font-weight: 700; font-size: 1.1rem; color: #28a745; border-top: 2px solid #dee2e6;">
                            <span>{{__('You Will Receive')}}</span>
                            <span id="finalAmount">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div style="margin-bottom: 30px;">
                    <h5 style="color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee;">{{__('2. Choose Payment Method')}}</h5>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <!-- bKash -->
                        <div class="payment-option" data-method="bkash" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; background: white; min-height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="bkash" id="bkash" {{ old('payment_method') == 'bkash' ? 'checked' : '' }} style="display: none;">
                            <div style="font-size: 28px; color: #6c757d; margin-bottom: 10px;"><i class="fas fa-mobile-alt"></i></div>
                            <div style="font-weight: 600; color: #333; font-size: 14px;">{{__('bKash')}}</div>
                        </div>

                        <!-- Nogod -->
                        <div class="payment-option" data-method="nogod" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; background: white; min-height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="nogod" id="nogod" {{ old('payment_method') == 'nogod' ? 'checked' : '' }} style="display: none;">
                            <div style="font-size: 28px; color: #6c757d; margin-bottom: 10px;"><i class="fas fa-mobile-alt"></i></div>
                            <div style="font-weight: 600; color: #333; font-size: 14px;">{{__('Nogod')}}</div>
                        </div>

                        <!-- Rocket -->
                        <div class="payment-option" data-method="rocket" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; background: white; min-height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="rocket" id="rocket" {{ old('payment_method') == 'rocket' ? 'checked' : '' }} style="display: none;">
                            <div style="font-size: 28px; color: #6c757d; margin-bottom: 10px;"><i class="fas fa-rocket"></i></div>
                            <div style="font-weight: 600; color: #333; font-size: 14px;">{{__('Rocket')}}</div>
                        </div>
                    </div>

                    @error('payment_method')
                    <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Payment Details Section -->
                <div style="margin-bottom: 30px;">
                    <h5 style="color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee;">{{__('3. Enter Payment Details')}}</h5>



                    <!-- bKash Details -->
                    <div id="bkashDetails" class="payment-details" style="display: none; margin-top: 20px; padding: 25px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('bKash Number')}} <span style="color: #dc3545;">*</span></label>
                                <div style="display: flex;">
                                    <span style="background: #e9ecef; border: 2px solid #e9ecef; border-radius: 6px 0 0 6px; padding: 12px 15px; color: #333; font-weight: 600;">+88</span>
                                    <input type="text" style="border: 2px solid #e9ecef; border-left: none; border-radius: 0 6px 6px 0; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                        id="bkash_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="01XXXXXXXXX">
                                </div>
                                @error('mobile_number')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Account Holder Name')}} <span style="color: #dc3545;">*</span></label>
                                <input type="text" style="border: 2px solid #e9ecef; border-radius: 6px; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                    id="bkash_account_name" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="Enter account holder name">
                                @error('account_holder_name')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Nogod Details -->
                    <div id="nogodDetails" class="payment-details" style="display: none; margin-top: 20px; padding: 25px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Nogod Number')}} <span style="color: #dc3545;">*</span></label>
                                <div style="display: flex;">
                                    <span style="background: #e9ecef; border: 2px solid #e9ecef; border-radius: 6px 0 0 6px; padding: 12px 15px; color: #333; font-weight: 600;">+88</span>
                                    <input type="text" style="border: 2px solid #e9ecef; border-left: none; border-radius: 0 6px 6px 0; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                        id="nogod_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="01XXXXXXXXX">
                                </div>
                                @error('mobile_number')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Account Holder Name')}} <span style="color: #dc3545;">*</span></label>
                                <input type="text" style="border: 2px solid #e9ecef; border-radius: 6px; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                    id="nogod_account_name" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="Enter account holder name">
                                @error('account_holder_name')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Rocket Details -->
                    <div id="rocketDetails" class="payment-details" style="display: none; margin-top: 20px; padding: 25px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Rocket Number')}} <span style="color: #dc3545;">*</span></label>
                                <div style="display: flex;">
                                    <span style="background: #e9ecef; border: 2px solid #e9ecef; border-radius: 6px 0 0 6px; padding: 12px 15px; color: #333; font-weight: 600;">+88</span>
                                    <input type="text" style="border: 2px solid #e9ecef; border-left: none; border-radius: 0 6px 6px 0; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                        id="rocket_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="01XXXXXXXXX">
                                </div>
                                @error('mobile_number')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Account Holder Name')}} <span style="color: #dc3545;">*</span></label>
                                <input type="text" style="border: 2px solid #e9ecef; border-radius: 6px; padding: 12px 15px; font-size: 14px; width: 100%; background: white;"
                                    id="rocket_account_name" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="Enter account holder name">
                                @error('account_holder_name')
                                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div style="margin-bottom: 30px;">
                    <h5 style="color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee;">{{__('4. Additional Notes')}} <small style="color: #666;">({{__('Optional')}})</small></h5>

                    <div>
                        <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">{{__('Notes or Instructions')}}</label>
                        <textarea style="border: 2px solid #e9ecef; border-radius: 6px; padding: 12px 15px; font-size: 14px; width: 100%; background: white; resize: vertical;"
                            id="notes" name="notes" rows="3" placeholder="Any additional information or special instructions...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" style="background: #007bff; color: white; padding: 15px 40px; border-radius: 8px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-paper-plane"></i> {{__('Submit Withdrawal Request')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .payment-option:hover {
        border-color: #007bff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    .payment-option.active {
        border-color: #007bff !important;
        background: #f8f9ff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .payment-option.active div:first-of-type {
        color: #007bff !important;
    }

    .payment-option.active div:last-of-type {
        color: #007bff !important;
        font-weight: 700 !important;
    }

    button:hover {
        background: #0056b3 !important;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }

        div[style*="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))"] {
            grid-template-columns: 1fr !important;
        }

        div[style*="flex: 1"] {
            flex: none !important;
            width: 100% !important;
            margin-bottom: 15px;
        }

        div[style*="width: 50%"] {
            width: 100% !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Withdrawal form loaded');

        // Payment method selection
        const paymentOptions = document.querySelectorAll('.payment-option');
        paymentOptions.forEach(function(option) {
            option.addEventListener('click', function() {
                const method = this.getAttribute('data-method');
                console.log('Payment method selected:', method);

                // Remove active class from all options
                paymentOptions.forEach(function(opt) {
                    opt.classList.remove('active');
                });

                // Add active class to clicked option
                this.classList.add('active');

                // Check the radio button
                const radioButton = this.querySelector('input[type="radio"]');
                if (radioButton) {
                    radioButton.checked = true;
                }

                // Hide all payment details
                const paymentDetails = document.querySelectorAll('.payment-details');
                paymentDetails.forEach(function(detail) {
                    detail.style.display = 'none';
                });

                // Show selected payment details
                const selectedDetails = document.getElementById(method + 'Details');
                if (selectedDetails) {
                    selectedDetails.style.display = 'block';
                }
            });
        });

        // Initialize on page load
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (selectedMethod) {
            const methodValue = selectedMethod.value;
            const methodOption = document.querySelector('.payment-option[data-method="' + methodValue + '"]');
            if (methodOption) {
                methodOption.classList.add('active');
            }
            const methodDetails = document.getElementById(methodValue + 'Details');
            if (methodDetails) {
                methodDetails.style.display = 'block';
            }
        }

        // Form submission handler
        const form = document.getElementById('withdrawalForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting...');

                // Find the selected payment method
                const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
                if (!selectedPaymentMethod) {
                    alert('Please select a payment method');
                    e.preventDefault();
                    return;
                }

                const selectedMethod = selectedPaymentMethod.value;
                console.log('Selected payment method:', selectedMethod);

                // Disable all inputs in hidden payment detail sections
                const allPaymentDetails = document.querySelectorAll('.payment-details');
                allPaymentDetails.forEach(function(detail) {
                    if (detail.style.display === 'none') {
                        // This section is hidden, disable all inputs inside it
                        const inputs = detail.querySelectorAll('input, select, textarea');
                        inputs.forEach(function(input) {
                            input.disabled = true;
                        });
                    }
                });
            });
        }
    });

    function calculateFee() {
        const amountInput = document.getElementById('amount');
        const amount = parseFloat(amountInput.value) || 0;
        console.log('Calculating fee for amount:', amount);

        if (amount > 0) {
            const feePercentage = parseFloat('{{ $settings->withdrawal_fee_percentage }}') || 0;
            const feeFixed = parseFloat('{{ $settings->withdrawal_fee_fixed }}') || 0;

            const percentageFee = (amount * feePercentage) / 100;
            const totalFee = percentageFee + feeFixed;
            const finalAmount = amount - totalFee;

            const requestedAmountEl = document.getElementById('requestedAmount');
            const processingFeeEl = document.getElementById('processingFee');
            const finalAmountEl = document.getElementById('finalAmount');
            const feeCalculationEl = document.getElementById('feeCalculation');

            if (requestedAmountEl) requestedAmountEl.textContent = '$' + amount.toFixed(2);
            if (processingFeeEl) processingFeeEl.textContent = '$' + totalFee.toFixed(2);
            if (finalAmountEl) finalAmountEl.textContent = '$' + finalAmount.toFixed(2);
            if (feeCalculationEl) feeCalculationEl.style.display = 'block';
        } else {
            const feeCalculationEl = document.getElementById('feeCalculation');
            if (feeCalculationEl) feeCalculationEl.style.display = 'none';
        }
    }
</script>
@endsection