# 🔍 Analisis Perbedaan Endpoint Admin vs Dokter Jadwal Jaga

## 🎯 Overview

Berdasarkan analisis codebase, ditemukan perbedaan signifikan antara:
- **Admin Endpoint**: `/admin/jadwal-jagas` (Filament Resource)
- **Dokter Endpoint**: `/api/v2/dashboards/dokter/jadwal-jaga` (API)

## 📊 Perbandingan Detail

### **1. Admin Endpoint (`/admin/jadwal-jagas`)**

#### **Teknologi:**
- **Framework**: Filament Admin Panel
- **Type**: Web Interface (CRUD)
- **Authentication**: Web session (admin login)

#### **Fitur:**
```php
// JadwalJagaResource.php
class JadwalJagaResource extends Resource
{
    protected static ?string $model = JadwalJaga::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = '📅 KALENDAR DAN JADWAL';
    protected static ?string $navigationLabel = 'Jadwal Jaga';
}
```

#### **Data yang Ditampilkan:**
- ✅ **Tanggal Jaga** (format: d/m/Y)
- ✅ **Pegawai** (nama lengkap)
- ✅ **Shift** (nama shift dengan badge)
- ✅ **Jam** (jam shift dengan custom indicator)
- ✅ **Peran** (Dokter/Paramedis/NonParamedis)
- ✅ **Status** (Aktif/Cuti/Izin/OnCall)
- ✅ **Unit Kerja** (Dokter Jaga/Pelayanan/Pendaftaran)
- ✅ **Tanggal Dibuat** (d/m/Y H:i)

#### **Fungsi:**
- ✅ **Create**: Membuat jadwal jaga baru
- ✅ **Read**: Melihat semua jadwal jaga
- ✅ **Update**: Mengedit jadwal jaga
- ✅ **Delete**: Menghapus jadwal jaga
- ✅ **Filter**: Filter berdasarkan berbagai kriteria
- ✅ **Search**: Pencarian berdasarkan nama pegawai, tanggal, dll

### **2. Dokter Endpoint (`/api/v2/dashboards/dokter/jadwal-jaga`)**

#### **Teknologi:**
- **Framework**: Laravel API
- **Type**: REST API
- **Authentication**: API token/session

#### **Fitur:**
```php
// DokterDashboardController.php
public function getJadwalJaga(Request $request)
{
    $user = Auth::user();
    $month = $request->get('month', Carbon::now()->month);
    $year = $request->get('year', Carbon::now()->year);
    $today = Carbon::today();
}
```

#### **Data yang Dikembalikan:**
- ✅ **Calendar Events** (untuk calendar view)
- ✅ **Weekly Schedule** (jadwal mingguan)
- ✅ **Today Schedule** (jadwal hari ini)
- ✅ **Shift Template Info** (detail shift)
- ✅ **Employee Info** (info pegawai)

#### **Response Structure:**
```json
{
  "success": true,
  "data": {
    "calendar_events": [...],
    "weekly_schedule": [...],
    "today_schedule": [...]
  }
}
```

## 🔍 Perbedaan Kunci

### **1. Scope Data**

| Aspek | Admin | Dokter |
|-------|-------|--------|
| **Scope** | Semua jadwal jaga | Hanya jadwal user yang login |
| **Filter** | Global (semua pegawai) | Personal (pegawai_id = user.id) |
| **Time Range** | Semua waktu | Bulan/tahun tertentu |

### **2. Format Data**

#### **Admin (Filament Table):**
```php
Tables\Columns\TextColumn::make('tanggal_jaga')
    ->label('Tanggal')
    ->date('d/m/Y')
    ->sortable()
    ->searchable()
```

#### **Dokter (API Response):**
```php
'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
'tanggal_formatted' => $jadwal->tanggal_jaga->format('l, d F Y'),
```

### **3. Authentication & Access**

| Aspek | Admin | Dokter |
|-------|-------|--------|
| **Auth Type** | Web session | API token/session |
| **Access Level** | Admin only | User specific |
| **Permissions** | Full CRUD | Read only |

### **4. Use Case**

#### **Admin Endpoint:**
- **Primary Use**: Management jadwal jaga
- **Users**: Administrators
- **Actions**: Create, Read, Update, Delete
- **Context**: Administrative dashboard

