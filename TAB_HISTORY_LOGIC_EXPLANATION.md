# 📚 Tab History Logic - COMPREHENSIVE EXPLANATION

## 🎯 **Overview**
Tab History menampilkan riwayat presensi dokter dengan informasi lengkap tentang jadwal jaga, waktu check-in/check-out, dan detail shift yang dijalankan.

## 🗃️ **Database Relationships**

### **Core Tables**
```sql
attendances         # Main attendance records
├── id (primary key)
├── user_id         → users.id (doctor)
├── jadwal_jaga_id  → jadwal_jagas.id (optional)
├── shift_id        → shift_templates.id (optional)  
├── date            # Attendance date
├── time_in         # Check-in time
├── time_out        # Check-out time
└── status          # Attendance status

jadwal_jagas        # Schedule assignments
├── id (primary key)
├── pegawai_id      → users.id (doctor)
├── shift_template_id → shift_templates.id
├── tanggal_jaga    # Schedule date
├── unit_kerja      # Work unit
├── peran           # Role
└── status_jaga     # Schedule status

shift_templates     # Shift definitions
├── id (primary key)
├── nama_shift      # Shift name (e.g., "Pagi", "Sore", "k4")
├── jam_masuk       # Start time
├── jam_pulang      # End time
└── durasi_jam      # Duration in hours
```

### **Relationship Chain**
```
User (Doctor) 
    ↓
Attendance ──optional──→ JadwalJaga ──required──→ ShiftTemplate
    ↓                         ↓                       ↓
time_in/out              unit_kerja/peran        nama_shift/jam_masuk/jam_pulang
```

## 🔄 **Backend Logic Flow**

### **1. API Endpoint**
```
GET /api/v2/dashboards/dokter/presensi?start=YYYY-MM-DD&end=YYYY-MM-DD
```

### **2. Data Query Process**
```php
// Step 1: Query attendance records dengan relationships
$historyQuery = Attendance::where('user_id', $user->id)
    ->with(['shift', 'jadwalJaga.shiftTemplate'])  // ← Load relationships
    ->orderByDesc('date');

// Step 2: Filter by date range
if ($request->has('start') && $request->has('end')) {
    // Custom range dari frontend
    $historyQuery->whereBetween('date', [$startDate, $endDate]);
} else {
    // Default: current month only
    $historyQuery->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year);
}
```

### **3. Data Processing Logic**
```php
$attendanceHistory = $historyQuery->get()
    ->map(function ($attendance) {
        // SIMPLE: Check existing relationships only
        $shiftInfo = null;
        
        // Priority check: jadwalJaga relationship
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            $shiftInfo = [
                'shift_name' => $shift->nama_shift ?? 'Shift',
                'shift_start' => $shift->jam_masuk->format('H:i'),
                'shift_end' => $shift->jam_pulang->format('H:i'),
                'shift_duration' => calculateShiftDuration(...)
            ];
        }
        // No complex fallback - just use relationship or null
        
        $attendance->shift_info = $shiftInfo;
        return $attendance;
    });
```

### **4. Response Structure**
```json
{
  "success": true,
  "message": "Data presensi berhasil dimuat",
  "data": {
    "today": { ... },           // Today's attendance summary
    "today_records": [ ... ],   // Today's individual records  
    "history": [ ... ],         // Historical attendance data
    "stats": { ... }            // Statistics summary
  }
}
```

## 🎨 **Frontend Logic Flow**

### **1. Data Fetching Process**
```typescript
// API Call via attendanceApi.ts
const fetchAttendanceHistory = async (startDate: Date, endDate: Date) => {
    const response = await fetch(`/api/v2/dashboards/dokter/presensi?start=${start}&end=${end}`);
    const data = await response.json();
    return data.data.history || [];  // Extract history array
};
```

### **2. Component Loading Logic (PresensiSimplified.tsx)**
```typescript
const loadHistory = useCallback(async () => {
    setHistoryLoading(true);
    
    // Fetch last 30 days by default
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    const historyData = await api.fetchAttendanceHistory(startDate, endDate);
    
    // Process and merge data
    let allRecords: any[] = [];
    
    if (Array.isArray(historyData)) {
        allRecords = [...historyData];
    } else if (historyData && typeof historyData === 'object') {
        // Handle complex response structure
        const history = historyData.history || [];
        const todayRecords = historyData.today_records || [];
        
        allRecords = [...history];
        
        // Merge today's records if not in history
        todayRecords.forEach((todayRecord: any) => {
            const existsInHistory = history.some((h: any) => h.id === todayRecord.id);
            if (!existsInHistory && todayRecord.time_in) {
                allRecords.push({...todayRecord, date: todayRecord.date || todayDate});
            }
        });
    }
    
    setAttendanceHistory(allRecords);
});
```

