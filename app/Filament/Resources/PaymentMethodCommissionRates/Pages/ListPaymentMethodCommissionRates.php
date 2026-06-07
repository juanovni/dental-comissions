<?php

namespace App\Filament\Resources\PaymentMethodCommissionRates\Pages;

use App\Filament\Resources\PaymentMethodCommissionRates\PaymentMethodCommissionRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethodCommissionRates extends ListRecords
{
    protected static string $resource = PaymentMethodCommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