#### **Dokter Endpoint:**
- **Primary Use**: View personal schedule
- **Users**: Doctors/Medical staff
- **Actions**: Read only
- **Context**: Mobile app dashboard

## 🚨 Masalah yang Ditemukan

### **1. Data Inconsistency**
- **Admin**: Menampilkan semua jadwal dengan format d/m/Y
- **Dokter**: Menampilkan jadwal personal dengan format Y-m-d
- **Impact**: Format tanggal berbeda bisa menyebabkan kebingungan

### **2. Field Mapping**
- **Admin**: Menggunakan `pegawai.name` (relationship)
- **Dokter**: Menggunakan `employee_name` (hardcoded)
- **Impact**: Potensi data tidak sinkron

### **3. Status Handling**
- **Admin**: Menampilkan semua status (Aktif/Cuti/Izin/OnCall)
- **Dokter**: Filter hanya status 'Aktif' untuk presensi
- **Impact**: Dokter tidak bisa melihat jadwal non-aktif

### **4. Time Zone**
- **Admin**: Menggunakan timezone default
- **Dokter**: Menggunakan Asia/Jakarta timezone
- **Impact**: Potensi perbedaan waktu

## 🛠️ Recommended Fixes

### **1. Standardize Data Format**
```php
// Standardize date format across both endpoints
'date_format' => 'Y-m-d', // ISO format for API
'display_format' => 'd/m/Y', // User-friendly format for UI
```

### **2. Unify Field Names**
```php
// Use consistent field names
'employee_name' => $jadwal->pegawai->name, // Instead of hardcoded
'employee_id' => $jadwal->pegawai_id,
```

### **3. Consistent Timezone**
```php
// Use Asia/Jakarta timezone consistently
$date = Carbon::parse($jadwal->tanggal_jaga)->setTimezone('Asia/Jakarta');
```

### **4. Add Admin API Endpoint**
```php
// Create admin API endpoint for consistency
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/jadwal-jagas', [AdminJadwalJagaController::class, 'index']);
    Route::post('/jadwal-jagas', [AdminJadwalJagaController::class, 'store']);
    Route::put('/jadwal-jagas/{id}', [AdminJadwalJagaController::class, 'update']);
    Route::delete('/jadwal-jagas/{id}', [AdminJadwalJagaController::class, 'destroy']);
});
```

### **5. Enhanced Response Structure**
```php
// Standardize API response structure
return response()->json([
    'success' => true,
    'data' => [
        'schedules' => $schedules,
        'meta' => [
            'total' => $schedules->count(),
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'Y-m-d',
            'display_format' => 'd/m/Y'
        ]
    ]
]);
```

## 📋 Implementation Checklist

- [ ] **Standardize Date Format**
  - [ ] Use ISO format (Y-m-d) for API
  - [ ] Use display format (d/m/Y) for UI
  - [ ] Add timezone consistency

- [ ] **Unify Field Names**
  - [ ] Use consistent employee field names
  - [ ] Standardize status field handling
  - [ ] Align shift template field names

- [ ] **Create Admin API**
  - [ ] Add admin API endpoints
  - [ ] Implement proper authentication
  - [ ] Add CRUD operations

- [ ] **Enhance Documentation**
  - [ ] Document API response formats
  - [ ] Add field mapping documentation
  - [ ] Create integration guide

- [ ] **Testing**
  - [ ] Test data consistency
  - [ ] Verify timezone handling
  - [ ] Validate field mapping

## 🎯 Expected Outcome

After implementing these fixes:
- ✅ **Consistent Data Format**: Same date/time format across endpoints
- ✅ **Unified Field Names**: Consistent field naming convention
- ✅ **Proper Timezone**: Asia/Jakarta timezone used everywhere
- ✅ **API Consistency**: Both admin and doctor endpoints follow same patterns
- ✅ **Better Integration**: Seamless data flow between admin and mobile apps

## 🔍 Next Steps

1. **Immediate**: Standardize date format and timezone
2. **Short-term**: Create admin API endpoints
3. **Medium-term**: Unify field names and response structure
4. **Long-term**: Implement comprehensive testing suite
