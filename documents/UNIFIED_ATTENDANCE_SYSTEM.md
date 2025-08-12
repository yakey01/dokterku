# 🎯 Unified Attendance Rate System

## Overview
Solusi komprehensif untuk menyelaraskan kalkulasi tingkat kehadiran antara Dashboard dan Presensi components.

## 🚨 Problem Statement

**Before**: Progress bar tingkat kehadiran tidak sinkron
- **Dashboard**: Menggunakan `dashboardData.performance.attendance_rate` (berbasis hari)
- **Presensi**: Menggunakan kalkulasi hours-based yang kompleks
- **Result**: Data dan progress bar menampilkan nilai berbeda

**After**: Progress bar tersinkronisasi dengan kalkulasi yang sama
- **Both Components**: Menggunakan `AttendanceCalculator` unified system
- **Result**: Data konsisten dan akurat di semua tampilan

## 🏗️ System Architecture

### 1. **AttendanceCalculator.ts** - Core Engine
```typescript
// Unified calculation engine
class AttendanceCalculator {
  static calculateAttendanceMetrics(records, monthStart, monthEnd): AttendanceMetrics
  static parseHours(hourString): number
  static calculateShiftDuration(startTime, endTime): number
}
```

#### **Priority System untuk Hours Calculation**:
1. **actual_hours** dari API (priority tertinggi)
2. **worked_hours** dari API  
3. Kalkulasi dari **time_in** dan **time_out**
4. Parse dari **hours** display format (fallback)

#### **Key Formula**:
```typescript
attendancePercentage = (totalAttendedHours / totalScheduledHours) × 100%
```

### 2. **UnifiedProgressBar.tsx** - Shared Component
```typescript
// Consistent visual representation
<UnifiedProgressBar
  percentage={attendanceMetrics.attendancePercentage}
  gradientColors="bg-gradient-to-r from-green-400 to-emerald-500"
  animated={true}
  showPercentage={true}
  size="md"
/>
```

## 🔄 Implementation Flow

### **Dashboard Component**:
```typescript
// 1. Fetch attendance history API
const attendanceHistory = await fetch('/api/v2/dashboards/dokter/attendance-history');

// 2. Use unified calculator
const unifiedMetrics = AttendanceCalculator.calculateAttendanceMetrics(
  attendanceHistory, monthStart, monthEnd
);

// 3. Update dashboard metrics
setDashboardMetrics({
  attendance: {
    rate: unifiedMetrics.attendancePercentage, // ✅ Synchronized
    daysPresent: unifiedMetrics.presentDays,
    totalDays: unifiedMetrics.totalDays
  }
});
```

### **Presensi Component**:
```typescript
// 1. Use same unified calculator
const unifiedMetrics = AttendanceCalculator.calculateAttendanceMetrics(
  formattedHistory, monthStart, monthEnd
);

// 2. Update monthly stats
setMonthlyStats({
  attendancePercentage: unifiedMetrics.attendancePercentage, // ✅ Synchronized
  totalAttendedHours: unifiedMetrics.totalAttendedHours,
  totalScheduledHours: unifiedMetrics.totalScheduledHours
});
```

## 📊 Data Flow Diagram

```
API Data Sources
├── /api/v2/dashboards/dokter/attendance-history (primary)
├── dashboardData.performance.attendance_rate (fallback)
└── Raw attendance records with time_in/time_out
                    ↓
           AttendanceCalculator
    ┌─────────────────────────────────┐
    │  1. Parse different hour formats │
    │  2. Calculate scheduled hours    │
    │  3. Calculate attended hours     │
    │  4. Apply unified formula       │
    └─────────────────────────────────┘
                    ↓
              AttendanceMetrics
    ┌─────────────────────────────────┐
    │ attendancePercentage: number    │
    │ totalAttendedHours: number      │
    │ totalScheduledHours: number     │
    │ presentDays: number             │
    │ progressBarValue: number        │
    └─────────────────────────────────┘
                    ↓
        Dashboard & Presensi Components
         (Both show identical values)
```

## 🎨 Visual Consistency

### Progress Bar Specifications:
- **Height**: 2px (h-2) standard
- **Colors**: `bg-gradient-to-r from-green-400 to-emerald-500`
- **Animation**: Smooth transition dengan accessibility support
- **Background**: `bg-gray-700/50` untuk konsistensi
- **Percentage Display**: Format `XX%` dengan color `text-green-400`

### Dashboard Display:
```tsx
<ProgressBarAnimation
  percentage={dashboardMetrics.attendance.rate} // ✅ Unified value
  gradientColors="bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400"
/>
```

### Presensi Display:
```tsx
<div 
  className="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full"
  style={{ width: `${monthlyStats.attendancePercentage}%` }} // ✅ Unified value
/>
```

## 🔧 Configuration & Extension

### Adding New Hour Formats:
```typescript
// In AttendanceCalculator.parseHours()
if (str.includes('new_format')) {
  // Add parsing logic
  return parsedValue;
}
```

### Custom Calculation Rules:
```typescript
// In AttendanceCalculator.calculateAttendanceMetrics()
// Add business-specific rules here
if (record.status === 'CustomStatus') {
  // Special handling
}
```

## 🧪 Testing & Validation

### Debug Information:
Both components now log unified metrics:
```javascript
console.log('📊 Dashboard using unified attendance metrics:', unifiedMetrics);
console.log('📊 Presensi using UNIFIED attendance metrics:', unifiedMetrics);
```

### Validation Checklist:
- [ ] Dashboard progress bar shows same percentage as Presensi
- [ ] Hours calculation uses API actual_hours when available
- [ ] Fallback to time_in/time_out calculation works
- [ ] Working days calculation excludes weekends
- [ ] Edge cases (no data, invalid times) handled gracefully

## 🚀 Benefits

### ✅ **Consistency**
- Identical calculations across all components
- Single source of truth for attendance logic
- Synchronized progress bars and metrics

### ✅ **Maintainability** 
- Centralized calculation logic
- Easy to update formulas globally
- Clear separation of concerns

### ✅ **Accuracy**
- Priority-based hour parsing
- Robust error handling
- Fallback mechanisms for missing data

### ✅ **Performance**
- Efficient calculation algorithms
- Minimal redundant API calls
- Optimized for large datasets

### ✅ **Accessibility**
- Progress bar with proper ARIA labels
- Reduced motion support
- Screen reader friendly

## 📝 Migration Notes

### For Developers:
1. Import `AttendanceCalculator` in components that need attendance metrics
2. Replace manual calculations with `calculateAttendanceMetrics()`
3. Use `UnifiedProgressBar` for consistent visual representation
4. Test with various attendance data scenarios

### API Considerations:
- Ensure attendance history API returns proper hour formats
- Consider adding `actual_hours` field for more accurate tracking
- Maintain backward compatibility with existing formats

## 🔮 Future Enhancements

1. **Real-time Sync**: WebSocket updates untuk live attendance changes
2. **Custom Thresholds**: Configurable attendance targets per user/department
3. **Historical Trends**: Month-over-month attendance comparison
4. **Predictive Analytics**: Forecast attendance patterns
5. **Multi-timezone Support**: Handle different timezone shifts

## 📞 Support

For issues or questions regarding the Unified Attendance System:
- Check console logs for debug information
- Verify API responses include required fields
- Test with different attendance record formats
- Review AttendanceCalculator logic for edge cases