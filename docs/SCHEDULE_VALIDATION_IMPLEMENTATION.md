# Jadwal Jaga Validation Implementation - Presensi Dokter

## 🎯 Overview

Implementasi validasi jadwal jaga yang ketat untuk memastikan dokter hanya dapat melakukan presensi sesuai dengan jadwal jaga yang telah ditugaskan oleh admin.

## ✨ Fitur Implementasi

### 1. **Validasi Jadwal Jaga di Backend**
- ✅ Mengecek apakah dokter memiliki jadwal jaga hari ini
- ✅ Memvalidasi status jaga (hanya 'Aktif' yang diizinkan)
- ✅ Memvalidasi waktu check-in/out sesuai jam jaga
- ✅ Mendukung shift malam (overnight shifts)
- ✅ Link attendance dengan jadwal jaga ID

### 2. **Validasi Jadwal Jaga di Frontend**
- ✅ Load dan filter jadwal jaga hari ini
- ✅ Validasi waktu real-time
- ✅ UI status yang jelas (Siap Jaga/Tidak Jaga)
- ✅ Disable tombol check-in/out jika tidak memenuhi syarat

### 3. **Enhanced Error Handling**
- ✅ Pesan error yang spesifik dan informatif
- ✅ Logging untuk debugging
- ✅ Graceful degradation

## 🔧 Backend Implementation

### Controller Updates (`DokterDashboardController.php`)

#### Check-In Validation
```php
public function checkIn(Request $request)
{
    // VALIDASI JADWAL JAGA - Cek apakah dokter memiliki jadwal jaga hari ini
    $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
        ->whereDate('tanggal_jaga', $today)
        ->where('status_jaga', 'Aktif')
        ->with('shiftTemplate')
        ->first();

    if (!$jadwalJaga) {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki jadwal jaga hari ini. Hubungi admin untuk informasi lebih lanjut.',
            'code' => 'NO_SCHEDULE'
        ], 422);
    }

    // VALIDASI WAKTU JAGA - Cek apakah saat ini dalam jam jaga
    $shiftTemplate = $jadwalJaga->shiftTemplate;
    if ($shiftTemplate) {
        $startTime = Carbon::parse($shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($shiftTemplate->jam_pulang);
        $currentTimeOnly = $currentTime->format('H:i:s');
        
        // Handle overnight shifts (end time < start time)
        if ($endTime->format('H:i:s') < $startTime->format('H:i:s')) {
            // For overnight shifts, check if current time is after start OR before end
            if ($currentTimeOnly < $startTime->format('H:i:s') && $currentTimeOnly > $endTime->format('H:i:s')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saat ini bukan jam jaga Anda. Jadwal jaga: ' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i'),
                    'code' => 'OUTSIDE_SHIFT_HOURS'
                ], 422);
            }
        } else {
            // For regular shifts, check if current time is within shift hours
            if ($currentTimeOnly < $startTime->format('H:i:s') || $currentTimeOnly > $endTime->format('H:i:s')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saat ini bukan jam jaga Anda. Jadwal jaga: ' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i'),
                    'code' => 'OUTSIDE_SHIFT_HOURS'
                ], 422);
            }
        }
    }
}
```

#### Check-Out Validation
```php
public function checkOut(Request $request)
{
    // VALIDASI JADWAL JAGA - Cek apakah ada jadwal jaga yang terkait
    if ($attendance->jadwal_jaga_id) {
        $jadwalJaga = $attendance->jadwalJaga;
        $shiftTemplate = $jadwalJaga->shiftTemplate;
        
        // Same time validation logic as check-in
        // ...
    }
}
```

### Model Relationships

#### Attendance Model
```php
/**
 * Relationship dengan JadwalJaga untuk validasi schedule
 */
public function jadwalJaga(): BelongsTo
{
    return $this->belongsTo(JadwalJaga::class, 'jadwal_jaga_id');
}
```

