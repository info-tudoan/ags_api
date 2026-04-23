<?php

namespace App\Services;

use App\Models\LocationTracking;
use App\Models\User;
use App\Models\WorkZone;
use Carbon\Carbon;

class AntiSpoofService
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    /**
     * Validate GPS accuracy
     */
    public function validateGpsAccuracy(int $accuracy, int $minAccuracy = 20): bool
    {
        return $accuracy <= $minAccuracy;
    }

    /**
     * Validate WiFi BSSID against zone's registered networks
     */
    public function validateWifiBssid(?WorkZone $zone, ?string $bssid): bool
    {
        if (!$zone || !$bssid) {
            return true;
        }

        $registeredNetworks = $zone->wifiNetworks()
            ->where('is_active', true)
            ->pluck('bssid')
            ->toArray();

        if (empty($registeredNetworks)) {
            return true;
        }

        return in_array($bssid, $registeredNetworks);
    }

    /**
     * Detect impossible travel (movement > 100 km/h)
     */
    public function detectImpossibleTravel(User $user, float $latitude, float $longitude): bool
    {
        $lastLocation = LocationTracking::where('user_id', $user->id)
            ->latest('timestamp')
            ->first();

        if (!$lastLocation) {
            return false;
        }

        $speed = $this->geofenceService->calculateSpeed(
            $lastLocation->latitude,
            $lastLocation->longitude,
            $latitude,
            $longitude,
            now()->diffInSeconds($lastLocation->timestamp)
        );

        return $speed > 100;
    }

    /**
     * Detect repeated out-of-zone events (suspicious activity)
     */
    public function detectRepeatedOutOfZone(User $user, int $thresholdCount = 3, int $minuteWindow = 60): bool
    {
        $startTime = now()->subMinutes($minuteWindow);

        $outOfZoneCount = LocationTracking::where('user_id', $user->id)
            ->where('is_in_zone', false)
            ->where('timestamp', '>=', $startTime)
            ->count();

        return $outOfZoneCount >= $thresholdCount;
    }

    /**
     * Validate device fingerprint consistency
     */
    public function validateDeviceFingerprint(User $user, array $deviceInfo): bool
    {
        $lastLocation = LocationTracking::where('user_id', $user->id)
            ->latest('timestamp')
            ->first();

        if (!$lastLocation || !$lastLocation->device_info) {
            return true;
        }

        $lastDeviceId = $lastLocation->device_info['device_id'] ?? null;
        $currentDeviceId = $deviceInfo['device_id'] ?? null;

        if (!$lastDeviceId || !$currentDeviceId) {
            return true;
        }

        return $lastDeviceId === $currentDeviceId;
    }

    /**
     * Run full anti-spoofing validation
     */
    public function runFullValidation(
        User $user,
        ?WorkZone $zone,
        float $latitude,
        float $longitude,
        int $accuracy,
        ?string $wifiBssid = null,
        array $deviceInfo = []
    ): array {
        $violations = [];

        if (!$this->validateGpsAccuracy($accuracy)) {
            $violations[] = 'Poor GPS accuracy';
        }

        if (!$this->validateWifiBssid($zone, $wifiBssid)) {
            $violations[] = 'Invalid WiFi network';
        }

        if ($this->detectImpossibleTravel($user, $latitude, $longitude)) {
            $violations[] = 'Impossible travel detected';
        }

        if ($this->detectRepeatedOutOfZone($user)) {
            $violations[] = 'Suspicious repeated out-of-zone events';
        }

        if (!$this->validateDeviceFingerprint($user, $deviceInfo)) {
            $violations[] = 'Device mismatch detected';
        }

        return $violations;
    }
}
