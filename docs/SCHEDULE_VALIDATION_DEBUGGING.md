# Schedule Validation Debugging Guide

## 🐛 Masalah yang Ditemukan

### 1. **Schedule Existence: Dokter harus memiliki jadwal jaga hari ini**
- ❌ Dokter tidak memiliki jadwal jaga untuk hari ini
- ❌ Jadwal jaga ada tapi tidak terdeteksi oleh sistem

### 2. **Schedule Status: Jadwal jaga harus berstatus 'Aktif'**
- ❌ Jadwal jaga ada tapi status bukan 'Aktif'
- ❌ Status jadwal jaga: 'Cuti', 'Izin', 'OnCall'

### 3. **Time Validation: Check-in/out hanya dalam jam jaga**
- ❌ Waktu check-in/out di luar jam jaga
- ❌ Shift malam tidak terdeteksi dengan benar

## 🔧 Solusi yang Diimplementasikan

### 1. **Debug Endpoint**
```bash
GET /api/v2/dashboards/dokter/debug-schedule
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "dr. Yaya Mulyana, M.Kes",
      "email": "yaya@example.com",
      "role": "dokter"
    },
    "today": "2025-01-27",
    "current_time": "2025-01-27 14:30:00",
    "all_schedules_today": [...],
    "active_schedule_today": {
      "id": 1,
      "tanggal_jaga": "2025-01-27",
      "status_jaga": "Aktif",
      "shift_template": {
        "id": 1,
        "nama_shift": "Shift Pagi",
        "jam_masuk": "08:00",
        "jam_pulang": "16:00"
      }
    },
    "attendance_today": null,
    "debug_info": {
      "total_schedules": 1,
      "active_schedules": 1,
      "has_active_schedule": true,
      "has_attendance": false
    }
  }
}
```

### 2. **Seeder untuk Testing**
```bash
php artisan db:seed --class=CreateYayaScheduleSeeder
```

**Seeder akan:**
- ✅ Mencari user Yaya
- ✅ Membuat shift template jika belum ada
- ✅ Membuat jadwal jaga untuk hari ini
- ✅ Membuat jadwal jaga untuk 7 hari ke depan
- ✅ Memastikan status jadwal jaga 'Aktif'

### 3. **Fixed API Response**
```json
{
  "success": true,
  "data": {
    "today": [...], // Jadwal jaga hari ini
    "currentShift": {...}, // Jadwal jaga aktif hari ini
    "weekly_schedule": [...],
    "calendar_events": [...]
  }
}
```

## 🧪 Testing Steps

### Step 1: Check User Data
```bash
# Cek apakah user Yaya ada
curl -X GET "http://localhost/api/v2/dashboards/dokter/debug-schedule" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Step 2: Create Schedule
```bash
# Jalankan seeder untuk membuat jadwal jaga
php artisan db:seed --class=CreateYayaScheduleSeeder
```

### Step 3: Verify Schedule
```bash
# Cek lagi setelah seeder
curl -X GET "http://localhost/api/v2/dashboards/dokter/debug-schedule" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Step 4: Test Check-in
```bash
# Test check-in dengan jadwal jaga
curl -X POST "http://localhost/api/v2/dashboards/dokter/checkin" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -7.8481,
    "longitude": 112.0178,
    "accuracy": 10,
    "location": "RS. Kediri Medical Center"
  }'
```

## 🔍 Database Queries untuk Debugging

### 1. Cek User Yaya
```sql
SELECT id, name, email, created_at 
FROM users 
WHERE name LIKE '%Yaya%' OR email LIKE '%yaya%';
```

### 2. Cek Jadwal Jaga Hari Ini
```sql
SELECT 
    jj.id,
    jj.tanggal_jaga,
    jj.pegawai_id,
    jj.status_jaga,
    jj.unit_kerja,
    jj.peran,
    st.nama_shift,
    st.jam_masuk,
    st.jam_pulang,
    u.name as user_name
FROM jadwal_jagas jj
JOIN users u ON jj.pegawai_id = u.id
LEFT JOIN shift_templates st ON jj.shift_template_id = st.id
WHERE jj.tanggal_jaga = CURDATE()
AND u.name LIKE '%Yaya%';
```

