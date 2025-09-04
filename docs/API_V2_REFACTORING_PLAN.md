# API v2 Refactoring Plan - Dokterku Medical Clinic Management System

## Executive Summary

This document outlines a comprehensive refactoring plan for implementing API v2 in the Dokterku medical clinic management system. The current system uses Laravel 11 + FilamentPHP with a basic API structure focused on Paramedis mobile features. This plan aims to create a scalable, enterprise-grade API architecture that supports all user roles with mobile-first design while maintaining backward compatibility.

## Current System Analysis

### Existing Architecture
- **Framework**: Laravel 11 + FilamentPHP
- **Authentication**: Laravel Sanctum (token-based)
- **API Version**: v1 (implicit, no versioning)
- **User Roles**: 7 roles (Admin, Manajer, Bendahara, Petugas, Paramedis, Dokter, Non-Paramedis)
- **Current API Focus**: Paramedis attendance and dashboard features
- **Mobile Support**: Limited to Paramedis role

### Current API Endpoints (v1)
```
API Authentication: /api/user
Attendance System: /api/attendance/* (GPS-based)
Face Recognition: /api/face-recognition/*
Paramedis Dashboard: /api/paramedis/*
Work Locations: /api/work-locations/active
```

### Technical Stack Assessment
- **Strengths**: Well-organized controllers, Sanctum integration, GPS features
- **Weaknesses**: No API versioning, limited mobile support, inconsistent response formats
- **Security**: Basic device binding, GPS spoofing detection
- **Performance**: No caching, basic pagination

## 1. API v2 Architecture Design

### 1.1 RESTful API Structure Blueprint

```
/api/v2/
├── auth/                          # Authentication endpoints
│   ├── login                      # Unified login (email/username)
│   ├── logout                     # Token revocation
│   ├── refresh                    # Token refresh
│   ├── me                         # Current user info
│   ├── forgot-password            # Password reset
│   └── reset-password             # Password reset confirmation
├── users/                         # User management
│   ├── profile                    # User profile CRUD
│   ├── avatar                     # Profile picture upload
│   ├── preferences                # User preferences
│   └── devices                    # Device management
├── attendance/                    # Universal attendance system
│   ├── checkin                    # GPS-based check-in
│   ├── checkout                   # GPS-based check-out
│   ├── history                    # Attendance history
│   ├── today                      # Today's status
│   ├── quick-checkin              # Dashboard quick check-in
│   ├── quick-checkout             # Dashboard quick check-out
│   └── statistics                 # Attendance stats
├── dashboards/                    # Role-specific dashboards
│   ├── admin                      # Admin dashboard data
│   ├── manajer                    # Manager dashboard data
│   ├── bendahara                  # Treasurer dashboard data
│   ├── petugas                    # Staff dashboard data
│   ├── paramedis                  # Paramedis dashboard data
│   ├── dokter                     # Doctor dashboard data
│   └── non-paramedis             # Non-Paramedis dashboard data
├── jaspel/                       # Service fee management
│   ├── calculations               # Jaspel calculations
│   ├── history                    # Jaspel history
│   ├── reports                    # Jaspel reports
│   └── approvals                  # Approval workflows
├── schedules/                     # Scheduling system
│   ├── my-schedule                # User's schedule
│   ├── shifts                     # Shift management
│   ├── calendar                   # Calendar view
│   └── requests                   # Schedule change requests
├── patients/                      # Patient management
│   ├── list                       # Patient listing
│   ├── search                     # Patient search
│   ├── create                     # New patient
│   └── {id}                       # Patient details
├── transactions/                  # Financial transactions
│   ├── income                     # Income records
│   ├── expenses                   # Expense records
│   ├── validations                # Transaction validations
│   └── reports                    # Financial reports
├── notifications/                 # Notification system
│   ├── list                       # User notifications
│   ├── mark-read                  # Mark as read
│   ├── settings                   # Notification preferences
│   └── push-tokens                # Push notification tokens
├── face-recognition/              # Face recognition system
│   ├── register                   # Register face
│   ├── verify                     # Verify face
│   ├── update                     # Update face data
│   └── status                     # Registration status
├── locations/                     # Location management
│   ├── work-locations             # Work location list
│   ├── validate                   # GPS validation
│   └── spoofing-check             # GPS spoofing detection
├── files/                        # File management
│   ├── upload                     # File upload
│   ├── download/{id}              # File download
│   └── employee-cards             # Employee card downloads
└── system/                       # System information
    ├── config                     # System configuration
    ├── health                     # Health check
    └── version                    # API version info
```

