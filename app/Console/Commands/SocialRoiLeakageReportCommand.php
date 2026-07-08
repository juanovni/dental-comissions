<?php

namespace App\Console\Commands;

use App\Services\SocialRoiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SocialRoiLeakageReportCommand extends Command
{
    protected $signature = 'social:roi-leakage-report
        {--week= : Fecha dentro de la semana a reportar, formato YYYY-MM-DD}
        {--output= : Ruta relativa en storage/app para guardar el PDF}
        {--min=1000 : Valor minimo estimado para incluir leads perdidos}';

    protected $description = 'Genera el reporte PDF semanal de fuga comercial social.';

    public function handle(SocialRoiService $service): int
    {
        $weekOption = $this->option('week');
        $weekStart = $weekOption
            ? CarbonImmutable::parse((string) $weekOption)->startOfWeek()
            : now()->subWeek()->toImmutable()->startOfWeek();
        $minimumValue = (float) $this->option('min');

        $report = $service->weeklyLeakageReport($weekStart, $minimumValue);
        $output = $this->option('output') ?: sprintf(
            'reports/social-roi/fuga-comercial-%s.pdf',
            $report['period_start']->format('Y-m-d'),
        );

        $pdf = Pdf::loadView('social-roi-leakage-report-pdf', ['report' => $report])
            ->setPaper('letter');

        Storage::disk('local')->put((string) $output, $pdf->output());

        $this->info('Reporte de fuga comercial generado: storage/app/'.$output);
        $this->line('Leads incluidos: '.$report['total_leads']);
        $this->line('Valor estimado perdido: $'.number_format($report['total_value'], 2));

        return self::SUCCESS;
    }
}
