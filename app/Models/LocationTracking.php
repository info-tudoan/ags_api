<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationTracking extends Model
{
    protected $table = 'location_tracking';

    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'accuracy_meters', 'in_zone_id',
        'is_in_zone', 'gps_source', 'device_info', 'timestamp'
    ];

    protected function casts(): array
    {
        return [
            'device_info' => 'json',
            'is_in_zone' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WorkZone::class, 'in_zone_id');
    }
}
