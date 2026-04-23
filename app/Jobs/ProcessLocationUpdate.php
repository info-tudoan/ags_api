<?php

namespace App\Jobs;

use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLocationUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected LocationTracking $location
    ) {}

    public function handle(): void
    {
        $user = $this->location->user;

        if (!$user || $user->status !== 'active') {
            return;
        }

        event(new \App\Events\LocationUpdated($user, $this->location));
    }
}
