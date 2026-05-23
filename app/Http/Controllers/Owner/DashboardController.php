<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\AddSiblingListService;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly AddSiblingListService $sibling) {}

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

        $parent = $this->sibling->eligibleSubscription($user);
        $siblingSlots = null;
        if ($parent !== null) {
            $limit = (int) ($parent->package?->featureValue('multiple_lists') ?? 0);
            $used = Tenant::query()->where('parent_subscription_id', $parent->id)->count();
            $siblingSlots = [
                'package' => $parent->package?->name,
                'free' => $limit - $used,
                'limit' => $limit,
            ];
        }

        return view('owner.dashboard', [
            'tenants' => $tenants,
            'siblingSlots' => $siblingSlots,
        ]);
    }
}
