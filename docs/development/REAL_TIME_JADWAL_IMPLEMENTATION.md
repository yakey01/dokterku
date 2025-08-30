# 🏥 Real-Time Jadwal Jaga Implementation Specifications

## 🎯 **SYSTEM OVERVIEW**

This document provides comprehensive implementation specifications for enhancing the existing jadwal jaga system with real-time capabilities.

## 📊 **CURRENT SYSTEM STATUS**

### ✅ **Existing Infrastructure**
- **Frontend**: React components with 60s auto-refresh
- **Backend**: Laravel API with attendance integration  
- **Database**: JadwalJaga model with proper relationships
- **Data Sources**: Real API endpoints (no mock data)
- **Dr. Yaya Issue**: ❌ Not hours-related (GPS coordinates -6.xxx)

### 🔍 **Key Findings**
- Both Dokter & Paramedis components fetch `attendance_records`
- Cache invalidation system exists in JadwalJaga model
- Real-time attendance tracking already implemented
- Schedule status calculation based on current time

---

## 🚀 **REAL-TIME ARCHITECTURE**

### **Phase 1: Enhanced Polling (Current)**
```typescript
// Current implementation - 60s refresh cycle
useEffect(() => {
  const refreshInterval = setInterval(() => {
    fetchJadwalJaga(true); // Force refresh
  }, 60000);
  return () => clearInterval(refreshInterval);
}, []);
```

### **Phase 2: WebSocket Integration**
```typescript
// Real-time WebSocket manager
interface WebSocketManager {
  connect(): Promise<void>;
  subscribe(channel: string, callback: Function): void;
  broadcast(event: string, data: any): void;
  disconnect(): void;
}

// Implementation
const wsManager = new WebSocketManager();
wsManager.subscribe(`schedule.${userId}`, handleScheduleUpdate);
wsManager.subscribe(`attendance.${userId}`, handleAttendanceUpdate);
```

### **Phase 3: Server-Sent Events (SSE)**
```php
// Laravel SSE endpoint
Route::get('/api/v2/stream/schedules/{userId}', function($userId) {
    return response()->stream(function() use ($userId) {
        while(true) {
            $data = JadwalJaga::getLatestForUser($userId);
            echo "data: " . json_encode($data) . "\n\n";
            sleep(5); // 5-second intervals
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
});
```

---

## 🔧 **BACKEND IMPLEMENTATIONS**

### **1. Real-Time Event Broadcasting**
```php
<?php
// app/Events/ScheduleUpdated.php
namespace App\Events;

use App\Models\JadwalJaga;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jadwalJaga;

    public function __construct(JadwalJaga $jadwalJaga)
    {
        $this->jadwalJaga = $jadwalJaga;
    }

    public function broadcastOn()
    {
        return new Channel('schedule.' . $this->jadwalJaga->pegawai_id);
    }

    public function broadcastWith()
    {
        return [
            'schedule_id' => $this->jadwalJaga->id,
            'user_id' => $this->jadwalJaga->pegawai_id,
            'date' => $this->jadwalJaga->tanggal_jaga,
            'shift' => $this->jadwalJaga->shiftTemplate->nama_shift,
            'status' => $this->jadwalJaga->status_jaga,
            'timestamp' => now()->toISOString()
        ];
    }
}
```

### **2. Enhanced JadwalJaga Model**
```php
<?php
// Add to existing JadwalJaga model
class JadwalJaga extends Model
{
    // ... existing code ...

    protected static function boot()
    {
        parent::boot();

        static::created(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
            broadcast(new ScheduleUpdated($jadwal))->toOthers();
        });

        static::updated(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
            broadcast(new ScheduleUpdated($jadwal))->toOthers();
        });

        static::deleted(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
            broadcast(new ScheduleDeleted($jadwal))->toOthers();
        });
    }

    /**
     * Get real-time schedule status
     */
    public function getRealTimeStatus(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $scheduleDate = $this->tanggal_jaga;
        
        $shiftStart = $scheduleDate->copy()->setTimeFromTimeString($this->shiftTemplate->jam_masuk);
        $shiftEnd = $scheduleDate->copy()->setTimeFromTimeString($this->shiftTemplate->jam_pulang);
        
        // Handle overnight shifts
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $status = 'upcoming';
        if ($now->between($shiftStart, $shiftEnd)) {
            $status = 'active';
        } elseif ($now->gt($shiftEnd)) {
            $status = 'completed';
        }

        return [
            'status' => $status,
            'current_time' => $now->toISOString(),
            'shift_start' => $shiftStart->toISOString(),
            'shift_end' => $shiftEnd->toISOString(),
            'time_until_start' => $status === 'upcoming' ? $now->diffInMinutes($shiftStart) : null,
            'time_until_end' => $status === 'active' ? $now->diffInMinutes($shiftEnd) : null,
        ];
    }
}
```