### 1.2 Standardized Response Format

```json
{
  "success": true|false,
  "message": "Human readable message",
  "data": {
    // Response payload
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00Z",
    "request_id": "uuid-v4",
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 15,
      "total": 150
    }
  },
  "errors": {
    // Validation errors (422 responses)
  }
}
```

### 1.3 Error Handling Standards

```json
// HTTP 422 - Validation Error
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Field is required"],
    "email": ["Email must be valid"]
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00Z",
    "request_id": "uuid-v4"
  }
}

// HTTP 401 - Unauthorized
{
  "success": false,
  "message": "Unauthenticated",
  "error_code": "UNAUTHORIZED",
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00Z",
    "request_id": "uuid-v4"
  }
}

// HTTP 403 - Forbidden
{
  "success": false,
  "message": "Access denied for this resource",
  "error_code": "FORBIDDEN",
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00Z",
    "request_id": "uuid-v4"
  }
}
```

## 2. Endpoint Mapping Plan (v1 to v2 Migration)

### 2.1 Current v1 → New v2 Mapping

| Current v1 Endpoint | New v2 Endpoint | Changes |
|-------------------|-----------------|---------|
| `/api/user` | `/api/v2/auth/me` | Moved to auth namespace |
| `/api/attendance/checkin` | `/api/v2/attendance/checkin` | Enhanced with device management |
| `/api/attendance/checkout` | `/api/v2/attendance/checkout` | Enhanced with device management |
| `/api/attendance/history` | `/api/v2/attendance/history` | Improved pagination |
| `/api/attendance/today` | `/api/v2/attendance/today` | Additional status info |
| `/api/face-recognition/*` | `/api/v2/face-recognition/*` | Standardized responses |
| `/api/paramedis/dashboard` | `/api/v2/dashboards/paramedis` | Moved to dashboards namespace |
| `/api/paramedis/attendance/*` | `/api/v2/attendance/*` | Unified attendance system |
| `/api/work-locations/active` | `/api/v2/locations/work-locations` | Enhanced location data |

### 2.2 New v2 Endpoints

| Endpoint | Purpose | Target Roles |
|----------|---------|--------------|
| `/api/v2/dashboards/admin` | Admin dashboard data | Admin |
| `/api/v2/dashboards/manajer` | Manager dashboard data | Manajer |
| `/api/v2/dashboards/bendahara` | Treasurer dashboard data | Bendahara |
| `/api/v2/dashboards/petugas` | Staff dashboard data | Petugas |
| `/api/v2/dashboards/dokter` | Doctor dashboard data | Dokter |
| `/api/v2/dashboards/non-paramedis` | Non-Paramedis dashboard data | Non-Paramedis |
| `/api/v2/jaspel/*` | Service fee management | All roles |
| `/api/v2/schedules/*` | Scheduling system | All roles |
| `/api/v2/notifications/*` | Push notifications | All roles |

## 3. Authentication & Authorization Strategy

### 3.1 Dual Authentication System

```php
// config/auth.php - Enhanced guards
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
    'api_v2' => [
        'driver' => 'passport', // OAuth2 for enterprise features
        'provider' => 'users',
    ],
],
```

### 3.2 Token Management Strategy

```php
// Token Types and Scopes
'token_types' => [
    'mobile_app' => [
        'expires_in' => 30 * 24 * 60, // 30 days
        'scopes' => ['mobile:attendance', 'mobile:dashboard', 'mobile:notifications'],
        'can_refresh' => true,
    ],
    'web_app' => [
        'expires_in' => 24 * 60, // 24 hours
        'scopes' => ['web:full'],
        'can_refresh' => true,
    ],
    'api_client' => [
        'expires_in' => 365 * 24 * 60, // 1 year
        'scopes' => ['api:read', 'api:write'],
        'can_refresh' => false,
    ],
],
```

