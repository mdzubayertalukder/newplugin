@extends('core::base.layouts.master')

@section('title')
{{ translate('Create Plan Limits') }} - {{ $package->name }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-plus"></i> {{ translate('Create Plan Limits') }} - {{ $package->name }}</h4>
            <div class="d-flex align-items-center gap-10 flex-wrap">
                <a href="{{ route('admin.dropshipping.plan-limits.index') }}" class="btn long">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Plan Limits') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-30">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Create Limits for') }} {{ $package->name }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.dropshipping.plan-limits.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="monthly_import_limit">{{ translate('Monthly Import Limit') }}</label>
                                <input type="number" class="form-control" id="monthly_import_limit" name="monthly_import_limit" 
                                       value="{{ old('monthly_import_limit', 10) }}" 
                                       min="-1" required>
                                <small class="form-text text-muted">{{ translate('Enter -1 for unlimited') }}</small>
                                @error('monthly_import_limit')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="total_import_limit">{{ translate('Total Import Limit') }}</label>
                                <input type="number" class="form-control" id="total_import_limit" name="total_import_limit" 
                                       value="{{ old('total_import_limit', -1) }}" 
                                       min="-1" required>
                                <small class="form-text text-muted">{{ translate('Enter -1 for unlimited') }}</small>
                                @error('total_import_limit')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="bulk_import_limit">{{ translate('Bulk Import Limit') }}</label>
                                <input type="number" class="form-control" id="bulk_import_limit" name="bulk_import_limit" 
                                       value="{{ old('bulk_import_limit', 5) }}" 
                                       min="-1" required>
                                <small class="form-text text-muted">{{ translate('Max products per bulk import. Enter -1 for unlimited') }}</small>
                                @error('bulk_import_limit')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="monthly_research_limit">{{ translate('Monthly Research Limit') }}</label>
                                <input type="number" class="form-control" id="monthly_research_limit" name="monthly_research_limit" 
                                       value="{{ old('monthly_research_limit', 10) }}" 
                                       min="-1" required>
                                <small class="form-text text-muted">{{ translate('Enter -1 for unlimited') }}</small>
                                @error('monthly_research_limit')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="total_research_limit">{{ translate('Total Research Limit') }}</label>
                                <input type="number" class="form-control" id="total_research_limit" name="total_research_limit" 
                                       value="{{ old('total_research_limit', -1) }}" 
                                       min="-1" required>
                                <small class="form-text text-muted">{{ translate('Enter -1 for unlimited') }}</small>
                                @error('total_research_limit')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="auto_sync_enabled">{{ translate('Auto Sync') }}</label>
                                <select class="form-control" id="auto_sync_enabled" name="auto_sync_enabled">
                                    <option value="1" {{ old('auto_sync_enabled', 0) == 1 ? 'selected' : '' }}>
                                        {{ translate('Enabled') }}
                                    </option>
                                    <option value="0" {{ old('auto_sync_enabled', 0) == 0 ? 'selected' : '' }}>
                                        {{ translate('Disabled') }}
                                    </option>
                                </select>
                                @error('auto_sync_enabled')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="pricing_markup_min">{{ translate('Minimum Markup %') }}</label>
                                <input type="number" class="form-control" id="pricing_markup_min" name="pricing_markup_min" 
                                       value="{{ old('pricing_markup_min') }}" 
                                       min="0" step="0.01">
                                <small class="form-text text-muted">{{ translate('Optional minimum markup percentage') }}</small>
                                @error('pricing_markup_min')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="pricing_markup_max">{{ translate('Maximum Markup %') }}</label>
                                <input type="number" class="form-control" id="pricing_markup_max" name="pricing_markup_max" 
                                       value="{{ old('pricing_markup_max') }}" 
                                       min="0" step="0.01">
                                <small class="form-text text-muted">{{ translate('Optional maximum markup percentage') }}</small>
                                @error('pricing_markup_max')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Additional Settings') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="auto_update_prices" name="auto_update_prices" value="1"
                                                           {{ old('auto_update_prices', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_update_prices">
                                                        {{ translate('Auto Update Prices') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="auto_update_stock" name="auto_update_stock" value="1"
                                                           {{ old('auto_update_stock', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_update_stock">
                                                        {{ translate('Auto Update Stock') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="import_reviews" name="import_reviews" value="1"
                                                           {{ old('import_reviews', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="import_reviews">
                                                        {{ translate('Import Reviews') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="icofont-save"></i> {{ translate('Create Plan Limits') }}
                        </button>
                        <a href="{{ route('admin.dropshipping.plan-limits.index') }}" class="btn btn-secondary">
                            <i class="icofont-close"></i> {{ translate('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
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
                        <td><strong>{{ translate('Price') }}:</strong></td>
                        <td>{{ $package->price ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ translate('Status') }}:</strong></td>
                        <td>
                            @if($package->status == 1)
                                <span class="badge badge-success">{{ translate('Active') }}</span>
                            @else
                                <span class="badge badge-danger">{{ translate('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white border-bottom2">
                <h4>{{ translate('Suggested Limits') }}</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">{{ translate('Based on package type, here are suggested limits:') }}</p>
                
                @if($package->type === 'free')
                    <div class="alert alert-info">
                        <strong>{{ translate('Free Package') }}</strong><br>
                        {{ translate('Monthly Import: 5') }}<br>
                        {{ translate('Monthly Research: 5') }}<br>
                        {{ translate('Bulk Import: 2') }}<br>
                        {{ translate('Auto Sync: Disabled') }}
                    </div>
                @elseif($package->type === 'paid')
                    <div class="alert alert-success">
                        <strong>{{ translate('Paid Package') }}</strong><br>
                        {{ translate('Monthly Import: 100') }}<br>
                        {{ translate('Monthly Research: 100') }}<br>
                        {{ translate('Bulk Import: 20') }}<br>
                        {{ translate('Auto Sync: Enabled') }}
                    </div>
                @else
                    <div class="alert alert-warning">
                        <strong>{{ translate('Basic Package') }}</strong><br>
                        {{ translate('Monthly Import: 10') }}<br>
                        {{ translate('Monthly Research: 10') }}<br>
                        {{ translate('Bulk Import: 5') }}<br>
                        {{ translate('Auto Sync: Disabled') }}
                    </div>
                @endif
                
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="applySuggestedLimits()">
                    {{ translate('Apply Suggested Limits') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function applySuggestedLimits() {
    const packageType = '{{ $package->type }}';
    
    if (packageType === 'free') {
        document.getElementById('monthly_import_limit').value = 5;
        document.getElementById('monthly_research_limit').value = 5;
        document.getElementById('bulk_import_limit').value = 2;
        document.getElementById('auto_sync_enabled').value = 0;
    } else if (packageType === 'paid') {
        document.getElementById('monthly_import_limit').value = 100;
        document.getElementById('monthly_research_limit').value = 100;
        document.getElementById('bulk_import_limit').value = 20;
        document.getElementById('auto_sync_enabled').value = 1;
        document.getElementById('auto_update_prices').checked = true;
        document.getElementById('auto_update_stock').checked = true;
    } else {
        document.getElementById('monthly_import_limit').value = 10;
        document.getElementById('monthly_research_limit').value = 10;
        document.getElementById('bulk_import_limit').value = 5;
        document.getElementById('auto_sync_enabled').value = 0;
    }
    
    // Keep total limits as unlimited
    document.getElementById('total_import_limit').value = -1;
    document.getElementById('total_research_limit').value = -1;
}
</script>
@endsection 