### **3. Real-Time API Controller**
```php
<?php
// app/Http/Controllers/Api/V2/RealTimeScheduleController.php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\JadwalJaga;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RealTimeScheduleController extends Controller
{
    /**
     * Get real-time schedule updates
     */
    public function getRealtimeUpdates(Request $request)
    {
        $user = auth()->user();
        $lastUpdate = $request->input('last_update');
        
        $query = JadwalJaga::where('pegawai_id', $user->id)
            ->with(['shiftTemplate', 'attendanceRecords']);
            
        if ($lastUpdate) {
            $query->where('updated_at', '>', Carbon::parse($lastUpdate));
        }
        
        $schedules = $query->get()->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'tanggal_jaga' => $schedule->tanggal_jaga->format('Y-m-d'),
                'status_jaga' => $schedule->status_jaga,
                'shift_template' => $schedule->shiftTemplate,
                'real_time_status' => $schedule->getRealTimeStatus(),
                'attendance' => $schedule->attendanceRecords->map(function($attendance) {
                    return [
                        'check_in_time' => $attendance->time_in,
                        'check_out_time' => $attendance->time_out,
                        'status' => $attendance->status
                    ];
                }),
                'updated_at' => $schedule->updated_at->toISOString()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'schedules' => $schedules,
                'server_time' => now()->toISOString(),
                'last_update' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Server-Sent Events stream
     */
    public function scheduleStream(Request $request)
    {
        $user = auth()->user();
        
        return response()->stream(function() use ($user) {
            while (true) {
                $schedules = JadwalJaga::where('pegawai_id', $user->id)
                    ->whereDate('tanggal_jaga', '>=', today())
                    ->with(['shiftTemplate', 'attendanceRecords'])
                    ->get()
                    ->map(function($schedule) {
                        return array_merge($schedule->toArray(), [
                            'real_time_status' => $schedule->getRealTimeStatus()
                        ]);
                    });

                echo "data: " . json_encode([
                    'type' => 'schedule_update',
                    'schedules' => $schedules,
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                
                ob_flush();
                flush();
                sleep(10); // 10-second intervals
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
```

---

## 🎨 **FRONTEND IMPLEMENTATIONS**

### **1. Real-Time WebSocket Manager**
```typescript
// resources/js/utils/WebSocketManager.ts
export class WebSocketManager {
    private connection: WebSocket | null = null;
    private listeners: Map<string, Function[]> = new Map();
    private reconnectAttempts = 0;
    private maxReconnectAttempts = 5;

    constructor(private url: string) {}

    connect(): Promise<void> {
        return new Promise((resolve, reject) => {
            try {
                this.connection = new WebSocket(this.url);
                
                this.connection.onopen = () => {
                    console.log('🔌 WebSocket connected');
                    this.reconnectAttempts = 0;
                    resolve();
                };

                this.connection.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    this.handleMessage(data);
                };

                this.connection.onclose = () => {
                    console.log('🔌 WebSocket disconnected');
                    this.handleReconnect();
                };

                this.connection.onerror = (error) => {
                    console.error('🔌 WebSocket error:', error);
                    reject(error);
                };
            } catch (error) {
                reject(error);
            }
        });
    }

    subscribe(channel: string, callback: Function): void {
        if (!this.listeners.has(channel)) {
            this.listeners.set(channel, []);
        }
        this.listeners.get(channel)!.push(callback);
    }

    private handleMessage(data: any): void {
        const { channel, event, payload } = data;
        const callbacks = this.listeners.get(channel) || [];
        callbacks.forEach(callback => callback(event, payload));
    }

    private handleReconnect(): void {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(`🔄 Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                this.connect();
            }, 1000 * this.reconnectAttempts);
        }
    }

    disconnect(): void {
        if (this.connection) {
            this.connection.close();
            this.connection = null;
        }
    }
}
```

### **2. Enhanced JadwalJaga Component**
```typescript
// Add to existing JadwalJaga component
import { WebSocketManager } from '../../utils/WebSocketManager';

