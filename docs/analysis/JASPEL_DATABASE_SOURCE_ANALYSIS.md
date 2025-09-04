# Jaspel Database Source Analysis - dr Rindang Fatihah Idana, M.Kes (Rp 1.177.000)

## 🔍 Database Table Sources Analysis

### Total Jaspel Calculation (Rp 1.177.000) Breakdown:

#### **Formula**: `Total Jaspel = Tindakan Jaspel + Pasien Harian Jaspel`

## 📊 Data Source 1: Tindakan Procedures

### Database Table: `tindakan`
**Query Logic**:
```sql
SELECT * FROM tindakan 
WHERE dokter_id = {dokter_id}  -- Found via user name mapping
AND status_validasi = 'disetujui'  -- Only validated procedures
-- + Date filters if applied
```

### Key Columns Used:
- **`dokter_id`** - Links to dokter table via `User.name` → `Dokter.nama_lengkap` matching
- **`jasa_dokter`** - Direct jaspel amount from validated tindakan
- **`status_validasi`** - Must be 'disetujui' for inclusion
- **`tanggal_tindakan`** - For date range filtering
- **`jenis_tindakan_id`** - Links to procedure types

### Calculation Logic:
```php
foreach ($tindakans as $tindakan) {
    $jaspelAmount = $tindakan->jasa_dokter ?? 0;  // Direct from tindakan.jasa_dokter column
    $totalTindakanJaspel += $jaspelAmount;
}
```

## 📊 Data Source 2: Pasien Harian

### Database Table: `jumlah_pasien_harian`
**Query Logic**:
```sql
SELECT * FROM jumlah_pasien_harian 
WHERE dokter_id = {dokter_id}  -- Same dokter mapping
AND input_by IN (SELECT id FROM users WHERE role = 'petugas')  -- Only petugas input
-- + Date filters if applied
```

### Key Columns Used:
- **`dokter_id`** - Links to dokter table
- **`jumlah_pasien_umum`** - Count of general patients
- **`jumlah_pasien_bpjs`** - Count of BPJS patients  
- **`jaspel_rupiah`** - Pre-calculated jaspel amount
- **`tanggal`** - For date range filtering
- **`input_by`** - Must be petugas role user

### Calculation Logic:
```php
foreach ($pasienRecords as $pasien) {
    $totalPasien = ($pasien->jumlah_pasien_umum ?? 0) + ($pasien->jumlah_pasien_bpjs ?? 0);
    
    // Priority: Use jaspel_rupiah if available, otherwise calculate
    $jaspelAmount = $pasien->jaspel_rupiah > 0 
        ? $pasien->jaspel_rupiah           // From jaspel_rupiah column
        : ($totalPasien * 2500);           // Calculated: Rp 2.500 per patient
    
    $totalPasienJaspel += $jaspelAmount;
}
```

## 🎯 Exact Database Columns for dr Rindang (ID: 14)

### Tindakan Jaspel Source:
```sql
-- Actual query for dr Rindang
SELECT t.jasa_dokter, t.tanggal_tindakan, jt.nama as jenis_tindakan
FROM tindakan t
JOIN jenis_tindakan jt ON t.jenis_tindakan_id = jt.id  
WHERE t.dokter_id = 1  -- dr Rindang's dokter_id
AND t.status_validasi = 'disetujui'
```

### Pasien Harian Jaspel Source:
```sql
-- Actual query for dr Rindang
SELECT 
    jumlah_pasien_umum,
    jumlah_pasien_bpjs, 
    jaspel_rupiah,
    tanggal
FROM jumlah_pasien_harian 
WHERE dokter_id = 1  -- dr Rindang's dokter_id
AND input_by IN (SELECT id FROM users WHERE role = 'petugas')
```

## ⚠️ Potential Discrepancy Sources

### 1. **User ID → Dokter ID Mapping**
```php
// Current mapping logic
$dokter = \App\Models\Dokter::where('nama_lengkap', 'like', '%' . $user->name . '%')
    ->orWhere('email', $user->email)
    ->first();
```
**Risk**: Name matching might map to wrong dokter if names are similar

