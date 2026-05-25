<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Y2K38 fix — convert TIMESTAMP columns (max 2038-01-19) to DATETIME
 * (max 9999-12-31). Required because the VIP package valid_days=36500
 * produces an expires_at of ~year 2126, which MySQL rejects with
 * SQLSTATE[22007] on TIMESTAMP.
 *
 * Columns touched (those that may hold dates beyond 2038):
 *   - tenants.expires_at        (VIP "full forever")
 *   - subscriptions.paid_at, expires_at (sub valid for VIP)
 *   - gift_reservations.expires_at      (60-min TTL, never past 2038
 *     in practice — but flip anyway for consistency)
 *
 * Safe: TIMESTAMP and DATETIME share the same storage format on
 * disk; ALTER TABLE re-writes the column in place without data loss.
 * Existing rows keep their values, indexes are rebuilt automatically.
 *
 * MySQL only — SQLite's "DATETIME" is just TEXT and doesn't have the
 * 2038 limit, so the test suite was never affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip on non-MySQL drivers — SQLite stores datetimes as TEXT.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Raw ALTER — Schema::table()->dateTime() wouldn't honor nullable
        // on the existing column without doctrine/dbal, and we don't
        // want to add that dep just for one migration.
        if (Schema::hasColumn('tenants', 'expires_at')) {
            DB::statement('ALTER TABLE tenants MODIFY expires_at DATETIME NULL');
        }
        if (Schema::hasColumn('subscriptions', 'paid_at')) {
            DB::statement('ALTER TABLE subscriptions MODIFY paid_at DATETIME NULL');
        }
        if (Schema::hasColumn('subscriptions', 'expires_at')) {
            DB::statement('ALTER TABLE subscriptions MODIFY expires_at DATETIME NULL');
        }
        if (Schema::hasColumn('gift_reservations', 'expires_at')) {
            DB::statement('ALTER TABLE gift_reservations MODIFY expires_at DATETIME NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Reverting would risk truncating rows beyond 2038. Intentional
        // no-op — once you've granted a VIP plan with a 2126 expiry,
        // there's no safe way back to TIMESTAMP without data loss.
    }
};
