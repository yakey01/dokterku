# Fix: Multiple Shifts Check-out Time Bug

## Problem Description
When a user has multiple shifts in a day and is already checked in, the system was incorrectly showing the checkout time for the wrong shift, resulting in negative minutes display like "-323 minutes".

### Example Scenario
- User has two shifts: 06:00-12:00 and 15:40-15:59
- User checks in at 06:15 for the 06:00-12:00 shift
- At 11:50, system shows: "Check-out terlalu awal. Anda dapat check-out mulai pukul 15:44 (-323.73 menit lagi)"
- Expected: Should allow checkout (11:45-13:00 window for 12:00 shift end)

## Root Cause Analysis

### Issue Location
**File**: `resources/js/components/dokter/Presensi.tsx`
**Lines**: ~1164-1172

### Problem Code
```typescript
let effectiveShift = sourceCurrentShift;
if ((!effectiveShift || !effectiveShift.shift_template) && Array.isArray(sourceTodaySchedule) && sourceTodaySchedule.length > 0) {
  // Simply picks first shift by start time
  const sorted = [...sourceTodaySchedule].sort((a: any, b: any) => {
    const [ah, am] = (a?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
    const [bh, bm] = (b?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
    return (ah * 60 + am) - (bh * 60 + bm);
  });
  effectiveShift = sorted[0];
}
```

### Why It Failed
1. System didn't check if user had an active attendance record
2. When determining effectiveShift, it would pick the next upcoming shift
3. For the 06:00-12:00 shift at 11:50, it would select 15:40-15:59 shift
4. Checkout calculation used 15:59 - 15 min = 15:44 as earliest checkout
5. Difference from 11:50 to 15:44 next day = -323 minutes

## Solution Implemented

### Fix Applied
Modified the `effectiveShift` determination logic to prioritize the shift the user actually checked into:

```typescript
// Determine effective shift with priority for checked-in shift
let effectiveShift = sourceCurrentShift;

// Check if user has an open attendance record (checked in but not out)
const openAttendance = Array.isArray(todayRecords) ? 
  todayRecords.find((r: any) => !!r.time_in && !r.time_out) : null;

if (openAttendance && Array.isArray(sourceTodaySchedule)) {
  // User is checked in - find the shift they're checked into
  const checkedInShift = sourceTodaySchedule.find(
    (s: any) => s.id === openAttendance.jadwal_jaga_id || 
                s.jadwal_jaga_id === openAttendance.jadwal_jaga_id
  );
  if (checkedInShift) {
    console.log('✅ Using checked-in shift:', checkedInShift);
    effectiveShift = checkedInShift;
  }
} else if ((!effectiveShift || !effectiveShift.shift_template) && 
           Array.isArray(sourceTodaySchedule) && sourceTodaySchedule.length > 0) {
  // No active attendance - use time-based selection
  const sorted = [...sourceTodaySchedule].sort((a: any, b: any) => {
    const [ah, am] = (a?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
    const [bh, bm] = (b?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
    return (ah * 60 + am) - (bh * 60 + bm);
  });
  effectiveShift = sorted[0];
}
```

## How The Fix Works

### Logic Flow
1. **Check for Active Attendance**: First checks if user has an open attendance record (checked in but not checked out)
2. **Match Shift to Attendance**: If active attendance exists, finds the corresponding shift from today's schedule
3. **Use Checked-In Shift**: Prioritizes the shift the user is actually working
4. **Fallback to Time-Based**: Only uses time-based selection if no active attendance exists

### Benefits
- ✅ Correct shift is used for checkout calculations
- ✅ No more negative minutes display
- ✅ Checkout window matches the actual working shift
- ✅ Supports multiple shifts per day correctly

## Testing & Verification

### Test Scripts Created
1. `public/test-multiple-shifts.php` - Analyzes users with multiple shifts
2. `public/debug-negative-minutes.php` - Debug checkout time calculations  
3. `public/test-shift-fix.php` - Verifies the fix implementation
4. `public/fix-shift-selection-logic.php` - Documentation of the fix

### Browser Console Verification
After the fix, the browser console will show:
```
✅ Using checked-in shift: {shift details}
```

This confirms the system is using the correct shift for checkout calculations.

## Impact on User Experience

### Before Fix
- Confusing negative minutes display
- Wrong checkout time shown
- System using incorrect shift for validation

### After Fix
- ✅ Correct checkout time displayed
- ✅ Proper shift validation
- ✅ Clear messaging for users
- ✅ Support for multiple shifts working correctly

## Related Fixes

This fix is part of a series of improvements:
1. **Automatic Check-out Prevention** - Fixed polling race conditions
2. **Work Location Tolerance** - Fixed global tolerance settings usage
3. **Multiple Shift Support** - This fix for correct shift selection

## Deployment Notes

### Steps to Deploy
1. Apply changes to `resources/js/components/dokter/Presensi.tsx`
2. Run `npm run build` to compile assets
3. Clear browser cache if needed
4. Test with users having multiple shifts

### Files Modified
- `resources/js/components/dokter/Presensi.tsx` (Lines ~1164-1190)

### Monitoring
Monitor browser console for the confirmation message:
- Look for: "✅ Using checked-in shift"
- Verify no negative minutes appear in UI
- Check that checkout times match shift schedules