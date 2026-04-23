<?php

namespace App\Services;

use App\Models\AttendanceException;
use App\Models\AttendanceRecord;
use App\Models\User;

class ExceptionService
{
    /**
     * Get pending exceptions for approval
     */
    public function getPendingExceptions(User $approver): mixed
    {
        $query = AttendanceException::where('status', 'pending');

        if ($approver->isTeamLead()) {
            $query->whereHas('attendanceRecord', function ($q) use ($approver) {
                $q->whereHas('shiftAssignment', function ($sq) use ($approver) {
                    $sq->whereHas('shift', function ($sq) use ($approver) {
                        $sq->where('team_lead_id', $approver->id);
                    });
                });
            });
        }

        return $query->with('user', 'attendanceRecord')->get();
    }

    /**
     * Approve exception
     */
    public function approveException(AttendanceException $exception, User $approver, string $reason = ''): AttendanceException
    {
        $exception->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approval_reason' => $reason,
        ]);

        return $exception;
    }

    /**
     * Reject exception
     */
    public function rejectException(AttendanceException $exception, User $approver, string $reason = ''): AttendanceException
    {
        $exception->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approval_reason' => $reason,
            'auto_deducted_minutes' => 0,
        ]);

        return $exception;
    }

    /**
     * Get exceptions for user
     */
    public function getUserExceptions(User $user, ?string $status = null)
    {
        $query = AttendanceException::where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with('attendanceRecord')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create manual exception
     */
    public function createManualException(
        User $user,
        ?AttendanceRecord $record,
        string $type,
        string $description,
        ?int $delayMinutes = null
    ): AttendanceException {
        return AttendanceException::create([
            'attendance_record_id' => $record?->id,
            'user_id' => $user->id,
            'type' => $type,
            'delay_minutes' => $delayMinutes,
            'description' => $description,
            'status' => 'pending',
            'auto_deducted_minutes' => 0,
        ]);
    }

    /**
     * Get exception statistics
     */
    public function getExceptionStats(User $user, string $month): array
    {
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $exceptions = AttendanceException::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_exceptions' => $exceptions->count(),
            'approved' => $exceptions->where('status', 'approved')->count(),
            'rejected' => $exceptions->where('status', 'rejected')->count(),
            'pending' => $exceptions->where('status', 'pending')->count(),
            'total_delay_minutes' => $exceptions->sum('delay_minutes'),
            'total_deducted_minutes' => $exceptions->sum('auto_deducted_minutes'),
        ];
    }
}
