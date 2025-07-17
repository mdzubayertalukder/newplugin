@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Plan Limits') }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-settings"></i> {{ translate('Dropshipping Plan Limits') }}</h4>
            <div class="d-flex align-items-center gap-10 flex-wrap">
                <a href="{{ route('admin.dropshipping.dashboard') }}" class="btn long">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Dashboard') }}
                </a>
                <a href="{{ route('admin.dropshipping.plan-limits.create-defaults') }}" class="btn long" 
                   onclick="return confirm('{{ translate('This will create default limits for all packages without limits. Continue?') }}')">
                    <i class="icofont-plus"></i> {{ translate('Create Default Limits') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Package Import Limits') }}</h4>
            </div>
            <div class="card-body">
                @if(isset($packages) && $packages->count() > 0)
                <div class="table-responsive">
                    <table class="text-nowrap dh-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Package Name') }}</th>
                                <th>{{ translate('Bulk Import Limit') }}</th>
                                <th>{{ translate('Monthly Limit') }}</th>
                                <th>{{ translate('Total Import Limit') }}</th>
                                <th>{{ translate('Auto Sync') }}</th>
                                <th>{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packages as $package)
                            <tr>
                                <td>
                                    <strong>{{ $package->name ?? 'Unknown Package' }}</strong>
                                    <br><small style="color: #666;">Package ID: {{ $package->id }}</small>
                                </td>
                                <td>
                                    <span style="color: #007bff; font-weight: bold;">
                                        @if($package->dropshippingLimits)
                                            {{ $package->dropshippingLimits->bulk_import_limit == -1 ? translate('Unlimited') : number_format($package->dropshippingLimits->bulk_import_limit) }}
                                        @else
                                            <span style="color: #999;">{{ translate('Not Set') }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #17a2b8; font-weight: bold;">
                                        @if($package->dropshippingLimits)
                                            {{ $package->dropshippingLimits->monthly_import_limit == -1 ? translate('Unlimited') : number_format($package->dropshippingLimits->monthly_import_limit) }}
                                        @else
                                            <span style="color: #999;">{{ translate('Not Set') }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #ffc107; font-weight: bold;">
                                        @if($package->dropshippingLimits)
                                            {{ $package->dropshippingLimits->total_import_limit == -1 ? translate('Unlimited') : number_format($package->dropshippingLimits->total_import_limit) }}
                                        @else
                                            <span style="color: #999;">{{ translate('Not Set') }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($package->dropshippingLimits)
                                        @if($package->dropshippingLimits->auto_sync_enabled)
                                            <span style="color: #28a745; font-weight: bold;">{{ translate('Enabled') }}</span>
                                        @else
                                            <span style="color: #dc3545; font-weight: bold;">{{ translate('Disabled') }}</span>
                                        @endif
                                    @else
                                        <span style="color: #999;">{{ translate('Not Set') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown-button">
                                        <a href="#" class="d-flex align-items-center" data-toggle="dropdown">
                                            <div class="menu-icon style--two mr-0">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if($package->dropshippingLimits)
                                                <a href="{{ route('admin.dropshipping.plan-limits.edit', $package->id) }}" class="dropdown-item">
                                                    <i class="icofont-edit"></i> {{ translate('Edit') }}
                                                </a>
                                                <a href="{{ route('admin.dropshipping.plan-limits.usage', $package->id) }}" class="dropdown-item">
                                                    <i class="icofont-chart-bar-graph"></i> {{ translate('View Usage') }}
                                                </a>
                                                <a href="#" class="dropdown-item" style="color: #dc3545;" onclick="confirmDelete({{ $package->id }})">
                                                    <i class="icofont-trash"></i> {{ translate('Delete') }}
                                                </a>
                                            @else
                                                <a href="{{ route('admin.dropshipping.plan-limits.create', $package->id) }}" class="dropdown-item">
                                                    <i class="icofont-plus"></i> {{ translate('Create Limits') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center" style="padding: 2rem 0;">
                    <i class="icofont-settings" style="font-size: 3rem; color: #999; margin-bottom: 1rem; display: block;"></i>
                    <h5 style="color: #999;">{{ translate('No plan limits configured') }}</h5>
                    <p style="color: #999;">{{ translate('Set import limits for different subscription packages') }}</p>
                    <a href="{{ route('admin.dropshipping.plan-limits.create-defaults') }}" class="btn long"
                       onclick="return confirm('{{ translate('This will create default limits for all packages. Continue?') }}')">
                        <i class="icofont-plus"></i> {{ translate('Create Default Limits') }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(packageId) {
    if (confirm('{{ translate("Are you sure you want to delete the limits for this package? This action cannot be undone.") }}')) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/dropshipping/plan-limits/' + packageId;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add method override
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

@endsection