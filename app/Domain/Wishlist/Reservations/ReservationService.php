<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Reservations;

use App\Domain\Wishlist\Mail\GiftReservationVerifyMail;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owns the lifecycle of a gift reservation:
 *
 *   request(...) → pending (60 min TTL) → verify(token) → active
 *                                       ↘ cancel(token) → cancelled
 *   pending past TTL is released back to available by the
 *   ReleaseExpiredReservations console command.
 *
 * Privacy: only this service ever reads the guest e-mail. Owners
 * see anonymous `reserved` status on their gifts; the model hides
 * guest_email from arrayable output by default.
 */
final class ReservationService
{
    public const PENDING_TTL_MINUTES = 60;

    public function request(Gift $gift, string $guestEmail, ?string $guestName, string $intent, ?string $ip = null): GiftReservation
    {
        if (! $gift->isAvailable()) {
            throw new ReservationException('Ten prezent został już zarezerwowany.');
        }

        $token = $this->generateToken();

        $reservation = DB::transaction(function () use ($gift, $guestEmail, $guestName, $intent, $ip, $token): GiftReservation {
            $fresh = Gift::query()->lockForUpdate()->findOrFail($gift->id);

            if (! $fresh->isAvailable()) {
                throw new ReservationException('Ten prezent został już zarezerwowany.');
            }

            return GiftReservation::create([
                'tenant_id' => $fresh->tenant_id,
                'gift_id' => $fresh->id,
                'guest_email' => mb_strtolower(trim($guestEmail)),
                'guest_name' => $guestName !== null ? trim($guestName) : null,
                'intent' => $intent,
                'status' => GiftReservation::STATUS_PENDING,
                'verification_token' => $token,
                'expires_at' => now()->addMinutes(self::PENDING_TTL_MINUTES),
                'ip' => $ip,
            ]);
        });

        Mail::to($reservation->guest_email)->queue(new GiftReservationVerifyMail($reservation));

        return $reservation;
    }

    /**
     * Confirms ownership of the e-mail and flips the gift to `reserved`.
     *
     * Idempotent: re-clicking the link after success returns the
     * already-active reservation instead of throwing.
     */
    public function verify(string $token): GiftReservation
    {
        // Use an outcome tuple so we can commit "expired"/"taken" bookkeeping
        // before raising — throwing inside the transaction would roll back
        // the very status update we want to persist.
        [$reservation, $error] = DB::transaction(function () use ($token): array {
            $reservation = GiftReservation::query()
                ->where('verification_token', $token)
                ->lockForUpdate()
                ->first();

            if ($reservation === null) {
                return [null, 'Link aktywacyjny jest nieprawidłowy.'];
            }

            if ($reservation->status === GiftReservation::STATUS_ACTIVE) {
                return [$reservation, null];
            }

            if ($reservation->status !== GiftReservation::STATUS_PENDING) {
                return [$reservation, 'Ta rezerwacja została już anulowana lub wygasła.'];
            }

            if ($reservation->expires_at !== null && $reservation->expires_at->isPast()) {
                $reservation->update(['status' => GiftReservation::STATUS_EXPIRED]);

                return [$reservation, 'Link aktywacyjny wygasł — zarezerwuj prezent ponownie.'];
            }

            $gift = Gift::query()->lockForUpdate()->findOrFail($reservation->gift_id);

            if (! $gift->isAvailable()) {
                $reservation->update(['status' => GiftReservation::STATUS_EXPIRED]);

                return [$reservation, 'Niestety, ktoś inny zdążył zarezerwować ten prezent.'];
            }

            $reservation->update([
                'status' => GiftReservation::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'expires_at' => null,
            ]);
            $gift->update(['status' => Gift::STATUS_RESERVED]);

            return [$reservation->refresh(), null];
        });

        if ($error !== null) {
            throw new ReservationException($error);
        }

        /** @var GiftReservation $reservation */
        return $reservation;
    }

    /**
     * Guest-side cancellation (using their token from the confirmation mail).
     */
    public function cancel(string $token): GiftReservation
    {
        return DB::transaction(function () use ($token): GiftReservation {
            $reservation = GiftReservation::query()
                ->where('verification_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($reservation->status, [GiftReservation::STATUS_CANCELLED, GiftReservation::STATUS_EXPIRED], true)) {
                return $reservation;
            }

            $wasActive = $reservation->status === GiftReservation::STATUS_ACTIVE;

            $reservation->update([
                'status' => GiftReservation::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            if ($wasActive) {
                $gift = Gift::query()->lockForUpdate()->findOrFail($reservation->gift_id);
                if ($gift->status === Gift::STATUS_RESERVED) {
                    $gift->update(['status' => Gift::STATUS_AVAILABLE]);
                }
            }

            return $reservation->refresh();
        });
    }

    /**
     * Cron-driven cleanup. Pending reservations past TTL release their
     * implicit hold (gift stays `available`). Active reservations are
     * left alone — only owner action moves them to `received`.
     */
    public function releaseExpired(): int
    {
        return GiftReservation::query()
            ->where('status', GiftReservation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => GiftReservation::STATUS_EXPIRED]);
    }

    private function generateToken(): string
    {
        $token = Str::random(48);
        if ($token === '') {
            throw new RuntimeException('Failed to generate verification token.');
        }

        return $token;
    }
}
