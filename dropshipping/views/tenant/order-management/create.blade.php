@extends('core::base.layouts.master')

@section('title')
{{__('Submit New Order')}}
@endsection

@section('custom_css')
<style>
    /* Modern Variables */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #28a745, #20c997);
        --warning-gradient: linear-gradient(135deg, #ffc107, #fd7e14);
        --danger-gradient: linear-gradient(135deg, #dc3545, #c82333);
        --info-gradient: linear-gradient(135deg, #17a2b8, #007bff);
        --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        --border-radius: 20px;
        --transition: all 0.3s ease;
    }

    /* Page Header */
    .page-header-create {
        background: var(--primary-gradient);
        border-radius: var(--border-radius);
        padding: 40px;
        margin-bottom: 30px;
        color: white;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }

    .page-header-create::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }

    .create-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .create-title h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .create-title p {
        opacity: 0.9;
        margin: 10px 0 0 0;
        font-size: 1.1rem;
    }

    /* Progress Steps */
    .progress-steps {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .step-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .step {
        display: flex;
        align-items: center;
        flex: 1;
        position: relative;
    }

    .step::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 60px;
        right: -20px;
        height: 2px;
        background: #e9ecef;
        transform: translateY(-50%);
        z-index: 1;
    }

    .step:last-child::after {
        display: none;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 15px;
        position: relative;
        z-index: 2;
        transition: var(--transition);
    }

    .step-number.active {
        background: var(--primary-gradient);
        color: white;
    }

    .step-number.completed {
        background: var(--success-gradient);
        color: white;
    }

    .step-title {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1.1rem;
    }

    .step-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 3px;
    }

    /* Modern Breadcrumb */
    .breadcrumb-modern {
        background: white;
        border-radius: 15px;
        padding: 15px 25px;
        margin-bottom: 30px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .breadcrumb-modern .breadcrumb {
        background: none;
        padding: 0;
        margin: 0;
    }

    .breadcrumb-modern .breadcrumb-item a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }

    .breadcrumb-modern .breadcrumb-item a:hover {
        color: #764ba2;
    }

    .breadcrumb-modern .breadcrumb-item.active {
        color: #6c757d;
    }

    /* Enhanced Order Selection Cards */
    .order-selection-card {
        border: 2px solid #e9ecef;
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 20px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        background: white;
        box-shadow: var(--card-shadow);
    }

    .order-selection-card:hover {
        border-color: #667eea;
        box-shadow: var(--hover-shadow);
        transform: translateY(-5px);
    }

    .order-selection-card.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, #f8f9ff, #ffffff);
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .order-selection-card .selection-indicator {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: var(--transition);
    }

    .order-selection-card.selected .selection-indicator {
        border-color: #667eea;
        background: var(--primary-gradient);
        color: white;
    }

    .order-selection-card h6 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .order-amount {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .order-earning {
        font-size: 1rem;
        color: #28a745;
        font-weight: 600;
        padding: 5px 12px;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 15px;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .order-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-processing {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
    }

    .status-delivered {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    /* Enhanced Commission Information */
    .commission-info {
        background: var(--primary-gradient);
        color: white;
        border-radius: var(--border-radius);
        padding: 30px;
        margin: 30px 0;
        display: none;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }

    .commission-info::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1) rotate(0deg);
        }

        50% {
            transform: scale(1.05) rotate(180deg);
        }
    }

    .commission-info h6 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .commission-info h6 i {
        margin-right: 10px;
        font-size: 1.4rem;
    }

    .commission-stat {
        text-align: center;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .commission-stat h5 {
        margin: 0 0 8px 0;
        font-size: 0.9rem;
        opacity: 0.9;
        font-weight: 500;
    }

    .commission-stat h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Enhanced Cards */
    .card-modern {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: var(--transition);
    }

    .card-modern:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .card-modern .card-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        padding: 25px 30px;
        margin: -30px -30px 30px -30px;
    }

    .card-modern .card-header h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-modern .card-header h5 i {
        margin-right: 15px;
        color: #667eea;
        font-size: 1.4rem;
    }

    /* Enhanced Form Elements */
    .form-group-modern {
        margin-bottom: 25px;
    }

    .form-group-modern label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
        display: block;
        font-size: 1rem;
    }

    .form-control-modern {
        border-radius: 15px;
        border: 2px solid #f1f3f4;
        padding: 15px 20px;
        font-size: 0.95rem;
        transition: var(--transition);
        background: white;
        width: 100%;
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
    }

    .form-control-modern::placeholder {
        color: #95a5a6;
        font-style: italic;
    }

    /* Enhanced Order Summary */
    .order-summary {
        background: linear-gradient(135deg, #f8f9fb, #ffffff);
        border-radius: 15px;
        padding: 25px;
        margin: 20px 0;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f4;
        transition: var(--transition);
    }

    .summary-row:hover {
        background: rgba(102, 126, 234, 0.05);
        border-radius: 10px;
        padding-left: 15px;
        padding-right: 15px;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 1.1rem;
        margin-top: 15px;
        padding-top: 20px;
        border-top: 2px solid #e9ecef;
    }

    .summary-row.earning {
        color: #28a745;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
    }

    /* Enhanced Buttons */
    .btn-modern {
        border-radius: 25px;
        padding: 15px 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: var(--transition);
        border: none;
        position: relative;
        overflow: hidden;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        font-size: 1rem;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: white;
    }

    .btn-modern:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-modern:disabled:hover {
        transform: none;
        box-shadow: none;
    }

    .btn-modern i {
        margin-right: 10px;
        font-size: 1.1rem;
    }

    .btn-modern.btn-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-modern.btn-success {
        background: var(--success-gradient);
        color: white;
    }

    .btn-modern.btn-secondary {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }

    /* Enhanced Empty State */
    .alert-empty {
        text-align: center;
        padding: 60px 30px;
        background: linear-gradient(135deg, #f8f9fb, #ffffff);
        border-radius: var(--border-radius);
        color: #6c757d;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: var(--card-shadow);
    }

    .alert-empty i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #dee2e6;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .alert-empty h5 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: #495057;
    }

    .alert-empty p {
        font-size: 1rem;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    /* Enhanced Help Information */
    .help-info {
        background: linear-gradient(135deg, #f8f9fb, #ffffff);
        border-radius: var(--border-radius);
        padding: 25px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .help-info ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .help-info li {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f3f4;
        transition: var(--transition);
    }

    .help-info li:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .help-info li:hover {
        background: rgba(102, 126, 234, 0.05);
        border-radius: 10px;
        padding-left: 15px;
        padding-right: 15px;
    }

    .help-info li i {
        margin-right: 15px;
        font-size: 1.1rem;
        color: #28a745;
    }

    /* Loading Animation */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header-create {
            padding: 25px 20px;
        }

        .create-header {
            flex-direction: column;
            text-align: center;
        }

        .create-title h1 {
            font-size: 2rem;
        }

        .card-modern {
            padding: 20px;
        }

        .card-modern .card-header {
            padding: 20px;
            margin: -20px -20px 20px -20px;
        }

        .order-selection-card {
            padding: 20px;
        }

        .step-indicator {
            flex-direction: column;
            gap: 20px;
        }

        .step::after {
            display: none;
        }

        .commission-info {
            padding: 20px;
        }

        .commission-stat {
            margin-bottom: 20px;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-fade-in {
        animation: fadeInUp 0.8s ease forwards;
    }

    .animate-slide-in {
        animation: slideInRight 0.8s ease forwards;
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Modern Breadcrumb -->
    <div class="breadcrumb-modern animate-fade-in">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dropshipping.order.management') }}">
                        <i class="fas fa-truck"></i> {{__('Order Management')}}
                    </a>
                </li>
                <li class="breadcrumb-item active">{{__('Submit New Order')}}</li>
            </ol>
        </nav>
    </div>

    <!-- Modern Page Header -->
    <div class="page-header-create animate-fade-in" style="animation-delay: 0.1s">
        <div class="create-header">
            <div class="create-title">
                <h1><i class="fas fa-plus-circle"></i> {{__('Convert Order to Dropshipping')}}</h1>
                <p>{{__('Transform your existing orders into profitable dropshipping opportunities')}}</p>
            </div>
            <div>
                <a href="{{ route('dropshipping.order.management') }}" class="btn-modern btn-secondary">
                    <i class="fas fa-arrow-left"></i> {{__('Back to Orders')}}
                </a>
            </div>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps animate-fade-in" style="animation-delay: 0.2s">
        <div class="step-indicator">
            <div class="step">
                <div class="step-number active" id="step1">1</div>
                <div>
                    <div class="step-title">{{__('Select Order')}}</div>
                    <div class="step-description">{{__('Choose an order to convert')}}</div>
                </div>
            </div>
            <div class="step">
                <div class="step-number" id="step2">2</div>
                <div>
                    <div class="step-title">{{__('Review Details')}}</div>
                    <div class="step-description">{{__('Verify order information')}}</div>
                </div>
            </div>
            <div class="step">
                <div class="step-number" id="step3">3</div>
                <div>
                    <div class="step-title">{{__('Submit')}}</div>
                    <div class="step-description">{{__('Complete the conversion')}}</div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('dropshipping.order.store') }}" method="POST" id="orderForm">
        @csrf

        <div class="row">
            <!-- Order Selection -->
            <div class="col-lg-8">
                <div class="card-modern animate-fade-in" style="animation-delay: 0.3s">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-shopping-cart"></i> {{__('Step 1: Select Order')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($inhouseOrders->count() > 0)
                        <p class="text-muted mb-4">{{__('Choose an existing inhouse order to convert to dropshipping and start earning commissions')}}</p>

                        <div class="row">
                            @foreach($inhouseOrders as $order)
                            <div class="col-md-6">
                                <div class="order-selection-card" onclick="selectOrder('{{ $order->id }}')">
                                    <input type="radio" name="order_id" value="{{ $order->id }}"
                                        id="order_{{ $order->id }}" style="display: none;" required>

                                    <div class="selection-indicator">
                                        <i class="fas fa-check"></i>
                                    </div>

                                    <h6>{{__('Order')}} #{{ $order->order_code }}</h6>

                                    <small class="text-muted d-block mb-3">
                                        <i class="far fa-calendar-alt"></i> {{__('Date')}}: {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}
                                    </small>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="order-amount">${{ number_format($order->total_payable_amount, 2) }}</span>
                                        <span class="order-earning">{{__('Earn')}}: ${{ number_format($order->total_payable_amount * 0.2, 2) }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> {{__('Customer')}}: {{ $order->customer_name ?? $order->guest_customer ?? 'Guest' }}
                                        </small>
                                        <span class="order-status status-{{ strtolower($order->delivery_status ?? 'pending') }}">
                                            {{ ucfirst($order->delivery_status ?? 'Pending') }}
                                        </span>
                                    </div>

                                    <small class="text-muted">
                                        <i class="fas fa-box"></i> {{__('Products')}}: {{ $order->total_product ?? 0 }} {{__('item(s)')}}
                                    </small>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Enhanced Pagination -->
                        @if($inhouseOrders->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $inhouseOrders->links() }}
                        </div>
                        @endif
                        @else
                        <div class="alert-empty">
                            <i class="fas fa-shopping-cart"></i>
                            <h5>{{__('No Orders Available')}}</h5>
                            <p>{{__('No inhouse orders found to convert to dropshipping.')}} <br>{{__('Create some orders first to get started with dropshipping.')}}</p>
                            <a href="{{ route('plugin.tlcommercecore.orders.inhouse') }}" class="btn-modern btn-primary">
                                <i class="fas fa-eye"></i> {{__('View Orders')}}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Enhanced Commission Information -->
                <div class="commission-info animate-fade-in" id="commissionInfo" style="animation-delay: 0.4s">
                    <h6><i class="fas fa-chart-line"></i> {{__('Earnings Overview')}}</h6>
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="commission-stat">
                                <h5>{{__('Order Amount')}}</h5>
                                <h4 id="orderAmount">$0.00</h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="commission-stat">
                                <h5>{{__('Commission')}}</h5>
                                <h4>20%</h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="commission-stat">
                                <h5>{{__('Your Earning')}}</h5>
                                <h4 id="totalEarning">$0.00</h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="commission-stat">
                                <h5>{{__('Status')}}</h5>
                                <h4 id="orderStatus">-</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Dropshipping Details -->
                <div class="card-modern animate-fade-in" style="animation-delay: 0.5s">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-truck"></i> {{__('Step 2: Dropshipping Details')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group-modern">
                            <label for="fulfillment_note">
                                <i class="fas fa-sticky-note"></i> {{__('Fulfillment Note')}}
                            </label>
                            <textarea class="form-control-modern" id="fulfillment_note" name="fulfillment_note"
                                rows="4" placeholder="{{__('Add any special instructions for dropshipping fulfillment (optional)...')}}">{{ old('fulfillment_note') }}</textarea>
                            @error('fulfillment_note')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            <small class="text-muted mt-2">
                                <i class="fas fa-info-circle"></i> {{__('This note will be visible to the dropshipping fulfillment team')}}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card-modern animate-slide-in" id="orderSummaryCard" style="display: none; animation-delay: 0.3s">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-receipt"></i> {{__('Conversion Summary')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="order-summary">
                            <div class="summary-row">
                                <span><i class="fas fa-hashtag"></i> {{__('Order Code')}}</span>
                                <span id="summaryOrderCode">-</span>
                            </div>
                            <div class="summary-row">
                                <span><i class="fas fa-user"></i> {{__('Customer')}}</span>
                                <span id="summaryCustomer">-</span>
                            </div>
                            <div class="summary-row">
                                <span><i class="fas fa-dollar-sign"></i> {{__('Order Amount')}}</span>
                                <span id="summaryAmount">-</span>
                            </div>
                            <div class="summary-row">
                                <span><i class="fas fa-box"></i> {{__('Products')}}</span>
                                <span id="summaryProducts">-</span>
                            </div>
                            <div class="summary-row earning">
                                <span><i class="fas fa-money-bill-wave"></i> {{__('Your Earning')}}</span>
                                <span id="summaryEarning">-</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-modern btn-primary" id="submitBtn" disabled>
                            <i class="fas fa-rocket"></i> {{__('Convert to Dropshipping')}}
                        </button>
                    </div>
                </div>

                <!-- Enhanced Help Information -->
                <div class="card-modern animate-slide-in" style="animation-delay: 0.4s">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-lightbulb"></i> {{__('How It Works')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="help-info">
                            <ul>
                                <li>
                                    <i class="fas fa-percentage"></i>
                                    <span>{{__('Earn 20% commission on each converted order')}}</span>
                                </li>
                                <li>
                                    <i class="fas fa-truck"></i>
                                    <span>{{__('Orders are processed and fulfilled via dropshipping')}}</span>
                                </li>
                                <li>
                                    <i class="fas fa-shipping-fast"></i>
                                    <span>{{__('Customer receives tracking information automatically')}}</span>
                                </li>
                                <li>
                                    <i class="fas fa-credit-card"></i>
                                    <span>{{__('Payments processed after successful delivery')}}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let selectedOrder = null;
    let currentStep = 1;

    function selectOrder(orderId) {
        // Remove selection from all orders
        document.querySelectorAll('.order-selection-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Add selection to clicked order
        const selectedCard = document.querySelector(`input[value="${orderId}"]`).closest('.order-selection-card');
        selectedCard.classList.add('selected');

        // Check the radio button
        document.getElementById(`order_${orderId}`).checked = true;

        // Get order details from the card
        const orderCode = selectedCard.querySelector('h6').textContent.trim();
        const orderAmount = parseFloat(selectedCard.querySelector('.order-amount').textContent.replace('$', ''));
        const customerName = selectedCard.querySelector('.fa-user').parentElement.textContent.replace('Customer: ', '');
        const productCount = selectedCard.querySelector('.fa-box').parentElement.textContent;
        const status = selectedCard.querySelector('.order-status').textContent.trim();

        selectedOrder = {
            id: orderId,
            code: orderCode,
            amount: orderAmount,
            customer: customerName,
            products: productCount,
            status: status
        };

        // Show commission info and summary with animation
        const commissionInfo = document.getElementById('commissionInfo');
        const summaryCard = document.getElementById('orderSummaryCard');

        commissionInfo.style.display = 'block';
        summaryCard.style.display = 'block';

        // Animate commission info
        setTimeout(() => {
            commissionInfo.style.opacity = '0';
            commissionInfo.style.transform = 'translateY(20px)';
            commissionInfo.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                commissionInfo.style.opacity = '1';
                commissionInfo.style.transform = 'translateY(0)';
            }, 50);
        }, 50);

        // Update displays
        updateEarnings();
        updateOrderSummary();
        updateSteps();
        checkFormCompletion();
    }

    function updateEarnings() {
        if (!selectedOrder) return;

        const totalEarning = selectedOrder.amount * 0.2; // 20% commission

        document.getElementById('orderAmount').textContent = `$${selectedOrder.amount.toFixed(2)}`;
        document.getElementById('totalEarning').textContent = `$${totalEarning.toFixed(2)}`;
        document.getElementById('orderStatus').textContent = selectedOrder.status;
    }

    function updateOrderSummary() {
        if (!selectedOrder) return;

        const totalEarning = selectedOrder.amount * 0.2;

        document.getElementById('summaryOrderCode').textContent = selectedOrder.code;
        document.getElementById('summaryCustomer').textContent = selectedOrder.customer;
        document.getElementById('summaryAmount').textContent = `$${selectedOrder.amount.toFixed(2)}`;
        document.getElementById('summaryProducts').textContent = selectedOrder.products;
        document.getElementById('summaryEarning').textContent = `$${totalEarning.toFixed(2)}`;
    }

    function updateSteps() {
        if (selectedOrder) {
            // Step 1 completed
            document.getElementById('step1').classList.add('completed');
            document.getElementById('step1').classList.remove('active');

            // Step 2 active
            document.getElementById('step2').classList.add('active');
            currentStep = 2;
        }
    }

    function checkFormCompletion() {
        const hasSelectedOrder = selectedOrder !== null;
        const submitBtn = document.getElementById('submitBtn');

        submitBtn.disabled = !hasSelectedOrder;

        if (hasSelectedOrder) {
            submitBtn.style.background = 'var(--success-gradient)';
            submitBtn.querySelector('i').className = 'fas fa-rocket';

            // Step 3 ready
            document.getElementById('step3').classList.add('active');
            currentStep = 3;
        } else {
            submitBtn.style.background = 'var(--primary-gradient)';
            submitBtn.querySelector('i').className = 'fas fa-rocket';
        }
    }

    // Enhanced form submission with loading
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (!selectedOrder) {
            e.preventDefault();
            showNotification('{{__("Please select an order first")}}', 'error');
            return;
        }

        // Show loading
        showLoading();

        // Update submit button
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{__("Converting Order...")}}';
        submitBtn.disabled = true;
    });

    // Loading Functions
    function showLoading() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 10px;
    `;

        notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.animate-fade-in, .animate-slide-in');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = el.classList.contains('animate-fade-in') ? 'translateY(30px)' : 'translateX(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.8s ease';
                el.style.opacity = '1';
                el.style.transform = 'translate(0)';
            }, index * 100);
        });
    });
</script>
@endsection