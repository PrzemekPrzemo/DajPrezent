<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\Rsvp;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public RSVP form handler. Posted from the wedding page; no auth.
 * Throttle is applied at route level (rsvp limiter) so a guest can't
 * spam the venue with 1000 dietary requirements.
 */
final class RsvpController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        // Wedding-only feature — no RSVP on wishlist tenants.
        if (! in_array($tenant->kind, ['wedding_basic', 'wedding_premium'], true)) {
            abort(404);
        }

        // If owner set a deadline and it's passed, refuse new RSVPs.
        $event = WeddingEvent::query()->where('tenant_id', $tenant->id)->first();
        if ($event !== null && $event->rsvp_deadline !== null && $event->rsvp_deadline->isPast()) {
            return back()->withErrors(['rsvp' => 'Termin potwierdzania obecności minął. Skontaktuj się bezpośrednio z parą młodą.']);
        }

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email:rfc', 'max:255'],
            'attending' => ['required', 'in:1,0'],
            'plus_one' => ['nullable', 'in:1'],
            'plus_one_name' => ['nullable', 'string', 'max:120'],
            'dietary' => ['nullable', 'string', 'max:200'],
            'transport_needed' => ['nullable', 'in:1'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        Rsvp::create([
            'tenant_id' => $tenant->id,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'attending' => (int) $data['attending'] === 1,
            'plus_one' => ($data['plus_one'] ?? null) === '1',
            'plus_one_name' => $data['plus_one_name'] ?? null,
            'dietary' => $data['dietary'] ?? null,
            'transport_needed' => ($data['transport_needed'] ?? null) === '1',
            'message' => $data['message'] ?? null,
            'ip' => $request->ip(),
        ]);

        return back()->with('rsvp_status', 'Dziękujemy! Twoje potwierdzenie zostało zapisane.');
    }
}
