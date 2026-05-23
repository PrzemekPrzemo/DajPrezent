<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repoint `tenants.parent_subscription_id` from the OLDEST subscription
 * (which the original backfill in 000500 used) to the LATEST ACTIVE one.
 *
 * Why: for renewed tenants the original backfill linked to the expired
 * first sub. GiftLimitGuard short-circuits on an expired parent and
 * reports "no active package" even though the tenant has paid for a
 * current subscription. New GiftLimitGuard logic falls through to
 * $tenant->subscriptions(), so this repoint is mainly for keeping the
 * AddSiblingListService slot accounting correct (siblings need to count
 * against the *current* sub's multiple_lists, not an expired one).
 *
 * Done in PHP rather than raw SQL because MySQL can't reference the
 * same table in an UPDATE subquery, and SQLite's NOW()/CURRENT_TIMESTAMP
 * semantics differ — staying in Eloquent dodges both.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->orderBy('id')->chunkById(500, function ($tenants): void {
            foreach ($tenants as $t) {
                $latest = DB::table('subscriptions')
                    ->where('tenant_id', $t->id)
                    ->where('status', 'active')
                    ->whereNotNull('paid_at')
                    ->where(function ($q): void {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->orderByDesc('paid_at')
                    ->orderByDesc('id')
                    ->value('id');

                if ($latest !== null && $latest !== $t->parent_subscription_id) {
                    DB::table('tenants')
                        ->where('id', $t->id)
                        ->update(['parent_subscription_id' => $latest]);
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally a no-op — re-pointing to an expired sub would
        // re-introduce the bug. Migration 000500 stays the canonical
        // initial backfill for fresh installs.
    }
};
