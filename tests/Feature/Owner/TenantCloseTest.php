<?php

declare(strict_types=1);

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'slug' => 'do-zamkniecia']);
});

it('refuses close without correct confirm_slug', function (): void {
    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/settings")
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'cos-innego'])
        ->assertSessionHasErrors('confirm_slug');

    expect(Tenant::query()->find($this->tenant->id))->not->toBeNull();
});

it('soft-deletes the tenant and renames its slug on confirmation', function (): void {
    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'do-zamkniecia'])
        ->assertRedirect(route('owner.dashboard'));

    // Active row gone via global SoftDeletes scope; visible withTrashed.
    expect(Tenant::query()->find($this->tenant->id))->toBeNull();

    $trashed = Tenant::withTrashed()->find($this->tenant->id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->is_public)->toBeFalse()
        ->and($trashed->slug)->toStartWith('closed-');
});

it('hard-deletes guest reservations (RODO purge)', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);
    $reservation = GiftReservation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'gosc@example.com',
    ]);

    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'do-zamkniecia'])
        ->assertRedirect();

    // Gone for real — not just soft-deleted.
    expect(GiftReservation::query()->find($reservation->id))->toBeNull();
});

it('removes gift image files from disk', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);
    $path = UploadedFile::fake()->image('p.jpg')->store('gifts/'.$this->tenant->id, 'public');
    $gift->update(['image_path' => $path]);
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'do-zamkniecia'])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});

it('keeps invoices intact (5-year legal retention)', function (): void {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'number' => 'FV/2026/06/0001',
        'buyer_name' => 'Anna',
        'buyer_address' => [],
        'items' => [['name' => 'X', 'qty' => 1, 'unit_net_gr' => 100, 'vat_rate' => 23, 'unit_gross_gr' => 123]],
        'total_net_gr' => 100,
        'total_vat_gr' => 23,
        'total_gross_gr' => 123,
        'status' => 'sent',
    ]);

    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'do-zamkniecia'])
        ->assertRedirect();

    expect(Invoice::query()->find($invoice->id))->not->toBeNull();
});

it('refuses to close someone else\'s tenant', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->delete("/panel/lists/{$this->tenant->id}", ['confirm_slug' => 'do-zamkniecia'])
        ->assertForbidden();

    expect(Tenant::query()->find($this->tenant->id))->not->toBeNull();
});
