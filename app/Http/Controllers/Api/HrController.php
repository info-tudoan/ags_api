<?php

namespace App\Http\Controllers\Api;

use App\Models\MonthlyReport;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrController
{
    public function __construct(protected ReportService $reportService) {}

    public function listReports(Request $request): JsonResponse
    {
        $query = MonthlyReport::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        $reports = $query->with('user', 'generator')
            ->orderBy('month', 'desc')
            ->paginate(20);

        return response()->json([
            'reports' => $reports,
        ]);
    }

    public function getReport(User $user, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $report = $this->reportService->getMonthlyReport($user, $validated['month']);

        if (!$report) {
            return response()->json([
                'message' => 'Report not found',
            ], 404);
        }

        return response()->json([
            'report' => $report,
        ]);
    }

    public function generateReport(User $user, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $admin = auth()->user();
        $report = $this->reportService->generateMonthlyReport($user, $validated['month'], $admin);

        return response()->json([
            'message' => 'Report generated',
            'report' => $report,
        ], 201);
    }

    public function generateAllReports(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $admin = auth()->user();
        $count = $this->reportService->generateReportsForMonth($validated['month'], $admin);

        return response()->json([
            'message' => "{$count} reports generated",
            'count' => $count,
        ], 201);
    }

    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $totalRecords = AttendanceRecord::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalUsers = User::where('status', 'active')->count();
        $avgRecordsPerUser = $totalUsers > 0 ? $totalRecords / $totalUsers : 0;

        $recordsByStatus = AttendanceRecord::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $recordsByRole = AttendanceRecord::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('user', function ($q) {
                $q->select('role');
            })
            ->join('users', 'attendance_records.user_id', '=', 'users.id')
            ->groupBy('users.role')
            ->selectRaw('users.role, count(*) as count')
            ->pluck('count', 'role')
            ->toArray();

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_records' => $totalRecords,
                'total_users' => $totalUsers,
                'avg_records_per_user' => round($avgRecordsPerUser, 2),
            ],
            'by_status' => $recordsByStatus,
            'by_role' => $recordsByRole,
        ]);
    }

    public function exportReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'month' => 'required|date_format:Y-m',
            'format' => 'required|in:pdf,csv',
        ]);

        if ($validated['format'] === 'pdf') {
            return response()->json([
                'message' => 'PDF export functionality requires barryvdh/laravel-dompdf',
                'download_url' => "/api/hr/reports/export/pdf/{$validated['user_id']}/{$validated['month']}",
            ]);
        }

        if ($validated['format'] === 'csv') {
            return response()->json([
                'message' => 'CSV export ready',
                'download_url' => "/api/hr/reports/export/csv/{$validated['user_id']}/{$validated['month']}",
            ]);
        }

        return response()->json([
            'message' => 'Invalid format',
        ], 400);
    }
}
