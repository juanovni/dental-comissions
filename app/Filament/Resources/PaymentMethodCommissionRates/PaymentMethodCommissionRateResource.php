<?php

namespace App\Filament\Resources\PaymentMethodCommissionRates;

use App\Filament\Resources\PaymentMethodCommissionRates\Pages\CreatePaymentMethodCommissionRate;
use App\Filament\Resources\PaymentMethodCommissionRates\Pages\EditPaymentMethodCommissionRate;
use App\Filament\Resources\PaymentMethodCommissionRates\Pages\ListPaymentMethodCommissionRates;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodCommissionRate;
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
use Filament\Tables\Table;

class PaymentMethodCommissionRateResource extends Resource
{
    protected static ?string $model = PaymentMethodCommissionRate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Comisiones por pago';

    protected static ?string $modelLabel = 'comision por metodo de pago';

    protected static ?string $pluralModelLabel = 'comisiones por metodo de pago';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('payment_method_id')
                ->label('Metodo de pago')
                ->options(fn () => PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('amount')->label('Monto')->numeric()->prefix('$')->required(),
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
                TextColumn::make('paymentMethod.name')->label('Metodo de pago')->searchable()->sortable(),
                TextColumn::make('amount')->label('Monto')->money('USD')->sortable(),
                TextColumn::make('starts_at')->label('Inicio')->date(),
                TextColumn::make('ends_at')->label('Fin')->date(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethodCommissionRates::route('/'),
            'create' => CreatePaymentMethodCommissionRate::route('/create'),
            'edit' => EditPaymentMethodCommissionRate::route('/{record}/edit'),
        ];
    }
}