### **3. Rendering Logic**
```typescript
// Tab content rendering
case 'history':
    return (
        <div className="...">
            <h2>Riwayat Presensi</h2>
            
            {historyLoading ? (
                <LoadingSpinner />
            ) : attendanceHistory.length === 0 ? (
                <EmptyState />
            ) : (
                <HistoryCards />
            )}
        </div>
    );
```

### **4. Individual Record Display**
```typescript
{paginatedHistory.map((record, index) => (
    <div key={index} className="attendance-card">
        {/* Header: Date & Status */}
        <div className="header">
            <div className="date">{formatDate(record.date)}</div>
            <div className="status">{record.status}</div>
        </div>
        
        {/* Check-in/Check-out Times */}
        <div className="times">
            <div>Check-in: {record.time_in || '--:--'}</div>
            <div>Check-out: {record.time_out || '--:--'}</div>
            <div>Durasi: {calculateWorkingHours(...)}</div>
        </div>
        
        {/* Shift Information (if available) */}
        {record.shift_info && (
            <div className="shift-info">
                <Clock icon />
                <span>{record.shift_info.shift_name}</span>
                <span>{record.shift_info.shift_start} - {record.shift_info.shift_end}</span>
                <span>({record.shift_info.shift_duration})</span>
            </div>
        )}
    </div>
))}
```

## 📊 **Data Processing Pipeline**

### **Step 1: Database Query**
```
Input: User ID + Date Range
Query: attendances WHERE user_id = X AND date BETWEEN start AND end
Load: WITH ['jadwalJaga.shiftTemplate']
Output: Collection of Attendance models
```

### **Step 2: Relationship Resolution**
```
For each attendance record:
  IF (attendance.jadwalJaga exists AND jadwalJaga.shiftTemplate exists):
    ✅ Create shift_info object with:
       - shift_name (from shiftTemplate.nama_shift)
       - shift_start (from shiftTemplate.jam_masuk) 
       - shift_end (from shiftTemplate.jam_pulang)
       - shift_duration (calculated)
  ELSE:
    ❌ Set shift_info = null (no data available)
```

### **Step 3: API Response Formation**
```
Backend processing:
  - Query results → Laravel Collection
  - map() processing → Add shift_info to each record
  - JSON serialization → Convert to array
  - Response wrapping → Add success/message/data structure

Frontend processing:
  - HTTP request → Fetch API call
  - JSON parsing → Convert response to JavaScript object
  - Data extraction → Get data.history array
  - State management → Store in attendanceHistory state
```

### **Step 4: UI Rendering**
```
Frontend rendering cycle:
  - Component mount → Trigger loadHistory()
  - API call → Fetch data from backend
  - State update → setAttendanceHistory(data)
  - Re-render → Display updated history cards
  - Pagination → Slice data for current page
  - Individual cards → Render each attendance record
```

## 🔗 **Logic Components**

### **1. Data Availability Logic**
```
🟢 Complete Record (WITH jadwal_jaga_id):
  - Shows: Full shift information
  - Display: Shift name, start time, end time, duration
  - Style: Cyan-colored info card
  
🔴 Incomplete Record (WITHOUT jadwal_jaga_id):
  - Shows: Only attendance times
  - Display: Check-in/check-out times only
  - Style: No shift info card displayed
```

### **2. Date Filtering Logic**
```typescript
// Default behavior: Last 30 days
const loadHistory = () => {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);  // 30 days back
    
    fetchAttendanceHistory(startDate, endDate);
};

// User-controlled filtering (if implemented)
const handleFilterChange = (period: string) => {
    if (period === 'weekly') {
        // Last 7 days
    } else if (period === 'monthly') {
        // Last 30 days  
    }
};
```

### **3. Pagination Logic**
```typescript
// Data slicing for display
const paginatedHistory = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage;
    return attendanceHistory.slice(startIndex, startIndex + itemsPerPage);
}, [attendanceHistory, currentPage, itemsPerPage]);

// Default: 5 records per page
const [itemsPerPage] = useState(5);
```

### **4. Empty State Logic**
```typescript
// Conditional rendering based on data availability
{historyLoading ? (
    <LoadingSpinner />
) : attendanceHistory.length === 0 ? (
    <EmptyState message="Belum ada riwayat presensi" />
) : (
    <HistoryCardsList />
)}
```

## 🎨 **UI Components Logic**

