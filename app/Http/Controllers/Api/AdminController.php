<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkZone;
use App\Models\ZoneWifiNetwork;
use App\Models\AttendanceException;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController
{
    public function __construct(protected ExceptionService $exceptionService) {}

    public function listZones(): JsonResponse
    {
        $zones = WorkZone::with('wifiNetworks')->get();

        return response()->json([
            'zones' => $zones,
        ]);
    }

    public function createZone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:primary,secondary,break',
            'description' => 'nullable|string',
            'coordinates' => 'required|array',
            'radius' => 'nullable|integer',
            'min_gps_accuracy' => 'nullable|integer',
        ]);

        $validated['created_by'] = auth()->id();

        $zone = WorkZone::create($validated);

        return response()->json([
            'message' => 'Zone created successfully',
            'zone' => $zone,
        ], 201);
    }

    public function updateZone(Request $request, WorkZone $zone): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:primary,secondary,break',
            'description' => 'nullable|string',
            'coordinates' => 'sometimes|array',
            'radius' => 'nullable|integer',
            'min_gps_accuracy' => 'nullable|integer',
        ]);

        $zone->update($validated);

        return response()->json([
            'message' => 'Zone updated',
            'zone' => $zone->load('wifiNetworks'),
        ]);
    }

    public function deleteZone(WorkZone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json([
            'message' => 'Zone deleted',
        ]);
    }

    public function addWifiNetwork(Request $request, WorkZone $zone): JsonResponse
    {
        $validated = $request->validate([
            'bssid' => 'required|string|unique:zone_wifi_networks',
            'ssid' => 'required|string',
            'signal_strength' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['zone_id'] = $zone->id;

        $network = ZoneWifiNetwork::create($validated);

        return response()->json([
            'message' => 'WiFi network added',
            'network' => $network,
        ], 201);
    }

    public function removeWifiNetwork(WorkZone $zone, ZoneWifiNetwork $network): JsonResponse
    {
        if ($network->zone_id !== $zone->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $network->delete();

        return response()->json([
            'message' => 'WiFi network removed',
        ]);
    }

    public function listExceptions(Request $request): JsonResponse
    {
        $query = AttendanceException::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $exceptions = $query->with(['user', 'attendanceRecord', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'exceptions' => $exceptions,
        ]);
    }

    public function approveException(Request $request, AttendanceException $exception): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $updated = $this->exceptionService->approveException(
            $exception,
            $user,
            $validated['reason'] ?? ''
        );

        return response()->json([
            'message' => 'Exception approved',
            'exception' => $updated,
        ]);
    }

    public function rejectException(Request $request, AttendanceException $exception): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $updated = $this->exceptionService->rejectException(
            $exception,
            $user,
            $validated['reason'] ?? ''
        );

        return response()->json([
            'message' => 'Exception rejected',
            'exception' => $updated,
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $logs = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    public function listUsers(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('name', 'asc')
            ->paginate(20);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'sometimes|in:employee,team_lead,admin,hr',
            'status' => 'sometimes|in:active,inactive',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated',
            'user' => $user,
        ]);
    }
}
