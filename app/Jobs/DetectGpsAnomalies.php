<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\LocationTracking;
use App\Services\AntiSpoofService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectGpsAnomalies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AntiSpoofService $antiSpoofService): void
    {
        $users = User::where('status', 'active')->get();

        foreach ($users as $user) {
            $recentLocation = LocationTracking::where('user_id', $user->id)
                ->latest('timestamp')
                ->first();

            if (!$recentLocation) {
                continue;
            }

            $violations = [];

            if ($antiSpoofService->detectImpossibleTravel(
                $user,
                $recentLocation->latitude,
                $recentLocation->longitude
            )) {
                $violations[] = 'Impossible travel detected';
            }

            if ($antiSpoofService->detectRepeatedOutOfZone($user)) {
                $violations[] = 'Repeated out-of-zone activity';
            }

            if (!empty($violations)) {
                event(new \App\Events\AnomalyDetected($user, $violations));
            }
        }
    }
}
