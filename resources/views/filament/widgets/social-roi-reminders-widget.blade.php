<x-filament-widgets::widget>
    <section class="social-roi-panel social-roi-reminders-panel">
        <div class="social-roi-panel-header">
            <h3 class="social-roi-panel-title">{{ $this->getHeading() }}</h3>
            <p class="social-roi-panel-description">{{ $this->getDescription() }}</p>
        </div>

        <div class="social-roi-reminders-list">
            @foreach ($this->getRemindersData() as $reminder)
                <div class="social-roi-reminder-row">
                    <span class="social-roi-reminder-icon">
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="social-roi-reminder-icon-svg"
                        />
                    </span>

                    <div class="social-roi-reminder-copy">
                        <span class="social-roi-reminder-label">{{ $reminder['label'] }}</span>
                        <span class="social-roi-reminder-description">{{ $reminder['description'] }}</span>
                    </div>

                    <span class="social-roi-reminder-badge">{{ $reminder['value'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
