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