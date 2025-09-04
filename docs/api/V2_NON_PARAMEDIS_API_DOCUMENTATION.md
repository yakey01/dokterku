# Dokterku API v2 - NonParamedis Endpoints Documentation

## Overview

The NonParamedis API v2 provides comprehensive mobile-first endpoints for non-medical administrative staff attendance management, dashboards, and reporting within the Dokterku clinic management system.

### Base URL
```
/api/v2/dashboards/nonparamedis
```

### Authentication
All endpoints require Bearer token authentication using Laravel Sanctum.

```http
Authorization: Bearer {your_token_here}
```

### Response Format
All responses follow the standardized v2 API format:

```json
{
  "status": "success|error",
  "message": "Human readable message",
  "data": {}, // Response data
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "request_id": "uuid-string"
  }
}
```

## Authentication & Authorization

### Role Requirements
- User must have role: `non_paramedis`
- User must be active (`is_active = true`)
- Valid Bearer token required

### Middleware Applied
- `auth:sanctum` - Sanctum authentication
- `enhanced.role:non_paramedis` - Role-based access control
- `ApiResponseHeadersMiddleware` - Standardized headers
- `ApiRateLimitMiddleware:attendance` - Rate limiting for attendance actions

## Endpoints

### 1. Dashboard Overview

**GET** `/api/v2/dashboards/nonparamedis/`

Get comprehensive dashboard data including stats, current status, and quick actions.

#### Response Data
```json
{
  "status": "success",
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Sari Lestari",
      "initials": "SL",
      "role": "Admin Non-Medis"
    },
    "stats": {
      "hours_today": 8,
      "minutes_today": 30,
      "work_days_this_month": 20,
      "total_work_hours_this_month": 168.5,
      "attendance_rate": 95,
      "shifts_this_week": 5,
      "expected_work_days": 22
    },
    "current_status": "checked_in|checked_out|not_checked_in",
    "today_attendance": {
      "check_in_time": "08:15",
      "check_out_time": "17:30",
      "work_duration": "8 jam 30 menit",
      "status": "checked_out",
      "approval_status": "approved"
    },
    "quick_actions": [
      {
        "id": "attendance",
        "title": "Check In",
        "subtitle": "Belum check-in hari ini",
        "icon": "🕐",
        "action": "attendance",
        "enabled": true
      },
      {
        "id": "schedule",
        "title": "Jadwal Kerja",
        "subtitle": "5 hari minggu ini",
        "icon": "📅",
        "action": "schedule",
        "enabled": true
      },
      {
        "id": "reports",
        "title": "Laporan Kehadiran",
        "subtitle": "Rate: 95%",
        "icon": "📊",
        "action": "reports",
        "enabled": true
      }
    ]
  }
}
```

### 2. Attendance Status

**GET** `/api/v2/dashboards/nonparamedis/attendance/status`

Get current attendance status and location information.

#### Response Data
```json
{
  "status": "success",
  "message": "Attendance status retrieved",
  "data": {
    "status": "checked_in|checked_out|not_checked_in",
    "check_in_time": "08:15:30",
    "check_out_time": "17:30:00",
    "work_duration": "8 jam 30 menit",
    "location": {
      "id": 1,
      "name": "Klinik Dokterku Pusat",
      "address": "Jl. Kesehatan No. 123",
      "radius": 100,
      "coordinates": {
        "latitude": -6.2088,
        "longitude": 106.8456
      }
    },
    "can_check_in": true,
    "can_check_out": false
  }
}
```

### 3. Check In

**POST** `/api/v2/dashboards/nonparamedis/attendance/checkin`

Record attendance check-in with GPS validation.

#### Request Body
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "work_location_id": 1
}
```

#### Parameters
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `latitude` | float | Yes | GPS latitude (-90 to 90) |
| `longitude` | float | Yes | GPS longitude (-180 to 180) |
| `accuracy` | float | No | GPS accuracy in meters |
| `work_location_id` | integer | No | Specific work location ID |

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Check-in berhasil!",
  "data": {
    "attendance_id": 123,
    "check_in_time": "08:15:30",
    "status": "checked_in",
    "location": {
      "id": 1,
      "name": "Klinik Dokterku Pusat",
      "distance": 45.2
    },
    "distance": 45.2
  }
}
```

