<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-notifications', fn ($user): bool => filled($user));
Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id): bool => $user->id === $id);
