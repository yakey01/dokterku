# 🚨 Progress Bar Troubleshooting Guide

## **Masalah: Progress Bar Tidak Berjalan**

### **Gejala:**
- Progress bar stuck di 0%
- Tidak ada animasi shimmer
- Working hours tidak bertambah
- Progress percentage tidak berubah

### **Penyebab Utama:**

#### 1. **Data Shift Template Kosong**
```typescript
// Masalah: scheduleData.currentShift.shift_template tidak ada
if (!scheduleData?.currentShift?.shift_template) {
  return { workedMs: 0, durasiMs: 0 }; // ❌ Progress = 0%
}
```

#### 2. **Today Records Tidak Terisi**
```typescript
// Masalah: todayRecords array kosong atau undefined
if (currentShiftId && Array.isArray(todayRecords) && todayRecords.length > 0) {
  // ❌ Tidak ada data attendance untuk progress calculation
}
```

#### 3. **Check-in Time Tidak Valid**
```typescript
// Masalah: attendanceData.checkInTime null atau invalid
if (!attendanceData?.checkInTime) return 0; // ❌ Progress = 0%
```

## 🔧 **Solusi yang Sudah Diimplementasi:**

### **1. Fallback Logic untuk Shift Template**
```typescript
if (!scheduleData?.currentShift?.shift_template) {
  // FALLBACK: Use default 8-hour shift if no shift template
  console.log('⚠️ No shift template found, using default 8-hour shift');
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 8, 0, 0); // 8:00 AM
  const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 16, 0, 0); // 4:00 PM
  const durasiMs = end.getTime() - start.getTime();
  
  // Use attendanceData for worked time calculation
  if (attendanceData?.checkInTime) {
    const checkInTime = new Date(attendanceData.checkInTime);
    const checkOutTime = attendanceData?.checkOutTime ? new Date(attendanceData.checkOutTime) : new Date();
    const workedMs = Math.max(0, checkOutTime.getTime() - checkInTime.getTime());
    return { workedMs, durasiMs };
  }
  
  return { workedMs: 0, durasiMs };
}
```

### **2. Priority System untuk Data Attendance**
```typescript
// PRIORITY 1: Use todayRecords if available
if (currentShiftId && Array.isArray(todayRecords) && todayRecords.length > 0) {
  const rec = todayRecords.find((r: any) => r.jadwal_jaga_id === currentShiftId);
  if (rec) {
    const tin = parseTOD(rec.time_in);
    if (tin) ins.push(tin);
    const tout = parseTOD(rec.time_out);
    if (tout) outs.push(tout);
  }
}

// PRIORITY 2: Fallback to attendanceData if no todayRecords
if (ins.length === 0 && attendanceData?.checkInTime) {
  const tin = parseTOD(attendanceData.checkInTime);
  if (tin) ins.push(tin);
}
```

### **3. Fallback Progress Calculation**
```typescript
const computeProgressPercent = () => {
  const { workedMs, durasiMs } = computeShiftStats();
  
  // If we have valid shift duration, calculate percentage
  if (durasiMs > 0) {
    const pct = Math.min(100, (workedMs / durasiMs) * 100);
    return Number.isFinite(pct) ? pct : 0;
  }
  
  // FALLBACK: Calculate progress based on check-in time and current time
  if (attendanceData?.checkInTime) {
    const checkInTime = new Date(attendanceData.checkInTime);
    const now = new Date();
    const workedMs = now.getTime() - checkInTime.getTime();
    
    // Assume 8-hour work day as fallback
    const fallbackDurationMs = 8 * 60 * 60 * 1000; // 8 hours in milliseconds
    const pct = Math.min(100, (workedMs / fallbackDurationMs) * 100);
    
    console.log('⚠️ Using fallback progress calculation:', {
      workedMs: Math.round(workedMs / 1000 / 60), // minutes
      fallbackDurationMs: Math.round(fallbackDurationMs / 1000 / 60), // minutes
      fallbackProgress: Math.round(pct)
    });
    
    return Number.isFinite(pct) ? pct : 0;
  }
  
  return 0;
};
```

