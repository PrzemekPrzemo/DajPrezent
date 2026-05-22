<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Polish copy + DajPrezent.pl branding for password reset mails.
 * Wraps Laravel's default ResetPassword to keep the token + URL
 * generation logic intact while overriding the message body.
 */
final class PolishResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], absolute: false));

        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Resetowanie hasła — DajPrezent.pl')
            ->greeting('Cześć!')
            ->line('Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.')
            ->action('Zmień hasło', $url)
            ->line("Link wygaśnie za {$minutes} minut.")
            ->line('Jeśli to nie Ty, zignoruj tę wiadomość — Twoje hasło pozostanie bez zmian.')
            ->salutation('Pozdrawiamy, zespół DajPrezent.pl');
    }
}
