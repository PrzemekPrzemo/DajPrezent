<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiple lists per purchase.
 *
 * Plus + Pro pakiety mają `multiple_lists` >= 1 (3 i 5 list odpowiednio).
 * Dotąd każdy tenant musiał mieć własną subskrypcję — limit pakietu
 * był nieużywany. Ta migracja dodaje wskaźnik na rodzicielską subskrypcję
 * dla sibling-listy: nowa lista dziedziczy expires_at + kind + locale
 * z parent sub, a `multiple_lists` limit jest egzekwowany w
 * AddSiblingListService.
 *
 * Backfill: dla każdego istniejącego tenanta szukamy jego "pierwszej"
 * subskrypcji (tej z FK tenant_id = self) i kopiujemy jej id jako
 * parent_subscription_id. Dla świeżych instancji bez subskrypcji
 * kolumna zostaje NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('parent_subscription_id')
                ->nullable()
                ->after('owner_user_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
        });

        // Backfill: link each tenant to its primary subscription row.
        DB::statement('
            UPDATE tenants
            SET parent_subscription_id = (
                SELECT s.id FROM subscriptions s
                WHERE s.tenant_id = tenants.id
                ORDER BY s.id ASC
                LIMIT 1
            )
        ');
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['parent_subscription_id']);
            $table->dropColumn('parent_subscription_id');
        });
    }
};
