{{-- Dropshipping Plugin Dashboard Widget --}}
@php
// Enhanced dropshipping check for dashboard widget
$showDropshippingWidget = false;

if (function_exists('isTenant') && isTenant()) {
try {
// Try standard plugin check first
$showDropshippingWidget = isActivePluging('dropshipping', true);

// Fallback checks if standard check fails
if (!$showDropshippingWidget) {
// Direct database check
$dropshippingPlugin = DB::table('tl_plugins')
->where('location', 'dropshipping')
->where('is_activated', config('settings.general_status.active'))
->first();
$showDropshippingWidget = ($dropshippingPlugin !== null);
}

// Final fallback - check if plugin exists
if (!$showDropshippingWidget && file_exists(base_path('plugins/dropshipping/plugin.json'))) {
$showDropshippingWidget = true;
}
} catch (\Exception $e) {
// Emergency fallback
$showDropshippingWidget = file_exists(base_path('plugins/dropshipping/plugin.json'));
}
}
@endphp

@if($showDropshippingWidget)

@php
$tenantId = tenant('id');
$dropshippingStats = [
'available_products' => 0,
'imported_products' => 0,
'this_month_imports' => 0,
'pending_imports' => 0
];

try {
// Get available products from main database
$dropshippingStats['available_products'] = DB::connection('mysql')->table('dropshipping_products')
->join('dropshipping_woocommerce_configs', 'dropshipping_woocommerce_configs.id', '=', 'dropshipping_products.woocommerce_config_id')
->where('dropshipping_woocommerce_configs.is_active', 1)
->where('dropshipping_products.status', 'publish')
->count();

// Get tenant's imported products
$dropshippingStats['imported_products'] = DB::table('dropshipping_product_import_history')
->where('tenant_id', $tenantId)
->where('import_status', 'completed')
->count();

// Get this month's imports
$dropshippingStats['this_month_imports'] = DB::table('dropshipping_product_import_history')
->where('tenant_id', $tenantId)
->where('import_status', 'completed')
->whereYear('created_at', now()->year)
->whereMonth('created_at', now()->month)
->count();

// Get pending imports
$dropshippingStats['pending_imports'] = DB::table('dropshipping_product_import_history')
->where('tenant_id', $tenantId)
->where('import_status', 'pending')
->count();

} catch (\Exception $e) {
// Silent fail if database not accessible
}
@endphp

