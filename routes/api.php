<?php

use App\Http\Controllers\VoiceToolController;
use Illuminate\Support\Facades\Route;

Route::middleware(['voice.tool'])->prefix('voice/tools')->group(function (): void {
    Route::post('identify-patient', [VoiceToolController::class, 'identifyPatient']);
    Route::post('get-available-slots', [VoiceToolController::class, 'getAvailableSlots']);
    Route::post('hold-slot', [VoiceToolController::class, 'holdSlot']);
    Route::post('create-appointment', [VoiceToolController::class, 'createAppointment']);
    Route::post('request-handoff', [VoiceToolController::class, 'requestHandoff']);
});
