# API Reference Documentation - Dokterku System

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Autentikasi](#autentikasi)
3. [Struktur Response](#struktur-response)
4. [Rate Limiting](#rate-limiting)
5. [Public Endpoints](#public-endpoints)
6. [Authentication Endpoints](#authentication-endpoints)
7. [Dashboard Endpoints](#dashboard-endpoints)
8. [Attendance Endpoints](#attendance-endpoints)
9. [Bulk Operations](#bulk-operations)
10. [Notification Endpoints](#notification-endpoints)
11. [Admin Panel Endpoints](#admin-panel-endpoints)
12. [Face Recognition Endpoints](#face-recognition-endpoints)
13. [Error Handling](#error-handling)
14. [Offline Support](#offline-support)

---

## Pendahuluan

### Base URL
```
Production: https://dokterku.com/api
Development: http://localhost:8000/api
```

### API Versioning
- **v1** (Legacy): `/api/`
- **v2** (Current): `/api/v2/`

### Content Type
```
Content-Type: application/json
Accept: application/json
```

### Supported HTTP Methods
- `GET` - Retrieve data
- `POST` - Create new resource
- `PUT` - Update existing resource (full replacement)
- `PATCH` - Partial update
- `DELETE` - Remove resource

---

## Autentikasi

### Laravel Sanctum Token-based Authentication

#### **Mendapatkan Token**
```http
POST /api/v2/auth/login
Content-Type: application/json

{
  "email": "petugas@dokterku.com",
  "password": "petugas123",
  "device_name": "iPhone 12 Pro"
}
```

#### **Response Login**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Staff Petugas",
      "email": "petugas@dokterku.com",
      "role": "petugas"
    },
    "token": "sanctum-token-here",
    "expires_at": "2025-08-15T10:30:00.000000Z"
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "request_id": "uuid-here"
  }
}
```

#### **Menggunakan Token**
```http
Authorization: Bearer {token}
```

### Biometric Authentication (Advanced)
```http
POST /api/v2/auth/biometric/setup
Authorization: Bearer {token}

{
  "biometric_type": "fingerprint|face_id|voice",
  "biometric_data": "base64_encoded_data",
  "device_id": "unique_device_identifier"
}
```

---

## Struktur Response

### Standard API Response Format (v2)
```json
{
  "success": true|false,
  "message": "Human readable message",
  "data": {
    // Response data object or array
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "request_id": "uuid-v4-here",
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  },
  "errors": [
    // Error details (only present on failure)
  ]
}
```

### Pagination Response
```json
{
  "data": [...],
  "links": {
    "first": "https://api.example.com/endpoint?page=1",
    "last": "https://api.example.com/endpoint?page=5",
    "prev": null,
    "next": "https://api.example.com/endpoint?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

---

## Rate Limiting

### Rate Limits by Endpoint Type

| Endpoint Type | Limit | Window |
|---------------|--------|---------|
| **Authentication** | 5 requests | per minute |
| **Attendance** | 10 requests | per minute |
| **Face Recognition** | 3 requests | per minute |
| **Bulk Operations** | 2 requests | per minute |
| **General API** | 60 requests | per minute |

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642780800
Retry-After: 60
```

---

## Public Endpoints

### System Health & Information

#### **API Health Check**
```http
GET /api/v2/system/health
```

**Response:**
```json
{
  "success": true,
  "message": "API is healthy",
  "data": {
    "status": "ok",
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "database": "connected"
  }
}
```

#### **API Version Information**
```http
GET /api/v2/system/version
```

**Response:**
```json
{
  "success": true,
  "data": {
    "api_version": "2.0",
    "laravel_version": "11.x.x",
    "release_date": "2025-07-15",
    "features": {
      "authentication": "✓",
      "attendance": "✓",
      "dashboards": "✓",
      "role_based_access": "✓",
      "mobile_optimization": "✓",
      "offline_sync": "pending",
      "push_notifications": "pending"
    }
  }
}
```

#### **Work Locations (GPS Reference)**
```http
GET /api/v2/locations/work-locations
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Klinik Utama Dokterku",
      "latitude": -6.175392,
      "longitude": 106.827153,
      "radius_meters": 100,
      "location_type": "main_office",
      "address": "Jl. Sudirman No. 1, Jakarta"
    }
  ]
}
```

---

## Authentication Endpoints

### **Login**
```http
POST /api/v2/auth/login

{
  "email": "user@dokterku.com",
  "password": "password123",
  "device_name": "iPhone 12 Pro",
  "remember_me": true
}
```

### **Logout**
```http
POST /api/v2/auth/logout
Authorization: Bearer {token}
```

### **Logout All Devices**
```http
POST /api/v2/auth/logout-all
Authorization: Bearer {token}
```

### **Get User Info**
```http
GET /api/v2/auth/me
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Staff Petugas",
    "email": "petugas@dokterku.com",
    "role": {
      "id": 2,
      "name": "petugas",
      "display_name": "Staff Petugas"
    },
    "is_active": true,
    "last_login": "2025-07-15T10:30:00.000000Z",
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### **Change Password**
```http
POST /api/v2/auth/change-password
Authorization: Bearer {token}

{
  "current_password": "old_password",
  "new_password": "new_password123",
  "new_password_confirmation": "new_password123"
}
```

### **Update Profile**
```http
PUT /api/v2/auth/profile
Authorization: Bearer {token}

{
  "name": "Updated Name",
  "email": "newemail@dokterku.com",
  "phone": "+6281234567890"
}
```

### **Session Management**
```http
GET /api/v2/auth/sessions
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "session_id_here",
      "device_name": "iPhone 12 Pro",
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "last_activity": "2025-07-15T10:30:00.000000Z",
      "current": true
    }
  ]
}
```

### **End Specific Session**
```http
DELETE /api/v2/auth/sessions/{session_id}
Authorization: Bearer {token}
```

---

## Dashboard Endpoints

### **Paramedis Dashboard**

#### **Main Dashboard**
```http
GET /api/v2/dashboards/paramedis/
Authorization: Bearer {token}
Role: paramedis
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "name": "Perawat Sari",
      "role": "Paramedis",
      "initials": "PS"
    },
    "stats": {
      "jaspel_monthly": 15200000,
      "jaspel_weekly": 3800000,
      "approved_jaspel": 12800000,
      "pending_jaspel": 2400000,
      "minutes_worked": 720,
      "shifts_this_month": 22
    },
    "today_attendance": {
      "checked_in": true,
      "check_in_time": "08:00",
      "work_duration": "4h 30m"
    }
  }
}
```

#### **Schedule Information**
```http
GET /api/v2/dashboards/paramedis/schedule
Authorization: Bearer {token}
```

#### **Performance Metrics**
```http
GET /api/v2/dashboards/paramedis/performance
Authorization: Bearer {token}
```

### **Dokter Dashboard**

#### **Main Dashboard**
```http
GET /api/v2/dashboards/dokter/
Authorization: Bearer {token}
Role: dokter
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Dr. Ahmad",
      "initials": "DA",
      "role": "Dokter Umum"
    },
    "stats": {
      "patients_today": 12,
      "tindakan_today": 8,
      "jaspel_month": 25000000,
      "shifts_week": 5
    },
    "current_status": "active",
    "quick_actions": [
      {
        "id": "presensi",
        "title": "Presensi",
        "subtitle": "Kelola kehadiran dan absensi",
        "icon": "📋",
        "action": "presensi",
        "enabled": true
      }
    ]
  }
}
```

#### **Attendance Status**
```http
GET /api/v2/dashboards/dokter/attendance/status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "checked_in|checked_out|not_checked_in",
    "check_in_time": "08:30",
    "check_out_time": null,
    "work_duration": "4h 15m"
  }
}
```

#### **Today's Attendance History**
```http
GET /api/v2/dashboards/dokter/attendance/today-history
Authorization: Bearer {token}
```

### **Non-Paramedis Dashboard**

#### **Main Dashboard**
```http
GET /api/v2/dashboards/nonparamedis/
Authorization: Bearer {token}
Role: non_paramedis
```

#### **Attendance Actions**
```http
POST /api/v2/dashboards/nonparamedis/attendance/checkin
Authorization: Bearer {token}

{
  "latitude": -6.175392,
  "longitude": 106.827153,
  "accuracy": 10.5,
  "work_location_id": 1
}
```

```http
POST /api/v2/dashboards/nonparamedis/attendance/checkout
Authorization: Bearer {token}

{
  "latitude": -6.175392,
  "longitude": 106.827153,
  "accuracy": 8.2
}
```

---

## Attendance Endpoints

### **Check In**
```http
POST /api/v2/attendance/checkin
Authorization: Bearer {token}

{
  "latitude": -6.175392,
  "longitude": 106.827153,
  "accuracy": 10.5,
  "work_location_id": 1,
  "device_info": {
    "device_id": "unique_device_id",
    "device_type": "ios|android|web",
    "app_version": "1.0.0"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Check-in successful",
  "data": {
    "id": 123,
    "date": "2025-07-15",
    "time_in": "08:30:00",
    "location": {
      "name": "Klinik Utama",
      "distance_meters": 5.2
    },
    "status": "checked_in"
  }
}
```

### **Check Out**
```http
POST /api/v2/attendance/checkout
Authorization: Bearer {token}

{
  "latitude": -6.175392,
  "longitude": 106.827153,
  "accuracy": 8.2
}
```

### **Today's Attendance**
```http
GET /api/v2/attendance/today
Authorization: Bearer {token}
```

### **Attendance History**
```http
GET /api/v2/attendance/history?page=1&per_page=15&from=2025-07-01&to=2025-07-15
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "date": "2025-07-15",
      "time_in": "08:30:00",
      "time_out": "17:00:00",
      "work_duration": "8h 30m",
      "status": "completed",
      "location": "Klinik Utama",
      "overtime_minutes": 30
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45
    }
  }
}
```

### **Attendance Statistics**
```http
GET /api/v2/attendance/statistics?period=month&year=2025&month=7
Authorization: Bearer {token}
```

---

## Bulk Operations

### **Bulk Create**
```http
POST /api/v2/bulk/create
Authorization: Bearer {token}

{
  "model": "pasien",
  "data": [
    {
      "nama": "John Doe",
      "tanggal_lahir": "1990-05-15",
      "jenis_kelamin": "L",
      "alamat": "Jl. Merdeka No. 1"
    }
  ],
  "validate_only": false
}
```

### **Bulk Update**
```http
PUT /api/v2/bulk/update
Authorization: Bearer {token}

{
  "model": "pasien",
  "updates": [
    {
      "id": 1,
      "data": {
        "alamat": "Alamat Baru"
      }
    }
  ]
}
```

### **Bulk Import CSV**
```http
POST /api/v2/bulk/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "file": "csv_file",
  "model": "pasien",
  "mapping": {
    "nama": "column_1",
    "email": "column_2"
  },
  "validate_before_import": true
}
```

### **Bulk Validation**
```http
POST /api/v2/bulk/validate
Authorization: Bearer {token}

{
  "model": "tindakan",
  "ids": [1, 2, 3, 4, 5],
  "action": "approve|reject",
  "comment": "Batch validation comment"
}
```

### **Bulk Statistics**
```http
GET /api/v2/bulk/stats?model=pasien&period=month
Authorization: Bearer {token}
```

### **Supported Models**
```http
GET /api/v2/bulk/supported-models
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "model": "pasien",
      "display_name": "Pasien",
      "supports": ["create", "update", "delete", "import", "export"],
      "max_batch_size": 1000
    },
    {
      "model": "tindakan",
      "display_name": "Tindakan",
      "supports": ["create", "update", "validate"],
      "max_batch_size": 500
    }
  ]
}
```

---

## Notification Endpoints

### **Get All Notifications**
```http
GET /api/v2/notifications?page=1&per_page=20&type=all&status=unread
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "tindakan_approved",
      "title": "Tindakan Disetujui",
      "message": "Tindakan konsultasi untuk pasien John Doe telah disetujui",
      "data": {
        "tindakan_id": 123,
        "pasien_name": "John Doe"
      },
      "read_at": null,
      "created_at": "2025-07-15T10:30:00.000000Z",
      "priority": "medium"
    }
  ]
}
```

### **Unread Count**
```http
GET /api/v2/notifications/unread-count
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "unread_count": 5,
    "priority_counts": {
      "high": 1,
      "medium": 3,
      "low": 1
    }
  }
}
```

### **Recent Notifications**
```http
GET /api/v2/notifications/recent?limit=10
Authorization: Bearer {token}
```

### **Mark as Read**
```http
POST /api/v2/notifications/{notification_id}/mark-read
Authorization: Bearer {token}
```

### **Mark All as Read**
```http
POST /api/v2/notifications/mark-all-read
Authorization: Bearer {token}
```

### **Notification Settings**
```http
GET /api/v2/notifications/settings
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "email_notifications": true,
    "push_notifications": true,
    "notification_types": {
      "tindakan_approved": true,
      "tindakan_rejected": true,
      "validation_required": true,
      "system_maintenance": false
    },
    "quiet_hours": {
      "enabled": true,
      "start_time": "22:00",
      "end_time": "07:00"
    }
  }
}
```

### **Update Notification Settings**
```http
PUT /api/v2/notifications/settings
Authorization: Bearer {token}

{
  "email_notifications": false,
  "notification_types": {
    "tindakan_approved": true,
    "system_maintenance": false
  }
}
```

---

## Admin Panel Endpoints

### **Non-Paramedis Management**

#### **List Non-Paramedis Users**
```http
GET /api/v2/admin/nonparamedis?page=1&search=nama&status=active
Authorization: Bearer {token}
Role: admin
```

#### **Create Non-Paramedis User**
```http
POST /api/v2/admin/nonparamedis
Authorization: Bearer {token}

{
  "name": "Staff Baru",
  "email": "staff@dokterku.com",
  "password": "password123",
  "phone": "+6281234567890",
  "address": "Alamat lengkap",
  "is_active": true
}
```

#### **Update Non-Paramedis User**
```http
PUT /api/v2/admin/nonparamedis/{user_id}
Authorization: Bearer {token}

{
  "name": "Updated Name",
  "is_active": false
}
```

#### **Toggle User Status**
```http
PATCH /api/v2/admin/nonparamedis/{user_id}/toggle-status
Authorization: Bearer {token}
```

#### **Reset User Password**
```http
POST /api/v2/admin/nonparamedis/{user_id}/reset-password
Authorization: Bearer {token}

{
  "new_password": "newpassword123"
}
```

### **Attendance Approval**

#### **Pending Approvals**
```http
GET /api/v2/admin/attendance-approvals/pending
Authorization: Bearer {token}
```

#### **Approve Attendance**
```http
POST /api/v2/admin/attendance-approvals/{attendance_id}/approve
Authorization: Bearer {token}

{
  "comment": "Approved by admin"
}
```

#### **Reject Attendance**
```http
POST /api/v2/admin/attendance-approvals/{attendance_id}/reject
Authorization: Bearer {token}

{
  "reason": "Location mismatch",
  "comment": "GPS coordinates don't match work location"
}
```

#### **Bulk Approve**
```http
POST /api/v2/admin/attendance-approvals/bulk-approve
Authorization: Bearer {token}

{
  "attendance_ids": [1, 2, 3, 4, 5],
  "comment": "Bulk approval for regular shifts"
}
```

### **Reports**

#### **Attendance Summary Report**
```http
GET /api/v2/admin/reports/attendance-summary?from=2025-07-01&to=2025-07-15&user_id=1
Authorization: Bearer {token}
```

#### **Export Attendance CSV**
```http
POST /api/v2/admin/reports/export-csv
Authorization: Bearer {token}

{
  "from_date": "2025-07-01",
  "to_date": "2025-07-15",
  "user_ids": [1, 2, 3],
  "include_overtime": true
}
```

---

## Face Recognition Endpoints

### **Register Face**
```http
POST /api/v2/face-recognition/register
Authorization: Bearer {token}

{
  "face_image": "base64_encoded_image",
  "device_id": "unique_device_identifier"
}
```

### **Verify Face**
```http
POST /api/v2/face-recognition/verify
Authorization: Bearer {token}

{
  "face_image": "base64_encoded_image",
  "device_id": "unique_device_identifier"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "verified": true,
    "confidence": 0.95,
    "user_id": 1,
    "verification_time": "2025-07-15T10:30:00.000000Z"
  }
}
```

### **Face Recognition Status**
```http
GET /api/v2/face-recognition/status
Authorization: Bearer {token}
```

### **Update Face Data**
```http
PUT /api/v2/face-recognition/update
Authorization: Bearer {token}

{
  "face_image": "new_base64_encoded_image"
}
```

---

## Error Handling

### Standard Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-07-15T10:30:00.000000Z",
    "request_id": "uuid-here"
  }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| `200` | OK - Request successful |
| `201` | Created - Resource created |
| `400` | Bad Request - Invalid input |
| `401` | Unauthorized - Authentication required |
| `403` | Forbidden - Insufficient permissions |
| `404` | Not Found - Resource not found |
| `422` | Unprocessable Entity - Validation errors |
| `429` | Too Many Requests - Rate limit exceeded |
| `500` | Internal Server Error - Server error |

### Error Types and Handling

#### **Validation Errors (422)**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### **Authentication Errors (401)**
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": {
    "token": ["Token expired or invalid"]
  }
}
```

#### **Authorization Errors (403)**
```json
{
  "success": false,
  "message": "This action is unauthorized.",
  "errors": {
    "role": ["Insufficient permissions for this resource"]
  }
}
```

#### **Rate Limit Errors (429)**
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "errors": {
    "rate_limit": ["Exceeded 60 requests per minute limit"]
  },
  "retry_after": 60
}
```

---

## Offline Support

### **Get Offline Data Package**
```http
GET /api/v2/offline/data?types=locations,users,schedule
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "locations": [...],
    "users": {...},
    "schedule": [...],
    "cache_timestamp": "2025-07-15T10:30:00.000000Z",
    "expires_at": "2025-07-15T18:30:00.000000Z"
  }
}
```

### **Sync Offline Attendance**
```http
POST /api/v2/offline/sync-attendance
Authorization: Bearer {token}

{
  "offline_records": [
    {
      "local_id": "uuid-local",
      "action": "checkin",
      "timestamp": "2025-07-15T08:30:00.000000Z",
      "latitude": -6.175392,
      "longitude": 106.827153,
      "offline_created": true
    }
  ]
}
```

### **Get Offline Status**
```http
GET /api/v2/offline/status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "offline_enabled": true,
    "cache_status": "valid",
    "pending_sync_count": 3,
    "last_sync": "2025-07-15T09:00:00.000000Z"
  }
}
```

---

## Device Management

### **Register Device**
```http
POST /api/v2/devices/register
Authorization: Bearer {token}

{
  "device_id": "unique_device_identifier",
  "device_name": "iPhone 12 Pro",
  "device_type": "ios",
  "app_version": "1.0.0",
  "os_version": "15.0",
  "push_token": "fcm_token_here"
}
```

### **Get User Devices**
```http
GET /api/v2/devices/
Authorization: Bearer {token}
```

### **Revoke Device**
```http
DELETE /api/v2/devices/{device_id}
Authorization: Bearer {token}
```

---

## Testing Endpoints

### **API Test Endpoint**
```http
GET /api/v2/dashboards/dokter/test
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "API endpoint is working - Authentication verified",
  "data": {
    "user": {
      "id": 1,
      "name": "Dr. Ahmad",
      "role": "dokter",
      "authenticated": true,
      "role_validated": true
    },
    "session": {
      "token_name": "mobile_app_token",
      "ip_address": "192.168.1.100",
      "user_agent": "DokterApp/1.0"
    }
  }
}
```

---

## Integration Examples

### **Mobile App Authentication Flow**
```javascript
// Step 1: Login
const loginResponse = await fetch('/api/v2/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'user@dokterku.com',
    password: 'password123',
    device_name: 'iPhone 12 Pro'
  })
});

const { data } = await loginResponse.json();
const token = data.token;

// Step 2: Store token securely
localStorage.setItem('auth_token', token);

// Step 3: Use token for API calls
const userResponse = await fetch('/api/v2/auth/me', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### **Attendance Check-in with GPS**
```javascript
// Step 1: Get user location
navigator.geolocation.getCurrentPosition(async (position) => {
  const { latitude, longitude, accuracy } = position.coords;
  
  // Step 2: Submit check-in
  const response = await fetch('/api/v2/attendance/checkin', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      latitude,
      longitude,
      accuracy,
      work_location_id: 1,
      device_info: {
        device_id: getDeviceId(),
        device_type: 'web',
        app_version: '1.0.0'
      }
    })
  });
  
  const result = await response.json();
  if (result.success) {
    console.log('Check-in successful:', result.data);
  }
});
```

### **Bulk Import with Progress Tracking**
```javascript
// Step 1: Prepare CSV file
const formData = new FormData();
formData.append('file', csvFile);
formData.append('model', 'pasien');
formData.append('validate_before_import', 'true');

// Step 2: Upload and import
const response = await fetch('/api/v2/bulk/import', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const result = await response.json();
if (result.success) {
  console.log(`Imported ${result.data.success_count} records`);
  console.log(`Errors: ${result.data.error_count}`);
}
```

---

## Security Best Practices

### **API Security Guidelines**

1. **Always use HTTPS** in production
2. **Store tokens securely** - Use encrypted storage
3. **Implement token refresh** - Handle expired tokens
4. **Validate all inputs** - Client and server-side
5. **Use rate limiting** - Respect API limits
6. **Log security events** - Monitor for abuse

### **Token Management**
```javascript
// Check token expiration
const isTokenExpired = (token) => {
  const payload = JSON.parse(atob(token.split('.')[1]));
  return Date.now() >= payload.exp * 1000;
};

// Refresh token automatically
const refreshTokenIfNeeded = async () => {
  if (isTokenExpired(currentToken)) {
    const response = await fetch('/api/v2/auth/refresh', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${currentToken}`
      }
    });
    const { data } = await response.json();
    return data.token;
  }
  return currentToken;
};
```

### **Error Handling Best Practices**
```javascript
const apiRequest = async (url, options = {}) => {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Authorization': `Bearer ${await refreshTokenIfNeeded()}`,
        'Content-Type': 'application/json',
        ...options.headers
      }
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new APIError(data.message, response.status, data.errors);
    }
    
    return data;
  } catch (error) {
    console.error('API Request failed:', error);
    throw error;
  }
};
```

---

## Change Log

### **Version 2.0 (Current)**
- ✅ Enhanced authentication with biometric support
- ✅ Role-based dashboard endpoints
- ✅ Comprehensive attendance tracking
- ✅ Bulk operations with validation
- ✅ Real-time notifications
- ✅ Face recognition integration
- ✅ Offline support capabilities
- ✅ Advanced admin panel endpoints
- ✅ Rate limiting and security enhancements

### **Version 1.0 (Legacy)**
- Basic authentication
- Simple attendance endpoints
- Limited dashboard support
- Basic CRUD operations

---

## Support and Contact

### **API Support Channels**
- **Technical Documentation**: This document
- **Email Support**: api-support@dokterku.com
- **Developer Portal**: https://docs.dokterku.com
- **Issue Tracker**: https://github.com/dokterku/api-issues

### **Rate Limits and Quotas**
- **Free Tier**: 1,000 requests/day
- **Pro Tier**: 10,000 requests/day
- **Enterprise**: Custom limits available

### **SLA and Uptime**
- **Uptime Target**: 99.9%
- **Response Time**: < 200ms average
- **Support Response**: < 24 hours

---

*Generated: 2025-07-15*  
*Version: 2.0.0*  
*Status: Documentation Phase - API Reference Complete*