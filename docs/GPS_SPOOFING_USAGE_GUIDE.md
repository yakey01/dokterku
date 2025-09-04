# 🛡️ **GPS Spoofing Detection - Panduan Penggunaan**

## 📋 **Daftar Isi**
1. [Akses Dashboard Admin](#1-akses-dashboard-admin)
2. [Cara Kerja Otomatis](#2-cara-kerja-otomatis)
3. [Penggunaan Manual Form](#3-penggunaan-manual-form)
4. [Implementasi di Routes Presensi](#4-implementasi-di-routes-presensi)
5. [Monitoring dan Alert](#5-monitoring-dan-alert)
6. [Troubleshooting](#6-troubleshooting)

---

## **1. 📱 Akses Dashboard Admin**

### **Menu Navigasi:**
```
Admin Panel → Presensi → GPS Spoofing Detection
```

### **Badge Notification:**
- **Red Badge**: 10+ deteksi belum direview
- **Orange Badge**: 5-10 deteksi belum direview  
- **Blue Badge**: 1-5 deteksi belum direview

---

## **2. 🤖 Cara Kerja Otomatis (Recommended)**

### **Sistem Berjalan Otomatis Ketika:**
✅ User melakukan check-in/check-out presensi
✅ Request mengandung data latitude & longitude
✅ Middleware `anti.gps.spoofing` aktif di route

### **Proses Otomatis:**
1. **Real-time Analysis**: Setiap request presensi dianalisis
2. **Multi-layer Detection**: 6 metode deteksi berbeda
3. **Risk Scoring**: Otomatis hitung skor 0-100%
4. **Auto Blocking**: Blokir jika skor ≥80% atau spoofing terdeteksi
5. **Instant Alerts**: Notifikasi real-time ke admin
6. **Database Logging**: Simpan semua data untuk audit

### **Data yang Dianalisis Otomatis:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8238,
  "accuracy": 15.5,
  "device_id": "auto-generated-hash",
  "mock_location_enabled": false,
  "developer_mode_enabled": false,
  "fake_gps_apps": [],
  "travel_speed": 45.2
}
```

---

## **3. 📝 Penggunaan Manual Form**

### **Kapan Gunakan Mode Manual:**
- 🧪 **Testing**: Simulasi deteksi spoofing
- 📊 **Historical Data**: Input data lama
- 🔍 **Investigation**: Review kasus khusus
- 📝 **Training**: Demo untuk staff

### **Cara Mengisi Form Manual:**

#### **🚨 Section: Informasi Deteksi**
```
👤 User*: Pilih user dari dropdown
📱 Device ID: Opsional (auto-generate jika kosong)
🌐 IP Address*: IP address user (wajib)
⚠️ Risk Level*: Pilih Rendah/Sedang/Tinggi/Kritis
📊 Risk Score*: Input 0-100 (skor risiko)
🎯 GPS Spoofed: Toggle ON jika GPS palsu
🚫 Blocked: Toggle ON jika user diblokir
```

#### **📍 Section: Data Lokasi**
```
- Klik pada peta untuk set koordinat
- Atau input manual latitude/longitude
- Isi akurasi, altitude, speed (opsional)
```

#### **🔍 Section: Hasil Deteksi**
```
Toggle ON metode yang terdeteksi:
📍 Mock Location
📱 Fake GPS App  
⚙️ Developer Mode
🚀 Impossible Travel
📊 Coordinate Anomaly
🛡️ Device Integrity Failed
```

#### **🚨 Section: Tindakan & Review**
```
🚨 Tindakan: Pilih None/Warning/Blocked/Flagged
📝 Catatan Admin: Penjelasan detail
👤 Direview Oleh: Admin yang handle kasus
```

---

## **4. 🛠️ Implementasi di Routes Presensi**

### **A. Tambah Middleware ke Specific Routes:**

```php
// routes/web.php
Route::middleware(['auth', 'anti.gps.spoofing'])->group(function () {
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
         ->name('attendance.check-in');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
         ->name('attendance.check-out');
});

// routes/api.php  
Route::middleware(['auth:sanctum', 'anti.gps.spoofing'])->group(function () {
    Route::post('/attendance/check-in', [ApiAttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [ApiAttendanceController::class, 'checkOut']);
});
```

### **B. Data yang Harus Dikirim Frontend:**

#### **Minimum Required:**
```javascript
{
  latitude: -6.2088200,    // Required
  longitude: 106.8238800,  // Required
  timestamp: "2025-07-11T22:45:00Z"
}
```

#### **Enhanced Detection (Recommended):**
```javascript
{
  // Basic Location
  latitude: -6.2088200,
  longitude: 106.8238800,
  accuracy: 15.5,
  altitude: 25.0,
  speed: 0.0,
  heading: 180.0,
  timestamp: "2025-07-11T22:45:00Z",
  location_source: "gps", // gps, network, passive
  
  // Device Fingerprint (Enhanced Security)
  device_id: "unique-device-identifier",
  platform: "android", // android, ios, web
  screen_resolution: "1080x1920",
  timezone: "Asia/Jakarta",
  
  // Security Flags
  mock_location_enabled: false,
  developer_mode_enabled: false,
  usb_debugging_enabled: false,
  unknown_sources_enabled: false,
  is_rooted: false,
  is_emulator: false,
  system_integrity: true,
  
  // App Detection
  installed_apps: ["com.whatsapp", "com.instagram"],
  suspicious_apps: []
}
```

### **C. Response dari Middleware:**

#### **Success Response:**
```json
{
  "success": true,
  "message": "Presensi berhasil",
  "gps_detection": {
    "risk_level": "low",
    "risk_score": 15,
    "is_spoofed": false
  }
}
```

#### **Blocked Response:**
```json
{
  "success": false,
  "error": "GPS_SPOOFING_DETECTED",
  "message": "🚫 Presensi diblokir karena terdeteksi GPS spoofing!\n\nAlasan: 📱 Aplikasi GPS palsu terdeteksi, ⚙️ Developer mode aktif\n\n⚠️ Risk Level: High\n📊 Risk Score: 85%\n\n💡 Nonaktifkan aplikasi GPS palsu dan developer mode, kemudian coba lagi.",
  "details": {
    "risk_level": "high",
    "risk_score": 85,
    "detected_methods": ["fake_gps_app", "developer_mode"],
    "action_taken": "blocked"
  },
  "blocked_at": "2025-07-11T22:45:00.000Z"
}
```

---

## **5. 📊 Monitoring dan Alert**

### **Real-time Notifications:**
- 🔔 **Pop-up Filament**: Notifikasi instant di dashboard
- 📧 **Email Alert**: Dikirim ke semua admin
- 💾 **Database Log**: Tersimpan untuk audit

### **Dashboard Features:**
- 📈 **Statistics**: Total, spoofed, blocked, unreviewed
- 🗺️ **Map View**: Lihat lokasi spoofing di peta
- 🔍 **Advanced Filter**: Filter by risk level, date, user
- ⚡ **Real-time Update**: Auto refresh setiap 30 detik

### **Admin Actions:**
- ✅ **Mark Reviewed**: Tandai sudah direview
- 🚫 **Block User**: Blokir user langsung
- 🗺️ **View Map**: Lihat lokasi di Google Maps
- 📝 **Add Notes**: Tambah catatan investigasi

---

## **6. 🔧 Troubleshooting**

### **Problem: Middleware tidak berjalan**
```bash
# Pastikan middleware terdaftar
php artisan route:list | grep anti.gps.spoofing

# Clear cache
php artisan config:clear
php artisan route:clear
```

### **Problem: Notifikasi tidak terkirim**
```bash
# Pastikan queue worker berjalan
php artisan queue:work

# Check admin users
php artisan tinker
>>> App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->count()
```

### **Problem: False positive detection**
- Adjust risk thresholds di `GpsSpoofingDetectionService`
- Review detection logic untuk environmental factors
- Whitelist trusted devices/locations

### **Problem: Database error**
```bash
# Run migration
php artisan migrate

# Check table structure
php artisan tinker
>>> Schema::hasTable('gps_spoofing_detections')
```

---

## **🎯 Quick Start Checklist**

### **Setup (One-time):**
- ✅ Migration sudah dijalankan
- ✅ Middleware terdaftar di `bootstrap/app.php`
- ✅ Admin users ada di database
- ✅ Queue worker berjalan (optional untuk email)

### **Usage:**
- ✅ Tambah middleware ke routes presensi
- ✅ Frontend kirim data location lengkap
- ✅ Monitor dashboard secara berkala
- ✅ Review deteksi yang flagged

### **Testing:**
- ✅ Test dengan fake GPS app
- ✅ Test dengan developer mode ON
- ✅ Test dengan koordinat (0,0)
- ✅ Verify email notifications

---

## **📱 Contoh Implementation Frontend**

### **JavaScript (Web):**
```javascript
async function submitAttendance(type) {
    // Get GPS location
    const position = await getCurrentPosition();
    
    // Prepare data
    const attendanceData = {
        type: type, // 'check_in' or 'check_out'
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
        timestamp: new Date().toISOString(),
        
        // Enhanced security data
        mock_location_enabled: await checkMockLocation(),
        developer_mode_enabled: await checkDeveloperMode(),
        platform: 'web',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
    
    // Submit to server
    try {
        const response = await fetch('/attendance/' + type, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(attendanceData)
        });
        
        if (!response.ok) {
            const error = await response.json();
            if (error.error === 'GPS_SPOOFING_DETECTED') {
                alert(error.message);
                return;
            }
        }
        
        const result = await response.json();
        alert('Presensi berhasil!');
        
    } catch (error) {
        console.error('Attendance error:', error);
    }
}
```

---

## **📞 Support**

Jika ada masalah atau pertanyaan:
1. Check log: `storage/logs/laravel.log`
2. Verify database: Tabel `gps_spoofing_detections`
3. Test middleware: Use Postman dengan data GPS
4. Contact admin: Review dashboard untuk deteksi terbaru

**GPS Spoofing Detection System siap digunakan!** 🚀