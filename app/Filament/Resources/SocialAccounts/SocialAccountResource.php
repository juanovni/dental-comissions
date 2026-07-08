<?php

namespace App\Filament\Resources\SocialAccounts;

use App\Enums\SocialPlatform;
use App\Filament\Resources\SocialAccounts\Pages\EditSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\ListSocialAccounts;
use App\Models\SocialAccount;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string | \UnitEnum | null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Cuentas sociales';

    protected static ?string $modelLabel = 'cuenta social';

    protected static ?string $pluralModelLabel = 'cuentas sociales';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('account_name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('external_account_id')->label('ID externo')->readOnly(),
            TextInput::make('page_id')->label('Page ID')->readOnly(),
            TextInput::make('instagram_business_account_id')->label('Instagram Business ID')->readOnly(),
            Toggle::make('is_active')->label('Activa'),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('platform')
                    ->label('Red')
                    ->badge()
                    ->formatStateUsing(fn (SocialPlatform $state): string => $state->label()),
                TextColumn::make('account_name')->label('Cuenta')->searchable()->sortable(),
                TextColumn::make('external_account_id')->label('ID externo')->searchable()->toggleable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
                TextColumn::make('posts_count')->counts('posts')->label('Posts')->sortable(),
                TextColumn::make('comments_count')->counts('comments')->label('Comentarios')->sortable(),
                TextColumn::make('last_synced_at')->label('Ultima sync')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Actualizada')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Red')
                    ->options(collect(SocialPlatform::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->placeholder('Todas')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialAccounts::route('/'),
            'edit' => EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