#### JadwalJaga Model
```php
/**
 * Get effective start time (custom or from template)
 */
public function getEffectiveStartTimeAttribute(): string
{
    if ($this->jam_jaga_custom) {
        return \Carbon\Carbon::parse($this->jam_jaga_custom)->format('H:i');
    }
    
    return $this->shiftTemplate->jam_masuk_format;
}

/**
 * Get effective end time from template
 */
public function getEffectiveEndTimeAttribute(): string
{
    return $this->shiftTemplate->jam_pulang_format;
}
```

## 🎨 Frontend Implementation

### State Management
```typescript
const [scheduleData, setScheduleData] = useState({
  todaySchedule: null,
  currentShift: null,
  workLocation: null,
  isOnDuty: false,
  canCheckIn: false,
  canCheckOut: false,
  validationMessage: ''
});
```

### Schedule Loading
```typescript
// Fetch today's schedule with proper filtering
const scheduleResponse = await fetch('/api/v2/dashboards/dokter/jadwal-jaga');

// Filter today's schedule from the response
const today = new Date().toISOString().split('T')[0];
const todaySchedule = scheduleData.data?.filter((schedule: any) => 
  schedule.tanggal_jaga === today && schedule.status_jaga === 'Aktif'
) || [];

// Get current shift (first active schedule for today)
const currentShift = todaySchedule.length > 0 ? todaySchedule[0] : null;
```

### Time Validation Logic
```typescript
// Check if current time is within shift hours
let isWithinShiftHours = false;
if (scheduleData.currentShift && scheduleData.currentShift.shift_template) {
  const shiftTemplate = scheduleData.currentShift.shift_template;
  const startTime = shiftTemplate.jam_masuk; // Format: "08:00"
  const endTime = shiftTemplate.jam_pulang; // Format: "16:00"
  
  // Parse shift times
  const [startHour, startMinute] = startTime.split(':').map(Number);
  const [endHour, endMinute] = endTime.split(':').map(Number);
  
  // Convert to minutes for easier comparison
  const currentMinutes = currentHour * 60 + currentMinute;
  const startMinutes = startHour * 60 + startMinute;
  const endMinutes = endHour * 60 + endMinute;
  
  // Handle overnight shifts (end time < start time)
  if (endMinutes < startMinutes) {
    // For overnight shifts, check if current time is after start OR before end
    isWithinShiftHours = currentMinutes >= startMinutes || currentMinutes <= endMinutes;
  } else {
    // For regular shifts, check if current time is within shift hours
    isWithinShiftHours = currentMinutes >= startMinutes && currentMinutes <= endMinutes;
  }
}
```

### UI Components

#### Status Dashboard
```tsx
{/* Schedule Status */}
<div className="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl p-3 border border-blue-400/30">
  <div className="flex items-center space-x-2 mb-2">
    <Clock className="w-4 h-4 text-blue-400" />
    <span className="text-sm font-medium text-blue-300">Jadwal Jaga</span>
  </div>
  {scheduleData.currentShift ? (
    <div className="text-white text-sm">
      <div>🕐 {scheduleData.currentShift.shift_template?.jam_masuk || '08:00'} - {scheduleData.currentShift.shift_template?.jam_pulang || '16:00'}</div>
      <div>📍 {scheduleData.currentShift.unit_kerja || 'Dokter Jaga'}</div>
      <div>👨‍⚕️ {scheduleData.currentShift.shift_template?.nama_shift || 'Shift'}</div>
    </div>
  ) : (
    <div className="text-red-300 text-sm">❌ Tidak ada jadwal jaga hari ini</div>
  )}
</div>
```

#### Button States
```tsx
<button 
  onClick={handleCheckIn}
  disabled={isCheckedIn || !scheduleData.canCheckIn}
  className={`relative group p-4 sm:p-5 md:p-6 lg:p-8 rounded-2xl sm:rounded-3xl transition-all duration-500 transform ${
    isCheckedIn || !scheduleData.canCheckIn
      ? 'opacity-50 cursor-not-allowed' 
      : 'hover:scale-105 active:scale-95'
  }`}
>
```

## 📊 Database Schema

