<x-filament-widgets::widget>
    <div x-data="statsOverview()" x-init="init()">
        @php
            $periodBadgeLabel = $this->getPeriodBadgeLabel();
            $currentPeriodLabel = $this->getCurrentPeriodLabel();
            $previousPeriodLabel = $this->getPreviousPeriodLabel();
        @endphp

        <div
            wire:key="roi-stats"
            class="social-roi-stats-grid"
        >
            @foreach ($this->getStats() as $stat)
                <div class="social-roi-stat-card">
                    <div class="social-roi-stat-card-header">
                        @if ($stat->getIcon())
                            <span class="social-roi-stat-icon-ctn">
                                <x-filament::icon
                                    :icon="$stat->getIcon()"
                                    class="social-roi-stat-icon"
                                />
                            </span>
                        @endif

                        <span class="social-roi-period-chip social-roi-period-chip-card" tabindex="0">
                            {{ $periodBadgeLabel }}
                            <span class="social-roi-period-info">i</span>
                            <span class="social-roi-period-tooltip">
                                <strong>Periodo actual</strong>
                                <span>{{ $currentPeriodLabel }}</span>
                                <strong>Compara con</strong>
                                <span>{{ $previousPeriodLabel }}</span>
                            </span>
                        </span>
                    </div>

                    <div class="social-roi-stat-card-body">
                        @if ($label = $stat->getLabel())
                            <div class="social-roi-stat-label">
                                {{ $label }}
                            </div>
                        @endif
                        @if ($value = $stat->getValue())
                            <div class="social-roi-stat-main-value">
                                {!! $value !!}
                            </div>
                        @endif
                    </div>

                    <div class="social-roi-stat-card-divider"></div>

                    <div class="social-roi-stat-card-footer">
                        @if ($description = $stat->getDescription())
                            {!! $description !!}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
