<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('number')->unique();              // numer wg sekwencji, np. FV/2026/06/0001
            $table->string('buyer_name');
            $table->string('buyer_nip')->nullable();
            $table->json('buyer_address');                   // {street, city, postal_code, country}
            $table->json('items');                           // pozycje FV (name, qty, net_gr, vat_rate, gross_gr)
            $table->unsignedInteger('total_net_gr');
            $table->unsignedInteger('total_vat_gr');
            $table->unsignedInteger('total_gross_gr');
            $table->enum('status', ['draft', 'queued', 'sent', 'accepted', 'rejected'])->default('draft');
            $table->string('ksef_reference_number')->nullable();
            $table->timestamp('ksef_acquisition_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
