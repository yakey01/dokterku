# Naming Standardization Report

## Completed Actions

### 1. Created Naming Standards Documentation
- **File**: `/docs/naming_standards.md`
- **Purpose**: Comprehensive guide for consistent naming conventions
- **Key Standards**:
  - Indonesian language for all labels
  - Consistent pluralization patterns
  - Standardized navigation groups with emojis
  - Uniform field naming conventions

### 2. Created Navigation Groups Configuration
- **File**: `/app/Config/NavigationGroups.php`
- **Purpose**: Centralized navigation group definitions
- **Groups Standardized**:
  - `👥 Manajemen Pasien` (Patient Management)
  - `🏥 Tindakan Medis` (Medical Procedures)
  - `💰 Manajemen Keuangan` (Financial Management)
  - `📊 Transaksi Harian` (Daily Transactions)
  - `✅ Validasi Data` (Data Validation)
  - `👨‍💼 Manajemen SDM` (HR Management)
  - `📋 Kehadiran` (Attendance)
  - `🏖️ Cuti & Izin` (Leave Management)
  - `📅 Jadwal` (Schedule)
  - `💼 Jaspel` (Service Allowance)
  - `📈 Laporan` (Reports)
  - `⚙️ Administrasi Sistem` (System Administration)
  - `👤 Manajemen Pengguna` (User Management)

### 3. Created Naming Standards Service
- **File**: `/app/Services/NamingStandardsService.php`
- **Purpose**: Programmatic access to naming standards
- **Features**:
  - Model label mappings
  - Field label translations
  - Icon standardization
  - Route name conventions
  - Status option translations

### 4. Created Standardization Command
- **File**: `/app/Console/Commands/StandardizeResourceNaming.php`
- **Purpose**: Automated naming standardization tool
- **Usage**: `php artisan dokterku:standardize-naming`
- **Features**:
  - Dry-run mode for preview
  - Bulk resource updates
  - Consistency checking

### 5. Applied Key Resource Standardizations
Updated navigation groups for consistency:
- **DokterResource**: `SDM` → `👨‍💼 Manajemen SDM`
- **PasienResource**: `👥 Data Pasien` → `👥 Manajemen Pasien`
- **PendapatanHarianResource**: `Transaksi Harian` → `📊 Transaksi Harian`
- **PengeluaranHarianResource**: `Transaksi Harian` → `📊 Transaksi Harian`
- **TindakanResource**: `Tindakan Medis` → `🏥 Tindakan Medis`

## Identified Naming Issues

### High Priority
1. **Mixed Language Usage**
   - Some resources use English labels
   - Navigation groups inconsistent language
   - Field labels mix Indonesian and English

2. **Inconsistent Navigation Groups**
   - `Financial Management` vs `Manajemen Keuangan`
   - `User Management` vs `Manajemen Pengguna`
   - Some groups lack emoji icons

3. **Pluralization Issues**
   - Database tables use singular forms
   - Some resources have inconsistent plural labels
   - Model names don't always match table names

### Medium Priority
1. **Icon Inconsistencies**
   - Different icon styles for similar resources
   - Some resources lack appropriate icons
   - Icon naming not standardized

2. **Route Naming**
   - Some routes use camelCase
   - Others use kebab-case
   - API endpoints inconsistent

### Low Priority
1. **Field Label Translations**
   - Some forms still use English labels
   - Validation messages mixed language
   - Helper text inconsistent

## Recommendations

### Immediate Actions
1. **Apply Navigation Group Standards**
   - Use the centralized NavigationGroups config
   - Update all resources to use emoji-prefixed groups
   - Ensure Indonesian language consistency

2. **Standardize Resource Labels**
   - Use NamingStandardsService for all resources
   - Update model labels to Indonesian
   - Ensure consistent pluralization

3. **Icon Standardization**
   - Apply consistent icon scheme
   - Use heroicons-outline style
   - Match icons to resource purpose

### Long-term Actions
1. **Database Schema Updates**
   - Consider table pluralization migration
   - Standardize foreign key naming
   - Update index naming conventions

2. **API Standardization**
   - Implement consistent endpoint naming
   - Update response formats
   - Standardize error messages

3. **Form Localization**
   - Complete Indonesian translation
   - Update validation messages
   - Standardize helper text

## Implementation Status
- ✅ **Completed**: Documentation and services created
- ✅ **Completed**: Key resource navigation groups standardized
- 🔄 **In Progress**: Comprehensive resource updates
- ⏳ **Pending**: Database schema considerations
- ⏳ **Pending**: API endpoint standardization

## Next Steps
1. Run `php artisan dokterku:standardize-naming` on all resources
2. Update validation resources with standard navigation groups
3. Apply field label standardization across forms
4. Review and update API endpoint naming
5. Create migration plan for database schema updates

## Files Modified
- `/app/Filament/Resources/DokterResource.php`
- `/app/Filament/Petugas/Resources/PasienResource.php`
- `/app/Filament/Petugas/Resources/PendapatanHarianResource.php`
- `/app/Filament/Petugas/Resources/PengeluaranHarianResource.php`
- `/app/Filament/Petugas/Resources/TindakanResource.php`

## Files Created
- `/docs/naming_standards.md`
- `/app/Config/NavigationGroups.php`
- `/app/Services/NamingStandardsService.php`
- `/app/Console/Commands/StandardizeResourceNaming.php`
- `/docs/naming_standardization_report.md`