const JadwalJaga = ({ userData, onNavigate }: JadwalJagaProps) => {
    // ... existing state ...
    const [wsManager, setWsManager] = useState<WebSocketManager | null>(null);
    const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected'>('disconnected');

    // Initialize WebSocket connection
    useEffect(() => {
        const initWebSocket = async () => {
            try {
                setConnectionStatus('connecting');
                const manager = new WebSocketManager(`ws://localhost:6001`);
                await manager.connect();
                
                // Subscribe to schedule updates
                manager.subscribe(`schedule.${userData?.id}`, handleRealTimeUpdate);
                manager.subscribe(`attendance.${userData?.id}`, handleAttendanceUpdate);
                
                setWsManager(manager);
                setConnectionStatus('connected');
                console.log('🚀 Real-time schedule updates enabled');
            } catch (error) {
                console.error('❌ WebSocket connection failed:', error);
                setConnectionStatus('disconnected');
                // Fallback to polling
                startPolling();
            }
        };

        if (userData?.id) {
            initWebSocket();
        }

        return () => {
            if (wsManager) {
                wsManager.disconnect();
            }
        };
    }, [userData?.id]);

    // Handle real-time schedule updates
    const handleRealTimeUpdate = useCallback((event: string, payload: any) => {
        console.log('📡 Real-time schedule update:', event, payload);
        
        switch (event) {
            case 'schedule.updated':
                setMissions(prev => prev.map(mission => 
                    mission.id === payload.schedule_id 
                        ? { ...mission, ...transformApiData([payload])[0] }
                        : mission
                ));
                break;
                
            case 'schedule.created':
                const newMission = transformApiData([payload])[0];
                setMissions(prev => [...prev, newMission]);
                break;
                
            case 'schedule.deleted':
                setMissions(prev => prev.filter(mission => mission.id !== payload.schedule_id));
                break;
        }
    }, []);

    // Handle real-time attendance updates
    const handleAttendanceUpdate = useCallback((event: string, payload: any) => {
        console.log('👤 Real-time attendance update:', event, payload);
        
        setMissions(prev => prev.map(mission => 
            mission.id === payload.jadwal_jaga_id 
                ? { 
                    ...mission, 
                    attendance: {
                        check_in_time: payload.time_in,
                        check_out_time: payload.time_out,
                        status: payload.status
                    }
                }
                : mission
        ));
    }, []);

    // Fallback polling when WebSocket unavailable
    const startPolling = useCallback(() => {
        const interval = setInterval(() => {
            fetchJadwalJaga(true);
        }, 30000); // 30s polling as fallback

        return () => clearInterval(interval);
    }, []);

    // Connection status indicator
    const ConnectionStatus = () => (
        <div className="flex items-center space-x-2 text-xs text-gray-400">
            <div className={`w-2 h-2 rounded-full ${
                connectionStatus === 'connected' ? 'bg-green-400' : 
                connectionStatus === 'connecting' ? 'bg-yellow-400 animate-pulse' : 
                'bg-red-400'
            }`} />
            <span>
                {connectionStatus === 'connected' ? 'Real-time' : 
                 connectionStatus === 'connecting' ? 'Connecting...' : 
                 'Offline mode'}
            </span>
        </div>
    );

    // Add to header section
    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
            {/* ... existing header ... */}
            <div className="text-center mb-6">
                {/* ... existing content ... */}
                <ConnectionStatus />
            </div>
            {/* ... rest of component ... */}
        </div>
    );
};
```

### **3. Server-Sent Events Integration**
```typescript
// resources/js/hooks/useServerSentEvents.ts
import { useEffect, useRef, useState } from 'react';

