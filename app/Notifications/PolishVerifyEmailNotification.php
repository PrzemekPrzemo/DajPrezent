<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class PolishVerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Potwierdź adres e-mail — DajPrezent.pl')
            ->greeting('Cześć '.($notifiable->name ?? '').'!')
            ->line('Aby aktywować swoje konto, kliknij poniższy przycisk.')
            ->action('Potwierdzam adres e-mail', $url)
            ->line('Jeśli nie zakładałeś konta, możesz zignorować tę wiadomość.')
            ->salutation('Pozdrawiamy, zespół DajPrezent.pl');
    }
}
