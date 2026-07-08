<x-filament-widgets::widget>
    <div x-data="statsOverview()" x-init="init()">
        <div class="mc-section-header">
            @if ($heading = $this->getHeading())
                <h3 class="mc-section-title">{{ $heading }}</h3>
            @endif
        </div>

        @if ($description = $this->getDescription())
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif

        <div
            wire:key="roi-stats"
            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:0.75rem;"
        >
            @foreach ($this->getStats() as $stat)
                <div class="fi-wi-stats-overview-stat" style="min-height:6rem;">
                    <div class="fi-wi-stats-overview-stat-content">
                        @if ($stat->getIcon())
                            <div class="fi-wi-stats-overview-stat-icon-ctn">
                                <x-filament::icon
                                    :icon="$stat->getIcon()"
                                    class="fi-wi-stats-overview-stat-icon"
                                />
                            </div>
                        @endif
                        @if ($label = $stat->getLabel())
                            <dd class="fi-wi-stats-overview-stat-label">
                                {{ $label }}
                            </dd>
                        @endif
                        @if ($value = $stat->getValue())
                            <dt class="fi-wi-stats-overview-stat-value">
                                {{ $value }}
                            </dt>
                        @endif
                        @if ($description = $stat->getDescription())
                            <dd class="fi-wi-stats-overview-stat-description"
                                @if ($descriptionColor = $stat->getDescriptionColor())
                                    style="--c-400:var(--{{ $descriptionColor }}-400);--c-600:var(--{{ $descriptionColor }}-600);"
                                @endif
                            >
                                {{ $description }}
                            </dd>
                        @endif
                    </div>
                    @if ($color = $stat->getColor())
                        <div
                            class="fi-wi-stats-overview-stat-bg-ctn"
                            style="--c-50:var(--{{ $color }}-50);--c-100:var(--{{ $color }}-100);--c-500:var(--{{ $color }}-500);"
                        ></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
