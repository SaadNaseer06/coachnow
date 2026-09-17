<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('coach.session-requests.{coachId}', function (User $user, int $coachId) {
    if ($user->isAdmin()) {
        return true;
    }

    return $user->isCoach() && (int) $user->coach?->id === $coachId;
});

Broadcast::channel('coach.status.{coachId}', function (User $user, int $coachId) {
    if ($user->isAdmin()) {
        return true;
    }

    return $user->isCoach() && (int) $user->coach?->id === $coachId;
});
