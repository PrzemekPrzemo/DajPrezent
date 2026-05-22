<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);

        $tenants = $user->tenants()
            ->withCount([
                'gifts as gifts_total' => fn ($q) => $q->withoutGlobalScope(TenantScope::class),
                'gifts as gifts_reserved' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('status', Gift::STATUS_RESERVED),
                'gifts as gifts_received' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('status', Gift::STATUS_RECEIVED),
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('owner.dashboard', ['tenants' => $tenants]);
    }
}
