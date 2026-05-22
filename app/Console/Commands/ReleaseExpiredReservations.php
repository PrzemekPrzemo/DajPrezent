<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Wishlist\Reservations\ReservationService;
use Illuminate\Console\Command;

final class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release-expired';

    protected $description = 'Mark pending gift reservations as expired once their verification TTL has passed.';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->releaseExpired();

        $this->info("Released {$count} expired reservation(s).");

        return self::SUCCESS;
    }
}
