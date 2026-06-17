<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-notifications', fn ($user): bool => filled($user));
