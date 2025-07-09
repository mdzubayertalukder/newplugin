@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Products') }}
@endsection

@section('main_content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 font-weight-bold text-dark mb-1">Dropshipping Products</h2>
                    <p class="text-muted">Browse and import products from our partner stores</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dropshipping.import.history') }}" class="btn btn-outline-primary">
                        <i class="fas fa-history"></i> Import History
                    </a>
                </div>
            </div>

            <!-- Store Filter -->
            @if($stores && count($stores) > 0)
            <div class="card mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="d-flex align-items-center gap-3">
                        <label for="store_id" class="form-label mb-0 text-muted">Filter by Store:</label>
                        <select name="store_id" id="store_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $selectedStore == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                            @endforeach
                        </select>
                        @if($selectedStore)
                        <a href="{{ route('dropshipping.products.all') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filter
                        </a>
                        @endif
                    </form>
                </div>
            </div>
            @endif

            <!-- Products Grid -->
            @if($products && count($products) > 0)
            <div class="row">
                @foreach($products as $product)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 product-card">
                        <!-- Product Image -->
                        <div class="position-relative overflow-hidden" style="height: 200px;">
                            @if(isset($product->image) && $product->image)
                            <img src="{{ $product->image }}"
                                class="card-img-top h-100 w-100"
                                style="object-fit: cover;"
                                alt="{{ $product->name }}"
                                onerror="this.src='{{ asset('images/placeholder-product.jpg') }}'">
                            @else
                            <div class="card-img-top h-100 w-100 d-flex align-items-center justify-content-center bg-light">
                                <i class="fas fa-image text-muted fa-3x"></i>
                            </div>
                            @endif

                            <!-- Store Badge -->
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-primary">{{ $product->store_name ?? 'Store' }}</span>
                            </div>

                            <!-- Stock Status -->
                            @if($product->stock_quantity > 0)
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-success">In Stock</span>
                            </div>
                            @else
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-warning">Out of Stock</span>
                            </div>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="card-body d-flex flex-column">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-2 text-dark" title="{{ $product->name }}">
                                    {{ Str::limit($product->name, 60) }}
                                </h6>

                                @if($product->short_description)
                                <p class="card-text text-muted small mb-2">
                                    {{ Str::limit(strip_tags($product->short_description), 80) }}
                                </p>
                                @endif

                                <!-- Price -->
                                <div class="d-flex align-items-center mb-2">
                                    @if($product->sale_price && $product->sale_price != $product->regular_price)
                                    <span class="h6 text-danger mb-0 me-2">${{ number_format(floatval($product->sale_price), 2) }}</span>
                                    <span class="text-muted text-decoration-line-through small">${{ number_format(floatval($product->regular_price), 2) }}</span>
                                    @else
                                    <span class="h6 text-dark mb-0">${{ number_format(floatval($product->regular_price), 2) }}</span>
                                    @endif
                                </div>

                                <!-- Product Details -->
                                <div class="small text-muted">
                                    <div class="d-flex justify-content-between">
                                        <span>SKU: {{ $product->sku ?: 'N/A' }}</span>
                                        <span>Stock: {{ $product->stock_quantity ?: '0' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Import Button -->
                            <div class="mt-3">
                                <div class="d-grid gap-2">
                                    <button type="button"
                                        class="btn btn-primary btn-sm import-btn"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        onclick="importProduct(this)"
                                        {{ $product->stock_quantity <= 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-download"></i> Import Product
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="showProductDetails({{ $product->id }})">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $products->appends(request()->query())->links() }}
            </div>
            @else
            <!-- No Products -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-box-open fa-4x text-muted"></i>
                </div>
                <h4 class="text-muted">No Products Available</h4>
                <p class="text-muted">There are no products available for import at the moment.</p>
                @if(!$stores || count($stores) == 0)
                <p class="text-muted small">Please ask your administrator to set up WooCommerce stores.</p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // DIRECT SCRIPT - Define importProduct function immediately
    console.log('Loading importProduct function...');

    window.importProduct = function(button) {
        console.log('importProduct called with button:', button);

        const productId = button.getAttribute('data-product-id');
        const productName = button.getAttribute('data-product-name');

        // Show confirmation
        if (!confirm('Are you sure you want to import "' + productName + '" to your store?')) {
            return;
        }

        // Disable button and show loading
        button.disabled = true;
        button.classList.add('importing');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

        // Make AJAX request
        fetch('/dropshipping/import/' + productId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    markup_percentage: 20
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Show success
                    button.innerHTML = '<i class="fas fa-check"></i> Imported!';
                    button.classList.remove('importing');
                    button.classList.add('import-success');

                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Product imported successfully!');
                    } else {
                        alert(data.message || 'Product imported successfully!');
                    }

                    // Disable permanently after 2 seconds
                    setTimeout(function() {
                        button.innerHTML = '<i class="fas fa-check"></i> Already Imported';
                        button.disabled = true;
                    }, 2000);
                } else {
                    // Show error
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    button.classList.remove('importing');

                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Import failed. Please try again.');
                    } else {
                        alert(data.message || 'Import failed. Please try again.');
                    }
                }
            })
            .catch(function(error) {
                console.error('Import error:', error);
                button.innerHTML = originalContent;
                button.disabled = false;
                button.classList.remove('importing');

                if (typeof toastr !== 'undefined') {
                    toastr.error('An error occurred. Please try again.');
                } else {
                    alert('An error occurred. Please try again.');
                }
            });
    };

    window.showProductDetails = function(productId) {
        console.log('showProductDetails called with productId:', productId);

        const modal = document.getElementById('productDetailsModal');
        const modalContent = document.getElementById('productDetailsContent');

        if (!modal || !modalContent) {
            alert('Modal not found');
            return;
        }

        // Show loading
        modalContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading product details...</p></div>';

        // Show modal
        if (typeof bootstrap !== 'undefined') {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        } else if (typeof $ !== 'undefined') {
            $(modal).modal('show');
        } else {
            modal.style.display = 'block';
        }
    };

    console.log('importProduct function loaded successfully:', typeof window.importProduct);
</script>

@endsection

@push('styles')
<style>
    .product-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    }

    .import-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .badge {
        font-size: 0.7rem;
    }

    .importing {
        pointer-events: none;
        opacity: 0.7;
    }

    .import-success {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
</style>
@endpush