#### Error Responses

**422 - Validation Error**
```json
{
  "status": "error",
  "message": "Invalid GPS data",
  "errors": {
    "latitude": ["The latitude field is required."]
  }
}
```

**422 - Already Checked In**
```json
{
  "status": "error",
  "message": "Anda sudah melakukan check-in hari ini"
}
```

**422 - Location Validation Failed**
```json
{
  "status": "error",
  "message": "Lokasi Anda berada di luar area kerja yang diizinkan",
  "errors": {
    "gps_validation": {
      "is_valid": false,
      "distance": 150.5,
      "max_distance": 100
    },
    "distance": 150.5,
    "gps_quality": "good"
  }
}
```

### 4. Check Out

**POST** `/api/v2/dashboards/nonparamedis/attendance/checkout`

Record attendance check-out with work duration calculation.

#### Request Body
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 8.0
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Check-out berhasil!",
  "data": {
    "attendance_id": 123,
    "check_out_time": "17:30:00",
    "work_duration_hours": 8.5,
    "work_duration_formatted": "8 jam 30 menit",
    "status": "checked_out",
    "location_valid": true,
    "distance": 35.8
  }
}
```

#### Error Responses

**422 - No Check-in Found**
```json
{
  "status": "error",
  "message": "Tidak ditemukan data check-in hari ini"
}
```

### 5. Today's History

**GET** `/api/v2/dashboards/nonparamedis/attendance/today-history`

Get today's attendance activity history.

#### Response Data
```json
{
  "status": "success",
  "message": "Today history retrieved",
  "data": {
    "history": [
      {
        "action": "Check-in",
        "time": "08:15",
        "subtitle": "Hari ini • Klinik Dokterku Pusat",
        "location_valid": true,
        "distance": 45.2
      },
      {
        "action": "Check-out",
        "time": "17:30",
        "subtitle": "Hari ini • 8 jam 30 menit",
        "location_valid": true,
        "distance": 35.8
      }
    ],
    "has_activity": true,
    "attendance_summary": {
      "total_work_time": "8 jam 30 menit",
      "status": "checked_out",
      "approval_status": "pending"
    }
  }
}
```

### 6. Schedule

**GET** `/api/v2/dashboards/nonparamedis/schedule`

Get work schedule for current month and week.

#### Query Parameters
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `month` | integer | Month (1-12) | Current month |
| `year` | integer | Year | Current year |

#### Response Data
```json
{
  "status": "success",
  "message": "Schedule retrieved successfully",
  "data": {
    "month": {
      "name": "Juli 2025",
      "total_days": 22,
      "work_days": 20,
      "calendar": [
        {
          "date": "2025-07-01",
          "status": "checked_out",
          "approval_status": "approved",
          "work_duration": "8 jam 15 menit",
          "location": "Klinik Dokterku Pusat"
        }
      ]
    },
    "weekly_shifts": [
      {
        "date": "2025-07-15",
        "day_name": "Senin",
        "check_in": "08:15",
        "check_out": "17:30",
        "duration": "8 jam 30 menit",
        "location": "Klinik Dokterku",
        "status": "checked_out",
        "approval_status": "approved"
      }
    ],
    "summary": {
      "total_work_hours": 168.5,
      "average_daily_hours": 8.4
    }
  }
}
```

### 7. Reports

**GET** `/api/v2/dashboards/nonparamedis/reports`

Get comprehensive attendance reports and analytics.

#### Query Parameters
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `period` | string | `week`, `month`, `year` | `month` |
| `date` | string | Date in Y-m-d format | Current date |

#### Response Data
```json
{
  "status": "success",
  "message": "Reports retrieved successfully",
  "data": {
    "period": {
      "type": "month",
      "start_date": "2025-07-01",
      "end_date": "2025-07-31",
      "display_name": "Juli 2025"
    },
    "summary": {
      "total_scheduled_days": 22,
      "work_days_completed": 20,
      "expected_work_days": 23,
      "attendance_rate": 95.7,
      "total_work_hours": 168.5,
      "average_daily_hours": 8.4,
      "approval_summary": {
        "approved": 18,
        "pending": 2,
        "rejected": 0
      }
    },
    "recent_history": [
      {
        "date": "15 Jul 2025",
        "day": "Senin",
        "check_in": "08:15",
        "check_out": "17:30",
        "duration": "8 jam 30 menit",
        "location": "Klinik Dokterku Pusat",
        "status": "checked_out",
        "approval_status": "approved",
        "location_valid": true
      }
    ],
    "performance_indicators": {
      "punctuality_score": 92.5,
      "consistency_score": 88.0,
      "location_compliance": 100.0
    }
  }
}
```

### 8. Profile

**GET** `/api/v2/dashboards/nonparamedis/profile`

Get user profile information and settings.

#### Response Data
```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Sari Lestari",
      "initials": "SL",
      "email": "sari.lestari@dokterku.com",
      "username": "sari.lestari",
      "nip": "NP001",
      "phone": "081234567890",
      "role": "Administrator Non-Medis",
      "join_date": "01 Jan 2025",
      "is_verified": true,
      "status": "active"
    },
    "attendance_stats": {
      "total_this_month": 20,
      "approved_this_month": 18,
      "approval_rate": 90.0
    },
    "settings": {
      "notifications_enabled": true,
      "dark_mode": false,
      "language": "id",
      "auto_checkout": false,
      "gps_accuracy_required": true
    },
    "permissions": {
      "can_check_in": true,
      "can_check_out": true,
      "can_view_reports": true,
      "can_edit_profile": false
    }
  }
}
```

## Error Handling

### Common Error Codes

| Code | Description |
|------|-------------|
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Invalid role or permissions |
| 422 | Validation Error - Invalid request data |
| 429 | Rate Limit Exceeded |
| 500 | Internal Server Error |

### Standard Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "errors": {}, // Validation errors if applicable
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "request_id": "uuid-string"
  }
}
```