### **1. Tab Navigation**
```typescript
const [activeTab, setActiveTab] = useState<'checkin' | 'history' | 'stats' | 'leave'>('checkin');

// Tab switching logic
const renderTabContent = () => {
    switch (activeTab) {
        case 'history':
            return <HistoryTabContent />;
        // ... other tabs
    }
};
```

### **2. Data Loading Triggers**
```typescript
// Load history when history tab is activated
useEffect(() => {
    if (activeTab === 'history') {
        loadHistory();  // Always reload when tab opened
    }
}, [activeTab, loadHistory]);
```

### **3. Time Formatting Logic**
```typescript
// Display formatting utilities
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: '2-digit', 
        day: '2-digit'
    });
};

const calculateWorkingHours = (checkIn: Date, checkOut: Date) => {
    const diffMs = checkOut.getTime() - checkIn.getTime();
    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    return `${hours}h ${minutes}m`;
};
```

## 🔄 **Complete Data Flow Diagram**

```
1. User clicks "History" tab
    ↓
2. loadHistory() triggered
    ↓  
3. API call: /api/v2/dashboards/dokter/presensi
    ↓
4. Backend query: Attendance::where('user_id')
    ↓
5. Load relationships: ->with(['jadwalJaga.shiftTemplate'])
    ↓
6. Process each record:
   IF (jadwalJaga exists) → Create shift_info
   ELSE → shift_info = null
    ↓
7. Return JSON response with history array
    ↓
8. Frontend receives data
    ↓
9. Process & merge today_records if needed
    ↓
10. Update state: setAttendanceHistory(data)
    ↓
11. Component re-renders with new data
    ↓
12. Display paginated history cards
    ↓
13. For each card:
    - Show date, status, times
    - IF (shift_info exists) → Show shift details
    - ELSE → Show times only
```

## 📋 **Current Logic Summary**

### **Backend Logic (SIMPLE)**
- ✅ **Query**: Direct attendance records dengan relationships
- ✅ **Processing**: Only use existing jadwalJaga relationships  
- ✅ **No Fallback**: No complex algorithms or data guessing
- ✅ **Performance**: Fast, straightforward database queries

### **Frontend Logic (ENHANCED)**
- ✅ **Data Handling**: Merge history + today_records for completeness
- ✅ **Loading States**: Proper loading/empty state management
- ✅ **Pagination**: 5 records per page dengan navigation
- ✅ **Conditional Display**: Show shift info only when available

### **Relationship Dependencies**
```
attendance.jadwal_jaga_id NOT NULL 
    AND jadwal_jaga.shift_template_id NOT NULL
    AND shift_template EXISTS
        ↓
    ✅ RESULT: Full shift information displayed

ANY relationship NULL or missing
        ↓  
    ❌ RESULT: Only attendance times shown (no shift info)
```

## 🎯 **Key Behaviors**

### **When Relationships Exist**
```
✅ Display includes:
  - 📅 Date formatted (DD/MM/YYYY)
  - ⏰ Check-in time (HH:MM)
  - 🏁 Check-out time (HH:MM) 
  - ⏱️ Working duration calculated
  - 🕐 Shift name (e.g., "k4", "Pagi")
  - 📊 Shift times (e.g., "07:45 - 07:50")
  - ⏰ Shift duration (e.g., "0j 5m")
```

### **When Relationships Missing**
```
❌ Display includes only:
  - 📅 Date formatted
  - ⏰ Check-in time (if available)
  - 🏁 Check-out time (if available)
  - ⏱️ Working duration (if both times available)
  
❌ NO shift information displayed
❌ NO warning messages
❌ NO fallback data
```

## 💡 **Design Philosophy**

### **Simplicity First**
- **Show only what exists** - no guessing or assumptions
- **Direct relationships** - no complex algorithms  
- **Clean display** - no confusing warnings or fallbacks
- **Fast performance** - minimal processing overhead

### **Data-Driven Display**
- **Garbage in, accurate out** - show exactly what's in database
- **No artificial intelligence** - no smart guessing
- **Predictable behavior** - always same logic for same data
- **Easy debugging** - simple to trace issues

### **User Experience**
- **Clear expectations** - user knows what they'll see
- **No surprises** - consistent behavior
- **Fast loading** - minimal data processing
- **Clean interface** - no warning clutter

## 📚 **LOGIC EXPLANATION COMPLETE**

**Tab History Logic**: **Simple, direct, relationship-based display**
**Philosophy**: **Show actual data only, no artificial enhancements**
**Performance**: **Fast queries with minimal processing**
**Maintainability**: **Easy to understand and debug**

**Result**: Clean, predictable history display based on actual database relationships! 📊