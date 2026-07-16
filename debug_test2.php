<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(\Illuminate\Contracts\Http\Kernel::class);

\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-07-13 09:00:00'));

try {
    $controller = app(\App\Http\Controllers\SocialAppointmentLinkController::class);
    echo "Controller instantiated OK\n";
} catch (\Throwable $e) {
    echo "Controller ERROR: " . $e->getMessage() . "\n";
}
