<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Support\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail wysyłany do support@dajprezent.pl natychmiast po założeniu
 * ticketa przez ownera. Treść po polsku, link do ticket-a w panelu
 * (mastera-admin lub bezpośredniego widoku w owner panelu).
 */
final class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->ticket;
        $msg = (new MailMessage)
            ->subject('[Support] #'.$t->id.' '.$t->subject)
            ->line('Nowe zgłoszenie supportowe na DajPrezent.pl.')
            ->line('**Kategoria:** '.$t->category)
            ->line('**Priorytet:** '.$t->priority)
            ->line('**Od:** '.($t->user?->email ?? $t->contact_email ?? 'anon'));

        if ($t->tenant_id !== null) {
            $msg->line('**Tenant:** '.($t->tenant?->slug ?? '#'.$t->tenant_id));
        }

        return $msg
            ->line('---')
            ->line($t->body)
            ->line('---')
            ->line('SLA: odpowiedź w ciągu 1 dnia roboczego.');
    }
}
