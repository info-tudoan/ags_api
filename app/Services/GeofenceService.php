<?php

namespace App\Services;

class GeofenceService
{
    /**
     * Check if point is within polygon using ray casting algorithm
     */
    public function isPointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $x = $latitude;
        $y = $longitude;
        $n = count($polygon);
        $inside = false;

        for ($p = $n - 1, $q = 0; $q < $n; $p = $q++) {
            $x1 = $polygon[$p][0];
            $y1 = $polygon[$p][1];
            $x2 = $polygon[$q][0];
            $y2 = $polygon[$q][1];

            if (($y1 <= $y && $y < $y2) || ($y2 <= $y && $y < $y1)) {
                if ($x < ($x2 - $x1) * ($y - $y1) / ($y2 - $y1) + $x1) {
                    $inside = !$inside;
                }
            }
        }

        return $inside;
    }

    /**
     * Check if point is within circle using distance formula
     */
    public function isPointInCircle(float $latitude, float $longitude, float $centerLat, float $centerLng, float $radiusMeters): bool
    {
        $distance = $this->haversineDistance($latitude, $longitude, $centerLat, $centerLng);
        return $distance <= $radiusMeters;
    }

    /**
     * Calculate distance between two points using Haversine formula (in meters)
     */
    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }

    /**
     * Get speed between two locations (km/h)
     */
    public function calculateSpeed(float $lat1, float $lng1, float $lat2, float $lng2, int $secondsElapsed): float
    {
        if ($secondsElapsed <= 0) {
            return 0;
        }

        $distanceMeters = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
        $distanceKm = $distanceMeters / 1000;
        $hoursElapsed = $secondsElapsed / 3600;

        return $hoursElapsed > 0 ? $distanceKm / $hoursElapsed : 0;
    }
}
