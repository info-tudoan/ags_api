<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkZone extends Model
{
    protected $fillable = ['name', 'type', 'description', 'coordinates', 'radius', 'min_gps_accuracy', 'created_by'];
    protected $casts = ['coordinates' => 'json'];

    public function wifiNetworks(): HasMany
    {
        return $this->hasMany(ZoneWifiNetwork::class, 'zone_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class, 'zone_id');
    }

    public function locationTracking(): HasMany
    {
        return $this->hasMany(LocationTracking::class, 'in_zone_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
