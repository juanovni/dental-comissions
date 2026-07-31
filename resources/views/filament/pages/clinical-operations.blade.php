<x-filament-panels::page>
    @php
        $metrics = $this->metrics();
        $distribution = $this->statusDistribution();
        $alerts = $this->alerts();
        $doctorLoad = $this->doctorLoad();
        $maxDistribution = max(1, collect($distribution)->max('count'));
    @endphp

    <style>
        .co-page { color: #0f172a; display: grid; gap: 1rem; }
        .co-kpis { display: grid; gap: .75rem; grid-template-columns: repeat(8, minmax(0, 1fr)); }
        .co-card, .co-kpi, .co-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; }
        .co-kpi { display: grid; gap: .75rem; min-height: 6.25rem; padding: .85rem; }
        .co-kpi-label { color: #64748b; font-size: .72rem; font-weight: 600; text-transform: uppercase; }
        .co-kpi-value { color: #0f172a; font-size: 1.35rem; font-weight: 700; }
        .co-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 2fr) minmax(20rem, 1fr); }
        .co-panel { display: grid; gap: .85rem; padding: .95rem; }
        .co-title { color: #334155; font-size: .82rem; font-weight: 650; text-transform: uppercase; }
        .co-state-row { align-items: center; display: grid; gap: .7rem; grid-template-columns: 10rem 1fr 2rem; }
        .co-bar-bg { background: #f1f5f9; border-radius: 999px; height: 1.45rem; overflow: hidden; }
        .co-bar { height: 100%; min-width: .25rem; }
        .co-alerts { background: #fff7f7; border-color: #fecaca; }
        .co-alert { background: #fff; border: 1px solid #fed7aa; border-radius: .55rem; color: #c2410c; font-size: .82rem; padding: .65rem .75rem; }
        .co-doctors { display: grid; }
        .co-doctor { align-items: center; border-top: 1px solid #e5e7eb; display: grid; gap: .75rem; grid-template-columns: 2fr repeat(3, 1fr) 1.6fr; padding: .8rem 0; }
        .co-doctor:first-child { border-top: 0; }
        .co-muted { color: #64748b; font-size: .78rem; }
        .co-progress { background: #f1f5f9; border-radius: 999px; height: .45rem; overflow: hidden; }
        .co-progress-fill { background: #d97706; height: 100%; }
        @media (max-width: 1100px) { .co-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .co-grid { grid-template-columns: 1fr; } .co-doctor { grid-template-columns: 1fr; } }
    </style>

    <div class="co-page">
        <div class="co-kpis">
            <div class="co-kpi"><div class="co-kpi-label">Agendadas</div><div class="co-kpi-value">{{ $metrics['scheduled'] }}</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Confirmadas</div><div class="co-kpi-value">{{ $metrics['confirmed'] }}</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Atendidas</div><div class="co-kpi-value">{{ $metrics['attended'] }}</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Canceladas</div><div class="co-kpi-value">{{ $metrics['cancelled'] }}</div></div>
            <div class="co-kpi"><div class="co-kpi-label">No Show</div><div class="co-kpi-value">{{ $metrics['no_show'] }}</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Espera prom</div><div class="co-kpi-value">{{ $metrics['avg_wait'] }}m</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Consulta prom</div><div class="co-kpi-value">{{ $metrics['avg_consultation'] }}m</div></div>
            <div class="co-kpi"><div class="co-kpi-label">Puntualidad</div><div class="co-kpi-value">{{ $metrics['punctuality'] }}%</div></div>
        </div>

        <div class="co-grid">
            <section class="co-panel">
                <div class="co-title">Estado actual de la clinica</div>
                @foreach ($distribution as $item)
                    <div class="co-state-row">
                        <span>{{ $item['label'] }}</span>
                        <span class="co-bar-bg"><span class="co-bar" style="display:block; width: {{ ($item['count'] / $maxDistribution) * 100 }}%; background: {{ $item['color'] }};"></span></span>
                        <strong>{{ $item['count'] }}</strong>
                    </div>
                @endforeach
            </section>

            <section class="co-panel co-alerts">
                <div class="co-title" style="color:#dc2626;">Alertas operativas</div>
                @forelse ($alerts as $alert)
                    <div class="co-alert">{{ $alert }}</div>
                @empty
                    <div class="co-muted">Sin alertas operativas.</div>
                @endforelse
            </section>
        </div>

        <section class="co-panel">
            <div class="co-title">Productividad y saturacion por doctor</div>
            <div class="co-doctors">
                @forelse ($doctorLoad as $doctor)
                    <div class="co-doctor">
                        <strong>{{ $doctor['name'] }}</strong>
                        <span><span class="co-muted">Citas</span><br>{{ $doctor['total'] }}</span>
                        <span><span class="co-muted">Atendidas</span><br>{{ $doctor['attended'] }}</span>
                        <span><span class="co-muted">En espera</span><br>{{ $doctor['waiting'] }}</span>
                        <span><span class="co-muted">Saturacion {{ $doctor['saturation'] }}%</span><span class="co-progress"><span class="co-progress-fill" style="display:block; width: {{ $doctor['saturation'] }}%;"></span></span></span>
                    </div>
                @empty
                    <div class="co-muted">No hay doctores activos.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
