<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RODO art. 17 (right to erasure) vs ustawa o rachunkowości
 * (5-letnia retencja faktur) wymagają, żeby usunięcie usera NIE
 * kasowało faktur. Tenants miały `cascadeOnDelete`, co przez łańcuch
 * cascadeOnDelete na invoices/subscriptions kasowało wszystko.
 *
 * Zmiana:
 *   - tenants.owner_user_id staje się nullable z nullOnDelete.
 *   - Po usunięciu konta user-a tenant zostaje (już soft-deleted
 *     przez TenantCloser), tylko jego owner_user_id staje się NULL.
 *   - Subscription/Invoice nie ruszają się — zachowane 5 lat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['owner_user_id']);
            $table->unsignedBigInteger('owner_user_id')->nullable()->change();
            $table->foreign('owner_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['owner_user_id']);
            $table->unsignedBigInteger('owner_user_id')->nullable(false)->change();
            $table->foreign('owner_user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
