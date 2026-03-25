<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
Route::get('/admin/{any}', [DashboardController::class, 'admin'])->where('any', '.*');
