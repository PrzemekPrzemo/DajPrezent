<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\InvoiceGenerator;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create(['name' => 'Anna Kowalska', 'email' => 'a@example.com']);
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $this->package = Package::factory()->create(['name' => 'Pro', 'price_pln_gr' => 9900]);
    $this->gen = app(InvoiceGenerator::class);
});

it('falls back to owner name/email when subscription has no billing snapshot (legacy rows)', function (): void {
    $sub = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => now(),
    ]);

    $invoice = $this->gen->generate($sub);

    expect($invoice->buyer_name)->toBe('Anna Kowalska')
        ->and($invoice->buyer_nip)->toBeNull()
        ->and($invoice->buyer_address['email'])->toBe('a@example.com');
});

it('renders B2C buyer with name + structured address', function (): void {
    $sub = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => now(),
        'buyer_name' => 'Jan Nowak',
        'buyer_street' => 'Kwiatowa 12/4',
        'buyer_postal_code' => '00-001',
        'buyer_city' => 'Warszawa',
    ]);

    $invoice = $this->gen->generate($sub);

    expect($invoice->buyer_name)->toBe('Jan Nowak')
        ->and($invoice->buyer_nip)->toBeNull()
        ->and($invoice->buyer_address['street'])->toBe('Kwiatowa 12/4')
        ->and($invoice->buyer_address['city_line'])->toBe('00-001 Warszawa');
});

it('renders B2B buyer with company name + NIP on the invoice', function (): void {
    $sub = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'amount_pln_gr' => 9900,
        'paid_at' => now(),
        'buyer_name' => 'Anna Kowalska',
        'buyer_company' => 'ACME sp. z o.o.',
        'buyer_nip' => '5252866457',
        'buyer_street' => 'Złota 75A/7',
        'buyer_postal_code' => '00-819',
        'buyer_city' => 'Warszawa',
    ]);

    $invoice = $this->gen->generate($sub);

    expect($invoice->buyer_name)->toBe('ACME sp. z o.o.')
        ->and($invoice->buyer_nip)->toBe('5252866457')
        ->and($invoice->buyer_address['street'])->toBe('Złota 75A/7')
        ->and($invoice->buyer_address['city_line'])->toBe('00-819 Warszawa');
});
