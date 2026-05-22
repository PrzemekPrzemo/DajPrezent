<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ReservationController;
use App\Http\Controllers\Public\WishlistController;
use App\Http\Middleware\ResolveTenantFromSlug;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('r')->name('public.reservations.')->controller(ReservationController::class)->group(function (): void {
    Route::get('verify/{token}', 'verify')->name('verify');
    Route::get('cancel/{token}', 'cancel')->name('cancel');
});

Route::middleware(ResolveTenantFromSlug::class)
    ->prefix('{slug}')
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'show'])->name('public.wishlist.show');
        Route::post('gifts/{gift}/reserve', [ReservationController::class, 'store'])
            ->name('public.reservations.store');
    });