### 3. Cek Attendance Hari Ini
```sql
SELECT 
    a.id,
    a.user_id,
    a.date,
    a.time_in,
    a.time_out,
    a.status,
    a.jadwal_jaga_id,
    u.name as user_name
FROM attendances a
JOIN users u ON a.user_id = u.id
WHERE a.date = CURDATE()
AND u.name LIKE '%Yaya%';
```

## 🛠️ Troubleshooting

### Problem 1: User tidak ditemukan
**Solution:**
```bash
# Cek apakah user ada
php artisan tinker
>>> User::where('name', 'like', '%Yaya%')->first();
```

### Problem 2: Jadwal jaga tidak ada
**Solution:**
```bash
# Jalankan seeder
php artisan db:seed --class=CreateYayaScheduleSeeder
```

### Problem 3: Status jadwal jaga bukan 'Aktif'
**Solution:**
```sql
-- Update status jadwal jaga
UPDATE jadwal_jagas 
SET status_jaga = 'Aktif' 
WHERE pegawai_id = (SELECT id FROM users WHERE name LIKE '%Yaya%')
AND tanggal_jaga = CURDATE();
```

### Problem 4: Shift template tidak ada
**Solution:**
```sql
-- Cek shift template
SELECT * FROM shift_templates;

-- Buat shift template jika tidak ada
INSERT INTO shift_templates (nama_shift, jam_masuk, jam_pulang, durasi_jam, warna) 
VALUES ('Shift Pagi', '08:00', '16:00', 8, '#10b981');
```

## 📊 Monitoring

### Logs untuk Debugging
```php
\Log::info('Schedule validation', [
    'user_id' => $user->id,
    'user_name' => $user->name,
    'today' => $today->format('Y-m-d'),
    'schedules_found' => $jadwalJaga->count(),
    'active_schedules' => $jadwalJaga->where('status_jaga', 'Aktif')->count()
]);
```

### Frontend Debug Info
```javascript
// Di browser console
fetch('/api/v2/dashboards/dokter/debug-schedule')
  .then(response => response.json())
  .then(data => console.log('Debug Schedule:', data));
```

## ✅ Expected Results

### Setelah Seeder Berhasil:
1. ✅ User Yaya ditemukan
2. ✅ Shift template 'Shift Pagi' dibuat
3. ✅ Jadwal jaga hari ini dibuat dengan status 'Aktif'
4. ✅ Jadwal jaga 7 hari ke depan dibuat
5. ✅ Debug endpoint menampilkan data lengkap

### Setelah Validasi Berhasil:
1. ✅ Frontend menampilkan "Siap Jaga"
2. ✅ Tombol check-in enabled
3. ✅ Check-in berhasil dengan validasi waktu
4. ✅ Check-out berhasil dengan validasi waktu

## 🚨 Common Issues

### Issue 1: Timezone Mismatch
**Problem:** Database timezone berbeda dengan aplikasi
**Solution:**
```php
// Di config/app.php
'timezone' => 'Asia/Jakarta',
```

### Issue 2: Date Format Mismatch
**Problem:** Format tanggal tidak konsisten
**Solution:**
```php
// Gunakan Carbon untuk konsistensi
$today = Carbon::today();
$dateString = $today->format('Y-m-d');
```

### Issue 3: Relationship Issues
**Problem:** Relasi model tidak terdefinisi dengan benar
**Solution:**
```php
// Pastikan relasi ada di model
public function jadwalJagas(): HasMany
{
    return $this->hasMany(JadwalJaga::class, 'pegawai_id');
}
```

## 📝 Notes

- Debug endpoint tersedia di `/api/v2/dashboards/dokter/debug-schedule`
- Seeder otomatis membuat jadwal jaga untuk testing
- Validasi dilakukan di frontend dan backend
- Logs tersedia untuk monitoring
- Database queries tersedia untuk manual debugging
