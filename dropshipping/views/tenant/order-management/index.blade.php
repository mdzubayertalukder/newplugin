@extends('core::base.layouts.master')

@section('title')
{{ __('Order Management') }}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --bg-color: #f8f9fa;
        --text-color: #343a40;
        --primary-color: #4e73df;
        --primary-hover-color: #2e59d9;
        --secondary-color: #858796;
        --card-bg-color: #ffffff;
        --card-border-color: #e3e6f0;
        --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        --border-radius: 0.35rem;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .c-main {
        background-color: var(--bg-color);
    }

    .card {
        background-color: var(--card-bg-color);
        border: 1px solid var(--card-border-color);
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid var(--card-border-color);
        padding: 0.75rem 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .card-body {
        padding: 1.25rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #5a5c69;
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        transition: background-color 0.15s ease-in-out;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover-color);
        border-color: var(--primary-hover-color);
    }

    .stat-card {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        border-left: 5px solid;
    }

    .stat-card-icon {
        font-size: 2.5rem;
        margin-right: 1rem;
        color: var(--secondary-color);
    }

    .stat-card-info h5 {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .stat-card-info p {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0;
    }

    .table-responsive {
        box-shadow: var(--card-shadow);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .table {
        background-color: var(--card-bg-color);
    }

    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid var(--primary-color);
        color: #5a5c69;
        font-weight: 700;
        padding: 1rem;
    }

    .table tbody tr:hover {
        background-color: #f2f2f2;
    }

    .badge-status {
        padding: 0.5em 0.75em;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8em;
    }

    .badge-pending {
        background-color: #f6c23e;
        color: white;
    }

    .badge-approved {
        background-color: #1cc88a;
        color: white;
    }

    .badge-rejected {
        background-color: #e74a3b;
        color: white;
    }

    .badge-shipped {
        background-color: #36b9cc;
        color: white;
    }

    .badge-delivered {
        background-color: #4e73df;
        color: white;
    }

    .badge-cancelled {
        background-color: #858796;
        color: white;
    }

    .badge-processing {
        background-color: #5a5c69;
        color: white;
    }

    .filter-form .form-control,
    .filter-form .btn {
        height: calc(1.5em + 1rem + 2px);
        padding: 0.5rem 1rem;
        font-size: .875rem;
        border-radius: var(--border-radius);
    }

    .action-buttons a {
        margin-right: 0.5rem;
    }

    /* Stat Cards v3 */
    .stat-card-v3 {
        border-left: 0.25rem solid;
        transition: all 0.2s ease-in-out;
    }

    .stat-card-v3:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15) !important;
    }

    .border-left-primary {
        border-left-color: #4e73df;
    }

    .border-left-warning {
        border-left-color: #f6c23e;
    }

    .border-left-success {
        border-left-color: #1cc88a;
    }

    .border-left-info {
        border-left-color: #36b9cc;
    }

    .text-primary {
        color: #4e73df !important;
    }

    .text-warning {
        color: #f6c23e !important;
    }

    .text-success {
        color: #1cc88a !important;
    }

    .text-info {
        color: #36b9cc !important;
    }

    .stat-card-v3 .card-body {
        padding: 1.25rem;
    }

    .stat-card-v3 .text-xs {
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .stat-card-v3 .h5 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0;
        color: #5a5c69;
    }

    .stat-card-v3 .fa-2x {
        font-size: 2rem;
        color: #dddfeb;
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="page-title">{{ __('Dropshipping Order Management') }}</h1>
        <div>
            <a href="{{ route('dropshipping.order.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> {{ __('New Order') }}
            </a>
            <a href="{{ route('dropshipping.withdrawals') }}" class="btn btn-success shadow-sm">
                <i class="fas fa-money-bill-wave fa-sm text-white-50"></i> {{ __('Withdrawals') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-v3 border-left-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Available Balance') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($balance->available_balance, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-v3 border-left-warning shadow-sm h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('Pending Balance') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($balance->pending_balance, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-v3 border-left-success shadow-sm h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Total Earnings') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['total_earnings'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-v3 border-left-info shadow-sm h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ __('Total Orders') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_orders'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Orders Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Orders') }}</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Actions:</div>
                    <a class="dropdown-item" href="#">Export Orders</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('dropshipping.order.management') }}" class="mb-4 filter-form">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by Order#, Product, Customer" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Order #') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                            <th>{{ __('Your Earning') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Submitted') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>
                                {{ $order->product_name }}
                                <small class="d-block text-muted">SKU: {{ $order->product_sku }}</small>
                            </td>
                            <td>
                                {{ $order->customer_name }}
                                <small class="d-block text-muted">{{ $order->customer_email }}</small>
                            </td>
                            <td>{{ $order->quantity }}</td>
                            <td>${{ number_format($order->total_amount, 2) }}</td>
                            <td>${{ number_format($order->tenant_earning, 2) }}</td>
                            <td>
                                <span class="badge-status badge-{{$order->status}}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>{{ $order->submitted_at ? $order->submitted_at->format('d M Y') : 'N/A' }}</td>
                            <td class="action-buttons">
                                <a href="{{ route('dropshipping.order.show', $order->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($order->canBeCancelled())
                                <form action="{{ route('user.dropshipping.orders.cancel', $order->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                <p class="my-4">{{__('No orders found.')}}</p>
                                <a href="{{ route('dropshipping.order.create') }}" class="btn btn-primary mb-4">{{__('Create your first order')}}</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end">
                {{ $orders->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Any specific JS for this page can go here
</script>
@endsection