### **4. Real-time Progress Updates**
```typescript
// Real-time progress bar updates - force re-render every second
useEffect(() => {
  if (!attendanceData.checkInTime || attendanceData.checkOutTime) {
    return; // Only update if checked in but not checked out
  }
  
  const progressTimer = setInterval(() => {
    // Force re-render by updating a state variable
    setCurrentTime(new Date());
  }, 1000);
  
  return () => clearInterval(progressTimer);
}, [attendanceData.checkInTime, attendanceData.checkOutTime]);
```

## 🧪 **Testing Progress Bar:**

### **File Testing:**
1. **`/public/test-progress-bar.html`** - Test UI progress bar
2. **`/public/test-progress-api.php`** - Test API responses

### **Test Scenarios:**
```bash
# Test normal scenario
curl "http://localhost/test-progress-api.php"

# Test checked in scenario
curl "http://localhost/test-progress-api.php?scenario=checked_in"

# Test checked out scenario
curl "http://localhost/test-progress-api.php?scenario=checked_out"

# Test no schedule scenario
curl "http://localhost/test-progress-api.php?scenario=no_schedule"

# Test late check-in scenario
curl "http://localhost/test-progress-api.php?scenario=late_checkin"
```

## 🔍 **Debug Steps:**

### **1. Check Console Logs**
```javascript
// Progress Debug akan muncul di console
console.log('🔍 Progress Debug:', {
  shiftId: currentShiftId,
  shiftStart: start.toLocaleTimeString(),
  shiftEnd: end.toLocaleTimeString(),
  checkIn: inTime?.toLocaleTimeString(),
  checkOut: outTime?.toLocaleTimeString(),
  effectiveIn: effectiveIn?.toLocaleTimeString(),
  effectiveOut: effectiveOut?.toLocaleTimeString(),
  workedMs: Math.round(workedMs / 1000 / 60), // minutes
  durasiMs: Math.round(durasiMs / 1000 / 60), // minutes
  progress: durasiMs > 0 ? Math.round((workedMs / durasiMs) * 100) : 0
});
```

### **2. Check API Responses**
```bash
# Test jadwal jaga API
curl "http://localhost/api/v2/dashboards/dokter/jadwal-jaga"

# Test presensi API
curl "http://localhost/api/v2/dashboards/dokter/presensi?include_all=1"
```

### **3. Verify Data Structure**
```typescript
// Pastikan struktur data sesuai
scheduleData: {
  currentShift: {
    shift_template: {
      jam_masuk: "08:00:00",    // ✅ Required
      jam_pulang: "16:00:00",   // ✅ Required
      durasi_jam: 8             // ✅ Optional
    }
  }
}

attendanceData: {
  checkInTime: "2024-01-15T08:30:00Z",  // ✅ Required for progress
  checkOutTime: null                      // ✅ null = still working
}
```

## 🚀 **Quick Fix Commands:**

### **1. Restart Component**
```javascript
// Force re-render
setCurrentTime(new Date());
```

### **2. Clear Cache**
```javascript
// Clear localStorage
localStorage.removeItem('attendance_cache');
```

### **3. Force Data Refresh**
```javascript
// Reload attendance data
loadAttendanceRecords();
```

## 📱 **Mobile Testing:**

### **1. Check Responsiveness**
- Progress bar harus responsive di mobile
- Touch events harus berfungsi
- Animasi harus smooth

### **2. Check GPS Integration**
- GPS permission harus granted
- Location harus valid
- Distance calculation harus akurat

## 🔧 **Jika Masih Bermasalah:**

### **1. Check Network Tab**
- API calls harus successful (200)
- Response data harus valid JSON
- No CORS errors

### **2. Check Error Console**
- JavaScript errors
- TypeScript compilation errors
- React component errors

### **3. Verify Dependencies**
```bash
# Check if all required packages installed
npm list react react-dom
npm list @types/react @types/react-dom
```

### **4. Check Browser Compatibility**
- Modern browser (Chrome 90+, Firefox 88+, Safari 14+)
- ES6+ support
- CSS Grid/Flexbox support

## 📞 **Support:**

Jika progress bar masih tidak berfungsi setelah semua langkah di atas:

1. **Check console logs** untuk error messages
2. **Verify API responses** untuk data integrity
3. **Test dengan file testing** untuk isolate masalah
4. **Check browser compatibility** dan device support
5. **Review network requests** untuk API issues

---

**Last Updated:** January 2024  
**Version:** 1.0  
**Status:** ✅ Implemented & Tested
