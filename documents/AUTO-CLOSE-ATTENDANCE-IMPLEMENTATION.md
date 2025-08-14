# Auto-Close Attendance Implementation

## Overview
Implemented automatic closure of attendance records that exceed checkout tolerance with a 1-minute work time penalty.

## Implementation Date
2025-08-12

## Requirements
- If user has checked in but not checked out
- Give them chance until checkout tolerance limit set by admin
- If exceeded, auto-close checkout with 1 minute work time penalty

## Solution Components

### 1. Command Implementation
**File**: `app/Console/Commands/AutoCloseAttendanceCommand.php`
- Created new Artisan command `attendance:auto-close`
- Checks all open attendance records
- Calculates max checkout time based on shift end + tolerance
- Auto-closes with 1 minute penalty if exceeded
- Supports dry-run mode for testing

### 2. Service Enhancement
**File**: `app/Services/AttendanceToleranceService.php`
- Added `hasExceededCheckoutTolerance()` method to check if tolerance exceeded
- Added `calculatePenaltyCheckoutTime()` method for penalty calculation
- Integrates with existing tolerance settings (user/role/global/work location)

### 3. Scheduling
**File**: `routes/console.php`
- Scheduled command to run every 5 minutes
- Logs output to `storage/logs/attendance-auto-close.log`
- Prevents overlapping runs

## Key Features

### Tolerance Priority
1. User-specific tolerance settings
2. Role-based tolerance settings  
3. Global tolerance settings
4. Work location fallback (default 60 minutes)

### Auto-Close Behavior
- Checks in time + 1 minute = Auto checkout time
- Records metadata about auto-closure
- Logs all auto-close actions
- Updates notes field with auto-close reason

### Safety Features
- Dry-run mode for testing
- Transaction-based updates
- Comprehensive logging
- Error handling and rollback

## Usage

### Manual Execution
```bash
# Dry run (preview only)
php artisan attendance:auto-close --dry-run

# Live execution
php artisan attendance:auto-close
```

### Automatic Execution
- Runs automatically every 5 minutes via Laravel scheduler
- Requires cron job: `* * * * * php artisan schedule:run`

## Monitoring
- Check logs at: `storage/logs/attendance-auto-close.log`
- Review Laravel logs for detailed auto-close events
- Monitor attendance records for `auto_closed` flag in metadata

## Testing
Tested successfully with dry-run mode on 2025-08-12.