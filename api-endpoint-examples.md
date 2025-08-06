# Jadwal Jaga API Endpoint Examples

This document provides practical examples for testing the new jadwal jaga API endpoints.

## Authentication

All endpoints require Sanctum authentication. Include the bearer token in the Authorization header:

```
Authorization: Bearer your-sanctum-token-here
```

## 1. Get Current Active Schedule

**Endpoint**: `GET /api/v2/jadwal-jaga/current`

**Example Request**:
```bash
curl -X GET "http://localhost:8000/api/v2/jadwal-jaga/current" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**With Date Parameter**:
```bash
curl -X GET "http://localhost:8000/api/v2/jadwal-jaga/current?date=2025-08-06" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Example Success Response**:
```json
{
  "success": true,
  "message": "Current active schedule retrieved successfully",
  "data": {
    "id": 123,
    "tanggal_jaga": "2025-08-06",
    "shift_template_id": 1,
    "pegawai_id": 5,
    "unit_kerja": "Dokter Jaga",
    "unit_instalasi": "Poli Umum",
    "peran": "Paramedis",
    "status_jaga": "Aktif",
    "effective_start_time": "08:00",
    "effective_end_time": "16:00",
    "is_today": true,
    "shift_template": {
      "id": 1,
      "nama_shift": "Shift Pagi",
      "jam_masuk": "08:00",
      "jam_pulang": "16:00",
      "durasi": "8 jam 0 menit",
      "warna": "#10b981"
    },
    "work_location": {
      "id": 1,
      "name": "Klinik Utama",
      "description": "Lokasi kerja utama",
      "address": "Jl. Kesehatan No. 123",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius_meters": 100,
      "location_type": "main_office",
      "tolerance_settings": {
        "late_tolerance_minutes": 15,
        "early_departure_tolerance_minutes": 15,
        "checkin_before_shift_minutes": 30,
        "checkout_after_shift_minutes": 60
      },
      "require_photo": true,
      "strict_geofence": false,
      "gps_accuracy_required": 50
    },
    "schedule_status": {
      "status": "checkin_window",
      "message": "Check-in window is open",
      "window_closes_in": 45,
      "is_late": false
    },
    "timing_info": {
      "shift_start": "08:00",
      "shift_end": "16:00",
      "current_time": "07:45",
      "check_in_window": {
        "start": "07:30",
        "end": "08:15",
        "is_open": true
      },
      "check_out_window": {
        "start": "15:45",
        "end": "17:00",
        "is_open": false
      },
      "status": "Early check-in period (within tolerance)",
      "next_action": "can_checkin"
    }
  },
  "meta": {
    "version": "2.0",
    "timestamp": "2025-08-06T07:45:00.000000Z",
    "request_id": "uuid-string"
  }
}
```

**Example Error Response (No Schedule)**:
```json
{
  "success": false,
  "message": "No active schedule found for 06 Aug 2025",
  "meta": {
    "version": "2.0",
    "timestamp": "2025-08-06T07:45:00.000000Z",
    "request_id": "uuid-string"
  },
  "error_code": "NOT_FOUND"
}
```

## 2. Validate Check-in

**Endpoint**: `POST /api/v2/jadwal-jaga/validate-checkin`

**Example Request**:
```bash
curl -X POST "http://localhost:8000/api/v2/jadwal-jaga/validate-checkin" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 10.5,
    "date": "2025-08-06"
  }'
```

**Example Success Response (Can Check-in)**:
```json
{
  "success": true,
  "message": "Validation successful - ready for check-in",
  "data": {
    "validation": {
      "valid": true,
      "message": "Semua validasi berhasil - check-in diizinkan",
      "code": "VALID",
      "can_checkin": true
    },
    "attendance_status": {
      "status": "not_checked_in",
      "message": "Belum check-in hari ini",
      "can_check_in": true,
      "can_check_out": false,
      "has_checked_in_today": false,
      "attendance": null
    },
    "schedule_details": {
      "id": 123,
      "tanggal_jaga": "2025-08-06",
      "shift_name": "Shift Pagi",
      "unit_kerja": "Dokter Jaga",
      "status_jaga": "Aktif",
      "effective_start_time": "08:00",
      "effective_end_time": "16:00",
      "is_late_checkin": false
    },
    "work_location": {
      "id": 1,
      "name": "Klinik Utama",
      "address": "Jl. Kesehatan No. 123",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius_meters": 100,
      "distance_from_user": 45.2,
      "within_geofence": true
    },
    "validation_details": {
      "schedule": {
        "valid": true,
        "message": "Jadwal jaga valid",
        "code": "VALID_SCHEDULE"
      },
      "location": {
        "valid": true,
        "message": "Lokasi kerja valid",
        "code": "VALID_WORK_LOCATION"
      },
      "time": {
        "valid": true,
        "message": "Check-in tepat waktu.",
        "code": "ON_TIME"
      }
    }
  }
}
```

