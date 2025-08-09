# Schedule Update Root Cause Analysis & Solution Design

## Executive Summary
The doctor attendance (presensi dokter) system shows outdated or incorrect schedule information. Based on the code analysis, the root cause is identified as a **caching issue combined with lack of real-time synchronization** between admin updates and frontend display.

## Current System Architecture

### 1. Data Flow
```
Admin Panel (Filament) → Database (jadwal_jagas table) → API (with Cache) → Frontend (React)
```

### 2. Components Involved
- **Admin**: `/app/Filament/Resources/JadwalJagaResource.php`
- **Model**: `/app/Models/JadwalJaga.php`
- **API**: `/app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php`
- **Frontend**: `/resources/js/components/dokter/Presensi.tsx`
- **Database**: `jadwal_jagas` table with shift templates

## Root Problems Identified

### Problem 1: Aggressive Caching Without Invalidation
**Location**: `DokterDashboardController.php` lines 201-211

```php
$cacheKey = "jadwal_jaga_{$user->id}_{$month}_{$year}";
$cacheTTL = $isRefresh ? 10 : 60; // 60 seconds normal cache
```

**Issue**: 
- Schedule data is cached for 60 seconds
- No cache invalidation when admin updates schedule
- Cache only cleared on check-in/check-out, not on schedule updates

### Problem 2: No Real-Time Update Mechanism
**Location**: `Presensi.tsx` lines 395-552

```javascript
// Polling every 15 seconds
const intervalId = window.setInterval(() => {
    fetchScheduleAndLocation();
}, 15000);
```

**Issue**:
- Frontend polls every 15 seconds but gets cached data
- No WebSocket or Server-Sent Events for real-time updates
- No cache-busting mechanism unless user manually refreshes

### Problem 3: Schedule Status Calculation
**Location**: `Presensi.tsx` lines 210-211

```javascript
isOnDuty: data.data.currentShift ? true : false,
```

**Issue**:
- `isOnDuty` status depends on `currentShift` existence
- If schedule is created/updated after cache, status won't reflect changes
- No validation against actual schedule time vs current time

### Problem 4: Missing Cache Invalidation on Admin Actions
**Location**: `JadwalJagaResource.php`

**Issue**:
- No cache clearing after create/update/delete operations
- Admin changes don't trigger cache refresh
- Users continue seeing stale data until cache expires

## Solution Design

### Immediate Fix (Quick Implementation)

#### 1. Add Cache Invalidation on Admin Actions
```php
// In JadwalJagaResource CreateJadwalJaga.php
protected function afterCreate(): void
{
    $record = $this->record;
    Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$record->tanggal_jaga->month}_{$record->tanggal_jaga->year}");
    Cache::forget("dokter_dashboard_stats_{$record->pegawai_id}");
}

// In JadwalJagaResource EditJadwalJaga.php
protected function afterSave(): void
{
    $record = $this->record;
    Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$record->tanggal_jaga->month}_{$record->tanggal_jaga->year}");
    Cache::forget("dokter_dashboard_stats_{$record->pegawai_id}");
}
```

#### 2. Reduce Cache TTL
```php
// In DokterDashboardController.php
$cacheTTL = $isRefresh ? 5 : 30; // Reduce from 60 to 30 seconds
```

#### 3. Add Force Refresh Parameter
```javascript
// In Presensi.tsx
const fetchScheduleAndLocation = async (forceRefresh = false) => {
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    };
    
    if (forceRefresh) {
        headers['Cache-Control'] = 'no-cache';
    }
    
    // Add timestamp to prevent browser caching
    const scheduleResponse = await fetch(
        `/api/v2/dashboards/dokter/jadwal-jaga?t=${Date.now()}&refresh=${forceRefresh}`,
        { headers }
    );
};
```

### Long-Term Solution (Comprehensive)

#### 1. Implement Event-Driven Cache Invalidation
```php
// Create Event: app/Events/JadwalJagaUpdated.php
namespace App\Events;

class JadwalJagaUpdated
{
    public function __construct(public JadwalJaga $jadwal) {}
}

// Create Listener: app/Listeners/ClearJadwalJagaCache.php
namespace App\Listeners;

class ClearJadwalJagaCache
{
    public function handle(JadwalJagaUpdated $event)
    {
        $jadwal = $event->jadwal;
        
        // Clear all related caches
        Cache::forget("jadwal_jaga_{$jadwal->pegawai_id}_{$jadwal->tanggal_jaga->month}_{$jadwal->tanggal_jaga->year}");
        Cache::forget("dokter_dashboard_stats_{$jadwal->pegawai_id}");
        
        // Clear weekly cache
        Cache::forget("jadwal_jaga_weekly_{$jadwal->pegawai_id}");
    }
}

// In JadwalJaga Model
protected static function booted()
{
    static::created(fn($model) => event(new JadwalJagaUpdated($model)));
    static::updated(fn($model) => event(new JadwalJagaUpdated($model)));
    static::deleted(fn($model) => event(new JadwalJagaUpdated($model)));
}
```

#### 2. Implement WebSocket for Real-Time Updates
```javascript
// Using Laravel Echo with Pusher/Soketi
Echo.private(`user.${userId}`)
    .listen('JadwalJagaUpdated', (e) => {
        // Force refresh schedule data
        fetchScheduleAndLocation(true);
        
        // Show notification
        showNotification('Jadwal jaga telah diperbarui');
    });
```

#### 3. Add Version-Based Caching
```php
// Add to jadwal_jagas table migration
$table->integer('version')->default(1);
$table->timestamp('last_modified_at')->nullable();

// In API response
'schedule_version' => $jadwal->version,
'last_modified' => $jadwal->last_modified_at,

// Frontend can compare versions
if (newVersion > currentVersion) {
    // Force refresh
}
```

## Implementation Priority

### Phase 1 (Immediate - 1 day)
1. ✅ Add cache invalidation in Filament admin
2. ✅ Reduce cache TTL to 30 seconds
3. ✅ Add force refresh parameter in API

### Phase 2 (Short-term - 3 days)
1. ⏳ Implement event-driven cache invalidation
2. ⏳ Add schedule version tracking
3. ⏳ Improve frontend polling logic

### Phase 3 (Long-term - 1 week)
1. ⏳ Implement WebSocket real-time updates
2. ⏳ Add Redis for better cache management
3. ⏳ Implement cache warming strategies

## Testing Checklist

### Scenario Testing
- [ ] Create new schedule → Check if appears immediately
- [ ] Update existing schedule → Check if updates reflect
- [ ] Delete schedule → Check if removal reflects
- [ ] Multiple schedule changes → Check consistency
- [ ] Different time zones → Check time accuracy

### Performance Testing
- [ ] Cache hit ratio monitoring
- [ ] API response time under load
- [ ] Frontend rendering performance
- [ ] Database query optimization

## Monitoring & Alerts

### Metrics to Track
1. Cache hit/miss ratio
2. Schedule update latency
3. API response times
4. Frontend polling frequency
5. User complaints about stale data

### Alert Conditions
- Cache miss ratio > 50%
- Schedule update latency > 5 seconds
- API response time > 1 second
- Frontend errors in schedule fetch

## Conclusion

The root cause is **aggressive caching without proper invalidation mechanisms**. The immediate fix involves adding cache invalidation on admin actions and reducing cache TTL. The long-term solution requires implementing event-driven architecture with real-time updates via WebSockets.

## Action Items

1. **Immediate**: Implement cache invalidation in admin panel
2. **Today**: Deploy reduced cache TTL
3. **This Week**: Implement event-driven cache invalidation
4. **Next Sprint**: Plan WebSocket implementation