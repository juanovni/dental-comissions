<?php

namespace App\Filament\Resources\DoctorAssistantAssignments;

use App\Enums\ProfessionalRole;
use App\Filament\Resources\DoctorAssistantAssignments\Pages\CreateDoctorAssistantAssignment;
use App\Filament\Resources\DoctorAssistantAssignments\Pages\EditDoctorAssistantAssignment;
use App\Filament\Resources\DoctorAssistantAssignments\Pages\ListDoctorAssistantAssignments;
use App\Models\DoctorAssistantAssignment;
use App\Models\Professional;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DoctorAssistantAssignmentResource extends Resource
{
    protected static ?string $model = DoctorAssistantAssignment::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Operación Clínica';

    protected static ?string $navigationLabel = 'Asignaciones';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'asignacion';

    protected static ?string $pluralModelLabel = 'asignaciones';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('doctor_id')
                ->label('Doctor')
                ->options(fn () => Professional::query()->where('role', ProfessionalRole::Doctor->value)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Select::make('assistant_id')
                ->label('Auxiliar')
                ->options(fn () => Professional::query()->where('role', ProfessionalRole::Assistant->value)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Toggle::make('is_active')->label('Activa')->default(true),
            DatePicker::make('starts_at')->label('Inicio'),
            DatePicker::make('ends_at')->label('Fin'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('doctor.name')->label('Doctor')->searchable()->sortable(),
                TextColumn::make('assistant.name')->label('Auxiliar')->searchable()->sortable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
                TextColumn::make('starts_at')->label('Inicio')->date()->sortable(),
                TextColumn::make('ends_at')->label('Fin')->date()->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorAssistantAssignments::route('/'),
            'create' => CreateDoctorAssistantAssignment::route('/create'),
            'edit' => EditDoctorAssistantAssignment::route('/{record}/edit'),
        ];
    }
}
