@extends('core::base.layouts.master')

@section('title')
{{ translate('Dropshipping Products') }}
@endsection

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
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
                                    <span class="h6 text-danger mb-0 me-2">৳{{ number_format(floatval($product->sale_price), 2) }}</span>
                                    <span class="text-muted text-decoration-line-through small">৳{{ number_format(floatval($product->regular_price), 2) }}</span>
                                    @else
                                    <span class="h6 text-dark mb-0">৳{{ number_format(floatval($product->regular_price), 2) }}</span>
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
                        
                        let errorMessage = data.message || 'Import failed. Please try again.';
                        
                        // Show upgrade message if limit reached
                        if (data.upgrade_message) {
                            errorMessage += '\n\n' + data.upgrade_message;
                        }
                        
                        alert(errorMessage);
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

<<<<<<< HEAD
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('meta[name="_token"]')?.getAttribute('content') || 
                         '{{ csrf_token() }}';

        if (!csrfToken) {
            console.error('CSRF token not found');
            showResearchError('Security token not found. Please refresh the page.');
            return;
        }

=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
        // Fetch comprehensive research data
        fetch(`/dropshipping/research/product/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
<<<<<<< HEAD
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
=======
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
            }
        })
        .then(response => {
            console.log('Research response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Research response data:', data);
            
            if (data.success) {
<<<<<<< HEAD
                // Store research data globally for tab access
                researchData = data.data;
                window.researchData = data.data;
=======
                researchData = data.data;
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                
                // Show the research results section
                document.getElementById('researchResults').style.display = 'block';
                
<<<<<<< HEAD
                // Debug: Check if all required elements exist
                console.log('DOM elements check:', {
                    viabilityAnalysis: document.getElementById('viabilityAnalysis'),
                    overviewContent: document.getElementById('overviewContent'),
                    priceAnalysisContent: document.getElementById('priceAnalysisContent'),
                    competitorAnalysisContent: document.getElementById('competitorAnalysisContent'),
                    productImagesContent: document.getElementById('productImagesContent'),
                    seoAnalysisContent: document.getElementById('seoAnalysisContent')
                });
                
                // Log all the data we received for debugging
                console.log('=== COMPLETE RESEARCH DATA RECEIVED ===');
                console.log('Full data object:', data.data);
                console.log('Price analysis data:', data.data.price_analysis);
                console.log('Shopping results:', data.data.shopping_results);
                console.log('Competitor analysis data:', data.data.detailed_competitors);
                console.log('Product images data:', data.data.product_images);
                console.log('SEO analysis data:', data.data.seo_insights);
                console.log('Profit calculator data:', data.data.profit_calculator);
                console.log('Social media targeting data:', data.data.social_media_targeting);
                console.log('Competition metrics data:', data.data.competition_metrics);
                
                // Render comprehensive overview with ALL information
                renderDropshippingViability(data.data);
                renderComprehensiveOverview(data.data);
                
                // Initialize tabs after content is rendered
                setTimeout(function() {
                    if (typeof initializeResearchTabs === 'function') {
                        initializeResearchTabs();
                    }
                }, 200);
                
                // Show usage information if available
                if (data.data.usage_info) {
                    showUsageInfo(data.data.usage_info);
                }
=======
                // Render all sections
                renderDropshippingViability(data.data);
                renderResearchOverview(data.data);
                renderPriceAnalysis(data.data);
                renderCompetitorAnalysis(data.data);
                renderProductImages(data.data);
                renderSeoAnalysis(data.data);
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-search me-2"></i>Refresh Research';
                    btn.disabled = false;
                }
                if (spinner) spinner.classList.add('d-none');
            } else {
                console.error('Research failed:', data.message);
<<<<<<< HEAD
                
                // Check if it's a limit reached error
                if (data.limit_reached) {
                    showLimitReachedError(data.message, data.upgrade_message);
                } else {
                    // Show error but also try to display some basic info
                    showResearchError(data.message || 'Research failed');
                    
                    // If the API is not configured, show a demo with basic info
                    if (data.message && data.message.includes('not available')) {
                        renderDemoData();
                    }
                }
                
=======
                showResearchError(data.message || 'Research failed');
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
                    btn.disabled = false;
                }
                if (spinner) spinner.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Research error:', error);
<<<<<<< HEAD
            let errorMessage = 'Research failed. Please try again.';
            
            if (error.message) {
                errorMessage = `Research failed: ${error.message}`;
            }
            
            showResearchError(errorMessage);
=======
            showResearchError('Research failed. Please check browser console for details.');
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
            if (btn) {
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
                btn.disabled = false;
            }
            if (spinner) spinner.classList.add('d-none');
        });
    };

    // Enhanced Dropshipping Research Functions
<<<<<<< HEAD
    
    window.showResearchError = function(message) {
        const researchResults = document.getElementById('researchResults');
        if (researchResults) {
            researchResults.style.display = 'block';
            researchResults.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Research Error:</strong> ${message}
                    <br><small class="mt-2 d-block">
                        Please check:
                        <ul class="mt-2 mb-0">
                            <li>Your internet connection</li>
                            <li>That the Serper API is configured in admin settings</li>
                            <li>That you have sufficient API credits</li>
                        </ul>
                    </small>
                </div>
            `;
        } else {
            alert('Research Error: ' + message);
        }
    };

    window.showLimitReachedError = function(message, upgradeMessage) {
        const researchResults = document.getElementById('researchResults');
        if (researchResults) {
            researchResults.style.display = 'block';
            researchResults.innerHTML = `
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Research Limit Reached</h5>
                    <p class="mb-2">${message}</p>
                    <p class="mb-3">${upgradeMessage}</p>
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-up me-1"></i>Upgrade Plan
                    </a>
                </div>
            `;
        } else {
            alert('Limit Reached: ' + message);
        }
    };

    window.showUsageInfo = function(usageInfo) {
        const usageHtml = `
            <div class="alert alert-info mb-3">
                <h6><i class="fas fa-chart-bar me-2"></i>Research Usage</h6>
                <p class="mb-0">
                    This month: ${usageInfo.monthly_used} / ${usageInfo.monthly_limit === -1 ? 'Unlimited' : usageInfo.monthly_limit}
                    ${usageInfo.remaining !== -1 ? ` (${usageInfo.remaining} remaining)` : ''}
                </p>
            </div>
        `;
        
        // Add usage info to the research results
        const researchResults = document.getElementById('researchResults');
        if (researchResults) {
            researchResults.insertAdjacentHTML('afterbegin', usageHtml);
        }
    };
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395

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
        
