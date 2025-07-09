@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Orders') }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card mb-30">
            <div class="card-body border-bottom2 mb-20">
                <div class="d-sm-flex justify-content-between align-items-center">
                    <h4 class="font-20">{{ translate('Dropshipping Orders Management') }}</h4>
                    <div>
                        <a href="{{ route('admin.dropshipping.orders.export') }}" class="btn btn-success">
                            <i class="icofont-download"></i> {{ translate('Export') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics Cards --}}
            <div class="row mb-4 px-3">
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['total_orders'] }}</h5>
                            <small>{{ translate('Total') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['pending_orders'] }}</h5>
                            <small>{{ translate('Pending') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['approved_orders'] }}</h5>
                            <small>{{ translate('Approved') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['rejected_orders'] }}</h5>
                            <small>{{ translate('Rejected') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['shipped_orders'] }}</h5>
                            <small>{{ translate('Shipped') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h5>{{ $stats['delivered_orders'] }}</h5>
                            <small>{{ translate('Delivered') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="px-3 mb-3">
                <form method="GET" action="{{ route('admin.dropshipping.orders.index') }}" class="row">
                    <div class="col-md-3">
                        <select name="status" class="theme-input-style">
                            <option value="">{{ translate('All Statuses') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ translate('Approved') }}</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ translate('Rejected') }}</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>{{ translate('Processing') }}</option>
                            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>{{ translate('Shipped') }}</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>{{ translate('Delivered') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="tenant_id" class="theme-input-style">
                            <option value="">{{ translate('All Tenants') }}</option>
                            @foreach($tenants as $tenant)
                            <option value="{{ $tenant }}" {{ request('tenant_id') == $tenant ? 'selected' : '' }}>
                                {{ translate('Tenant') }} {{ $tenant }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="search" class="theme-input-style"
                            placeholder="{{ translate('Search by order, product, customer...') }}"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn long">{{ translate('Filter') }}</button>
                    </div>
                </form>
            </div>

            {{-- Orders Table --}}
            <div class="table-responsive">
                <table class="hoverable text-nowrap">
                    <thead>
                        <tr>
                            <th>{{ translate('Order') }}</th>
                            <th>{{ translate('Tenant') }}</th>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Commission') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($orders->count() > 0)
                        @foreach($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_number }}</strong>
                                @if($order->created_at >= \Carbon\Carbon::now()->subHours(24))
                                <span class="badge badge-success">{{ translate('New') }}</span>
                                @endif
                            </td>
                            <td>{{ $order->tenant_id }}</td>
                            <td>
                                {{ $order->product_name }}
                                <br><small class="text-muted">{{ translate('Qty') }}: {{ $order->quantity }}</small>
                            </td>
                            <td>
                                <strong>{{ $order->customer_name }}</strong>
                                @if($order->customer_email)
                                <br><small class="text-muted">{{ $order->customer_email }}</small>
                                @endif
                            </td>
                            <td>${{ number_format($order->total_amount, 2) }}</td>
                            <td>
                                <strong class="text-success">${{ number_format($order->tenant_earning, 2) }}</strong>
                                <br><small class="text-muted">{{ $order->commission_rate }}%</small>
                            </td>
                            <td>
                                @php
                                $statusClasses = [
                                'pending' => 'badge-warning',
                                'approved' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'processing' => 'badge-info',
                                'shipped' => 'badge-primary',
                                'delivered' => 'badge-success',
                                'cancelled' => 'badge-secondary'
                                ];
                                $statusClass = $statusClasses[$order->status] ?? 'badge-secondary';
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>
                                {{ $order->created_at->format('M d, Y') }}
                                <br><small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.dropshipping.orders.show', $order->id) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="icofont-eye"></i>
                                    </a>
                                    @if($order->status === 'pending')
                                    <button class="btn btn-success btn-sm"
                                        onclick="approveOrder({{ $order->id }})"
                                        title="{{ translate('Approve') }}">
                                        <i class="icofont-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                        onclick="rejectOrder({{ $order->id }})"
                                        title="{{ translate('Reject') }}">
                                        <i class="icofont-close"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="icofont-shopping-cart" style="font-size: 48px; color: #ddd;"></i>
                                <p class="mt-2 text-muted">{{ translate('No orders found') }}</p>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    function approveOrder(orderId) {
        if (confirm('{{ translate("Are you sure you want to approve this order?") }}')) {
            fetch('/admin/dropshipping/orders/' + orderId + '/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        notes: ''
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '{{ translate("Error approving order") }}');
                    }
                });
        }
    }

    function rejectOrder(orderId) {
        const reason = prompt('{{ translate("Please provide a reason for rejection:") }}');
        if (reason && reason.trim()) {
            fetch('/admin/dropshipping/orders/' + orderId + '/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        reason: reason.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '{{ translate("Error rejecting order") }}');
                    }
                });
        }
    }
</script>
@endsection