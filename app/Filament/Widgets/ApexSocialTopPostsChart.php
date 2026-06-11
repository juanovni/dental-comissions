<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialTopPostsChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 37;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Posts por revenue';

    protected ?string $description = 'Publicaciones que generan actividad clinica atribuida.';

    protected function getOptions(): array
    {
        $posts = app(SocialRoiService::class)->topPosts(8);

        return $this->baseApexOptions([
            'chart' => [
                'height' => 320,
                'type' => 'bar',
            ],
            'colors' => ['#0f766e'],
            'plotOptions' => [
                'bar' => [
                    'barHeight' => '58%',
                    'borderRadius' => 6,
                    'borderRadiusApplication' => 'end',
                    'horizontal' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Revenue',
                    'data' => $posts->map(fn ($post): float => (float) $post->revenue_generated)->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $posts->map(fn ($post): string => str($post->caption ?: $post->external_post_id)->limit(32)->toString())->all(),
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
                'labels' => [
                    'style' => [
                        'colors' => '#6b7280',
                        'fontSize' => '12px',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#374151',
                        'fontSize' => '12px',
                        'fontWeight' => 500,
                    ],
                ],
            ],
        ]);
    }
}
