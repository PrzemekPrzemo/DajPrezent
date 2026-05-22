<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Owner\BookmarkletController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\GiftController;
use App\Http\Controllers\Owner\TenantSettingsController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\ReservationController;
use App\Http\Controllers\Public\UnlockController;
use App\Http\Controllers\Public\WishlistController;
use App\Http\Controllers\Webhooks\PayUWebhookController;
use App\Http\Middleware\ResolveTenantFromSlug;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

/* Pricing + checkout (public) */
Route::get('pakiety', PricingController::class)->name('public.pricing');
Route::get('buy/return', [CheckoutController::class, 'return'])->name('public.checkout.return');
Route::get('buy/{code}', [CheckoutController::class, 'buy'])->name('public.checkout.buy');
Route::post('buy/{code}', [CheckoutController::class, 'store'])
    ->middleware('auth')
    ->name('public.checkout.store');

/* Auth */
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'show'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    Route::get('password/forgot', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('password/forgot', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('password/reset/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->name('password.update');
});
Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/* Email verification */
Route::middleware('auth')->group(function (): void {
    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/* Owner panel */
Route::middleware('auth')->prefix('panel')->name('owner.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::prefix('lists/{tenant}')->group(function (): void {
        Route::get('gifts', [GiftController::class, 'index'])->name('gifts.index');
        Route::post('gifts', [GiftController::class, 'store'])->name('gifts.store');
        Route::patch('gifts/{gift}', [GiftController::class, 'update'])->name('gifts.update');
        Route::delete('gifts/{gift}', [GiftController::class, 'destroy'])->name('gifts.destroy');
        Route::post('gifts/{gift}/received', [GiftController::class, 'markReceived'])->name('gifts.received');

        Route::get('settings', [TenantSettingsController::class, 'edit'])->name('tenant.settings.edit');
        Route::patch('settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
    });

    Route::prefix('bookmarklet')->name('bookmarklet.')->controller(BookmarkletController::class)->group(function (): void {
        Route::get('/', 'show')->name('show');
        Route::get('import', 'import')->name('import');
        Route::post('import', 'store')->name('store');
    });
});

/* Reservation actions (verify/cancel by link from e-mail) */
Route::prefix('r')->name('public.reservations.')->controller(ReservationController::class)->group(function (): void {
    Route::get('verify/{token}', 'verify')->name('verify');
    Route::get('cancel/{token}', 'cancel')->name('cancel');
});

/* Webhooks (CSRF exempt — see bootstrap/app.php) */
Route::post('webhooks/payu', PayUWebhookController::class)->name('webhooks.payu');

/* Public list under tenant slug — MUST come last so it doesn't shadow /login etc. */
Route::middleware(ResolveTenantFromSlug::class)
    ->prefix('{slug}')
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'show'])->name('public.wishlist.show');
        Route::get('unlock', [UnlockController::class, 'show'])->name('public.wishlist.unlock');
        Route::post('unlock', [UnlockController::class, 'store']);
        Route::post('gifts/{gift}/reserve', [ReservationController::class, 'store'])
            ->name('public.reservations.store');
    });
