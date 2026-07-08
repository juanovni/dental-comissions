<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

abstract class ApexChartWidget extends Widget
{
    protected string $view = 'filament.widgets.apex-chart-widget';

    protected int | string | array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $maxHeight = '320px';

    abstract protected function getOptions(): array;

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMaxHeight(): ?string
    {
        return $this->maxHeight;
    }

    public function getChartOptions(): array
    {
        return $this->getOptions();
    }

    public function getChartRenderKey(): string
    {
        $filters = property_exists($this, 'pageFilters') ? $this->pageFilters : [];

        return md5(static::class . json_encode($filters));
    }
}
