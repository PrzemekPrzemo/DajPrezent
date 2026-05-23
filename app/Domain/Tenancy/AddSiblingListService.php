<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Spawns sibling tenants under an already-paid subscription.
 *
 * Plus = 3 list, Pro = 5 list — `multiple_lists` w packages.features.
 * Każdy sibling dziedziczy expires_at + kind + locale z parent sub
 * i wskazuje na nią przez parent_subscription_id. Limit prezentów
 * (gift_limit) jest PER tenant, nie współdzielony.
 */
final class AddSiblingListService
{
    /**
     * Pick the user's best subscription that still has at least one free
     * sibling slot. Returns null if no eligible sub exists.
     */
    public function eligibleSubscription(User $user): ?Subscription
    {
        $subs = Subscription::query()
            ->where('status', 'active')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('tenant', fn (Builder $q) => $q->where('owner_user_id', $user->id))
            ->with('package')
            ->orderByDesc('paid_at')
            ->get();

        foreach ($subs as $sub) {
            $limit = (int) ($sub->package?->featureValue('multiple_lists') ?? 0);
            if ($limit < 2) {
                continue; // pakiet jednolisty (Mini/Standard/Free) — pomijamy
            }
            $used = Tenant::query()->where('parent_subscription_id', $sub->id)->count();
            if ($used < $limit) {
                return $sub;
            }
        }

        return null;
    }

    /**
     * Create a sibling tenant under $parent. Caller is responsible for
     * validating slug uniqueness via Rule, but we double-check eligibility.
     *
     * @throws \DomainException when no sibling slot is free
     */
    public function create(User $user, Subscription $parent, string $slug, string $name): Tenant
    {
        $limit = (int) ($parent->package?->featureValue('multiple_lists') ?? 0);
        $used = Tenant::query()->where('parent_subscription_id', $parent->id)->count();

        if ($limit < 2 || $used >= $limit) {
            throw new \DomainException('Pakiet nie pozwala na dodanie kolejnej listy.');
        }

        return Tenant::create([
            'owner_user_id' => $user->id,
            'parent_subscription_id' => $parent->id,
            'slug' => $slug,
            'name' => $name,
            'kind' => str_starts_with((string) $parent->package?->code, 'wedding_')
                ? (string) $parent->package?->code
                : 'wishlist',
            'locale' => $parent->tenant?->locale ?? 'pl',
            'expires_at' => $parent->expires_at,
            'is_public' => true,
        ]);
    }
}
