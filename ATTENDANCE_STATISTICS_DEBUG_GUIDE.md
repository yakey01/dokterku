# Attendance Statistics Debug Guide

## 🐛 Problem Summary
Attendance statistics showing all zeros despite filtering logs showing data is being processed correctly:
- Hari Hadir: 0
- Hari Terlambat: 0h 
- Kekurangan Jam: 0%
- Kehadiran (Jam): 0/0h

## 🔧 Debugging Enhancements Applied

### 1. Enhanced AttendanceCalculator Debugging
**File**: `resources/js/utils/AttendanceCalculator.ts`

**Changes**:
- Added comprehensive date format parsing (DD/MM/YYYY, DD/MM/YY, DD-MM-YY, ISO)
- Added detailed logging for date filtering process
- Added status-based filtering debugging
- Added hours calculation debugging for each record
- Added input validation and error handling

**Debug Logs to Look For**:
```
🔄 AttendanceCalculator: Starting unified calculation
🔍 Date filter check: (shows date parsing and range validation)
📊 Status-based filtering: (shows status categorization)
📊 Record X calculation: (shows hours calculation for each record)
📊 Hours calculation summary: (shows totals)
```

### 2. Enhanced Presensi Component Debugging
**File**: `resources/js/components/dokter/Presensi.tsx`

**Changes**:
- Added comprehensive data mapping debugging
- Enhanced filter period date range calculation
- Added AttendanceCalculator-compatible data transformation debugging
- Added statistics calculation validation
- Added global debug function for console testing

**Debug Logs to Look For**:
```
🔄 Stats tab - recalculating with filtered data...
📊 Debug: AttendanceHistory length: X
🔍 Filtering data: (shows filter process)
🔍 Record X filter check: (shows individual record filtering)
📊 Filter results: (shows filtering summary)
🐛 RECORD X DETAILED MAPPING: (shows data transformation)
📊 Calculator-compatible data: (shows final data format)
📊 Filtered statistics calculated: (shows final results)
```

## 🧪 Testing Instructions

### Step 1: Open Browser Console
1. Navigate to the attendance page
2. Open browser Developer Tools (F12)
3. Go to Console tab

### Step 2: Check Automatic Logs
Look for these log patterns in the console:
- Data loading logs with `🔄` prefix
- Filtering logs with `🔍` prefix  
- Statistics calculation logs with `📊` prefix
- Error logs with `⚠️` or `❌` prefix

### Step 3: Run Manual Debug
In browser console, run:
```javascript
debugAttendanceStats()
```

This will output a comprehensive debug report showing:
- Attendance history data
- Filtered data
- Date ranges used for calculation
- Calculator-compatible data transformation
- Final calculated metrics
- Current monthlyStats state

### Step 4: Check Statistics Tab
1. Click on Statistics tab
2. Change filter period (weekly/monthly)
3. Watch console for calculation logs
4. Verify if statistics update correctly

## 🔍 Key Areas to Investigate

### 1. Date Format Issues
**Problem**: Records might have dates in unexpected formats
**Check**: Look for date parsing logs showing format detection
**Solution**: Enhanced date parsing handles multiple formats

### 2. Status Field Mapping
**Problem**: Status values might not match expected values
**Check**: Look for "Status-based filtering" logs
**Expected Values**: 'Hadir', 'Tepat Waktu', 'Terlambat'

### 3. Hours Calculation
**Problem**: Hours fields might be missing or in wrong format
**Check**: Look for "Record X calculation" logs showing hours sources
**Fields Used**: `actual_hours`, `worked_hours`, `duration_minutes`, `time_in/time_out`, `hours`

### 4. Date Range Filtering
**Problem**: Date ranges might not include current data
**Check**: Look for "Date filter check" logs showing range validation
**Fix**: Enhanced date range calculation with proper hour/minute/second handling

## 🎯 Expected Behavior

After fixes, you should see:
1. **Non-zero filtered data**: Filter should return records within the selected period
2. **Proper status categorization**: Records should be categorized as present/late/absent
3. **Hours calculation**: Should calculate worked hours from available data fields
4. **Statistics update**: monthlyStats should update when filter period changes

## 🚨 Common Issues & Solutions

### Issue 1: No Records in Filter
**Symptoms**: "Filtered records for stats: 0"
**Solution**: Check date format parsing in filtering function

### Issue 2: Records Filtered But Zero Present Days
**Symptoms**: "Filtered records: X" but "presentDays: 0"
**Solution**: Check status field values against expected values

### Issue 3: Present Days Calculated But Zero Hours
**Symptoms**: "presentDays: X" but "totalAttendedHours: 0"
**Solution**: Check hours calculation logic and data field availability

### Issue 4: Statistics Not Updating
**Symptoms**: Statistics remain at initial values
**Solution**: Check useEffect dependencies and state update logic

## 📝 Next Steps

1. **Test with Real Data**: Run the debugging on actual attendance data
2. **Identify Root Cause**: Use console logs to pinpoint exact failure point
3. **Apply Targeted Fix**: Based on debugging results, apply specific fix
4. **Validate Fix**: Ensure statistics display correctly for different time periods

## 🔧 Additional Debugging Commands

Add these to browser console for more specific debugging:

```javascript
// Check raw attendance history
console.log('Raw attendance history:', window.debugAttendanceStats().attendanceHistory)

// Check current filter
console.log('Current filter period:', document.querySelector('[data-filter-period]')?.dataset?.filterPeriod)

// Manual calculation test
const testData = [{
  date: '14/08/2025',
  status: 'Hadir',
  time_in: '08:00',
  time_out: '17:00',
  actual_hours: 8,
  scheduled_hours: 8
}];
console.log('Manual test:', AttendanceCalculator.calculateAttendanceMetrics(testData, new Date('2025-08-01'), new Date('2025-08-31')))
```