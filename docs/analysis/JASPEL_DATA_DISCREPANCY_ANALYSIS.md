# Jaspel Data Discrepancy Analysis

## 📋 Overview

**Issue**: Data discrepancy between laporan jaspel list table and detail page
- **List Page**: `http://127.0.0.1:8000/bendahara/laporan-jaspel`
- **Detail Page**: `http://127.0.0.1:8000/bendahara/laporan-jaspel/14`

## 🔍 Data Flow Analysis

### List Page Data Flow
```mermaid
ListLaporanKeuanganReport
  ↓
JaspelReportService::getValidatedJaspelByRole('semua', $filters)
  ↓ 
ProcedureJaspelCalculationService::getAllUsersWithProcedureJaspel($role, $filters)
  ↓
ProcedureJaspelCalculationService::calculateJaspelFromProcedures($userId, $filters) [for each user]
  ↓
Aggregated data with filtering
```

### Detail Page Data Flow
```mermaid
ViewJaspelDetail
  ↓
ProcedureJaspelCalculationService::calculateJaspelFromProcedures($userId, [])
  ↓
Individual user data without filtering
```

## 🎯 Key Differences Identified

### 1. **Filter Application**

#### List Page Filters:
```php
// ListLaporanKeuanganReport.php
$filters = [];
if (!empty($tableFilters['date_range'])) {
    $filters['date_from'] = $tableFilters['date_range']['date_from'] ?? null;
    $filters['date_to'] = $tableFilters['date_range']['date_to'] ?? null;
}
$filters['search'] = $this->getTableSearch();

// Cache-busting parameters
if (request()->has('clear') || request()->has('refresh') || request()->has('v')) {
    $filters['cache_bust'] = time();
}
```

#### Detail Page Filters:
```php
// ViewJaspelDetail.php
$procedureData = $procedureCalculator->calculateJaspelFromProcedures($this->userId ?? 0, []);
// NO FILTERS APPLIED
```

### 2. **Data Scope Differences**

#### List Page Logic:
```php
// getAllUsersWithProcedureJaspel()
foreach ($users as $user) {
    $calculation = $this->calculateJaspelFromProcedures($user->id, $filters);
    
    // CRITICAL: Only includes users with jaspel > 0
    if ($calculation['total_jaspel'] > 0) {
        $results[] = [/* user data */];
    }
}
```

#### Detail Page Logic:
```php
// calculateJaspelFromProcedures() 
// Shows ALL data for user regardless of total amount
// No filtering for jaspel > 0
```

### 3. **Caching Strategy**

#### List Page Caching:
```php
// Has cache clearing logic
\Illuminate\Support\Facades\Cache::forget('db_subagent_jaspel_role_agg_petugas_dokter_');
\Illuminate\Support\Facades\Cache::forget('db_subagent_jaspel_role_agg_petugas_semua_');
```

#### Detail Page Caching:
```php
// No cache management - always fresh calculation
```

## 🚨 Identified Discrepancies

### 1. **Date Range Filtering**
**Issue**: List page may have active date filters that limit displayed data
**Impact**: User might appear with different totals or not appear at all in list

### 2. **Zero Amount Filtering** 
**Issue**: List page excludes users with `total_jaspel = 0`
**Impact**: Detail page might show user with Rp 0 while list page hides them

### 3. **Cache Staleness**
**Issue**: List page might show cached data while detail page shows fresh calculation
**Impact**: Temporal inconsistency in displayed amounts

### 4. **Filter Parameter Propagation**
**Issue**: List page filters not passed to detail page
**Impact**: Detail page calculates based on ALL data, list based on filtered data

## 💡 Root Cause Analysis

### Primary Cause: **Filter Context Loss**
When navigating from list to detail:
1. **List page** applies filters (date range, search, cache bust)
2. **Detail page** receives user ID but NOT the applied filters
3. **Different data scope** results in different calculations

### Secondary Causes:
1. **Cache Desynchronization**: List page clears cache, detail page doesn't
2. **Temporal Data Changes**: Data might change between list load and detail view
3. **Aggregation Logic**: List excludes zero amounts, detail shows everything

## ✅ Recommended Solutions

### 1. **Filter Context Preservation**
```php
// In ViewJaspelDetail.php
public function mount(int $record): void {
    $this->userId = $record;
    $this->user = User::find($record);
    
    // PRESERVE filters from list page session
    $listFilters = session('laporan_jaspel_filters', []);
    $this->appliedFilters = $listFilters;
}

// Apply same filters as list page
$procedureData = $procedureCalculator->calculateJaspelFromProcedures(
    $this->userId, 
    $this->appliedFilters
);
```

### 2. **Cache Synchronization**
```php
// Ensure both pages use same cache keys
$cacheKey = "jaspel_calculation_{$userId}_{$filterHash}";
```

### 3. **Data Consistency Validation**
```php
// Add validation in detail page
@if($procedureData['total_jaspel'] == 0)
    <div class="alert">
        Note: This user has no jaspel data for the current filter period
    </div>
@endif
```

### 4. **Filter Display in Detail Page**
```php
// Show active filters in detail page
@if($appliedFilters)
    <div class="filter-info">
        Active Filters: {{ json_encode($appliedFilters) }}
    </div>
@endif
```

## 🧪 Testing Recommendations

### Verification Steps:
1. **Apply date filter** in list page → Check if detail page reflects same period
2. **Clear cache** in list page → Verify detail page shows fresh data
3. **Search filter** in list page → Check detail calculation consistency
4. **Compare totals** between list row and detail page for same user

### Expected Outcomes:
- **Consistent Data**: Same user should show identical totals in both views
- **Filter Awareness**: Detail page should respect list page filters
- **Cache Consistency**: Both should use same cached/fresh data
- **Temporal Stability**: Data should be consistent within same session

## 📊 Implementation Priority

### High Priority:
1. **Filter Context Preservation** - Ensure detail page uses same filters as list
2. **Cache Synchronization** - Both pages use same cache strategy

### Medium Priority:
3. **Data Validation Alerts** - Inform users of discrepancies
4. **Filter Display** - Show active filters in detail page

### Low Priority:
5. **Real-time Sync** - Live updates between list and detail views

---

**Analysis Date**: August 30, 2025  
**Status**: Root cause identified, solutions recommended  
**Priority**: High - Data consistency critical for financial reporting  
**Next Steps**: Implement filter context preservation in detail page