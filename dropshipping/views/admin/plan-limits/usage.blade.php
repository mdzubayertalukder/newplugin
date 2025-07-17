@extends('core::base.layouts.master')

@section('title')
{{ translate('Usage Statistics') }} - {{ $package->name }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-chart-bar-graph"></i> {{ translate('Usage Statistics') }} - {{ $package->name }}</h4>
            <div class="d-flex align-items-center gap-10 flex-wrap">
                <a href="{{ route('admin.dropshipping.plan-limits.index') }}" class="btn long">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Plan Limits') }}
                </a>
                <a href="{{ route('admin.dropshipping.plan-limits.edit', $package->id) }}" class="btn long">
                    <i class="icofont-edit"></i> {{ translate('Edit Limits') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Package Information') }}</h4>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>{{ translate('Package Name') }}:</strong></td>
                        <td>{{ $package->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Package ID') }}:</strong></td>
                        <td>{{ $package->id }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Package Type') }}:</strong></td>
                        <td>{{ ucfirst($package->type ?? 'Unknown') }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Active Tenants') }}:</strong></td>
                        <td>{{ count($usageStats) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Current Limits') }}</h4>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>{{ translate('Monthly Import') }}:</strong></td>
                        <td>{{ $limits->monthly_import_limit == -1 ? translate('Unlimited') : number_format($limits->monthly_import_limit) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Total Import') }}:</strong></td>
                        <td>{{ $limits->total_import_limit == -1 ? translate('Unlimited') : number_format($limits->total_import_limit) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Bulk Import') }}:</strong></td>
                        <td>{{ $limits->bulk_import_limit == -1 ? translate('Unlimited') : number_format($limits->bulk_import_limit) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Monthly Research') }}:</strong></td>
                        <td>{{ $limits->monthly_research_limit == -1 ? translate('Unlimited') : number_format($limits->monthly_research_limit) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Total Research') }}:</strong></td>
                        <td>{{ $limits->total_research_limit == -1 ? translate('Unlimited') : number_format($limits->total_research_limit) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Auto Sync') }}:</strong></td>
                        <td>
                            @if($limits->auto_sync_enabled)
                                <span class="badge badge-success">{{ translate('Enabled') }}</span>
                            @else
                                <span class="badge badge-secondary">{{ translate('Disabled') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Tenant Usage Statistics') }}</h4>
            </div>
            <div class="card-body">
                @if(count($usageStats) > 0)
                    <div class="table-responsive">
                        <table class="text-nowrap dh-table">
                            <thead>
                                <tr>
                                    <th>{{ translate('Store Name') }}</th>
                                    <th>{{ translate('Tenant ID') }}</th>
                                    <th>{{ translate('Monthly Imports') }}</th>
                                    <th>{{ translate('Total Imports') }}</th>
                                    <th>{{ translate('Monthly Research') }}</th>
                                    <th>{{ translate('Total Research') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($usageStats as $stat)
                                <tr>
                                    <td>
                                        <strong>{{ $stat['store_name'] ?? 'Unknown Store' }}</strong>
                                    </td>
                                    <td>
                                        <small style="color: #666;">{{ $stat['tenant_id'] }}</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $monthlyImportUsed = $stat['stats']['monthly_import_used'] ?? 0;
                                                $monthlyImportLimit = $limits->monthly_import_limit;
                                                $monthlyImportPercent = $monthlyImportLimit == -1 ? 0 : min(100, ($monthlyImportUsed / $monthlyImportLimit) * 100);
                                                $monthlyImportColor = $monthlyImportPercent > 90 ? 'danger' : ($monthlyImportPercent > 70 ? 'warning' : 'success');
                                            @endphp
                                            <div class="progress-bar bg-{{ $monthlyImportColor }}" style="width: {{ $monthlyImportPercent }}%">
                                                {{ $monthlyImportUsed }}{{ $monthlyImportLimit != -1 ? '/' . $monthlyImportLimit : '' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $totalImportUsed = $stat['stats']['total_import_used'] ?? 0;
                                                $totalImportLimit = $limits->total_import_limit;
                                                $totalImportPercent = $totalImportLimit == -1 ? 0 : min(100, ($totalImportUsed / $totalImportLimit) * 100);
                                                $totalImportColor = $totalImportPercent > 90 ? 'danger' : ($totalImportPercent > 70 ? 'warning' : 'success');
                                            @endphp
                                            <div class="progress-bar bg-{{ $totalImportColor }}" style="width: {{ $totalImportPercent }}%">
                                                {{ $totalImportUsed }}{{ $totalImportLimit != -1 ? '/' . $totalImportLimit : '' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $monthlyResearchUsed = $stat['stats']['monthly_research_used'] ?? 0;
                                                $monthlyResearchLimit = $limits->monthly_research_limit;
                                                $monthlyResearchPercent = $monthlyResearchLimit == -1 ? 0 : min(100, ($monthlyResearchUsed / $monthlyResearchLimit) * 100);
                                                $monthlyResearchColor = $monthlyResearchPercent > 90 ? 'danger' : ($monthlyResearchPercent > 70 ? 'warning' : 'success');
                                            @endphp
                                            <div class="progress-bar bg-{{ $monthlyResearchColor }}" style="width: {{ $monthlyResearchPercent }}%">
                                                {{ $monthlyResearchUsed }}{{ $monthlyResearchLimit != -1 ? '/' . $monthlyResearchLimit : '' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $totalResearchUsed = $stat['stats']['total_research_used'] ?? 0;
                                                $totalResearchLimit = $limits->total_research_limit;
                                                $totalResearchPercent = $totalResearchLimit == -1 ? 0 : min(100, ($totalResearchUsed / $totalResearchLimit) * 100);
                                                $totalResearchColor = $totalResearchPercent > 90 ? 'danger' : ($totalResearchPercent > 70 ? 'warning' : 'success');
                                            @endphp
                                            <div class="progress-bar bg-{{ $totalResearchColor }}" style="width: {{ $totalResearchPercent }}%">
                                                {{ $totalResearchUsed }}{{ $totalResearchLimit != -1 ? '/' . $totalResearchLimit : '' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $canImport = $stat['stats']['can_import'] ?? true;
                                            $canResearch = $stat['stats']['can_research'] ?? true;
                                        @endphp
                                        @if($canImport && $canResearch)
                                            <span class="badge badge-success">{{ translate('Active') }}</span>
                                        @elseif(!$canImport && !$canResearch)
                                            <span class="badge badge-danger">{{ translate('Limits Reached') }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ translate('Partial Limits') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center" style="padding: 2rem 0;">
                        <i class="icofont-chart-bar-graph" style="font-size: 3rem; color: #999; margin-bottom: 1rem; display: block;"></i>
                        <h5 style="color: #999;">{{ translate('No Active Tenants') }}</h5>
                        <p style="color: #999;">{{ translate('No tenants are currently using this package') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection 