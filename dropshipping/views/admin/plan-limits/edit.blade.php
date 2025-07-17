@extends('core::base.layouts.master')

@section('title')
{{ translate('Edit Plan Limits') }} - {{ $package->name }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-12">
        <div class="align-items-center border-bottom2 d-flex flex-wrap gap-10 justify-content-between mb-4 pb-3">
            <h4><i class="icofont-edit"></i> {{ translate('Edit Plan Limits') }} - {{ $package->name }}</h4>
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
                <h4>{{ translate('Edit Limits for') }} {{ $package->name }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.dropshipping.plan-limits.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="monthly_import_limit">{{ translate('Monthly Import Limit') }}</label>
                                <input type="number" class="form-control" id="monthly_import_limit" name="monthly_import_limit" 
                                       value="{{ old('monthly_import_limit', $limits->monthly_import_limit) }}" 
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
                                       value="{{ old('total_import_limit', $limits->total_import_limit) }}" 
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
                                       value="{{ old('bulk_import_limit', $limits->bulk_import_limit) }}" 
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
                                       value="{{ old('monthly_research_limit', $limits->monthly_research_limit) }}" 
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
                                       value="{{ old('total_research_limit', $limits->total_research_limit) }}" 
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
                                    <option value="1" {{ old('auto_sync_enabled', $limits->auto_sync_enabled) == 1 ? 'selected' : '' }}>
                                        {{ translate('Enabled') }}
                                    </option>
                                    <option value="0" {{ old('auto_sync_enabled', $limits->auto_sync_enabled) == 0 ? 'selected' : '' }}>
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
                                       value="{{ old('pricing_markup_min', $limits->pricing_markup_min) }}" 
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
                                       value="{{ old('pricing_markup_max', $limits->pricing_markup_max) }}" 
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
                                                           {{ old('auto_update_prices', $limits->settings['auto_update_prices'] ?? false) ? 'checked' : '' }}>
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
                                                           {{ old('auto_update_stock', $limits->settings['auto_update_stock'] ?? false) ? 'checked' : '' }}>
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
                                                           {{ old('import_reviews', $limits->settings['import_reviews'] ?? false) ? 'checked' : '' }}>
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
                            <i class="icofont-save"></i> {{ translate('Update Plan Limits') }}
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
                <h4>{{ translate('Quick Actions') }}</h4>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.dropshipping.plan-limits.usage', $package->id) }}" class="btn btn-info btn-block mb-2">
                    <i class="icofont-chart-bar-graph"></i> {{ translate('View Usage Statistics') }}
                </a>
                <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete()">
                    <i class="icofont-trash"></i> {{ translate('Delete Limits') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('{{ translate("Are you sure you want to delete the limits for this package? This action cannot be undone.") }}')) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.dropshipping.plan-limits.destroy", $package->id) }}';
        
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