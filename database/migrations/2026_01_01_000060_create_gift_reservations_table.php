<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
            $table->string('guest_email');
            $table->string('guest_name')->nullable();
            $table->enum('intent', ['reserve', 'give'])->default('reserve');
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');
            $table->string('verification_token', 64)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();     // dla pending: now()+60min
            $table->timestamp('cancelled_at')->nullable();
            $table->string('ip', 45)->nullable();            // wsparcie IPv6
            $table->timestamps();

            $table->index(['gift_id', 'status']);
            $table->index('guest_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_reservations');
    }
};
