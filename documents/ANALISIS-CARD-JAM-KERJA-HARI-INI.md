# 📊 ANALISIS CARD "JAM KERJA HARI INI"

## Overview
Card "Jam Kerja Hari Ini" menampilkan waktu check-in dan check-out yang **update dinamis** saat user melakukan check-out.

---

## 🎨 1. UI STRUKTUR CARD

### Layout Card
```jsx
<div className="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
  <div className="flex items-center justify-between mb-4">
    <h4 className="text-lg font-semibold text-white flex items-center space-x-2">
      <Clock className="w-5 h-5 text-blue-400" />
      <span>Jam Kerja Hari Ini</span>
    </h4>
    <div className="flex items-center space-x-1">
      <div className={`w-2 h-2 rounded-full ${attendanceData.checkOutTime ? 'bg-gray-400' : 'bg-green-400 animate-pulse'}`}></div>
      <span className="text-xs text-green-300">{attendanceData.checkOutTime ? 'Selesai' : 'Live'}</span>
    </div>
  </div>
```

### Status Indicator
- **Live**: Dot hijau berkedip (`bg-green-400 animate-pulse`) saat belum checkout
- **Selesai**: Dot abu-abu (`bg-gray-400`) saat sudah checkout

---

## ⏰ 2. CHECK-IN TIME DISPLAY

### Komponen Check-In
```jsx
<div className="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-2xl p-4">
  <div className="flex items-center space-x-3">
    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center">
      <Sun className="w-5 h-5 text-white" />
    </div>
    <div>
      <div className="text-xl font-bold text-green-400">
        {displayCheckInDate ? displayCheckInDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
      </div>
      <div className="text-xs text-green-300">Check In</div>
    </div>
  </div>
</div>
```

### Logic Check-In Time
```typescript
const displayCheckInDate = useMemo(() => {
  if (currentShiftRecord) return parseTodayTimeToDate(currentShiftRecord.time_in);
  return attendanceData?.checkInTime ? new Date(attendanceData.checkInTime) : null;
}, [currentShiftRecord, attendanceData?.checkInTime]);
```

**Prioritas Data:**
1. `currentShiftRecord.time_in` (dari server - lebih akurat)
2. `attendanceData.checkInTime` (state local)
3. `null` (tampil `--:--`)

---

## 🌙 3. CHECK-OUT TIME DISPLAY (DINAMIS)

### Komponen Check-Out
```jsx
<div className="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-2xl p-4">
  <div className="flex items-center space-x-3">
    <div className="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
      <Moon className="w-5 h-5 text-white" />
    </div>
    <div>
      <div className="text-xl font-bold text-purple-400">
        {displayCheckOutDate ? displayCheckOutDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
      </div>
      <div className="text-xs text-purple-300">Check Out</div>
    </div>
  </div>
</div>
```

### Logic Check-Out Time (DINAMIS)
```typescript
const displayCheckOutDate = useMemo(() => {
  const todayStr = getLocalDateStr();
  
  // Prefer server recorded checkout; clamp display to shift end if exceeded
  const rawEnd = scheduleData?.currentShift?.shift_template?.jam_pulang;
  const shiftEnd = typeof rawEnd === 'string' ? build(todayStr, rawEnd) : null;
  
  // Server data first, then local state
  const serverOut = currentShiftRecord ? build(todayStr, currentShiftRecord.time_out) : null;
  const recorded = serverOut || (attendanceData?.checkOutTime ? new Date(attendanceData.checkOutTime) : null);
  
  // Clamp to shift end if checkout after shift
  if (shiftEnd && recorded) return recorded > shiftEnd ? shiftEnd : recorded;
  
  return recorded;
}, [scheduleData?.currentShift, currentShiftRecord, attendanceData?.checkOutTime]);
```

**Prioritas Data:**
1. `currentShiftRecord.time_out` (dari server)
2. `attendanceData.checkOutTime` (state local - **UPDATE SAAT CHECKOUT**)
3. `null` (tampil `--:--`)

