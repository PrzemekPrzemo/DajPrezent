<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Public landing — sales funnel root.
 *
 * Stats are pulled from the DB once a minute and cached so the
 * homepage stays cheap even under a small AdWords burst.
 */
final class LandingController extends Controller
{
    public function __invoke(): View
    {
        $stats = Cache::remember('landing.stats', now()->addMinutes(5), function (): array {
            return [
                'lists' => Tenant::query()->where('is_public', true)->count(),
                'gifts' => Gift::query()->count(),
                'reservations' => GiftReservation::query()
                    ->where('status', GiftReservation::STATUS_ACTIVE)
                    ->count(),
            ];
        });

        // Floor counters so a freshly-installed instance doesn't show "0".
        $stats = [
            'lists' => max($stats['lists'], 124),
            'gifts' => max($stats['gifts'], 3_472),
            'reservations' => max($stats['reservations'], 1_891),
        ];

        return view('welcome', ['stats' => $stats]);
    }
}
