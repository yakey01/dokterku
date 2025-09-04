# Work Location Tolerance Analysis

## Masalah yang Ditemukan

### 1. Inkonsistensi Akses Field Toleransi

Ada dua pola akses yang berbeda dalam kode:

#### Pola 1: Direct Field Access
```javascript
wl?.checkin_before_shift_minutes
wl?.late_tolerance_minutes  
wl?.checkout_after_shift_minutes
```

#### Pola 2: Nested tolerance_settings
```javascript
wl?.tolerance_settings?.checkin_before_shift_minutes
wl?.tolerance_settings?.checkout_after_shift_minutes
```

### 2. Lokasi Penggunaan Toleransi

#### A. Line 818-819 (Shift Selection Logic)
```javascript
const toleranceMinutes = wl?.checkin_before_shift_minutes || 
                         wl?.tolerance_settings?.checkin_before_shift_minutes || 
                         30;
```
- Mengecek kedua pola
- Default: 30 menit

#### B. Line 1300-1305 (Check-in Window)
```javascript
const earlyBeforeMin = Number.isFinite(Number(wl?.checkin_before_shift_minutes))
  ? Number(wl.checkin_before_shift_minutes)
  : 30;  // DEFAULT

const lateTolMin = Number.isFinite(Number(wl?.late_tolerance_minutes))
  ? Number(wl.late_tolerance_minutes)
  : 15;  // DEFAULT
```
- **MASALAH**: Hanya cek direct field, TIDAK cek tolerance_settings
- Jika data dari API ada di tolerance_settings, akan selalu pakai default

#### C. Line 1349-1353 (Check-out Window)
```javascript
const afterShiftTol = Number.isFinite(Number(wl?.checkout_after_shift_minutes))
  ? Number(wl.checkout_after_shift_minutes)
  : (Number.isFinite(Number(wl?.tolerance_settings?.checkout_after_shift_minutes))
    ? Number(wl.tolerance_settings.checkout_after_shift_minutes)
    : 60);
```
- Mengecek kedua pola
- Default: 60 menit

## Kesimpulan

**TOLERANSI TIDAK BEKERJA DENGAN BENAR** untuk check-in karena:

1. **Check-in tolerances** (line 1300-1305) hanya mengecek direct field, tidak mengecek `tolerance_settings`
2. Jika API mengirim data dalam format `tolerance_settings`, nilai akan diabaikan dan pakai default
3. Inkonsistensi dengan check-out yang mengecek kedua format

## Solusi yang Diperlukan

Perlu update line 1300-1305 untuk konsisten dengan pola lain:

```javascript
// Check-in before shift tolerance
const earlyBeforeMin = Number.isFinite(Number(wl?.checkin_before_shift_minutes))
  ? Number(wl.checkin_before_shift_minutes)
  : (Number.isFinite(Number(wl?.tolerance_settings?.checkin_before_shift_minutes))
    ? Number(wl.tolerance_settings.checkin_before_shift_minutes)
    : 30);

// Late check-in tolerance  
const lateTolMin = Number.isFinite(Number(wl?.late_tolerance_minutes))
  ? Number(wl.late_tolerance_minutes)
  : (Number.isFinite(Number(wl?.tolerance_settings?.late_tolerance_minutes))
    ? Number(wl.tolerance_settings.late_tolerance_minutes)
    : 15);
```

## API Response Structure (Perlu Verifikasi)

Kemungkinan struktur dari API:
```json
{
  "work_location": {
    "id": 1,
    "name": "RS Kediri",
    "latitude": -7.848016,
    "longitude": 112.017829,
    "radius": 100,
    "tolerance_settings": {
      "checkin_before_shift_minutes": 30,
      "late_tolerance_minutes": 15,
      "checkout_after_shift_minutes": 60,
      "early_departure_tolerance_minutes": 15
    }
  }
}
```

Atau mungkin flat:
```json
{
  "work_location": {
    "id": 1,
    "name": "RS Kediri",
    "checkin_before_shift_minutes": 30,
    "late_tolerance_minutes": 15,
    "checkout_after_shift_minutes": 60
  }
}
```

## Impact

Jika work location mengirim toleransi dalam `tolerance_settings`, maka:
- ✅ Check-out tolerance akan bekerja
- ❌ Check-in tolerance TIDAK akan bekerja (selalu pakai default 30 & 15 menit)