# Fix: Cannot Access Uninitialized Variable Error

## Problem Analysis
Error terjadi setelah menambahkan logic untuk memprioritaskan shift yang sedang aktif pada user dengan multiple shifts.

### Error Message
```
ReferenceError: Cannot access uninitialized variable
```

### Root Cause
Variable `todayRecords` digunakan dalam fungsi `validateCurrentStatus()` tetapi:
1. `todayRecords` adalah state yang didefinisikan di level komponen (line 22)
2. Fungsi `validateCurrentStatus` tidak memiliki akses langsung ke state ini
3. Variable digunakan tanpa memastikan tersedia dalam scope fungsi

## Code Analysis

### Original Problem Code
```typescript
// Inside validateCurrentStatus function
const openAttendance = Array.isArray(todayRecords) ? 
  todayRecords.find((r: any) => !!r.time_in && !r.time_out) : null;
// Error: todayRecords is not defined in this scope
```

### State Definition
```typescript
// Line 22 - Component level state
const [todayRecords, setTodayRecords] = useState<any[]>([]);
```

## Solution Applied

### 1. Add todayRecords to Function Parameters
```typescript
async function validateCurrentStatus(overrides?: {
  currentShift?: any;
  todaySchedule?: any[];
  workLocation?: any;
  todayRecords?: any[];  // Added this parameter
}) {
```

### 2. Use Parameter Instead of Direct State Access
```typescript
// Get todayRecords from overrides or use empty array as fallback
const sourceTodayRecords = overrides?.todayRecords ?? [];

// Check if user has an open attendance record
const openAttendance = Array.isArray(sourceTodayRecords) ? 
  sourceTodayRecords.find((r: any) => !!r.time_in && !r.time_out) : null;
```

### 3. Update All Function Calls
```typescript
// Before
await validateCurrentStatus();

// After
await validateCurrentStatus({ todayRecords });
```

## Files Modified
- `resources/js/components/dokter/Presensi.tsx`
  - Line 1104-1108: Added todayRecords parameter
  - Line 1168-1173: Use sourceTodayRecords from parameter
  - Line 1297: Use sourceTodayRecords for thereIsOpenToday
  - Multiple lines: Updated all validateCurrentStatus calls

## Verification Steps
1. Build completed successfully: `npm run build`
2. No more initialization errors in console
3. Multiple shift logic still works correctly

## Lessons Learned
1. Always check variable scope when using state in async functions
2. Pass state as parameters to functions that need them
3. Use parameter fallbacks to avoid undefined errors
4. Test thoroughly after adding new logic that accesses state

## Prevention
For future development:
- Always pass required state as parameters to async functions
- Use TypeScript interfaces to ensure required parameters
- Test with production build to catch minification issues early