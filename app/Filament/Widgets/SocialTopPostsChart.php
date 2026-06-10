<?php

namespace App\Filament\Widgets;

use App\Services\SocialRoiService;
use Filament\Widgets\ChartWidget;

class SocialTopPostsChart extends ChartWidget
{
    protected static ?int $sort = 32;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Posts por revenue';

    protected ?string $description = 'Publicaciones que generan actividad clinica atribuida.';

    protected function getData(): array
    {
        $posts = app(SocialRoiService::class)->topPosts(8);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $posts->map(fn ($post): float => (float) $post->revenue_generated)->all(),
                    'backgroundColor' => '#0f766e',
                    'borderColor' => '#99f6e4',
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $posts->map(fn ($post): string => str($post->caption ?: $post->external_post_id)->limit(32)->toString())->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
