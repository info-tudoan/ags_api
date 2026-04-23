# GPS Attendance System - Backend Implementation Guide

## Phase 1: ✅ COMPLETE
- Laravel 11 initialized with all dependencies
- 11 database migrations created and applied:
  - users (with GPS fields: role, mfa_enabled, mfa_secret, employee_id)
  - work_zones
  - zone_wifi_networks
  - shift_templates
  - shifts
  - shift_assignments
  - attendance_records
  - attendance_exceptions
  - location_tracking
  - monthly_reports
  - audit_logs
- JWT and Spatie dependencies installed
- .env configured for SQLite development

## Phase 2: Create Models with Relationships

Create these model files in `app/Models/`:

### 2.1 User Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject {
    use HasApiTokens;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'status',
        'mfa_enabled', 'mfa_secret', 'employee_id'
    ];

    protected $hidden = ['password', 'mfa_secret'];
    protected $casts = ['mfa_enabled' => 'boolean'];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return ['role' => $this->role];
    }

    // Relationships
    public function shiftAssignments(): HasMany {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function attendanceRecords(): HasMany {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function locationTracking(): HasMany {
        return $this->hasMany(LocationTracking::class);
    }

    public function monthlyReports(): HasMany {
        return $this->hasMany(MonthlyReport::class);
    }

    public function auditLogs(): HasMany {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function shifts(): HasMany {
        return $this->hasMany(Shift::class, 'team_lead_id');
    }

    public function exceptions(): HasMany {
        return $this->hasMany(AttendanceException::class);
    }

    public function approvedExceptions(): HasMany {
        return $this->hasMany(AttendanceException::class, 'approved_by');
    }
}
```

### 2.2 WorkZone Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkZone extends Model {
    protected $fillable = ['name', 'type', 'description', 'coordinates', 'radius', 'min_gps_accuracy', 'created_by'];
    protected $casts = ['coordinates' => 'json'];

    public function wifiNetworks(): HasMany {
        return $this->hasMany(ZoneWifiNetwork::class, 'zone_id');
    }

    public function shiftAssignments(): HasMany {
        return $this->hasMany(ShiftAssignment::class, 'zone_id');
    }

    public function locationTracking(): HasMany {
        return $this->hasMany(LocationTracking::class, 'in_zone_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### 2.3 ZoneWifiNetwork Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneWifiNetwork extends Model {
    protected $table = 'zone_wifi_networks';
    protected $fillable = ['zone_id', 'bssid', 'ssid', 'signal_strength', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo {
        return $this->belongsTo(WorkZone::class);
    }
}
```

### 2.4 ShiftTemplate Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model {
    protected $fillable = ['name', 'start_time', 'end_time', 'duration_minutes', 'delay_threshold_minutes'];

    public function shifts(): HasMany {
        return $this->hasMany(Shift::class, 'shift_template_id');
    }
}
```

### 2.5 Shift Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model {
    protected $fillable = ['shift_template_id', 'shift_date', 'team_lead_id'];
    protected $casts = ['shift_date' => 'date'];

    public function template(): BelongsTo {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function teamLead(): BelongsTo {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function assignments(): HasMany {
        return $this->hasMany(ShiftAssignment::class, 'shift_id');
    }
}
```

### 2.6 ShiftAssignment Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends Model {
    protected $fillable = ['shift_id', 'user_id', 'zone_id', 'status'];

    public function shift(): BelongsTo {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo {
        return $this->belongsTo(WorkZone::class);
    }

    public function attendanceRecords(): HasMany {
        return $this->hasMany(AttendanceRecord::class);
    }
}
```

### 2.7 AttendanceRecord Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model {
    protected $fillable = [
        'user_id', 'shift_assignment_id', 'check_in_time', 'check_out_time',
        'check_in_location', 'check_out_location', 'check_in_wifi_bssid',
        'check_out_wifi_bssid', 'check_in_mfa_verified', 'check_out_mfa_verified',
        'total_time_in_zone', 'status'
    ];
    protected $casts = ['check_in_location' => 'json', 'check_out_location' => 'json'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function shiftAssignment(): BelongsTo {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function exception(): HasOne {
        return $this->hasOne(AttendanceException::class);
    }
}
```

### 2.8 AttendanceException Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceException extends Model {
    protected $fillable = [
        'attendance_record_id', 'user_id', 'type', 'delay_minutes',
        'auto_deducted_minutes', 'description', 'status', 'approved_by', 'approval_reason'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord(): BelongsTo {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function approver(): BelongsTo {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
```

### 2.9 LocationTracking Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationTracking extends Model {
    protected $table = 'location_tracking';
    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'accuracy_meters', 'in_zone_id',
        'is_in_zone', 'gps_source', 'device_info', 'timestamp'
    ];
    protected $casts = ['device_info' => 'json', 'is_in_zone' => 'boolean'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo {
        return $this->belongsTo(WorkZone::class, 'in_zone_id');
    }
}
```

### 2.10 MonthlyReport Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model {
    protected $fillable = [
        'user_id', 'month', 'total_work_days', 'total_hours_worked',
        'total_delays_minutes', 'total_deducted_minutes', 'effective_hours',
        'generated_at', 'generated_by'
    ];
    protected $casts = ['generated_at' => 'datetime'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
```

### 2.11 AuditLog Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model {
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'created_at'
    ];
    protected $casts = ['old_values' => 'json', 'new_values' => 'json'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
```

## Phase 3: Setup Authentication (JWT + TOTP)

### 3.1 Create Enums
Create `app/Enums/` directory with:

- **UserRole.php**
```php
<?php
namespace App\Enums;

enum UserRole: string {
    case EMPLOYEE = 'employee';
    case TEAM_LEAD = 'team_lead';
    case ADMIN = 'admin';
    case HR = 'hr';
}
```

- **AttendanceStatus.php**
```php
<?php
namespace App\Enums;

enum AttendanceStatus: string {
    case ON_TIME = 'on_time';
    case EARLY = 'early';
    case DELAYED = 'delayed';
    case ABSENT = 'absent';
}
```

- **ExceptionType.php**
```php
<?php
namespace App\Enums;

enum ExceptionType: string {
    case DELAY_OVER_2H = 'delay_over_2h';
    case DELAY_UNDER_2H = 'delay_under_2h';
    case GPS_ANOMALY = 'gps_anomaly';
    case MANUAL_CORRECTION = 'manual_correction';
    case OUT_OF_ZONE = 'out_of_zone';
}
```

### 3.2 Create Services

Create `app/Services/MfaService.php`:
```php
<?php
namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackend;
use BaconQrCode\Writer;

class MfaService {
    protected Google2FA $google2fa;

    public function __construct() {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(string $email, string $secret): string {
        return $this->google2fa->getQRCodeUrl('GPS Attendance', $email, $secret);
    }

    public function verifyToken(string $secret, string $token): bool {
        return $this->google2fa->verifyKey($secret, $token);
    }
}
```

### 3.3 Create AuthController

Create `app/Http/Controllers/Api/AuthController.php`:
```php
<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController {
    public function __construct(protected MfaService $mfaService) {}

    public function register(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone' => 'nullable|string',
            'role' => 'required|in:employee,team_lead,admin,hr'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json(['message' => 'User created'], 201);
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        if ($user->mfa_enabled) {
            return response()->json(['message' => 'MFA required', 'mfa_required' => true]);
        }

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function mfaSetup(Request $request) {
        $user = auth()->user();
        $secret = $this->mfaService->generateSecret();
        $qrCodeUrl = $this->mfaService->getQRCodeUrl($user->email, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl
        ]);
    }

    public function mfaVerify(Request $request) {
        $validated = $request->validate([
            'token' => 'required|numeric',
            'secret' => 'required|string'
        ]);

        if (!$this->mfaService->verifyToken($validated['secret'], $validated['token'])) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        auth()->user()->update([
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($validated['secret'])
        ]);

        return response()->json(['message' => 'MFA enabled']);
    }
}
```

## Phase 4-8: Remaining Implementation

### Essential Services to Create:
1. **GeofenceService** - Point-in-polygon algorithm
2. **AttendanceService** - Check-in/out logic
3. **LocationService** - Real-time tracking
4. **ReportService** - Monthly report generation
5. **AntiSpoofService** - WiFi & GPS validation
6. **ExceptionService** - Exception handling

### API Routes Structure
```php
// routes/api.php
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('mfa/setup', [AuthController::class, 'mfaSetup'])->middleware('auth:api');
    Route::post('mfa/verify', [AuthController::class, 'mfaVerify']);
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('employee')->group(function () {
        // Employee endpoints
    });
    
    Route::middleware('role:team_lead,admin')->prefix('team-lead')->group(function () {
        // Team lead endpoints
    });
    
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Admin endpoints
    });
    
    Route::middleware('role:hr')->prefix('hr')->group(function () {
        // HR endpoints
    });
});
```

## Running Development Server

```bash
# Create .env.local for local overrides
php artisan serve --port=8000

# For WebSocket support (Soketi)
docker-compose up -d

# Generate sample data
php artisan db:seed
```

## Next Steps Priority:
1. ✅ Create all 11 models (copy from above)
2. Create services (GeofenceService, AttendanceService, etc.)
3. Create API controllers and routes
4. Implement middleware for role-based access
5. Write tests
6. Document API with Postman/OpenAPI

## Database File Location
- `database/database.sqlite` - SQLite database (for development)

For production, switch to MySQL by updating .env:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=gps_attendance
DB_USERNAME=root
DB_PASSWORD=secret
```

## Key Configuration Files
- `.env` - Environment variables
- `config/jwt.php` - JWT configuration
- `config/broadcasting.php` - WebSocket configuration

This backend is now ready for model and service implementation!
