<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();           // stabilny identyfikator: free|mini|standard|plus|pro|wedding_basic|wedding_premium
            $table->string('name');
            $table->enum('kind', ['standard', 'wedding']);
            $table->unsignedInteger('price_pln_gr');    // cena brutto w groszach
            $table->unsignedSmallInteger('valid_days'); // 30, 270 (9 mc), 365 (12 mc)
            $table->unsignedSmallInteger('gift_limit')->nullable(); // null = bez limitu
            $table->json('features');                   // pełna definicja flag z config/packages.php
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
