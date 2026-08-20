<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('clinic.{clinicId}.notifications', function (User $user, int $clinicId): bool {
    return $user->clinics()->where('clinic_id', $clinicId)->exists();
});

Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id): bool => $user->id === $id);
