<div class="product-details">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            @if(!empty($images))
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($images as $index => $image)
                    @if($image)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ $image }}" class="d-block w-100 rounded" alt="Product Image" style="height: 300px; object-fit: cover;">
                    </div>
                    @endif
                    @endforeach
                </div>
                @if(count($images) > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                @endif
            </div>
            @else
            <div class="text-center bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                <i class="fas fa-image text-muted fa-4x"></i>
            </div>
            @endif
        </div>

        <!-- Product Information -->
        <div class="col-md-6">
            <div class="product-info">
                <!-- Store Badge -->
                <div class="mb-2">
                    <span class="badge bg-primary">{{ $product->store_name }}</span>
                    @if($product->stock_quantity > 0)
                    <span class="badge bg-success">In Stock</span>
                    @else
                    <span class="badge bg-warning">Out of Stock</span>
                    @endif
                </div>

                <!-- Product Name -->
                <h4 class="fw-bold text-dark mb-3">{{ $product->name }}</h4>

                <!-- Pricing -->
                <div class="mb-3">
                    @if($product->sale_price && $product->sale_price != $product->regular_price)
                    <div class="d-flex align-items-center gap-2">
                        <span class="h4 text-danger mb-0">${{ number_format(floatval($product->sale_price), 2) }}</span>
                        <span class="text-muted text-decoration-line-through">${{ number_format(floatval($product->regular_price), 2) }}</span>
                        <span class="badge bg-danger">
                            {{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF
                        </span>
                    </div>
                    @else
                    <span class="h4 text-primary">${{ number_format(floatval($product->regular_price), 2) }}</span>
                    @endif
                </div>

                <!-- Product Details Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-muted">SKU:</small>
                        <div class="fw-semibold">{{ $product->sku ?: 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Stock:</small>
                        <div class="fw-semibold">{{ $product->stock_quantity ?: '0' }} units</div>
                    </div>
                    @if($product->weight)
                    <div class="col-6">
                        <small class="text-muted">Weight:</small>
                        <div class="fw-semibold">{{ $product->weight }}</div>
                    </div>
                    @endif
                    @if(!empty($categories))
                    <div class="col-12">
                        <small class="text-muted">Categories:</small>
                        <div class="fw-semibold">{{ implode(', ', $categories) }}</div>
                    </div>
                    @endif
                </div>

                <!-- Short Description -->
                @if($product->short_description)
                <div class="mb-3">
                    <h6>Overview</h6>
                    <p class="text-muted">{{ strip_tags($product->short_description) }}</p>
                </div>
                @endif

                <!-- Import Action -->
                <div class="mt-4">
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label small">Markup Percentage:</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="markupPercentage" value="20" min="0" max="500">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-4 d-flex align-items-end">
                            <button type="button"
                                class="btn btn-primary btn-sm w-100"
                                onclick="importProductFromModal({{ $product->id }})"
                                {{ $product->stock_quantity <= 0 ? 'disabled' : '' }}>
                                <i class="fas fa-download"></i> Import
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">
                        Your price: $<span id="calculatedPrice">{{ number_format(floatval($product->regular_price) * 1.2, 2) }}</span>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    @if($product->description)
    <div class="mt-4">
        <h6>Description</h6>
        <div class="border-top pt-3">
            {!! $product->description !!}
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const markupInput = document.getElementById('markupPercentage');
        const calculatedPriceSpan = document.getElementById('calculatedPrice');
        const basePrice = {
            {
                floatval($product - > regular_price)
            }
        };

        if (markupInput && calculatedPriceSpan) {
            markupInput.addEventListener('input', function() {
                const markup = parseFloat(this.value) || 0;
                const finalPrice = basePrice * (1 + markup / 100);
                calculatedPriceSpan.textContent = finalPrice.toFixed(2);
            });
        }
    });

    function importProductFromModal(productId) {
        const markup = document.getElementById('markupPercentage').value || 20;
        const button = event.target;

        // Disable button and show loading
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

        fetch('/dropshipping/import/' + productId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    markup_percentage: parseFloat(markup)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> Imported!';
                    button.classList.add('btn-success');

                    // Show success toast
                    if (typeof showToast === 'function') {
                        showToast('success', data.message || 'Product imported successfully!');
                    }

                    // Close modal after short delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('productDetailsModal'));
                        if (modal) modal.hide();
                    }, 1500);
                } else {
                    button.innerHTML = '<i class="fas fa-download"></i> Import';
                    button.disabled = false;

                    if (typeof showToast === 'function') {
                        showToast('error', data.message || 'Import failed. Please try again.');
                    }
                }
            })
            .catch(error => {
                console.error('Import error:', error);
                button.innerHTML = '<i class="fas fa-download"></i> Import';
                button.disabled = false;

                if (typeof showToast === 'function') {
                    showToast('error', 'An error occurred. Please try again.');
                }
            });
    }
</script>