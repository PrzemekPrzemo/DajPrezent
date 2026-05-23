<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->owner = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => Hash::make('correct-horse'),
    ]);
});

it('refuses delete without matching email confirmation', function (): void {
    $this->actingAs($this->owner)
        ->from('/panel/konto')
        ->delete('/panel/konto', [
            'confirm_email' => 'wrong@example.com',
            'current_password' => 'correct-horse',
        ])
        ->assertSessionHasErrors('confirm_email');

    expect(User::query()->find($this->owner->id))->not->toBeNull();
});

it('refuses delete without correct current password', function (): void {
    $this->actingAs($this->owner)
        ->from('/panel/konto')
        ->delete('/panel/konto', [
            'confirm_email' => 'owner@example.com',
            'current_password' => 'wrong',
        ])
        ->assertSessionHasErrors('current_password');

    expect(User::query()->find($this->owner->id))->not->toBeNull();
});

it('hard-deletes the user, closes their tenants, anonymises subscriptions', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'slug' => 'will-go']);
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);
    GiftReservation::factory()->create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'gosc@example.com',
    ]);

    $sub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create()->id,
        'status' => 'active',
        'amount_pln_gr' => 9900,
        'buyer_name' => 'Anna Kowalska',
        'buyer_company' => 'ACME',
        'buyer_nip' => '5252866457',
        'buyer_street' => 'Kwiatowa 1',
        'buyer_postal_code' => '00-001',
        'buyer_city' => 'Warszawa',
    ]);

    $this->actingAs($this->owner)
        ->delete('/panel/konto', [
            'confirm_email' => 'owner@example.com',
            'current_password' => 'correct-horse',
        ])
        ->assertRedirect(route('home'));

    // User hard-deleted.
    expect(User::query()->find($this->owner->id))->toBeNull();

    // Tenant soft-deleted.
    expect(Tenant::query()->find($tenant->id))->toBeNull();
    expect(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();

    // Reservation hard-deleted (RODO PII purge).
    expect(GiftReservation::query()->count())->toBe(0);

    // Subscription kept but anonymised.
    $sub->refresh();
    expect($sub->buyer_name)->toBe('[usunięty użytkownik]')
        ->and($sub->buyer_company)->toBeNull()
        ->and($sub->buyer_nip)->toBeNull()
        ->and($sub->buyer_city)->toBeNull();

    // Auth session destroyed.
    expect(auth()->check())->toBeFalse();
});

it('keeps invoices intact after account deletion (5-year retention)', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'slug' => 'will-go-2']);
    $invoice = Invoice::create([
        'tenant_id' => $tenant->id,
        'number' => 'FV/2026/06/0042',
        'buyer_name' => 'Anna Kowalska',
        'buyer_address' => [],
        'items' => [['name' => 'X', 'qty' => 1, 'unit_net_gr' => 100, 'vat_rate' => 23, 'unit_gross_gr' => 123]],
        'total_net_gr' => 100, 'total_vat_gr' => 23, 'total_gross_gr' => 123,
        'status' => 'sent',
    ]);

    $this->actingAs($this->owner)
        ->delete('/panel/konto', [
            'confirm_email' => 'owner@example.com',
            'current_password' => 'correct-horse',
        ])
        ->assertRedirect();

    expect(Invoice::query()->find($invoice->id))->not->toBeNull();
});

it('redirects guests to /login', function (): void {
    $this->delete('/panel/konto', [])->assertRedirect('/login');
});
