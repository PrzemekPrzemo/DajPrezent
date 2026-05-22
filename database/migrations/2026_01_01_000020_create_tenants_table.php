<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->enum('kind', ['wishlist', 'wedding_basic', 'wedding_premium'])->default('wishlist');
            $table->string('locale', 5)->default('pl');
            $table->string('password_hash')->nullable();   // ochrona listy hasłem (jeśli pakiet pozwala)
            $table->string('cover_image_path')->nullable();
            $table->json('theme')->nullable();
            $table->timestamp('expires_at')->nullable();    // null = nieaktywny / nieopłacony
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
