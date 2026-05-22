<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Wishlist\Reservations\ReservationException;
use App\Domain\Wishlist\Reservations\ReservationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function verify(string $token): View
    {
        try {
            $reservation = $this->reservations->verify($token);
        } catch (ReservationException $e) {
            return view('public.reservations.failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return view('public.reservations.verified', [
            'reservation' => $reservation,
        ]);
    }

    public function cancel(string $token): View
    {
        try {
            $reservation = $this->reservations->cancel($token);
        } catch (ReservationException $e) {
            return view('public.reservations.failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return view('public.reservations.cancelled', [
            'reservation' => $reservation,
        ]);
    }
}
