<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\TeamLeadController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HrController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Setup endpoint — protected by secret key, for initial deployment only
Route::post('setup', function (\Illuminate\Http\Request $request) {
    $secret = env('SETUP_SECRET', '');
    if (!$secret || $request->header('X-Setup-Secret') !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $output = [];

    Artisan::call('migrate', ['--force' => true]);
    $output['migrate'] = Artisan::output();

    if ($request->boolean('seed', false)) {
        Artisan::call('db:seed', ['--force' => true]);
        $output['seed'] = Artisan::output();
    }

    Artisan::call('config:cache');
    Artisan::call('route:cache');

    return response()->json(['message' => 'Setup complete', 'output' => $output]);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('mfa/verify-login', [AuthController::class, 'mfaVerifyLogin']);

    Route::middleware('auth:api')->group(function () {
        Route::post('mfa/setup', [AuthController::class, 'mfaSetup']);
        Route::post('mfa/verify', [AuthController::class, 'mfaVerify']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('employee')->group(function () {
        Route::get('dashboard', [EmployeeController::class, 'dashboard']);
        Route::post('check-in', [EmployeeController::class, 'checkIn']);
        Route::post('check-out', [EmployeeController::class, 'checkOut']);
        Route::get('location/current', [EmployeeController::class, 'currentLocation']);
        Route::get('location/history', [EmployeeController::class, 'locationHistory']);
        Route::get('reports/monthly', [EmployeeController::class, 'monthlyReport']);
        Route::get('shifts/upcoming', [EmployeeController::class, 'upcomingShifts']);
    });

    Route::middleware('role:team_lead,shift_manager,admin')->prefix('team-lead')->group(function () {
        Route::post('shifts', [TeamLeadController::class, 'createShifts']);
        Route::get('shifts', [TeamLeadController::class, 'listShifts']);
        Route::get('shifts/{shift}', [TeamLeadController::class, 'getShift']);
        Route::patch('shifts/{shift}', [TeamLeadController::class, 'updateShift']);
        Route::delete('shifts/{shift}', [TeamLeadController::class, 'deleteShift']);
        Route::post('shifts/{shift}/assign-employees', [TeamLeadController::class, 'assignEmployees']);
        Route::delete('shifts/{shift}/employees/{userId}', [TeamLeadController::class, 'unassignEmployee']);
        Route::get('attendance', [TeamLeadController::class, 'teamAttendance']);
        Route::get('attendance/{userId}', [TeamLeadController::class, 'memberAttendance']);
        Route::patch('exceptions/{exception}/confirm', [TeamLeadController::class, 'confirmException']);
        Route::get('exceptions', [TeamLeadController::class, 'listExceptions']);
        Route::get('employees', [TeamLeadController::class, 'listEmployees']);
        Route::get('zones', [TeamLeadController::class, 'listZones']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('zones', [AdminController::class, 'listZones']);
        Route::post('zones', [AdminController::class, 'createZone']);
        Route::patch('zones/{zone}', [AdminController::class, 'updateZone']);
        Route::delete('zones/{zone}', [AdminController::class, 'deleteZone']);
        Route::post('zones/{zone}/wifi', [AdminController::class, 'addWifiNetwork']);
        Route::delete('zones/{zone}/wifi/{network}', [AdminController::class, 'removeWifiNetwork']);
        Route::get('exceptions', [AdminController::class, 'listExceptions']);
        Route::patch('exceptions/{exception}/approve', [AdminController::class, 'approveException']);
        Route::patch('exceptions/{exception}/reject', [AdminController::class, 'rejectException']);
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('users', [AdminController::class, 'listUsers']);
        Route::patch('users/{user}', [AdminController::class, 'updateUser']);
    });

    Route::middleware('role:hr')->prefix('hr')->group(function () {
        Route::get('reports', [HrController::class, 'listReports']);
        Route::get('reports/{user}', [HrController::class, 'getReport']);
        Route::post('reports/{user}/generate', [HrController::class, 'generateReport']);
        Route::post('reports/generate-all', [HrController::class, 'generateAllReports']);
        Route::get('statistics', [HrController::class, 'statistics']);
        Route::post('reports/export', [HrController::class, 'exportReport']);
    });
});
