<?php

namespace App\Filament\Resources\PaymentMethodCommissionRates\Pages;

use App\Filament\Resources\PaymentMethodCommissionRates\PaymentMethodCommissionRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethodCommissionRate extends EditRecord
{
    protected static string $resource = PaymentMethodCommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
