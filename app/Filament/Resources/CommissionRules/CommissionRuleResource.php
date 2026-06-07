<?php

namespace App\Filament\Resources\CommissionRules;

use App\Enums\CommissionType;
use App\Enums\ProfessionalRole;
use App\Filament\Resources\CommissionRules\Pages\CreateCommissionRule;
use App\Filament\Resources\CommissionRules\Pages\EditCommissionRule;
use App\Filament\Resources\CommissionRules\Pages\ListCommissionRules;
use App\Models\CommissionRule;
use App\Models\Procedure;
use App\Models\Professional;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommissionRuleResource extends Resource
{
    protected static ?string $model = CommissionRule::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Reglas de comision';

    protected static ?string $modelLabel = 'regla de comision';

    protected static ?string $pluralModelLabel = 'reglas de comision';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Select::make('role')
                ->label('Rol')
                ->options([
                    ProfessionalRole::Doctor->value => 'Doctor',
                    ProfessionalRole::Assistant->value => 'Auxiliar',
                ])
                ->required(),
            Select::make('professional_id')
                ->label('Profesional especifico')
                ->options(fn () => Professional::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->helperText('Opcional. Si queda vacio aplica como regla general por rol/procedimiento.'),
            Select::make('procedure_id')
                ->label('Procedimiento especifico')
                ->options(fn () => Procedure::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->helperText('Opcional. Si queda vacio aplica como regla general del rol/profesional.'),
            Select::make('commission_type')
                ->label('Tipo de comision')
                ->options([
                    CommissionType::FixedPerProcedure->value => 'Fija por procedimiento',
                    CommissionType::PercentageOfInternalRate->value => 'Porcentaje de tarifa interna',
                    CommissionType::Mixed->value => 'Mixta',
                    CommissionType::None->value => 'Sin comision',
                ])
                ->required(),
            TextInput::make('fixed_amount')->label('Monto fijo')->numeric()->prefix('$'),
            TextInput::make('percentage_value')->label('Porcentaje')->numeric()->suffix('%'),
            TextInput::make('internal_rate')->label('Tarifa interna override')->numeric()->prefix('$'),
            DatePicker::make('starts_at')->label('Inicio'),
            DatePicker::make('ends_at')->label('Fin'),
            Toggle::make('is_active')->label('Activa')->default(true),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('role')->label('Rol')->badge()->formatStateUsing(fn (ProfessionalRole $state): string => $state === ProfessionalRole::Doctor ? 'Doctor' : 'Auxiliar'),
                TextColumn::make('professional.name')->label('Profesional')->placeholder('General'),
                TextColumn::make('procedure.name')->label('Procedimiento')->placeholder('General'),
                TextColumn::make('commission_type')->label('Tipo')->badge(),
                TextColumn::make('fixed_amount')->label('Fijo')->money('USD'),
                TextColumn::make('percentage_value')->label('%'),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rol')->options([
                    ProfessionalRole::Doctor->value => 'Doctor',
                    ProfessionalRole::Assistant->value => 'Auxiliar',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissionRules::route('/'),
            'create' => CreateCommissionRule::route('/create'),
            'edit' => EditCommissionRule::route('/{record}/edit'),
        ];
    }
}
