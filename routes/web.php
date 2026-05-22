<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::prefix('r')->name('public.reservations.')->controller(ReservationController::class)->group(function (): void {
    Route::get('verify/{token}', 'verify')->name('verify');
    Route::get('cancel/{token}', 'cancel')->name('cancel');
});
