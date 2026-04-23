# AGS API — GPS Attendance System

Backend API for the Airport Ground Staff GPS Attendance System. Automates employee time tracking using GPS geofencing, shift scheduling, exception handling, and reporting.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **MySQL** — primary database
- **JWT** (`tymon/jwt-auth`) — stateless authentication
- **TOTP MFA** (`pragmarx/google2fa`) — two-factor authentication
- **Pusher/Soketi** — real-time WebSocket broadcasting
- **Supervisor** — process management (nginx + php-fpm + queue worker)

## Roles

| Role | Permissions |
|------|-------------|
| `employee` | Check-in/out, view own dashboard & reports |
| `team_lead` | Schedule shifts, assign employees, confirm exceptions |
| `admin` | Manage zones, approve exceptions, manage users |
| `hr` | View/export monthly reports and statistics |

---

## Local Development

### Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ (or SQLite for quick start)

### Setup

```bash
git clone <repo-url>
cd ags_api

composer install

cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Edit .env with your DB credentials, then:
php artisan migrate
php artisan db:seed

php artisan serve
```

### Seed Accounts (password: `password`)

| Email | Role |
|-------|------|
| admin@gps.test | admin |
| hr@gps.test | hr |
| teamlead1@gps.test | team_lead |
| teamlead2@gps.test | team_lead |
| emp1@gps.test … emp10@gps.test | employee |

---

## Docker

### Build

```bash
docker build -t ags-api .
```

### Run

```bash
docker run -p 8000:80 \
  -e DATA_BASE_HOST=your-mysql-host \
  -e DATA_BASE_PORT=3306 \
  -e DATA_BASE_USERNAME=root \
  -e DATA_BASE_PASSWORD=secret \
  -e DATA_BASE_DB=gps_attendance \
  -e APP_ENV=production \
  -e APP_KEY=base64:your-app-key \
  -e JWT_SECRET=your-jwt-secret \
  ags-api
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DATA_BASE_HOST` | MySQL host | `127.0.0.1` |
| `DATA_BASE_PORT` | MySQL port | `3306` |
| `DATA_BASE_USERNAME` | MySQL user | `root` |
| `DATA_BASE_PASSWORD` | MySQL password | _(empty)_ |
| `DATA_BASE_DB` | Database name | `laravel` |
| `APP_KEY` | Laravel app key (base64) | _(auto-generated)_ |
| `APP_ENV` | Environment (`production`/`local`) | `production` |
| `APP_DEBUG` | Enable debug mode | `false` |
| `APP_URL` | Public URL | `http://localhost` |
| `JWT_SECRET` | JWT signing secret | _(required)_ |
| `JWT_TTL` | JWT token lifetime (minutes) | `60` |
| `LOG_LEVEL` | Log level (`error`/`debug`) | `error` |

> The container automatically runs `php artisan migrate --force` and caches config/routes on startup.

---

## API Overview

Base URL: `/api`

### Authentication

```
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me
POST   /api/auth/mfa/setup
POST   /api/auth/mfa/verify
```

### Employee

```
GET    /api/employee/dashboard
POST   /api/employee/check-in
POST   /api/employee/check-out
GET    /api/employee/location/current
GET    /api/employee/location/history
GET    /api/employee/reports/monthly
GET    /api/employee/shifts/upcoming
```

### Team Lead

```
GET    /api/team-lead/shifts
POST   /api/team-lead/shifts
GET    /api/team-lead/shifts/{id}
PATCH  /api/team-lead/shifts/{id}
POST   /api/team-lead/shifts/{id}/assign-employees
GET    /api/team-lead/attendance
GET    /api/team-lead/attendance/{userId}
GET    /api/team-lead/exceptions
PATCH  /api/team-lead/exceptions/{id}/confirm
GET    /api/team-lead/employees
GET    /api/team-lead/zones
```

### Admin

```
GET    /api/admin/zones
POST   /api/admin/zones
PATCH  /api/admin/zones/{id}
DELETE /api/admin/zones/{id}
POST   /api/admin/zones/{id}/wifi
DELETE /api/admin/zones/{id}/wifi/{wifiId}
GET    /api/admin/users
PATCH  /api/admin/users/{id}
GET    /api/admin/exceptions
PATCH  /api/admin/exceptions/{id}/approve
PATCH  /api/admin/exceptions/{id}/reject
GET    /api/admin/audit-logs
```

### HR

```
GET    /api/hr/reports
GET    /api/hr/reports/{user}
POST   /api/hr/reports/{user}/generate
POST   /api/hr/reports/export
GET    /api/hr/statistics
```

---

## Database Schema

| Table | Description |
|-------|-------------|
| `users` | All users with role & MFA settings |
| `work_zones` | GPS geofence areas (polygon or circle) |
| `zone_wifi_networks` | WiFi BSSIDs for anti-spoofing |
| `shift_templates` | Reusable shift time templates |
| `shifts` | Scheduled shift instances |
| `shift_assignments` | Employee-to-shift-to-zone mappings |
| `attendance_records` | Check-in/out records with GPS coordinates |
| `attendance_exceptions` | Delays, anomalies, manual corrections |
| `location_tracking` | Real-time GPS location history |
| `monthly_reports` | Aggregated monthly attendance reports |
| `audit_logs` | Immutable action audit trail |

---

## Docker Internals

The container runs three processes via Supervisor:

| Process | Description |
|---------|-------------|
| `php-fpm` | PHP FastCGI process manager |
| `nginx` | HTTP server (port 80) |
| `queue-worker` | Laravel queue worker for background jobs |

Config files: [`docker/nginx.conf`](docker/nginx.conf), [`docker/supervisord.conf`](docker/supervisord.conf), [`docker/entrypoint.sh`](docker/entrypoint.sh)
