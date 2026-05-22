<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'slug' => 'test-lista']);
});

function packageWithExport(bool $export): Package
{
    return Package::factory()->create([
        'price_pln_gr' => 9900,
        'features' => ['export' => $export],
    ]);
}

function activeSub(int $tenantId, int $packageId): Subscription
{
    return Subscription::factory()->create([
        'tenant_id' => $tenantId,
        'package_id' => $packageId,
        'status' => 'active',
        'amount_pln_gr' => 9900,
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
}

it('exports gifts as UTF-8 CSV when the active package allows export', function (): void {
    activeSub($this->tenant->id, packageWithExport(true)->id);

    Gift::factory()->create([
        'tenant_id' => $this->tenant->id,
        'title' => 'Książka „Władca Pierścieni"',
        'price_pln_gr' => 12999,
        'priority' => 1,
        'status' => Gift::STATUS_RESERVED,
    ]);

    $response = $this->actingAs($this->owner)->get("/panel/lists/{$this->tenant->id}/gifts/export.csv");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('dajprezent-test-lista-');

    $body = $response->streamedContent();
    expect($body)->toStartWith("\xEF\xBB\xBF") // UTF-8 BOM
        ->toContain('Tytuł')
        ->toContain('Władca Pierścieni')
        ->toContain('129,99')
        ->toContain('muszę mieć')
        ->toContain('zarezerwowany');
});

it('refuses export for a package without the export feature', function (): void {
    activeSub($this->tenant->id, packageWithExport(false)->id);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts/export.csv")
        ->assertForbidden();
});

it('refuses export when there is no active subscription', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts/export.csv")
        ->assertForbidden();
});

it('refuses export for a tenant the user does not own', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);
    activeSub($foreign->id, packageWithExport(true)->id);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$foreign->id}/gifts/export.csv")
        ->assertForbidden();
});

it('exports nothing more than the current tenant\'s gifts', function (): void {
    activeSub($this->tenant->id, packageWithExport(true)->id);

    Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Mine']);
    $other = Tenant::factory()->create();
    Gift::factory()->create(['tenant_id' => $other->id, 'title' => 'Theirs']);

    $body = $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts/export.csv")
        ->streamedContent();

    expect($body)->toContain('Mine')
        ->not->toContain('Theirs');
});
