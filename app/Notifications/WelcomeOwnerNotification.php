<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, after the first successful activation of a tenant
 * (free plan immediately, paid plan via PayU IPN). Goal: get the
 * owner back into /panel within 5 minutes and adding gifts.
 */
final class WelcomeOwnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $publicUrl = url('/'.$this->tenant->slug);
        $manageUrl = url(route('owner.gifts.index', $this->tenant, absolute: false));

        return (new MailMessage)
            ->subject('Twoja lista jest gotowa — '.$this->tenant->name)
            ->greeting('Cześć '.($notifiable->name ?? '').'!')
            ->line('Twoja lista **'.$this->tenant->name.'** jest aktywna pod adresem:')
            ->line('🔗 '.$publicUrl)
            ->line('---')
            ->line('**Co dalej?**')
            ->line('1️⃣ **Dodaj prezenty** — wklej linki ze sklepów, tytuł i cena pobiorą się automatycznie.')
            ->line('2️⃣ **Udostępnij listę** — wyślij QR lub link bliskim.')
            ->line('3️⃣ **Świętuj** — bliscy zarezerwują anonimowo, Ty zobaczysz tylko statusy.')
            ->action('Dodaj pierwszy prezent →', $manageUrl)
            ->line('Pamiętaj — adres e-mail osób rezerwujących **nigdy nie trafia do Ciebie**. To Twoja gwarancja niespodzianki.')
            ->salutation('Powodzenia, zespół DajPrezent.pl');
    }
}
