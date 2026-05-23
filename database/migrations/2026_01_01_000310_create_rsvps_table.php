<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('guest_name', 120);
            $table->string('guest_email')->nullable();         // optional; some RSVP-ing without email
            $table->boolean('attending')->default(true);
            $table->boolean('plus_one')->default(false);
            $table->string('plus_one_name', 120)->nullable();
            $table->string('dietary', 200)->nullable();        // np. „wegetariańska, bez orzechów"
            $table->boolean('transport_needed')->default(false);
            $table->text('message')->nullable();
            $table->string('ip', 45)->nullable();              // IPv6 supported
            $table->timestamps();

            $table->index(['tenant_id', 'attending']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
