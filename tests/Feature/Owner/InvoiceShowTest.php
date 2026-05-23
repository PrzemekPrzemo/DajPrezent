<?php

declare(strict_types=1);

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

function buildInvoice(int $tenantId, string $number = 'FV/2026/06/0001'): Invoice
{
    return Invoice::create([
        'tenant_id' => $tenantId,
        'number' => $number,
        'buyer_name' => 'Anna Kowalska',
        'buyer_address' => ['email' => 'anna@example.com'],
        'items' => [['name' => 'DajPrezent.pl — Pakiet Pro', 'qty' => 1, 'unit_net_gr' => 8049, 'vat_rate' => 23, 'unit_gross_gr' => 9900]],
        'total_net_gr' => 8049,
        'total_vat_gr' => 1851,
        'total_gross_gr' => 9900,
        'status' => 'sent',
        'ksef_reference_number' => 'STUB-KSEF-TEST-'.$number,
        'ksef_acquisition_at' => now(),
    ]);
}

it('renders the printable invoice view', function (): void {
    $invoice = buildInvoice($this->tenant->id);

    $this->actingAs($this->owner)
        ->get("/panel/invoices/{$invoice->id}")
        ->assertOk()
        ->assertSee('FV/2026/06/0001')
        ->assertSee('Anna Kowalska')
        ->assertSee('Pakiet Pro')
        ->assertSee('99,00 zł')             // brutto
        ->assertSee('80,49 zł')             // netto
        ->assertSee('18,51 zł')             // vat
        ->assertSee('Sendormeco Holding sp. z o.o.')
        ->assertSee('5252866457')           // seller NIP
        ->assertSee('0000906110')           // seller KRS
        ->assertSee('STUB-KSEF-TEST-');
});

it('forbids access to another owner\'s invoice', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);
    $invoice = buildInvoice($foreign->id, 'FV/2026/06/0099');

    $this->actingAs($this->owner)
        ->get("/panel/invoices/{$invoice->id}")
        ->assertNotFound();
});

it('redirects guests to /login', function (): void {
    $invoice = buildInvoice($this->tenant->id);
    $this->get("/panel/invoices/{$invoice->id}")->assertRedirect('/login');
});
