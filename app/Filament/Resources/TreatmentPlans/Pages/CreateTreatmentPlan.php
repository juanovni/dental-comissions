<?php

namespace App\Filament\Resources\TreatmentPlans\Pages;

use App\Filament\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = Filament::getTenant()?->getKey();
        $data['code'] = $this->generateCode();
        $data['created_by'] = auth()->id();

        return $data;
    }

    private function generateCode(): string
    {
        $year = date('Y');
        $clinicId = Filament::getTenant()?->getKey();
        $lastPlan = \App\Models\TreatmentPlan::where('clinic_id', $clinicId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('PT-%s-%05d', $year, $lastPlan + 1);
    }
}