**Clamp Logic:**
- Jika checkout setelah jam shift berakhir → tampil jam shift berakhir
- Jika checkout dalam jam shift → tampil waktu checkout sebenarnya

---

## 🔄 4. UPDATE DINAMIS SAAT CHECK-OUT

### Flow Update Check-Out

#### Step 1: Optimistic Update
```typescript
const handleCheckout = async () => {
  const now = new Date();
  const optimisticTime = now.toISOString();
  
  // IMMEDIATE UPDATE - langsung tampil di UI
  setAttendanceData(prev => ({
    ...prev,
    checkOutTime: optimisticTime, // Update local state
    lastUpdated: now
  }));
};
```

#### Step 2: Server Response Update
```typescript
if (payload?.success) {
  const actualCheckOutTime = payload.data?.time_out || payload.data?.checkOutTime || optimisticTime;
  
  // UPDATE dengan waktu dari server (lebih akurat)
  setAttendanceData(prev => ({ 
    ...prev, 
    checkOutTime: actualCheckOutTime, // Server time menggantikan optimistic
    lastUpdated: new Date() 
  }));
}
```

#### Step 3: Multiple Checkout Support
```typescript
// MULTIPLE CHECKOUT: Keep checkout button enabled
setScheduleData(prev => ({
  ...prev,
  canCheckOut: true, // Tetap bisa checkout lagi
  validationMessage: 'Checkout berhasil! Anda dapat checkout lagi jika diperlukan.'
}));
```

### State Changes Timeline
```
1. User klik checkout → checkOutTime = "2024-08-11T21:45:00.000Z" (optimistic)
2. Server response → checkOutTime = "2024-08-11T14:45:00.000Z" (server time)
3. UI update → Tampil "22.02" (server time dalam timezone lokal)
4. Status indicator → Berubah dari "Live" ke "Selesai"
```

---

## 📊 5. PROGRESS BAR INTEGRATION

### Progress Calculation
```typescript
const computeProgressPercent = (): number => {
  if (!attendanceData?.checkInTime) return 0;
  
  const checkInTime = new Date(attendanceData.checkInTime);
  const checkOutTime = attendanceData?.checkOutTime 
    ? new Date(attendanceData.checkOutTime) 
    : new Date(); // Use current time if not checked out
    
  // Calculate progress based on shift duration
  const progress = ((checkOutTime.getTime() - checkInTime.getTime()) / totalShiftMs) * 100;
  return Math.min(Math.max(progress, 0), 100);
};
```

### Progress Bar UI
```jsx
<div className="flex justify-between text-sm mb-2">
  <span className="text-gray-300">Progress Hari Ini</span>
  <span className="text-cyan-400">{computeProgressPercent().toFixed(1)}%</span>
</div>
<div className="w-full bg-gray-700/50 rounded-full h-3">
  <div 
    className="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 h-3 rounded-full transition-all duration-500"
    style={{ width: `${computeProgressPercent().toFixed(1)}%` }}
  >
    {!attendanceData.checkOutTime && (
      <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
    )}
  </div>
</div>
```

**Fitur Progress Bar:**
- **Live Animation**: Berkedip putih saat belum checkout
- **Real-time**: Update setiap detik menggunakan current time
- **Final State**: Berhenti berkedip saat sudah checkout

---

## 🎯 6. STATE MANAGEMENT

### Main State
```typescript
const [attendanceData, setAttendanceData] = useState({
  checkInTime: null as string | null,
  checkOutTime: null as string | null,  // KEY: Ini yang update saat checkout
  workingHours: '00:00:00',
  location: 'RS. Kediri Medical Center'
});
```

### Computed Values
```typescript
// Waktu yang ditampilkan di card
const displayCheckInDate = useMemo(() => { ... });
const displayCheckOutDate = useMemo(() => { ... });  // Update otomatis saat checkOutTime berubah

// Status indicator
const isLive = !attendanceData.checkOutTime;
const statusText = attendanceData.checkOutTime ? 'Selesai' : 'Live';
```

