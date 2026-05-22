<?php

declare(strict_types=1);

use App\Console\Commands\ReleaseExpiredReservations;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ReleaseExpiredReservations::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