### 3.3 Role-Based Access Control (RBAC)

```php
// Enhanced permission system
'permissions' => [
    'attendance' => [
        'view_own' => ['paramedis', 'dokter', 'non_paramedis'],
        'view_all' => ['admin', 'manajer', 'petugas'],
        'manage' => ['admin', 'petugas'],
    ],
    'dashboard' => [
        'view_own' => ['all'],
        'view_analytics' => ['admin', 'manajer', 'bendahara'],
    ],
    'jaspel' => [
        'view_own' => ['paramedis', 'dokter'],
        'view_all' => ['admin', 'manajer', 'bendahara'],
        'approve' => ['manajer', 'bendahara'],
    ],
],
```

### 3.4 Device Binding and Security

```php
// Enhanced device management
class DeviceSecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        
        // Check device registration
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('status', 'active')
            ->first();
            
        if (!$device && $this->requiresDeviceBinding($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Device not registered',
                'error_code' => 'DEVICE_NOT_REGISTERED'
            ], 403);
        }
        
        return $next($request);
    }
}
```

## 4. Mobile-First API Design

### 4.1 Mobile Endpoints for All User Roles

#### 4.1.1 Admin Mobile Dashboard
```json
GET /api/v2/dashboards/admin
{
  "success": true,
  "data": {
    "overview": {
      "total_users": 150,
      "active_users": 142,
      "pending_approvals": 8,
      "system_alerts": 2
    },
    "recent_activities": [],
    "financial_summary": {},
    "user_management": {
      "new_registrations": 3,
      "pending_devices": 5
    }
  }
}
```

#### 4.1.2 Manajer Mobile Dashboard
```json
GET /api/v2/dashboards/manajer
{
  "success": true,
  "data": {
    "team_overview": {
      "total_staff": 45,
      "present_today": 42,
      "on_leave": 3,
      "late_arrivals": 5
    },
    "performance_metrics": {},
    "jaspel_approvals": {
      "pending": 12,
      "this_month": 45
    },
    "schedule_management": {}
  }
}
```

#### 4.1.3 Dokter Mobile Dashboard
```json
GET /api/v2/dashboards/dokter
{
  "success": true,
  "data": {
    "today_schedule": {
      "shifts": [],
      "patients": 12,
      "procedures": 8
    },
    "jaspel": {
      "this_month": 8500000,
      "pending": 2100000,
      "approved": 6400000
    },
    "attendance": {
      "status": "checked_in",
      "time_in": "08:15:00"
    }
  }
}
```

### 4.2 Offline-First Capabilities

```php
// Sync mechanism for offline data
class OfflineSyncController extends Controller
{
    public function syncData(Request $request)
    {
        $lastSync = $request->input('last_sync');
        $updates = $this->getUpdatedData($lastSync);
        
        return response()->json([
            'success' => true,
            'data' => [
                'attendance' => $updates['attendance'],
                'schedules' => $updates['schedules'],
                'notifications' => $updates['notifications'],
                'sync_timestamp' => now()->toISOString()
            ]
        ]);
    }
    
    public function uploadOfflineData(Request $request)
    {
        $offlineData = $request->input('offline_data');
        $results = [];
        
        foreach ($offlineData as $item) {
            $results[] = $this->processOfflineItem($item);
        }
        
        return response()->json([
            'success' => true,
            'data' => ['processed' => $results]
        ]);
    }
}
```

### 4.3 Push Notification Integration

```php
// Firebase Cloud Messaging integration
class PushNotificationService
{
    public function sendToUser($userId, $notification)
    {
        $devices = UserDevice::where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->where('status', 'active')
            ->get();
            
        foreach ($devices as $device) {
            $this->sendFCMNotification($device->fcm_token, $notification);
        }
    }
    
    public function sendRoleBasedNotification($role, $notification)
    {
        $users = User::whereHas('role', function($q) use ($role) {
            $q->where('name', $role);
        })->get();
        
        foreach ($users as $user) {
            $this->sendToUser($user->id, $notification);
        }
    }
}
```

