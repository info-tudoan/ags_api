<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ShiftTemplate::create([
            'name' => 'Ca Sáng (Morning Shift)',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'duration_minutes' => 480,
            'delay_threshold_minutes' => 120,
        ]);

        ShiftTemplate::create([
            'name' => 'Ca Chiều (Afternoon Shift)',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'duration_minutes' => 480,
            'delay_threshold_minutes' => 120,
        ]);

        ShiftTemplate::create([
            'name' => 'Ca Đêm (Night Shift)',
            'start_time' => '00:00:00',
            'end_time' => '08:00:00',
            'duration_minutes' => 480,
            'delay_threshold_minutes' => 120,
        ]);

        ShiftTemplate::create([
            'name' => 'Ca Hành Chính (Office Hours)',
            'start_time' => '08:00:00',
            'end_time' => '17:30:00',
            'duration_minutes' => 570,
            'delay_threshold_minutes' => 60,
        ]);
    }
}
