<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    $this->tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'stany']);
});

it('hands the verification token to the browser via session flash after reserve', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->from('/'.$this->tenant->slug)
        ->post("/{$this->tenant->slug}/gifts/{$gift->id}/reserve", [
            'email' => 'gosc@example.com', 'intent' => 'reserve',
        ])
        ->assertRedirect()
        ->assertSessionHas('just_reserved_gift', $gift->id)
        ->assertSessionHas('just_reserved_token');

    // Token w sesji to nasz prawdziwy verification_token z DB.
    $reservation = GiftReservation::query()->where('gift_id', $gift->id)->firstOrFail();
    expect(session('just_reserved_token'))->toBe($reservation->verification_token);
});

it('emits an inline localStorage write on the wishlist page after redirect-back', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);

    $body = (string) $this->withSession([
        'just_reserved_gift' => $gift->id,
        'just_reserved_token' => 'tok-abc-1234567890',
    ])->get('/'.$this->tenant->slug)
        ->assertOk()
        ->getContent();

    expect($body)
        ->toContain("dp.reserved.{$gift->id}")
        ->toContain('tok-abc-1234567890');
});

it('renders the three guest-side states distinctively', function (): void {
    $available = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Dostepny aparat', 'status' => Gift::STATUS_AVAILABLE]);
    $reserved = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Zarezerwowane buty', 'status' => Gift::STATUS_RESERVED]);
    $received = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Otrzymany sweter', 'status' => Gift::STATUS_RECEIVED]);

    $body = (string) $this->get('/'.$this->tenant->slug)->assertOk()->getContent();

    // Available → CTA „Zarezerwuj prezent" widoczne.
    expect($body)->toContain('Zarezerwuj prezent');

    // Reserved (przez kogoś innego) → chip „Zarezerwowane 💗".
    expect($body)->toContain('Zarezerwowane');
    expect($body)->toContain('aria-label="Prezent zarezerwowany"');

    // Received → chip „otrzymany".
    expect($body)->toContain('otrzymany');

    // Wszystkie tytuły są widoczne (owner-side privacy: brak email-i gości).
    expect($body)
        ->toContain('Dostepny aparat')
        ->toContain('Zarezerwowane buty')
        ->toContain('Otrzymany sweter');
});

it('renders a "Cofnij rezerwację" anchor in the Alpine template for myToken', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'status' => Gift::STATUS_RESERVED]);

    $body = (string) $this->get('/'.$this->tenant->slug)->assertOk()->getContent();

    expect($body)->toContain('Cofnij rezerwację')
        ->toContain('Twoja rezerwacja')
        ->toContain('dp.reserved.'.$gift->id);
});

it('cancellation route (/r/cancel/{token}) frees the gift again', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'status' => Gift::STATUS_AVAILABLE]);

    // Sztucznie utwórz aktywną rezerwację i przejdź przez cancel.
    $reservation = GiftReservation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'gift_id' => $gift->id,
        'status' => GiftReservation::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $gift->update(['status' => Gift::STATUS_RESERVED]);

    $this->get('/r/cancel/'.$reservation->verification_token)->assertOk();

    expect($reservation->fresh()->status)->toBe(GiftReservation::STATUS_CANCELLED)
        ->and($gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);
});