**Example Error Response (Outside Geofence)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "validation": {
      "valid": false,
      "message": "Anda berada di luar area kerja yang diizinkan. Jarak Anda dari lokasi kerja adalah 250 meter, sedangkan radius yang diizinkan adalah 100 meter.",
      "code": "OUTSIDE_GEOFENCE",
      "can_checkin": false
    },
    "attendance_status": {
      "status": "not_checked_in",
      "message": "Belum check-in hari ini",
      "can_check_in": true,
      "can_check_out": false,
      "has_checked_in_today": false,
      "attendance": null
    },
    "validation_details": {
      "schedule": {
        "valid": true,
        "code": "VALID_SCHEDULE"
      },
      "location": {
        "valid": false,
        "message": "Anda berada di luar area kerja yang diizinkan",
        "code": "OUTSIDE_GEOFENCE",
        "data": {
          "distance": 250,
          "allowed_radius": 100,
          "location_name": "Klinik Utama"
        }
      }
    }
  }
}
```

**Example Error Response (Too Early)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "validation": {
      "valid": false,
      "message": "Terlalu awal untuk check-in. Anda dapat check-in mulai pukul 07:30 (30 menit sebelum shift dimulai).",
      "code": "TOO_EARLY",
      "can_checkin": false
    },
    "validation_details": {
      "time": {
        "valid": false,
        "code": "TOO_EARLY",
        "data": {
          "shift_start": "08:00",
          "check_in_earliest": "07:30",
          "current_time": "07:00",
          "tolerance_settings": {
            "late_tolerance_minutes": 15,
            "checkin_before_shift_minutes": 30
          }
        }
      }
    }
  }
}
```

**Example Error Response (Already Checked In)**:
```json
{
  "success": false,
  "message": "Validation successful but check-in not allowed",
  "data": {
    "validation": {
      "valid": true,
      "message": "Semua validasi berhasil - check-in diizinkan",
      "code": "VALID",
      "can_checkin": false
    },
    "attendance_status": {
      "status": "checked_in",
      "message": "Sudah check-in, belum check-out",
      "can_check_in": false,
      "can_check_out": true,
      "has_checked_in_today": true,
      "attendance": {
        "id": 456,
        "time_in": "08:05:30",
        "time_out": null,
        "status": "present"
      }
    }
  }
}
```

## 3. Other Existing Endpoints

**Get Today's Schedule**:
```bash
curl -X GET "http://localhost:8000/api/v2/jadwal-jaga/today" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Get Week Schedule**:
```bash
curl -X GET "http://localhost:8000/api/v2/jadwal-jaga/week?week_start=2025-08-04" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Get Schedule Duration**:
```bash
curl -X GET "http://localhost:8000/api/v2/jadwal-jaga/duration?date=2025-08-06" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Common Error Codes

| HTTP Status | Error Code | Description |
|-------------|------------|-------------|
| 400 | `NO_SCHEDULE` | User has no schedule for the specified date |
| 400 | `SCHEDULE_INACTIVE` | User's schedule is not active (Cuti, Izin, etc.) |
| 400 | `OUTSIDE_GEOFENCE` | User is outside the allowed work location radius |
| 400 | `TOO_EARLY` | Check-in attempted before allowed window |
| 400 | `TOO_LATE` | Check-in attempted after allowed window |
| 400 | `CANNOT_CHECK_IN` | User has already checked in or completed attendance |
| 401 | `UNAUTHORIZED` | Missing or invalid authentication token |
| 404 | `NOT_FOUND` | No active schedule found for the specified date |
| 422 | `VALIDATION_ERROR` | Request validation failed (invalid coordinates, date format, etc.) |

## Testing Tips

1. **Get Authentication Token**: Use Laravel Sanctum to get a valid token
2. **Test Coordinates**: Use Jakarta coordinates (-6.2088, 106.8456) for testing
3. **Check User Schedule**: Ensure the authenticated user has an active schedule
4. **Work Location Setup**: Verify work locations are configured with proper GPS coordinates
5. **Time Testing**: Test different times to see various validation responses
6. **Error Scenarios**: Test edge cases like being outside geofence, wrong time, etc.

## Postman Collection

You can create a Postman collection with these examples for easier testing:

1. Create a new collection "Jadwal Jaga API"
2. Add environment variables for base URL and auth token
3. Import the requests above
4. Test various scenarios with different parameters

## Mobile App Integration

These endpoints provide everything needed for mobile attendance apps:

1. **Schedule Discovery**: Check if user has active schedule
2. **Pre-validation**: Validate before showing check-in button
3. **Real-time Feedback**: Show detailed error messages
4. **Location Guidance**: Show distance and allowed radius
5. **Time Windows**: Display check-in/check-out windows

The validation endpoint returns comprehensive data that mobile apps can use to provide helpful user guidance and prevent failed check-in attempts.