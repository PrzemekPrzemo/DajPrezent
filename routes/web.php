<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Owner\AccountController;
use App\Http\Controllers\Owner\BookmarkletController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\GiftController;
use App\Http\Controllers\Owner\GiftExportController;
use App\Http\Controllers\Owner\GiftPreviewController;
use App\Http\Controllers\Owner\InvoiceController;
use App\Http\Controllers\Owner\RodoExportController;
use App\Http\Controllers\Owner\TenantQrController;
use App\Http\Controllers\Owner\TenantSettingsController;
use App\Http\Controllers\Owner\WeddingController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\ReservationController;
use App\Http\Controllers\Public\RsvpController;
use App\Http\Controllers\Public\UnlockController;
use App\Http\Controllers\Public\WishlistController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhooks\PayUWebhookController;
use App\Http\Middleware\ResolveTenantFromSlug;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::post('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

/* Static + legal pages */
Route::view('regulamin', 'public.legal.regulamin')->name('public.legal.terms');
Route::view('polityka-prywatnosci', 'public.legal.privacy')->name('public.legal.privacy');
Route::view('faq', 'public.static.faq')->name('public.faq');
Route::view('kontakt', 'public.static.contact')->name('public.contact');
Route::get('sitemap.xml', SitemapController::class)->name('public.sitemap');

/* Pricing + checkout (public) */
Route::get('pakiety', PricingController::class)->name('public.pricing');
Route::get('buy/return', [CheckoutController::class, 'return'])->name('public.checkout.return');
Route::get('buy/{code}', [CheckoutController::class, 'buy'])->name('public.checkout.buy');
Route::post('buy/{code}', [CheckoutController::class, 'store'])
    ->middleware(['auth', 'throttle:checkout'])
    ->name('public.checkout.store');

/* Auth */
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('register', [RegisterController::class, 'show'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:signup');

    Route::get('password/forgot', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('password/forgot', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth')
        ->name('password.email');
    Route::get('password/reset/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth')
        ->name('password.update');
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
        Route::get('gifts/export.csv', [GiftExportController::class, 'csv'])->name('gifts.export.csv');
        Route::get('qr.svg', TenantQrController::class)->name('qr');

        Route::get('settings', [TenantSettingsController::class, 'edit'])->name('tenant.settings.edit');
        Route::patch('settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
        Route::delete('/', [TenantSettingsController::class, 'destroy'])->name('tenant.destroy');

        Route::get('wedding', [WeddingController::class, 'edit'])->name('wedding.edit');
        Route::patch('wedding', [WeddingController::class, 'update'])->name('wedding.update');
    });

    Route::get('rodo/eksport', RodoExportController::class)->name('rodo.export');

    Route::post('api/gift-preview', GiftPreviewController::class)
        ->middleware('throttle:30,1') // 30 paste-previews per minute per user
        ->name('api.gift-preview');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('konto', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('konto/profil', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('konto/haslo', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::delete('konto', [AccountController::class, 'destroy'])->name('account.destroy');

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
            ->middleware('throttle:reservation')
            ->name('public.reservations.store');
        Route::post('rsvp', [RsvpController::class, 'store'])
            ->middleware('throttle:rsvp')
            ->name('public.rsvp.store');
    });
