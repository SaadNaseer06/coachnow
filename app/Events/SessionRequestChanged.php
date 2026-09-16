<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionRequestChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $request
     */
    public function __construct(
        public array $request,
        public string $action = 'updated'
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $requested = ! empty($this->request['requested_coach_id'])
            ? (int) $this->request['requested_coach_id']
            : 0;
        $host = ! empty($this->request['host_coach_id'])
            ? (int) $this->request['host_coach_id']
            : 0;

        $ids = array_values(array_unique(array_filter([$requested, $host])));

        if ($ids !== []) {
            return array_map(
                fn (int $id) => new PrivateChannel('coach.session-requests.'.$id),
                $ids
            );
        }

        return [
            new Channel('coaches.session-requests'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session-request.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'request' => $this->request,
        ];
    }
}
