<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminNfcController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPortfolioController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Dedicated Routes
|--------------------------------------------------------------------------
|
| Handled exclusively with ['auth', 'super_admin'] middleware and prefix '/admin'.
| All routes here are isolated from user/public web routes.
|
*/

// 1. Dashboard Overview & Realtime Stats
Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

// 2. User Accounts Moderation & Status Toggle
Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');

// 3. NFC Hardware Cards & Stock Management
Route::post('/nfc', [AdminNfcController::class, 'store'])->name('nfc.create');
Route::post('/nfc/batch', [AdminNfcController::class, 'batchStore'])->name('nfc.batch');
Route::post('/nfc/{device}/assign', [AdminNfcController::class, 'assign'])->name('nfc.assign');
Route::delete('/nfc/{device}', [AdminNfcController::class, 'destroy'])->name('nfc.delete');

// 4. NFC Orders & Fulfillment Pipeline
Route::post('/orders/create-offline', [AdminOrderController::class, 'createOffline'])->name('orders.create-offline');
Route::post('/orders/{order}/connect', [AdminOrderController::class, 'connectNfc'])->name('orders.connect');
Route::patch('/orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify-payment');
Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

// 5. Portfolio Management (Pricing, Vouchers, Payments, Entitlements)
Route::patch('/portfolio/plans/{plan}', [AdminPortfolioController::class, 'updatePlan'])->name('portfolio.plans.update');
Route::post('/portfolio/vouchers', [AdminPortfolioController::class, 'storeVoucher'])->name('portfolio.vouchers.store');
Route::patch('/portfolio/vouchers/{voucher}/toggle', [AdminPortfolioController::class, 'toggleVoucher'])->name('portfolio.vouchers.toggle');
Route::delete('/portfolio/vouchers/{voucher}', [AdminPortfolioController::class, 'deleteVoucher'])->name('portfolio.vouchers.delete');
Route::post('/portfolio/payment-methods', [AdminPortfolioController::class, 'storePaymentMethod'])->name('portfolio.payment-methods.store');
Route::put('/portfolio/payment-methods/{method}', [AdminPortfolioController::class, 'updatePaymentMethod'])->name('portfolio.payment-methods.update');
Route::delete('/portfolio/payment-methods/{method}', [AdminPortfolioController::class, 'deletePaymentMethod'])->name('portfolio.payment-methods.delete');
Route::post('/portfolio/orders/{order}/verify', [AdminPortfolioController::class, 'verifyPayment'])->name('portfolio.orders.verify');
Route::post('/portfolio/users/{user}/entitlement', [AdminPortfolioController::class, 'manageEntitlement'])->name('portfolio.users.entitlement');
Route::post('/portfolio/settings', [AdminPortfolioController::class, 'updateSettings'])->name('portfolio.settings.update');
Route::post('/portfolio/toggle-free-mode', [AdminPortfolioController::class, 'toggleFreeMode'])->name('portfolio.toggle-free-mode');
Route::post('/portfolio/slides', [AdminPortfolioController::class, 'updateSlides'])->name('portfolio.slides.update');
Route::post('/portfolio/order-steps', [AdminPortfolioController::class, 'updateOrderSteps'])->name('portfolio.order-steps.update');
