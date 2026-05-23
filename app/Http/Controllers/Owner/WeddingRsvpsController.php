<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wedding\Models\Rsvp;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Owner-facing list of RSVPs for a wedding tenant. Shows headline
 * counts (attending, headcount with plus-ones, dietary requirements)
 * + the raw table. Links to CSV download + invitation PDF.
 */
final class WeddingRsvpsController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
        if (! in_array($tenant->kind, ['wedding_basic', 'wedding_premium'], true)) {
            abort(404);
        }

        $rsvps = Rsvp::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->paginate(50);

        $attending = Rsvp::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->where('attending', true)
            ->get();

        $stats = [
            'total' => $rsvps->total(),
            'attending' => $attending->count(),
            'declined' => Rsvp::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->where('attending', false)
                ->count(),
            'head_count' => $attending->sum(fn (Rsvp $r) => $r->headCount()),
            'with_dietary' => $attending->filter(fn (Rsvp $r) => $r->dietary !== null && $r->dietary !== '')->count(),
            'with_transport' => $attending->filter(fn (Rsvp $r) => $r->transport_needed)->count(),
        ];

        return view('owner.wedding.rsvps', [
            'tenant' => $tenant,
            'rsvps' => $rsvps,
            'stats' => $stats,
        ]);
    }
}
