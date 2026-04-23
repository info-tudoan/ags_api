<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceException;
use App\Models\MonthlyReport;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate monthly report for user
     */
    public function generateMonthlyReport(User $user, string $month, ?User $generatedBy = null): MonthlyReport
    {
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $records = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalWorkDays = $records->count();
        $totalMinutesWorked = $records->sum('total_time_in_zone') ?? 0;
        $totalHoursWorked = $totalMinutesWorked / 60;

        $exceptions = AttendanceException::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalDelaysMinutes = $exceptions->sum('delay_minutes') ?? 0;
        $totalDeductedMinutes = $exceptions->sum('auto_deducted_minutes') ?? 0;

        $effectiveHours = $totalHoursWorked - ($totalDeductedMinutes / 60);

        return MonthlyReport::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month,
            ],
            [
                'total_work_days' => $totalWorkDays,
                'total_hours_worked' => round($totalHoursWorked, 2),
                'total_delays_minutes' => $totalDelaysMinutes,
                'total_deducted_minutes' => $totalDeductedMinutes,
                'effective_hours' => round($effectiveHours, 2),
                'generated_at' => now(),
                'generated_by' => $generatedBy?->id,
            ]
        );
    }

    /**
     * Get monthly report for user
     */
    public function getMonthlyReport(User $user, string $month): ?MonthlyReport
    {
        return MonthlyReport::where('user_id', $user->id)
            ->where('month', $month)
            ->first();
    }

    /**
     * Get all reports for user with pagination
     */
    public function getUserReports(User $user, int $perPage = 12)
    {
        return MonthlyReport::where('user_id', $user->id)
            ->orderBy('month', 'desc')
            ->paginate($perPage);
    }

    /**
     * Generate reports for all users for given month
     */
    public function generateReportsForMonth(string $month, ?User $generatedBy = null): int
    {
        $users = User::where('status', 'active')->get();
        $count = 0;

        foreach ($users as $user) {
            $this->generateMonthlyReport($user, $month, $generatedBy);
            $count++;
        }

        return $count;
    }
}
