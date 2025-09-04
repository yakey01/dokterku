# Permission & Security Analysis - Dashboard Petugas

## 🔐 Executive Summary

Dashboard Petugas mengimplementasikan sistem authorization berlapis dengan kombinasi role-based access control (RBAC) dan permission-based access control (PBAC) menggunakan Spatie Laravel Permission. Sistem ini menerapkan data scoping yang konsisten untuk membatasi akses pengguna hanya pada data yang mereka input sendiri.

## 🏗️ Architecture Overview

### Permission System Stack
1. **Laravel Gate System** - Base authorization layer
2. **Spatie Laravel Permission** - Role & permission management
3. **Custom Role Model** - Legacy custom role system (deprecated)
4. **Filament Policy Integration** - Resource-level authorization
5. **Database Scoping** - Data-level access control

### Authentication Flow
```
User Request → PetugasMiddleware → Role Check → Policy Check → Data Scoping → Response
```

## 👤 User Model Integration

### Role System
**File:** `app/Models/User.php`
- **Trait:** `HasRoles` (Spatie Permission)
- **Primary Role:** `getPrimaryRoleName()` - Returns first role if multiple
- **Role Check:** `hasRole()` - Enhanced to support both single and array roles
- **Legacy Support:** Maintains backward compatibility during migration

### Panel Access Control
**Method:** `canAccessPanel(Panel $panel)`
- **Admin Panel:** Requires 'admin' role OR 'view_admin_panel' permission
- **Role-based Panels:** Maps panel ID to required role
- **Petugas Panel:** Requires 'petugas' role specifically

### Permission Enhancement
**Method:** `can($abilities, $arguments)`
- **Spatie First:** Checks Spatie permissions first
- **Laravel Fallback:** Falls back to Laravel's default can() method
- **Integration:** Seamlessly integrates both systems

## 🔒 Permission System Analysis

### Core Permission Types

#### 1. Resource-Level Permissions (Filament)
```php
// Patient Management
'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien', 'delete_pasien', 'delete_any_pasien'

// Medical Procedures
'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan', 'delete_tindakan', 'delete_any_tindakan'

// Financial Management
'view_any_pendapatan', 'view_pendapatan', 'create_pendapatan', 'update_pendapatan', 'delete_pendapatan', 'delete_any_pendapatan'
'view_any_pengeluaran', 'view_pengeluaran', 'create_pengeluaran', 'update_pengeluaran', 'delete_pengeluaran', 'delete_any_pengeluaran'
```

#### 2. Business Logic Permissions
```php
// Transaction Management
'input_transactions'      // Can create/edit transactions
'validate_transactions'   // Can approve/reject transactions
'view_own_data'          // Can view own data only
'manage_patients'        // Can manage patient records
```

#### 3. Legacy Permissions (Deprecated)
```php
// Patient Management
'view-patients', 'create-patients', 'edit-patients', 'delete-patients'

// Medical Procedures
'view-procedures', 'create-procedures', 'edit-procedures', 'delete-procedures', 'perform-procedures'

// Financial Management  
'view-finances', 'create-finances', 'edit-finances', 'delete-finances'
```

### Petugas Role Permissions

#### From Migration Bridge (Current)
```php
'petugas' => [
    // Patient Management
    'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien',
    
    // Medical Procedures
    'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan',
    
    // Financial Management
    'view_any_pendapatan', 'view_pendapatan', 'create_pendapatan',
    
    // Business Logic
    'input_transactions', 'view_own_data', 'manage_patients'
]
```

#### From Custom Role System (Legacy)
```php
'petugas' => [
    'input_transactions', 'view_own_data', 'manage_patients'
]
```

## 🛡️ Policy Implementation Analysis

### 1. PasienPolicy
**File:** `app/Policies/PasienPolicy.php`
- **Permission Model:** Legacy system (`view-patients`, `create-patients`, etc.)
- **Data Scoping:** NOT implemented at policy level
- **Force Delete:** Admin role only
- **Issue:** Uses deprecated permissions

### 2. TindakanPolicy
**File:** `app/Policies/TindakanPolicy.php`
- **Permission Model:** Mixed (both `input_transactions` and `view-procedures`)
- **Data Scoping:** Implemented with user involvement check
- **Validation Logic:** Bendahara can validate, creators can edit
- **Complex Logic:** Checks input_by, dokter_id, paramedis_id, non_paramedis_id

### 3. PendapatanHarianPolicy
**File:** `app/Policies/PendapatanHarianPolicy.php`
- **Permission Model:** Mixed (Filament + business logic)
- **Data Scoping:** `input_by = user->id OR validate_transactions`
- **Validation Logic:** Validators can access all, creators can access own
- **Bulk Actions:** Comprehensive bulk permission support

### 4. PengeluaranHarianPolicy
**File:** `app/Policies/PengeluaranHarianPolicy.php`
- **Permission Model:** Similar to PendapatanHarian
- **Data Scoping:** Same pattern as PendapatanHarian
- **Consistency:** Good alignment with income resource

### 5. JumlahPasienHarianPolicy
**File:** `app/Policies/JumlahPasienHarianPolicy.php`
- **Permission Model:** Specific permissions + business logic
- **Data Scoping:** `input_by = user->id OR validate_transactions`
- **Comprehensive:** Most complete policy implementation

## 📊 Data Scoping Implementation

### Resource-Level Scoping
All Petugas resources implement consistent data scoping:

