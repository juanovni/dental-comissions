<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal - {{ $report->professional->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0d9488; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 22px; color: #0d9488; }
        .header p { margin: 5px 0 0; color: #666; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 30%; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .info-box .label { font-weight: bold; color: #666; font-size: 10px; text-transform: uppercase; }
        .info-box .value { font-size: 14px; margin-top: 3px; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 20px; background: #f0fdfa; padding: 15px; border-radius: 4px; }
        .summary-item { text-align: center; }
        .summary-item .number { font-size: 20px; font-weight: bold; color: #0d9488; }
        .summary-item .label { font-size: 10px; color: #666; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #0d9488; color: white; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #eee; font-size: 11px; }
        tr:nth-child(even) { background: #f9fafb; }
        .text-right { text-align: right; }
        .totals { margin-top: 10px; }
        .totals table { width: 50%; margin-left: auto; }
        .totals td { padding: 4px 6px; font-size: 11px; }
        .totals .total-row td { font-weight: bold; border-top: 2px solid #0d9488; font-size: 13px; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Comisiones Semanal</h1>
        <p>Dental Commissions MVP</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Doctor</div>
            <div class="value">{{ $report->professional->name }}</div>
        </div>
        <div class="info-box">
            <div class="label">Periodo</div>
            <div class="value">{{ $report->week_start->format('d/m/Y') }} - {{ $report->week_end->format('d/m/Y') }}</div>
        </div>
        <div class="info-box">
            <div class="label">Estado</div>
            <div class="value">{{ $report->status->label() }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="number">{{ $report->total_activities }}</div>
            <div class="label">Actividades</div>
        </div>
        <div class="summary-item">
            <div class="number">{{ $report->total_patients }}</div>
            <div class="label">Pacientes</div>
        </div>
        <div class="summary-item">
            <div class="number">${{ number_format($report->total_doctor_commission, 2) }}</div>
            <div class="label">Com. Doctor</div>
        </div>
        <div class="summary-item">
            <div class="number">${{ number_format($report->total_assistant_commission, 2) }}</div>
            <div class="label">Com. Auxiliares</div>
        </div>
        <div class="summary-item">
            <div class="number">${{ number_format($report->total_commission, 2) }}</div>
            <div class="label">Total</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Procedimiento</th>
                <th>Com. Doctor</th>
                <th>Com. Auxiliares</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->activities as $activity)
                <tr>
                    <td>{{ $activity->activity_date->format('d/m/Y') }}</td>
                    <td>{{ $activity->patient->full_name }}</td>
                    <td>{{ $activity->procedure->name }}</td>
                    <td class="text-right">${{ number_format($activity->doctor_commission_amount, 2) }}</td>
                    <td class="text-right">${{ number_format($activity->assistant_commission_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No hay actividades registradas</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Total comision doctor:</td>
                <td class="text-right">${{ number_format($report->total_doctor_commission, 2) }}</td>
            </tr>
            <tr>
                <td>Total comision auxiliares:</td>
                <td class="text-right">${{ number_format($report->total_assistant_commission, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">${{ number_format($report->total_commission, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} | Dental Commissions MVP
    </div>
</body>
</html>
