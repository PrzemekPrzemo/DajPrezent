<?php

declare(strict_types=1);

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->stranger = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

function makeInvoice(int $tenantId, string $number = 'FV/2026/06/0001'): Invoice
{
    return Invoice::create([
        'tenant_id' => $tenantId,
        'number' => $number,
        'buyer_name' => 'Anna Kowalska',
        'buyer_address' => ['email' => 'a@b.com'],
        'items' => [['name' => 'Pro', 'qty' => 1, 'unit_net_gr' => 8049, 'vat_rate' => 23, 'unit_gross_gr' => 9900]],
        'total_net_gr' => 8049,
        'total_vat_gr' => 1851,
        'total_gross_gr' => 9900,
        'status' => 'sent',
        'ksef_reference_number' => 'STUB-KSEF-TEST-'.$number,
    ]);
}

it('shows only the owner\'s own invoices on /panel/invoices', function (): void {
    makeInvoice($this->tenant->id, 'FV/2026/06/0001');

    $strangerTenant = Tenant::factory()->create(['owner_user_id' => $this->stranger->id]);
    makeInvoice($strangerTenant->id, 'FV/2026/06/0009');

    $this->actingAs($this->owner)
        ->get('/panel/invoices')
        ->assertOk()
        ->assertSee('FV/2026/06/0001')
        ->assertDontSee('FV/2026/06/0009');
});

it('redirects guests away from /panel/invoices', function (): void {
    $this->get('/panel/invoices')->assertRedirect('/login');
});

it('handles empty state', function (): void {
    $this->actingAs($this->owner)
        ->get('/panel/invoices')
        ->assertOk()
        ->assertSee('Nie masz jeszcze');
});
