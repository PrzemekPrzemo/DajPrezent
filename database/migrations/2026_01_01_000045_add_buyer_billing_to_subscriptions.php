<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            // Buyer billing snapshot captured at checkout time.
            // Defaults to the user's name for B2C; company + NIP filled
            // when "Chcę fakturę na firmę" is ticked. We snapshot here
            // (not on User) because the same person can buy multiple
            // packages with different billing data (private vs. company).
            $table->string('buyer_name')->nullable()->after('amount_pln_gr');
            $table->string('buyer_company')->nullable()->after('buyer_name');
            $table->string('buyer_nip', 10)->nullable()->after('buyer_company');
            $table->string('buyer_street')->nullable()->after('buyer_nip');
            $table->string('buyer_postal_code', 10)->nullable()->after('buyer_street');
            $table->string('buyer_city')->nullable()->after('buyer_postal_code');
            $table->string('buyer_country', 2)->default('PL')->after('buyer_city');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'buyer_name', 'buyer_company', 'buyer_nip',
                'buyer_street', 'buyer_postal_code', 'buyer_city', 'buyer_country',
            ]);
        });
    }
};
