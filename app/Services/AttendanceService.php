<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceException;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    /**
     * Record check-in for user
     */
    public function checkIn(
        User $user,
        ShiftAssignment $assignment,
        float $latitude,
        float $longitude,
        int $accuracy,
        ?string $wifiBssid = null,
        bool $mfaVerified = true
    ): AttendanceRecord {
        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'shift_assignment_id' => $assignment->id,
            'check_in_time' => now(),
            'check_in_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
            'check_in_wifi_bssid' => $wifiBssid,
            'check_in_mfa_verified' => $mfaVerified,
            'status' => 'on_time',
        ]);

        $this->calculateDelay($record, $assignment);

        return $record;
    }

    /**
     * Record check-out for user
     */
    public function checkOut(
        AttendanceRecord $record,
        float $latitude,
        float $longitude,
        int $accuracy,
        ?string $wifiBssid = null,
        bool $mfaVerified = true
    ): AttendanceRecord {
        $record->update([
            'check_out_time' => now(),
            'check_out_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
            'check_out_wifi_bssid' => $wifiBssid,
            'check_out_mfa_verified' => $mfaVerified,
            'total_time_in_zone' => $record->check_in_time->diffInMinutes(now()),
        ]);

        return $record;
    }

    /**
     * Calculate delay and create exception if needed
     */
    protected function calculateDelay(AttendanceRecord $record, ShiftAssignment $assignment): void
    {
        $shift = $assignment->shift;
        $template = $shift->template;
        $shiftStartTime = Carbon::parse($template->start_time);
        $checkInTime = $record->check_in_time;

        if ($checkInTime->isAfter($shiftStartTime)) {
            $delayMinutes = $checkInTime->diffInMinutes($shiftStartTime);

            $exception = AttendanceException::create([
                'attendance_record_id' => $record->id,
                'user_id' => $record->user_id,
                'type' => $delayMinutes > 120 ? 'delay_over_2h' : 'delay_under_2h',
                'delay_minutes' => $delayMinutes,
                'auto_deducted_minutes' => $delayMinutes > 120 ? 120 : 0,
                'description' => "User checked in {$delayMinutes} minutes late",
                'status' => $delayMinutes > 120 ? 'pending' : 'pending_confirmation',
            ]);

            if ($delayMinutes <= 120) {
                $record->update(['status' => 'delayed']);
            } else {
                $record->update(['status' => 'delayed']);
            }
        }
    }

    /**
     * Get current attendance status for user
     */
    public function getCurrentStatus(User $user): ?AttendanceRecord
    {
        return AttendanceRecord::where('user_id', $user->id)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();
    }
}
