<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('couple_names', 120)->nullable();             // "Anna & Tomek"
            $table->string('hashtag', 60)->nullable();                   // "#AnnaITomek2026"
            $table->timestamp('ceremony_at')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->decimal('venue_lat', 9, 6)->nullable();
            $table->decimal('venue_lng', 9, 6)->nullable();
            $table->string('reception_venue_name')->nullable();
            $table->string('reception_venue_address')->nullable();
            $table->string('dress_code', 80)->nullable();
            $table->text('story_text')->nullable();                      // Jak się poznali itp.
            $table->text('schedule_text')->nullable();                   // markdown / wolny tekst
            $table->text('accommodation_text')->nullable();
            $table->date('rsvp_deadline')->nullable();
            $table->string('theme', 30)->default('classic');             // classic | minimalist | garden | gold
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_events');
    }
};
