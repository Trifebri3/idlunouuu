<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\NfcDeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Landing Page
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// 2. Google OAuth & Dev Authentication
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/auth/dev-login/{role?}', [GoogleAuthController::class, 'devLogin'])->name('auth.dev-login');
Route::get('/auth/dev-google-login', [GoogleAuthController::class, 'devGoogleLogin'])->name('auth.dev-google-login');

// 3. Authenticated LUNOU Studio & Management
Route::middleware(['auth'])->group(function () {
    // Studio / Dashboard Main View
    Route::get('/dashboard', [StudioController::class, 'index'])->name('dashboard');
    Route::get('/studio', [StudioController::class, 'index'])->name('studio');

    // Studio Profile & Username
    Route::post('/studio/profile', [StudioController::class, 'update'])->name('studio.profile.update');
    Route::post('/studio/username', [StudioController::class, 'updateUsername'])->name('studio.username.update');
    Route::get('/studio/check-username', [StudioController::class, 'checkUsername'])->name('studio.username.check');

    // Studio Components Management
    Route::post('/studio/components', [ComponentController::class, 'store'])->name('studio.components.store');
    Route::put('/studio/components/{component}', [ComponentController::class, 'update'])->name('studio.components.update');
    Route::delete('/studio/components/{component}', [ComponentController::class, 'destroy'])->name('studio.components.destroy');
    Route::post('/studio/components/reorder', [ComponentController::class, 'reorder'])->name('studio.components.reorder');
    Route::patch('/studio/components/{component}/toggle', [ComponentController::class, 'toggleVisibility'])->name('studio.components.toggle');

    // NFC Device Management & Orders
    Route::post('/studio/nfc/order', [NfcDeviceController::class, 'orderNfcCard'])->name('studio.nfc.order');
    Route::post('/studio/nfc/claim', [NfcDeviceController::class, 'claim'])->name('studio.nfc.claim');
    Route::post('/studio/nfc/redeem', [NfcDeviceController::class, 'redeemOrderCode'])->name('studio.nfc.redeem');
    Route::post('/studio/nfc/demo', [NfcDeviceController::class, 'generateDemo'])->name('studio.nfc.demo');
    Route::delete('/studio/nfc/{device}', [NfcDeviceController::class, 'unbind'])->name('studio.nfc.unbind');

    // Super Admin Area
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::patch('/admin/users/{user}/status', [AdminDashboardController::class, 'toggleUserStatus'])->name('admin.users.status');
    Route::post('/admin/nfc', [AdminDashboardController::class, 'createNfcCard'])->name('admin.nfc.create');
    Route::post('/admin/nfc/batch', [AdminDashboardController::class, 'batchCreateNfcCards'])->name('admin.nfc.batch');
    Route::post('/admin/nfc/{device}/assign', [AdminDashboardController::class, 'assignNfcCard'])->name('admin.nfc.assign');
    Route::delete('/admin/nfc/{device}', [AdminDashboardController::class, 'deleteNfcCard'])->name('admin.nfc.delete');
    Route::post('/admin/orders/create-offline', [AdminDashboardController::class, 'createOfflineOrder'])->name('admin.orders.create-offline');
    Route::post('/admin/orders/{order}/connect', [AdminDashboardController::class, 'connectOrderToNfc'])->name('admin.orders.connect');
    Route::patch('/admin/orders/{order}/verify-payment', [AdminDashboardController::class, 'verifyPayment'])->name('admin.orders.verify-payment');
    Route::patch('/admin/orders/{order}/status', [AdminDashboardController::class, 'updateOrderStatus'])->name('admin.orders.status');

    // Existing Breeze profile settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 4. NFC Tap Direct Resolution & Activation
Route::get('/nfc/{publicId}/activate', [NfcDeviceController::class, 'showActivation'])->name('nfc.activate');
Route::post('/nfc/{publicId}/activate', [NfcDeviceController::class, 'claimDirect'])->name('nfc.activate.claim');
Route::get('/nfc/{publicId}', [PublicProfileController::class, 'nfcTap'])->name('nfc.tap');

// 5. Auth routes from Breeze (login/register/logout fallback)
require __DIR__.'/auth.php';

// 6. Public Profile Route (Must be last to avoid catching reserved routes)
Route::get('/{username}', [PublicProfileController::class, 'show'])->name('public.profile');
Route::post('/{username}/click/{component}', [PublicProfileController::class, 'trackClick'])->name('public.click');
