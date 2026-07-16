<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-07-13 09:00:00'));

try {
    $offer = \App\Models\AppointmentSlotOffer::first();
    if (!$offer) {
        die("No offer found - run test first\n");
    }
    echo "Offer found: {$offer->token}\n";
    echo "Offer status: {$offer->status}\n";
    
    $controller = app(\App\Http\Controllers\SocialAppointmentLinkController::class);
    $view = $controller->show($offer->token);
    echo "View rendered: " . get_class($view) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
