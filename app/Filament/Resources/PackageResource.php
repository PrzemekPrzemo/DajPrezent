<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Billing\Models\Package;
use App\Filament\Resources\PackageResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $label = 'pakiet';

    protected static ?string $pluralLabel = 'pakiety';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->disabled()->required(),
            TextInput::make('name')->required()->maxLength(80),
            Select::make('kind')->options(['standard' => 'Standard', 'wedding' => 'Wedding'])->required(),
            TextInput::make('price_pln_gr')
                ->label('Cena (grosze)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->helperText('Wartość brutto w groszach, np. 9900 = 99,00 zł'),
            TextInput::make('valid_days')->required()->numeric()->minValue(1)->maxValue(3650),
            TextInput::make('gift_limit')->label('Limit prezentów (puste = bez limitu)')->numeric()->minValue(0),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('kind')->badge(),
                TextColumn::make('price_pln_gr')
                    ->label('Cena')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' zł')
                    ->sortable(),
                TextColumn::make('valid_days')->label('Ważność (dni)'),
                TextColumn::make('gift_limit')->label('Limit'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('price_pln_gr')
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Pakiety pochodzą z config/packages.php przez PackageSeeder.
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
