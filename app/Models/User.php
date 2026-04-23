<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'status',
        'mfa_enabled', 'mfa_secret', 'employee_id'
    ];

    protected $hidden = [
        'password', 'remember_token', 'mfa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->role];
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceExceptions(): HasMany
    {
        return $this->hasMany(AttendanceException::class);
    }

    public function locationTracking(): HasMany
    {
        return $this->hasMany(LocationTracking::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function shiftsAsTeamLead(): HasMany
    {
        return $this->hasMany(Shift::class, 'team_lead_id');
    }

    public function approvedExceptions(): HasMany
    {
        return $this->hasMany(AttendanceException::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeamLead(): bool
    {
        return $this->role === 'team_lead';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }
}
