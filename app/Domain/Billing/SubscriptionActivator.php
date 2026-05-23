<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Jobs\IssueInvoiceJob;
use App\Notifications\WelcomeOwnerNotification;
use Illuminate\Support\Facades\DB;

/**
 * Applies a successful PayU webhook to a Subscription / Tenant.
 *
 * Idempotent — re-sent COMPLETED notifications produce no further
 * state change. The tenant's expires_at is set the first time we see
 * COMPLETED (paid_at remains immutable); subsequent re-applies skip
 * the recompute.
 */
final class SubscriptionActivator
{
    /**
     * @return bool true if anything changed.
     */
    public function activate(Subscription $subscription): bool
    {
        $changed = DB::transaction(function () use ($subscription): bool {
            $sub = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($sub->status === 'active') {
                return false;
            }

            $package = $sub->package;
            $now = now();
            $expiresAt = $now->copy()->addDays($package->valid_days);

            $sub->update([
                'status' => 'active',
                'paid_at' => $sub->paid_at ?? $now,
                'expires_at' => $expiresAt,
            ]);

            /** @var Tenant $tenant */
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($sub->tenant_id);
            $tenant->update([
                'expires_at' => $expiresAt,
                'is_public' => true,
            ]);

            return true;
        });

        // Side effects only after a *first* activation. Idempotent
        // replays of the same IPN never re-dispatch.
        if ($changed) {
            $fresh = $subscription->fresh();
            if ($fresh->amount_pln_gr > 0) {
                IssueInvoiceJob::dispatch($fresh);
            }
            $tenant = $fresh->tenant;
            if ($tenant !== null && $tenant->owner !== null) {
                $tenant->owner->notify(new WelcomeOwnerNotification($tenant));
            }
        }

        return $changed;
    }

    public function markCancelled(Subscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription): bool {
            $sub = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if (in_array($sub->status, ['cancelled', 'refunded', 'expired'], true)) {
                return false;
            }

            $sub->update(['status' => 'cancelled']);

            return true;
        });
    }
}