<<<<<<< HEAD
        console.log('Price analysis data:', analysis);
        console.log('Shopping results:', shopping);
        
        if (!shopping.length && !analysis.min_price) {
=======
        if (!analysis.min_price) {
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
            priceDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No price data available for analysis</p>
<<<<<<< HEAD
                    <small>Try searching for a different product or check if the API is working</small>
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-success text-white rounded">
<<<<<<< HEAD
                        <h5 class="mb-0">$${analysis.min_price || 'N/A'}</h5>
=======
                        <h5 class="mb-0">$${analysis.min_price}</h5>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                        <small>Lowest Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-info text-white rounded">
<<<<<<< HEAD
                        <h5 class="mb-0">$${analysis.avg_price || 'N/A'}</h5>
=======
                        <h5 class="mb-0">$${analysis.avg_price}</h5>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                        <small>Average Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-warning text-white rounded">
<<<<<<< HEAD
                        <h5 class="mb-0">$${analysis.median_price || 'N/A'}</h5>
=======
                        <h5 class="mb-0">$${analysis.median_price}</h5>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                        <small>Median Price</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-danger text-white rounded">
<<<<<<< HEAD
                        <h5 class="mb-0">$${analysis.max_price || 'N/A'}</h5>
=======
                        <h5 class="mb-0">$${analysis.max_price}</h5>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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
<<<<<<< HEAD
        const websites = data.competitor_websites || [];
        const searchResults = data.search_results || [];
        
        console.log('Competitor analysis data:', {competitors, websites, searchResults});
        
        if (!competitors.length && !websites.length && !searchResults.length) {
            competitorDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No competitor data available</p>
                    <small>Try searching for a different product or check if the API is working</small>
=======
        
        if (!competitors.length) {
            competitorDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No detailed competitor data available</p>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                </div>
            `;
            return;
        }
        
<<<<<<< HEAD
        let html = `<div class="row">`;
        
        // Show detailed competitors if available
        if (competitors.length > 0) {
            html += `<div class="col-12 mb-4">
                <h6><i class="fas fa-crown text-warning me-2"></i>Detailed Competitors</h6>
                <div class="row">
                    ${competitors.slice(0, 6).map(comp => 
                        '<div class="col-md-6 mb-3">' +
                        '<div class="competitor-card rounded p-3 border">' +
                        '<div class="d-flex justify-content-between align-items-start mb-2">' +
                        '<h6 class="mb-0">' + (comp.name || comp.domain || 'Unknown') + '</h6>' +
                        '<span class="badge bg-primary">' + (comp.market_position || 'Competitor') + '</span>' +
                        '</div>' +
                        '<div class="row text-center mb-2">' +
                        '<div class="col-4">' +
                        '<small class="text-muted">Products</small>' +
                        '<div class="fw-bold">' + (comp.total_products || 'N/A') + '</div>' +
                        '</div>' +
                        '<div class="col-4">' +
                        '<small class="text-muted">Avg Price</small>' +
                        '<div class="fw-bold text-success">$' + (comp.avg_price || 'N/A') + '</div>' +
                        '</div>' +
                        '<div class="col-4">' +
                        '<small class="text-muted">Range</small>' +
                        '<div class="fw-bold">$' + (comp.price_range?.min || 'N/A') + ' - $' + (comp.price_range?.max || 'N/A') + '</div>' +
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
            </div>`;
        }
        
        // Show search results as competitors
        if (searchResults.length > 0) {
            html += `<div class="col-12 mb-4">
                <h6><i class="fas fa-search text-info me-2"></i>Search Results Competitors</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${searchResults.slice(0, 10).map(result => {
                                const isBangladeshi = result.is_bangladeshi || false;
                                const rowClass = isBangladeshi ? 'table-success' : '';
                                const locationBadge = isBangladeshi ? '<span class="badge bg-success">🇧🇩 Bangladesh</span>' : '<span class="badge bg-secondary">Global</span>';
                                
                                return '<tr class="' + rowClass + '">' +
                                    '<td><strong>' + (result.domain || 'Unknown') + '</strong></td>' +
                                    '<td>' + (result.title ? result.title.substring(0, 60) + '...' : 'No title') + '</td>' +
                                    '<td>' + locationBadge + '</td>' +
                                    '<td>' +
                                    (result.link ? 
                                        '<a href="' + result.link + '" target="_blank" class="btn btn-sm btn-outline-primary">' +
                                        '<i class="fas fa-external-link-alt"></i> Visit' +
                                        '</a>' 
                                        : '<small class="text-muted">No link</small>') +
                                    '</td>' +
                                    '</tr>';
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
        }
        
        html += `</div>`;
        
        // Add insights section
        html += `
            <div class="alert alert-light">
                <h6><i class="fas fa-lightbulb text-info me-2"></i>Competitive Insights</h6>
                <ul class="mb-0">
                    <li>Monitor competitor pricing and product positioning</li>
                    <li>Analyze successful product titles and descriptions</li>
                    <li>Focus on Bangladeshi competitors for local market insights</li>
                    <li>Look for gaps in competitor offerings</li>
                </ul>
=======
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
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
            </div>
        `;
        
        competitorDiv.innerHTML = html;
    };

    // Product images gallery
    window.renderProductImages = function(data) {
        const imagesDiv = document.getElementById('productImagesContent');
        const images = data.product_images || [];
        
<<<<<<< HEAD
        console.log('Product images data:', images);
        
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
        if (!images.length) {
            imagesDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-images fa-2x mb-2"></i>
                    <p>No product images found from market research</p>
<<<<<<< HEAD
                    <small>Images will be extracted from competitor websites when available</small>
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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
        
<<<<<<< HEAD
        console.log('SEO analysis data:', {seoInsights, titles, descriptions});
        console.log('Full SEO insights object:', seoInsights);
        
        if (!titles.length && !descriptions.length && !seoInsights.common_keywords?.length) {
            seoDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>No SEO data available</p>
                    <small>SEO recommendations will be generated based on competitor analysis</small>
                </div>
            `;
            return;
        }
        
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-heading text-primary me-2"></i>Title Suggestions</h6>
                    <div class="suggestions-list">
<<<<<<< HEAD
                        ${titles.length > 0 ? titles.slice(0, 6).map(title =>
=======
                        ${titles.slice(0, 6).map(title => 
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                            '<div class="suggestion-item border rounded p-2 mb-2">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                            '<span>' + title + '</span>' +
                            '<button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(\'' + title.replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-copy"></i>' +
                            '</button>' +
                            '</div>' +
                            '<small class="text-muted">' + title.length + ' characters</small>' +
                            '</div>'
<<<<<<< HEAD
                        ).join('') : '<div class="text-muted">No title suggestions available</div>'}
=======
                        ).join('')}
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-file-alt text-info me-2"></i>Meta Description Suggestions</h6>
                    <div class="suggestions-list">
<<<<<<< HEAD
                        ${descriptions.length > 0 ? descriptions.slice(0, 4).map(desc =>
=======
                        ${descriptions.slice(0, 4).map(desc => 
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                            '<div class="suggestion-item border rounded p-2 mb-2">' +
                            '<div class="d-flex justify-content-between align-items-start">' +
                            '<span class="flex-grow-1">' + desc + '</span>' +
                            '<button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard(\'' + desc.replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-copy"></i>' +
                            '</button>' +
                            '</div>' +
                            '<small class="text-muted">' + desc.length + ' characters</small>' +
                            '</div>'
<<<<<<< HEAD
                        ).join('') : '<div class="text-muted">No description suggestions available</div>'}
=======
                        ).join('')}
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                    </div>
                </div>
            </div>
            
            ${seoInsights.common_keywords?.length ? `
            <div class="mt-4">
                <h6><i class="fas fa-tags text-success me-2"></i>Common Keywords Found</h6>
                <div class="keyword-tags">
<<<<<<< HEAD
                    ${seoInsights.common_keywords.slice(0, 15).map(keyword =>
=======
                    ${seoInsights.common_keywords.slice(0, 15).map(keyword => 
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                        '<span class="badge bg-light text-dark me-1 mb-1">' + keyword + '</span>'
                    ).join('')}
                </div>
            </div>
            ` : ''}
<<<<<<< HEAD
            
            ${seoInsights.optimization_tips?.length ? `
            <div class="mt-4">
                <h6><i class="fas fa-lightbulb text-warning me-2"></i>SEO Optimization Tips</h6>
                <div class="alert alert-info">
                    <ul class="mb-0">
                        ${seoInsights.optimization_tips.map(tip => '<li>' + tip + '</li>').join('')}
                    </ul>
                </div>
            </div>
            ` : ''}
            
            ${seoInsights.content_gaps?.length ? `
            <div class="mt-4">
                <h6><i class="fas fa-exclamation-triangle text-danger me-2"></i>Content Gaps Identified</h6>
                <div class="alert alert-warning">
                    <ul class="mb-0">
                        ${seoInsights.content_gaps.map(gap => '<li>' + gap + '</li>').join('')}
                    </ul>
                </div>
            </div>
            ` : ''}
            
            ${seoInsights.competitor_keywords?.length ? `
            <div class="mt-4">
                <h6><i class="fas fa-users text-info me-2"></i>Competitor Keywords</h6>
                <div class="keyword-tags">
                    ${seoInsights.competitor_keywords.slice(0, 20).map(keyword =>
                        '<span class="badge bg-secondary me-1 mb-1">' + keyword + '</span>'
                    ).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Debug section to show all SEO data -->
            <div class="mt-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-code"></i> SEO Debug Data</h6>
                    </div>
                    <div class="card-body">
                        <small><pre>${JSON.stringify({
                            seo_insights: seoInsights,
                            suggested_titles: titles,
                            meta_descriptions: descriptions
                        }, null, 2)}</pre></small>
                    </div>
                </div>
            </div>
=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
        `;
        
        seoDiv.innerHTML = html;
    };

<<<<<<< HEAD
    // Profit Calculator rendering
    window.renderProfitCalculator = function(data) {
        const profitDiv = document.getElementById('profitCalculatorContent');
        const profitData = data.profit_calculator || {};
        const costScenarios = profitData.cost_scenarios || [];
        const breakEven = profitData.break_even_analysis || {};
        
        console.log('Profit calculator data:', profitData);
        
        // Check if we have research data to enhance the calculator
        if (costScenarios.length > 0 || breakEven.break_even_units) {
            // Enhanced calculator with research data
            let html = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Enhanced with Market Research Data</strong> - The calculator below includes insights from competitor analysis.
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h6><i class="fas fa-chart-line text-success me-2"></i>Market-Based Cost Scenarios</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Scenario</th>
                                        <th>Product Cost</th>
                                        <th>Total Costs</th>
                                        <th>Suggested Price</th>
                                        <th>Profit</th>
                                        <th>Margin %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${costScenarios.map(scenario =>
                                        '<tr>' +
                                        '<td><strong>' + (scenario.scenario || 'Standard') + '</strong></td>' +
                                        '<td>৳' + (scenario.product_cost || 0).toFixed(2) + '</td>' +
                                        '<td>৳' + (scenario.total_cost || 0).toFixed(2) + '</td>' +
                                        '<td class="text-primary">৳' + (scenario.suggested_price || 0).toFixed(2) + '</td>' +
                                        '<td class="text-success">৳' + (scenario.profit || 0).toFixed(2) + '</td>' +
                                        '<td><span class="badge bg-success">' + (scenario.margin_percentage || 0).toFixed(1) + '%</span></td>' +
                                        '</tr>'
                                    ).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                ${breakEven.break_even_units ? `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-primary">${breakEven.break_even_units}</h5>
                                <small class="text-muted">Units to Break Even</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-success">৳${(breakEven.break_even_revenue || 0).toFixed(2)}</h5>
                                <small class="text-muted">Break Even Revenue</small>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Research-Based Insights:</strong> These calculations incorporate competitor pricing and market analysis. The basic calculator above remains available for quick estimates.
                </div>
            `;
            
            // Append to existing content instead of replacing
            const existingContent = profitDiv.querySelector('.default-profit-calculator');
            if (existingContent) {
                const researchSection = document.createElement('div');
                researchSection.className = 'research-enhanced-calculator mt-4';
                researchSection.innerHTML = html;
                profitDiv.appendChild(researchSection);
            } else {
                profitDiv.innerHTML = html;
            }
        }
        // If no research data, the default calculator remains as is
    };

    // Social Media Targeting rendering
    window.renderSocialMediaTargeting = function(data) {
        const socialDiv = document.getElementById('socialMediaContent');
        const socialData = data.social_media_targeting || {};
        const facebook = socialData.facebook_instagram || {};
        const tiktok = socialData.tiktok || {};
        const youtube = socialData.youtube || {};
        
        console.log('Social media targeting data:', socialData);
        
        if (!facebook.target_demographics && !tiktok.target_demographics && !youtube.content_opportunities) {
            socialDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-share-alt fa-2x mb-2"></i>
                    <p>No social media targeting data available</p>
                    <small>Social media recommendations will be generated based on target audience analysis</small>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="row">
                ${facebook.target_demographics ? `
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fab fa-facebook me-2"></i>Facebook & Instagram</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Target Demographics:</strong>
                                <ul class="list-unstyled mt-2">
                                    <li><i class="fas fa-users me-2"></i>Age: ${facebook.target_demographics.age_range || 'N/A'}</li>
                                    <li><i class="fas fa-venus-mars me-2"></i>Gender: ${facebook.target_demographics.gender || 'N/A'}</li>
                                    <li><i class="fas fa-dollar-sign me-2"></i>Income: $${facebook.target_demographics.income || 'N/A'}</li>
                                </ul>
                            </div>
                            ${facebook.interests?.length ? `
                            <div class="mb-3">
                                <strong>Interests:</strong>
                                <div class="mt-2">
                                    ${facebook.interests.slice(0, 4).map(interest =>
                                        '<span class="badge bg-light text-dark me-1 mb-1">' + interest + '</span>'
                                    ).join('')}
                                </div>
                            </div>
                            ` : ''}
                            <div class="text-center">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-success">$${facebook.estimated_cpc || 0}</div>
                                        <small class="text-muted">Est. CPC</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-info">${facebook.conversion_rate || 0}%</div>
                                        <small class="text-muted">Conv. Rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${tiktok.target_demographics ? `
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="fab fa-tiktok me-2"></i>TikTok</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Target Demographics:</strong>
                                <ul class="list-unstyled mt-2">
                                    <li><i class="fas fa-users me-2"></i>Age: ${tiktok.target_demographics.age_range || 'N/A'}</li>
                                </ul>
                            </div>
                            ${tiktok.hashtag_strategy?.length ? `
                            <div class="mb-3">
                                <strong>Hashtag Strategy:</strong>
                                <div class="mt-2">
                                    ${tiktok.hashtag_strategy.slice(0, 4).map(hashtag =>
                                        '<span class="badge bg-secondary me-1 mb-1">' + hashtag + '</span>'
                                    ).join('')}
                                </div>
                            </div>
                            ` : ''}
                            <div class="text-center">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-success">$${tiktok.estimated_cpc || 0}</div>
                                        <small class="text-muted">Est. CPC</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-warning">${tiktok.viral_potential || 'Medium'}</div>
                                        <small class="text-muted">Viral Potential</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${youtube.content_opportunities?.length ? `
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fab fa-youtube me-2"></i>YouTube</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Content Opportunities:</strong>
                                <ul class="list-unstyled mt-2">
                                    ${youtube.content_opportunities.slice(0, 3).map(opportunity =>
                                        '<li><i class="fas fa-video me-2"></i>' + opportunity + '</li>'
                                    ).join('')}
                                </ul>
                            </div>
                            <div class="text-center">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-success">$${youtube.estimated_cpc || 0}</div>
                                        <small class="text-muted">Est. CPC</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-info">${youtube.long_term_value || 'High'}</div>
                                        <small class="text-muted">LTV</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            
            <div class="alert alert-success mt-3">
                <i class="fas fa-bullhorn me-2"></i>
                <strong>Marketing Tip:</strong> Start with the platform that matches your target demographic best. Test small budgets first and scale successful campaigns.
            </div>
        `;
        
        socialDiv.innerHTML = html;
    };

    // Competition Metrics rendering
    window.renderCompetitionMetrics = function(data) {
        const metricsDiv = document.getElementById('competitionMetricsContent');
        const metricsData = data.competition_metrics || {};
        const concentration = metricsData.market_concentration || {};
        const barriers = metricsData.entry_barriers || {};
        const advantages = metricsData.competitive_advantages || [];
        const trends = metricsData.market_trends || [];
        
        console.log('Competition metrics data:', metricsData);
        
        if (!concentration.top_3_share && !barriers.overall_difficulty && !advantages.length && !trends.length) {
            metricsDiv.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <p>No competition metrics available</p>
                    <small>Market competition analysis will be generated based on competitor research</small>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="row mb-4">
                ${concentration.top_3_share ? `
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-chart-pie text-primary me-2"></i>Market Concentration</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="h4 text-warning">${concentration.top_3_share || 'N/A'}</div>
                                    <small class="text-muted">Top 3 Share</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 text-info">${concentration.hhi_index || 'N/A'}</div>
                                    <small class="text-muted">HHI Index</small>
                                </div>
                                <div class="col-4">
                                    <div class="h6 text-secondary">${concentration.market_type || 'N/A'}</div>
                                    <small class="text-muted">Market Type</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${barriers.overall_difficulty ? `
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-shield-alt text-danger me-2"></i>Entry Barriers</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Capital Requirements:</span>
                                    <span class="badge bg-${barriers.capital_requirements === 'Low' ? 'success' : barriers.capital_requirements === 'Medium' ? 'warning' : 'danger'}">${barriers.capital_requirements || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Brand Loyalty:</span>
                                    <span class="badge bg-${barriers.brand_loyalty === 'Low' ? 'success' : barriers.brand_loyalty === 'Medium' ? 'warning' : 'danger'}">${barriers.brand_loyalty || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Overall Difficulty:</span>
                                    <span class="badge bg-${barriers.overall_difficulty === 'Low' ? 'success' : barriers.overall_difficulty === 'Medium' ? 'warning' : 'danger'}">${barriers.overall_difficulty || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            
            ${advantages.length ? `
            <div class="row mb-4">
                <div class="col-12">
                    <h6><i class="fas fa-trophy text-warning me-2"></i>Competitive Advantages</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Advantage</th>
                                    <th>Importance</th>
                                    <th>Achievability</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${advantages.map(advantage => {
                                    const priority = advantage.importance === 'High' && advantage.achievability === 'High' ? 'High' :
                                                   advantage.importance === 'High' && advantage.achievability === 'Medium' ? 'Medium' : 'Low';
                                    return '<tr>' +
                                        '<td><strong>' + (advantage.advantage || 'N/A') + '</strong></td>' +
                                        '<td><span class="badge bg-' + (advantage.importance === 'High' ? 'danger' : advantage.importance === 'Medium' ? 'warning' : 'secondary') + '">' + (advantage.importance || 'N/A') + '</span></td>' +
                                        '<td><span class="badge bg-' + (advantage.achievability === 'High' ? 'success' : advantage.achievability === 'Medium' ? 'warning' : 'secondary') + '">' + (advantage.achievability || 'N/A') + '</span></td>' +
                                        '<td><span class="badge bg-' + (priority === 'High' ? 'primary' : priority === 'Medium' ? 'info' : 'light text-dark') + '">' + priority + '</span></td>' +
                                        '</tr>';
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${trends.length ? `
            <div class="row">
                <div class="col-12">
                    <h6><i class="fas fa-trending-up text-success me-2"></i>Market Trends</h6>
                    <div class="alert alert-light">
                        <ul class="mb-0">
                            ${trends.map(trend => '<li>' + trend + '</li>').join('')}
                        </ul>
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        
        metricsDiv.innerHTML = html;
    };

=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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

<<<<<<< HEAD
    // Remove old duplicate functions - they're replaced by the enhanced versions above

    // Old SEO functions removed - using enhanced versions above

    // Old competitor analysis functions removed - using enhanced versions above
    
    // Demo data function for when API is not configured
    window.renderDemoData = function() {
        const demoData = {
            price_analysis: {
                min_price: 15.99,
                max_price: 89.99,
                avg_price: 45.50,
                median_price: 42.00,
                total_sources: 5,
                price_gaps: [
                    {
                        gap_range: "$20-30",
                        opportunity: "Budget segment underserved",
                        potential_volume: "High"
                    }
                ]
            },
            shopping_results: [
                {
                    title: "Demo Product - Similar Item",
                    source: "Demo Store",
                    price: 25.99,
                    price_formatted: "$25.99",
                    link: "#",
                    is_bangladeshi: true
                }
            ],
            search_results: [
                {
                    title: "Demo Search Result",
                    domain: "demo-store.com",
                    link: "#",
                    is_bangladeshi: false
                }
            ],
            suggested_titles: [
                "Premium Quality Product - Best Price",
                "Professional Grade Item - Fast Delivery",
                "Top Rated Product - Customer Choice"
            ],
            meta_descriptions: [
                "Get the best deals on premium quality products with fast delivery and excellent customer service.",
                "Shop top-rated items at competitive prices with money-back guarantee."
            ],
            product_images: [],
            seo_insights: {
                common_keywords: ["quality", "premium", "best", "price", "delivery"]
            },
            profit_calculator: {
                cost_scenarios: [
                    {
                        scenario: "Basic Dropshipping",
                        product_cost: 12.00,
                        total_cost: 22.60,
                        suggested_price: 35.99,
                        profit: 13.39,
                        margin_percentage: 37.2
                    },
                    {
                        scenario: "Premium Positioning",
                        product_cost: 12.00,
                        total_cost: 27.10,
                        suggested_price: 49.99,
                        profit: 22.89,
                        margin_percentage: 45.8
                    }
                ],
                break_even_analysis: {
                    break_even_units: 37,
                    break_even_revenue: 1332
                }
            },
            social_media_targeting: {
                facebook_instagram: {
                    target_demographics: {
                        age_range: "25-45",
                        gender: "All genders",
                        income: "35,000-75,000"
                    },
                    interests: ["Home improvement", "Technology", "Quality products"],
                    estimated_cpc: 0.85,
                    conversion_rate: 2.3
                },
                tiktok: {
                    target_demographics: {
                        age_range: "18-35"
                    },
                    hashtag_strategy: ["#productreview", "#homeimprovement", "#lifehack"],
                    estimated_cpc: 0.65,
                    viral_potential: "High"
                },
                youtube: {
                    content_opportunities: ["Unboxing videos", "Product reviews", "How-to tutorials"],
                    estimated_cpc: 0.45,
                    long_term_value: "High"
                }
            },
            competition_metrics: {
                market_concentration: {
                    top_3_share: "62%",
                    hhi_index: 1850,
                    market_type: "Moderately concentrated"
                },
                entry_barriers: {
                    capital_requirements: "Low",
                    brand_loyalty: "Medium",
                    overall_difficulty: "Medium"
                },
                competitive_advantages: [
                    {
                        advantage: "Price competitiveness",
                        importance: "High",
                        achievability: "Medium"
                    },
                    {
                        advantage: "Unique features",
                        importance: "Medium",
                        achievability: "High"
                    }
                ],
                market_trends: [
                    "Increasing demand for eco-friendly options",
                    "Growing preference for online purchasing",
                    "Rising importance of customer reviews"
                ]
            }
        };
        
        document.getElementById('researchResults').style.display = 'block';
        renderPriceAnalysis(demoData);
        renderCompetitorAnalysis(demoData);
        renderProductImages(demoData);
        renderSeoAnalysis(demoData);
        renderProfitCalculator(demoData);
        renderSocialMediaTargeting(demoData);
        renderCompetitionMetrics(demoData);
=======
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
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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

<<<<<<< HEAD
    // NEW: Comprehensive overview with ALL research information in one place
    window.renderComprehensiveOverview = function(data) {
        const overviewDiv = document.getElementById('comprehensiveOverview');
        if (!overviewDiv) {
            console.error('Comprehensive overview div not found');
            return;
        }

        console.log('Rendering comprehensive overview with ALL data:', data);

        let html = `
            <!-- Market Overview Section -->
            <div class="mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-chart-pie me-2"></i>Market Overview</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card bg-light p-3 rounded text-center">
                            <div class="h4 text-primary">${data.competitor_websites?.length || 0}</div>
                            <small class="text-muted">Competitors Found</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-light p-3 rounded text-center">
                            <div class="h4 text-success">${data.shopping_results?.length || 0}</div>
                            <small class="text-muted">Shopping Results</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-light p-3 rounded text-center">
                            <div class="h4 text-info">${data.search_results?.length || 0}</div>
                            <small class="text-muted">Search Results</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-light p-3 rounded text-center">
                            <div class="h4 text-warning">${data.product_images?.length || 0}</div>
                            <small class="text-muted">Product Images</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Analysis Section -->
            <div class="mb-4">
                <h5 class="text-success mb-3"><i class="fas fa-dollar-sign me-2"></i>Price Analysis</h5>
                ${data.price_analysis ? `
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-success text-white rounded">
                            <h5 class="mb-0">$${data.price_analysis.min_price || 'N/A'}</h5>
                            <small>Lowest Price</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-info text-white rounded">
                            <h5 class="mb-0">$${data.price_analysis.avg_price || data.price_analysis.average_price || 'N/A'}</h5>
                            <small>Average Price</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-warning text-white rounded">
                            <h5 class="mb-0">$${data.price_analysis.median_price || 'N/A'}</h5>
                            <small>Median Price</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-danger text-white rounded">
                            <h5 class="mb-0">$${data.price_analysis.max_price || 'N/A'}</h5>
                            <small>Highest Price</small>
                        </div>
                    </div>
                </div>
                ` : '<div class="alert alert-info">No price analysis data available</div>'}
                
                ${data.shopping_results?.length ? `
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
                            ${data.shopping_results.slice(0, 10).map(item =>
                                '<tr>' +
                                '<td>' +
                                '<strong>' + (item.source || 'Unknown') + '</strong><br>' +
                                '<small class="text-muted">' + (item.title?.substring(0, 50) || '') + '...</small>' +
                                '</td>' +
                                '<td>' +
                                '<span class="h6 text-success">' + (item.price_formatted || item.price || 'N/A') + '</span>' +
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
                ` : ''}
            </div>

            <!-- SEO Analysis Section -->
            <div class="mb-4">
                <h5 class="text-warning mb-3"><i class="fas fa-search-plus me-2"></i>SEO Analysis</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-heading text-primary me-2"></i>Title Suggestions</h6>
                        <div class="suggestions-list">
                            ${data.suggested_titles?.length ? data.suggested_titles.slice(0, 6).map(title =>
                                '<div class="suggestion-item border rounded p-2 mb-2">' +
                                '<div class="d-flex justify-content-between align-items-center">' +
                                '<span>' + title + '</span>' +
                                '<button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(\'' + title.replace(/'/g, "\\'") + '\')">' +
                                '<i class="fas fa-copy"></i>' +
                                '</button>' +
                                '</div>' +
                                '<small class="text-muted">' + title.length + ' characters</small>' +
                                '</div>'
                            ).join('') : '<div class="text-muted">No title suggestions available</div>'}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-alt text-info me-2"></i>Meta Description Suggestions</h6>
                        <div class="suggestions-list">
                            ${data.meta_descriptions?.length ? data.meta_descriptions.slice(0, 4).map(desc =>
                                '<div class="suggestion-item border rounded p-2 mb-2">' +
                                '<div class="d-flex justify-content-between align-items-start">' +
                                '<span class="flex-grow-1">' + desc + '</span>' +
                                '<button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard(\'' + desc.replace(/'/g, "\\'") + '\')">' +
                                '<i class="fas fa-copy"></i>' +
                                '</button>' +
                                '</div>' +
                                '<small class="text-muted">' + desc.length + ' characters</small>' +
                                '</div>'
                            ).join('') : '<div class="text-muted">No description suggestions available</div>'}
                        </div>
                    </div>
                </div>
                
                ${data.seo_insights?.common_keywords?.length ? `
                <div class="mt-3">
                    <h6><i class="fas fa-tags text-success me-2"></i>Common Keywords Found</h6>
                    <div class="keyword-tags">
                        ${data.seo_insights.common_keywords.slice(0, 15).map(keyword =>
                            '<span class="badge bg-light text-dark me-1 mb-1">' + keyword + '</span>'
                        ).join('')}
                    </div>
                </div>
                ` : ''}
            </div>

            <!-- Competitor Analysis Section -->
            <div class="mb-4">
                <h5 class="text-info mb-3"><i class="fas fa-users me-2"></i>Competitor Analysis</h5>
                ${data.search_results?.length ? `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.search_results.slice(0, 10).map(result => {
                                const isBangladeshi = result.is_bangladeshi || false;
                                const rowClass = isBangladeshi ? 'table-success' : '';
                                const locationBadge = isBangladeshi ? '<span class="badge bg-success">🇧🇩 Bangladesh</span>' : '<span class="badge bg-secondary">Global</span>';
                                
                                return '<tr class="' + rowClass + '">' +
                                    '<td><strong>' + (result.domain || 'Unknown') + '</strong></td>' +
                                    '<td>' + (result.title ? result.title.substring(0, 60) + '...' : 'No title') + '</td>' +
                                    '<td>' + locationBadge + '</td>' +
                                    '<td>' +
                                    (result.link ?
                                        '<a href="' + result.link + '" target="_blank" class="btn btn-sm btn-outline-primary">' +
                                        '<i class="fas fa-external-link-alt"></i> Visit' +
                                        '</a>'
                                        : '<small class="text-muted">No link</small>') +
                                    '</td>' +
                                    '</tr>';
                            }).join('')}
                        </tbody>
                    </table>
                </div>
                ` : '<div class="alert alert-info">No competitor data available</div>'}
            </div>

            <!-- Profit Calculator Section -->
            ${data.profit_calculator?.cost_scenarios?.length ? `
            <div class="mb-4">
                <h5 class="text-success mb-3"><i class="fas fa-calculator me-2"></i>Profit Analysis</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Scenario</th>
                                <th>Product Cost</th>
                                <th>Total Costs</th>
                                <th>Suggested Price</th>
                                <th>Profit</th>
                                <th>Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.profit_calculator.cost_scenarios.map(scenario =>
                                '<tr>' +
                                '<td><strong>' + (scenario.scenario || 'Standard') + '</strong></td>' +
                                '<td>৳' + (scenario.product_cost || 0).toFixed(2) + '</td>' +
                                '<td>৳' + (scenario.total_cost || 0).toFixed(2) + '</td>' +
                                '<td class="text-primary">৳' + (scenario.suggested_price || 0).toFixed(2) + '</td>' +
                                '<td class="text-success">৳' + (scenario.profit || 0).toFixed(2) + '</td>' +
                                '<td><span class="badge bg-success">' + (scenario.margin_percentage || 0).toFixed(1) + '%</span></td>' +
                                '</tr>'
                            ).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}

            <!-- Social Media Targeting Section -->
            ${data.social_media_targeting ? `
            <div class="mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-share-alt me-2"></i>Social Media Targeting</h5>
                <div class="row">
                    ${data.social_media_targeting.facebook_instagram ? `
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fab fa-facebook me-2"></i>Facebook & Instagram</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Target Demographics:</strong>
                                    <ul class="list-unstyled mt-2">
                                        <li><i class="fas fa-users me-2"></i>Age: ${data.social_media_targeting.facebook_instagram.target_demographics?.age_range || 'N/A'}</li>
                                        <li><i class="fas fa-venus-mars me-2"></i>Gender: ${data.social_media_targeting.facebook_instagram.target_demographics?.gender || 'N/A'}</li>
                                        <li><i class="fas fa-dollar-sign me-2"></i>Income: $${data.social_media_targeting.facebook_instagram.target_demographics?.income || 'N/A'}</li>
                                    </ul>
                                </div>
                                <div class="text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-success">$${data.social_media_targeting.facebook_instagram.estimated_cpc || 0}</div>
                                            <small class="text-muted">Est. CPC</small>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-info">${data.social_media_targeting.facebook_instagram.conversion_rate || 0}%</div>
                                            <small class="text-muted">Conv. Rate</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            <!-- Competition Metrics Section -->
            ${data.competition_metrics ? `
            <div class="mb-4">
                <h5 class="text-danger mb-3"><i class="fas fa-chart-bar me-2"></i>Competition Metrics</h5>
                <div class="row">
                    ${data.competition_metrics.market_concentration ? `
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie text-primary me-2"></i>Market Concentration</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="h4 text-warning">${data.competition_metrics.market_concentration.top_3_share || 'N/A'}</div>
                                        <small class="text-muted">Top 3 Share</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h4 text-info">${data.competition_metrics.market_concentration.hhi_index || 'N/A'}</div>
                                        <small class="text-muted">HHI Index</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h6 text-secondary">${data.competition_metrics.market_concentration.market_type || 'N/A'}</div>
                                        <small class="text-muted">Market Type</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.competition_metrics.entry_barriers ? `
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-shield-alt text-danger me-2"></i>Entry Barriers</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Capital Requirements:</span>
                                        <span class="badge bg-${data.competition_metrics.entry_barriers.capital_requirements === 'Low' ? 'success' : data.competition_metrics.entry_barriers.capital_requirements === 'Medium' ? 'warning' : 'danger'}">${data.competition_metrics.entry_barriers.capital_requirements || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Brand Loyalty:</span>
                                        <span class="badge bg-${data.competition_metrics.entry_barriers.brand_loyalty === 'Low' ? 'success' : data.competition_metrics.entry_barriers.brand_loyalty === 'Medium' ? 'warning' : 'danger'}">${data.competition_metrics.entry_barriers.brand_loyalty || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Overall Difficulty:</span>
                                        <span class="badge bg-${data.competition_metrics.entry_barriers.overall_difficulty === 'Low' ? 'success' : data.competition_metrics.entry_barriers.overall_difficulty === 'Medium' ? 'warning' : 'danger'}">${data.competition_metrics.entry_barriers.overall_difficulty || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            <!-- Product Images Section -->
            ${data.product_images?.length ? `
            <div class="mb-4">
                <h5 class="text-secondary mb-3"><i class="fas fa-images me-2"></i>Product Images</h5>
                <div class="row">
                    ${data.product_images.slice(0, 8).map(image =>
                        '<div class="col-md-3 mb-3">' +
                        '<div class="card">' +
                        '<img src="' + image.url + '" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Product Image">' +
                        '<div class="card-body p-2">' +
                        '<small class="text-muted"><strong>' + (image.source || 'Unknown') + '</strong></small><br>' +
                        '<small>' + (image.price || 'N/A') + '</small>' +
                        '</div>' +
                        '</div>' +
                        '</div>'
                    ).join('')}
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> These images are from competitor websites. Use for inspiration and ensure you have proper rights before using.
                </div>
            </div>
            ` : ''}

            <!-- Complete Research Data Debug -->
            <div class="mt-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-code"></i> Complete Research Data (Debug)</h6>
                    </div>
                    <div class="card-body">
                        <small><pre>${JSON.stringify(data, null, 2)}</pre></small>
                    </div>
                </div>
            </div>
        `;
        
        overviewDiv.innerHTML = html;
        console.log('Comprehensive overview rendered successfully with all research data');
    };

=======
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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