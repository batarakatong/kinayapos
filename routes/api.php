<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Branch & user-branch
    Route::get('/branches', [App\Http\Controllers\BranchController::class, 'index']);

    // Products & stock
    Route::apiResource('products', App\Http\Controllers\ProductController::class)->middleware('branch');
    Route::post('products/{product}/image', [App\Http\Controllers\ProductController::class, 'uploadImage'])->middleware('branch');
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

    // Notifications for branch (accessible by all authenticated users)
    Route::get('notifications/branch/{branch}', [App\Http\Controllers\Admin\NotificationController::class, 'forBranch']);
    Route::post('notifications/{notification}/read', [App\Http\Controllers\Admin\NotificationController::class, 'markReadByBranch']);
});

// Public endpoints (auth, health, payment callbacks)
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/payments/callback/midtrans', [App\Http\Controllers\PaymentCallbackController::class, 'midtrans']);

// ── Admin Panel (super_admin only) ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('admin')->group(function () {

    // Users — CRUD + assign/remove branch
    Route::get('users', [App\Http\Controllers\Admin\UserAdminController::class, 'index']);
    Route::post('users', [App\Http\Controllers\Admin\UserAdminController::class, 'store']);
    Route::get('users/{user}', [App\Http\Controllers\Admin\UserAdminController::class, 'show']);
    Route::put('users/{user}', [App\Http\Controllers\Admin\UserAdminController::class, 'update']);
    Route::delete('users/{user}', [App\Http\Controllers\Admin\UserAdminController::class, 'destroy']);
    Route::post('users/{user}/branches', [App\Http\Controllers\Admin\UserAdminController::class, 'assignBranch']);
    Route::delete('users/{user}/branches/{branchId}', [App\Http\Controllers\Admin\UserAdminController::class, 'removeBranch']);

    // Branches — CRUD + toggle active
    Route::get('branches', [App\Http\Controllers\Admin\BranchAdminController::class, 'index']);
    Route::post('branches', [App\Http\Controllers\Admin\BranchAdminController::class, 'store']);
    Route::get('branches/{branch}', [App\Http\Controllers\Admin\BranchAdminController::class, 'show']);
    Route::put('branches/{branch}', [App\Http\Controllers\Admin\BranchAdminController::class, 'update']);
    Route::delete('branches/{branch}', [App\Http\Controllers\Admin\BranchAdminController::class, 'destroy']);
    Route::patch('branches/{branch}/toggle', [App\Http\Controllers\Admin\BranchAdminController::class, 'toggle']);

    // Billing
    Route::get('billings', [App\Http\Controllers\Admin\BillingController::class, 'index']);
    Route::post('billings', [App\Http\Controllers\Admin\BillingController::class, 'store']);
    Route::get('billings/{billing}', [App\Http\Controllers\Admin\BillingController::class, 'show']);
    Route::put('billings/{billing}', [App\Http\Controllers\Admin\BillingController::class, 'update']);
    Route::delete('billings/{billing}', [App\Http\Controllers\Admin\BillingController::class, 'destroy']);
    Route::patch('billings/{billing}/pay', [App\Http\Controllers\Admin\BillingController::class, 'markPaid']);

    // Notifications
    Route::get('notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::post('notifications', [App\Http\Controllers\Admin\NotificationController::class, 'store']);
    Route::get('notifications/{notification}', [App\Http\Controllers\Admin\NotificationController::class, 'show']);
    Route::put('notifications/{notification}', [App\Http\Controllers\Admin\NotificationController::class, 'update']);
    Route::delete('notifications/{notification}', [App\Http\Controllers\Admin\NotificationController::class, 'destroy']);

    // SMTP Settings
    Route::get('smtp', [App\Http\Controllers\Admin\SmtpController::class, 'index']);
    Route::post('smtp', [App\Http\Controllers\Admin\SmtpController::class, 'store']);
    Route::get('smtp/{smtp}', [App\Http\Controllers\Admin\SmtpController::class, 'show']);
    Route::put('smtp/{smtp}', [App\Http\Controllers\Admin\SmtpController::class, 'update']);
    Route::delete('smtp/{smtp}', [App\Http\Controllers\Admin\SmtpController::class, 'destroy']);
    Route::post('smtp/{smtp}/test', [App\Http\Controllers\Admin\SmtpController::class, 'test']);

    // Report Schedules (daily email per branch)
    Route::get('report-schedules', [App\Http\Controllers\Admin\SmtpController::class, 'scheduleIndex']);
    Route::get('report-schedules/{branchId}', [App\Http\Controllers\Admin\SmtpController::class, 'scheduleShow']);
    Route::put('report-schedules/{branchId}', [App\Http\Controllers\Admin\SmtpController::class, 'scheduleUpsert']);
    Route::delete('report-schedules/{branchId}', [App\Http\Controllers\Admin\SmtpController::class, 'scheduleDelete']);

    // Billing Packages / Pricing Plans
    Route::get('packages', [App\Http\Controllers\Admin\BillingPackageController::class, 'index']);
    Route::post('packages', [App\Http\Controllers\Admin\BillingPackageController::class, 'store']);
    Route::get('packages/{package}', [App\Http\Controllers\Admin\BillingPackageController::class, 'show']);
    Route::put('packages/{package}', [App\Http\Controllers\Admin\BillingPackageController::class, 'update']);
    Route::delete('packages/{package}', [App\Http\Controllers\Admin\BillingPackageController::class, 'destroy']);
    Route::patch('packages/{package}/toggle', [App\Http\Controllers\Admin\BillingPackageController::class, 'toggle']);
});