### 2. **Data Validation Status**
**Tindakan**: Only `status_validasi = 'disetujui'` included
**Pasien Harian**: No validation status filter - includes all records

### 3. **Input Authority Filter**
**Tindakan**: No input_by filter
**Pasien Harian**: Only `input_by` with 'petugas' role

### 4. **Jaspel Calculation Methods**
**Tindakan**: Direct from `tindakan.jasa_dokter` column
**Pasien Harian**: Priority to `jaspel_rupiah` column, fallback to calculated amount

## 🔧 Data Consistency Solution

### Verify Exact Data for dr Rindang (ID: 14):
```sql
-- Check dokter mapping
SELECT u.id as user_id, u.name, d.id as dokter_id, d.nama_lengkap 
FROM users u 
LEFT JOIN dokters d ON d.nama_lengkap LIKE CONCAT('%', u.name, '%')
WHERE u.id = 14;

-- Check tindakan data
SELECT COUNT(*) as tindakan_count, SUM(jasa_dokter) as tindakan_total
FROM tindakan 
WHERE dokter_id = (SELECT id FROM dokters WHERE nama_lengkap LIKE '%Rindang%')
AND status_validasi = 'disetujui';

-- Check pasien harian data  
SELECT COUNT(*) as pasien_days, SUM(jaspel_rupiah) as pasien_total
FROM jumlah_pasien_harian 
WHERE dokter_id = (SELECT id FROM dokters WHERE nama_lengkap LIKE '%Rindang%')
AND input_by IN (SELECT id FROM users WHERE role = 'petugas');
```

## 📋 Expected Breakdown for Rp 1.177.000:

Based on the calculation logic:
- **Tindakan Jaspel**: Sum of `tindakan.jasa_dokter` where `status_validasi = 'disetujui'`
- **Pasien Jaspel**: Sum of `jumlah_pasien_harian.jaspel_rupiah` where `input_by` is petugas
- **Total**: Tindakan + Pasien = Rp 1.177.000

## 🧪 Verification Query

```sql
-- Complete verification for dr Rindang
WITH dokter_mapping AS (
    SELECT d.id as dokter_id, d.nama_lengkap
    FROM dokters d 
    WHERE d.nama_lengkap LIKE '%Rindang%'
),
tindakan_jaspel AS (
    SELECT 
        'tindakan' as source,
        SUM(t.jasa_dokter) as amount,
        COUNT(*) as count
    FROM tindakan t
    JOIN dokter_mapping dm ON t.dokter_id = dm.dokter_id
    WHERE t.status_validasi = 'disetujui'
),
pasien_jaspel AS (
    SELECT 
        'pasien_harian' as source,
        SUM(jph.jaspel_rupiah) as amount,
        COUNT(*) as count
    FROM jumlah_pasien_harian jph
    JOIN dokter_mapping dm ON jph.dokter_id = dm.dokter_id
    JOIN users u ON jph.input_by = u.id
    WHERE u.role = 'petugas'
)
SELECT 
    tj.amount as tindakan_jaspel,
    pj.amount as pasien_jaspel,
    (tj.amount + pj.amount) as total_jaspel
FROM tindakan_jaspel tj, pasien_jaspel pj;
```

## 📊 Answer: Database Columns for Total Jaspel

**The Rp 1.177.000 comes from:**

1. **`tindakan.jasa_dokter`** (Sum of validated medical procedures)
2. **`jumlah_pasien_harian.jaspel_rupiah`** (Sum of daily patient jaspel)

**Filtered by:**
- **Dokter ID**: Mapped from `users.name` → `dokters.nama_lengkap`
- **Validation Status**: `tindakan.status_validasi = 'disetujui'`
- **Input Authority**: `jumlah_pasien_harian.input_by` must be petugas role
- **Date Range**: Applied if filters exist from list page

---

**Analysis Date**: August 30, 2025  
**User**: dr Rindang Fatihah Idana, M.Kes (ID: 14)  
**Total Amount**: Rp 1.177.000  
**Primary Sources**: tindakan.jasa_dokter + jumlah_pasien_harian.jaspel_rupiah