export const useServerSentEvents = (url: string, onMessage: (data: any) => void) => {
    const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected'>('disconnected');
    const eventSourceRef = useRef<EventSource | null>(null);

    useEffect(() => {
        const connectSSE = () => {
            setConnectionStatus('connecting');
            
            eventSourceRef.current = new EventSource(url);
            
            eventSourceRef.current.onopen = () => {
                console.log('📡 SSE connected');
                setConnectionStatus('connected');
            };

            eventSourceRef.current.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    onMessage(data);
                } catch (error) {
                    console.error('❌ SSE message parse error:', error);
                }
            };

            eventSourceRef.current.onerror = () => {
                console.error('❌ SSE connection error');
                setConnectionStatus('disconnected');
                
                // Reconnect after delay
                setTimeout(() => {
                    if (eventSourceRef.current?.readyState === EventSource.CLOSED) {
                        connectSSE();
                    }
                }, 5000);
            };
        };

        connectSSE();

        return () => {
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
            }
        };
    }, [url, onMessage]);

    return { connectionStatus };
};

// Usage in JadwalJaga component
const { connectionStatus } = useServerSentEvents(
    `/api/v2/stream/schedules/${userData?.id}`,
    handleSSEMessage
);
```

---

## 🛠 **DEPLOYMENT CONFIGURATION**

### **1. Laravel Broadcasting Setup**
```php
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ],
],

// Alternative: Laravel WebSockets
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],
```

### **2. Queue Configuration**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### **3. Routes Configuration**
```php
// routes/api.php
Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/realtime/schedules', [RealTimeScheduleController::class, 'getRealtimeUpdates']);
        Route::get('/stream/schedules/{userId}', [RealTimeScheduleController::class, 'scheduleStream']);
    });
});

