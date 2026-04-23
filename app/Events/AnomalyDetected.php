<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public User $user,
        public array $violations
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin'),
            new Channel('alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'anomaly.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'violations' => $this->violations,
            'timestamp' => now(),
        ];
    }
}
