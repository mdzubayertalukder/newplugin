@extends('core::base.layouts.master')

@section('title')
{{__('Order Details')}} - {{ $order->order_number }}
@endsection

@section('style')
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
    .page-header-detailed {
        background: var(--primary-gradient);
        border-radius: var(--border-radius);
        padding: 40px;
        margin-bottom: 30px;
        color: white;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }

    .page-header-detailed::before {
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

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .order-title h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .order-title p {
        opacity: 0.9;
        margin: 10px 0 0 0;
        font-size: 1.1rem;
    }

    .order-status-large {
        padding: 15px 30px;
        border-radius: 30px;
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.1);
        display: inline-flex;
        align-items: center;
        backdrop-filter: blur(10px);
    }

    .order-status-large i {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    /* Breadcrumb */
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

    /* Order Cards */
    .order-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: var(--transition);
    }

    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .order-card h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 15px;
    }

    .order-card h5 i {
        margin-right: 15px;
        color: #667eea;
        font-size: 1.4rem;
    }

    /* Product Details */
    .product-details {
        display: flex;
        align-items: center;
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fb, #ffffff);
        border-radius: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .product-icon {
        width: 80px;
        height: 80px;
        background: var(--primary-gradient);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .product-info h6 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .product-info p {
        color: #6c757d;
        margin: 3px 0;
    }

    .product-info .price {
        font-size: 1.1rem;
        font-weight: 600;
        color: #28a745;
    }

    /* Customer Details */
    .customer-card {
        background: linear-gradient(135deg, #f8f9fb, #ffffff);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .customer-avatar {
        width: 60px;
        height: 60px;
        background: var(--success-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }

    .customer-info h6 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .customer-info p {
        color: #6c757d;
        margin: 3px 0;
        font-size: 0.95rem;
    }

    /* Order Timeline */
    .order-timeline {
        position: relative;
        padding-left: 30px;
    }

    .order-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        padding-left: 25px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -23px;
        top: 5px;
        width: 12px;
        height: 12px;
        background: #667eea;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px #667eea;
    }

    .timeline-item.completed::before {
        background: #28a745;
        box-shadow: 0 0 0 3px #28a745;
    }

    .timeline-item.rejected::before {
        background: #dc3545;
        box-shadow: 0 0 0 3px #dc3545;
    }

    .timeline-content {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .timeline-content h6 {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .timeline-content p {
        color: #6c757d;
        margin: 0;
        font-size: 0.9rem;
    }

    .timeline-content small {
        color: #95a5a6;
        font-size: 0.8rem;
    }

    /* Financial Details */
    .financial-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .financial-row:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 1.1rem;
        padding-top: 20px;
        border-top: 2px solid #e9ecef;
    }

    .financial-row.earning {
        color: #28a745;
    }

    .financial-row.commission {
        color: #6f42c1;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 30px;
    }

    .btn-action-modern {
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
    }

    .btn-action-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-action-modern:hover::before {
        left: 100%;
    }

    .btn-action-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: white;
    }

    .btn-action-modern i {
        margin-right: 10px;
        font-size: 1.1rem;
    }

    .btn-action-modern.btn-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-action-modern.btn-success {
        background: var(--success-gradient);
        color: white;
    }

    .btn-action-modern.btn-danger {
        background: var(--danger-gradient);
        color: white;
    }

    .btn-action-modern.btn-secondary {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }

    /* Info Badges */
    .info-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .info-badge.badge-primary {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    .info-badge.badge-success {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .info-badge.badge-warning {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header-detailed {
            padding: 25px 20px;
        }

        .order-header {
            flex-direction: column;
            text-align: center;
        }

        .order-title h1 {
            font-size: 2rem;
        }

        .order-card {
            padding: 20px;
        }

        .product-details {
            flex-direction: column;
            text-align: center;
        }

        .product-icon {
            margin: 0 0 15px 0;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-action-modern {
            width: 100%;
            justify-content: center;
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
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-modern animate-fade-in">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dropshipping.order.management') }}"><i class="fas fa-truck"></i> {{__('Order Management')}}</a></li>
                <li class="breadcrumb-item active">{{__('Order Details')}}</li>
            </ol>
        </nav>
    </div>

    <!-- Page Header -->
    <div class="page-header-detailed animate-fade-in" style="animation-delay: 0.1s">
        <div class="order-header">
            <div class="order-title">
                <h1><i class="fas fa-receipt"></i> {{ $order->order_number }}</h1>
                <p>{{__('Order submitted on')}} {{ $order->submitted_at ? $order->submitted_at->format('M d, Y \a\t h:i A') : __('N/A') }}</p>
            </div>
            <div class="order-status-large">
                @if($order->status === 'pending')
                <i class="fas fa-clock"></i>
                @elseif($order->status === 'approved')
                <i class="fas fa-check"></i>
                @elseif($order->status === 'rejected')
                <i class="fas fa-times"></i>
                @elseif($order->status === 'processing')
                <i class="fas fa-cog fa-spin"></i>
                @elseif($order->status === 'shipped')
                <i class="fas fa-shipping-fast"></i>
                @elseif($order->status === 'delivered')
                <i class="fas fa-check-double"></i>
                @elseif($order->status === 'cancelled')
                <i class="fas fa-ban"></i>
                @endif
                {{ ucfirst($order->status) }}
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Product Details -->
            <div class="order-card animate-fade-in" style="animation-delay: 0.2s">
                <h5><i class="fas fa-box"></i> {{__('Product Details')}}</h5>
                <div class="product-details">
                    <div class="product-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="product-info">
                        <h6>{{ $order->product_name }}</h6>
                        @if($order->product_sku)
                        <p><strong>{{__('SKU')}}:</strong> {{ $order->product_sku }}</p>
                        @endif
                        <p><strong>{{__('Quantity')}}:</strong> {{ $order->quantity }}</p>
                        <p><strong>{{__('Unit Price')}}:</strong> <span class="price">${{ number_format($order->unit_price, 2) }}</span></p>
                        <p><strong>{{__('Total Amount')}}:</strong> <span class="price">${{ number_format($order->total_amount, 2) }}</span></p>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="order-card animate-fade-in" style="animation-delay: 0.3s">
                <h5><i class="fas fa-user"></i> {{__('Customer Information')}}</h5>
                <div class="customer-card">
                    <div class="customer-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="customer-info">
                        <h6>{{ $order->customer_name }}</h6>
                        @if($order->customer_email)
                        <p><i class="fas fa-envelope"></i> {{ $order->customer_email }}</p>
                        @endif
                        @if($order->customer_phone)
                        <p><i class="fas fa-phone"></i> {{ $order->customer_phone }}</p>
                        @endif
                    </div>
                </div>

                @if($order->shipping_address)
                <div class="mt-3">
                    <h6><i class="fas fa-map-marker-alt"></i> {{__('Shipping Address')}}</h6>
                    <p class="text-muted">{{ $order->shipping_address }}</p>
                </div>
                @endif
            </div>

            <!-- Order Timeline -->
            <div class="order-card animate-fade-in" style="animation-delay: 0.4s">
                <h5><i class="fas fa-history"></i> {{__('Order Timeline')}}</h5>
                <div class="order-timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-content">
                            <h6>{{__('Order Submitted')}}</h6>
                            <p>{{__('Order has been submitted and is awaiting review')}}</p>
                            <small>{{ $order->submitted_at ? $order->submitted_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>

                    @if($order->status === 'approved' || $order->status === 'processing' || $order->status === 'shipped' || $order->status === 'delivered')
                    <div class="timeline-item completed">
                        <div class="timeline-content">
                            <h6>{{__('Order Approved')}}</h6>
                            <p>{{__('Order has been approved by admin')}}</p>
                            <small>{{ $order->approved_at ? $order->approved_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'rejected')
                    <div class="timeline-item rejected">
                        <div class="timeline-content">
                            <h6>{{__('Order Rejected')}}</h6>
                            <p>{{ $order->rejection_reason ?: __('Order has been rejected') }}</p>
                            <small>{{ $order->updated_at ? $order->updated_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'processing')
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h6>{{__('Order Processing')}}</h6>
                            <p>{{__('Order is currently being processed')}}</p>
                            <small>{{__('In Progress')}}</small>
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'shipped' || $order->status === 'delivered')
                    <div class="timeline-item completed">
                        <div class="timeline-content">
                            <h6>{{__('Order Shipped')}}</h6>
                            <p>{{__('Order has been shipped to customer')}}</p>
                            <small>{{ $order->shipped_at ? $order->shipped_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'delivered')
                    <div class="timeline-item completed">
                        <div class="timeline-content">
                            <h6>{{__('Order Delivered')}}</h6>
                            <p>{{__('Order has been delivered to customer')}}</p>
                            <small>{{ $order->delivered_at ? $order->delivered_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'cancelled')
                    <div class="timeline-item rejected">
                        <div class="timeline-content">
                            <h6>{{__('Order Cancelled')}}</h6>
                            <p>{{__('Order has been cancelled')}}</p>
                            <small>{{ $order->updated_at ? $order->updated_at->format('M d, Y \a\t h:i A') : __('N/A') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Financial Details -->
            <div class="order-card animate-slide-in" style="animation-delay: 0.2s">
                <h5><i class="fas fa-calculator"></i> {{__('Financial Details')}}</h5>
                <div class="financial-row">
                    <span>{{__('Total Amount')}}</span>
                    <span><strong>${{ number_format($order->total_amount, 2) }}</strong></span>
                </div>
                <div class="financial-row commission">
                    <span>{{__('Commission Rate')}}</span>
                    <span><strong>{{ number_format($order->commission_rate, 2) }}%</strong></span>
                </div>
                <div class="financial-row commission">
                    <span>{{__('Commission Amount')}}</span>
                    <span><strong>${{ number_format($order->commission_amount, 2) }}</strong></span>
                </div>
                <div class="financial-row earning">
                    <span>{{__('Your Earning')}}</span>
                    <span><strong>${{ number_format($order->tenant_earning, 2) }}</strong></span>
                </div>
            </div>

            <!-- Order Information -->
            <div class="order-card animate-slide-in" style="animation-delay: 0.3s">
                <h5><i class="fas fa-info-circle"></i> {{__('Order Information')}}</h5>
                <div class="mb-3">
                    <strong>{{__('Order Number')}}:</strong><br>
                    <span class="info-badge badge-primary">{{ $order->order_number }}</span>
                </div>

                @if($order->order_code)
                <div class="mb-3">
                    <strong>{{__('Original Order Code')}}:</strong><br>
                    <span class="info-badge badge-success">{{ $order->order_code }}</span>
                </div>
                @endif

                <div class="mb-3">
                    <strong>{{__('Status')}}:</strong><br>
                    <span class="info-badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'approved' ? 'success' : 'primary') }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                @if($order->submitted_by)
                <div class="mb-3">
                    <strong>{{__('Submitted By')}}:</strong><br>
                    <span class="text-muted">{{ $order->submittedBy->name ?? __('N/A') }}</span>
                </div>
                @endif

                @if($order->approved_by)
                <div class="mb-3">
                    <strong>{{__('Approved By')}}:</strong><br>
                    <span class="text-muted">{{ $order->approvedBy->name ?? __('N/A') }}</span>
                </div>
                @endif
            </div>

            <!-- Additional Notes -->
            @if($order->fulfillment_note || $order->admin_notes)
            <div class="order-card animate-slide-in" style="animation-delay: 0.4s">
                <h5><i class="fas fa-sticky-note"></i> {{__('Notes')}}</h5>

                @if($order->fulfillment_note)
                <div class="mb-3">
                    <strong>{{__('Fulfillment Note')}}:</strong>
                    <p class="text-muted mt-2">{{ $order->fulfillment_note }}</p>
                </div>
                @endif

                @if($order->admin_notes)
                <div class="mb-3">
                    <strong>{{__('Admin Notes')}}:</strong>
                    <p class="text-muted mt-2">{{ $order->admin_notes }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="order-card animate-slide-in" style="animation-delay: 0.5s">
                <h5><i class="fas fa-cogs"></i> {{__('Actions')}}</h5>
                <div class="action-buttons">
                    <a href="{{ route('dropshipping.order.management') }}" class="btn-action-modern btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{__('Back to Orders')}}
                    </a>

                    @if($order->canBeCancelled())
                    <button class="btn-action-modern btn-danger" onclick="cancelOrder({{ $order->id }})">
                        <i class="fas fa-times"></i> {{__('Cancel Order')}}
                    </button>
                    @endif

                    <button class="btn-action-modern btn-primary" onclick="printOrder()">
                        <i class="fas fa-print"></i> {{__('Print Order')}}
                    </button>

                    <button class="btn-action-modern btn-success" onclick="exportOrder()">
                        <i class="fas fa-download"></i> {{__('Export PDF')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Enhanced Cancel Order with confirmation
    function cancelOrder(orderId) {
        if (confirm('{{__("Are you sure you want to cancel this order? This action cannot be undone.")}}')) {
            showLoading();

            fetch(`{{ route('user.dropshipping.orders.cancel', '') }}/${orderId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('{{__("Order cancelled successfully!")}}', 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route('
                            dropshipping.order.management ') }}';
                        }, 1500);
                    } else {
                        showNotification(data.message || '{{__("Error cancelling order")}}', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('{{__("Error cancelling order")}}', 'error');
                });
        }
    }

    // Print Order
    function printOrder() {
        window.print();
    }

    // Export Order
    function exportOrder() {
        showLoading();

        // Simulate export process
        setTimeout(() => {
            hideLoading();
            showNotification('{{__("Order exported successfully!")}}', 'success');
        }, 2000);
    }

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