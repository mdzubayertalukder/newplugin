<?php

use Illuminate\Support\Facades\Route;
use Plugin\Dropshipping\Http\Controllers\Tenant\DropshippingTenantController;
use Plugin\Dropshipping\Http\Controllers\Tenant\ProductImportController;
use Plugin\Dropshipping\Http\Controllers\Tenant\OrderManagementController;
use Plugin\Dropshipping\Http\Controllers\Tenant\WithdrawalController;
use Plugin\Dropshipping\Http\Controllers\Tenant\ProductResearchController;

// Simple tenant routes for dropshipping
Route::group(['prefix' => 'user', 'as' => 'user.dropshipping.', 'middleware' => ['auth']], function () {

    // Main products page - shows all available products
    Route::get('/dropshipping-products', [DropshippingTenantController::class, 'allProducts'])->name('products');

    // Import single product
    Route::post('/dropshipping-products/import/{productId}', [ProductImportController::class, 'importSingle'])->name('import.single');

    // Import history
    Route::get('/dropshipping-history', [ProductImportController::class, 'history'])->name('history');

    // Dashboard (optional)
    Route::get('/dropshipping-dashboard', [DropshippingTenantController::class, 'dashboard'])->name('dashboard');

    // Product Research Routes (Serper Integration)
    Route::prefix('dropshipping/research')->as('research.')->group(function () {
        Route::post('/product/{productId}', [ProductResearchController::class, 'researchProduct'])->name('product');
        Route::post('/price-comparison/{productId}', [ProductResearchController::class, 'priceComparison'])->name('price.comparison');
        Route::post('/seo-analysis/{productId}', [ProductResearchController::class, 'seoAnalysis'])->name('seo.analysis');
        Route::post('/competitor-analysis/{productId}', [ProductResearchController::class, 'competitorAnalysis'])->name('competitor.analysis');
        Route::post('/search', [ProductResearchController::class, 'searchProduct'])->name('search');
    });

    // Order Management Routes
    Route::prefix('dropshipping/orders')->as('orders.')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index'])->name('index');
        Route::get('/create', [OrderManagementController::class, 'create'])->name('create');
        Route::post('/', [OrderManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [OrderManagementController::class, 'show'])->name('show');
        Route::post('/{id}/cancel', [OrderManagementController::class, 'cancel'])->name('cancel');
        Route::get('/product-details/{productId}', [OrderManagementController::class, 'getProductDetails'])->name('product.details');
    });

    // Withdrawal Routes
    Route::prefix('dropshipping/withdrawals')->as('withdrawals.')->group(function () {
        Route::get('/', [WithdrawalController::class, 'index'])->name('index');
        Route::get('/create', [WithdrawalController::class, 'create'])->name('create');
        Route::post('/', [WithdrawalController::class, 'store'])->name('store');
        Route::get('/{id}', [WithdrawalController::class, 'show'])->name('show');
        Route::post('/{id}/cancel', [WithdrawalController::class, 'cancel'])->name('cancel');
        Route::get('/info/ajax', [WithdrawalController::class, 'getWithdrawalInfo'])->name('info');
        Route::post('/calculate-fee', [WithdrawalController::class, 'calculateFee'])->name('calculate.fee');
    });
});

// Alternative routes without prefix for easier access
Route::group(['as' => 'dropshipping.', 'middleware' => ['auth']], function () {

    // Direct access routes
    Route::get('/dropshipping', [DropshippingTenantController::class, 'allProducts'])->name('products.all');
    Route::get('/dropshipping/all-products', [DropshippingTenantController::class, 'allProducts'])->name('products');
    Route::get('/dropshipping/my-products', [DropshippingTenantController::class, 'myProducts'])->name('my.products');
    Route::post('/dropshipping/import/{productId}', [ProductImportController::class, 'importSingle'])->name('import.product');
    Route::get('/dropshipping/history', [ProductImportController::class, 'history'])->name('import.history');
    Route::get('/dropshipping/product-details/{productId}', [DropshippingTenantController::class, 'getProductDetails'])->name('product.details');

    // Product Research (Direct Access)
    Route::prefix('dropshipping/research')->as('research.')->group(function () {
        Route::post('/product/{productId}', [ProductResearchController::class, 'researchProduct'])->name('product.direct');
        Route::post('/price-comparison/{productId}', [ProductResearchController::class, 'priceComparison'])->name('price.comparison.direct');
        Route::post('/seo-analysis/{productId}', [ProductResearchController::class, 'seoAnalysis'])->name('seo.analysis.direct');
        Route::post('/competitor-analysis/{productId}', [ProductResearchController::class, 'competitorAnalysis'])->name('competitor.analysis.direct');
        Route::post('/search', [ProductResearchController::class, 'searchProduct'])->name('search.direct');
    });

    // Order Management (Direct Access)
    Route::prefix('dropshipping')->group(function () {
        Route::get('/order-management', [OrderManagementController::class, 'index'])->name('order.management');
        Route::get('/order-management/create', [OrderManagementController::class, 'create'])->name('order.create');
        Route::post('/order-management', [OrderManagementController::class, 'store'])->name('order.store');
        Route::get('/order-management/{id}', [OrderManagementController::class, 'show'])->name('order.show');

        // Withdrawals (Direct Access)
        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals');
        Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawal.create');
        Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawal.store');
    });
});
