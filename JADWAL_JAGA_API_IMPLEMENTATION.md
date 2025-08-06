# Jadwal Jaga API Implementation Report

## Overview

Successfully analyzed and enhanced the existing jadwal jaga (work schedule) system in the Dokterku Laravel application by adding comprehensive schedule validation API endpoints before attendance check-in.

## System Architecture Analysis

### Existing Components
1. **JadwalJaga Model** (`app/Models/JadwalJaga.php`)
   - Relationships: User (pegawai), ShiftTemplate, WorkLocation
   - Key fields: `tanggal_jaga`, `shift_template_id`, `pegawai_id`, `status_jaga`, `unit_kerja`
   - Custom time handling with `jam_jaga_custom` for schedule overrides
   - Effective time calculation with timezone support

2. **ShiftTemplate Model** (`app/Models/ShiftTemplate.php`)
   - Defines shift patterns: `nama_shift`, `jam_masuk`, `jam_pulang`
   - Duration calculation with overnight shift support
   - Formatted time display methods

3. **AttendanceValidationService** (`app/Services/AttendanceValidationService.php`)
   - Comprehensive validation logic for schedule, location, timing
   - Multi-layered validation: schedule → location → time → compatibility
   - Tolerance settings from WorkLocation for flexible check-in/out windows

4. **Attendance Model** (`app/Models/Attendance.php`)
   - Links to JadwalJaga via `jadwal_jaga_id`
   - GPS validation and work location integration
   - Status tracking: `not_checked_in`, `checked_in`, `completed`

### Database Schema
```sql
jadwal_jagas table:
- id, tanggal_jaga, shift_template_id, pegawai_id
- unit_instalasi, unit_kerja, peran, status_jaga
- jam_masuk_custom, jam_pulang_custom (for schedule overrides)
- keterangan, created_at, updated_at
- Indexes: [tanggal_jaga, pegawai_id], [pegawai_id, status_jaga]
- Unique constraint: [tanggal_jaga, pegawai_id, shift_template_id]
```

## New API Endpoints Implementation

### 1. GET `/api/v2/jadwal-jaga/current`
**Purpose**: Get current active schedule for authenticated user

**Features**:
- Retrieves active schedules (`status_jaga = 'Aktif'`)
- Includes shift template details and work location information
- Real-time schedule status calculation
- Timing window information for check-in/check-out
- Tolerance settings from work location

**Response Structure**:
```json
{
  "success": true,
  "message": "Current active schedule retrieved successfully",
  "data": {
    "id": 123,
    "tanggal_jaga": "2025-08-06",
    "shift_template_id": 1,
    "unit_kerja": "Dokter Jaga",
    "status_jaga": "Aktif",
    "effective_start_time": "08:00",
    "effective_end_time": "16:00",
    "is_today": true,
    "shift_template": {
      "nama_shift": "Shift Pagi",
      "jam_masuk": "08:00",
      "jam_pulang": "16:00"
    },
    "work_location": {
      "name": "Klinik Utama",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius_meters": 100,
      "tolerance_settings": {
        "late_tolerance_minutes": 15,
        "checkin_before_shift_minutes": 30
      }
    },
    "schedule_status": {
      "status": "checkin_window",
      "message": "Check-in window is open",
      "window_closes_in": 45
    },
    "timing_info": {
      "shift_start": "08:00",
      "current_time": "07:45",
      "check_in_window": {
        "start": "07:30",
        "end": "08:15",
        "is_open": true
      }
    }
  }
}
```

### 2. POST `/api/v2/jadwal-jaga/validate-checkin`
**Purpose**: Comprehensive validation before attendance check-in

**Features**:
- Multi-layer validation using AttendanceValidationService
- GPS location validation against work locations
- Schedule timing validation with tolerance windows
- Attendance status checking (prevents double check-in)
- Detailed validation feedback for mobile UI

**Request**:
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "date": "2025-08-06"
}
```

**Response Structure**:
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
      "has_checked_in_today": false
    },
    "schedule_details": {
      "id": 123,
      "shift_name": "Shift Pagi",
      "unit_kerja": "Dokter Jaga",
      "effective_start_time": "08:00",
      "is_late_checkin": false
    },
    "work_location": {
      "name": "Klinik Utama",
      "distance_from_user": 45.2,
      "within_geofence": true
    },
    "validation_details": {
      "schedule": {"valid": true, "code": "VALID_SCHEDULE"},
      "location": {"valid": true, "code": "VALID_WORK_LOCATION"},
      "time": {"valid": true, "code": "ON_TIME"}
    }
  }
}
```

