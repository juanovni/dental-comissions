<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\SocialRoiPeriod;

trait HasSocialRoiWidgetPeriod
{
    private const ROI_PERIOD = '3_months';

    protected function getWidgetPeriod(): array
    {
        return SocialRoiPeriod::resolve($this->getWidgetPeriodFilters());
    }

    protected function getWidgetPeriodFilters(): array
    {
        return ['period' => self::ROI_PERIOD];
    }

    protected function socialRoiDescription(string $description): string
    {
        $label = $this->getWidgetPeriod()['label'];

        return $description . ' Periodo: ' . $label . '.';
    }
}
