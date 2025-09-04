# Fix untuk Masalah "Masih ada presensi yang belum check-out"

## Masalah
Error **"Check-in gagal: HTTP 422 - Masih ada presensi yang belum check-out untuk shift sebelumnya"** muncul walaupun tombol check-out sudah diklik.

## Penyebab Utama

### 1. Logic Check-in Hanya Cek Hari Ini
Kode lama hanya mengecek presensi hari ini:
```php
// KODE LAMA - Hanya cek hari ini
$openAttendance = Attendance::where('user_id', $user->id)
    ->whereDate('date', $today)  // ← Masalah: hanya hari ini
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->first();
```

**Masalah**: Jika dokter lupa check-out kemarin, sistem tidak mendeteksinya dan error tetap muncul.

### 2. Check-out Bisa Gagal Tanpa Feedback
- Check-out diklik tapi gagal (koneksi, timeout)
- Browser tertutup sebelum proses selesai
- Session expired saat check-out

## Solusi Implementasi

### 1. Perbaikan Logic Check-in (Line 925-952)
```php
// KODE BARU - Cek 7 hari ke belakang
$openAttendance = Attendance::where('user_id', $user->id)
    ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->first();

if ($openAttendance) {
    $attendanceDate = Carbon::parse($openAttendance->date);
    $dateStr = $attendanceDate->isToday() ? 'hari ini' : 
              ($attendanceDate->isYesterday() ? 'kemarin' : 
               $attendanceDate->format('d M Y'));
    
    return response()->json([
        'success' => false,
        'message' => "Masih ada presensi yang belum check-out dari $dateStr. Silakan check-out terlebih dahulu atau hubungi admin.",
        'code' => 'OPEN_ATTENDANCE_EXISTS',
        'data' => [
            'open_attendance' => [
                'date' => $openAttendance->date->format('Y-m-d'),
                'time_in' => $openAttendance->time_in?->format('H:i'),
                'jadwal_jaga_id' => $openAttendance->jadwal_jaga_id,
            ]
        ]
    ], 422);
}
```

### 2. Perbaikan Logic Check-out (Line 1041-1048)
```php
// KODE BARU - Bisa check-out presensi lama
$attendance = Attendance::where('user_id', $user->id)
    ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->with('jadwalJaga.shiftTemplate')
    ->first();
```

### 3. Script Helper untuk Fix Manual
Created: `public/fix-unclosed-attendance.php`

**Fungsi**:
- Deteksi semua presensi yang belum check-out
- Auto-close presensi lama dengan waktu sesuai jadwal
- Dry-run mode untuk review sebelum fix

**Cara Pakai**:
```bash
# Check unclosed attendance (dry run)
php public/fix-unclosed-attendance.php

# Auto-fix unclosed attendance
# Edit file: set $AUTO_FIX = true
php public/fix-unclosed-attendance.php
```

## Cara Mengatasi untuk User

### Jika Error Muncul:

#### Option 1: Check-out Manual
1. Refresh halaman presensi
2. Cari tombol check-out yang aktif
3. Klik check-out untuk presensi lama
4. Tunggu konfirmasi berhasil
5. Baru bisa check-in shift baru

#### Option 2: Hubungi Admin
Minta admin untuk:
```bash
# Jalankan script fix
php public/fix-unclosed-attendance.php

# Atau manual close via database
UPDATE attendances 
SET time_out = NOW(), 
    work_duration_minutes = TIMESTAMPDIFF(MINUTE, time_in, NOW())
WHERE user_id = [USER_ID] 
  AND time_out IS NULL;
```

## Improvement Recommendations

### 1. Auto Check-out System
Implement automatic check-out setelah shift berakhir + buffer:
```php
// Scheduled command to run every hour
$overdueAttendances = Attendance::whereNull('time_out')
    ->whereHas('jadwalJaga', function($q) {
        $q->where('tanggal_jaga', '<', Carbon::today())
          ->orWhere(function($q2) {
            // Shift sudah lewat 2 jam
          });
    })->get();

foreach ($overdueAttendances as $attendance) {
    // Auto close with shift end time
}
```

### 2. Better Error Messages
Pesan error sekarang sudah diperbaiki:
- ❌ Lama: "Masih ada presensi yang belum check-out untuk shift sebelumnya"
- ✅ Baru: "Masih ada presensi yang belum check-out dari **kemarin**. Silakan check-out terlebih dahulu atau hubungi admin."

### 3. Frontend Improvements
- Show banner if ada unclosed attendance
- Quick action button untuk check-out lama
- Auto-refresh setelah check-out berhasil

## Testing

### Test Scenarios:
1. ✅ Normal flow: check-in → check-out → check-in baru
2. ✅ Lupa check-out hari ini → bisa check-out dulu
3. ✅ Lupa check-out kemarin → terdeteksi dan bisa diselesaikan
4. ✅ Multiple unclosed → ambil yang terbaru untuk check-out

## Summary

**Problem Solved**:
- ✅ Deteksi presensi lama yang belum check-out (sampai 7 hari)
- ✅ Bisa check-out presensi dari hari sebelumnya
- ✅ Pesan error lebih jelas dengan tanggal
- ✅ Script helper untuk admin fix manual
- ✅ Data diagnostik untuk troubleshooting

**Files Modified**:
- `app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php`
- Created: `public/fix-unclosed-attendance.php`

**Next Steps**:
1. Deploy changes ke production
2. Monitor untuk pastikan tidak ada issue baru
3. Consider implement auto check-out system
4. Add notification untuk remind check-out