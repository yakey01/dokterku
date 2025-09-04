# Deep Analysis: Mengapa Jadwal Yaya Menampilkan TES 2

## Executive Summary

**ROOT CAUSE DITEMUKAN**: 
1. ✅ **Yaya MEMILIKI jadwal hari ini** - Ada 3 jadwal untuk 10 Agustus 2025
2. ❌ **SEMUA jadwalnya menggunakan SHIFT TESTING** - Bukan shift normal
3. 🐛 **Frontend logic error** - Menampilkan "TES 2" padahal seharusnya "tes 6" yang sedang aktif

## Detailed Findings

### 1. Jadwal Yaya Hari Ini (10 Agustus 2025)

Dr. Yaya memiliki **3 jadwal jaga** hari ini:

| ID  | Shift Name | Jam Masuk | Jam Pulang | Status saat 18:16 |
|-----|------------|-----------|------------|-------------------|
| 115 | TES 2      | 15:40     | 15:59      | ❌ Sudah lewat    |
| 124 | Pagi       | 06:00     | 12:00      | ❌ Sudah lewat    |
| 145 | tes 6      | 18:00     | 18:30      | ✅ **AKTIF SEKARANG** |

### 2. Masalah yang Ditemukan

#### A. Database Penuh dengan Test Data
```json
Total Test Shifts di Database: 9
- Tes 1, TES 2, Tes 3, tes 4, tes 5, tes 6, tes 7, tes 8
- Shift Test Check-In

Jadwal menggunakan test shifts: 20+ records
Mayoritas untuk Dr. Yaya
```

#### B. Frontend Selection Logic Issue
Frontend menampilkan "TES 2" (15:40-15:59) padahal:
- Shift ini sudah lewat 2+ jam yang lalu
- Ada shift "tes 6" (18:00-18:30) yang **sedang aktif**

#### C. Date Format Confusion
Database menyimpan tanggal dengan timezone:
- `tanggal_jaga`: "2025-08-09T17:00:00.000000Z" 
- Ini sebenarnya adalah 10 Agustus 2025 (UTC+7)
- Frontend mungkin confused dengan timezone handling

### 3. Alur Data yang Seharusnya

```mermaid
graph TD
    A[Database: jadwal_jaga] -->|API| B[/api/v2/dashboards/dokter/jadwal-jaga]
    B --> C[Frontend: scheduleData]
    C --> D[validateCurrentStatus]
    D --> E[Determine currentShift]
    E --> F[Display in UI]
    
    G[Logic Priority] --> H[1. Active shift within time window]
    H --> I[2. Upcoming shift]
    I --> J[3. Most recent past shift]
```

### 4. Bug di Frontend Logic

File: `resources/js/components/dokter/Presensi.tsx`

```javascript
// Current logic shows:
// 1. Checks for current shift
// 2. If not found, shows nearest/upcoming
// 3. Falls back to most recent past

// BUG: "TES 2" dipilih sebagai "most recent past" 
// padahal "tes 6" sedang aktif
```

### 5. Data Lengkap Jadwal Yaya Minggu Ini

```
Total: 14 jadwal
Semua menggunakan shift testing (Tes 1, TES 2, tes 3, dll)
Tanggal: 5-10 Agustus 2025
```

## Root Cause Analysis

### Primary Issues:
1. **Test Data Pollution**: Database production terpolusi dengan shift testing
2. **Frontend Logic Bug**: Salah memilih shift yang akan ditampilkan
3. **No Data Validation**: Tidak ada filter untuk exclude test data

### Contributing Factors:
1. **Timezone Handling**: Tanggal disimpan dalam UTC tapi displayed in local time
2. **Multiple Test Schedules**: Yaya memiliki multiple overlapping test schedules
3. **Selection Priority**: Logic pemilihan currentShift tidak optimal

## Solutions

### Immediate Actions:

#### 1. Hapus Semua Test Shift Templates
```sql
-- Check first
SELECT * FROM shift_templates WHERE nama_shift LIKE '%tes%' OR nama_shift LIKE '%test%';

-- Delete schedules using test shifts
DELETE FROM jadwal_jagas WHERE shift_template_id IN (
    SELECT id FROM shift_templates WHERE nama_shift LIKE '%tes%' OR nama_shift LIKE '%test%'
);

-- Delete test shift templates
DELETE FROM shift_templates WHERE nama_shift LIKE '%tes%' OR nama_shift LIKE '%test%';
```

#### 2. Fix Frontend Selection Logic
```javascript
// Priority should be:
// 1. Current active shift (within start-end time)
// 2. Upcoming shift (not started yet)
// 3. Most recent completed shift

// Add validation to exclude test shifts
if (shift.nama_shift?.toLowerCase().includes('tes')) {
    continue; // Skip test shifts
}
```

### Long-term Fixes:

1. **Add Validation Rules**:
   - Prevent creation of shifts with "test/tes" in production
   - Add `is_test` boolean field to shift_templates

2. **Improve Data Management**:
   - Separate test data from production
   - Use seeders for test data that can be rolled back

3. **Fix Timezone Handling**:
   - Ensure consistent timezone usage
   - Display times in user's local timezone

## Impact Assessment

### Current Impact:
- ✅ Check-in/checkout functionality still works
- ⚠️ Confusing UI showing wrong shift information
- ❌ Production data mixed with test data

### After Cleanup:
- Need to create proper shift schedules for affected users
- May need to reassign schedules that were using test shifts
- UI will show correct shift information

## Execution Plan

### Step 1: Backup Current Data
```bash
php artisan db:backup
```

### Step 2: Delete Test Shifts
Run the deletion script:
```
http://localhost:8000/delete-test-shifts.php?confirm=DELETE_TEST_SHIFTS
```

### Step 3: Create Proper Shifts
```php
// Create normal shifts
ShiftTemplate::create([
    ['nama_shift' => 'Pagi', 'jam_masuk' => '06:00', 'jam_pulang' => '14:00'],
    ['nama_shift' => 'Siang', 'jam_masuk' => '14:00', 'jam_pulang' => '22:00'],
    ['nama_shift' => 'Malam', 'jam_masuk' => '22:00', 'jam_pulang' => '06:00']
]);
```

### Step 4: Reassign Schedules
Update affected users with proper shift schedules

### Step 5: Fix Frontend Logic
Deploy the frontend fixes to properly select current shift

## Monitoring

After cleanup, monitor:
1. Check-in/checkout functionality
2. Shift display accuracy
3. User complaints about missing schedules
4. API response times

## Prevention

1. **Code Review**: Review all shift creation code
2. **Validation**: Add backend validation for shift names
3. **Testing Environment**: Separate test database
4. **Documentation**: Document proper shift management procedures