## 5. Database & Performance Optimization

### 5.1 API-Specific Database Optimizations

```sql
-- Enhanced indexes for API performance
CREATE INDEX idx_users_role_active ON users (role_id, is_active);
CREATE INDEX idx_attendance_user_date ON attendances (user_id, date);
CREATE INDEX idx_attendance_date_status ON attendances (date, status);
CREATE INDEX idx_jaspel_user_month ON jaspels (user_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications (user_id, read_at);

-- API-specific tables
CREATE TABLE api_request_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_time TIMESTAMP,
    response_time_ms INT,
    status_code INT,
    INDEX idx_user_endpoint (user_id, endpoint),
    INDEX idx_request_time (request_time)
);

CREATE TABLE api_rate_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    endpoint VARCHAR(255),
    requests_count INT DEFAULT 0,
    window_start TIMESTAMP,
    UNIQUE KEY unique_user_endpoint_window (user_id, endpoint, window_start)
);
```

### 5.2 Caching Strategies

```php
// Redis caching for API responses
class ApiCacheService
{
    public function getDashboardData($userId, $role)
    {
        $cacheKey = "dashboard:{$role}:{$userId}";
        
        return Cache::remember($cacheKey, 300, function() use ($userId, $role) {
            return $this->generateDashboardData($userId, $role);
        });
    }
    
    public function invalidateUserCache($userId)
    {
        $user = User::find($userId);
        $role = $user->role->name;
        
        Cache::forget("dashboard:{$role}:{$userId}");
        Cache::forget("attendance:today:{$userId}");
        Cache::forget("jaspel:monthly:{$userId}");
    }
}

// Cache configuration
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
    'api_cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => 2,
    ],
],
```

### 5.3 Query Optimization

```php
// Optimized queries with eager loading
class OptimizedAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->with(['user:id,name', 'workLocation:id,name'])
            ->select(['id', 'user_id', 'date', 'time_in', 'time_out', 'status', 'work_location_id'])
            ->orderBy('date', 'desc')
            ->simplePaginate(15);
            
        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances),
            'meta' => [
                'has_more' => $attendances->hasMorePages(),
                'next_page_url' => $attendances->nextPageUrl()
            ]
        ]);
    }
}
```

## 6. Non-Paramedis Mobile Integration

### 6.1 Mobile-Optimized Blade Views

```php
// routes/web.php - Non-Paramedis mobile routes
Route::middleware(['auth', 'role:non_paramedis'])->prefix('mobile/non-paramedis')->group(function () {
    Route::get('/dashboard', [NonParamedisMobileController::class, 'dashboard'])->name('mobile.non-paramedis.dashboard');
    Route::get('/attendance', [NonParamedisMobileController::class, 'attendance'])->name('mobile.non-paramedis.attendance');
    Route::get('/schedule', [NonParamedisMobileController::class, 'schedule'])->name('mobile.non-paramedis.schedule');
    Route::get('/jaspel', [NonParamedisMobileController::class, 'jaspel'])->name('mobile.non-paramedis.jaspel');
});
```

### 6.2 Mobile-Responsive Components

```blade
{{-- resources/views/mobile/non-paramedis/dashboard.blade.php --}}
@extends('layouts.mobile-app')

@section('title', 'Dashboard Non-Paramedis')

@section('content')
<div class="mobile-dashboard" x-data="nonParamedisDashboard()">
    <!-- Mobile-optimized dashboard content -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <h3>Kehadiran Bulan Ini</h3>
            <p class="stat-number" x-text="stats.attendance.thisMonth"></p>
        </div>
        
        <div class="stat-card">
            <h3>Jaspel Pending</h3>
            <p class="stat-number" x-text="formatCurrency(stats.jaspel.pending)"></p>
        </div>
    </div>
    
    <!-- Quick actions -->
    <div class="quick-actions">
        <button @click="quickCheckin()" class="action-btn primary">
            Check In
        </button>
        <button @click="quickCheckout()" class="action-btn secondary">
            Check Out
        </button>
    </div>
</div>

<script>
function nonParamedisDashboard() {
    return {
        stats: {
            attendance: { thisMonth: 0 },
            jaspel: { pending: 0 }
        },
        
        init() {
            this.loadDashboardData();
        },
        
        async loadDashboardData() {
            try {
                const response = await fetch('/api/v2/dashboards/non-paramedis');
                const data = await response.json();
                this.stats = data.data;
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        },
        
        async quickCheckin() {
            // GPS-based check-in logic
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }
    }
}
</script>
@endsection
```

