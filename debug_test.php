<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-07-13 09:00:00'));

app(\App\Services\SocialCrmSettingsService::class);

try {
    $result = app(\App\Services\AppointmentAvailabilityService::class)->availabilityWindow(null, 5, 21);
    echo "availabilityWindow OK: " . count($result['days']) . " days\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
