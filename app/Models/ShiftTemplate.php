<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'duration_minutes', 'delay_threshold_minutes'];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'shift_template_id');
    }
}
