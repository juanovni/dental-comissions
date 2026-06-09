<?php

use App\Http\Controllers\TestWhatsappController;
use App\Http\Controllers\MetaSocialWebhookController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TestMetaSocialController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'receive']);
Route::get('/webhook/meta/social', [MetaSocialWebhookController::class, 'verify']);
Route::post('/webhook/meta/social', [MetaSocialWebhookController::class, 'receive']);

if (app()->environment('local', 'testing')) {
    Route::post('/test/whatsapp', [TestWhatsappController::class, 'test']);
    Route::post('/test/meta/comment', [TestMetaSocialController::class, 'comment']);
}
