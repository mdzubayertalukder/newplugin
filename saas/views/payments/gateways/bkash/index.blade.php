@extends('core::base.layouts.master')
@section('title')
{{ translate('bKash Payment') }}
@endsection
@section('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('custom_css')
<style>
    .bkash-payment-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        background: white;
    }

    .bkash-logo {
        text-align: center;
        margin-bottom: 30px;
    }

    .payment-amount {
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        color: #E2136E;
        margin-bottom: 30px;
    }

    .bkash-button {
        background: #E2136E;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 5px;
        font-size: 16px;
        width: 100%;
        cursor: pointer;
        transition: background 0.3s;
    }

    .bkash-button:hover {
        background: #c1105c;
    }

    .bkash-button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .loading {
        display: none;
        text-align: center;
        margin-top: 20px;
    }

    .error-message {
        color: #dc3545;
        text-align: center;
        margin-top: 10px;
        display: none;
    }
</style>
@endsection
@section('main_content')
<div class="bkash-payment-container">
    <div class="bkash-logo">
        <img src="https://seeklogo.com/images/B/bkash-logo-FBB258B90F-seeklogo.com.png" alt="bKash" height="60">
    </div>

    <div class="payment-amount">
        {{ translate('Amount to Pay') }}: {{ $total_payable_amount }} {{ $currency }}
    </div>

    <button id="bkash-button" class="bkash-button">
        {{ translate('Pay with bKash') }}
    </button>

    <div class="loading" id="loading">
        <i class="fa fa-spinner fa-spin"></i> {{ translate('Processing payment...') }}
    </div>

    <div class="error-message" id="error-message"></div>

    <!-- Hidden form data -->
    <input type="hidden" id="total_payable_amount" value="{{ $total_payable_amount }}">
    <input type="hidden" id="currency" value="{{ $currency }}">
    <input type="hidden" id="app_key" value="{{ $app_key }}">
    <input type="hidden" id="base_url" value="{{ $base_url }}">
</div>
@endsection

