@extends('core::base.layouts.master')

@section('title')
{{__('Order Management')}}
@endsection

@section('style')
<style>
    /* Modern Page Layout */
    .page-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .page-header-modern h1 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-header-modern p {
        opacity: 0.9;
        margin: 5px 0 0 0;
        font-size: 1.1rem;
    }

    /* Enhanced Stats Cards */
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 30px 25px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .stats-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 20px;
        color: white;
    }

    .stats-card-available .stats-card-icon {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .stats-card-pending .stats-card-icon {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
    }

    .stats-card-earnings .stats-card-icon {
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
    }

    .stats-card-orders .stats-card-icon {
        background: linear-gradient(135deg, #007bff, #6610f2);
    }

    .stats-card h3 {
        font-size: 2.2rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: #2c3e50;
    }

    .stats-card p {
        color: #6c757d;
        margin: 0;
        font-weight: 500;
    }

    .stats-card small {
        color: #95a5a6;
        font-size: 0.85rem;
        display: block;
        margin-top: 5px;
    }

    /* Order Statistics Section */
    .order-stats-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .order-stats-section h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
    }

    .order-stats-section h5 i {
        margin-right: 10px;
        color: #667eea;
    }

    .stat-item {
        text-align: center;
        padding: 20px 10px;
        border-radius: 15px;
        background: #f8f9fb;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .stat-item:hover {
        transform: translateY(-3px);
        border-color: #667eea;
        background: white;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
    }

    .stat-item h4 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }

    .stat-item span {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Enhanced Orders Table */
    .orders-section {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .orders-section .card-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        padding: 25px 30px;
        border-radius: 0;
    }

    .orders-section .card-header h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .orders-section .card-header h5 i {
        margin-right: 10px;
        color: #667eea;
    }

    .orders-section .card-body {
        padding: 0;
    }

    .table-modern {
        margin: 0;
        font-size: 0.95rem;
    }

    .table-modern thead th {
        background: #f8f9fa;
        border: none;
        padding: 20px 15px;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .table-modern tbody td {
        padding: 20px 15px;
        border-top: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .table-modern tbody tr {
        transition: all 0.2s ease;
    }

    .table-modern tbody tr:hover {
        background: #f8f9fb;
        transform: scale(1.01);
    }

    /* Enhanced Status Badges */
    .order-status-badge {
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        display: inline-flex;
        align-items: center;
    }

    .order-status-badge i {
        margin-right: 5px;
    }

    .status-pending {
        background: linear-gradient(135deg, #ffc107, #ffb000);
        color: #fff;
        box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
    }

    .status-approved {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: #fff;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }

    .status-processing {
        background: linear-gradient(135deg, #17a2b8, #007bff);
        color: #fff;
        box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
    }

    .status-shipped {
        background: linear-gradient(135deg, #007bff, #6610f2);
        color: #fff;
        box-shadow: 0 3px 10px rgba(0, 123, 255, 0.3);
    }

    .status-delivered {
        background: linear-gradient(135deg, #28a745, #34ce57);
        color: #fff;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }

    .status-cancelled {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #fff;
        box-shadow: 0 3px 10px rgba(108, 117, 125, 0.3);
    }

    .status-rejected {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: #fff;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
    }

    /* Enhanced Buttons */
    .btn-modern {
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .btn-modern.btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-modern.btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-action {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 3px;
        transition: all 0.3s ease;
        border: none;
        font-size: 0.85rem;
    }

    .btn-action:hover {
        transform: scale(1.1);
    }

    .btn-action.btn-outline-primary {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    .btn-action.btn-outline-primary:hover {
        background: #667eea;
        color: white;
    }

    .btn-action.btn-outline-danger {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .btn-action.btn-outline-danger:hover {
        background: #dc3545;
        color: white;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #dee2e6;
    }

    .empty-state h5 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: #495057;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 25px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header-modern {
            padding: 20px;
            text-align: center;
        }

        .page-header-modern h1 {
            font-size: 1.5rem;
        }

        .stats-card {
            margin-bottom: 20px;
        }

        .stat-item {
            margin-bottom: 15px;
        }

        .table-responsive {
            border-radius: 0;
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

    .animate-fade-in {
        animation: fadeInUp 0.6s ease forwards;
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Modern Page Header -->
    <div class="page-header-modern animate-fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-truck"></i> {{__('Order Management')}}</h1>
                <p>{{__('Manage your dropshipping orders and track earnings')}}</p>
            </div>
            <div class="d-flex mt-3 mt-md-0">
                <a href="{{ route('dropshipping.order.create') }}" class="btn btn-modern btn-primary me-3">
                    <i class="fas fa-plus"></i> {{__('New Order')}}
                </a>
                <a href="{{ route('dropshipping.withdrawals') }}" class="btn btn-modern btn-success">
                    <i class="fas fa-money-bill-wave"></i> {{__('Withdrawals')}}
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-available animate-fade-in" style="animation-delay: 0.1s">
                <div class="stats-card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>${{ number_format($balance->available_balance, 2) }}</h3>
                <p>{{__('Available Balance')}}</p>
                <small>{{__('Ready for withdrawal')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-pending animate-fade-in" style="animation-delay: 0.2s">
                <div class="stats-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>${{ number_format($balance->pending_balance, 2) }}</h3>
                <p>{{__('Pending Balance')}}</p>
                <small>{{__('From pending orders')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-earnings animate-fade-in" style="animation-delay: 0.3s">
                <div class="stats-card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>${{ number_format($stats['total_earnings'], 2) }}</h3>
                <p>{{__('Total Earnings')}}</p>
                <small>{{__('All-time earnings')}}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card stats-card-orders animate-fade-in" style="animation-delay: 0.4s">
                <div class="stats-card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>{{ $stats['total_orders'] }}</h3>
                <p>{{__('Total Orders')}}</p>
                <small>{{__('Orders submitted')}}</small>
            </div>
        </div>
    </div>

    <!-- Order Statistics -->
    <div class="order-stats-section animate-fade-in" style="animation-delay: 0.5s">
        <h5><i class="fas fa-chart-pie"></i> {{__('Order Statistics')}}</h5>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-warning">{{ $stats['pending_orders'] }}</h4>
                    <span>{{__('Pending')}}</span>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-success">{{ $stats['approved_orders'] }}</h4>
                    <span>{{__('Approved')}}</span>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-danger">{{ $stats['rejected_orders'] }}</h4>
                    <span>{{__('Rejected')}}</span>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-primary">{{ $balance->approved_orders }}</h4>
                    <span>{{__('Shipped')}}</span>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-info">{{ $balance->total_orders }}</h4>
                    <span>{{__('Delivered')}}</span>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <h4 class="text-primary">${{ number_format($balance->available_balance + $balance->pending_balance, 2) }}</h4>
                    <span>{{__('Total Balance')}}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Orders Table -->
    <div class="orders-section animate-fade-in" style="animation-delay: 0.6s">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list-alt"></i> {{__('Recent Orders')}}</h5>
                <a href="{{ route('dropshipping.order.create') }}" class="btn btn-modern btn-primary btn-sm">
                    <i class="fas fa-plus"></i> {{__('New Order')}}
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> {{__('Order #')}}</th>
                            <th><i class="fas fa-box"></i> {{__('Product')}}</th>
                            <th><i class="fas fa-user"></i> {{__('Customer')}}</th>
                            <th><i class="fas fa-sort-numeric-up"></i> {{__('Quantity')}}</th>
                            <th><i class="fas fa-dollar-sign"></i> {{__('Total Amount')}}</th>
                            <th><i class="fas fa-money-bill-wave"></i> {{__('Your Earning')}}</th>
                            <th><i class="fas fa-info-circle"></i> {{__('Status')}}</th>
                            <th><i class="fas fa-calendar"></i> {{__('Submitted')}}</th>
                            <th><i class="fas fa-cogs"></i> {{__('Actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td>
                                <strong class="text-primary">{{ $order->order_number }}</strong>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $order->product_name }}</strong>
                                    @if($order->product_sku)
                                    <br><small class="text-muted">SKU: {{ $order->product_sku }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $order->customer_name }}</strong>
                                    <br><small class="text-muted">{{ $order->customer_email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light">{{ $order->quantity }}</span>
                            </td>
                            <td>
                                <strong class="text-dark">${{ number_format($order->total_amount, 2) }}</strong>
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($order->tenant_earning, 2) }}</strong>
                            </td>
                            <td>
                                <span class="order-status-badge status-{{ $order->status }}">
                                    @if($order->status === 'pending')
                                    <i class="fas fa-clock"></i>
                                    @elseif($order->status === 'approved')
                                    <i class="fas fa-check"></i>
                                    @elseif($order->status === 'rejected')
                                    <i class="fas fa-times"></i>
                                    @elseif($order->status === 'shipped')
                                    <i class="fas fa-shipping-fast"></i>
                                    @elseif($order->status === 'delivered')
                                    <i class="fas fa-check-double"></i>
                                    @endif
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $order->submitted_at ? $order->submitted_at->format('M d, Y') : '' }}</span>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('dropshipping.order.show', $order->id) }}"
                                        class="btn btn-action btn-outline-primary" title="{{__('View Details')}}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($order->canBeCancelled())
                                    <button class="btn btn-action btn-outline-danger"
                                        onclick="cancelOrder({{ $order->id }})"
                                        title="{{__('Cancel Order')}}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->links() }}
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h5>{{__('No orders found')}}</h5>
                <p>{{__('You haven\'t submitted any orders yet. Start by creating your first order!')}}</p>
                <a href="{{ route('dropshipping.order.create') }}" class="btn btn-modern btn-primary">
                    <i class="fas fa-plus"></i> {{__('Create First Order')}}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    function cancelOrder(orderId) {
        if (confirm('{{__("Are you sure you want to cancel this order?")}}')) {
            fetch(`{{ route('user.dropshipping.orders.cancel', '') }}/${orderId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '{{__("Error cancelling order")}}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{__("Error cancelling order")}}');
                });
        }
    }

    // Add smooth animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.animate-fade-in');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
@endsection