<x-filament-widgets::widget>
    <section class="{{ $this->getCardClass() }}">
        <div class="mc-section-header">
            @if ($heading = $this->getHeading())
                <h3 class="mc-section-title">{{ $heading }}</h3>
            @endif
        </div>

        @if ($description = $this->getDescription())
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif

        <div
            wire:key="apex-chart-{{ $this->getChartRenderKey() }}"
            x-data="{
                chart: null,
                failedAttempts: 0,
                options: @js($this->getChartOptions()),
                init() {
                    this.renderChart()
                },
                renderChart() {
                    if (this.chart) {
                        return
                    }

                    if (! window.ApexCharts) {
                        this.failedAttempts++
                        window.setTimeout(() => this.renderChart(), 50)

                        return
                    }

                    this.$nextTick(() => {
                        if (this.chart) {
                            return
                        }

                        this.chart = new window.ApexCharts(this.$refs.chart, this.options)
                        this.chart.render()
                    })
                },
                destroy() {
                    if (this.chart) {
                        this.chart.destroy()
                        this.chart = null
                    }
                },
            }"
            x-init="init()"
        >
            <div
                x-ref="chart"
                style="min-height: {{ $this->getMaxHeight() }};"
            ></div>

            <template x-if="failedAttempts > 40 && ! chart">
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    El grafico no pudo cargar. Recarga la pagina o revisa los assets del panel.
                </p>
            </template>
        </div>
    </section>
</x-filament-widgets::widget>