### 6.3 Livewire Integration

```php
// app/Livewire/NonParamedis/AttendanceWidget.php
class AttendanceWidget extends Component
{
    public $todayAttendance;
    public $canCheckIn = false;
    public $canCheckOut = false;
    
    public function mount()
    {
        $this->loadTodayAttendance();
    }
    
    public function loadTodayAttendance()
    {
        $this->todayAttendance = NonParamedisAttendance::where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();
            
        $this->canCheckIn = !$this->todayAttendance;
        $this->canCheckOut = $this->todayAttendance && !$this->todayAttendance->time_out;
    }
    
    public function quickCheckIn()
    {
        if ($this->canCheckIn) {
            NonParamedisAttendance::create([
                'user_id' => auth()->id(),
                'date' => today(),
                'time_in' => now(),
                'type' => 'quick_checkin'
            ]);
            
            $this->loadTodayAttendance();
            $this->dispatch('attendance-updated');
        }
    }
    
    public function render()
    {
        return view('livewire.non-paramedis.attendance-widget');
    }
}
```

## 7. Security & Performance Plan

### 7.1 Rate Limiting Strategy

```php
// config/api.php - Rate limiting configuration
'rate_limits' => [
    'authentication' => [
        'attempts' => 5,
        'decay_minutes' => 15,
    ],
    'general_api' => [
        'requests' => 100,
        'per_minutes' => 1,
    ],
    'attendance' => [
        'requests' => 10,
        'per_minutes' => 1,
    ],
    'file_upload' => [
        'requests' => 20,
        'per_minutes' => 5,
    ],
],

// Middleware implementation
class ApiRateLimitMiddleware
{
    public function handle($request, Closure $next, $limitType = 'general_api')
    {
        $limits = config("api.rate_limits.{$limitType}");
        $key = $this->resolveRequestSignature($request, $limitType);
        
        if (RateLimiter::tooManyAttempts($key, $limits['requests'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }
        
        RateLimiter::hit($key, $limits['per_minutes'] * 60);
        
        return $next($request);
    }
}
```

### 7.2 Security Measures

```php
// Enhanced security middleware stack
'api_v2' => [
    'throttle:api',
    'auth:sanctum',
    'device.binding',
    'gps.spoofing.detection',
    'request.logging',
    'response.encryption', // For sensitive data
],

// GPS spoofing detection
class GpsSpoofingDetectionMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($this->hasGpsData($request)) {
            $suspiciousActivity = $this->detectSpoofing($request);
            
            if ($suspiciousActivity) {
                GpsSpoofingDetection::create([
                    'user_id' => $request->user()->id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy' => $request->accuracy,
                    'detection_type' => $suspiciousActivity['type'],
                    'confidence_score' => $suspiciousActivity['confidence'],
                    'raw_data' => $request->all(),
                ]);
                
                if ($suspiciousActivity['confidence'] > 0.8) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GPS manipulation detected',
                        'error_code' => 'GPS_SPOOFING_DETECTED'
                    ], 403);
                }
            }
        }
        
        return $next($request);
    }
}
```

### 7.3 Performance Monitoring

```php
// API performance monitoring
class ApiPerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log slow requests
        if ($responseTime > 1000) { // > 1 second
            Log::warning('Slow API request', [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'response_time' => $responseTime,
                'user_id' => $request->user()?->id,
            ]);
        }
        
        // Store metrics
        ApiRequestLog::create([
            'user_id' => $request->user()?->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_time' => now(),
            'response_time_ms' => $responseTime,
            'status_code' => $response->getStatusCode(),
        ]);
        
        return $response;
    }
}
```

