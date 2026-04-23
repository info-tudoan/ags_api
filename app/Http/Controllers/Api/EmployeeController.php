<?php

namespace App\Http\Controllers\Api;

use App\Models\AttendanceRecord;
use App\Models\LocationTracking;
use App\Models\ShiftAssignment;
use App\Models\MonthlyReport;
use App\Services\AttendanceService;
use App\Services\LocationService;
use App\Services\AntiSpoofService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected LocationService $locationService,
        protected AntiSpoofService $antiSpoofService
    ) {}

    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        $todayAssignment = ShiftAssignment::where('user_id', $user->id)
            ->whereHas('shift', function ($q) {
                $q->whereDate('shift_date', today());
            })
            ->first();

        $todayAttendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->first();

        $currentLocation = LocationTracking::where('user_id', $user->id)
            ->latest('timestamp')
            ->first();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'assignment' => $todayAssignment,
            'attendance' => $todayAttendance,
            'current_location' => $currentLocation,
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_assignment_id' => 'required|exists:shift_assignments,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|integer',
            'wifi_bssid' => 'nullable|string',
            'device_info' => 'nullable|array',
        ]);

        $user = auth()->user();
        $assignment = ShiftAssignment::find($validated['shift_assignment_id']);

        if ($assignment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $violations = $this->antiSpoofService->runFullValidation(
            $user,
            $assignment->zone,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy'],
            $validated['wifi_bssid'],
            $validated['device_info'] ?? []
        );

        if (!empty($violations)) {
            return response()->json([
                'message' => 'Check-in validation failed',
                'violations' => $violations,
            ], 400);
        }

        $this->locationService->trackLocation(
            $user,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy'],
            'gps',
            $validated['device_info']
        );

        $record = $this->attendanceService->checkIn(
            $user,
            $assignment,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy'],
            $validated['wifi_bssid']
        );

        return response()->json([
            'message' => 'Check-in successful',
            'record' => $record,
        ], 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendance_record_id' => 'required|exists:attendance_records,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|integer',
            'wifi_bssid' => 'nullable|string',
        ]);

        $user = auth()->user();
        $record = AttendanceRecord::find($validated['attendance_record_id']);

        if ($record->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->locationService->trackLocation(
            $user,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy']
        );

        $updated = $this->attendanceService->checkOut(
            $record,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy'],
            $validated['wifi_bssid']
        );

        return response()->json([
            'message' => 'Check-out successful',
            'record' => $updated,
        ]);
    }

    public function currentLocation(): JsonResponse
    {
        $user = auth()->user();
        $location = LocationTracking::where('user_id', $user->id)
            ->latest('timestamp')
            ->first();

        return response()->json([
            'location' => $location,
        ]);
    }

    public function locationHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $user = auth()->user();
        $locations = $this->locationService->getLocationHistory(
            $user,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'locations' => $locations,
        ]);
    }

    public function monthlyReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $user = auth()->user();
        $report = MonthlyReport::where('user_id', $user->id)
            ->where('month', $validated['month'])
            ->first();

        return response()->json([
            'report' => $report,
        ]);
    }

    public function upcomingShifts(): JsonResponse
    {
        $user = auth()->user();
        $shifts = ShiftAssignment::where('user_id', $user->id)
            ->whereHas('shift', function ($q) {
                $q->whereDate('shift_date', '>=', today());
            })
            ->with(['shift', 'zone'])
            ->orderBy('shift_date', 'asc')
            ->get();

        return response()->json([
            'shifts' => $shifts,
        ]);
    }
}
