<?php

use App\Http\Controllers\TestWhatsappController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'receive']);

if (app()->environment('local', 'testing')) {
    Route::post('/test/whatsapp', [TestWhatsappController::class, 'test']);
}
