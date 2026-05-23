<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Reservations\ReservationException;
use App\Domain\Wishlist\Reservations\ReservationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(Request $request, string $slug, string $gift): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:80'],
            'intent' => ['required', 'in:reserve,give'],
        ]);

        // Tenant is already bound by ResolveTenantFromSlug; the global scope
        // restricts findOrFail() to the current tenant's gifts.
        $giftModel = Gift::query()->findOrFail($gift);

        try {
            $reservation = $this->reservations->request(
                gift: $giftModel,
                guestEmail: $validated['email'],
                guestName: $validated['name'] ?? null,
                intent: $validated['intent'],
                ip: $request->ip(),
            );
        } catch (ReservationException $e) {
            return back()->withErrors(['gift' => $e->getMessage()]);
        }

        return back()->with([
            'status' => 'Wysłaliśmy link aktywacyjny na podany adres e-mail. Sprawdź skrzynkę (i folder Spam) w ciągu 60 minut.',
            // Browser-side per-guest tracking so the wishlist page can show
            // "your reservation" badges and offer a self-service cancel
            // without re-checking the e-mail — UX z dokumentu MD.
            'just_reserved_gift' => $giftModel->id,
            'just_reserved_token' => $reservation->verification_token,
        ]);
    }

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
