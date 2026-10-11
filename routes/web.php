<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\NfcDeviceController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPortfolioController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\TermsController;
use App\Models\PortfolioSetting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Landing Page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'showcaseSlides' => PortfolioSetting::get('showcase_slides', PortfolioSetting::defaultSlides()),
        'orderSteps' => PortfolioSetting::get('order_steps', PortfolioSetting::defaultOrderSteps()),
        'freeModeEnabled' => (bool) PortfolioSetting::get('free_mode_enabled', false),
        'saweriaUrl' => PortfolioSetting::get('saweria_url', 'https://saweria.co/Try3'),
    ]);
})->name('home');

// 2. Google OAuth & Production Authentication
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

if (app()->isLocal()) {
    Route::get('/auth/dev-login/{role?}', [GoogleAuthController::class, 'devLogin'])->name('auth.dev-login');
    Route::get('/auth/dev-google-login', [GoogleAuthController::class, 'devGoogleLogin'])->name('auth.dev-google-login');
}

// 3. Authenticated LUNOU Studio & Management
Route::middleware(['auth'])->group(function () {
    // Studio / Dashboard Main View
    Route::get('/dashboard', [StudioController::class, 'index'])->name('dashboard');
    Route::get('/studio', [StudioController::class, 'index'])->name('studio');

    // Studio Profile & Username
    Route::post('/studio/profile', [StudioController::class, 'update'])->name('studio.profile.update');
    Route::post('/studio/avatar', [StudioController::class, 'uploadAvatar'])->name('studio.avatar.upload');
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

    // Web Portfolio Builder & Subscription Management
    Route::get('/studio/portfolio', [PortfolioController::class, 'index'])->name('studio.portfolio');
    Route::post('/studio/portfolio/checkout', [PortfolioController::class, 'checkout'])->name('studio.portfolio.checkout');
    Route::post('/studio/portfolio/validate-voucher', [PortfolioController::class, 'validateVoucher'])->name('studio.portfolio.voucher.validate');
    Route::post('/studio/portfolio/orders/{order}/payment-proof', [PortfolioController::class, 'submitPaymentProof'])->name('studio.portfolio.orders.payment');
    Route::post('/studio/portfolio/save', [PortfolioController::class, 'savePortfolio'])->name('studio.portfolio.save');
    Route::post('/studio/portfolio/publish', [PortfolioController::class, 'togglePublish'])->name('studio.portfolio.publish');
    Route::post('/studio/portfolio/pages', [PortfolioController::class, 'savePage'])->name('studio.portfolio.pages.save');
    Route::post('/studio/portfolio/pages/reorder', [PortfolioController::class, 'reorderPages'])->name('studio.portfolio.pages.reorder');
    Route::delete('/studio/portfolio/pages/{page}', [PortfolioController::class, 'deletePage'])->name('studio.portfolio.pages.delete');
    Route::post('/studio/portfolio/assets', [PortfolioController::class, 'uploadAsset'])->name('studio.portfolio.assets.upload');
    Route::delete('/studio/portfolio/assets/{asset}', [PortfolioController::class, 'deleteAsset'])->name('studio.portfolio.assets.delete');

    // Existing Breeze profile settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 4. Super Admin Dedicated Isolated Routes
Route::middleware(['auth', 'super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

// 5. Terms of Service & Privacy Platform
Route::get('/terms', [TermsController::class, 'show'])->name('terms');
Route::get('/syarat-ketentuan', [TermsController::class, 'show'])->name('terms.id');

// 6. Public Portfolio Publisher (/p/username)
Route::get('/p/{username}', [PublicPortfolioController::class, 'show'])->name('portfolio.public');
Route::get('/p/{username}/download/{asset}', [PublicPortfolioController::class, 'downloadPdf'])->name('portfolio.download.pdf');

// 7. NFC Tap Direct Resolution & Activation
Route::get('/nfc/{publicId}/activate', [NfcDeviceController::class, 'showActivation'])->name('nfc.activate');
Route::post('/nfc/{publicId}/activate', [NfcDeviceController::class, 'claimDirect'])->name('nfc.activate.claim');
Route::get('/nfc/{publicId}', [PublicProfileController::class, 'nfcTap'])->name('nfc.tap');

// 5. Auth routes from Breeze (login/register/logout fallback)
require __DIR__.'/auth.php';

// 6. Public Profile Route (Must be last to avoid catching reserved routes)
Route::get('/{username}', [PublicProfileController::class, 'show'])->name('public.profile');
Route::post('/{username}/click/{component}', [PublicProfileController::class, 'trackClick'])->name('public.click');
