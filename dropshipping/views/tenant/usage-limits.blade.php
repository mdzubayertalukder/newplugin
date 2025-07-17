{{-- Usage Limits Display for Tenants --}}
@php
    use Plugin\Dropshipping\Services\LimitService;
    
    $tenantId = tenant('id');
    $usageDisplay = LimitService::getUsageDisplay($tenantId);
    $packageInfo = LimitService::getTenantPackageInfo($tenantId);
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    Usage Limits
                    @if($packageInfo)
                        <span class="badge badge-info ms-2">{{ $packageInfo->package_name }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if(isset($usageDisplay['error']))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ $usageDisplay['error'] }}
                    </div>
                @else
                    <div class="row">
                        <!-- Product Import Limits -->
                        <div class="col-md-6">
                            <h6><i class="fas fa-download me-2"></i>Product Import</h6>
                            
                            <!-- Monthly Import Limit -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Monthly Imports</span>
                                    <span>{{ $usageDisplay['imports']['monthly']['display'] }}</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar 
                                        @if($usageDisplay['imports']['monthly']['percentage'] >= 90) bg-danger
                                        @elseif($usageDisplay['imports']['monthly']['percentage'] >= 70) bg-warning
                                        @else bg-success
                                        @endif" 
                                        role="progressbar" 
                                        style="width: {{ $usageDisplay['imports']['monthly']['percentage'] }}%"
                                        aria-valuenow="{{ $usageDisplay['imports']['monthly']['percentage'] }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        {{ $usageDisplay['imports']['monthly']['percentage'] }}%
                                    </div>
                                </div>
                            </div>

                            <!-- Total Import Limit -->
                            @if($usageDisplay['imports']['total']['limit'] !== -1)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Total Imports</span>
                                        <span>{{ $usageDisplay['imports']['total']['display'] }}</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar 
                                            @if($usageDisplay['imports']['total']['percentage'] >= 90) bg-danger
                                            @elseif($usageDisplay['imports']['total']['percentage'] >= 70) bg-warning
                                            @else bg-success
                                            @endif" 
                                            role="progressbar" 
                                            style="width: {{ $usageDisplay['imports']['total']['percentage'] }}%"
                                            aria-valuenow="{{ $usageDisplay['imports']['total']['percentage'] }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            {{ $usageDisplay['imports']['total']['percentage'] }}%
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Bulk Import Limit -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Bulk Import Limit</span>
                                    <span>{{ $usageDisplay['bulk_import_limit'] === -1 ? 'Unlimited' : $usageDisplay['bulk_import_limit'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Research Limits -->
                        <div class="col-md-6">
                            <h6><i class="fas fa-search me-2"></i>Product Research</h6>
                            
                            <!-- Monthly Research Limit -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Monthly Research</span>
                                    <span>{{ $usageDisplay['research']['monthly']['display'] }}</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar 
                                        @if($usageDisplay['research']['monthly']['percentage'] >= 90) bg-danger
                                        @elseif($usageDisplay['research']['monthly']['percentage'] >= 70) bg-warning
                                        @else bg-success
                                        @endif" 
                                        role="progressbar" 
                                        style="width: {{ $usageDisplay['research']['monthly']['percentage'] }}%"
                                        aria-valuenow="{{ $usageDisplay['research']['monthly']['percentage'] }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        {{ $usageDisplay['research']['monthly']['percentage'] }}%
                                    </div>
                                </div>
                            </div>

                            <!-- Total Research Limit -->
                            @if($usageDisplay['research']['total']['limit'] !== -1)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Total Research</span>
                                        <span>{{ $usageDisplay['research']['total']['display'] }}</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar 
                                            @if($usageDisplay['research']['total']['percentage'] >= 90) bg-danger
                                            @elseif($usageDisplay['research']['total']['percentage'] >= 70) bg-warning
                                            @else bg-success
                                            @endif" 
                                            role="progressbar" 
                                            style="width: {{ $usageDisplay['research']['total']['percentage'] }}%"
                                            aria-valuenow="{{ $usageDisplay['research']['total']['percentage'] }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            {{ $usageDisplay['research']['total']['percentage'] }}%
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Upgrade Plan Button -->
                    @if($usageDisplay['imports']['monthly']['percentage'] >= 80 || $usageDisplay['research']['monthly']['percentage'] >= 80)
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Approaching Limits</h6>
                            <p class="mb-2">You're approaching your monthly limits. Consider upgrading your plan to get more imports and research capabilities.</p>
                            <a href="#" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-up me-1"></i>Upgrade Plan
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.progress {
    height: 8px;
}

.progress-bar {
    transition: width 0.3s ease;
}

.badge {
    font-size: 0.75em;
}
</style> 