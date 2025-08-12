# Multi-Shift Implementation Documentation

## Overview
Successfully implemented comprehensive multi-shift support for the attendance system, allowing staff like Yaya to work multiple shifts per day with proper validation and tracking.

## Implementation Date
August 11, 2025

## Key Features Implemented

### 1. Database Schema Enhancements
**Migration: `2025_08_11_enable_multishift_support.php`**

#### New Fields Added to `attendances` table:
- `shift_sequence` (integer) - Tracks shift order (1st, 2nd, 3rd shift of the day)
- `previous_attendance_id` (foreign key) - Links to previous shift attendance
- `gap_from_previous_minutes` (integer) - Minutes between shifts
- `is_additional_shift` (boolean) - Marks shifts beyond the first
- `is_overtime_shift` (boolean) - Marks overtime shifts (after 2nd shift by default)
- `next_shift_start` (time) - Time when next shift begins
- `next_shift_id` (foreign key) - Reference to next shift template

#### New Table: `shift_gap_rules`
- Manages allowed shift combinations
- Defines minimum/maximum gaps between specific shift pairs
- Supports custom gap rules per shift combination

### 2. Service Layer Updates
**File: `app/Services/CheckInValidationService.php`**

#### Enhanced `checkExistingAttendance()` Method:
- Supports multiple attendances per day
- Validates shift sequence
- Enforces gap requirements between shifts
- Detects overtime shifts
- Links attendance records

#### Multi-Shift Validation Rules:
```php
// Configuration (config/attendance.php)
'multishift' => [
    'enabled' => true,
    'max_shifts_per_day' => 3,
    'min_gap_between_shifts' => 60,  // minutes
    'max_gap_between_shifts' => 720, // minutes (12 hours)
    'allow_overtime_shifts' => true,
    'overtime_after_shifts' => 2,    // Mark as overtime after 2 shifts
]
```

### 3. Model Updates
**File: `app/Models/Attendance.php`**

#### New Relationships:
- `previousAttendance()` - Links to previous shift's attendance
- `nextShift()` - References next shift template
- `sameDayAttendances()` - Gets all attendances for the same day

#### New Methods:
- `getTodayAttendances()` - Returns all attendances for today (ordered)
- Support for multi-shift fields in fillable and casts arrays

### 4. API Controller Updates
**File: `app/Http/Controllers/Api/V2/Attendance/AttendanceController.php`**

#### Enhanced Check-in Response:
```json
{
  "attendance_id": 21,
  "schedule": {
    "shift_sequence": 2,
    "is_additional_shift": true,
    "is_overtime": false
  },
  "validation_details": {
    "message": "Check-in berhasil - Shift ke-2",
    "code": "VALID"
  }
}
```

## Business Logic Rules

### 1. Shift Sequence Management
- First shift of the day: `shift_sequence = 1`
- Subsequent shifts increment the sequence
- Maximum 3 shifts per day (configurable)

### 2. Gap Validation
- **Minimum Gap**: 60 minutes between shift checkout and next check-in
- **Maximum Gap**: 720 minutes (12 hours) to maintain shift continuity
- Gap is calculated from previous shift's checkout time

### 3. Overtime Detection
- Shifts beyond the 2nd are marked as overtime
- Configurable via `overtime_after_shifts` setting
- Tracked via `is_overtime_shift` field

### 4. Check-in Window Validation
Each shift has its own check-in window:
- **Early tolerance**: 30 minutes before shift start
- **Late tolerance**: 60 minutes after shift start
- Window validation applies to each shift independently

### 5. Shift Availability Logic
The system finds the next available shift by:
1. Getting all scheduled shifts for the day
2. Filtering out already-used shifts
3. Finding shifts where current time is within the check-in window
4. Assigning the earliest available shift

## Test Results

### Successful Test Scenarios:
✅ **First Shift Check-in**: Normal morning shift (06:00)
✅ **Multi-Shift Validation**: Prevents check-in without checkout
✅ **Gap Enforcement**: Requires 60-minute minimum gap
✅ **Second Shift**: Successfully checks in after proper gap
✅ **Overtime Detection**: Third shift marked as overtime
✅ **Max Shift Limit**: Prevents 4th shift (exceeds limit)

### Test Output:
```
=== SUMMARY ===
Total Shifts Completed: 3
- Shift 1: 05:45 - 13:55
- Shift 2: 14:56 - 22:00  
- Shift 3: 23:00 - 23:30 (OVERTIME)
```

## Configuration

### Environment Variables:
```env
ATTENDANCE_ALLOW_MULTIPLE_SHIFTS=true
ATTENDANCE_MAX_SHIFTS_PER_DAY=3
ATTENDANCE_MIN_SHIFT_GAP=60
ATTENDANCE_MAX_SHIFT_GAP=720
ATTENDANCE_ALLOW_OVERTIME=true
ATTENDANCE_OVERTIME_AFTER=2
```

### Config File: `config/attendance.php`
All multi-shift settings are centralized in the configuration file for easy management.

## API Endpoints

### Check-in Endpoint
**POST** `/api/v2/attendance/checkin`

Enhanced to support multi-shift with:
- Automatic shift sequence assignment
- Gap validation
- Overtime detection
- Previous attendance linking

### Response includes:
- `shift_sequence`: Current shift number
- `is_additional_shift`: Boolean flag
- `is_overtime`: Boolean flag
- `gap_minutes`: Minutes since last shift

## Database Integrity

### Constraints:
- Unique constraint on `(user_id, date, shift_sequence)` prevents duplicate sequences
- Foreign key to `previous_attendance_id` maintains shift chain
- Check constraints on gap values ensure data validity

### Indexes:
- Composite index on `(user_id, date, shift_sequence)` for fast lookups
- Index on `is_overtime_shift` for overtime reporting
- Index on `shift_sequence` for shift ordering

## Future Enhancements

### Potential Improvements:
1. **Shift Swap Management**: Allow users to swap shifts
2. **Break Time Tracking**: Track breaks between shifts
3. **Overtime Calculation**: Calculate overtime pay rates
4. **Shift Patterns**: Support recurring shift patterns
5. **Team Shift Coordination**: Ensure minimum staffing levels

### Reporting Enhancements:
1. Multi-shift attendance reports
2. Overtime analysis dashboards
3. Shift pattern analytics
4. Gap time utilization reports

## Migration Rollback

If needed, the migration can be rolled back:
```bash
php artisan migrate:rollback --step=1
```

This will:
- Remove multi-shift fields from attendances table
- Drop shift_gap_rules table
- Restore original unique constraints

## Conclusion

The multi-shift implementation successfully addresses the requirement for staff members like Yaya to work multiple shifts per day. The system enforces business rules while maintaining flexibility through configuration. All validation layers work together to ensure data integrity and proper shift management.

### Key Achievements:
✅ Comprehensive validation with 8-layer check-in validation
✅ Flexible configuration for different shift patterns
✅ Proper gap management between shifts
✅ Overtime detection and tracking
✅ Backward compatibility with single-shift scenarios
✅ Complete API integration
✅ Thorough testing with real-world scenarios