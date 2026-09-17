<?php

namespace App\Events;

use App\Models\Coach;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoachStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Coach $coach,
        public string $previousStatus,
        public string $status,
    ) {
        $this->coach->loadMissing('user');
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('coach.status.'.$this->coach->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'coach.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'coach_id' => $this->coach->id,
            'previous_status' => $this->previousStatus,
            'status' => $this->status,
            'label' => ucfirst($this->status),
            'title' => match ($this->status) {
                'active' => 'You’re live on Find a Coach',
                'paused' => 'Your listing was paused',
                'pending' => 'Your listing is pending review',
                default => 'Listing status updated',
            },
            'message' => match ($this->status) {
                'active' => 'An admin approved your profile. Athletes can now discover and book you.',
                'paused' => 'An admin paused your profile. You’re hidden from Find a Coach until reactivated.',
                'pending' => 'Your profile was set back to pending. Finish any missing details and wait for approval.',
                default => 'Your CoachNow listing status is now '.$this->status.'.',
            },
            'profile_url' => route('coach.profile'),
            'dashboard_url' => route('coach.dashboard'),
        ];
    }
}
