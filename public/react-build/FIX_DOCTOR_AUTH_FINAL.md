# Final Fix untuk Doctor Authentication Issue

## Masalah yang Ditemukan
1. **Device Limit**: API v2 login memiliki device limit yang ketat
2. **Authentication Mismatch**: Web-api routes memerlukan session auth, bukan Bearer token

## Solusi Final

### 1. Gunakan Session Authentication
Doctor mobile app harus menggunakan session authentication (bukan API token) karena:
- Web-api routes (`/dokter/web-api/*`) menggunakan middleware `['auth', 'role:dokter']`
- Ini memerlukan Laravel session cookie, bukan Bearer token

### 2. Cara Login yang Benar
User dokter harus login melalui:
1. **Web Login Form** di `/login` dengan credentials `3333@dokter.local / password`
2. Ini akan membuat Laravel session cookie
3. Kemudian navigasi ke `/dokter/mobile-app` akan bekerja

### 3. Perubahan yang Dibuat
Mengembalikan endpoint ke web-api routes:
- `/api/v2/dashboards/dokter/checkin` → `/dokter/web-api/checkin`
- `/api/v2/dashboards/dokter/checkout` → `/dokter/web-api/checkout`
- `/api/v2/dashboards/dokter/presensi` → `/dokter/web-api/presensi-data`

### 4. Testing Tools
- `/test-session-bypass.html` - Test session authentication

## Langkah-langkah Debugging

### Step 1: Pastikan User Login
```
1. Buka /login
2. Login dengan 3333@dokter.local / password
3. Akan redirect otomatis ke /dokter/mobile-app
```

### Step 2: Test Session
```
1. Buka /test-session-bypass.html
2. Klik "Check Session" - harus menunjukkan user dokter
3. Test web-api endpoints
```

### Step 3: Test di Mobile App
```
1. Navigate ke /dokter/mobile-app
2. Buka presensi tab
3. Coba check-in dengan map
```

## Root Cause Analysis
- Doctor mobile app route (`/dokter/mobile-app`) membuat Bearer token
- Tapi web-api routes tidak menggunakan Bearer token auth
- Routes menggunakan session middleware: `['auth', 'role:dokter']`
- Solution: Pastikan session login dulu sebelum akses mobile app

## Expected Behavior
1. User login via web form → Laravel session created
2. Navigate to mobile app → Token dibuat untuk API calls (optional)
3. Web-api calls menggunakan session cookie (credentials: 'include')
4. Check-in berhasil karena session valid

## Monitoring
Check Laravel logs untuk melihat authentication errors:
```bash
tail -f storage/logs/laravel.log
```

## Alternative Solution (Future)
Jika ingin menggunakan API v2:
1. Fix device limit di `UserDevice::autoRegisterDevice`
2. Update semua web-api routes ke API v2 format
3. Ensure proper Bearer token handling