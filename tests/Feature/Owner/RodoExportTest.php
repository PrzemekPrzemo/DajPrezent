<?php

declare(strict_types=1);

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Models\User;

it('streams the user\'s data as JSON', function (): void {
    $owner = User::factory()->create(['name' => 'Anna Kowalska', 'email' => 'anna@example.com']);
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id, 'slug' => 'moja']);
    Gift::factory()->create(['tenant_id' => $tenant->id, 'title' => 'Aparat']);
    Invoice::create([
        'tenant_id' => $tenant->id,
        'number' => 'FV/2026/06/0001',
        'buyer_name' => 'Anna Kowalska',
        'buyer_address' => [],
        'items' => [['name' => 'Pro', 'qty' => 1, 'unit_net_gr' => 8049, 'vat_rate' => 23, 'unit_gross_gr' => 9900]],
        'total_net_gr' => 8049,
        'total_vat_gr' => 1851,
        'total_gross_gr' => 9900,
        'status' => 'sent',
    ]);

    $response = $this->actingAs($owner)->get('/panel/rodo/eksport');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/json');
    expect($response->headers->get('Content-Disposition'))->toContain('dajprezent-rodo-export-'.$owner->id);

    $body = $response->streamedContent();
    $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

    expect($data['account']['email'])->toBe('anna@example.com')
        ->and($data['tenants'][0]['slug'])->toBe('moja')
        ->and($data['tenants'][0]['gifts'][0]['title'])->toBe('Aparat')
        ->and($data['tenants'][0]['invoices'][0]['number'])->toBe('FV/2026/06/0001')
        ->and($data['rodo_notice'])->toContain('art. 20 RODO');
});

it('does NOT include guest e-mails from reservations in the export', function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);
    GiftReservation::factory()->create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'super-secret@example.com',
    ]);

    $body = $this->actingAs($owner)
        ->get('/panel/rodo/eksport')
        ->streamedContent();

    expect($body)->not->toContain('super-secret@example.com');
});

it('only exports the requesting user\'s own data', function (): void {
    $me = User::factory()->create(['email' => 'me@example.com']);
    $stranger = User::factory()->create(['email' => 'stranger@example.com']);
    Tenant::factory()->create(['owner_user_id' => $me->id, 'slug' => 'mine']);
    Tenant::factory()->create(['owner_user_id' => $stranger->id, 'slug' => 'theirs']);

    $body = $this->actingAs($me)->get('/panel/rodo/eksport')->streamedContent();

    expect($body)->toContain('mine')->not->toContain('theirs')->not->toContain('stranger@example.com');
});

it('redirects guests to /login', function (): void {
    $this->get('/panel/rodo/eksport')->assertRedirect('/login');
});
