<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\InvoiceGenerator;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create(['name' => 'Anna Kowalska', 'email' => 'a@example.com']);
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $this->package = Package::factory()->create(['name' => 'Pro', 'price_pln_gr' => 9900]);
    $this->subscription = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'status' => 'pending',
        'paid_at' => now()->setDate(2026, 6, 15),
    ]);
    $this->gen = app(InvoiceGenerator::class);
});

it('creates an invoice with monthly-counter numbering', function (): void {
    $invoice = $this->gen->generate($this->subscription);

    expect($invoice->number)->toBe('FV/2026/06/0001')
        ->and($invoice->buyer_name)->toBe('Anna Kowalska')
        ->and($invoice->status)->toBe('queued');
});

it('increments the counter for subsequent invoices in the same month', function (): void {
    $first = $this->gen->generate($this->subscription);

    $other = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => now()->setDate(2026, 6, 28),
    ]);
    $second = $this->gen->generate($other);

    expect($first->number)->toBe('FV/2026/06/0001')
        ->and($second->number)->toBe('FV/2026/06/0002');
});

it('restarts the counter at month boundary', function (): void {
    $this->gen->generate($this->subscription);

    $other = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => now()->setDate(2026, 7, 1),
    ]);
    $july = $this->gen->generate($other);

    expect($july->number)->toBe('FV/2026/07/0001');
});

it('splits gross into net + 23%% VAT without rounding drift', function (): void {
    $invoice = $this->gen->generate($this->subscription);

    expect($invoice->total_gross_gr)->toBe(9900)
        ->and($invoice->total_net_gr + $invoice->total_vat_gr)->toBe(9900)
        // 9900 / 1.23 = 8048.78 → 8049 grosze net, 1851 VAT
        ->and($invoice->total_net_gr)->toBe(8049)
        ->and($invoice->total_vat_gr)->toBe(1851);
});

it('stores a single line item with the package name', function (): void {
    $invoice = $this->gen->generate($this->subscription);

    expect($invoice->items)->toBeArray()->toHaveCount(1)
        ->and($invoice->items[0]['name'])->toContain('Pro')
        ->and($invoice->items[0]['vat_rate'])->toBe(23)
        ->and($invoice->items[0]['unit_gross_gr'])->toBe(9900);
});

it('queries invoices across tenants for numbering (system-wide counter)', function (): void {
    $first = $this->gen->generate($this->subscription);

    $otherTenant = Tenant::factory()->create();
    $other = Subscription::factory()->create([
        'tenant_id' => $otherTenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => $this->subscription->paid_at,
    ]);
    $second = $this->gen->generate($other);

    expect($second->number)->toBe('FV/2026/06/0002');

    // Even though invoices live on different tenants, both rows survive
    // the global scope query (admin context — no current tenant set).
    expect(Invoice::query()->count())->toBe(2);
});
