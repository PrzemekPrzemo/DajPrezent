<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Top pakiety po liczbie aktywnych subskrypcji. Pokazuje który
 * pakiet faktycznie się sprzedaje — bywa, że "polecany" w UI
 * nie odzwierciedla rzeczywistego best-sellera.
 */
final class TopPackagesWidget extends BaseWidget
{
    protected static ?string $heading = 'Najczęściej kupowane pakiety';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Package::query()
                    ->select('packages.*')
                    ->selectSub(
                        Subscription::query()
                            ->whereColumn('subscriptions.package_id', 'packages.id')
                            ->where('status', 'active')
                            ->selectRaw('COUNT(*)'),
                        'active_subs_count'
                    )
                    ->where('is_active', true)
                    ->orderByDesc('active_subs_count')
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Pakiet')->weight('bold'),
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('price_pln_gr')
                    ->label('Cena')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 0, ',', ' ').' zł'),
                TextColumn::make('active_subs_count')
                    ->label('Aktywne')
                    ->numeric()
                    ->sortable()
                    ->color('success'),
            ]);
    }
}