@section('custom_scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        'use strict';

        let paymentToken = null;
        let paymentID = null;

        const appKey = $('#app_key').val();
        const baseUrl = $('#base_url').val();
        const amount = $('#total_payable_amount').val();
        const currency = $('#currency').val();

        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Get bKash token first
        function getBkashToken() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route("plugin.saas.bkash.get.token") }}',
                    type: 'POST',
                    data: {},
                    success: function(response) {
                        console.log('Token response:', response);
                        if (response.success) {
                            paymentToken = response.token;
                            resolve(response.token);
                        } else {
                            reject(response.message || 'Failed to get token');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Token request failed:', xhr.responseText);
                        if (xhr.status === 419) {
                            reject('Session expired. Please refresh the page and try again.');
                        } else if (xhr.status === 500) {
                            reject('Server error. Please check your bKash configuration.');
                        } else {
                            reject('Connection error: ' + (xhr.responseJSON?.message || error));
                        }
                    }
                });
            });
        }

        // Create payment
        function createPayment() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route("plugin.saas.bkash.create.payment") }}',
                    type: 'POST',
                    data: {
                        token: paymentToken
                    },
                    success: function(response) {
                        console.log('Create payment response:', response);
                        if (response.success) {
                            paymentID = response.data.paymentID;
                            resolve(response.data);
                        } else {
                            reject(response.message || 'Failed to create payment');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Create payment failed:', xhr.responseText);
                        reject('Failed to create payment: ' + (xhr.responseJSON?.message || error));
                    }
                });
            });
        }

        // Execute payment
        function executePayment() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route("plugin.saas.bkash.execute.payment") }}',
                    type: 'POST',
                    data: {
                        token: paymentToken,
                        paymentID: paymentID
                    },
                    success: function(response) {
                        console.log('Execute payment response:', response);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response.message || 'Failed to execute payment');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Execute payment failed:', xhr.responseText);
                        reject('Failed to execute payment: ' + (xhr.responseJSON?.message || error));
                    }
                });
            });
        }

        // Verify payment status (alternative to execute)
        function verifyPayment() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route("plugin.saas.bkash.verify.payment") }}',
                    type: 'POST',
                    data: {
                        token: paymentToken,
                        paymentID: paymentID
                    },
                    success: function(response) {
                        console.log('Verify payment response:', response);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response.message || 'Failed to verify payment');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Verify payment failed:', xhr.responseText);
                        reject('Failed to verify payment: ' + (xhr.responseJSON?.message || error));
                    }
                });
            });
        }

        // Show error message
        function showError(message) {
            $('#error-message').text(message).show();
            $('#loading').hide();
            $('#bkash-button').prop('disabled', false);
        }

        // Hide error message
        function hideError() {
            $('#error-message').hide();
        }

        // Main payment flow
        $('#bkash-button').on('click', async function() {
            hideError();
            $(this).prop('disabled', true);
            $('#loading').show();

            try {
                // Step 1: Get token
                await getBkashToken();

                // Step 2: Create payment
                const paymentData = await createPayment();

                // Step 3: Show bKash payment interface
                if (paymentData.paymentID) {
                    // For bKash tokenized checkout, we need to show the payment interface
                    // This could be a popup, iframe, or redirect to bKash app
                    showBkashPaymentInterface(paymentData);
                } else {
                    showError('Failed to create payment: No payment ID received');
                }

            } catch (error) {
                console.error('Payment error:', error);
                showError(error || 'Payment failed. Please try again.');
            }
        });

        // Show bKash payment interface
        function showBkashPaymentInterface(paymentData) {
            $('#loading').html(`
                <div style="text-align: center;">
                    <i class="fa fa-spinner fa-spin"></i> {{ translate("Payment initiated...") }}<br>
                    <small style="color: #666; margin-top: 10px; display: block;">
                        {{ translate("Please complete the payment in your bKash app") }}<br>
                        {{ translate("Amount:") }} ${amount} ${currency}<br>
                        {{ translate("Payment ID:") }} ${paymentData.paymentID}
                    </small>
                    <div style="margin-top: 15px;">
                        <button id="check-payment-btn" class="btn btn-primary" style="background: #E2136E; border: none; color: white; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            {{ translate("I have completed the payment") }}
                        </button>
                        <br>
                        <small style="color: #999; margin-top: 5px; display: block;">
                            {{ translate("Click this button after completing payment in your bKash app") }}
                        </small>
                    </div>
                </div>
            `);

            // Add click handler for manual payment check
            $('#check-payment-btn').on('click', async function() {
                $(this).prop('disabled', true).text('{{ translate("Checking payment...") }}');
                await checkPaymentStatus(paymentData);
            });

            // Also try automatic checking with longer intervals
            setTimeout(async function() {
                await checkPaymentStatus(paymentData);
            }, 15000); // First check after 15 seconds

            setTimeout(async function() {
                await checkPaymentStatus(paymentData);
            }, 30000); // Second check after 30 seconds

            setTimeout(async function() {
                await checkPaymentStatus(paymentData);
            }, 45000); // Third check after 45 seconds
        }

        // Check payment status with better error handling
        async function checkPaymentStatus(paymentData) {
            try {
                console.log('Checking payment status for:', paymentData.paymentID);

                // First try to verify payment status
                let result;
                try {
                    result = await verifyPayment();
                    console.log('Verify payment result:', result);
                } catch (verifyError) {
                    console.log('Verify payment failed, trying execute payment:', verifyError);
                    // If verify fails, try execute payment
                    result = await executePayment();
                }

                if (result.success && result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else if (result.success && result.status === 'completed') {
                    // Payment verified as completed
                    window.location.href = '{{ route("plugin.saas.bkash.success.payment") }}';
                } else {
                    // Handle specific bKash errors with better user guidance
                    if (result.message && result.message.includes('Payment state is invalid')) {
                        showError(`
                            <strong>{{ translate("Payment not completed yet") }}</strong><br>
                            {{ translate("Please ensure you have completed the payment in your bKash app.") }}<br>
                            {{ translate("If you have already paid, please wait a moment and click 'I have completed the payment' again.") }}<br>
                            <small>{{ translate("Payment ID:") }} ${paymentData.paymentID}</small>
                        `);
                    } else if (result.message && result.message.includes('Payment ID is invalid')) {
                        showError('{{ translate("Payment session expired. Please try the payment again.") }}');
                    } else if (result.message && result.message.includes('Payment has already been completed')) {
                        // Payment was successful but we need to redirect
                        window.location.href = '{{ route("plugin.saas.bkash.success.payment") }}';
                    } else {
                        showError(result.message || '{{ translate("Payment execution failed") }}');
                    }
                }
            } catch (error) {
                console.error('Payment status check failed:', error);
                // Don't show error immediately, let user try manually
                $('#check-payment-btn').prop('disabled', false).text('{{ translate("Check Payment Again") }}');
            }
        }
    });
</script>
@endsection