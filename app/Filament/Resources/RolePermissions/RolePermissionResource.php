<?php

namespace App\Filament\Resources\RolePermissions;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Filament\Resources\RolePermissions\Pages\EditRolePermission;
use App\Filament\Resources\RolePermissions\Pages\ListRolePermissions;
use App\Models\RolePermission;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Permisos de roles';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'permiso de rol';

    protected static ?string $pluralModelLabel = 'permisos de roles';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('role')
                ->label('Rol')
                ->options(UserRole::options())
                ->disabled()
                ->dehydrated(false),
            Select::make('permission')
                ->label('Permiso')
                ->options(UserPermission::options())
                ->disabled()
                ->dehydrated(false),
            Toggle::make('is_enabled')
                ->label('Habilitado')
                ->helperText('Activa o desactiva este acceso para el rol seleccionado.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('role')
            ->columns([
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('permission')
                    ->label('Permiso')
                    ->formatStateUsing(fn (UserPermission $state): string => $state->label())
                    ->searchable(),
                TextColumn::make('permission_group')
                    ->label('Grupo')
                    ->state(fn (RolePermission $record): string => $record->permission->group())
                    ->badge(),
                IconColumn::make('is_enabled')
                    ->label('Habilitado')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(UserRole::options()),
                SelectFilter::make('is_enabled')
                    ->label('Estado')
                    ->options([
                        '1' => 'Habilitado',
                        '0' => 'Deshabilitado',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRolePermissions::route('/'),
            'edit' => EditRolePermission::route('/{record}/edit'),
        ];
    }
}