```php
// PasienResource.php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('input_by', auth()->id());
}

// TindakanResource.php  
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('input_by', Auth::id())
        ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis']);
}

// PendapatanHarianResource.php
->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));

// PengeluaranHarianResource.php
->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));

// JumlahPasienHarianResource.php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('input_by', auth()->id());
}
```

### Widget-Level Scoping
```php
// PetugasStatsWidget.php
$todayPasien = Pasien::whereDate('created_at', $today)
    ->where('input_by', $userId)
    ->count();

$todayPendapatan = \App\Models\PendapatanHarian::where('tanggal_input', $today)
    ->where('input_by', $userId)
    ->sum('jumlah');
```

## 🔍 Security Assessment

### ✅ Strengths

1. **Consistent Data Scoping**
   - All resources implement proper data isolation
   - Users can only access their own data
   - Prevents horizontal privilege escalation

2. **Comprehensive Policy Coverage**
   - All main resources have dedicated policies
   - Bulk actions are properly controlled
   - Validation workflow is implemented

3. **Role-Based Access Control**
   - Clear role separation between user types
   - Panel access is properly controlled
   - Multiple authentication layers

4. **Migration Strategy**
   - Smooth transition from custom to Spatie system
   - Backward compatibility maintained
   - Data integrity preserved

### ⚠️ Vulnerabilities & Issues

#### High Priority

1. **Permission System Inconsistency**
   - **Issue:** Mixed usage of legacy and new permissions
   - **Impact:** Potential bypass of security checks
   - **Files:** PasienPolicy still uses legacy permissions
   - **Fix:** Standardize all policies to use new permission system

2. **Missing Input Validation**
   - **Issue:** No comprehensive input validation in policies
   - **Impact:** Potential injection attacks
   - **Files:** All policy files
   - **Fix:** Add input sanitization and validation

3. **No Rate Limiting**
   - **Issue:** No protection against brute force or spam
   - **Impact:** System abuse potential
   - **Files:** All resources
   - **Fix:** Implement rate limiting middleware

#### Medium Priority

1. **Policy Logic Complexity**
   - **Issue:** TindakanPolicy has complex nested logic
   - **Impact:** Maintainability and security audit challenges
   - **Files:** TindakanPolicy.php
   - **Fix:** Refactor into smaller, testable methods

2. **Missing Audit Trail**
   - **Issue:** No logging of permission checks or access attempts
   - **Impact:** No forensic capabilities
   - **Files:** All resources
   - **Fix:** Implement comprehensive audit logging

3. **No Time-based Access Control**
   - **Issue:** No shift-based or time-based restrictions
   - **Impact:** Users can access outside work hours
   - **Files:** All resources
   - **Fix:** Implement temporal access controls

#### Low Priority

1. **Verbose Error Messages**
   - **Issue:** Error messages may expose system information
   - **Impact:** Information disclosure
   - **Files:** Policy files
   - **Fix:** Implement generic error messages

2. **No Permission Caching**
   - **Issue:** Permission checks happen on every request
   - **Impact:** Performance degradation
   - **Files:** All resources
   - **Fix:** Implement permission result caching

## 🎯 Security Recommendations

### Immediate Actions (High Priority)

1. **Standardize Permission System**
   ```php
   // Update all policies to use consistent permission names
   // Replace legacy permissions with new Filament-style permissions
   ```

2. **Implement Input Validation**
   ```php
   // Add comprehensive input validation in all policies
   // Sanitize user inputs before policy checks
   ```

3. **Add Rate Limiting**
   ```php
   // Implement rate limiting middleware for all resources
   // Prevent abuse of create/update operations
   ```

### Short-term Actions (Medium Priority)

1. **Refactor Complex Policies**
   ```php
   // Break down TindakanPolicy into smaller methods
   // Add unit tests for policy logic
   ```

2. **Implement Audit Logging**
   ```php
   // Log all permission checks and access attempts
   // Create audit trail for security monitoring
   ```

3. **Add Temporal Access Controls**
   ```php
   // Implement shift-based access restrictions
   // Add time-based permission checks
   ```

### Long-term Actions (Low Priority)

1. **Performance Optimization**
   ```php
   // Implement permission result caching
   // Optimize database queries in policies
   ```

2. **Advanced Security Features**
   ```php
   // Add IP-based access controls
   // Implement device fingerprinting
   ```

## 📋 Testing Requirements

### Unit Tests Needed
1. **Policy Tests**
   - Test all permission scenarios
   - Test data scoping logic
   - Test edge cases and error conditions

2. **Role Tests**
   - Test role assignment and removal
   - Test permission inheritance
   - Test role-based panel access

3. **Middleware Tests**
   - Test PetugasMiddleware logic
   - Test authentication flow
   - Test unauthorized access attempts

### Integration Tests Needed
1. **End-to-End Security Tests**
   - Test complete authorization flow
   - Test data isolation between users
   - Test permission escalation attempts

2. **Performance Tests**
   - Test permission check performance
   - Test database query optimization
   - Test widget loading with large datasets

## 📈 Monitoring & Metrics

### Security Metrics to Track
1. **Failed Authorization Attempts**
2. **Permission Check Performance**
3. **Data Access Patterns**
4. **Role Assignment Changes**
5. **Policy Violation Attempts**

### Alerting Thresholds
1. **Multiple failed permission checks** - Same user, short time
2. **Unusual data access patterns** - Outside normal hours
3. **Role escalation attempts** - Unauthorized role changes
4. **Policy bypass attempts** - Direct database access

---

**Generated:** {{ now()->format('d/m/Y H:i') }}
**Audit Version:** 1.0.0
**Analyst:** System Dashboard Optimization System
**Next Review:** Q4 2025