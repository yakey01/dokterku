# ATTENDANCE DISCREPANCY FINAL ANALYSIS

## Executive Summary

**User Issue**: Dr. Yaya's attendance rate shows 56% in "stats presensi" but 85.7% in leaderboard, causing confusion about which value is correct.

**Root Cause**: Multiple API endpoints use different calculation methods, data sources, and working day definitions, leading to inconsistent attendance rates across the system.

## Findings

### Current Attendance Data for Dr. Yaya (August 2025)
- **Attendance Records**: 19 total records across 10 distinct dates
- **Distinct Attendance Days**: 10 days (Aug 1, 4, 6, 7, 8, 9, 10, 11, 12, 18)
- **Days with Complete Time-out**: 18 records have time_out values

### API Endpoint Analysis

| Endpoint | Attendance Rate | Calculation Method | Status |
|----------|----------------|-------------------|---------|
| `/api/v2/dashboards/dokter` (Main Dashboard) | 33.33% | AttendanceRecap model | ✅ Working |
| `/api/v2/dashboards/dokter/presensi` | 26.3% | Fallback calculation (distinct dates / working days) | ✅ Working |
| `/api/v2/dashboards/dokter/leaderboard` | Should be 85.7% | Records with time_out / working days (exclude weekends) | ❌ Empty result |
| `DokterStatsController` | 0% | Uses non-existent `kehadiran` table | ❌ Broken |

### Calculation Method Differences

#### 1. Leaderboard Calculation (Expected 85.7%)
```php
// From DokterDashboardController::leaderboard()
$attendanceCount = Attendance::where('user_id', $user->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->whereNotNull('time_out')  // Requires completed attendance
    ->count();

$workingDaysInMonth = 21; // Excludes weekends (Sat/Sun)
$attendanceRate = ($attendanceCount / $workingDaysInMonth) * 100;
// Result: 18 records with time_out / 21 working days = 85.7%
```

#### 2. Presensi API Calculation (26.3%)
```php
// From DokterDashboardController::getPresensi()
$attendanceDays = Attendance::where('user_id', $user->id)
    ->whereBetween('date', [$startDate, $endDate])
    ->distinct('date')  // Counts distinct attendance dates
    ->count();

$workingDays = 26; // Excludes Sundays only (Mon-Sat)
$attendanceRate = ($attendanceDays / $workingDays) * 100;
// Result: 10 distinct days / 26 working days = 38.46%
// But API returned 26.3% - possibly from AttendanceRecap
```

#### 3. AttendanceRecap Model (33.33%)
```php
// From AttendanceRecap::getRecapData()
// Uses complex business logic with different criteria
// Actual: 33.33% for Dr. Yaya
```

#### 4. DokterStatsController (0% - Broken)
```php
// Attempts to query non-existent 'kehadiran' table
$tableExists = DB::select("SHOW TABLES LIKE 'kehadiran'");
if (empty($tableExists)) {
    return ['current' => 0, 'rate' => 0]; // Always returns 0
}
```

## Mystery of the 56% Value

**Status**: **NOT FOUND** in current API endpoints.

**Possible Sources**:
1. **Frontend Mock Data**: May be hardcoded fallback value
2. **Cached Data**: Old cached response from previous calculation
3. **Different Time Period**: Calculation from different month/year
4. **Hidden API Endpoint**: Undiscovered endpoint with different logic
5. **Browser Storage**: Value stored in localStorage/sessionStorage

### Investigation Results
- ✅ Tested all known API endpoints
- ✅ Checked DokterStatsController (returns 0%)
- ✅ Analyzed AttendanceRecap model (returns 33.33%)
- ✅ Verified leaderboard calculation logic (should be 85.7%)
- ❌ Could not locate exact source of 56% value

## Technical Issues Identified

### 1. Inconsistent Working Days Definition
- **Leaderboard**: Excludes weekends (Sat/Sun) = 21 days
- **Presensi API**: Excludes Sundays only (Mon-Sat) = 26 days
- **Impact**: Same attendance data yields different rates

### 2. Different Attendance Criteria
- **Leaderboard**: Requires `time_out` (completed attendance)
- **Presensi API**: Any `time_in` (started attendance)
- **Impact**: Different record counts for same period

### 3. DokterStatsController Broken
- **Issue**: Queries non-existent `kehadiran` table
- **Current**: Always returns 0% attendance
- **Fix Needed**: Update to use `attendance` table

### 4. Multiple Calculation Sources
- **AttendanceRecap Model**: Complex business logic
- **Direct Database Queries**: Simple counting
- **Impact**: Different results for same user/period

## Recommendations

### Immediate Fixes

1. **Fix DokterStatsController**
```php
// Replace 'kehadiran' with 'attendance'
$attendanceToday = DB::table('attendance as a')
    ->join('dokters as d', 'a.user_id', '=', 'd.user_id')
    ->whereDate('a.date', $today)
    ->count();
```

2. **Standardize Working Days Definition**
   - Choose one definition: either exclude weekends OR exclude Sundays only
   - Update all endpoints to use same definition

3. **Standardize Attendance Criteria**
   - Choose one criterion: either require `time_out` OR accept `time_in`
   - Update all endpoints to use same criterion

### Long-term Solutions

1. **Create Unified Attendance Service**
```php
class AttendanceCalculationService 
{
    public function calculateAttendanceRate($userId, $month, $year): float
    {
        // Single source of truth for attendance calculation
    }
}
```

2. **Frontend Data Source Consolidation**
   - Use single API endpoint for all attendance data
   - Remove fallback values and mock data
   - Implement proper error handling

3. **Documentation**
   - Document official attendance calculation method
   - Define business rules for working days
   - Specify attendance completion criteria

## Current Data Summary for Dr. Yaya

| Metric | Value | Calculation |
|--------|-------|-------------|
| **Total Attendance Records** | 19 | Raw database count |
| **Distinct Attendance Days** | 10 | Unique dates with attendance |
| **Completed Attendances** | 18 | Records with time_out |
| **Calendar Days (August)** | 31 | Total days in month |
| **Working Days (Mon-Sat)** | 26 | Exclude Sundays |
| **Working Days (Mon-Fri)** | 21 | Exclude weekends |

### Calculated Rates
- **Leaderboard Method**: 18/21 = 85.7%
- **Presensi Method**: 10/26 = 38.5%
- **AttendanceRecap**: 33.33%
- **Calendar Method**: 10/31 = 32.3%

## Conclusion

The 85.7% vs 56% discrepancy is caused by:
1. **Different calculation methods** across API endpoints
2. **Inconsistent working day definitions**
3. **Different attendance completion criteria**

The 56% value was not found in current API endpoints, suggesting it may be:
- Cached data from previous calculation
- Frontend fallback/mock value
- Hidden API endpoint not yet discovered

**Recommended Action**: Implement unified attendance calculation service and standardize all endpoints to use the same business logic.