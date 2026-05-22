<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_master_admin' => true]);
});

it('renders the Subscriptions list for master admin', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'admin-list-tenant']);
    $package = Package::factory()->create(['name' => 'Pro']);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
        'amount_pln_gr' => 9900,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/subscriptions')
        ->assertOk()
        ->assertSee('admin-list-tenant')
        ->assertSee('Pro');
});

it('renders the Invoices list for master admin', function (): void {
    $tenant = Tenant::factory()->create();
    Invoice::create([
        'tenant_id' => $tenant->id,
        'number' => 'FV/2026/06/0001',
        'buyer_name' => 'Anna Kowalska',
        'buyer_address' => ['email' => 'a@b.com'],
        'items' => [['name' => 'Pakiet Pro', 'qty' => 1, 'unit_net_gr' => 8049, 'vat_rate' => 23, 'unit_gross_gr' => 9900]],
        'total_net_gr' => 8049,
        'total_vat_gr' => 1851,
        'total_gross_gr' => 9900,
        'status' => 'sent',
        'ksef_reference_number' => 'STUB-KSEF-TEST-FV-2026-06-0001',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/invoices')
        ->assertOk()
        ->assertSee('FV/2026/06/0001')
        ->assertSee('Anna Kowalska');
});

it('denies non-admins access to billing resources', function (): void {
    $user = User::factory()->create(['is_master_admin' => false]);

    $this->actingAs($user)->get('/admin/subscriptions')->assertForbidden();
    $this->actingAs($user)->get('/admin/invoices')->assertForbidden();
});
