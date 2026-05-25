<?php

declare(strict_types=1);

use App\Domain\Invoicing\InvoiceGenerator;
use App\Domain\Settings\SettingsRepository;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_master_admin' => true]);
});

it('exposes /admin/settings to master admin only', function (): void {
    $this->get('/admin/settings')->assertRedirect(); // guest → login

    $regular = User::factory()->create(['is_master_admin' => false]);
    $this->actingAs($regular)
        ->get('/admin/settings')
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('Bramka płatności — PayU', false)
        ->assertSee('Krajowy System e-Faktur', false)
        ->assertSee('Numeracja faktur', false);
});

it('SettingsRepository encrypts sensitive values at rest', function (): void {
    $repo = app(SettingsRepository::class);
    $repo->set('payu.client_secret', 'super-secret-xyz');

    $row = DB::table('app_settings')->where('key', 'payu.client_secret')->first();
    expect($row->is_encrypted)->toBe(1)
        ->and($row->value)->not->toContain('super-secret-xyz');

    // Round-trip through repository decrypts.
    expect($repo->get('payu.client_secret'))->toBe('super-secret-xyz');
});

it('SettingsRepository stores non-sensitive values as plaintext', function (): void {
    $repo = app(SettingsRepository::class);
    $repo->set('payu.pos_id', '300746');

    $row = DB::table('app_settings')->where('key', 'payu.pos_id')->first();
    expect((int) $row->is_encrypted)->toBe(0)
        ->and($row->value)->toBe('300746');
});

it('falls back to config defaults when key is unset', function (): void {
    config(['settings.defaults.foo.bar' => 'default-value']);
    expect(app(SettingsRepository::class)->get('foo.bar'))->toBe('default-value');
});

it('InvoiceGenerator honors configured number format and yearly reset', function (): void {
    $repo = app(SettingsRepository::class);
    $repo->set('invoice.number_format', 'INV-{YYYY}-{NN}');
    $repo->set('invoice.sequence_reset', 'yearly');

    // Reflection on private method to test in isolation
    $generator = app(InvoiceGenerator::class);
    $reflect = new ReflectionMethod($generator, 'nextNumber');
    $number = $reflect->invoke($generator, Carbon::create(2026, 5, 15, 14, 0, 0));

    expect($number)->toBe('INV-2026-01');
});
