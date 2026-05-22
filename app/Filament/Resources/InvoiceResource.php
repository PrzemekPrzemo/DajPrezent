<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Invoicing\Models\Invoice;
use App\Filament\Resources\InvoiceResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $label = 'faktura';

    protected static ?string $pluralLabel = 'faktury';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 16;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Numer')->searchable()->copyable(),
                TextColumn::make('tenant.slug')->label('Tenant')->searchable(),
                TextColumn::make('buyer_name')->label('Nabywca')->searchable(),
                TextColumn::make('buyer_nip')->label('NIP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_gross_gr')
                    ->label('Brutto')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' zł')
                    ->sortable(),
                TextColumn::make('total_net_gr')
                    ->label('Netto')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' zł')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_vat_gr')
                    ->label('VAT')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' zł')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'queued',
                        'success' => fn ($state): bool => in_array($state, ['sent', 'accepted'], true),
                        'danger' => 'rejected',
                    ]),
                TextColumn::make('ksef_reference_number')
                    ->label('KSeF')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('ksef_acquisition_at')->label('KSeF data')->dateTime('d.m.Y H:i')->toggleable(),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Szkic',
                    'queued' => 'W kolejce',
                    'sent' => 'Wysłana',
                    'accepted' => 'Zaakceptowana',
                    'rejected' => 'Odrzucona',
                ]),
            ])
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
