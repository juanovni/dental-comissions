<?php

namespace App\Filament\Resources\Clinics\RelationManagers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Usuarios de la clínica';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('pivot.role')->label('Rol en clínica')->badge(),
                TextColumn::make('role')
                    ->label('Rol global')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                IconColumn::make('pivot.is_default')->label('Default')->boolean(),
                IconColumn::make('pivot.is_active')->label('Activo en clínica')->boolean(),
                IconColumn::make('is_active')->label('Activo global')->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Asignar usuario existente')
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->orderBy('name'))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->label('Rol en clínica')
                            ->options(UserRole::options())
                            ->default(UserRole::Admin->value)
                            ->required(),
                        Toggle::make('is_default')
                            ->label('Es clínica por defecto')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Membresía activa')
                            ->default(true),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['permissions'] = null;

                        return $data;
                    }),
                Action::make('createAndAttachUser')
                    ->label('Crear usuario para clínica')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                        TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                        Select::make('global_role')
                            ->label('Rol global')
                            ->options(UserRole::options())
                            ->default(UserRole::Admin->value)
                            ->required(),
                        Toggle::make('global_is_active')
                            ->label('Activo global')
                            ->default(true),
                        Select::make('membership_role')
                            ->label('Rol en clínica')
                            ->options(UserRole::options())
                            ->default(UserRole::Admin->value)
                            ->required(),
                        Toggle::make('membership_is_default')
                            ->label('Es clínica por defecto')
                            ->default(true),
                        Toggle::make('membership_is_active')
                            ->label('Membresía activa')
                            ->default(true),
                    ])
                    ->action(function (array $data): void {
                        $user = User::query()->where('email', $data['email'])->first();

                        if ($user === null) {
                            $user = User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                                'role' => $data['global_role'],
                                'is_active' => $data['global_is_active'],
                                'remember_token' => Str::random(10),
                            ]);
                        } else {
                            $user->update([
                                'name' => $data['name'],
                                'role' => $data['global_role'],
                                'is_active' => $data['global_is_active'],
                            ]);
                        }

                        $this->getOwnerRecord()->users()->syncWithoutDetaching([
                            $user->id => [
                            'role' => $data['membership_role'],
                            'is_default' => $data['membership_is_default'],
                            'is_active' => $data['membership_is_active'],
                            'permissions' => null,
                            ],
                        ]);

                        Notification::make()
                            ->title($user->wasRecentlyCreated ? 'Usuario creado y asignado' : 'Usuario existente asignado a la clínica')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('editMembership')
                    ->label('Editar membresía')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->pivot->role,
                        'is_default' => (bool) $record->pivot->is_default,
                        'is_active' => (bool) $record->pivot->is_active,
                    ])
                    ->form([
                        Select::make('role')
                            ->label('Rol en clínica')
                            ->options(UserRole::options())
                            ->required(),
                        Toggle::make('is_default')
                            ->label('Es clínica por defecto'),
                        Toggle::make('is_active')
                            ->label('Membresía activa'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->getOwnerRecord()->users()->updateExistingPivot($record->id, [
                            'role' => $data['role'],
                            'is_default' => $data['is_default'],
                            'is_active' => $data['is_active'],
                        ]);

                        Notification::make()
                            ->title('Membresía actualizada')
                            ->success()
                            ->send();
                    }),
                DetachAction::make()
                    ->label('Quitar de la clínica'),
            ]);
    }
}