<!-- Available Products Widget -->
<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        {{ translate('Available Products') }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        {{ number_format($dropshippingStats['available_products']) }}
                    </div>
                </div>
                <div class="col-auto">
                    <i class="icofont-package fa-2x text-gray-300"></i>
                </div>
            </div>
            <div class="row no-gutters align-items-center mt-2">
                <div class="col">
                    <a href="{{ route('dropshipping.products.all') }}" class="btn btn-primary btn-sm">
                        {{ translate('Browse Products') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Imported Products Widget -->
<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        {{ translate('My Products') }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        {{ number_format($dropshippingStats['imported_products']) }}
                    </div>
                </div>
                <div class="col-auto">
                    <i class="icofont-check-alt fa-2x text-gray-300"></i>
                </div>
            </div>
            <div class="row no-gutters align-items-center mt-2">
                <div class="col">
                    <a href="{{ route('dropshipping.my.products') }}" class="btn btn-success btn-sm">
                        {{ translate('View My Products') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- This Month's Imports Widget -->
<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        {{ translate('This Month') }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        {{ number_format($dropshippingStats['this_month_imports']) }}
                    </div>
                    <small class="text-muted">imports completed</small>
                </div>
                <div class="col-auto">
                    <i class="icofont-download fa-2x text-gray-300"></i>
                </div>
            </div>
            <div class="row no-gutters align-items-center mt-2">
                <div class="col">
                    <a href="{{ route('dropshipping.import.history') }}" class="btn btn-info btn-sm">
                        {{ translate('View History') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dropshipping Dashboard Link -->
<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                        {{ translate('Dropshipping') }}
                    </div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                        {{ translate('Manage Products') }}
                    </div>
                    <small class="text-muted">Import & sync products</small>
                </div>
                <div class="col-auto">
                    <i class="icofont-truck fa-2x text-gray-300"></i>
                </div>
            </div>
            <div class="row no-gutters align-items-center mt-2">
                <div class="col">
                    <a href="{{ route('user.dropshipping.dashboard') }}" class="btn btn-warning btn-sm">
                        {{ translate('Open Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endif

@php
use Plugin\Dropshipping\Models\DropshippingOrder;
use Plugin\Dropshipping\Models\WithdrawalRequest;
use Plugin\Dropshipping\Models\TenantBalance;
use Carbon\Carbon;

// Check if user has admin access and is not on tenant
if (!isTenant() && auth()->user() && auth()->user()->hasRole('Super Admin')) {
try {
// Get dropshipping order statistics
$totalDropshippingOrders = DropshippingOrder::count();
$pendingOrders = DropshippingOrder::where('status', 'pending')->count();
$approvedOrders = DropshippingOrder::where('status', 'approved')->count();
$todayOrders = DropshippingOrder::whereDate('created_at', Carbon::today())->count();

// Get recent orders
$recentDropshippingOrders = DropshippingOrder::with(['submittedBy'])
->orderBy('created_at', 'desc')
->limit(5)
->get();

// Get withdrawal statistics
$pendingWithdrawals = WithdrawalRequest::where('status', 'pending')->count();
$totalEarnings = TenantBalance::sum('total_earnings');
$totalAvailableBalance = TenantBalance::sum('available_balance');
} catch (\Exception $e) {
// Set default values if database queries fail
$totalDropshippingOrders = $pendingOrders = $approvedOrders = $todayOrders = 0;
$recentDropshippingOrders = collect([]);
$pendingWithdrawals = 0;
$totalEarnings = $totalAvailableBalance = 0;

// Log the error for debugging
\Log::error('Dropshipping dashboard error: ' . $e->getMessage());
}
} else {
$totalDropshippingOrders = $pendingOrders = $approvedOrders = $todayOrders = 0;
$recentDropshippingOrders = collect([]);
$pendingWithdrawals = 0;
$totalEarnings = $totalAvailableBalance = 0;
}
@endphp

@if (!isTenant() && auth()->user() && auth()->user()->hasRole('Super Admin'))
{{-- Dropshipping Overview Section --}}
<div class="row mb-30">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white border-bottom2">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="icofont-truck" style="color: #6f42c1;"></i>
                        {{ translate('Dropshipping Overview') }}
                    </h4>
                    <a href="{{ route('admin.dropshipping.dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="icofont-eye"></i> {{ translate('View Full Dashboard') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                {{-- Quick Statistics --}}
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="icon-box me-3" style="background: rgba(111,66,193,0.1); padding: 12px; border-radius: 50%;">
                                <i class="icofont-shopping-cart" style="font-size: 20px; color: #6f42c1;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ number_format($totalDropshippingOrders) }}</h5>
                                <small class="text-muted">{{ translate('Total Orders') }}</small>
                                @if($todayOrders > 0)
                                <div class="badge badge-success">+{{ $todayOrders }} {{ translate('today') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="icon-box me-3" style="background: rgba(253,126,20,0.1); padding: 12px; border-radius: 50%;">
                                <i class="icofont-clock-time" style="font-size: 20px; color: #fd7e14;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ number_format($pendingOrders) }}</h5>
                                <small class="text-muted">{{ translate('Pending Orders') }}</small>
                                @if($pendingOrders > 0)
                                <div class="badge badge-warning">{{ translate('Need Approval') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="icon-box me-3" style="background: rgba(23,162,184,0.1); padding: 12px; border-radius: 50%;">
                                <i class="icofont-money-bag" style="font-size: 20px; color: #17a2b8;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">${{ number_format($totalEarnings, 2) }}</h5>
                                <small class="text-muted">{{ translate('Total Earnings') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="icon-box me-3" style="background: rgba(220,53,69,0.1); padding: 12px; border-radius: 50%;">
                                <i class="icofont-bank-transfer-alt" style="font-size: 20px; color: #dc3545;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ number_format($pendingWithdrawals) }}</h5>
                                <small class="text-muted">{{ translate('Pending Withdrawals') }}</small>
                                @if($pendingWithdrawals > 0)
                                <div class="badge badge-danger">{{ translate('Action Required') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Orders --}}
                @if($recentDropshippingOrders->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-3">
                            <i class="icofont-clock-time"></i> {{ translate('Recent Dropshipping Orders') }}
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ translate('Order') }}</th>
                                        <th>{{ translate('Tenant') }}</th>
                                        <th>{{ translate('Customer') }}</th>
                                        <th>{{ translate('Amount') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Date') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDropshippingOrders as $order)
                                    <tr>
                                        <td>
                                            <strong class="text-primary">{{ $order->order_number }}</strong>
                                            @if($order->created_at >= Carbon::now()->subHours(24))
                                            <span class="badge badge-success badge-sm">{{ translate('New') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $order->tenant_id }}</span>
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
                                            <div>
                                                <strong>${{ number_format($order->total_amount, 2) }}</strong>
                                                <br><small class="text-success">{{ translate('Earn') }}: ${{ number_format($order->tenant_earning, 2) }}</small>
                                            </div>
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
                                            <span class="badge {{ $statusClass }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $order->created_at->format('M d, Y') }}
                                                <br><small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($order->status === 'pending')
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success btn-sm"
                                                    onclick="approveOrder('{{ $order->id }}')"
                                                    title="{{ translate('Approve Order') }}">
                                                    <i class="icofont-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="rejectOrder('{{ $order->id }}')"
                                                    title="{{ translate('Reject Order') }}">
                                                    <i class="icofont-close"></i>
                                                </button>
                                            </div>
                                            @else
                                            <a href="{{ route('admin.dropshipping.orders.show', $order->id) }}"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="icofont-eye"></i> {{ translate('View') }}
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($totalDropshippingOrders > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.dropshipping.orders.index') }}" class="btn btn-outline-primary">
                                <i class="icofont-list"></i> {{ translate('View All Orders') }} ({{ $totalDropshippingOrders }})
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="icofont-shopping-cart" style="font-size: 48px; color: #dee2e6;"></i>
                    </div>
                    <h6 class="text-muted">{{ translate('No dropshipping orders yet') }}</h6>
                    <p class="text-muted mb-0">{{ translate('Orders will appear here when tenants submit them') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions for Pending Items --}}
@if($pendingOrders > 0 || $pendingWithdrawals > 0)
<div class="row mb-30">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center">
            <i class="icofont-warning-alt me-2" style="font-size: 20px;"></i>
            <div class="flex-grow-1">
                <strong>{{ translate('Action Required') }}</strong> -
                @if($pendingOrders > 0)
                {{ $pendingOrders }} {{ translate('pending order(s)') }}
                @endif
                @if($pendingOrders > 0 && $pendingWithdrawals > 0) {{ translate('and') }} @endif
                @if($pendingWithdrawals > 0)
                {{ $pendingWithdrawals }} {{ translate('pending withdrawal(s)') }}
                @endif
                {{ translate('need your approval') }}.
            </div>
            <div>
                @if($pendingOrders > 0)
                <a href="{{ route('admin.dropshipping.orders.index', ['status' => 'pending']) }}"
                    class="btn btn-warning btn-sm me-2">
                    <i class="icofont-check"></i> {{ translate('Review Orders') }}
                </a>
                @endif
                @if($pendingWithdrawals > 0)
                <a href="{{ route('admin.dropshipping.withdrawals.index', ['status' => 'pending']) }}"
                    class="btn btn-info btn-sm">
                    <i class="icofont-money"></i> {{ translate('Review Withdrawals') }}
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<script>
    function approveOrder(orderId) {
        if (confirm('{{ translate("Are you sure you want to approve this order?") }}')) {
            fetch(`/admin/dropshipping/orders/${orderId}/approve`, {
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
                })
                .catch(error => {
                    alert('{{ translate("Error approving order") }}');
                });
        }
    }

    function rejectOrder(orderId) {
        const reason = prompt('{{ translate("Please provide a reason for rejection:") }}');
        if (reason && reason.trim()) {
            fetch(`/admin/dropshipping/orders/${orderId}/reject`, {
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
                })
                .catch(error => {
                    alert('{{ translate("Error rejecting order") }}');
                });
        }
    }
</script>
@endif