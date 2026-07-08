<x-filament-widgets::widget>
    <section class="mc-card">
        <h3 class="mc-section-title">{{ $this->getHeading() }}</h3>
        <div class="mc-facts">
            @foreach ($this->getReminders() as $reminder)
                @php
                    $badgeClass = match ($reminder['priority']) {
                        'danger' => 'mc-badge-danger',
                        'warning' => 'mc-badge-warning',
                        default => 'mc-badge-success',
                    };
                @endphp
                <div class="mc-fact">
                    <span class="mc-fact-label">
                        {{ $reminder['label'] }}
                    </span>
                    <div>
                        <p class="mc-fact-value">
                            <span class="mc-badge {{ $badgeClass }}" style="font-size:0.85rem;padding:0.2rem 0.6rem;">
                                {{ $reminder['value'] }}
                            </span>
                            {{ $reminder['description'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
