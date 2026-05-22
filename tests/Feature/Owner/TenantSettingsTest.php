<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

it('shows the settings page to the owner', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/settings")
        ->assertOk()
        ->assertSee('Ustawienia listy');
});

it('forbids access to a stranger tenant settings', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get("/panel/lists/{$this->tenant->id}/settings")
        ->assertForbidden();
});

it('updates the tenant name', function (): void {
    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/settings", [
            'name' => 'Nowa Nazwa',
        ])
        ->assertRedirect();

    expect($this->tenant->fresh()->name)->toBe('Nowa Nazwa');
});

it('sets a password hash when one is provided', function (): void {
    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/settings", [
            'name' => $this->tenant->name,
            'password' => 'tajny',
        ])
        ->assertRedirect();

    $this->tenant->refresh();
    expect($this->tenant->password_hash)->not->toBeNull()
        ->and(Hash::check('tajny', (string) $this->tenant->password_hash))->toBeTrue();
});

it('removes the password when remove_password is set', function (): void {
    $this->tenant->update(['password_hash' => Hash::make('istniejace')]);

    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/settings", [
            'name' => $this->tenant->name,
            'remove_password' => '1',
        ])
        ->assertRedirect();

    expect($this->tenant->fresh()->password_hash)->toBeNull();
});

it('rejects too-short passwords', function (): void {
    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/settings")
        ->patch("/panel/lists/{$this->tenant->id}/settings", [
            'name' => $this->tenant->name,
            'password' => 'ab',
        ])
        ->assertSessionHasErrors('password');
});