**Validation Error Response**:
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "validation": {
      "valid": false,
      "message": "Anda berada di luar area kerja yang diizinkan",
      "code": "OUTSIDE_GEOFENCE",
      "can_checkin": false
    }
  }
}
```

## Enhanced Route Structure

Updated `/Users/kym/Herd/Dokterku/routes/api-improved.php`:
```php
// Jadwal Jaga (Schedules)
Route::prefix('jadwal-jaga')->name('jadwal.')->group(function () {
    Route::get('/', 'Api\V2\JadwalJagaController@index');
    Route::get('/user/{userId}', 'Api\V2\JadwalJagaController@getUserSchedules');
    Route::get('/current', 'Api\V2\JadwalJagaController@current'); // NEW
    Route::post('/validate-checkin', 'Api\V2\JadwalJagaController@validateCheckin'); // NEW
    Route::get('/today', 'Api\V2\JadwalJagaController@today');
    Route::get('/week', 'Api\V2\JadwalJagaController@week');
    Route::get('/duration', 'Api\V2\JadwalJagaController@duration');
    Route::get('/monthly', 'Api\V2\JadwalJagaController@getMonthlySchedules');
});
```

## Validation Flow Integration

### Multi-Layer Validation Process
1. **Schedule Validation** → Check if user has active schedule for date
2. **Location Validation** → GPS geofencing with work location radius
3. **Time Validation** → Check-in window with tolerance settings
4. **Attendance Status** → Prevent duplicate check-ins
5. **Compatibility Check** → Shift-location compatibility validation

### Key Features
- **Tolerance Management**: Configurable late/early tolerance from WorkLocation
- **Geofencing**: GPS accuracy requirements and radius validation
- **Schedule Override**: Support for custom schedule times (`jam_jaga_custom`)
- **Overnight Shifts**: Proper handling of shifts crossing midnight
- **Real-time Status**: Current schedule status relative to time
- **Error Handling**: Comprehensive error codes and user-friendly messages

## Security & Performance

### Security Measures
- Authentication required via Sanctum middleware
- Input validation for GPS coordinates and date formats
- SQL injection prevention through Eloquent ORM
- Rate limiting via Laravel throttle middleware

### Performance Optimizations
- Database indexes on frequently queried fields
- Relationship eager loading to prevent N+1 queries
- Cache invalidation when schedules are modified
- Optimized GPS distance calculations

## Error Codes Reference

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `VALID` | All validations passed | 200 |
| `VALID_BUT_LATE` | Valid but late check-in | 200 |
| `NO_SCHEDULE` | No schedule found for date | 400 |
| `SCHEDULE_INACTIVE` | Schedule status not active | 400 |
| `OUTSIDE_GEOFENCE` | GPS location outside work area | 400 |
| `TOO_EARLY` | Check-in attempted too early | 400 |
| `TOO_LATE` | Check-in attempted too late | 400 |
| `CANNOT_CHECK_IN` | Already checked in or completed | 400 |
| `VALIDATION_ERROR` | Input validation failed | 422 |

## Mobile App Integration

The API provides everything needed for mobile attendance apps:

1. **Schedule Discovery**: `/current` endpoint shows active schedule
2. **Pre-flight Validation**: `/validate-checkin` validates before actual check-in
3. **Real-time Feedback**: Detailed error messages and timing windows
4. **GPS Integration**: Accurate location validation with tolerance
5. **Status Tracking**: Prevent duplicate operations

## Future Enhancements

1. **Multiple Shifts**: Support for users with multiple shifts per day
2. **Break Time Tracking**: Integration with break time tolerance
3. **Overtime Calculation**: Automatic overtime detection and reporting
4. **Schedule Conflicts**: Detection and resolution of conflicting schedules
5. **Mobile Notifications**: Push notifications for check-in reminders

## Conclusion

Successfully implemented comprehensive schedule validation API endpoints that integrate seamlessly with the existing Dokterku system. The solution provides robust validation, detailed error reporting, and optimal user experience for mobile attendance applications while maintaining security and performance standards.