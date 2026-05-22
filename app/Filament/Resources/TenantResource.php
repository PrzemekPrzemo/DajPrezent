<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Tenancy\Models\Tenant;
use App\Filament\Resources\TenantResource\Pages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationGroup = 'Klienci';

    protected static ?string $label = 'lista';

    protected static ?string $pluralLabel = 'listy klientów';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nazwa listy')
                ->required()
                ->maxLength(120),
            TextInput::make('slug')
                ->label('Slug (dajprezent.pl/...)')
                ->required()
                ->maxLength(40)
                ->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9][a-z0-9-]{0,38}[a-z0-9]$/'),
            Select::make('owner_user_id')
                ->label('Właściciel')
                ->relationship('owner', 'email')
                ->searchable()
                ->required(),
            Select::make('kind')
                ->options([
                    'wishlist' => 'Wishlist',
                    'wedding_basic' => 'Wedding Basic',
                    'wedding_premium' => 'Wedding Premium',
                ])
                ->required(),
            Select::make('locale')
                ->options(['pl' => 'Polski', 'en' => 'English'])
                ->default('pl')
                ->required(),
            DateTimePicker::make('expires_at')
                ->label('Wygasa')
                ->seconds(false),
            Toggle::make('is_public')->label('Publiczna'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(),
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable()->copyable()
                    ->url(fn (Tenant $r): string => url('/'.$r->slug), shouldOpenInNewTab: true),
                TextColumn::make('owner.email')->label('Właściciel')->searchable(),
                BadgeColumn::make('kind')->colors([
                    'success' => 'wishlist',
                    'warning' => 'wedding_basic',
                    'primary' => 'wedding_premium',
                ]),
                TextColumn::make('expires_at')->label('Wygasa')->dateTime('d.m.Y')->sortable(),
                TextColumn::make('gifts_count')->counts('gifts')->label('Prezentów'),
                BadgeColumn::make('is_public')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'publiczna' : 'ukryta')
                    ->colors(['success' => fn ($state) => (bool) $state, 'danger' => fn ($state) => ! (bool) $state]),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('kind')->options([
                    'wishlist' => 'Wishlist',
                    'wedding_basic' => 'Wedding Basic',
                    'wedding_premium' => 'Wedding Premium',
                ]),
                Filter::make('expired')
                    ->label('Wygasłe')
                    ->query(fn (Builder $q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now())),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
