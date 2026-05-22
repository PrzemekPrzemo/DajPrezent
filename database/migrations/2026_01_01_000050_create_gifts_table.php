<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('url', 1024)->nullable();
            $table->unsignedInteger('price_pln_gr')->nullable();
            $table->unsignedTinyInteger('priority')->default(2); // 1=must-have, 2=normal, 3=nice-to-have
            $table->string('category', 64)->nullable();
            $table->enum('status', ['available', 'reserved', 'received'])->default('available');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
