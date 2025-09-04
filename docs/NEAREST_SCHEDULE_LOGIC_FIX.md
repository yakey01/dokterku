# Nearest Schedule Logic Implementation

## Problem Statement
The attendance (presensi) app was showing the wrong schedule when a user had multiple shifts in a day. For example:
- User "TES 2" has schedule: 07:30-08:00 (morning) and 17:45-18:00 (evening)
- App was showing 17:45-18:00 even in the morning when it should show 07:30-08:00

## Root Cause
The previous logic wasn't properly prioritizing the nearest upcoming schedule. It was selecting shifts based on simple time comparison without considering which schedule is most relevant to the current time.

## Solution Implemented

### Improved Schedule Selection Logic
Modified `/resources/js/components/dokter/Presensi.tsx` to implement a smarter schedule selection algorithm:

```javascript
// PRIORITY LOGIC:
// 1. Current shift (if within shift time + 30min buffer)
// 2. Nearest upcoming shift (sorted by distance to start time)  
// 3. Most recent past shift (if all shifts have passed)
```

### Key Changes

#### 1. Distance Calculation
Added calculation of distance from current time to each shift's start time:
```javascript
const distanceToStart = Math.abs(st - nowSec);
```

#### 2. Better Upcoming Detection
Improved detection of upcoming shifts:
```javascript
const isUpcoming = !isCurrent && (st > nowSec);
```

#### 3. Sorted Selection
Upcoming shifts are now sorted by distance to find the nearest one:
```javascript
const upcoming = normalized
  .filter(n => n.isUpcoming)
  .sort((a, b) => a.distanceToStart - b.distanceToStart)[0];
```

#### 4. Debug Logging
Added console logging to track schedule selection decisions:
```javascript
console.log('📅 All schedules for today:', normalized.map(n => ({
  time: `${n.startTime} - ${n.endTime}`,
  isCurrent: n.isCurrent,
  isUpcoming: n.isUpcoming,
  distance: n.distanceToStart
})));
```

## Expected Behavior

### Example: Multiple Daily Schedules
For a user with schedules 07:30-08:00 and 17:45-18:00:

| Current Time | Display | Reason |
|-------------|---------|---------|
| 06:00 | 07:30-08:00 | Nearest upcoming shift |
| 07:00 | 07:30-08:00 | Within 30-min buffer (07:00-08:30) |
| 07:45 | 07:30-08:00 | Currently in shift |
| 09:00 | 17:45-18:00 | Next upcoming shift |
| 17:15 | 17:45-18:00 | Within 30-min buffer |
| 17:50 | 17:45-18:00 | Currently in shift |
| 19:00 | 17:45-18:00 | Most recent past shift |

## Buffer Time Logic
- Each shift has a **30-minute buffer** before and after
- Users can check-in 30 minutes before shift starts
- Users can check-out up to 30 minutes after shift ends
- During buffer time, the shift is considered "current"

## Testing

### Test Script
Created `/public/test-nearest-schedule.php` to verify the logic:
- Shows all schedules for the day
- Calculates which schedule should be displayed
- Shows timeline visualization
- Explains the decision logic

### Verification Steps
1. Access test script: `http://localhost:8000/test-nearest-schedule.php`
2. Check schedule display matches expected behavior
3. Monitor console logs in browser for debug info
4. Use refresh button to force schedule update

## Files Modified

1. **Frontend Logic**
   - `/resources/js/components/dokter/Presensi.tsx` - Improved schedule selection algorithm

2. **Test Scripts**
   - `/public/test-nearest-schedule.php` - Logic verification tool

## Additional Improvements from Previous Fix

### Cache Management
- Cache invalidation on admin updates
- Reduced cache TTL (30s for schedules, 120s for dashboard)
- Force refresh mechanism with UI button

### Result
✅ Schedule now correctly shows the nearest/most relevant shift based on current time
✅ Morning shift (07:30-08:00) displays in the morning
✅ Evening shift (17:45-18:00) displays in the evening
✅ Smooth transitions between shifts with buffer time support

## Next Steps (Optional)
1. Add visual indicator showing all daily schedules
2. Highlight which schedule is currently active/upcoming
3. Add countdown timer to next shift
4. Implement notifications for upcoming shifts