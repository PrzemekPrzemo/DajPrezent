<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Billing\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

/**
 * Sprzedaż przez 6 ostatnich miesięcy — liczba opłaconych
 * subskrypcji per miesiąc. Pokazuje trend, nie absolutną wartość,
 * więc nie różnicujemy pakietów na osie.
 */
final class SubscriptionsLast6MonthsChart extends ChartWidget
{
    protected static ?string $heading = 'Sprzedaż 6 ostatnich miesięcy';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $payload = Cache::remember('admin.stats.subs6m', now()->addMinutes(15), function (): array {
            $months = [];
            $labels = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = now()->startOfMonth()->subMonths($i);
                // Don't filter by status='active' — that would let
                // historical sales bars shrink as users let subscriptions
                // expire/cancel, silently rewriting the past. A "what
                // was actually paid in month X" chart must be immutable
                // once the month is closed.
                $count = Subscription::query()
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->count();
                $months[] = $count;
                $labels[] = $m->translatedFormat('M Y');
            }

            return ['labels' => $labels, 'data' => $months];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Opłaconych subskrypcji',
                    'data' => $payload['data'],
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.18)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $payload['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