## Rate Limiting

Attendance endpoints are rate-limited to prevent abuse:
- Check-in/Check-out: 5 requests per minute
- Other endpoints: 60 requests per minute

## GPS Validation

### Location Requirements
- Must be within work location geofence (default: 100m radius)
- GPS accuracy should be ≤ 50 meters for optimal validation
- Spoofing detection is automatically applied

### GPS Quality Indicators
- **Excellent**: ≤ 5m accuracy
- **Good**: 5-20m accuracy  
- **Fair**: 20-50m accuracy
- **Poor**: > 50m accuracy

## Security Features

1. **Device Fingerprinting**: Tracks device information
2. **IP Logging**: Records request IP addresses
3. **GPS Spoofing Detection**: Validates GPS authenticity
4. **Rate Limiting**: Prevents API abuse
5. **Role-based Access**: Strict permission enforcement

## Data Models

### NonParamedisAttendance Model
```
- id: integer
- user_id: foreign key to users
- work_location_id: foreign key to work_locations
- attendance_date: date
- check_in_time: timestamp
- check_in_latitude/longitude: decimal
- check_in_accuracy: decimal
- check_in_distance: decimal
- check_in_valid_location: boolean
- check_out_time: timestamp
- check_out_latitude/longitude: decimal
- check_out_accuracy: decimal  
- check_out_distance: decimal
- check_out_valid_location: boolean
- total_work_minutes: integer
- status: enum (checked_in, checked_out, incomplete)
- approval_status: enum (pending, approved, rejected)
- approved_by: foreign key to users
- approved_at: timestamp
- approval_notes: text
- device_info: json
- gps_metadata: json
- suspected_spoofing: boolean
- notes: text
```

## Testing Endpoints

Use the test endpoint to verify API connectivity:

**GET** `/api/v2/dashboards/nonparamedis/test`

```json
{
  "success": true,
  "message": "API endpoint is working - Authentication verified",
  "data": {
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "user": {
      "id": 1,
      "name": "Test User",
      "role": "non_paramedis",
      "authenticated": true,
      "role_validated": true
    },
    "session": {
      "token_name": "mobile-device",
      "ip_address": "192.168.1.100",
      "user_agent": "PostmanRuntime/7.32.3"
    }
  }
}
```

---

*Generated on 2025-07-15 | API v2.0 | Dokterku Medical Clinic Management System*