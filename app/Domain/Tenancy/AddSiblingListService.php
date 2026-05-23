<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
     *
     * Selection order:
     *   1. Subscriptions whose package has multiple_lists >= 2 AND has
     *      a free slot.
     *   2. Ordered by remaining slots DESC, then paid_at DESC — pick the
     *      bigger pakiet first so we don't waste Pro slots when Plus is
     *      also available.
     *   3. SoftDeletes-aware: we accept subs even if the primary tenant
     *      was soft-deleted, otherwise an owner who deleted their first
     *      list would lose access to any siblings they paid for.
     *   4. Requires paid_at IS NOT NULL — pending checkouts don't count.
     */
    public function eligibleSubscription(User $user): ?Subscription
    {
        $subs = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('paid_at')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            // @phpstan-ignore-next-line — Tenant::tenant uses SoftDeletes; withTrashed is valid
            ->whereHas('tenant', fn (Builder $q) => $q->withTrashed()->where('owner_user_id', $user->id))
            ->with('package')
            ->orderByDesc('paid_at')
            ->get();

        $best = null;
        $bestFree = 0;
        foreach ($subs as $sub) {
            $limit = (int) ($sub->package?->featureValue('multiple_lists') ?? 0);
            if ($limit < 2) {
                continue; // pakiet jednolisty (Mini/Standard/Free) — pomijamy
            }
            // Count BOTH live and soft-deleted siblings — a slot once
            // spent doesn't come back when the owner deletes the list.
            // Stops loop-create-and-delete abuse of slug namespace.
            $used = Tenant::query()
                ->withTrashed()
                ->where('parent_subscription_id', $sub->id)
                ->count();
            $free = $limit - $used;
            if ($free > $bestFree) {
                $best = $sub;
                $bestFree = $free;
            }
        }

        return $best;
    }

    /**
     * Create a sibling tenant under $parent inside a transaction with
     * a row lock on the parent subscription, so two concurrent POSTs
     * can't double-spend the last free slot.
     *
     * @throws \DomainException when no sibling slot is free
     * @throws \LogicException when $parent is not owned by $user
     */
    public function create(User $user, Subscription $parent, string $slug, string $name): Tenant
    {
        return DB::transaction(function () use ($user, $parent, $slug, $name): Tenant {
            // Re-lock the parent row inside the tx so race-checkers
            // serialise. Re-fetch as fresh: status/expires_at may
            // have flipped since the controller picked it.
            $fresh = Subscription::query()
                ->whereKey($parent->id)
                ->with('package', 'tenant')
                ->lockForUpdate()
                ->first();

            if ($fresh === null) {
                throw new \DomainException('Subskrypcja nieaktywna.');
            }

            // Defense-in-depth ownership check: future callers that
            // hand us a $parent not produced by eligibleSubscription()
            // shouldn't be able to mint cross-account lists.
            if (($fresh->tenant?->owner_user_id ?? null) !== $user->id) {
                throw new \LogicException('Subskrypcja nie należy do tego użytkownika.');
            }

            if ($fresh->status !== 'active' || $fresh->paid_at === null
                || ($fresh->expires_at !== null && $fresh->expires_at->isPast())) {
                throw new \DomainException('Subskrypcja wygasła — przedłuż pakiet.');
            }

            $limit = (int) ($fresh->package?->featureValue('multiple_lists') ?? 0);
            $used = Tenant::query()
                ->withTrashed()
                ->where('parent_subscription_id', $fresh->id)
                ->count();

            if ($limit < 2 || $used >= $limit) {
                throw new \DomainException('Pakiet nie pozwala na dodanie kolejnej listy.');
            }

            return Tenant::create([
                'owner_user_id' => $user->id,
                'parent_subscription_id' => $fresh->id,
                'slug' => $slug,
                'name' => $name,
                'kind' => str_starts_with((string) $fresh->package?->code, 'wedding_')
                    ? (string) $fresh->package?->code
                    : 'wishlist',
                'locale' => $fresh->tenant?->locale ?? 'pl',
                'expires_at' => $fresh->expires_at,
                // Default to private — matches CheckoutService's primary
                // tenant default. Owner opts in to public via settings.
                'is_public' => false,
            ]);
        });
    }
}