## 8. Documentation Strategy

### 8.1 OpenAPI/Swagger Implementation

```php
// config/l5-swagger.php - Swagger configuration
'default' => 'v2',
'documentations' => [
    'v2' => [
        'api' => [
            'title' => 'Dokterku API v2',
            'version' => '2.0.0',
            'description' => 'Medical Clinic Management System API v2',
        ],
        'routes' => [
            'api' => '/api/v2/documentation',
        ],
        'paths' => [
            'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
            'docs_json' => 'api-v2-docs.json',
            'docs_yaml' => 'api-v2-docs.yaml',
            'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
            'annotations' => [
                base_path('app/Http/Controllers/Api/V2'),
            ],
        ],
    ],
],
```

### 8.2 API Documentation Examples

```php
/**
 * @OA\Post(
 *     path="/api/v2/attendance/checkin",
 *     summary="GPS-based attendance check-in",
 *     tags={"Attendance"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"latitude", "longitude"},
 *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
 *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
 *             @OA\Property(property="accuracy", type="number", format="float", example=10.5),
 *             @OA\Property(property="face_image", type="string", format="base64", description="Base64 encoded face image"),
 *             @OA\Property(property="location_name", type="string", example="Klinik Utama"),
 *             @OA\Property(property="notes", type="string", example="Check-in normal")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Check-in successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Check-in berhasil"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="attendance_id", type="integer", example=123),
 *                 @OA\Property(property="time_in", type="string", format="time", example="08:15:30"),
 *                 @OA\Property(property="status", type="string", example="present"),
 *                 @OA\Property(
 *                     property="coordinates",
 *                     type="object",
 *                     @OA\Property(property="latitude", type="number", format="float"),
 *                     @OA\Property(property="longitude", type="number", format="float")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="Already checked in or validation error"),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation errors")
 * )
 */
public function checkin(CheckinRequest $request): JsonResponse
{
    // Implementation
}
```

## 9. Implementation Timeline

### Phase 1: Foundation (Week 1-2)
- [ ] Create API v2 folder structure
- [ ] Implement base controller classes
- [ ] Set up authentication system
- [ ] Create standardized response format
- [ ] Implement basic middleware stack

### Phase 2: Core Features (Week 3-4)
- [ ] Migrate attendance system to v2
- [ ] Implement universal dashboard endpoints
- [ ] Add face recognition v2 endpoints
- [ ] Create user management APIs
- [ ] Implement basic caching

### Phase 3: Role-Specific Features (Week 5-6)
- [ ] Create role-specific dashboard APIs
- [ ] Implement jaspel management APIs
- [ ] Add scheduling system APIs
- [ ] Create notification system
- [ ] Implement file management APIs

### Phase 4: Mobile Optimization (Week 7-8)
- [ ] Create mobile-optimized endpoints
- [ ] Implement offline sync capabilities
- [ ] Add push notification support
- [ ] Create Non-Paramedis mobile views
- [ ] Optimize for mobile performance

### Phase 5: Security & Performance (Week 9-10)
- [ ] Implement advanced rate limiting
- [ ] Add GPS spoofing detection
- [ ] Create performance monitoring
- [ ] Implement advanced caching
- [ ] Add security logging

### Phase 6: Documentation & Testing (Week 11-12)
- [ ] Complete OpenAPI documentation
- [ ] Create API testing suite
- [ ] Implement automated tests
- [ ] Performance testing
- [ ] Security testing

## 10. Testing Strategy

### 10.1 API Testing Approach

```php
// tests/Feature/Api/V2/AttendanceTest.php
class AttendanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_checkin_with_valid_gps()
    {
        $user = User::factory()->create(['role_id' => Role::where('name', 'paramedis')->first()->id]);
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/v2/attendance/checkin', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10.5,
            'location_name' => 'Test Location'
        ]);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'attendance_id',
                        'time_in',
                        'status',
                        'coordinates'
                    ],
                    'meta' => [
                        'version',
                        'timestamp',
                        'request_id'
                    ]
                ]);
                
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => today(),
            'status' => 'present'
        ]);
    }
    
    public function test_user_cannot_checkin_twice_same_day()
    {
        $user = User::factory()->create(['role_id' => Role::where('name', 'paramedis')->first()->id]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today()
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/v2/attendance/checkin', [
            'latitude' => -6.2088,
            'longitude' => 106.8456
        ]);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in hari ini'
                ]);
    }
}
```

