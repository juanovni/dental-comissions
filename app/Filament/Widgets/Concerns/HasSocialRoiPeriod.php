<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\SocialRoiPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait HasSocialRoiPeriod
{
    use InteractsWithPageFilters;

    protected function socialRoiPeriodLabel(): string
    {
        return SocialRoiPeriod::resolve($this->pageFilters)['label'];
    }

    protected function socialRoiDescription(string $description): string
    {
        return $description . ' Periodo: ' . $this->socialRoiPeriodLabel() . '.';
    }
}
