# Jadwal Jaga Filter Issue - Analysis & Solution

## Problem Summary
Doctor schedules (jadwal jaga) created by admin are not showing up completely in the doctor dashboard because of a hard-coded filter in the API.

## Root Cause Analysis

### 1. **The Filter Issue**
In `app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php` (line 567):
```php
->where('jadwal_jagas.unit_kerja', 'Dokter Jaga') // ❌ EXCLUDES other unit_kerja values
```

### 2. **Available Unit Kerja Options**
In `app/Filament/Resources/JadwalJagaResource.php` (lines 176-182):
```php
Forms\Components\Select::make('unit_kerja')
    ->options([
        'Pendaftaran' => 'Pendaftaran',
        'Pelayanan' => 'Pelayanan', 
        'Dokter Jaga' => 'Dokter Jaga'
    ])
```

### 3. **Impact**
- Schedules with `unit_kerja = 'Pendaftaran'` are NOT shown to doctors
- Schedules with `unit_kerja = 'Pelayanan'` are NOT shown to doctors
- Only schedules with `unit_kerja = 'Dokter Jaga'` are shown to doctors

## Solution

### Option 1: Show All Schedules for Doctors (Recommended)
Remove the unit_kerja filter entirely for doctors:

```php
// In DokterDashboardController.php, around line 567
// REMOVE this line:
->where('jadwal_jagas.unit_kerja', 'Dokter Jaga')

// This will show ALL schedules assigned to the doctor regardless of unit_kerja
```

### Option 2: Show Multiple Unit Kerja Values
If you want to be selective about which unit_kerja values to show:

```php
// Replace the single where clause with whereIn:
->whereIn('jadwal_jagas.unit_kerja', ['Dokter Jaga', 'Pelayanan', 'Pendaftaran'])
```

### Option 3: Make it Configurable
Add a configuration option to control which unit_kerja values are visible:

```php
// In config/app.php or create config/jadwal.php
'doctor_visible_unit_kerja' => ['Dokter Jaga', 'Pelayanan', 'Pendaftaran'],

// Then in DokterDashboardController.php:
->whereIn('jadwal_jagas.unit_kerja', config('jadwal.doctor_visible_unit_kerja', ['Dokter Jaga']))
```

## Implementation Steps

1. **Update the Controller**
2. **Clear Cache**
3. **Test the API**
4. **Verify in UI**

## Testing Checklist

- [ ] Create jadwal jaga with unit_kerja = 'Pendaftaran' for a doctor
- [ ] Create jadwal jaga with unit_kerja = 'Pelayanan' for a doctor  
- [ ] Create jadwal jaga with unit_kerja = 'Dokter Jaga' for a doctor
- [ ] Verify ALL three schedules appear in doctor's dashboard
- [ ] Test API endpoint: `/api/v2/dashboards/dokter/jadwal-jaga`
- [ ] Check mobile app view
- [ ] Check web dashboard view

## Files to Modify

1. **Primary Fix**:
   - `/app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php`

2. **Optional - If Role-Based Filtering Needed**:
   - `/app/Models/JadwalJaga.php` - Add scope for flexible filtering
   - `/config/jadwal.php` - Create configuration file

## SQL Query Impact

### Before (Current - Restrictive):
```sql
SELECT * FROM jadwal_jagas 
WHERE pegawai_id = ? 
AND unit_kerja = 'Dokter Jaga' -- ❌ Filters out other units
```

### After (Fixed - Inclusive):
```sql
SELECT * FROM jadwal_jagas 
WHERE pegawai_id = ? 
-- ✅ Shows all schedules for the doctor
```

## Benefits of This Fix

1. **Complete Schedule Visibility**: Doctors see ALL their assigned schedules
2. **Admin Flexibility**: Admin can assign doctors to any unit without visibility issues
3. **Cross-Department Support**: Doctors working in multiple units see complete schedule
4. **No Data Loss**: All created schedules become visible as intended

## Potential Concerns & Mitigations

### Concern 1: Too Much Information
**Mitigation**: Add UI filters to let doctors filter by unit_kerja in the frontend

### Concern 2: Role Confusion  
**Mitigation**: Display unit_kerja clearly in the schedule view

### Concern 3: Performance
**Mitigation**: The query performance remains the same (actually slightly better without the extra WHERE clause)

## Recommended Implementation

```php
// File: app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
// Method: getJadwalJaga (around line 550-600)

// BEFORE:
$jadwalJaga = DB::table('jadwal_jagas')
    ->join('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
    ->leftJoin('users', 'jadwal_jagas.pegawai_id', '=', 'users.id')
    ->where('jadwal_jagas.pegawai_id', $user->id)
    ->where('jadwal_jagas.unit_kerja', 'Dokter Jaga') // ← REMOVE THIS LINE
    ->whereDate('jadwal_jagas.tanggal_jaga', '>=', $startDate)
    ->whereDate('jadwal_jagas.tanggal_jaga', '<=', $endDate)
    ->select([...])
    ->orderBy('jadwal_jagas.tanggal_jaga', 'asc')
    ->get();

// AFTER:
$jadwalJaga = DB::table('jadwal_jagas')
    ->join('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
    ->leftJoin('users', 'jadwal_jagas.pegawai_id', '=', 'users.id')
    ->where('jadwal_jagas.pegawai_id', $user->id)
    // unit_kerja filter REMOVED - shows all schedules
    ->whereDate('jadwal_jagas.tanggal_jaga', '>=', $startDate)
    ->whereDate('jadwal_jagas.tanggal_jaga', '<=', $endDate)
    ->select([...])
    ->orderBy('jadwal_jagas.tanggal_jaga', 'asc')
    ->get();
```

## Conclusion

The issue is caused by an overly restrictive filter that only shows schedules with `unit_kerja = 'Dokter Jaga'`. By removing this filter, doctors will see ALL their schedules regardless of which unit they're assigned to, which aligns with the admin's intention when creating schedules with different unit_kerja values.