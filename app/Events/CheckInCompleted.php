<?php

namespace App\Events;

use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckInCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public User $user,
        public AttendanceRecord $record
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('attendance'),
            new Channel("team.{$this->user->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.check_in';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'check_in_time' => $this->record->check_in_time,
            'status' => $this->record->status,
            'timestamp' => now(),
        ];
    }
}
