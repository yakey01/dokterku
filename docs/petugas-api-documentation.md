# API Documentation - Petugas Dashboard

## Table of Contents
1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base URLs](#base-urls)
4. [Common Patterns](#common-patterns)
5. [Pasien API](#pasien-api)
6. [Tindakan API](#tindakan-api)
7. [Pendapatan Harian API](#pendapatan-harian-api)
8. [Pengeluaran Harian API](#pengeluaran-harian-api)
9. [Jumlah Pasien Harian API](#jumlah-pasien-harian-api)
10. [Stats API](#stats-api)
11. [Notification API](#notification-api)
12. [Export API](#export-api)
13. [Bulk Operations API](#bulk-operations-api)
14. [Validation Workflow API](#validation-workflow-api)
15. [Error Handling](#error-handling)
16. [Rate Limiting](#rate-limiting)

---

## Overview

The Petugas Dashboard API provides RESTful endpoints for managing clinic data including patients, treatments, financial transactions, and reports. All endpoints are secured with authentication and implement data scoping to ensure users only access their own data.

### API Version
- **Current Version**: v2
- **Base Path**: `/api/v2`
- **Content Type**: `application/json`

### Key Features
- **Data Scoping**: Users only access their own data
- **Role-based Permissions**: Different access levels per role
- **Validation Workflows**: Built-in approval processes
- **Bulk Operations**: Efficient mass data operations
- **Export Capabilities**: Multiple format support
- **Real-time Notifications**: WebSocket integration

---

## Authentication

### Bearer Token Authentication
All API requests require a valid Bearer token in the Authorization header.

```http
Authorization: Bearer {your-api-token}
```

### Obtaining Tokens
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "petugas@dokterku.com",
    "password": "your-password"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "Petugas Name",
            "email": "petugas@dokterku.com",
            "role": "petugas"
        }
    }
}
```

### Token Refresh
```http
POST /api/auth/refresh
Authorization: Bearer {current-token}
```

---

## Base URLs

### Production
```
https://api.dokterku.com/api/v2
```

### Staging
```
https://staging-api.dokterku.com/api/v2
```

### Development
```
http://localhost:8000/api/v2
```

---

## Common Patterns

### Standard Response Format
```json
{
    "success": true,
    "data": {},
    "message": "Operation completed successfully",
    "meta": {
        "total": 100,
        "per_page": 25,
        "current_page": 1,
        "last_page": 4
    }
}
```

### Error Response Format
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid",
        "details": {
            "nama": ["The nama field is required."],
            "email": ["The email must be a valid email address."]
        }
    }
}
```

### Query Parameters
- **`page`**: Page number for pagination (default: 1)
- **`per_page`**: Records per page (default: 25, max: 100)
- **`search`**: Search term for filtering
- **`sort`**: Sort field (default: created_at)
- **`order`**: Sort order (asc/desc, default: desc)
- **`filters`**: JSON object for filtering

---

## Pasien API

### List Patients
```http
GET /api/v2/pasien
```

**Query Parameters:**
- `search`: Search by name or medical record number
- `jenis_kelamin`: Filter by gender (L/P)
- `created_from`: Filter from date (YYYY-MM-DD)
- `created_to`: Filter to date (YYYY-MM-DD)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "no_rekam_medis": "RM-2024-001",
            "nama": "John Doe",
            "tanggal_lahir": "1990-01-15",
            "jenis_kelamin": "L",
            "alamat": "Jl. Test No. 123",
            "no_telepon": "08123456789",
            "email": "john@test.com",
            "umur": 34,
            "input_by": 1,
            "created_at": "2024-07-15T10:30:00Z",
            "updated_at": "2024-07-15T10:30:00Z"
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 25,
        "current_page": 1,
        "last_page": 2
    }
}
```

### Get Patient by ID
```http
GET /api/v2/pasien/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "no_rekam_medis": "RM-2024-001",
        "nama": "John Doe",
        "tanggal_lahir": "1990-01-15",
        "jenis_kelamin": "L",
        "alamat": "Jl. Test No. 123",
        "no_telepon": "08123456789",
        "email": "john@test.com",
        "pekerjaan": "Engineer",
        "status_pernikahan": "menikah",
        "kontak_darurat_nama": "Jane Doe",
        "kontak_darurat_telepon": "08987654321",
        "umur": 34,
        "tindakan_count": 5,
        "input_by": 1,
        "created_at": "2024-07-15T10:30:00Z",
        "updated_at": "2024-07-15T10:30:00Z"
    }
}
```

### Create Patient
```http
POST /api/v2/pasien
Content-Type: application/json

{
    "no_rekam_medis": "RM-2024-002",
    "nama": "Jane Smith",
    "tanggal_lahir": "1985-05-20",
    "jenis_kelamin": "P",
    "alamat": "Jl. Example St. 456",
    "no_telepon": "08123456790",
    "email": "jane@test.com",
    "pekerjaan": "Teacher",
    "status_pernikahan": "single"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "no_rekam_medis": "RM-2024-002",
        "nama": "Jane Smith",
        "tanggal_lahir": "1985-05-20",
        "jenis_kelamin": "P",
        "alamat": "Jl. Example St. 456",
        "no_telepon": "08123456790",
        "email": "jane@test.com",
        "pekerjaan": "Teacher",
        "status_pernikahan": "single",
        "umur": 39,
        "input_by": 1,
        "created_at": "2024-07-15T11:00:00Z",
        "updated_at": "2024-07-15T11:00:00Z"
    },
    "message": "Patient created successfully"
}
```

### Update Patient
```http
PUT /api/v2/pasien/{id}
Content-Type: application/json

{
    "nama": "Jane Smith Updated",
    "alamat": "Updated Address",
    "no_telepon": "08123456999"
}
```

### Delete Patient
```http
DELETE /api/v2/pasien/{id}
```

**Response:**
```json
{
    "success": true,
    "message": "Patient deleted successfully"
}
```

---

## Tindakan API

### List Treatments
```http
GET /api/v2/tindakan
```

**Query Parameters:**
- `pasien_id`: Filter by patient ID
- `status_validasi`: Filter by validation status (pending/approved/rejected)
- `tanggal_from`: Filter from date
- `tanggal_to`: Filter to date

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "jenis_tindakan_id": 1,
            "pasien_id": 1,
            "tanggal_tindakan": "2024-07-15T14:30:00Z",
            "shift_id": 1,
            "dokter_id": 1,
            "tarif": 150000,
            "jasa_dokter": 60000,
            "jasa_paramedis": 30000,
            "jasa_non_paramedis": 15000,
            "status": "selesai",
            "status_validasi": "approved",
            "input_by": 1,
            "approved_by": 2,
            "approved_at": "2024-07-15T15:00:00Z",
            "pasien": {
                "id": 1,
                "nama": "John Doe",
                "no_rekam_medis": "RM-2024-001"
            },
            "jenis_tindakan": {
                "id": 1,
                "nama": "Konsultasi Umum",
                "tarif": 150000
            },
            "created_at": "2024-07-15T14:30:00Z",
            "updated_at": "2024-07-15T15:00:00Z"
        }
    ]
}
```

### Create Treatment
```http
POST /api/v2/tindakan
Content-Type: application/json

{
    "jenis_tindakan_id": 1,
    "pasien_id": 1,
    "tanggal_tindakan": "2024-07-15T14:30:00",
    "shift_id": 1,
    "dokter_id": 1,
    "tarif": 150000,
    "jasa_dokter": 60000,
    "jasa_paramedis": 30000,
    "jasa_non_paramedis": 15000
}
```

### Submit for Validation
```http
POST /api/v2/tindakan/{id}/submit
```

**Response:**
```json
{
    "success": true,
    "data": {
        "status": "pending",
        "auto_approved": false,
        "requires_approval_from": "supervisor",
        "submission_id": "TND-20240715-001"
    },
    "message": "Treatment submitted for validation"
}
```

---

## Pendapatan Harian API

### List Daily Income
```http
GET /api/v2/pendapatan-harian
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "tanggal_input": "2024-07-15",
            "shift": "Pagi",
            "pendapatan_id": 1,
            "nominal": 500000,
            "deskripsi": "Pendapatan konsultasi hari ini",
            "status_validasi": "approved",
            "user_id": 1,
            "pendapatan": {
                "id": 1,
                "nama_pendapatan": "Konsultasi"
            },
            "created_at": "2024-07-15T16:00:00Z",
            "updated_at": "2024-07-15T16:00:00Z"
        }
    ]
}
```

### Create Daily Income
```http
POST /api/v2/pendapatan-harian
Content-Type: application/json

{
    "tanggal_input": "2024-07-15",
    "shift": "Pagi",
    "pendapatan_id": 1,
    "nominal": 300000,
    "deskripsi": "Pendapatan dari tindakan medis"
}
```

---

## Pengeluaran Harian API

### List Daily Expenses
```http
GET /api/v2/pengeluaran-harian
```

### Create Daily Expense
```http
POST /api/v2/pengeluaran-harian
Content-Type: application/json

{
    "tanggal_input": "2024-07-15",
    "shift": "Pagi",
    "pengeluaran_id": 1,
    "nominal": 150000,
    "deskripsi": "Pembelian obat-obatan"
}
```

---

## Jumlah Pasien Harian API

### List Daily Patient Reports
```http
GET /api/v2/jumlah-pasien-harian
```

### Create Daily Patient Report
```http
POST /api/v2/jumlah-pasien-harian
Content-Type: application/json

{
    "tanggal": "2024-07-15",
    "shift": "Pagi",
    "jumlah_pasien": 25,
    "keterangan": "Hari ini cukup ramai, banyak pasien flu"
}
```

---

## Stats API

### Get Dashboard Statistics
```http
GET /api/v2/stats/dashboard
```

**Response:**
```json
{
    "success": true,
    "data": {
        "daily": {
            "today": {
                "pasien_count": 10,
                "tindakan_count": 15,
                "tindakan_sum": 2250000,
                "pendapatan_sum": 3000000,
                "pengeluaran_sum": 500000,
                "net_income": 2500000
            },
            "yesterday": {
                "pasien_count": 8,
                "tindakan_count": 12,
                "tindakan_sum": 1800000,
                "pendapatan_sum": 2400000,
                "pengeluaran_sum": 400000,
                "net_income": 2000000
            }
        },
        "weekly": {
            "this_week": {
                "pasien_count": 65,
                "tindakan_count": 85,
                "tindakan_sum": 12750000,
                "pendapatan_sum": 18000000,
                "pengeluaran_sum": 3500000,
                "net_income": 14500000
            }
        },
        "monthly": {
            "this_month": {
                "pasien_count": 280,
                "tindakan_count": 350,
                "tindakan_sum": 52500000,
                "pendapatan_sum": 75000000,
                "pengeluaran_sum": 15000000,
                "net_income": 60000000
            }
        },
        "trends": {
            "pasien_trend": "+15%",
            "income_trend": "+22%",
            "efficiency_score": 87
        }
    }
}
```

### Get Trend Data
```http
GET /api/v2/stats/trends?period=7days
```

**Query Parameters:**
- `period`: 7days, 30days, 90days, 1year

---

## Notification API

### Get User Notifications
```http
GET /api/v2/notifications
```

**Query Parameters:**
- `limit`: Number of notifications (default: 10)
- `unread_only`: true/false (default: false)

**Response:**
```json
{
    "success": true,
    "data": {
        "notifications": [
            {
                "id": "notif_123",
                "title": "Validation Required",
                "message": "Treatment TND-20240715-001 requires your approval",
                "type": "validation_pending",
                "priority": "medium",
                "read_at": null,
                "created_at": "2024-07-15T16:30:00Z",
                "data": {
                    "model_type": "Tindakan",
                    "model_id": 1,
                    "action_url": "/petugas/tindakan/1"
                }
            }
        ],
        "total": 5,
        "unread": 3
    }
}
```

### Mark Notification as Read
```http
POST /api/v2/notifications/{id}/read
```

### Clear All Notifications
```http
POST /api/v2/notifications/clear-all
```

---

## Export API

### Export Data
```http
POST /api/v2/export
Content-Type: application/json

{
    "model": "Pasien",
    "format": "csv",
    "filters": {
        "created_from": "2024-07-01",
        "created_to": "2024-07-15"
    },
    "columns": ["nama", "no_rekam_medis", "tanggal_lahir", "jenis_kelamin"]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "export_id": "exp_123456",
        "file_url": "https://api.dokterku.com/exports/pasien_20240715.csv",
        "expires_at": "2024-07-16T16:30:00Z",
        "record_count": 150,
        "file_size": "25.6 KB"
    },
    "message": "Export completed successfully"
}
```

### Get Export Status
```http
GET /api/v2/export/{export_id}/status
```

### Download Export File
```http
GET /api/v2/export/{export_id}/download
```

---

## Bulk Operations API

### Bulk Create
```http
POST /api/v2/bulk/create
Content-Type: application/json

{
    "model": "Pasien",
    "data": [
        {
            "nama": "Patient 1",
            "no_rekam_medis": "RM-2024-100",
            "tanggal_lahir": "1990-01-01",
            "jenis_kelamin": "L"
        },
        {
            "nama": "Patient 2",
            "no_rekam_medis": "RM-2024-101",
            "tanggal_lahir": "1985-05-15",
            "jenis_kelamin": "P"
        }
    ],
    "options": {
        "batch_size": 50,
        "validate": true
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "operation_id": "bulk_123456",
        "created": 2,
        "failed": 0,
        "total": 2,
        "errors": [],
        "processing_time": "1.23s"
    },
    "message": "Bulk create completed successfully"
}
```

### Bulk Update
```http
POST /api/v2/bulk/update
Content-Type: application/json

{
    "model": "Pasien",
    "data": [
        {
            "id": 1,
            "alamat": "Updated Address 1"
        },
        {
            "id": 2,
            "alamat": "Updated Address 2"
        }
    ]
}
```

### Get Bulk Operation Status
```http
GET /api/v2/bulk/{operation_id}/status
```

---

## Validation Workflow API

### Get Pending Validations
```http
GET /api/v2/validation/pending
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "val_123",
            "model": "Tindakan",
            "model_id": 1,
            "amount": 250000,
            "priority": "medium",
            "submitted_by": {
                "id": 1,
                "name": "Petugas Name"
            },
            "submitted_at": "2024-07-15T14:30:00Z",
            "data": {
                "pasien_name": "John Doe",
                "tindakan_name": "Konsultasi Umum"
            }
        }
    ],
    "total": 5
}
```

### Approve Item
```http
POST /api/v2/validation/{id}/approve
Content-Type: application/json

{
    "reason": "Data sudah sesuai dan lengkap",
    "notes": "Approved by supervisor"
}
```

### Reject Item
```http
POST /api/v2/validation/{id}/reject
Content-Type: application/json

{
    "reason": "Data tidak lengkap",
    "notes": "Mohon lengkapi dokumentasi tindakan"
}
```

### Request Revision
```http
POST /api/v2/validation/{id}/revision
Content-Type: application/json

{
    "reason": "Perlu perbaikan data tarif",
    "notes": "Sesuaikan tarif dengan standar yang berlaku"
}
```

---

## Error Handling

### Standard Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `UNAUTHORIZED` | 401 | Invalid or missing authentication |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `CONFLICT` | 409 | Resource conflict (duplicate data) |
| `RATE_LIMITED` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |

### Error Response Examples

#### Validation Error
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid",
        "details": {
            "nama": ["The nama field is required."],
            "tanggal_lahir": ["The tanggal lahir must be a valid date."]
        }
    }
}
```

#### Authorization Error
```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Unauthenticated. Please provide a valid token."
    }
}
```

#### Permission Error
```json
{
    "success": false,
    "error": {
        "code": "FORBIDDEN",
        "message": "You don't have permission to access this resource."
    }
}
```

---

## Rate Limiting

### Limits
- **General API**: 60 requests per minute
- **Export API**: 10 requests per minute
- **Bulk Operations**: 5 requests per minute
- **Authentication**: 5 requests per minute

### Headers
Response includes rate limit headers:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642694400
```

### Rate Limit Exceeded
```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMITED",
        "message": "Too many requests. Please try again later.",
        "retry_after": 60
    }
}
```

---

## Webhooks (Optional)

### Available Events
- `patient.created`
- `patient.updated`
- `treatment.submitted`
- `treatment.approved`
- `treatment.rejected`
- `validation.pending`

### Webhook Payload Example
```json
{
    "event": "treatment.approved",
    "timestamp": "2024-07-15T16:30:00Z",
    "data": {
        "id": 1,
        "model": "Tindakan",
        "approved_by": 2,
        "approved_at": "2024-07-15T16:30:00Z",
        "user_id": 1
    }
}
```

---

## SDK and Libraries

### PHP SDK
```bash
composer require dokterku/petugas-api-php
```

### JavaScript/Node.js
```bash
npm install @dokterku/petugas-api-js
```

### Python
```bash
pip install dokterku-petugas-api
```

---

## Support and Contact

### API Support
- **Email**: api-support@dokterku.com
- **Slack**: #api-support
- **Documentation**: https://docs.api.dokterku.com

### Status Page
- **URL**: https://status.dokterku.com
- **Uptime Monitoring**: 99.9% SLA

---

*This API documentation is version-controlled and automatically updated. Always refer to the latest version for accurate information.*

**Version**: 2.0  
**Last Updated**: 2024-07-15  
**API Team**: Dokterku Development Team