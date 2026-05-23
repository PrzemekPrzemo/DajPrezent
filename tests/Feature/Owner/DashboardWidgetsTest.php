<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
});

it('shows a red "wygasa za" chip when the list expires soon', function (): void {
    Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'expires_at' => now()->addDays(5),
        'name' => 'Lista A',
    ]);

    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee('wygasa za 5 dni')
        ->assertSee('bg-red-50 text-red-700', false);
});

it('shows an amber "wygasa za" chip when the list expires in 14-29 days', function (): void {
    Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'expires_at' => now()->addDays(20),
    ]);

    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee('bg-amber-50 text-amber-800', false);
});

it('shows the regular "ważna do" line when there is plenty of time', function (): void {
    Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'expires_at' => now()->addMonths(6),
    ]);

    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee('ważna do')
        ->assertSee('bg-emerald-50 text-emerald-700', false);
});

it('shows progress when gifts have receivers', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Gift::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => Gift::STATUS_AVAILABLE]);
    Gift::factory()->count(3)->create(['tenant_id' => $tenant->id, 'status' => Gift::STATUS_RESERVED]);
    Gift::factory()->count(5)->create(['tenant_id' => $tenant->id, 'status' => Gift::STATUS_RECEIVED]);

    // 8 of 10 = 80%
    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee('width: 80%', false)
        ->assertSee('80% prezentów ma odbiorcę');
});

it('does not render the progress bar for empty lists', function (): void {
    Tenant::factory()->create(['owner_user_id' => $this->owner->id]);

    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertDontSee('prezentów ma odbiorcę');
});
