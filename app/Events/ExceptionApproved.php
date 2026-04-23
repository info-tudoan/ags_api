<?php

namespace App\Events;

use App\Models\User;
use App\Models\AttendanceException;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExceptionApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public AttendanceException $exception,
        public User $approver
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("user.{$this->exception->user_id}"),
            new Channel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'exception.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'exception_id' => $this->exception->id,
            'user_id' => $this->exception->user_id,
            'type' => $this->exception->type,
            'approved_by' => $this->approver->name,
            'approval_reason' => $this->exception->approval_reason,
            'timestamp' => now(),
        ];
    }
}
