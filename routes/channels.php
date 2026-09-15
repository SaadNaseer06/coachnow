<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('coaches.session-requests', function (User $user) {
    return $user->isCoach() || $user->isAdmin();
});
