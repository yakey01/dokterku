# New Employee Attendance Fix Plan

## Problem Summary
Laila (new Paramedis employee created on 2025-07-26) shows "27 Tidak Hadir" in the dashboard, even though she just joined today. The system is incorrectly counting ALL working days in the month as absent days.

## Root Cause
The system calculates absent days as: `working_days - present_days`
- `working_days` = all Mon-Sat days in the month (27 days)
- For new employees, this counts days BEFORE they were hired as absent

## User's Clarification
"tidak hadir itu jika ada jadwal jaga tapi tidak cek in dan cek out sama sekali"
(Absent means having a scheduled shift but not checking in/out at all)

## Proposed Fix

### 1. Update ParamedisDashboardController.php
Change the attendance calculation logic to:
- Count scheduled shifts (jadwal jaga) instead of all working days
- Only count as absent if there was a scheduled shift but no attendance
- For employees without scheduled shifts, count from their start date

### 2. Update AttendanceRecap Model
Apply the same logic to ensure consistency across all reports:
- Consider employee start date
- Use scheduled shifts for absence calculation

### 3. Frontend Updates (if needed)
Update the React component to handle the new `scheduled_shifts` field in the API response.

## Implementation Details

### API Changes
```php
// Old calculation
$workingDays = countAllWorkingDaysInMonth(); // 27 days
$absent = $workingDays - $present; // Wrong for new employees

// New calculation
$scheduledShifts = JadwalJaga::where('pegawai_id', $user->id)
    ->whereBetween('tanggal_jaga', [$startDate, $endDate])
    ->count();
    
if ($scheduledShifts > 0) {
    // Has scheduled shifts - count absences based on shifts
    $workingDays = $scheduledShifts;
} else {
    // No scheduled shifts - count from employee start date
    $employeeStartDate = $user->created_at->startOfDay();
    $workingDays = countWorkingDaysBetween($employeeStartDate, $endDate);
}
```

### Expected Results
For Laila (created 2025-07-26):
- Before fix: 0 Hadir, 0 Terlambat, 27 Tidak Hadir ❌
- After fix: 0 Hadir, 0 Terlambat, 0 Tidak Hadir ✅

## Files to Update
1. `/app/Http/Controllers/Api/V2/Dashboards/ParamedisDashboardController.php` - Main fix
2. `/app/Models/AttendanceRecap.php` - Consistency fix
3. `/resources/js/components/paramedis/Laporan.tsx` - May need update if API changes

## Testing
1. Test with Laila (new employee, no scheduled shifts)
2. Test with existing employees with scheduled shifts
3. Verify attendance recap shows consistent data
4. Clear cache to ensure fresh data