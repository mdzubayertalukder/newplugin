@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Orders') }}
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
        padding: 25px 20px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .stats-card-total::before {
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .stats-card-pending::before {
        background: linear-gradient(90deg, #ffc107, #fd7e14);
    }

    .stats-card-approved::before {
        background: linear-gradient(90deg, #28a745, #20c997);
    }

    .stats-card-rejected::before {
        background: linear-gradient(90deg, #dc3545, #c82333);
    }

    .stats-card-shipped::before {
        background: linear-gradient(90deg, #007bff, #6610f2);
    }

    .stats-card-delivered::before {
        background: linear-gradient(90deg, #28a745, #34ce57);
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
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Enhanced Filters Section */
    .filters-section {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .filters-section h5 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .filters-section h5 i {
        margin-right: 10px;
        color: #667eea;
    }

    .form-select-modern,
    .form-input-modern {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .form-select-modern:focus,
    .form-input-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        background: white;
    }

    .btn-filter {
        border-radius: 12px;
        padding: 12px 25px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }

    /* Enhanced Orders Table */
    .orders-section {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .orders-section .section-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        padding: 25px 30px;
    }

    .orders-section .section-header h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .orders-section .section-header h5 i {
        margin-right: 10px;
        color: #667eea;
    }

    .orders-section .section-body {
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

    /* Enhanced Action Buttons */
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

    .btn-action.btn-outline-success {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .btn-action.btn-outline-success:hover {
        background: #28a745;
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

    /* Enhanced Export Button */
    .btn-export {
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-export i {
        margin-right: 8px;
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

    /* New Badge */
    .badge-new {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 12px;
        margin-left: 8px;
        font-weight: 600;
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Modern Page Header -->
    <div class="page-header-modern animate-fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="icofont-truck"></i> {{ translate('Dropshipping Orders Management') }}</h1>
                <p>{{ translate('Monitor and manage all dropshipping orders from tenants') }}</p>
            </div>
            <div class="d-flex mt-3 mt-md-0">
                <a href="{{ route('admin.dropshipping.orders.export') }}" class="btn-export">
                    <i class="icofont-download"></i> {{ translate('Export Orders') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-total animate-fade-in" style="animation-delay: 0.1s">
                <h3>{{ $stats['total_orders'] }}</h3>
                <p>{{ translate('Total Orders') }}</p>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-pending animate-fade-in" style="animation-delay: 0.2s">
                <h3>{{ $stats['pending_orders'] }}</h3>
                <p>{{ translate('Pending') }}</p>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-approved animate-fade-in" style="animation-delay: 0.3s">
                <h3>{{ $stats['approved_orders'] }}</h3>
                <p>{{ translate('Approved') }}</p>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-rejected animate-fade-in" style="animation-delay: 0.4s">
                <h3>{{ $stats['rejected_orders'] }}</h3>
                <p>{{ translate('Rejected') }}</p>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-shipped animate-fade-in" style="animation-delay: 0.5s">
                <h3>{{ $stats['shipped_orders'] }}</h3>
                <p>{{ translate('Shipped') }}</p>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-card-delivered animate-fade-in" style="animation-delay: 0.6s">
                <h3>{{ $stats['delivered_orders'] }}</h3>
                <p>{{ translate('Delivered') }}</p>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters Section -->
    <div class="filters-section animate-fade-in" style="animation-delay: 0.7s">
        <h5><i class="icofont-filter"></i> {{ translate('Filter Orders') }}</h5>
        <form method="GET" action="{{ route('admin.dropshipping.orders.index') }}" class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">{{ translate('Status') }}</label>
                <select name="status" class="form-select form-select-modern">
                    <option value="">{{ translate('All Statuses') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ translate('Approved') }}</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ translate('Rejected') }}</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>{{ translate('Processing') }}</option>
                    <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>{{ translate('Shipped') }}</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>{{ translate('Delivered') }}</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">{{ translate('Tenant') }}</label>
                <select name="tenant_id" class="form-select form-select-modern">
                    <option value="">{{ translate('All Tenants') }}</option>
                    @foreach($tenants as $tenant)
                    <option value="{{ $tenant }}" {{ request('tenant_id') == $tenant ? 'selected' : '' }}>
                        {{ translate('Tenant') }} {{ $tenant }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-8 mb-3">
                <label class="form-label">{{ translate('Search') }}</label>
                <input type="text" name="search" class="form-control form-input-modern"
                    placeholder="{{ translate('Search by order, product, customer...') }}"
                    value="{{ request('search') }}">
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-filter w-100">
                    <i class="icofont-search-1"></i> {{ translate('Filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Enhanced Orders Table -->
    <div class="orders-section animate-fade-in" style="animation-delay: 0.8s">
        <div class="section-header">
            <h5><i class="icofont-list"></i> {{ translate('All Orders') }}</h5>
        </div>
        <div class="section-body">
            @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th><i class="icofont-hashtag"></i> {{ translate('Order') }}</th>
                            <th><i class="icofont-building"></i> {{ translate('Tenant') }}</th>
                            <th><i class="icofont-box"></i> {{ translate('Product') }}</th>
                            <th><i class="icofont-user"></i> {{ translate('Customer') }}</th>
                            <th><i class="icofont-dollar"></i> {{ translate('Amount') }}</th>
                            <th><i class="icofont-money"></i> {{ translate('Commission') }}</th>
                            <th><i class="icofont-info-circle"></i> {{ translate('Status') }}</th>
                            <th><i class="icofont-calendar"></i> {{ translate('Date') }}</th>
                            <th><i class="icofont-settings"></i> {{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <strong class="text-primary">{{ $order->order_number }}</strong>
                                    @if($order->created_at >= \Carbon\Carbon::now()->subHours(24))
                                    <span class="badge-new">{{ translate('New') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-white">{{ $order->tenant_id }}</span>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $order->product_name }}</strong>
                                    <br><small class="text-muted">{{ translate('Qty') }}: {{ $order->quantity }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $order->customer_name }}</strong>
                                    @if($order->customer_email)
                                    <br><small class="text-muted">{{ $order->customer_email }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong class="text-dark">${{ number_format($order->total_amount, 2) }}</strong>
                            </td>
                            <td>
                                <div>
                                    <strong class="text-success">${{ number_format($order->tenant_earning, 2) }}</strong>
                                    <br><small class="text-muted">{{ $order->commission_rate }}% {{ translate('commission') }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="order-status-badge status-{{ $order->status }}">
                                    @if($order->status === 'pending')
                                    <i class="icofont-clock-time"></i>
                                    @elseif($order->status === 'approved')
                                    <i class="icofont-check-circled"></i>
                                    @elseif($order->status === 'rejected')
                                    <i class="icofont-close-circled"></i>
                                    @elseif($order->status === 'processing')
                                    <i class="icofont-gear"></i>
                                    @elseif($order->status === 'shipped')
                                    <i class="icofont-truck"></i>
                                    @elseif($order->status === 'delivered')
                                    <i class="icofont-check-alt"></i>
                                    @endif
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold">{{ $order->created_at->format('M d, Y') }}</span>
                                    <br><small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('admin.dropshipping.orders.show', $order->id) }}"
                                        class="btn btn-action btn-outline-primary" title="{{ translate('View Details') }}">
                                        <i class="icofont-eye"></i>
                                    </a>
                                    @if($order->status === 'pending')
                                    <button class="btn btn-action btn-outline-success"
                                        onclick="approveOrder({{ $order->id }})"
                                        title="{{ translate('Approve') }}">
                                        <i class="icofont-check"></i>
                                    </button>
                                    <button class="btn btn-action btn-outline-danger"
                                        onclick="rejectOrder({{ $order->id }})"
                                        title="{{ translate('Reject') }}">
                                        <i class="icofont-close"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Enhanced Pagination -->
            @if($orders->hasPages())
            <div class="d-flex justify-content-center mt-4 p-3">
                {{ $orders->withQueryString()->links() }}
            </div>
            @endif
            @else
            <div class="empty-state">
                <i class="icofont-shopping-cart"></i>
                <h5>{{ translate('No orders found') }}</h5>
                <p>{{ translate('No dropshipping orders match your current filter criteria.') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Enhanced Modal for Order Actions -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Approve Order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Admin Notes (Optional)') }}</label>
                        <textarea class="form-control" id="approveNotes" rows="3"
                            placeholder="{{ translate('Add any notes for this approval...') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="confirmApprove()">{{ translate('Approve Order') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Reject Order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Rejection Reason') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" rows="3" required
                            placeholder="{{ translate('Please provide a reason for rejecting this order...') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">{{ translate('Reject Order') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentOrderId = null;

    function approveOrder(orderId) {
        currentOrderId = orderId;
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }

    function rejectOrder(orderId) {
        currentOrderId = orderId;
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }

    function confirmApprove() {
        if (!currentOrderId) return;

        const notes = document.getElementById('approveNotes').value;

        fetch('/admin/dropshipping/orders/' + currentOrderId + '/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    admin_notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ translate("Error approving order") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ translate("Error approving order") }}');
            });
    }

    function confirmReject() {
        if (!currentOrderId) return;

        const reason = document.getElementById('rejectReason').value.trim();
        if (!reason) {
            alert('{{ translate("Please provide a rejection reason") }}');
            return;
        }

        fetch('/admin/dropshipping/orders/' + currentOrderId + '/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    rejection_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ translate("Error rejecting order") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ translate("Error rejecting order") }}');
            });
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