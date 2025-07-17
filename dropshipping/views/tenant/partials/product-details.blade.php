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
<<<<<<< HEAD
                                <div class="h5 mb-0">৳{{ number_format(floatval($product->regular_price), 2) }}</div>
=======
                                <div class="h5 mb-0">{{ $product->currency_symbol }}{{ number_format(floatval($product->regular_price), 2) }}</div>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="price-card border rounded p-2 text-center">
                                <small class="text-muted">Suggested Sale Price</small>
<<<<<<< HEAD
                                <div class="h5 mb-0 text-success">৳{{ number_format(floatval($product->regular_price) * 1.7, 2) }}</div>
=======
                                <div class="h5 mb-0 text-success">{{ $product->currency_symbol }}{{ number_format(floatval($product->regular_price) * 1.7, 2) }}</div>
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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

<<<<<<< HEAD
        <!-- Comprehensive Research Overview - All Information in One Place -->
        <div class="comprehensive-research-overview">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Complete Market Research Overview</h5>
                </div>
                <div class="card-body" id="comprehensiveOverview">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>Click "Start Market Research" to view comprehensive market analysis</p>
                        <small>All research data will be displayed here in one comprehensive view</small>
=======
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
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
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
<<<<<<< HEAD

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

/* Fix tab content display issues */
#researchTabsContent .tab-pane {
    display: none !important;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#researchTabsContent .tab-pane.active {
    display: block !important;
    opacity: 1;
}

#researchTabsContent .tab-pane.show.active {
    display: block !important;
    opacity: 1;
}

/* Ensure tab content is visible */
.tab-content {
    min-height: 300px;
}

/* Visual feedback for active tabs */
.nav-pills .nav-link.active {
    background-color: #007bff !important;
    color: white !important;
    border-color: #007bff !important;
}

/* Ensure card bodies are visible */
.card-body {
    min-height: 100px;
    position: relative;
}
</style>

<script>
// Enhanced tab functionality with proper initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs immediately when DOM is ready
    initializeResearchTabs();
    
    // Also initialize when research results become visible
    const researchResults = document.getElementById('researchResults');
    if (researchResults) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const target = mutation.target;
                    if (target.style.display !== 'none') {
                        // Research results are now visible, reinitialize tabs
                        setTimeout(initializeResearchTabs, 100);
                    }
                }
            });
        });
        
        observer.observe(researchResults, {
            attributes: true,
            attributeFilter: ['style']
        });
    }
});

function initializeResearchTabs() {
    console.log('Initializing research tabs...');
    
    // Remove any existing event listeners to prevent duplicates
    const tabButtons = document.querySelectorAll('#researchTabs button[data-bs-toggle="pill"]');
    
    // Manual tab switching that always works
    tabButtons.forEach(function(button) {
        // Remove existing listeners
        button.removeEventListener('click', handleTabClick);
        // Add new listener
        button.addEventListener('click', handleTabClick);
    });
    
    console.log('Initialized', tabButtons.length, 'tab buttons');
    
    // Also try Bootstrap initialization if available
    if (typeof bootstrap !== 'undefined') {
        try {
            tabButtons.forEach(function(button) {
                new bootstrap.Tab(button);
            });
            console.log('Bootstrap tabs initialized');
        } catch (e) {
            console.log('Bootstrap tab initialization failed:', e);
        }
    }
}

function handleTabClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const clickedTab = this;
    const targetId = clickedTab.getAttribute('data-bs-target');
    
    console.log('Tab clicked:', targetId, 'by', clickedTab.textContent.trim());
    
    // Remove active class from all tabs
    document.querySelectorAll('#researchTabs .nav-link').forEach(function(tab) {
        tab.classList.remove('active');
        tab.setAttribute('aria-selected', 'false');
    });
    
    // Hide all content panes first
    document.querySelectorAll('#researchTabsContent .tab-pane').forEach(function(pane) {
        pane.classList.remove('show', 'active');
        pane.style.display = 'none';
    });
    
    // Add active class to clicked tab
    clickedTab.classList.add('active');
    clickedTab.setAttribute('aria-selected', 'true');
    
    // Show corresponding content pane
    const targetPane = document.querySelector(targetId);
    if (targetPane) {
        // Force display and add classes
        targetPane.style.display = 'block';
        targetPane.classList.add('show', 'active');
        
        console.log('Successfully activated tab pane:', targetId);
        
        // Trigger any specific content loading for this tab
        triggerTabContentLoad(targetId);
        
        // Force a reflow to ensure visual update
        targetPane.offsetHeight;
        
        // Add a visible indicator that the tab is working
        const cardBody = targetPane.querySelector('.card-body');
        if (cardBody) {
            // Temporarily flash the background to show it's working
            cardBody.style.backgroundColor = '#e3f2fd';
            setTimeout(function() {
                cardBody.style.backgroundColor = '';
            }, 500);
        }
        
    } else {
        console.error('Target pane not found:', targetId);
    }
}