### JadwalJaga Table
```sql
CREATE TABLE jadwal_jagas (
    id BIGINT PRIMARY KEY,
    tanggal_jaga DATE NOT NULL,
    shift_template_id BIGINT REFERENCES shift_templates(id),
    pegawai_id BIGINT REFERENCES users(id),
    unit_kerja ENUM('Dokter Jaga', 'Pendaftaran', 'Pelayanan'),
    peran ENUM('Dokter', 'Paramedis', 'NonParamedis'),
    status_jaga ENUM('Aktif', 'Cuti', 'Izin', 'OnCall') DEFAULT 'Aktif',
    jam_masuk_custom TIME NULL,
    jam_pulang_custom TIME NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    INDEX idx_tanggal_pegawai (tanggal_jaga, pegawai_id),
    INDEX idx_pegawai_status (pegawai_id, status_jaga),
    
    -- Unique constraint
    UNIQUE KEY unique_schedule (tanggal_jaga, pegawai_id, shift_template_id)
);
```

### Attendance Table
```sql
ALTER TABLE attendances ADD COLUMN jadwal_jaga_id BIGINT REFERENCES jadwal_jagas(id);
```

## 🔒 Security & Validation

### Validation Rules
1. **Schedule Existence**: Dokter harus memiliki jadwal jaga hari ini
2. **Schedule Status**: Jadwal jaga harus berstatus 'Aktif'
3. **Time Validation**: Check-in/out hanya dalam jam jaga
4. **Overnight Shift Support**: Mendukung shift malam (22:00 - 06:00)
5. **Attendance Linking**: Attendance terhubung dengan jadwal jaga

### Error Codes
- `NO_SCHEDULE`: Tidak ada jadwal jaga hari ini
- `OUTSIDE_SHIFT_HOURS`: Di luar jam jaga
- `ALREADY_CHECKED_IN`: Sudah check-in hari ini
- `NOT_CHECKED_IN`: Belum check-in

## 📱 User Experience

### Flow Presensi dengan Validasi
1. **Load Schedule**: Sistem memuat jadwal jaga hari ini
2. **Validate Status**: Mengecek apakah dokter dapat melakukan presensi
3. **Display Status**: Menampilkan status jadwal jaga
4. **Enable/Disable Buttons**: Tombol sesuai validasi
5. **GPS Validation**: Validasi lokasi GPS
6. **Backend Validation**: Double-check di server
7. **Success/Error**: Feedback yang jelas

### Pesan Validasi
- ❌ "Anda tidak memiliki jadwal jaga hari ini. Hubungi admin untuk informasi lebih lanjut."
- ❌ "Saat ini bukan jam jaga Anda. Jadwal jaga: 08:00 - 16:00"
- ❌ "Anda sudah check-in hari ini"
- ❌ "Belum check-in atau sudah check-out"

## 🚀 Deployment

### Backend Requirements
- Laravel 10+ dengan Carbon
- Database dengan timezone Asia/Jakarta
- Proper indexing untuk performance

### Frontend Requirements
- React dengan TypeScript
- Real-time time validation
- Responsive UI components

## 📊 Monitoring & Logging

### Logs
```php
\Log::error('Check-in error for user ' . $user->id, [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

### Metrics
- Schedule validation success rate
- Check-in/out compliance rate
- Error frequency by type
- Performance metrics

## 🔄 Future Enhancements

### Planned Features
- [ ] Real-time schedule updates via WebSocket
- [ ] Schedule conflict detection
- [ ] Automatic schedule assignment
- [ ] Schedule notification system
- [ ] Offline schedule caching
- [ ] Schedule analytics dashboard

### Technical Improvements
- [ ] Optimize database queries
- [ ] Add caching for schedule data
- [ ] Implement retry mechanism
- [ ] Add unit tests
- [ ] Performance optimization

## 📝 Notes

- Sistem ini memastikan dokter hanya dapat melakukan presensi sesuai jadwal yang ditugaskan
- Validasi dilakukan di frontend dan backend untuk keamanan maksimal
- Mendukung berbagai jenis shift (pagi, siang, malam, overnight)
- UI memberikan feedback yang jelas tentang status validasi
- Sistem dapat menangani berbagai skenario error dengan graceful degradation
