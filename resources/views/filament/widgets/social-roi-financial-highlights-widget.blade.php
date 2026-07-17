<x-filament-widgets::widget>
    @php
        $periodBadge = $this->getPeriodBadgeLabel();
        $currentLabel = $this->getCurrentPeriodLabel();
        $previousLabel = $this->getPreviousPeriodLabel();
        $data = $this->getHighlightsData();
        $attributedPct = $data['attribution_rate'];
        $nonAttributedPct = $data['total_revenue'] > 0
            ? round(($data['non_attributed_revenue'] / $data['total_revenue']) * 100, 1)
            : 0;
    @endphp

    <section class="social-roi-panel social-roi-financial-panel">
        <div class="social-roi-panel-header">
            <div>
                <h3 class="social-roi-panel-title">{{ $this->getHeading() }}</h3>
                <p class="social-roi-panel-description">Ingreso real vs atribucion social</p>
            </div>
            <span class="social-roi-period-chip social-roi-period-chip-card" tabindex="0">
                {{ $periodBadge }}
                <span class="social-roi-period-info">i</span>
                <span class="social-roi-period-tooltip">
                    <strong>Periodo actual</strong>
                    <span>{{ $currentLabel }}</span>
                    <strong>Compara con</strong>
                    <span>{{ $previousLabel }}</span>
                </span>
            </span>
        </div>

        <div class="social-roi-financial-body">
            <div class="social-roi-financial-total-section">
                <span class="social-roi-financial-total-label">Total</span>
                <div class="social-roi-financial-total-row">
                    <span class="social-roi-financial-total-value">
                        ${{ number_format($data['total_revenue'], 2) }}
                    </span>
                    @php $trend = $data['total_revenue_trend']; @endphp
                    <span class="social-roi-trend-badge social-roi-trend-badge-{{ $trend['status'] }}">
                        @if ($trend['change'] > 0)
                            &uarr;
                        @elseif ($trend['change'] < 0)
                            &darr;
                        @else
                            &rarr;
                        @endif
                        {{ $trend['label'] }}
                    </span>
                </div>
            </div>

            <div class="social-roi-financial-bar-section">
                <svg class="social-roi-financial-bar" viewBox="0 0 100 8" role="img" aria-label="Distribucion de ingresos">
                    <rect class="social-roi-financial-bar-bg" x="0" y="0" width="100" height="8" rx="4"></rect>
                    <rect class="social-roi-financial-bar-attributed" x="0" y="0" width="{{ min(100, max(0, $attributedPct)) }}" height="8" rx="4"></rect>
                    <rect class="social-roi-financial-bar-non-attributed" x="{{ min(100, max(0, $attributedPct)) }}" y="0" width="{{ min(100, max(0, $nonAttributedPct)) }}" height="8" rx="4"></rect>
                </svg>
                <div class="social-roi-financial-bar-labels">
                    <span class="social-roi-financial-bar-label">
                        <span class="social-roi-financial-dot social-roi-financial-dot-attributed"></span>
                        Atribuido social
                    </span>
                    <span class="social-roi-financial-bar-label">
                        <span class="social-roi-financial-dot social-roi-financial-dot-non-attributed"></span>
                        No atribuido
                    </span>
                </div>
            </div>

            <div class="social-roi-financial-facts">
                <div class="social-roi-financial-fact">
                    <span class="social-roi-financial-fact-icon">
                        <x-filament::icon
                            icon="heroicon-o-share"
                            class="social-roi-financial-fact-icon-svg"
                        />
                    </span>
                    <span class="social-roi-financial-fact-label">Ingresos atribuidos</span>
                    <span class="social-roi-financial-fact-value" title="{{ $data['attributed_revenue'] > 0 ? number_format($data['attributed_revenue'], 2) : '0' }}">
                        ${{ number_format($data['attributed_revenue'], 2) }}
                        <span class="social-roi-financial-fact-pct">{{ $attributedPct }}%</span>
                        @php $rowTrend = $data['attributed_trend']; @endphp
                        <span class="social-roi-financial-mini-trend social-roi-financial-mini-trend-{{ $rowTrend['status'] }}">
                            {{ $rowTrend['label'] }}
                        </span>
                    </span>
                </div>
                <div class="social-roi-financial-fact">
                    <span class="social-roi-financial-fact-icon">
                        <x-filament::icon
                            icon="heroicon-o-building-storefront"
                            class="social-roi-financial-fact-icon-svg"
                        />
                    </span>
                    <span class="social-roi-financial-fact-label">Otras fuentes</span>
                    <span class="social-roi-financial-fact-value">
                        ${{ number_format($data['non_attributed_revenue'], 2) }}
                        <span class="social-roi-financial-fact-pct">{{ $nonAttributedPct }}%</span>
                        @php $rowTrend = $data['non_attributed_trend']; @endphp
                        <span class="social-roi-financial-mini-trend social-roi-financial-mini-trend-{{ $rowTrend['status'] }}">
                            {{ $rowTrend['label'] }}
                        </span>
                    </span>
                </div>
                <div class="social-roi-financial-fact">
                    <span class="social-roi-financial-fact-icon">
                        <x-filament::icon
                            icon="heroicon-o-trophy"
                            class="social-roi-financial-fact-icon-svg"
                        />
                    </span>
                    <span class="social-roi-financial-fact-label">Ganado Pipeline</span>
                    <span class="social-roi-financial-fact-value">
                        ${{ number_format($data['won_pipeline_value'], 2) }}
                        <span class="social-roi-financial-fact-tag" tabindex="0">
                            estimado
                            <span class="social-roi-financial-tag-tooltip">
                                Valor estimado de leads movidos a Won. No representa ingreso clinico real hasta que exista actividad registrada.
                            </span>
                        </span>
                        @php $rowTrend = $data['won_pipeline_trend']; @endphp
                        <span class="social-roi-financial-mini-trend social-roi-financial-mini-trend-{{ $rowTrend['status'] }}">
                            {{ $rowTrend['label'] }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
