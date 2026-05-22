<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Billing\Models\Subscription;
use App\Filament\Resources\SubscriptionResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $label = 'subskrypcja';

    protected static ?string $pluralLabel = 'subskrypcje';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 15;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tenant.slug')
                    ->label('Tenant')
                    ->searchable()
                    ->url(fn (Subscription $r): string => url('/'.$r->tenant?->slug), shouldOpenInNewTab: true),
                TextColumn::make('package.name')->label('Pakiet')->badge()->sortable(),
                TextColumn::make('amount_pln_gr')
                    ->label('Kwota')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' zł')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => fn ($state): bool => in_array($state, ['cancelled', 'refunded'], true),
                    ]),
                TextColumn::make('paid_at')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('expires_at')
                    ->label('Wygasa')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->color(fn (Subscription $r): ?string => $r->expires_at?->diffInDays(now(), absolute: false) > -14 ? 'danger' : null),
                TextColumn::make('payu_order_id')->label('PayU')->toggleable(isToggledHiddenByDefault: true)->copyable(),
                TextColumn::make('invoice.number')->label('FV')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Oczekuje',
                    'active' => 'Aktywna',
                    'expired' => 'Wygasła',
                    'cancelled' => 'Anulowana',
                    'refunded' => 'Zwrot',
                ]),
                Filter::make('expiring_soon')
                    ->label('Wygasające w 14 dni')
                    ->query(fn (Builder $q) => $q->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(14))),
            ])
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Subskrypcje pochodzą z checkoutu / PayU IPN.
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