### 10.2 Performance Testing

```php
// tests/Performance/ApiPerformanceTest.php
class ApiPerformanceTest extends TestCase
{
    public function test_dashboard_endpoint_performance()
    {
        $user = User::factory()->create(['role_id' => Role::where('name', 'paramedis')->first()->id]);
        Sanctum::actingAs($user);
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/v2/dashboards/paramedis');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'Dashboard endpoint should respond within 500ms');
    }
    
    public function test_concurrent_requests_handling()
    {
        $users = User::factory()->count(10)->create();
        $responses = [];
        
        foreach ($users as $user) {
            Sanctum::actingAs($user);
            $responses[] = $this->getJson('/api/v2/attendance/today');
        }
        
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }
}
```

## 11. Backward Compatibility Plan

### 11.1 v1 Deprecation Timeline

**Phase 1 (Months 1-3): Parallel Operation**
- Both v1 and v2 APIs operational
- v2 API feature-complete
- Gradual migration of mobile apps to v2
- v1 marked as deprecated in documentation

**Phase 2 (Months 4-6): Migration Period**
- Active migration of clients to v2
- v1 API receives only critical bug fixes
- Notification system alerts v1 users to migrate
- Performance monitoring of both versions

**Phase 3 (Months 7-12): Sunset Phase**
- v1 API marked for retirement
- v1 endpoints return deprecation warnings
- Support only for critical security fixes
- Client migration enforcement

**Phase 4 (Month 12+): v1 Retirement**
- v1 API completely disabled
- All traffic redirected to v2
- Legacy code cleanup
- Full v2 optimization

### 11.2 Migration Tools

```php
// Migration helper for API consumers
class ApiMigrationHelper
{
    public function checkCompatibility($v1Request)
    {
        $v2Endpoint = $this->mapV1ToV2Endpoint($v1Request->path());
        $v2Parameters = $this->mapV1ToV2Parameters($v1Request->all());
        
        return [
            'v2_endpoint' => $v2Endpoint,
            'v2_parameters' => $v2Parameters,
            'breaking_changes' => $this->getBreakingChanges($v1Request->path()),
            'migration_notes' => $this->getMigrationNotes($v1Request->path())
        ];
    }
    
    private function mapV1ToV2Endpoint($v1Path)
    {
        $mapping = [
            '/api/user' => '/api/v2/auth/me',
            '/api/attendance/checkin' => '/api/v2/attendance/checkin',
            '/api/attendance/checkout' => '/api/v2/attendance/checkout',
            '/api/paramedis/dashboard' => '/api/v2/dashboards/paramedis',
        ];
        
        return $mapping[$v1Path] ?? null;
    }
}
```

## 12. Conclusion

This comprehensive refactoring plan transforms the Dokterku API from a basic Paramedis-focused system to an enterprise-grade, multi-role API supporting all clinic operations. The plan ensures:

1. **Scalability**: Modular architecture supports future expansion
2. **Performance**: Optimized queries, caching, and mobile-first design
3. **Security**: Enhanced authentication, device binding, and GPS validation
4. **Maintainability**: Clean code structure, comprehensive testing, and documentation
5. **User Experience**: Role-specific mobile optimization and offline capabilities

The phased implementation approach minimizes disruption while delivering immediate value to clinic operations. The backward compatibility strategy ensures smooth migration from v1 to v2 over a 12-month period.

**Key Success Metrics:**
- API response time < 500ms for 95% of requests
- 99.9% uptime for critical endpoints
- Support for 500+ concurrent users
- Mobile app performance improvement of 40%
- Reduced development time for new features by 60%

This plan positions Dokterku as a modern, scalable medical clinic management system capable of supporting growth and technological advancement.