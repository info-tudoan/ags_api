<?php

namespace App\Http\Controllers\Api;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceException;
use App\Models\WorkZone;
use App\Models\User;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamLeadController
{
    public function __construct(protected ExceptionService $exceptionService) {}

    public function createShifts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_template_id' => 'required|exists:shift_templates,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'name' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);
        $created = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            Shift::create([
                'shift_template_id' => $validated['shift_template_id'],
                'shift_date' => $date,
                'name' => $validated['name'] ?? null,
                'team_lead_id' => $user->id,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} shifts created successfully",
            'count' => $created,
        ], 201);
    }

    public function listShifts(Request $request): JsonResponse
    {
        $user = auth()->user();
        // Admin and HR can see all shifts; team_lead sees only their own
        $query = ($user->role === 'admin' || $user->role === 'hr')
            ? Shift::query()
            : Shift::where('team_lead_id', $user->id);

        if ($request->has('start_date')) {
            $query->whereDate('shift_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('shift_date', '<=', $request->end_date);
        }

        $shifts = $query->with(['template', 'assignments'])
            ->orderBy('shift_date', 'desc')
            ->paginate(15);

        return response()->json([
            'shifts' => $shifts,
        ]);
    }

    public function getShift(Shift $shift): JsonResponse
    {
        $user = auth()->user();
        if ($shift->team_lead_id !== $user->id && !in_array($user->role, ['admin', 'hr'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'shift' => $shift->load(['template', 'assignments.user', 'assignments.zone', 'teamLead']),
        ]);
    }

    public function updateShift(Request $request, Shift $shift): JsonResponse
    {
        $user = auth()->user();
        if ($shift->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'shift_template_id' => 'required|exists:shift_templates,id',
            'shift_date' => 'required|date',
        ]);

        $shift->update($validated);

        return response()->json([
            'message' => 'Shift updated',
            'shift' => $shift->load(['template', 'assignments']),
        ]);
    }

    public function assignEmployees(Request $request, Shift $shift): JsonResponse
    {
        $user = auth()->user();
        if ($shift->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.zone_id' => 'required|exists:work_zones,id',
        ]);

        $created = 0;
        foreach ($validated['assignments'] as $assignment) {
            ShiftAssignment::updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'user_id' => $assignment['user_id'],
                    'zone_id' => $assignment['zone_id'],
                ],
                ['status' => 'scheduled']
            );
            $created++;
        }

        return response()->json([
            'message' => "{$created} employees assigned",
            'count' => $created,
        ], 201);
    }

    public function teamAttendance(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = $request->query('date', today()->toDateString());

        $records = AttendanceRecord::whereHas('shiftAssignment', function ($q) use ($user) {
            $q->whereHas('shift', function ($sq) use ($user) {
                $sq->where('team_lead_id', $user->id);
            });
        })
            ->whereDate('created_at', $date)
            ->with(['user', 'shiftAssignment'])
            ->get();

        return response()->json([
            'date' => $date,
            'records' => $records,
            'total' => $records->count(),
        ]);
    }

    public function memberAttendance(Request $request, $userId): JsonResponse
    {
        $user = auth()->user();
        $startDate = $request->query('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $records = AttendanceRecord::where('user_id', $userId)
            ->whereHas('shiftAssignment', function ($q) use ($user) {
                $q->whereHas('shift', function ($sq) use ($user) {
                    $sq->where('team_lead_id', $user->id);
                });
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'exception'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user_id' => $userId,
            'records' => $records,
            'total' => $records->count(),
        ]);
    }

    public function confirmException(Request $request, AttendanceException $exception): JsonResponse
    {
        $user = auth()->user();

        $authorized = $exception->attendanceRecord
            ->shiftAssignment
            ->shift
            ->team_lead_id === $user->id;

        if (!$authorized) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $updated = $this->exceptionService->approveException(
            $exception,
            $user,
            $validated['reason'] ?? ''
        );

        return response()->json([
            'message' => 'Exception confirmed',
            'exception' => $updated,
        ]);
    }

    public function listEmployees(Request $request): JsonResponse
    {
        $employees = User::where('role', 'employee')
            ->where('status', 'active')
            ->select(['id', 'name', 'email', 'employee_id', 'role'])
            ->orderBy('name')
            ->get();

        return response()->json(['users' => $employees]);
    }

    public function listZones(): JsonResponse
    {
        $zones = WorkZone::select(['id', 'name', 'type'])->get();

        return response()->json(['zones' => $zones]);
    }

    public function listExceptions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $status = $request->query('status', 'pending');

        $exceptions = AttendanceException::where('status', $status)
            ->whereHas('attendanceRecord', function ($q) use ($user) {
                $q->whereHas('shiftAssignment', function ($sq) use ($user) {
                    $sq->whereHas('shift', function ($sq) use ($user) {
                        $sq->where('team_lead_id', $user->id);
                    });
                });
            })
            ->with(['user', 'attendanceRecord'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'exceptions' => $exceptions,
        ]);
    }
}