function triggerTabContentLoad(tabId) {
    console.log('Loading content for tab:', tabId);
    
    // Always update the content to show the tab is working
    const targetPane = document.querySelector(tabId);
    if (!targetPane) {
        console.error('Target pane not found:', tabId);
        return;
    }
    
    const cardBody = targetPane.querySelector('.card-body');
    if (!cardBody) {
        console.error('Card body not found in:', tabId);
        return;
    }
    
    // Get the tab name for display
    const tabName = tabId.replace('#', '').charAt(0).toUpperCase() + tabId.replace('#', '').slice(1);
    
    // Check if we have research data and render functions available
    if (typeof window.researchData !== 'undefined' && window.researchData) {
        console.log('Research data available, rendering content for:', tabId);
        
        // First show that the tab is active with research data
        cardBody.innerHTML = `
            <div class="alert alert-success">
                <h6><i class="fas fa-check-circle me-2"></i>${tabName} Tab Active with Research Data!</h6>
                <p class="mb-0">Loading ${tabName.toLowerCase()} analysis...</p>
            </div>
        `;
        
        // Then try to render the actual content
        setTimeout(function() {
            switch(tabId) {
                case '#prices':
                    if (typeof window.renderPriceAnalysis === 'function') {
                        window.renderPriceAnalysis(window.researchData);
                        console.log('Price analysis content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderPriceAnalysis function not available</div>';
                    }
                    break;
                case '#competitors':
                    if (typeof window.renderCompetitorAnalysis === 'function') {
                        window.renderCompetitorAnalysis(window.researchData);
                        console.log('Competitor analysis content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderCompetitorAnalysis function not available</div>';
                    }
                    break;
                case '#images':
                    if (typeof window.renderProductImages === 'function') {
                        window.renderProductImages(window.researchData);
                        console.log('Product images content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderProductImages function not available</div>';
                    }
                    break;
                case '#seo':
                    if (typeof window.renderSeoAnalysis === 'function') {
                        window.renderSeoAnalysis(window.researchData);
                        console.log('SEO analysis content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderSeoAnalysis function not available</div>';
                    }
                    break;
                case '#profit':
                    if (typeof window.renderProfitCalculator === 'function') {
                        window.renderProfitCalculator(window.researchData);
                        console.log('Profit calculator content rendered');
                    } else {
                        // Profit calculator has default content, don't override
                        console.log('Using default profit calculator');
                    }
                    break;
                case '#social':
                    if (typeof window.renderSocialMediaTargeting === 'function') {
                        window.renderSocialMediaTargeting(window.researchData);
                        console.log('Social media content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderSocialMediaTargeting function not available</div>';
                    }
                    break;
                case '#metrics':
                    if (typeof window.renderCompetitionMetrics === 'function') {
                        window.renderCompetitionMetrics(window.researchData);
                        console.log('Competition metrics content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderCompetitionMetrics function not available</div>';
                    }
                    break;
                default:
                    if (typeof window.renderResearchOverview === 'function') {
                        window.renderResearchOverview(window.researchData);
                        console.log('Overview content rendered');
                    } else {
                        cardBody.innerHTML += '<div class="alert alert-warning">renderResearchOverview function not available</div>';
                    }
            }
        }, 100);
        
    } else {
        console.log('No research data available for tab:', tabId);
        
        // Show clear visual feedback that the tab is working but needs research
        if (tabId !== '#profit') { // Don't override profit calculator default content
            cardBody.innerHTML = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>${tabName} Tab Successfully Activated!</h6>
                    <p>This tab is working correctly. Please run market research to see ${tabName.toLowerCase()} data.</p>
                    <button class="btn btn-primary btn-sm" onclick="document.querySelector('button[onclick=\\"startProductResearch()\\"]').click()">
                        <i class="fas fa-search me-1"></i>Start Market Research
                    </button>
                </div>
                <div class="text-center text-muted py-3">
                    <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                    <p>Waiting for research data...</p>
                </div>
            `;
        }
    }
}

// Debug function to check tab status and content
window.debugTabs = function() {
    console.log('=== TAB DEBUG INFO ===');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    
    const tabs = document.querySelectorAll('#researchTabs button');
    console.log('Tab buttons found:', tabs.length);
    tabs.forEach(function(tab, index) {
        console.log(`Tab ${index}:`, {
            text: tab.textContent.trim(),
            target: tab.getAttribute('data-bs-target'),
            active: tab.classList.contains('active')
        });
    });
    
    const panes = document.querySelectorAll('#researchTabsContent .tab-pane');
    console.log('Tab panes found:', panes.length);
    panes.forEach(function(pane, index) {
        const contentDiv = pane.querySelector('.card-body');
        console.log(`Pane ${index}:`, {
            id: pane.id,
            active: pane.classList.contains('active'),
            visible: pane.classList.contains('show'),
            hasContent: contentDiv ? contentDiv.innerHTML.length > 200 : false,
            contentPreview: contentDiv ? contentDiv.innerHTML.substring(0, 100) + '...' : 'No content div'
        });
    });
    
    // Check if research data is available
    console.log('Research data available:', typeof window.researchData !== 'undefined');
    if (typeof window.researchData !== 'undefined') {
        console.log('Research data keys:', Object.keys(window.researchData || {}));
    }
};

// Function to manually trigger content rendering for testing
window.testTabContent = function() {
    console.log('Testing tab content rendering...');
    
    // Check if we have research data
    if (typeof window.researchData === 'undefined' || !window.researchData) {
        console.log('No research data available, using demo data');
        // Use demo data for testing
        window.researchData = {
            price_analysis: { min_price: 25, max_price: 75, avg_price: 50 },
            shopping_results: [{ title: 'Test Product', source: 'Test Store', price_formatted: '$25.99' }],
            search_results: [{ title: 'Test Result', domain: 'test.com' }],
            suggested_titles: ['Test Title 1', 'Test Title 2'],
            meta_descriptions: ['Test description 1', 'Test description 2']
        };
    }
    
    // Re-render all content
    if (typeof renderPriceAnalysis === 'function') {
        console.log('Rendering price analysis...');
        renderPriceAnalysis(window.researchData);
    }
    
    if (typeof renderCompetitorAnalysis === 'function') {
        console.log('Rendering competitor analysis...');
        renderCompetitorAnalysis(window.researchData);
    }
    
    if (typeof renderSeoAnalysis === 'function') {
        console.log('Rendering SEO analysis...');
        renderSeoAnalysis(window.researchData);
    }
    
    if (typeof renderProductImages === 'function') {
        console.log('Rendering product images...');
        renderProductImages(window.researchData);
    }
    
    console.log('Content rendering test completed');
};

// Simple function to test tab switching visually
window.testTabSwitching = function() {
    console.log('Testing tab switching...');
    
    const tabs = ['#overview', '#prices', '#competitors', '#images', '#seo', '#profit', '#social', '#metrics'];
    let currentIndex = 0;
    
    function switchToNextTab() {
        if (currentIndex >= tabs.length) {
            console.log('Tab switching test completed');
            return;
        }
        
        const tabId = tabs[currentIndex];
        const tabButton = document.querySelector(`button[data-bs-target="${tabId}"]`);
        
        if (tabButton) {
            console.log('Switching to tab:', tabId);
            tabButton.click();
            
            // Verify the tab is visible
            setTimeout(function() {
                const targetPane = document.querySelector(tabId);
                if (targetPane && targetPane.classList.contains('active')) {
                    console.log('✓ Tab', tabId, 'is now active and visible');
                } else {
                    console.log('✗ Tab', tabId, 'failed to activate');
                }
                
                currentIndex++;
                setTimeout(switchToNextTab, 1000); // Wait 1 second before next tab
            }, 200);
        } else {
            console.log('Tab button not found for:', tabId);
            currentIndex++;
            switchToNextTab();
        }
    }
    
    switchToNextTab();
};

// Profit Calculator Function
window.calculateProfit = function() {
    const productCost = parseFloat(document.getElementById('productCost').value) || 0;
    const markupPercentage = parseFloat(document.getElementById('markupPercentage').value) || 0;
    const additionalCosts = parseFloat(document.getElementById('additionalCosts').value) || 0;
    
    // Calculate selling price
    const sellingPrice = productCost * (1 + markupPercentage / 100);
    
    // Calculate total costs
    const totalCosts = productCost + additionalCosts;
    
    // Calculate profit
    const totalProfit = sellingPrice - totalCosts;
    
    // Calculate profit margin (profit / selling price)
    const profitMargin = sellingPrice > 0 ? (totalProfit / sellingPrice) * 100 : 0;
    
    // Calculate ROI (profit / total investment)
    const roi = totalCosts > 0 ? (totalProfit / totalCosts) * 100 : 0;
    
    // Update display
    document.getElementById('sellingPrice').textContent = '৳' + sellingPrice.toFixed(2);
    document.getElementById('totalProfit').textContent = '৳' + totalProfit.toFixed(2);
    document.getElementById('profitMargin').textContent = profitMargin.toFixed(1) + '%';
    document.getElementById('roi').textContent = roi.toFixed(1) + '%';
    
    // Add visual feedback
    const profitElement = document.getElementById('totalProfit');
    const marginElement = document.getElementById('profitMargin');
    
    // Color coding based on profit margin
    if (profitMargin >= 40) {
        profitElement.className = 'h4 text-success';
        marginElement.className = 'h5 text-success';
    } else if (profitMargin >= 25) {
        profitElement.className = 'h4 text-warning';
        marginElement.className = 'h5 text-warning';
    } else {
        profitElement.className = 'h4 text-danger';
        marginElement.className = 'h5 text-danger';
    }
};

// Auto-calculate when inputs change
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['productCost', 'markupPercentage', 'additionalCosts'];
    inputs.forEach(function(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', calculateProfit);
        }
    });
});
// Product Research Functions
let researchData = null;

window.startProductResearch = function() {
    // Get product ID from stored global variable
    const productId = window.currentProductId;
    
    if (!productId) {
        alert('No product selected for research');
        return;
    }
    
    console.log('Starting market research for product ID:', productId);
    
    const btn = document.querySelector('button[onclick="startProductResearch()"]');
    const spinner = document.getElementById('researchSpinner');
    
    if (btn) {
        // Show loading state  
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Searching Websites...';
        if (spinner) spinner.classList.remove('d-none');
    }

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('meta[name="_token"]')?.getAttribute('content') || 
                     '{{ csrf_token() }}';

    if (!csrfToken) {
        console.error('CSRF token not found');
        showSearchError('Security token not found. Please refresh the page.');
        return;
    }

    // Get product name for search
    const productName = document.querySelector('h4.fw-bold.text-dark')?.textContent?.trim() || 'Product';

    // Perform product search to get top 50 websites
    fetch(`/dropshipping/research/search`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            query: productName
        })
    })
    .then(response => {
        console.log('Search response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Search response data:', data);
        
        if (data.success) {
            // Show the research results section
            document.getElementById('researchResults').style.display = 'block';
            
            // Render the website search results
            renderWebsiteSearchResults(data.data);
            
            if (btn) {
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Refresh Search';
                btn.disabled = false;
            }
            if (spinner) spinner.classList.add('d-none');
        } else {
            console.error('Search failed:', data.message);
            
            // Check if it's a limit reached error
            if (data.limit_reached) {
                showLimitReachedError(data.message, data.upgrade_message);
            } else {
                showSearchError(data.message || 'Search failed');
            }
            
            if (btn) {
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
                btn.disabled = false;
            }
            if (spinner) spinner.classList.add('d-none');
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        let errorMessage = 'Search failed. Please try again.';
        
        if (error.message) {
            errorMessage = `Search failed: ${error.message}`;
        }
        
        showSearchError(errorMessage);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-search me-2"></i>Start Market Research';
            btn.disabled = false;
        }
        if (spinner) spinner.classList.add('d-none');
    });
};

// Function to render website search results
window.renderWebsiteSearchResults = function(searchData) {
    const viabilityDiv = document.getElementById('viabilityAnalysis');
    const overviewDiv = document.getElementById('comprehensiveOverview');
    
    const websites = searchData.websites || [];
    const searchSummary = searchData.search_summary || {};
    
    // Update viability section with search summary
    viabilityDiv.innerHTML = `
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success">
                    <h5><i class="fas fa-search me-2"></i>Market Research Complete!</h5>
                    <p class="mb-2">Found <strong>${searchData.total_websites}</strong> websites selling "${searchData.query}"</p>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 rounded">
                                <div class="h4 text-primary">${searchSummary.total_sites_searched || 0}</div>
                                <small class="text-muted">Sites Searched</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 rounded">
                                <div class="h4 text-success">${searchSummary.sites_with_results || 0}</div>
                                <small class="text-muted">Sites with Results</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 rounded">
                                <div class="h4 text-info">${searchSummary.total_products_found || 0}</div>
                                <small class="text-muted">Products Found</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 rounded">
                                <div class="h4 text-warning">${websites.filter(w => w.is_bangladeshi).length}</div>
                                <small class="text-muted">BD Websites</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Render the comprehensive website list
    overviewDiv.innerHTML = `
        <div class="website-search-results">
            <h5 class="text-primary mb-3">
                <i class="fas fa-globe me-2"></i>Top ${websites.length} Websites with Product Links
            </h5>
            
            ${websites.length > 0 ? `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Website</th>
                            <th width="25%">Product</th>
                            <th width="15%">Price</th>
                            <th width="10%">Location</th>
                            <th width="10%">Score</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${websites.map(website => {
                            const locationBadge = website.is_bangladeshi 
                                ? '<span class="badge bg-success">🇧🇩 BD</span>' 
                                : '<span class="badge bg-secondary">Global</span>';
                            
                            const scoreColor = website.relevance_score >= 80 ? 'success' : 
                                             website.relevance_score >= 60 ? 'warning' : 'secondary';
                            
                            return `
                                <tr class="${website.is_bangladeshi ? 'table-success' : ''}">
                                    <td><strong>${website.rank}</strong></td>
                                    <td>
                                        <div>
                                            <strong>${website.site_name}</strong>
                                            <br>
                                            <small class="text-muted">${website.domain}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>${website.product_name.substring(0, 40)}${website.product_name.length > 40 ? '...' : ''}</strong>
                                            ${website.description ? `<br><small class="text-muted">${website.description.substring(0, 80)}${website.description.length > 80 ? '...' : ''}</small>` : ''}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold">${website.price}</span>
                                    </td>
                                    <td>${locationBadge}</td>
                                    <td>
                                        <span class="badge bg-${scoreColor}">${website.relevance_score}%</span>
                                    </td>
                                    <td>
                                        <a href="${website.product_link}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> Visit
                                        </a>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb me-2"></i>Market Research Insights</h6>
                    <ul class="mb-0">
                        <li><strong>Bangladeshi websites</strong> are prioritized and highlighted in green</li>
                        <li><strong>Relevance scores</strong> indicate how well the product matches your search</li>
                        <li><strong>Click "Visit"</strong> to see the actual product listings on each website</li>
                        <li><strong>Compare prices</strong> across different platforms to find the best deals</li>
                        <li><strong>Focus on high-scoring BD sites</strong> for better local market penetration</li>
                    </ul>
                </div>
            </div>
            ` : `
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>No websites found</h5>
                <p>Try searching for a different product or check your search configuration.</p>
            </div>
            `}
        </div>
    `;
};

// Function to show search errors
window.showSearchError = function(message) {
    const viabilityDiv = document.getElementById('viabilityAnalysis');
    const overviewDiv = document.getElementById('comprehensiveOverview');
    
    if (viabilityDiv) {
        viabilityDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Search Failed</h5>
                <p class="mb-0">${message}</p>
            </div>
        `;
    }
    
    if (overviewDiv) {
        overviewDiv.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i>
                <h5>Search Error</h5>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="startProductResearch()">
                    <i class="fas fa-redo me-2"></i>Try Again
                </button>
            </div>
        `;
    }
    
    // Show the research results section even on error
    const resultsSection = document.getElementById('researchResults');
    if (resultsSection) {
        resultsSection.style.display = 'block';
    }
};

// Function to show limit reached errors (for compatibility)
window.showLimitReachedError = function(message, upgradeMessage) {
    const viabilityDiv = document.getElementById('viabilityAnalysis');
    const overviewDiv = document.getElementById('comprehensiveOverview');
    
    if (viabilityDiv) {
        viabilityDiv.innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Usage Limit Reached</h5>
                <p class="mb-2">${message}</p>
                ${upgradeMessage ? `<p class="mb-0"><small>${upgradeMessage}</small></p>` : ''}
            </div>
        `;
    }
    
    if (overviewDiv) {
        overviewDiv.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-lock fa-3x mb-3 text-warning"></i>
                <h5>Usage Limit Reached</h5>
                <p>${message}</p>
                ${upgradeMessage ? `<p><small class="text-muted">${upgradeMessage}</small></p>` : ''}
            </div>
        `;
    }
    
    // Show the research results section
    const resultsSection = document.getElementById('researchResults');
    if (resultsSection) {
        resultsSection.style.display = 'block';
    }
};
</script>
=======

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
>>>>>>> 9e8aaee60d86aca05b60ddc02aee8cd96e018395
