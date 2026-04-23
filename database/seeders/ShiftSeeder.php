<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\WorkZone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $morningTemplate = ShiftTemplate::where('name', 'like', '%Sáng%')->first();
        $afternoonTemplate = ShiftTemplate::where('name', 'like', '%Chiều%')->first();

        $teamLead1 = User::where('email', 'teamlead1@gps.test')->first();
        $teamLead2 = User::where('email', 'teamlead2@gps.test')->first();

        $employees = User::where('role', 'employee')->get();
        $parkingZone = WorkZone::where('name', 'like', '%Parking%')->first();
        $warehouseZone = WorkZone::where('name', 'like', '%Warehouse%')->first();

        // Create shifts for the next 7 days
        for ($i = 0; $i < 7; $i++) {
            $shiftDate = Carbon::today()->addDays($i)->toDateString();

            // Morning shift - Team Lead 1
            $morningShift = Shift::create([
                'shift_template_id' => $morningTemplate->id,
                'shift_date' => $shiftDate,
                'team_lead_id' => $teamLead1->id,
            ]);

            // Assign first 5 employees to morning shift
            foreach ($employees->take(5) as $employee) {
                ShiftAssignment::create([
                    'shift_id' => $morningShift->id,
                    'user_id' => $employee->id,
                    'zone_id' => $parkingZone->id,
                    'status' => 'scheduled',
                ]);
            }

            // Afternoon shift - Team Lead 2
            $afternoonShift = Shift::create([
                'shift_template_id' => $afternoonTemplate->id,
                'shift_date' => $shiftDate,
                'team_lead_id' => $teamLead2->id,
            ]);

            // Assign last 5 employees to afternoon shift
            foreach ($employees->skip(5)->take(5) as $employee) {
                ShiftAssignment::create([
                    'shift_id' => $afternoonShift->id,
                    'user_id' => $employee->id,
                    'zone_id' => $warehouseZone->id,
                    'status' => 'scheduled',
                ]);
            }
        }
    }
}
