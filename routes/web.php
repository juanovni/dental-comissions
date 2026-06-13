<?php

use App\Http\Controllers\MetaAuthController;
use App\Http\Controllers\MetaSocialWebhookController;
use App\Http\Controllers\SocialSmartLinkController;
use App\Http\Controllers\TestMetaSocialController;
use App\Http\Controllers\TestWhatsappController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'receive']);
Route::get('/webhook/meta/social', [MetaSocialWebhookController::class, 'verify']);
Route::post('/webhook/meta/social', [MetaSocialWebhookController::class, 'receive']);
Route::get('/auth/meta/redirect', [MetaAuthController::class, 'redirect'])->name('meta.auth.redirect');
Route::get('/auth/meta/callback', [MetaAuthController::class, 'callback'])->name('meta.auth.callback');
Route::get('/v/{trackingToken}', [SocialSmartLinkController::class, 'show'])->name('social-smart-link.show');
Route::post('/v/{trackingToken}/event', [SocialSmartLinkController::class, 'track'])->name('social-smart-link.track');

if (app()->environment('local', 'testing')) {
    Route::post('/test/whatsapp', [TestWhatsappController::class, 'test']);
    Route::post('/test/meta/comment', [TestMetaSocialController::class, 'comment']);
}
