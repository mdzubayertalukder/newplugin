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
                    <a href="{{ route('dropshipping.order.management') }}" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Order Management
                    </a>
                    <a href="{{ route('dropshipping.withdrawals') }}" class="btn btn-info">
                        <i class="fas fa-money-bill-wave"></i> Withdrawals
                    </a>
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

        // Use jQuery AJAX like the working version
        const importUrl = '{{ route("dropshipping.import.product", ":id") }}'.replace(':id', productId);

        if (typeof $ !== 'undefined') {
            $.ajax({
                url: importUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    markup_percentage: 20
                },
                success: function(response) {
                    if (response.success) {
                        // Show success
                        button.innerHTML = '<i class="fas fa-check"></i> Imported!';
                        button.classList.remove('importing');
                        button.classList.add('import-success');

                        // Show success message
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'Product imported successfully!');
                        } else {
                            alert(response.message || 'Product imported successfully!');
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
                            toastr.error(response.message || 'Import failed. Please try again.');
                        } else {
                            alert(response.message || 'Import failed. Please try again.');
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Import error:', xhr);
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    button.classList.remove('importing');

                    let errorMessage = 'An error occurred. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // Use default message
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        } else {
            // Fallback to fetch if jQuery not available
            fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        '_token': '{{ csrf_token() }}',
                        'markup_percentage': 20
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.innerHTML = '<i class="fas fa-check"></i> Imported!';
                        button.classList.remove('importing');
                        button.classList.add('import-success');
                        alert(data.message || 'Product imported successfully!');
                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-check"></i> Already Imported';
                            button.disabled = true;
                        }, 2000);
                    } else {
                        button.innerHTML = originalContent;
                        button.disabled = false;
                        button.classList.remove('importing');
                        alert(data.message || 'Import failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Import error:', error);
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    button.classList.remove('importing');
                    alert('An error occurred. Please try again.');
                });
        }
    };

    window.showProductDetails = function(productId) {
        console.log('showProductDetails called with productId:', productId);

        const modal = document.getElementById('productDetailsModal');
        const modalContent = document.getElementById('productDetailsContent');

        if (!modal || !modalContent) {
            alert('Modal not found');
            return;
        }

        // Store product ID globally for research functionality
        window.currentProductId = productId;
        modal.dataset.productId = productId;

        // Show loading
        modalContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading product details...</p></div>';

        // Show modal
        let bootstrapModal;
        if (typeof bootstrap !== 'undefined') {
            bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        } else if (typeof $ !== 'undefined') {
            $(modal).modal('show');
        } else {
            modal.style.display = 'block';
        }

        // Fetch product details
        fetch(`/dropshipping/product-details/${productId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalContent.innerHTML = data.html;
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ${data.message || 'Failed to load product details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading product details:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error loading product details. Please try again.
                </div>
            `;
        });
    };

    console.log('importProduct function loaded successfully:', typeof window.importProduct);

    // Product Research Functions
    let researchData = null;

    window.startProductResearch = function() {
        // Get product ID from stored global variable
        const productId = window.currentProductId;
        
        if (!productId) {
            alert('No product selected for research');
            return;
        }
        
        console.log('Starting research for product ID:', productId);
        
        const btn = document.querySelector('button[onclick="startProductResearch()"]');
        const spinner = document.getElementById('researchSpinner');
        
        if (btn) {
            // Show loading state  
            btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
        }

        // Fetch comprehensive research data
        fetch(`/dropshipping/research/product/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
            }
        })
        .then(response => {
            console.log('Research response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Research response data:', data);
            
            if (data.success) {
                researchData = data.data;
                
                // Show the research results section
                document.getElementById('researchResults').style.display = 'block';
                
                // Render all sections
                renderDropshippingViability(data.data);
                renderResearchOverview(data.data);
                renderPriceAnalysis(data.data);
                renderCompetitorAnalysis(data.data);
                renderProductImages(data.data);
                renderSeoAnalysis(data.data);
                
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-search me-2"></i>Refresh Research';
                    btn.disabled = false;
                }
                if (spinner) spinner.classList.add('d-none');
            } else {
                console.error('Research failed:', data.message);
                showResearchError(data.message || 'Research failed');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
                    btn.disabled = false;
                }
                if (spinner) spinner.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Research error:', error);
            showResearchError('Research failed. Please check browser console for details.');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
                btn.disabled = false;
            }
            if (spinner) spinner.classList.add('d-none');
        });
    };

    // Enhanced Dropshipping Research Functions

    // New comprehensive dropshipping viability analysis
    window.renderDropshippingViability = function(data) {
        const viabilityDiv = document.getElementById('viabilityAnalysis');
        const analysis = data.dropshipping_analysis || {};
        
        let viabilityClass = '';
        switch(analysis.viability_level?.toLowerCase()) {
            case 'excellent': viabilityClass = 'viability-excellent'; break;
            case 'good': viabilityClass = 'viability-good'; break;
            case 'fair': viabilityClass = 'viability-fair'; break;
            case 'poor': viabilityClass = 'viability-poor'; break;
            default: viabilityClass = 'bg-secondary';
        }
        
        let html = `
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3 rounded ${viabilityClass} text-white mb-3">
                        <h2 class="display-4 fw-bold">${analysis.viability_score || 0}%</h2>
                        <h5 class="mb-0">Dropshipping Viability</h5>
                        <p class="mb-0">${analysis.viability_level || 'Unknown'}</p>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-6">
                            <div class="stat-card bg-light p-3 rounded mb-2">
                                <h6 class="text-muted mb-1">Competition Level</h6>
                                <h5 class="mb-0">${analysis.competition_level || 'Unknown'}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card bg-light p-3 rounded mb-2">
                                <h6 class="text-muted mb-1">Profit Potential</h6>
                                <h5 class="mb-0">${analysis.profit_potential || 'Unknown'}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card bg-light p-3 rounded mb-2">
                                <h6 class="text-muted mb-1">Market Saturation</h6>
                                <h5 class="mb-0">${analysis.market_saturation || 'Unknown'}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card bg-light p-3 rounded mb-2">
                                <h6 class="text-muted mb-1">Suggested Markup</h6>
                                <h5 class="mb-0">${analysis.suggested_markup || 'N/A'}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-success"><i class="fas fa-thumbs-up me-2"></i>Pros</h6>
                    <ul class="list-unstyled">
                        ${(analysis.pros || []).map(pro => '<li><i class="fas fa-check text-success me-2"></i>' + pro + '</li>').join('')}
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger"><i class="fas fa-thumbs-down me-2"></i>Cons</h6>
                    <ul class="list-unstyled">
                        ${(analysis.cons || []).map(con => '<li><i class="fas fa-times text-danger me-2"></i>' + con + '</li>').join('')}
                    </ul>
                </div>
            </div>
            
            ${analysis.recommendations?.length ? `
            <div class="mt-3">
                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Recommendations</h6>
                <div class="alert alert-info">
                    <ul class="mb-0">
                        ${analysis.recommendations.map(rec => '<li>' + rec + '</li>').join('')}
                    </ul>
                </div>
            </div>
            ` : ''}
        `;
        
        viabilityDiv.innerHTML = html;
    };

    // Enhanced overview with market summary
    window.renderResearchOverview = function(data) {
        const overviewDiv = document.getElementById('overviewContent');
        const statsDiv = document.getElementById('quickStats');
        
        let html = `
            <div class="mb-3">
                <h6><i class="fas fa-chart-pie text-primary me-2"></i>Market Overview</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-2">
                            <strong>Search Results:</strong> ${data.search_results?.length || 0} listings<br>
                            <small class="text-muted">Competitor websites found in search</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-2">
                            <strong>Shopping Results:</strong> ${data.shopping_results?.length || 0} stores<br>
                            <small class="text-muted">Different retailers selling this product</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <h6><i class="fas fa-store text-success me-2"></i>Top Competitor Domains</h6>
                <div class="competitor-list">
                    ${(data.competitor_websites || []).slice(0, 5).map(comp => 
                        '<div class="d-flex justify-content-between align-items-center border-bottom py-2">' +
                        '<span><strong>' + comp.domain + '</strong></span>' +
                        '<span class="badge bg-secondary">' + comp.count + ' products</span>' +
                        '</div>'
                    ).join('')}
                </div>
            </div>
        `;
        
        let statsHtml = `
            <div class="text-center">
                <div class="stat-item mb-3">
                    <div class="h4 text-primary">${data.competitor_websites?.length || 0}</div>
                    <small>Competitors</small>
                </div>
                <div class="stat-item mb-3">
                    <div class="h4 text-success">${data.price_analysis?.total_sources || 0}</div>
                    <small>Price Sources</small>
                </div>
                <div class="stat-item mb-3">
                    <div class="h4 text-info">${data.product_images?.length || 0}</div>
                    <small>Product Images</small>
                </div>
            </div>
        `;
        
        overviewDiv.innerHTML = html;
        statsDiv.innerHTML = statsHtml;
    };

    // Enhanced price analysis with visual comparison
    window.renderPriceAnalysis = function(data) {
        const priceDiv = document.getElementById('priceAnalysisContent');
        const analysis = data.price_analysis || {};
        const shopping = data.shopping_results || [];
        
        if (!analysis.min_price) {
            priceDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No price data available for analysis</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-success text-white rounded">
                        <h5 class="mb-0">$${analysis.min_price}</h5>
                        <small>Lowest Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-info text-white rounded">
                        <h5 class="mb-0">$${analysis.avg_price}</h5>
                        <small>Average Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-warning text-white rounded">
                        <h5 class="mb-0">$${analysis.median_price}</h5>
                        <small>Median Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-danger text-white rounded">
                        <h5 class="mb-0">$${analysis.max_price}</h5>
                        <small>Highest Price</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h6><i class="fas fa-store me-2"></i>Price Comparison by Store</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Store</th>
                                <th>Price</th>
                                <th>Rating</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${shopping.slice(0, 10).map(item => 
                                '<tr>' +
                                '<td>' +
                                '<strong>' + (item.source || 'Unknown') + '</strong><br>' +
                                '<small class="text-muted">' + (item.title?.substring(0, 50) || '') + '...</small>' +
                                '</td>' +
                                '<td>' +
                                '<span class="h6 text-success">' + (item.price_formatted || 'N/A') + '</span>' +
                                '</td>' +
                                '<td>' +
                                (item.rating ? 
                                    '<span class="badge bg-warning text-dark">' +
                                    '<i class="fas fa-star"></i> ' + item.rating +
                                    '</span>' +
                                    (item.reviews ? '<br><small>' + item.reviews + ' reviews</small>' : '') 
                                    : '<small class="text-muted">No rating</small>') +
                                '</td>' +
                                '<td>' +
                                (item.link ? 
                                    '<a href="' + item.link + '" target="_blank" class="btn btn-sm btn-outline-primary">' +
                                    '<i class="fas fa-external-link-alt"></i> Visit' +
                                    '</a>' 
                                    : '<small class="text-muted">No link</small>') +
                                '</td>' +
                                '</tr>'
                            ).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        priceDiv.innerHTML = html;
    };

    // Enhanced competitor analysis
    window.renderCompetitorAnalysis = function(data) {
        const competitorDiv = document.getElementById('competitorAnalysisContent');
        const competitors = data.detailed_competitors || [];
        
        if (!competitors.length) {
            competitorDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No detailed competitor data available</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="row">
                ${competitors.slice(0, 6).map(comp => 
                    '<div class="col-md-6 mb-3">' +
                    '<div class="competitor-card rounded p-3">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<h6 class="mb-0">' + comp.name + '</h6>' +
                    '<span class="badge bg-primary">' + comp.market_position + '</span>' +
                    '</div>' +
                    '<div class="row text-center mb-2">' +
                    '<div class="col-4">' +
                    '<small class="text-muted">Products</small>' +
                    '<div class="fw-bold">' + comp.total_products + '</div>' +
                    '</div>' +
                    '<div class="col-4">' +
                    '<small class="text-muted">Avg Price</small>' +
                    '<div class="fw-bold text-success">$' + comp.avg_price + '</div>' +
                    '</div>' +
                    '<div class="col-4">' +
                    '<small class="text-muted">Range</small>' +
                    '<div class="fw-bold">$' + comp.price_range.min + ' - $' + comp.price_range.max + '</div>' +
                    '</div>' +
                    '</div>' +
                    (comp.trust_indicators?.length ? 
                        '<div class="mb-2">' +
                        comp.trust_indicators.map(indicator => 
                            '<span class="badge bg-light text-dark me-1">' + indicator + '</span>'
                        ).join('') +
                        '</div>' 
                        : '') +
                    '</div>' +
                    '</div>'
                ).join('')}
            </div>
        `;
        
        competitorDiv.innerHTML = html;
    };

    // Product images gallery
    window.renderProductImages = function(data) {
        const imagesDiv = document.getElementById('productImagesContent');
        const images = data.product_images || [];
        
        if (!images.length) {
            imagesDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-images fa-2x mb-2"></i>
                    <p>No product images found from market research</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="image-gallery">
                ${images.map((image, index) => 
                    '<div class="image-item">' +
                    '<img src="' + image.url + '" ' +
                    'class="research-image w-100" ' +
                    'style="height: 150px; object-fit: cover;"' +
                    'alt="Product Image">' +
                    '<div class="mt-2">' +
                    '<small class="text-muted"><strong>' + image.source + '</strong></small><br>' +
                    '<small>' + image.price + '</small>' +
                    '</div>' +
                    '</div>'
                ).join('')}
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> These images are from competitor websites. Use for inspiration and ensure you have proper rights before using.
            </div>
        `;
        
        imagesDiv.innerHTML = html;
    };

    // Enhanced SEO analysis
    window.renderSeoAnalysis = function(data) {
        const seoDiv = document.getElementById('seoAnalysisContent');
        const seoInsights = data.seo_insights || {};
        const titles = data.suggested_titles || [];
        const descriptions = data.meta_descriptions || [];
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-heading text-primary me-2"></i>Title Suggestions</h6>
                    <div class="suggestions-list">
                        ${titles.slice(0, 6).map(title => 
                            '<div class="suggestion-item border rounded p-2 mb-2">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                            '<span>' + title + '</span>' +
                            '<button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(\'' + title.replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-copy"></i>' +
                            '</button>' +
                            '</div>' +
                            '<small class="text-muted">' + title.length + ' characters</small>' +
                            '</div>'
                        ).join('')}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-file-alt text-info me-2"></i>Meta Description Suggestions</h6>
                    <div class="suggestions-list">
                        ${descriptions.slice(0, 4).map(desc => 
                            '<div class="suggestion-item border rounded p-2 mb-2">' +
                            '<div class="d-flex justify-content-between align-items-start">' +
                            '<span class="flex-grow-1">' + desc + '</span>' +
                            '<button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard(\'' + desc.replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-copy"></i>' +
                            '</button>' +
                            '</div>' +
                            '<small class="text-muted">' + desc.length + ' characters</small>' +
                            '</div>'
                        ).join('')}
                    </div>
                </div>
            </div>
            
            ${seoInsights.common_keywords?.length ? `
            <div class="mt-4">
                <h6><i class="fas fa-tags text-success me-2"></i>Common Keywords Found</h6>
                <div class="keyword-tags">
                    ${seoInsights.common_keywords.slice(0, 15).map(keyword => 
                        '<span class="badge bg-light text-dark me-1 mb-1">' + keyword + '</span>'
                    ).join('')}
                </div>
            </div>
            ` : ''}
        `;
        
        seoDiv.innerHTML = html;
    };

    // Error display function
    window.showResearchError = function(message) {
        const viabilityDiv = document.getElementById('viabilityAnalysis');
        if (viabilityDiv) {
            viabilityDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Research Failed:</strong> ${message}
                </div>
            `;
        }
        
        // Show the research results section even on error
        const resultsSection = document.getElementById('researchResults');
        if (resultsSection) {
            resultsSection.style.display = 'block';
        }
    };

    // Utility function
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = '<i class="fas fa-check text-success"></i> Copied to clipboard!';
            toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: white; padding: 10px 15px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 9999;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        });
    };

    window.loadPriceAnalysis = function(productId) {
        fetch(`/dropshipping/research/price-comparison/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPriceAnalysis(data.data);
            } else {
                document.getElementById('priceAnalysis').innerHTML = 
                    `<div class="text-center text-muted"><p>Price analysis unavailable: ${data.message}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Price analysis error:', error);
            document.getElementById('priceAnalysis').innerHTML = 
                '<div class="text-center text-muted"><p>Failed to load price analysis</p></div>';
        });
    };

    window.renderPriceAnalysis = function(data) {
        const priceDiv = document.getElementById('priceAnalysis');
        
        if (!data.competitors || data.competitors.length === 0) {
            priceDiv.innerHTML = '<div class="text-center text-muted"><p>No competitor pricing data found</p></div>';
            return;
        }

        let html = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <h5 class="text-primary">$${data.price_stats?.min || 'N/A'}</h5>
                            <small>Lowest Price</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <h5 class="text-info">$${data.price_stats?.average || 'N/A'}</h5>
                            <small>Average Price</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <h5 class="text-warning">$${data.price_stats?.max || 'N/A'}</h5>
                            <small>Highest Price</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <h5 class="text-success">${data.competitors?.length || 0}</h5>
                            <small>Competitors</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Price</th>
                            <th>Product</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.competitors.forEach(competitor => {
            html += `
                <tr>
                    <td><strong>${competitor.source || 'Unknown'}</strong></td>
                    <td><span class="badge bg-primary">$${competitor.price || 'N/A'}</span></td>
                    <td>${competitor.title ? competitor.title.substring(0, 50) + '...' : 'No title'}</td>
                    <td>
                        ${competitor.link ? `<a href="${competitor.link}" target="_blank" class="btn btn-sm btn-outline-primary">View <i class="fas fa-external-link-alt"></i></a>` : 'N/A'}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        priceDiv.innerHTML = html;
    };

    window.loadSeoAnalysis = function(productId) {
        fetch(`/dropshipping/research/seo-analysis/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSeoAnalysis(data.data);
            } else {
                document.getElementById('seoInsights').innerHTML = 
                    `<div class="text-center text-muted"><p>SEO analysis unavailable: ${data.message}</p></div>`;
            }
        })
        .catch(error => {
            console.error('SEO analysis error:', error);
            document.getElementById('seoInsights').innerHTML = 
                '<div class="text-center text-muted"><p>Failed to load SEO analysis</p></div>';
        });
    };

    window.renderSeoAnalysis = function(data) {
        const seoDiv = document.getElementById('seoInsights');
        
        let html = `
            <div class="row">
                <div class="col-md-12">
                    <h6><i class="fas fa-lightbulb text-warning"></i> Title Suggestions</h6>
                    <div class="mb-3">
        `;

        if (data.suggested_titles && data.suggested_titles.length > 0) {
            data.suggested_titles.forEach(title => {
                html += `
                    <div class="alert alert-light d-flex justify-content-between align-items-center">
                        <span>${title}</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('${title.replace(/'/g, "\\'")}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-muted">No title suggestions available</p>';
        }

        html += `
                    </div>
                    
                    <h6><i class="fas fa-tags text-info"></i> Keywords</h6>
                    <div class="mb-3">
        `;

        if (data.keywords && data.keywords.length > 0) {
            data.keywords.forEach(keyword => {
                html += `<span class="badge bg-secondary me-1 mb-1">${keyword}</span>`;
            });
        } else {
            html += '<p class="text-muted">No keywords extracted</p>';
        }

        html += `
                    </div>
                    
                    <h6><i class="fas fa-chart-line text-success"></i> SEO Recommendations</h6>
                    <div class="alert alert-info">
                        <ul class="mb-0">
                            <li>Use competitor keywords in your title</li>
                            <li>Include price-related terms for better shopping results</li>
                            <li>Add product benefits and features</li>
                            <li>Use location-based keywords if applicable</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        seoDiv.innerHTML = html;
    };

    window.loadCompetitorAnalysis = function(productId) {
        fetch(`/dropshipping/research/competitor-analysis/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCompetitorAnalysis(data.data);
            } else {
                document.getElementById('competitorAnalysis').innerHTML = 
                    `<div class="text-center text-muted"><p>Competitor analysis unavailable: ${data.message}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Competitor analysis error:', error);
            document.getElementById('competitorAnalysis').innerHTML = 
                '<div class="text-center text-muted"><p>Failed to load competitor analysis</p></div>';
        });
    };

    window.renderCompetitorAnalysis = function(data) {
        const competitorDiv = document.getElementById('competitorAnalysis');
        
        let html = `
            <div class="row">
                <div class="col-md-12">
                    <h6><i class="fas fa-crown text-warning"></i> Market Leaders</h6>
                    <div class="row mb-4">
        `;

        if (data.market_leaders && data.market_leaders.length > 0) {
            data.market_leaders.slice(0, 5).forEach((leader, index) => {
                html += `
                    <div class="col-md-2 mb-2">
                        <div class="card text-center">
                            <div class="card-body p-2">
                                <h6 class="card-title small">#${index + 1}</h6>
                                <small>${leader.domain || 'Unknown'}</small>
                                ${leader.link ? `<div><a href="${leader.link}" target="_blank" class="btn btn-xs btn-outline-primary mt-1">Visit</a></div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-muted col-12">No market leaders identified</p>';
        }

        html += `
                    </div>
                    
                    <h6><i class="fas fa-store text-primary"></i> Competitor Websites</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Website</th>
                                    <th>Product Title</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        if (data.websites && data.websites.length > 0) {
            data.websites.slice(0, 10).forEach(website => {
                html += `
                    <tr>
                        <td><strong>${website.domain || 'Unknown'}</strong></td>
                        <td>${website.title ? website.title.substring(0, 60) + '...' : 'No title'}</td>
                        <td>
                            ${website.link ? `<a href="${website.link}" target="_blank" class="btn btn-sm btn-outline-primary">Analyze <i class="fas fa-external-link-alt"></i></a>` : 'N/A'}
                        </td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="3" class="text-center text-muted">No competitor websites found</td></tr>';
        }

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-light">
                <h6><i class="fas fa-tips text-info"></i> Competitive Insights</h6>
                <ul class="mb-0">
                    <li>Monitor top competitor pricing strategies</li>
                    <li>Analyze their product descriptions and titles</li>
                    <li>Consider similar marketing approaches</li>
                    <li>Look for unique selling propositions</li>
                </ul>
            </div>
        `;
        
        competitorDiv.innerHTML = html;
    };

    window.showResearchError = function(message) {
        const overviewDiv = document.getElementById('researchOverview');
        if (overviewDiv) {
            overviewDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }
    };

    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.innerHTML = '<i class="fas fa-check"></i> Copied to clipboard!';
            toast.className = 'alert alert-success position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 2000);
        }).catch(() => {
            alert('Failed to copy to clipboard');
        });
    };

    console.log('Research functions loaded successfully');
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