---

## ⚡ 7. REAL-TIME FEATURES

### Auto-refresh Timer
```typescript
useEffect(() => {
  const timer = setInterval(() => {
    // Update working hours setiap detik jika belum checkout
    if (isCheckedIn && !attendanceData.checkOutTime) {
      const newWorkingHours = calculateWorkingHours();
      setAttendanceData(prev => ({
        ...prev,
        workingHours: newWorkingHours
      }));
    }
  }, 1000);
  
  return () => clearInterval(timer);
}, [isCheckedIn, attendanceData.checkInTime, attendanceData.checkOutTime]);
```

### Polling Integration
```typescript
useEffect(() => {
  const pollingInterval = setInterval(() => {
    // Refresh data setiap 30 detik tanpa mengganggu optimistic updates
    if (!isOperationInProgress) {
      loadTodayAttendance();
    }
  }, 30000);
  
  return () => clearInterval(pollingInterval);
}, [isOperationInProgress]);
```

---

## 🔧 8. CONTOH SKENARIO UPDATE

### Scenario 1: Normal Checkout
```
Initial State:
- checkInTime: "2024-08-11T13:45:00.000Z" (21:45 WIB)
- checkOutTime: null
- Display: "21.45" | "--:--"
- Status: "Live" (dot hijau berkedip)

User clicks checkout (22:02 WIB):
1. Optimistic update → checkOutTime: "2024-08-11T14:02:00.000Z" 
2. Display immediately → "21.45" | "22.02"
3. Status → "Selesai" (dot abu-abu)
4. Server confirms → checkOutTime: "2024-08-11T14:02:15.000Z" (server time)
5. Final display → "21.45" | "22.02" (server time)
```

### Scenario 2: Multiple Checkout
```
First checkout (22:02):
- checkOutTime: "2024-08-11T14:02:00.000Z"
- Display: "21.45" | "22.02"

Second checkout (22:15):
- checkOutTime: "2024-08-11T14:15:00.000Z" (UPDATE)
- Display: "21.45" | "22.15" (BERUBAH DINAMIS)
- Button tetap enabled untuk checkout ketiga
```

### Scenario 3: Late Checkout (After Shift)
```
Shift berakhir 16:00, checkout 17:30:
- Raw checkOutTime: "2024-08-11T09:30:00.000Z" 
- Shift end: "2024-08-11T08:00:00.000Z" (16:00 WIB)
- Display: "21.45" | "16.00" (CLAMPED ke shift end)
- Actual working time tetap dihitung hingga 17:30
```

---

## 🎨 9. STYLING & ANIMATIONS

### Check-Out Card Styling
```css
/* Background gradient purple/pink untuk checkout */
bg-gradient-to-br from-purple-500/20 to-pink-500/20

/* Icon background gradient */  
bg-gradient-to-br from-purple-400 to-pink-500

/* Text color purple untuk waktu checkout */
text-purple-400

/* Label color */
text-purple-300
```

### Status Animations
```css
/* Live status - dot berkedip */
bg-green-400 animate-pulse

/* Progress bar - berkedip saat live */
{!attendanceData.checkOutTime && (
  <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
)}
```

---

## ✅ KESIMPULAN

Card "Jam Kerja Hari Ini" memiliki fitur:

1. **Update Dinamis** - Check-out time langsung berubah saat user klik checkout
2. **Optimistic UI** - Tampil langsung tanpa tunggu server response
3. **Server Sync** - Waktu final dari server (lebih akurat) 
4. **Multiple Checkout** - Support checkout berulang dengan update time
5. **Real-time Progress** - Progress bar update setiap detik
6. **Status Indicator** - Visual feedback "Live" vs "Selesai"
7. **Shift Clamping** - Tampilan waktu dibatasi jam shift
8. **Timezone Handling** - Format waktu sesuai locale Indonesia

Logic ini memberikan **user experience yang responsif** dengan feedback visual yang jelas saat checkout dilakukan.