// routes/channels.php
Broadcast::channel('schedule.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

---

## 📊 **PERFORMANCE OPTIMIZATION**

### **1. Caching Strategy**
```php
// Enhanced caching in JadwalJaga model
protected static function clearDashboardCacheForUser($userId)
{
    if (!$userId) return;
    
    $cacheKeys = [
        "realtime_schedule_{$userId}",
        "schedule_status_{$userId}",
        "attendance_data_{$userId}",
        // ... existing keys
    ];

    Cache::tags(['schedules', "user_{$userId}"])->flush();
    
    foreach ($cacheKeys as $key) {
        Cache::forget($key);
    }
}
```

### **2. Database Optimization**
```sql
-- Add indexes for real-time queries
CREATE INDEX idx_jadwal_jaga_realtime ON jadwal_jagas (pegawai_id, tanggal_jaga, updated_at);
CREATE INDEX idx_attendance_realtime ON attendances (jadwal_jaga_id, created_at);
```

### **3. Frontend Performance**
```typescript
// Debounced updates to prevent UI flickering
const debouncedUpdateMissions = useMemo(
    () => debounce((newMissions: Mission[]) => {
        setMissions(newMissions);
    }, 300),
    []
);

// Virtual scrolling for large datasets
const VirtualizedScheduleList = useMemo(() => (
    <FixedSizeList
        height={600}
        itemCount={missions.length}
        itemSize={120}
        itemData={missions}
    >
        {ScheduleCard}
    </FixedSizeList>
), [missions]);
```

---

## 🔒 **SECURITY CONSIDERATIONS**

### **1. Authentication**
```php
// Middleware for real-time endpoints
class RealTimeAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate user access to requested schedule data
        $userId = $request->route('userId');
        if (auth()->id() !== (int) $userId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
```

### **2. Rate Limiting**
```php
// config/services.php
'rate_limiting' => [
    'realtime_api' => '60,1', // 60 requests per minute
    'sse_stream' => '1,1',    // 1 connection per minute
],
```

### **3. Data Sanitization**
```typescript
// Frontend data validation
const validateScheduleData = (data: any): Mission | null => {
    if (!data.id || !data.tanggal_jaga || !data.shift_template) {
        console.warn('❌ Invalid schedule data received:', data);
        return null;
    }
    
    return {
        id: String(data.id),
        title: sanitizeString(data.title),
        date: sanitizeDate(data.tanggal_jaga),
        // ... other fields
    };
};
```

---

## 🧪 **TESTING STRATEGY**

### **1. Unit Tests**
```php
// tests/Unit/RealTimeScheduleTest.php
class RealTimeScheduleTest extends TestCase
{
    /** @test */
    public function it_broadcasts_schedule_updates()
    {
        Event::fake();
        
        $jadwal = JadwalJaga::factory()->create();
        $jadwal->update(['status_jaga' => 'Aktif']);
        
        Event::assertDispatched(ScheduleUpdated::class);
    }

    /** @test */
    public function it_calculates_real_time_status_correctly()
    {
        Carbon::setTestNow('2025-08-18 10:00:00');
        
        $jadwal = JadwalJaga::factory()->create([
            'tanggal_jaga' => today(),
            // shift: 08:00 - 16:00
        ]);
        
        $status = $jadwal->getRealTimeStatus();
        
        $this->assertEquals('active', $status['status']);
    }
}
```

### **2. Integration Tests**
```typescript
// Frontend testing with Jest
describe('Real-time JadwalJaga', () => {
    it('should handle WebSocket updates', async () => {
        const mockWS = new MockWebSocket();
        const component = render(<JadwalJaga userData={mockUser} />);
        
        // Simulate schedule update
        mockWS.send({
            channel: 'schedule.1',
            event: 'schedule.updated',
            payload: { schedule_id: 1, status_jaga: 'Aktif' }
        });
        
        expect(component.getByText('ACTIVE')).toBeInTheDocument();
    });
});
```

---

## 📈 **MONITORING & ANALYTICS**

### **1. Performance Metrics**
```php
// Real-time performance tracking
class RealTimeMetrics
{
    public static function trackWebSocketConnections(): void
    {
        Cache::increment('websocket_connections');
    }

    public static function trackScheduleUpdates(): void
    {
        Cache::increment('schedule_updates_today', 1, 86400);
    }

    public static function getMetrics(): array
    {
        return [
            'active_connections' => Cache::get('websocket_connections', 0),
            'daily_updates' => Cache::get('schedule_updates_today', 0),
            'average_response_time' => Cache::get('avg_response_time', 0),
        ];
    }
}
```

### **2. Error Tracking**
```typescript
// Frontend error monitoring
const trackRealTimeError = (error: Error, context: string) => {
    console.error(`🚨 Real-time error in ${context}:`, error);
    
    // Send to monitoring service
    if (window.analytics) {
        window.analytics.track('real_time_error', {
            error: error.message,
            context,
            timestamp: Date.now(),
            user_id: userData?.id
        });
    }
};
```

---

## 🎯 **SUCCESS METRICS**

### **Key Performance Indicators**
- ⚡ **Real-time Update Latency**: < 500ms
- 🔄 **WebSocket Connection Uptime**: > 99%
- 📱 **Mobile Performance**: < 2s load time
- 🎮 **Gaming UI Responsiveness**: < 100ms interactions
- 📊 **Data Accuracy**: 100% schedule-attendance sync

### **User Experience Metrics**
- 🚀 **Perceived Performance**: Real-time badge updates
- 📈 **Engagement**: Increased time on schedule page
- ✅ **Task Completion**: Faster check-in/check-out flows
- 😊 **User Satisfaction**: Gaming-style visual feedback

---

## 🔄 **ROLLOUT PLAN**

### **Phase 1: Enhanced Polling (Current - Week 1)**
- ✅ Optimize current 60s refresh cycle
- ✅ Improve error handling and fallbacks
- ✅ Add connection status indicators

### **Phase 2: WebSocket Integration (Week 2-3)**
- 🔄 Implement Laravel Broadcasting
- 🔄 Add WebSocket manager to frontend
- 🔄 Deploy real-time event system

### **Phase 3: Performance Optimization (Week 4)**
- ⚡ Add caching layers
- ⚡ Implement virtual scrolling
- ⚡ Optimize database queries

### **Phase 4: Advanced Features (Week 5-6)**
- 🚀 Server-Sent Events fallback
- 🚀 Push notifications
- 🚀 Offline capability with sync

---

*This implementation provides a comprehensive real-time system while maintaining the existing gaming-style UI and ensuring robust fallbacks for maximum reliability.*