<?php

namespace App\Events;

use App\Models\User;
use App\Models\LocationTracking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public User $user,
        public LocationTracking $location
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("location.{$this->user->id}"),
            new Channel('location.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'latitude' => $this->location->latitude,
            'longitude' => $this->location->longitude,
            'accuracy' => $this->location->accuracy_meters,
            'in_zone' => $this->location->is_in_zone,
            'zone_id' => $this->location->in_zone_id,
            'timestamp' => $this->location->timestamp,
        ];
    }
}
