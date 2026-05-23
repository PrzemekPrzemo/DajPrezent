<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Single-page editor for the wedding micro-site that ships with the
 * wedding_basic / wedding_premium tier. Edits the WeddingEvent
 * (one-to-one with the tenant) — couple names, ceremony where & when,
 * dress code, story, schedule, RSVP deadline, theme.
 */
final class WeddingController extends Controller
{
    public function __construct(private readonly CurrentTenant $current) {}

    public function edit(Request $request, Tenant $tenant): View|RedirectResponse
    {
        $this->authorizeWedding($request, $tenant);
        $this->current->set($tenant);

        $event = WeddingEvent::query()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['theme' => 'classic'],
        );

        return view('owner.wedding.edit', [
            'tenant' => $tenant,
            'event' => $event,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeWedding($request, $tenant);
        $this->current->set($tenant);

        $data = $request->validate([
            'couple_names' => ['nullable', 'string', 'max:120'],
            'hashtag' => ['nullable', 'string', 'max:60'],
            'ceremony_at' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'venue_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'venue_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'reception_venue_name' => ['nullable', 'string', 'max:160'],
            'reception_venue_address' => ['nullable', 'string', 'max:255'],
            'dress_code' => ['nullable', 'string', 'max:80'],
            'story_text' => ['nullable', 'string', 'max:5000'],
            'schedule_text' => ['nullable', 'string', 'max:5000'],
            'accommodation_text' => ['nullable', 'string', 'max:5000'],
            'rsvp_deadline' => ['nullable', 'date'],
            'theme' => ['required', Rule::in(WeddingEvent::THEMES)],
        ]);

        $event = WeddingEvent::query()->firstOrCreate(['tenant_id' => $tenant->id]);
        $event->fill($data)->save();

        return redirect()
            ->route('owner.wedding.edit', $tenant)
            ->with('status', 'Zapisano stronę ślubną.');
    }

    private function authorizeWedding(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
        if (! in_array($tenant->kind, ['wedding_basic', 'wedding_premium'], true)) {
            abort(404);
        }
    }
}
