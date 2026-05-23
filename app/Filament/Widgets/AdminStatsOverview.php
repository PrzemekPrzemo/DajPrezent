<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Master-admin "north star" overview — single screen with MRR, active subs,
 * tenant growth, library size and operational load. Values are cached for
 * 5 minutes so opening the dashboard doesn't hit the DB on every refresh.
 */
final class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return Cache::remember('admin.stats.overview', now()->addMinutes(5), fn (): array => [
            $this->mrrStat(),
            $this->activeSubsStat(),
            $this->newTenantsStat(),
            $this->giftsStat(),
            $this->reservationsStat(),
            $this->supportStat(),
        ]);
    }

    private function mrrStat(): Stat
    {
        // Best-effort MRR: sum of paid sub amounts in the last 30 days,
        // amortised over the period. KISS — replace with proper accrual
        // calc when needed.
        $sumGr = (int) Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('amount_pln_gr');

        return Stat::make('Sprzedaż 30 dni', number_format($sumGr / 100, 0, ',', ' ').' zł')
            ->description('suma opłaconych pakietów')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success');
    }

    private function activeSubsStat(): Stat
    {
        $count = Subscription::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(14)])
            ->count();

        return Stat::make('Aktywne subskrypcje', (string) $count)
            ->description($expiringSoon > 0 ? "$expiringSoon wygasa w ciągu 14 dni" : 'wszystkie zdrowe')
            ->descriptionIcon('heroicon-m-clock')
            ->color($expiringSoon > 0 ? 'warning' : 'primary');
    }

    private function newTenantsStat(): Stat
    {
        $thisMonth = Tenant::query()->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = Tenant::query()
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->count();

        $delta = $lastMonth > 0 ? (int) round(($thisMonth - $lastMonth) * 100 / $lastMonth) : null;

        return Stat::make('Nowe listy w tym mc', (string) $thisMonth)
            ->description($delta === null ? 'brak danych z porównawczego miesiąca' : ($delta >= 0 ? "+$delta% vs poprzedni" : "$delta% vs poprzedni"))
            ->descriptionIcon($delta !== null && $delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($delta !== null && $delta >= 0 ? 'success' : 'danger');
    }

    private function giftsStat(): Stat
    {
        $total = Gift::query()->count();
        $thisWeek = Gift::query()->where('created_at', '>=', now()->subDays(7))->count();

        return Stat::make('Prezentów w bazie', number_format($total, 0, ',', ' '))
            ->description("+$thisWeek w ostatnim tygodniu")
            ->descriptionIcon('heroicon-m-gift')
            ->color('primary');
    }

    private function reservationsStat(): Stat
    {
        $active = GiftReservation::query()
            ->where('status', GiftReservation::STATUS_ACTIVE)
            ->count();
        $verifiedRate = $this->verifiedReservationRate();

        return Stat::make('Aktywne rezerwacje', (string) $active)
            ->description($verifiedRate.'% gości weryfikuje e-mail')
            ->descriptionIcon('heroicon-m-envelope-open')
            ->color('primary');
    }

    private function supportStat(): Stat
    {
        $open = SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count();
        $high = SupportTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->where('priority', 'high')
            ->count();

        return Stat::make('Otwarte zgłoszenia', (string) $open)
            ->description($high > 0 ? "$high z priorytetem wysokim" : 'brak pilnych')
            ->descriptionIcon('heroicon-m-lifebuoy')
            ->color($high > 0 ? 'danger' : 'success');
    }

    private function verifiedReservationRate(): int
    {
        $window = now()->subDays(30);
        $total = GiftReservation::query()->where('created_at', '>=', $window)->count();
        if ($total === 0) {
            return 0;
        }
        $verified = GiftReservation::query()
            ->where('created_at', '>=', $window)
            ->whereNotNull('email_verified_at')
            ->count();

        return (int) round($verified * 100 / $total);
    }
}
