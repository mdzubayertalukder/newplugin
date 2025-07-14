<div class="product-details">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-5">
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
        <div class="col-md-7">
            <div class="product-info">
                <!-- Store Badge -->
                <div class="mb-3">
                    <span class="badge bg-primary">{{ $product->store_name }}</span>
                    @if($product->stock_quantity > 0)
                    <span class="badge bg-success">In Stock ({{ $product->stock_quantity }})</span>
                    @else
                    <span class="badge bg-warning">Out of Stock</span>
                    @endif
                </div>

                <!-- Product Name -->
                <h4 class="fw-bold text-dark mb-3">{{ $product->name }}</h4>

                <!-- Pricing -->
                <div class="pricing-info mb-3">
                    <div class="row">
                        <div class="col-6">
                            <div class="price-card border rounded p-2 text-center">
                                <small class="text-muted">Your Cost</small>
                                <div class="h5 mb-0">{{ $product->currency_symbol }}{{ number_format(floatval($product->regular_price), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="price-card border rounded p-2 text-center">
                                <small class="text-muted">Suggested Sale Price</small>
                                <div class="h5 mb-0 text-success">{{ $product->currency_symbol }}{{ number_format(floatval($product->regular_price) * 1.7, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="product-stats mb-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="h6 mb-0">{{ strlen($product->description) }}</div>
                                <small class="text-muted">Characters</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="h6 mb-0">{{ count($images) }}</div>
                                <small class="text-muted">Images</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="h6 mb-0" id="categoryInfo">General</div>
                                <small class="text-muted">Category</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Research Button -->
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="startProductResearch()">
                        <i class="fas fa-search me-2"></i>Start Market Research
                        <div class="spinner-border spinner-border-sm ms-2 d-none" id="researchSpinner" role="status"></div>
                    </button>
                    <small class="text-muted">Get comprehensive dropshipping analysis for this product</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Research Results -->
    <div id="researchResults" class="research-results mt-4" style="display: none;">
        <!-- Dropshipping Viability Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dropshipping Viability Analysis</h5>
            </div>
            <div class="card-body" id="viabilityAnalysis">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- Research Tabs -->
        <ul class="nav nav-pills nav-fill mb-3" id="researchTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-eye me-1"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="prices-tab" data-bs-toggle="pill" data-bs-target="#prices" type="button" role="tab">
                    <i class="fas fa-tags me-1"></i>Price Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="competitors-tab" data-bs-toggle="pill" data-bs-target="#competitors" type="button" role="tab">
                    <i class="fas fa-users me-1"></i>Competitors
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="images-tab" data-bs-toggle="pill" data-bs-target="#images" type="button" role="tab">
                    <i class="fas fa-images me-1"></i>Product Images
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="seo-tab" data-bs-toggle="pill" data-bs-target="#seo" type="button" role="tab">
                    <i class="fas fa-search-plus me-1"></i>SEO Insights
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="researchTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Market Summary</h6>
                            </div>
                            <div class="card-body" id="overviewContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>Click "Start Market Research" to view comprehensive market analysis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Quick Stats</h6>
                            </div>
                            <div class="card-body" id="quickStats">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                    <p>Market data will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Analysis Tab -->
            <div class="tab-pane fade" id="prices" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Competitor Price Analysis</h6>
                    </div>
                    <div class="card-body" id="priceAnalysisContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                            <p>Price comparison data will appear here after research</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Competitors Tab -->
            <div class="tab-pane fade" id="competitors" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Detailed Competitor Analysis</h6>
                    </div>
                    <div class="card-body" id="competitorAnalysisContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-building fa-2x mb-2"></i>
                            <p>Competitor information will appear here after research</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Images Tab -->
            <div class="tab-pane fade" id="images" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Product Images from Market Research</h6>
                    </div>
                    <div class="card-body" id="productImagesContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-camera fa-2x mb-2"></i>
                            <p>Market product images will appear here after research</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Insights Tab -->
            <div class="tab-pane fade" id="seo" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">SEO Optimization Suggestions</h6>
                    </div>
                    <div class="card-body" id="seoAnalysisContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-lightbulb fa-2x mb-2"></i>
                            <p>SEO recommendations will appear here after research</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Description -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Product Description</h6>
        </div>
        <div class="card-body">
            <div class="description-content" style="max-height: 200px; overflow-y: auto;">
                {!! $product->description !!}
            </div>
        </div>
    </div>
</div>

<style>
.pricing-info .price-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transition: all 0.3s ease;
}

.pricing-info .price-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-stats .stat-item {
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    margin: 0 2px;
}

.research-results .card {
    border: none !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.viability-excellent { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.viability-good { background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); }
.viability-fair { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
.viability-poor { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); }

.competitor-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.competitor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.price-range-bar {
    height: 8px;
    background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
    border-radius: 4px;
    position: relative;
}

.price-indicator {
    position: absolute;
    top: -2px;
    width: 12px;
    height: 12px;
    background: #007bff;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.research-image {
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.research-image:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
</style>