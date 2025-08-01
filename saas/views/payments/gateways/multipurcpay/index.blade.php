@extends('core::base.layouts.master')

@section('title')
    {{ translate('Multipurcpay Payment') }}
@endsection

@section('custom_css')
    <style>
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .payment-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .payment-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .payment-button {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .instruction-text {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.5;
        }
    </style>
@endsection

@section('main_content')
<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            @if($logo)
                <img src="{{ getFilePath($logo) }}" alt="Multipurcpay" class="payment-logo">
            @else
                <h2>{{ translate('Multipurcpay Payment') }}</h2>
            @endif
            
            <div class="payment-amount">
                {{ $currency }} {{ number_format($payable_amount, 2) }}
            </div>
            <p class="text-muted">{{ translate('Redirecting to payment gateway...') }}</p>
        </div>

        <div class="alert alert-info">
            <strong>{{ translate('Processing Payment') }}</strong><br>
            {{ translate('You will be redirected to the payment gateway automatically. If not redirected within 5 seconds, click the button below.') }}
        </div>

        <div class="payment-details">
            <div class="detail-row">
                <span>{{ translate('Package') }}:</span>
                <span>{{ session('store_name', 'N/A') }}</span>
            </div>
            <div class="detail-row">
                <span>{{ translate('Payment Method') }}:</span>
                <span>Multipurcpay</span>
            </div>
            <div class="detail-row">
                <span>{{ translate('Currency') }}:</span>
                <span>{{ $currency }}</span>
            </div>
            <div class="detail-row">
                <span>{{ translate('Amount') }}:</span>
                <span>{{ $currency }} {{ number_format($payable_amount, 2) }}</span>
            </div>
        </div>

        @if($instruction)
            <div class="alert alert-info">
                <div class="instruction-text">
                    {!! nl2br(e($instruction)) !!}
                </div>
            </div>
        @endif

        <div id="payment-form">
            <button type="button" id="pay-now-btn" class="payment-button btn-primary">
                {{ translate('Continue to Payment') }} - {{ $currency }} {{ number_format($payable_amount, 2) }}
            </button>
            
            <button type="button" id="cancel-btn" class="payment-button btn-secondary">
                {{ translate('Cancel Payment') }}
            </button>
        </div>

        <div id="loading" class="loading" style="display: block;">
            <div class="spinner"></div>
            <p>{{ translate('Initializing payment...') }}</p>
            <p class="text-muted">{{ translate('Please wait while we redirect you to the payment gateway') }}</p>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
    console.log('Multipurcpay JavaScript Loading...'); // Debug log
    
    document.addEventListener('DOMContentLoaded', function () {
        console.log('DOM Content Loaded'); // Debug log
        
        const payNowBtn = document.getElementById('pay-now-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const paymentForm = document.getElementById('payment-form');
        const loadingDiv = document.getElementById('loading');
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        console.log('Elements found:', {
            payNowBtn: !!payNowBtn,
            cancelBtn: !!cancelBtn,
            paymentForm: !!paymentForm,
            loadingDiv: !!loadingDiv,
            csrfToken: !!csrfToken
        }); // Debug log

        if (!csrfToken) {
            console.error('CSRF token not found!');
            alert('CSRF token missing. Please refresh the page.');
            return;
        }

        const showError = (message) => {
            console.log('Showing error:', message); // Debug log
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (paymentForm) paymentForm.style.display = 'block';
            if (typeof toastr !== 'undefined') {
                toastr.error(message, '{{ translate("Payment Error") }}');
            } else {
                alert('Payment Error: ' + message);
            }
        };

        const initiatePayment = () => {
            console.log('Initiating payment...'); // Debug log
            
            if (paymentForm) paymentForm.style.display = 'none';
            if (loadingDiv) loadingDiv.style.display = 'block';

            const data = {
                _token: '{{ csrf_token() }}',
                amount: '{{ $payable_amount }}',
                currency: '{{ $currency }}'
            };
            
            console.log('Payment data:', data); // Debug log
            console.log('Request URL:', '{{ route("plugin.saas.multipurcpay.create.charge") }}'); // Debug log

            fetch('{{ route("plugin.saas.multipurcpay.create.charge") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                console.log('Response headers:', response.headers); // Debug log
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Payment Response:', data); // Debug log
                if (data.success && data.payment_url) {
                    console.log('Redirecting to:', data.payment_url); // Debug log
                    // Redirect to payment URL
                    window.location.href = data.payment_url;
                } else {
                    console.log('Payment failed:', data.message); // Debug log
                    showError(data.message || '{{ translate("Payment initiation failed. Please try again.") }}');
                }
            })
            .catch(error => {
                console.error('Payment Error:', error);
                showError('{{ translate("An unexpected error occurred. Please try again.") }}: ' + error.message);
            });
        };

        // Auto-initiate payment after 2 seconds (fallback mechanism)
        setTimeout(() => {
            console.log('Auto-initiating payment (fallback)...'); // Debug log
            initiatePayment();
        }, 2000);

        if (payNowBtn) {
            console.log('Adding click event to Pay Now button'); // Debug log
            payNowBtn.addEventListener('click', function(e) {
                console.log('Pay Now button clicked!'); // Debug log
                e.preventDefault();
                initiatePayment();
            });
        } else {
            console.error('Pay Now button not found!');
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                console.log('Cancel button clicked!'); // Debug log
                e.preventDefault();
                if (confirm('{{ translate("Are you sure you want to cancel this payment?") }}')) {
                    window.location.href = '{{ route("plugin.saas.multipurcpay.cancel.payment") }}';
                }
            });
        } else {
            console.error('Cancel button not found!');
        }
    });
</script>
@endsection