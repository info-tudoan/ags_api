<?php

namespace App\Services;

use App\Models\LocationTracking;
use App\Models\User;
use App\Models\WorkZone;

class LocationService
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    /**
     * Record location update for user
     */
    public function trackLocation(
        User $user,
        float $latitude,
        float $longitude,
        int $accuracy,
        string $gpsSource = 'gps',
        ?array $deviceInfo = null
    ): LocationTracking {
        $inZone = null;
        $isInZone = false;

        $zone = $this->findZoneForLocation($latitude, $longitude);
        if ($zone) {
            $inZone = $zone->id;
            $isInZone = true;
        }

        return LocationTracking::create([
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'in_zone_id' => $inZone,
            'is_in_zone' => $isInZone,
            'gps_source' => $gpsSource,
            'device_info' => $deviceInfo,
            'timestamp' => now(),
        ]);
    }

    /**
     * Find zone containing the given coordinates
     */
    protected function findZoneForLocation(float $latitude, float $longitude): ?WorkZone
    {
        $zones = WorkZone::all();

        foreach ($zones as $zone) {
            $coordinates = $zone->coordinates;

            if ($zone->type === 'primary' || $zone->type === 'secondary') {
                if (is_array($coordinates) && isset($coordinates['polygon'])) {
                    if ($this->geofenceService->isPointInPolygon($latitude, $longitude, $coordinates['polygon'])) {
                        return $zone;
                    }
                }
            } elseif ($zone->type === 'break') {
                if (isset($coordinates['center'])) {
                    $center = $coordinates['center'];
                    if ($this->geofenceService->isPointInCircle(
                        $latitude,
                        $longitude,
                        $center['latitude'],
                        $center['longitude'],
                        $zone->radius ?? 100
                    )) {
                        return $zone;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get recent locations for user
     */
    public function getRecentLocations(User $user, int $limit = 50): mixed
    {
        return LocationTracking::where('user_id', $user->id)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get location history for date range
     */
    public function getLocationHistory(User $user, string $startDate, string $endDate): mixed
    {
        return LocationTracking::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    /**
     * Check if user is currently in any zone
     */
    public function isUserInZone(User $user): bool
    {
        $lastLocation = LocationTracking::where('user_id', $user->id)
            ->latest('timestamp')
            ->first();

        return $lastLocation?->is_in_zone ?? false;
    }
}
