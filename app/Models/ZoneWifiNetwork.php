<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneWifiNetwork extends Model
{
    protected $table = 'zone_wifi_networks';
    protected $fillable = ['zone_id', 'bssid', 'ssid', 'signal_strength', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WorkZone::class);
    }
}
