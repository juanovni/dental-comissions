<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Fuga Comercial Social</title>
    <style>
        body { color: #17202a; font-family: Helvetica, Arial, sans-serif; font-size: 11px; margin: 24px; }
        .header { background: #0f766e; color: #fff; padding: 18px 20px; }
        .header h1 { font-size: 22px; margin: 0 0 4px; }
        .header p { margin: 0; opacity: .9; }
        .summary { background: #f0fdfa; border: 1px solid #99f6e4; margin: 18px 0; padding: 14px; }
        .summary table { margin: 0; }
        .metric { color: #000000; font-size: 18px; font-weight: bold; }
        h2 { border-bottom: 1px solid #d1d5db; color: #000000; font-size: 15px; margin: 22px 0 10px; padding-bottom: 6px; }
        table { border-collapse: collapse; margin-bottom: 14px; width: 100%; }
        th { background: #134e4a; color: #fff; font-size: 9px; padding: 7px 5px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px 5px; vertical-align: top; }
        tr:nth-child(even) { background: #f9fafb; }
        .money { font-weight: bold; text-align: right; white-space: nowrap; }
        .empty { color: #6b7280; padding: 18px; text-align: center; }
        .pill { background: #fee2e2; border-radius: 12px; color: #991b1b; display: inline-block; padding: 3px 8px; }
        .audit-box { background: #fffbeb; border-left: 4px solid #f59e0b; margin-bottom: 10px; padding: 10px 12px; }
        ul { margin: 6px 0 0 18px; padding: 0; }
        li { margin-bottom: 4px; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 9px; margin-top: 24px; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Semanal de Fuga Comercial</h1>
        <p>OdonCRM | {{ $report['period_start']->format('d/m/Y') }} - {{ $report['period_end']->format('d/m/Y') }}</p>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>
                    <div>Leads perdidos de alto valor</div>
                    <div class="metric">{{ $report['total_leads'] }}</div>
                </td>
                <td>
                    <div>Fuga estimada</div>
                    <div class="metric">${{ number_format($report['total_value'], 2) }}</div>
                </td>
                <td>
                    <div>Criterio</div>
                    <div class="metric">+${{ number_format($report['minimum_value'], 0) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <h2>Leads En Etapa Perdido</h2>
    <table>
        <thead>
            <tr>
                <th>Lead</th>
                <th>Procedimiento</th>
                <th>Valor</th>
                <th>Motivo</th>
                <th>Ultima actividad</th>
                <th>Recomendacion</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['leads'] as $lead)
                <tr>
                    <td>{{ $lead['lead_name'] }}</td>
                    <td>{{ $lead['procedure'] }}</td>
                    <td class="money">${{ number_format($lead['estimated_value'], 2) }}</td>
                    <td><span class="pill">{{ $lead['lost_reason'] }}</span></td>
                    <td>{{ $lead['last_activity_at'] }}</td>
                    <td>{{ $lead['recovery_recommendation'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No hay leads perdidos de alto valor en este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Auditoria IA / Comercial</h2>
    <div class="audit-box">
        <strong>Fuente:</strong> {{ $report['audit']['source'] ?? 'local' }}
        @if (! empty($report['audit']['top_motivos']))
            <ul>
                @foreach ($report['audit']['top_motivos'] as $motivo)
                    <li>{{ $motivo['motivo'] ?? 'Motivo' }}: {{ $motivo['cantidad'] ?? 0 }} leads, {{ $motivo['porcentaje'] ?? 0 }}%, ${{ number_format((float) ($motivo['valor_estimado'] ?? 0), 2) }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <h2>Recomendaciones</h2>
    <ul>
        @foreach (($report['audit']['recomendaciones'] ?? []) as $recommendation)
            <li>{{ is_array($recommendation) ? json_encode($recommendation, JSON_UNESCAPED_UNICODE) : $recommendation }}</li>
        @endforeach
    </ul>

    @if (! empty($report['audit']['alertas']))
        <h2>Alertas</h2>
        <ul>
            @foreach ($report['audit']['alertas'] as $alert)
                <li>{{ is_array($alert) ? json_encode($alert, JSON_UNESCAPED_UNICODE) : $alert }}</li>
            @endforeach
        </ul>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} | Valores en USD | OdonCRM
    </div>
</body>
</html>
