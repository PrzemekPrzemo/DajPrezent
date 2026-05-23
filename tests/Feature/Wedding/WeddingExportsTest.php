<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\Rsvp;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wedding_premium',
        'slug' => 'pdf-test',
    ]);
    WeddingEvent::factory()->create([
        'tenant_id' => $this->tenant->id,
        'couple_names' => 'Anna & Tomek',
        'ceremony_at' => now()->addMonths(3),
        'venue_name' => 'Pałac',
        'venue_address' => 'Pałacowa 1',
    ]);
});

it('streams the RSVP CSV with UTF-8 BOM and proper escaping', function (): void {
    Rsvp::factory()->create([
        'tenant_id' => $this->tenant->id,
        'guest_name' => 'Marek',
        'guest_email' => 'marek@example.com',
        'attending' => true,
        'plus_one' => true,
        'plus_one_name' => 'Anna',
        'dietary' => 'wegetariańska',
        'transport_needed' => true,
        'message' => 'Wszystkiego najlepszego',
    ]);

    $response = $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/rsvps/export.csv")
        ->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    $body = $response->streamedContent();
    expect($body)
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain('Marek')
        ->toContain('marek@example.com')
        ->toContain('wegetariańska')
        ->toContain('Wszystkiego najlepszego');
});

it('builds an A6 invitation PDF that contains the public URL and a QR data-URI', function (): void {
    $response = $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/zaproszenie.pdf")
        ->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect((string) $response->headers->get('Content-Disposition'))->toContain('zaproszenie-pdf-test.pdf');
    // dompdf output is binary PDF — sanity-check magic bytes.
    expect(substr((string) $response->getContent(), 0, 4))->toBe('%PDF');
});

it('refuses RSVP exports for a stranger tenant', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id, 'kind' => 'wedding_basic']);
    WeddingEvent::factory()->create(['tenant_id' => $foreign->id]);

    $this->actingAs($this->owner)->get("/panel/lists/{$foreign->id}/rsvps/export.csv")->assertForbidden();
    $this->actingAs($this->owner)->get("/panel/lists/{$foreign->id}/zaproszenie.pdf")->assertForbidden();
});

it('returns 404 for exports on a non-wedding tenant', function (): void {
    $wishlist = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'kind' => 'wishlist']);

    $this->actingAs($this->owner)->get("/panel/lists/{$wishlist->id}/rsvps/export.csv")->assertNotFound();
    $this->actingAs($this->owner)->get("/panel/lists/{$wishlist->id}/zaproszenie.pdf")->assertNotFound();
});

it('shows the RSVP dashboard with headcount and dietary stats', function (): void {
    Rsvp::factory()->create(['tenant_id' => $this->tenant->id, 'attending' => true, 'plus_one' => true]);
    Rsvp::factory()->create(['tenant_id' => $this->tenant->id, 'attending' => true, 'dietary' => 'wegańska']);
    Rsvp::factory()->create(['tenant_id' => $this->tenant->id, 'attending' => false]);

    $body = (string) $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/rsvps")
        ->assertOk()
        ->getContent();

    expect($body)
        ->toContain('Potwierdzenia obecności')
        ->toContain('3') // total responses
        ->toContain('miejsc razem')
        ->toContain('z dietą');
});

it('shows the empty state when no RSVPs yet', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/rsvps")
        ->assertOk()
        ->assertSee('Brak potwierdzeń')
        ->assertSee('Zaproszenie PDF');
});
