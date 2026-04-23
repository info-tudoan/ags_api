<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'user_id', 'month', 'total_work_days', 'total_hours_worked',
        'total_delays_minutes', 'total_deducted_minutes', 'effective_hours',
        'generated_at', 'generated_by'
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'total_hours_worked' => 'decimal:2',
            'effective_hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
