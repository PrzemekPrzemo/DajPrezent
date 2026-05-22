<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Mail;

use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class GiftReservationVerifyMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public GiftReservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdź rezerwację prezentu — DajPrezent.pl',
        );
    }

    public function content(): Content
    {
        $verifyUrl = url(route('public.reservations.verify', [
            'token' => $this->reservation->verification_token,
        ], absolute: false));

        $cancelUrl = url(route('public.reservations.cancel', [
            'token' => $this->reservation->verification_token,
        ], absolute: false));

        return new Content(
            markdown: 'emails.reservations.verify',
            with: [
                'reservation' => $this->reservation,
                'verifyUrl' => $verifyUrl,
                'cancelUrl' => $cancelUrl,
                'ttlMinutes' => 60,
            ],
        );
    }
}
