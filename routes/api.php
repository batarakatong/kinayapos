<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Branch & user-branch
    Route::get('/branches', [App\Http\Controllers\BranchController::class, 'index']);

    // Products & stock
    Route::apiResource('products', App\Http\Controllers\ProductController::class)->middleware('branch');
    Route::get('stocks', [App\Http\Controllers\StockController::class, 'index'])->middleware('branch');
    Route::post('stocks/adjust', [App\Http\Controllers\StockController::class, 'adjust'])->middleware('branch');

    // Sales & payments
    Route::apiResource('sales', App\Http\Controllers\SaleController::class)->middleware('branch');
    Route::post('sales/{sale}/pay', [App\Http\Controllers\PaymentController::class, 'pay'])->middleware('branch');
    Route::post('payments/qris', [App\Http\Controllers\PaymentController::class, 'createQris'])->middleware('branch');
    Route::get('payments/{payment}/status', [App\Http\Controllers\PaymentController::class, 'status'])->middleware('branch');

    // Purchases (CRUD + pay instalment)
    Route::apiResource('purchases', App\Http\Controllers\PurchaseController::class)->middleware('branch');
    Route::post('purchases/{purchase}/pay', [App\Http\Controllers\PurchaseController::class, 'pay'])->middleware('branch');

    // Customers & suppliers
    Route::apiResource('customers', App\Http\Controllers\CustomerController::class)->middleware('branch');
    Route::apiResource('suppliers', App\Http\Controllers\SupplierController::class)->middleware('branch');

    // Receivables & payables
    Route::apiResource('receivables', App\Http\Controllers\ReceivableController::class)->middleware('branch');
    Route::post('receivables/{receivable}/pay', [App\Http\Controllers\ReceivableController::class, 'pay'])->middleware('branch');
    Route::apiResource('payables', App\Http\Controllers\PayableController::class)->middleware('branch');
    Route::post('payables/{payable}/pay', [App\Http\Controllers\PayableController::class, 'pay'])->middleware('branch');

    // Reports
    Route::get('reports/sales', [App\Http\Controllers\ReportController::class, 'sales'])->middleware('branch');
    Route::get('reports/stocks', [App\Http\Controllers\ReportController::class, 'stocks'])->middleware('branch');
    Route::get('reports/purchases', [App\Http\Controllers\ReportController::class, 'purchases'])->middleware('branch');
    Route::get('reports/receivables', [App\Http\Controllers\ReportController::class, 'receivables'])->middleware('branch');
    Route::get('reports/payables', [App\Http\Controllers\ReportController::class, 'payables'])->middleware('branch');

    // Sync
    Route::post('sync/push', [App\Http\Controllers\SyncController::class, 'push'])->middleware('branch');
    Route::get('sync/pull', [App\Http\Controllers\SyncController::class, 'pull'])->middleware('branch');
});

// Public endpoints (auth, health, payment callbacks)
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/payments/callback/midtrans', [App\Http\Controllers\PaymentCallbackController::class, 'midtrans']);

