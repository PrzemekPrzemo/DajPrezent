<?php

declare(strict_types=1);

use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->user = User::factory()->create(['email' => 'owner@example.com']);
});

it('renders support index empty state for fresh user', function (): void {
    $this->actingAs($this->user)
        ->get('/panel/wsparcie')
        ->assertOk()
        ->assertSee('Nie masz jeszcze zgłoszeń', false);
});

it('renders the create form with category, priority, optional tenant', function (): void {
    Tenant::factory()->create(['owner_user_id' => $this->user->id, 'name' => 'Moja Lista']);

    $this->actingAs($this->user)
        ->get('/panel/wsparcie/nowe')
        ->assertOk()
        ->assertSee('Kategoria', false)
        ->assertSee('Priorytet', false)
        ->assertSee('Moja Lista');
});

it('stores a ticket, mails support, redirects to show', function (): void {
    Notification::fake();
    config(['seller.contact.email' => 'support@dajprezent.pl']);

    $response = $this->actingAs($this->user)->post('/panel/wsparcie', [
        'category' => 'technical',
        'priority' => 'high',
        'subject' => 'Nie działa upload zdjęcia',
        'body' => 'Klikam Wgraj i nic się nie dzieje, console pokazuje 500.',
    ])->assertRedirect();

    $ticket = SupportTicket::query()->latest('id')->firstOrFail();
    expect($ticket->user_id)->toBe($this->user->id)
        ->and($ticket->category)->toBe('technical')
        ->and($ticket->priority)->toBe('high')
        ->and($ticket->status)->toBe('open')
        ->and($ticket->contact_email)->toBe('owner@example.com');

    Notification::assertSentOnDemand(SupportTicketCreatedNotification::class);
});

it('rejects ticket pinned to a stranger tenant', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($this->user)
        ->from('/panel/wsparcie/nowe')
        ->post('/panel/wsparcie', [
            'category' => 'other',
            'priority' => 'normal',
            'subject' => 'X',
            'body' => 'Y',
            'tenant_id' => $foreign->id,
        ])
        ->assertForbidden();

    expect(SupportTicket::query()->count())->toBe(0);
});

it('hides admin_notes + ip in toArray (owner shouldn\'t see them)', function (): void {
    $ticket = SupportTicket::factory()->create([
        'user_id' => $this->user->id,
        'admin_notes' => 'internal note',
        'ip' => '203.0.113.4',
    ]);

    $array = $ticket->toArray();
    expect($array)->not->toHaveKey('admin_notes')->not->toHaveKey('ip');
});

it('refuses to show a stranger\'s ticket', function (): void {
    $other = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get('/panel/wsparcie/'.$ticket->id)
        ->assertForbidden();
});

it('redirects guests away from /panel/wsparcie', function (): void {
    $this->get('/panel/wsparcie')->assertRedirect('/login');
});
