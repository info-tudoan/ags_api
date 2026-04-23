<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'user_id', 'shift_assignment_id', 'check_in_time', 'check_out_time',
        'check_in_location', 'check_out_location', 'check_in_wifi_bssid',
        'check_out_wifi_bssid', 'check_in_mfa_verified', 'check_out_mfa_verified',
        'total_time_in_zone', 'status'
    ];

    protected function casts(): array
    {
        return [
            'check_in_location' => 'json',
            'check_out_location' => 'json',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_mfa_verified' => 'boolean',
            'check_out_mfa_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function exception(): HasOne
    {
        return $this->hasOne(AttendanceException::class);
    }
}
