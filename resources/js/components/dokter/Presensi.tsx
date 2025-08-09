import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { Calendar, Clock, DollarSign, User, Home, MapPin, CheckCircle, XCircle, Zap, Heart, Brain, Shield, Target, Award, TrendingUp, Sun, Moon, Coffee, Star, Crown, Hand, Camera, Wifi, WifiOff, AlertTriangle, AlertCircle, History, UserCheck, FileText, Settings, Bell, ChevronLeft, ChevronRight, Filter, Plus, Send, RefreshCw, Navigation } from 'lucide-react';
import DynamicMap from './DynamicMap';
import { useGPSLocation, useGPSAvailability, useGPSPermission } from '../../hooks/useGPSLocation';
import { GPSStatus, GPSStrategy } from '../../utils/GPSManager';
import getUnifiedAuthInstance from '../../utils/UnifiedAuth';
import '../../../css/map-styles.css';

const CreativeAttendanceDashboard = () => {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [activeTab, setActiveTab] = useState('checkin');
  const [attendanceData, setAttendanceData] = useState({
    checkInTime: null as string | null,
    checkOutTime: null as string | null,
    workingHours: '00:00:00',
    overtimeHours: '00:00:00',
    breakTime: '00:00:00',
    location: 'RS. Kediri Medical Center'
  });
  // Multi-shift aware: keep all today's attendance records if provided by API
  const [todayRecords, setTodayRecords] = useState<any[]>([]);

  // User Data State
  const [userData, setUserData] = useState<{
    name: string;
    email: string;
    role: string;
  } | null>(null);

  // Jadwal Jaga dan Work Location State
  const [scheduleData, setScheduleData] = useState({
    todaySchedule: null as any,
    currentShift: null as any,
    workLocation: null as any,
    isOnDuty: false,
    canCheckIn: false,
    canCheckOut: false,
    validationMessage: ''
  });

  // Server time offset and shift window tracking for live clock/hints
  const serverOffsetRef = useRef<number>(0);
  const shiftTimesRef = useRef<{
    startMs: number;
    endMs: number;
    startBufMs: number;
    endBufMs: number;
  } | null>(null);
  const [clockNow, setClockNow] = useState<string>('');
  const [shiftTimeHint, setShiftTimeHint] = useState<string>('');
  const [remainingShiftMs, setRemainingShiftMs] = useState(0);

  const formatHHMMSS = (date: Date) => {
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
  };

  const formatDuration = (ms: number) => {
    const s = Math.max(0, Math.floor(ms / 1000));
    const hh = Math.floor(s / 3600)
      .toString()
      .padStart(2, '0');
    const mm = Math.floor((s % 3600) / 60)
      .toString()
      .padStart(2, '0');
    const ss = Math.floor(s % 60)
      .toString()
      .padStart(2, '0');
    return `${hh}:${mm}:${ss}`;
  };

  // Helper: local date string YYYY-MM-DD (avoid UTC shift)
  const getLocalDateStr = () => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  };

  // Helper: parse today's HH:MM(:SS) into Date (bind to today). If ISO, returns Date directly
  const parseTodayTimeToDate = (timeStr?: string | null): Date | null => {
    if (!timeStr) return null;
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(timeStr)) {
      const now = new Date();
      const [hh, mm, ss] = timeStr.split(':').map(Number);
      return new Date(now.getFullYear(), now.getMonth(), now.getDate(), hh || 0, mm || 0, ss || 0);
    }
    const d = new Date(timeStr);
    return isNaN(d.getTime()) ? null : d;
  };

  // Derive current shift's attendance record (multi-shift aware)
  const currentShiftRecord = useMemo(() => {
    const cs: any = scheduleData.currentShift as any;
    const currentShiftId = cs?.id || cs?.jadwal_jaga_id;
    if (currentShiftId && todayRecords?.length) {
      return todayRecords.find((r: any) => r.jadwal_jaga_id === currentShiftId) || null;
    }
    return null;
  }, [scheduleData.currentShift, todayRecords]);

  const displayCheckInDate = useMemo(() => {
    if (currentShiftRecord) return parseTodayTimeToDate(currentShiftRecord.time_in);
    return attendanceData.checkInTime ? new Date(attendanceData.checkInTime) : null;
  }, [currentShiftRecord, attendanceData.checkInTime]);

  const displayCheckOutDate = useMemo(() => {
    if (currentShiftRecord) return parseTodayTimeToDate(currentShiftRecord.time_out);
    return attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime) : null;
  }, [currentShiftRecord, attendanceData.checkOutTime]);

  // Target jam kerja mengikuti shift jaga (bukan fixed 8 jam)
  const computeTargetHours = (): number | null => {
    const shift = scheduleData?.currentShift?.shift_template;
    if (!shift) return null;
    if (typeof shift.durasi_jam === 'number' && isFinite(shift.durasi_jam) && shift.durasi_jam > 0) {
      return shift.durasi_jam;
    }
    const jamMasuk = shift.jam_masuk as string | undefined;
    const jamPulang = shift.jam_pulang as string | undefined;
    if (jamMasuk && jamPulang) {
      const [sh, sm] = jamMasuk.split(':').map(Number);
      const [eh, em] = jamPulang.split(':').map(Number);
      const startMinutes = sh * 60 + sm;
      const endMinutes = eh * 60 + em;
      let durationMin = endMinutes - startMinutes;
      if (durationMin < 0) durationMin += 24 * 60; // overnight
      return durationMin / 60;
    }
    return null;
  };
  // Compute worked time within shift window per operational rules
  const computeShiftStats = () => {
    const shift = scheduleData?.currentShift?.shift_template;
    if (!shift) return { workedMs: 0, durasiMs: 0 };
    const now = new Date();
    const [sh, sm] = (shift.jam_masuk || '00:00').split(':').map(Number);
    const [eh, em] = (shift.jam_pulang || '00:00').split(':').map(Number);
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), sh || 0, sm || 0, 0);
    let end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), eh || 0, em || 0, 0);
    if (end.getTime() < start.getTime()) end = new Date(end.getTime() + 24 * 60 * 60 * 1000);
    const durasiMs = Math.max(0, end.getTime() - start.getTime());

    // Determine in/out from records
    const parseTOD = (dateStr?: string | null) => {
      if (!dateStr) return null;
      // If dateStr looks like HH:MM, bind to today; else assume ISO
      if (/^\d{2}:\d{2}(:\d{2})?$/.test(dateStr)) {
        const [hh, mm, ss] = dateStr.split(':').map(Number);
        const d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hh || 0, mm || 0, ss || 0);
        return d;
      }
      const d = new Date(dateStr);
      return isNaN(d.getTime()) ? null : d;
    };

    // Collect in/out for current shift only
    const ins: Date[] = [];
    const outs: Date[] = [];
    const currentShiftId = (scheduleData.currentShift as any)?.id || (scheduleData.currentShift as any)?.jadwal_jaga_id;
    if (currentShiftId && todayRecords && todayRecords.length) {
      const rec = todayRecords.find((r: any) => r.jadwal_jaga_id === currentShiftId);
      if (rec) {
        const tin = parseTOD(rec.time_in);
        if (tin) ins.push(tin);
        const tout = parseTOD(rec.time_out);
        if (tout) outs.push(tout);
      }
    } else {
      const tin = parseTOD(attendanceData.checkInTime);
      if (tin) ins.push(tin);
      const tout = parseTOD(attendanceData.checkOutTime);
      if (tout) outs.push(tout);
    }

    // in = waktu_cek_in_pertama (valid), choose earliest
    const inTime = ins.length ? new Date(Math.min(...ins.map(d => d.getTime()))) : null;
    // Multi cek-out → pilih cek-out terdekat ke end namun ≤ end
    let outTime: Date | null = null;
    const outsBeforeOrAtEnd = outs.filter(d => d.getTime() <= end.getTime());
    if (outsBeforeOrAtEnd.length) {
      outTime = new Date(Math.max(...outsBeforeOrAtEnd.map(d => d.getTime())));
    } else {
      // If none ≤ end and there is an open attendance, use now; else if outs exist > end, clamp to end
    const hasOpen = (todayRecords && todayRecords.length)
        ? todayRecords.some((r: any) => r.jadwal_jaga_id === currentShiftId && !!r.time_in && !r.time_out)
        : (!!attendanceData.checkInTime && !attendanceData.checkOutTime);
      if (hasOpen) {
        outTime = now;
      } else if (outs.length) {
        outTime = end;
      }
    }

    // Apply clamping
    const effectiveIn = inTime ? new Date(Math.max(inTime.getTime(), start.getTime())) : null;
    const baseOut = outTime || now;
    const effectiveOut = new Date(Math.min(baseOut.getTime(), end.getTime()));

    let workedMs = 0;
    if (effectiveIn && effectiveOut.getTime() > effectiveIn.getTime()) {
      workedMs = effectiveOut.getTime() - effectiveIn.getTime();
    }
    return { workedMs, durasiMs };
  };
  const computeProgressPercent = () => {
    const { workedMs, durasiMs } = computeShiftStats();
    if (!durasiMs) return 0;
    const pct = Math.min(100, (workedMs / durasiMs) * 100);
    return Number.isFinite(pct) ? pct : 0;
  };

  // Debug progress calculation in console when check-in/out changes
  useEffect(() => {
    const { workedMs, durasiMs } = computeShiftStats();
    if (!attendanceData.checkInTime) {
      console.log('🧮 Progress Debug: no check-in yet');
      return;
    }
    const startIso = new Date(attendanceData.checkInTime).toISOString();
    const endIso = attendanceData.checkOutTime
      ? new Date(attendanceData.checkOutTime).toISOString()
      : new Date().toISOString();
    const pct = computeProgressPercent();
    console.log('🧮 Progress Debug', {
      checkInTime: startIso,
      checkOutTime: attendanceData.checkOutTime ? endIso : '(live now)',
      targetHours: (durasiMs / 3600000).toFixed(2),
      workedHours: (workedMs / 3600000).toFixed(2),
      percent: pct.toFixed(1)
    });
  }, [attendanceData.checkInTime, attendanceData.checkOutTime]);

  // Countdown kekurangan: berjalan setelah jam mulai shift (bukan dari check-in)
  // Jika shift belum mulai, kekurangan masih penuh
  // Jika shift sudah mulai, kekurangan berkurang berdasarkan waktu dari jam mulai shift
  useEffect(() => {
    const st = scheduleData.currentShift?.shift_template?.jam_masuk;
    const et = scheduleData.currentShift?.shift_template?.jam_pulang;
    if (!st || !et) { setRemainingShiftMs(0); return; }

    const now = new Date();
    const [sh, sm] = st.split(':').map(Number);
    const [eh, em] = et.split(':').map(Number);

    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), sh, sm, 0);
    let end   = new Date(now.getFullYear(), now.getMonth(), now.getDate(), eh, em, 0);
    if (end.getTime() < start.getTime()) end = new Date(end.getTime() + 24*60*60*1000);

    const totalMs = Math.max(0, end.getTime() - start.getTime());

    let t: number | undefined;
    const tick = () => {
      const n = new Date(Date.now() + (serverOffsetRef.current || 0));
      const base = Math.max(n.getTime(), start.getTime()); // sebelum start → clamp ke start
      const remaining = Math.max(0, end.getTime() - base);
      setRemainingShiftMs(Math.min(remaining, totalMs));
      t = window.setTimeout(tick, 1000) as unknown as number;
    };
    tick();
    return () => { if (t) { window.clearTimeout(t); } };
  }, [scheduleData.currentShift?.shift_template?.jam_masuk, scheduleData.currentShift?.shift_template?.jam_pulang]);
  
  // Hospital Location Data (Dynamic - dari API)
  const [hospitalLocation, setHospitalLocation] = useState({
    lat: -7.8481, // Default Kediri coordinates
    lng: 112.0178,
    name: 'Loading...',
    address: 'Loading...',
    radius: 50 // meters
  });

  const memoizedFallbackLocation = useMemo(() => ({
    lat: hospitalLocation.lat,
    lng: hospitalLocation.lng
  }), [hospitalLocation.lat, hospitalLocation.lng]);
  
  // Memoized time update to prevent unnecessary re-renders
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(prevTime => {
        const newTime = new Date();
        // Only update if time actually changed
        return prevTime.getTime() !== newTime.getTime() ? newTime : prevTime;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Optimized user data loading with proper error handling
  useEffect(() => {
    let isMounted = true;
    let retryCount = 0;
    const maxRetries = 3;

    const loadUserData = async () => {
      try {
        console.log('🔍 Starting user data load...');
        
        // Get token with better error handling
        let token = localStorage.getItem('auth_token');
        console.log('🔍 Token from localStorage:', token ? 'Found' : 'Not found');
        
        if (!token) {
          const csrfMeta = document.querySelector('meta[name="csrf-token"]');
          token = csrfMeta?.getAttribute('content') || '';
          console.log('🔍 Token from meta tag:', token ? 'Found' : 'Not found');
        }

        // Validate token before making request
        if (!token) {
          console.warn('No authentication token found');
          if (isMounted) {
            setUserData({
              name: 'Guest User',
              email: 'guest@example.com',
              role: 'guest'
            });
          }
          return;
        }

        console.log('🔍 Making API request to /api/v2/dashboards/dokter/');
        
        // Simple fetch without complex URL construction
        const response = await fetch('/api/v2/dashboards/dokter/', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token
          },
          credentials: 'same-origin'
        });

        console.log('🔍 Response status:', response.status);
        console.log('🔍 Response ok:', response.ok);

        if (!isMounted) return;

        // Check content type before parsing
        const contentType = response.headers.get("content-type");
        console.log('🔍 Content-Type:', contentType);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        if (!contentType || !contentType.includes('application/json')) {
          throw new Error('Response is not JSON');
        }

        const data = await response.json();
        console.log('🔍 Parsed response data:', data);

        if (data.success && data.data?.user) {
          if (isMounted) {
            setUserData(prevData => {
              const newData = data.data.user;
              // Only update if data actually changed
              return JSON.stringify(prevData) !== JSON.stringify(newData) ? newData : prevData;
            });
            console.log('✅ User data updated successfully');
          }
        } else {
          console.warn('No user data in response');
          if (isMounted) {
            setUserData({
              name: 'Unknown User',
              email: 'unknown@example.com',
              role: 'unknown'
            });
          }
        }
      } catch (error) {
        if (!isMounted) return;
        
        console.error('❌ Error loading user data:', error);
        
        // Retry logic for network errors
        const errorMessage = error instanceof Error ? error.message : String(error);
        if (retryCount < maxRetries && (errorMessage.includes('network') || errorMessage.includes('fetch'))) {
          retryCount++;
          console.log(`🔄 Retrying user data load... (${retryCount}/${maxRetries})`);
          setTimeout(() => loadUserData(), 1000 * retryCount);
          return;
        }
        
        // Set fallback data on error
        setUserData({
          name: 'Error Loading User',
          email: 'error@example.com',
          role: 'error'
        });
      }
    };

    loadUserData();

    return () => {
      isMounted = false;
    };
  }, []); // Empty dependency array - only run once on mount

  // Optimized schedule data loading
  useEffect(() => {
    let isMounted = true;

    const loadScheduleData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!token) return;

        const response = await fetch('/api/v2/dashboards/dokter/jadwal-jaga', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });

        if (!isMounted) return;

        if (response.ok) {
          const data = await response.json();
          if (data.success && data.data) {
            setScheduleData(prevData => {
              const newData = {
                todaySchedule: data.data.today || null,
                currentShift: data.data.currentShift || null,
                workLocation: data.data.workLocation || null,
                isOnDuty: data.data.currentShift ? true : false,
                canCheckIn: !data.data.currentShift || !data.data.currentShift.checkInTime,
                canCheckOut: data.data.currentShift && data.data.currentShift.checkInTime && !data.data.currentShift.checkOutTime,
                validationMessage: ''
              };
              
              // Only update if data actually changed
              return JSON.stringify(prevData) !== JSON.stringify(newData) ? newData : prevData;
            });
          }
        }
      } catch (error) {
        if (!isMounted) return;
        console.error('Error loading schedule data:', error);
      }
    };

    loadScheduleData();

    return () => {
      isMounted = false;
    };
  }, []); // Empty dependency array - only run once on mount

  // Memoized time formatting
  const formatTime = useCallback((date: Date): string => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit',
      second: '2-digit'
    });
  }, []);

  // Memoized greeting calculation
  const getGreeting = useCallback(() => {
    const hour = currentTime.getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 17) return 'Selamat Siang';
    return 'Selamat Malam';
  }, [currentTime]);

  // Memoized attendance status
  const attendanceStatus = useMemo(() => {
    if (isCheckedIn) {
      return {
        status: 'checked-in',
        text: 'Sudah Check-in',
        color: 'text-green-500',
        bgColor: 'bg-green-100',
        icon: CheckCircle
      };
    }
    return {
      status: 'not-checked-in',
      text: 'Belum Check-in',
      color: 'text-red-500',
      bgColor: 'bg-red-100',
      icon: XCircle
    };
  }, [isCheckedIn]);

  // World-Class GPS Integration
  const gpsAvailability = useGPSAvailability();
  const gpsPermission = useGPSPermission();
  const {
    location: gpsLocation,
    status: gpsStatus,
    error: gpsError,
    isLoading: gpsLoading,
    accuracy: gpsAccuracy,
    confidence: gpsConfidence,
    source: gpsSource,
    getCurrentLocation,
    watchPosition,
    stopWatching,
    requestPermission,
    retryLocation,
    clearCache,
    distanceToLocation,
    isWithinRadius,
    getDiagnostics
  } = useGPSLocation({
    autoStart: true,
    watchMode: false,
    fallbackLocation: memoizedFallbackLocation,
    onError: (error) => {
      console.error('🚨 GPS Error:', error);
    },
    onPermissionDenied: () => {
      console.warn('⚠️ GPS Permission Denied');
    },
    enableHighAccuracy: true
  });
  
  // Legacy state mapping for compatibility
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number; accuracy?: number } | null>(null);
  const [distanceToHospital, setDistanceToHospital] = useState<number | null>(null);
  
  // Sync GPS location with legacy state
  useEffect(() => {
    if (gpsLocation) {
      const location = {
        lat: gpsLocation.latitude,
        lng: gpsLocation.longitude,
        accuracy: gpsLocation.accuracy
      };
      setUserLocation(location);
      
      // Calculate distance using hook utility
      const distance = distanceToLocation(hospitalLocation.lat, hospitalLocation.lng);
      setDistanceToHospital(distance);
      
      // Log GPS diagnostics
      const diagnostics = getDiagnostics();
      console.log('🌍 GPS Diagnostics:', {
        status: gpsStatus,
        source: gpsSource,
        confidence: `${(gpsConfidence * 100).toFixed(0)}%`,
        accuracy: `${gpsAccuracy?.toFixed(0)}m`,
        distance: distance ? `${distance.toFixed(0)}m` : 'N/A',
        withinRadius: isWithinRadius(hospitalLocation.lat, hospitalLocation.lng, hospitalLocation.radius),
        ...diagnostics
      });
    }
  }, [gpsLocation, hospitalLocation, distanceToLocation, isWithinRadius, getDiagnostics, gpsStatus, gpsSource, gpsConfidence, gpsAccuracy]);
  
  // Handle GPS availability messages
  useEffect(() => {
    if (!gpsAvailability.available && gpsAvailability.reason) {
      console.warn('⚠️ GPS Not Available:', gpsAvailability.reason);
      
      if (gpsAvailability.reason.includes('HTTPS')) {
        const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
        if (!isLocalhost) {
          alert('⚠️ GPS tidak tersedia di koneksi HTTP.\n\nUntuk menggunakan GPS, akses aplikasi melalui:\n• HTTPS (https://)\n• Localhost untuk development\n\nMenggunakan lokasi default untuk testing.');
        }
      }
    }
  }, [gpsAvailability]);
  
  // Handle GPS permission changes
  useEffect(() => {
    console.log('🔒 GPS Permission Status:', gpsPermission);
    
    if (gpsPermission === 'denied') {
      console.warn('⚠️ GPS Permission Denied - User needs to enable location access');
    } else if (gpsPermission === 'prompt') {
      console.log('💡 GPS Permission will be requested when needed');
    }
  }, [gpsPermission]);
  
  // Refresh GPS location handler
  const handleRefreshGPS = useCallback(async () => {
    console.log('🔄 Refreshing GPS location...');
    clearCache();
    await retryLocation();
  }, [clearCache, retryLocation]);
  
  // Request GPS permission handler
  const handleRequestPermission = useCallback(async () => {
    console.log('📍 Requesting GPS permission...');
    const granted = await requestPermission();
    
    if (granted) {
      console.log('✅ GPS permission granted');
    } else {
      console.warn('❌ GPS permission denied');
      alert('Izin GPS ditolak. Silakan aktifkan akses lokasi di pengaturan browser.');
    }
  }, [requestPermission]);

  // Load schedule and work location data with force refresh option
  const loadScheduleAndWorkLocation = async (forceRefresh = false) => {
      // Declare variables at the beginning for proper scoping
      let todaySchedule: any[] = [];
      let currentShift: any = null;
      let wl: any = null;

      try {
        // Use UnifiedAuth for proper authentication
        const unifiedAuth = getUnifiedAuthInstance();
        const authHeaders = unifiedAuth.getAuthHeaders();

      // Add timestamp and refresh parameter to prevent caching
      const timestamp = Date.now();
      const refreshParam = forceRefresh ? '&refresh=1' : '';
      
      // Always request fresh data and include session cookies
      const scheduleResponse = await fetch(`/api/v2/dashboards/dokter/jadwal-jaga?t=${timestamp}${refreshParam}`, {
          method: 'GET',
        headers: {
          ...authHeaders,
          'Cache-Control': forceRefresh ? 'no-cache' : 'max-age=30',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
        });

        console.log('🔍 Schedule response status:', scheduleResponse.status);

        // Check content type for schedule response
        const scheduleContentType = scheduleResponse.headers.get("content-type");
        console.log('🔍 Schedule Content-Type:', scheduleContentType);
        
        if (!scheduleContentType || !scheduleContentType.includes("application/json")) {
          console.error('❌ Schedule API returned non-JSON response. Content-Type:', scheduleContentType);
          throw new Error(`Schedule API returned non-JSON response: ${scheduleContentType}`);
        }

        if (scheduleResponse.ok) {
          const scheduleData = await scheduleResponse.json();
          
          // Build today's schedules from API; ignore backend currentShift for deterministic selection
          todaySchedule = Array.isArray(scheduleData?.data?.today) ? scheduleData.data.today : [];
          currentShift = null;

          // Fallback parsing if backend fields unavailable
          if ((!todaySchedule || todaySchedule.length === 0)) {
            const today = getLocalDateStr();
            let dataArray: any[] = [];
          if (Array.isArray(scheduleData.data)) {
            dataArray = scheduleData.data;
          } else if (scheduleData.data && typeof scheduleData.data === 'object') {
            if (scheduleData.data.weekly_schedule) {
              dataArray = Array.isArray(scheduleData.data.weekly_schedule) ? scheduleData.data.weekly_schedule : [];
            } else if (scheduleData.data.calendar_events) {
              dataArray = Array.isArray(scheduleData.data.calendar_events) ? scheduleData.data.calendar_events : [];
            }
          }
            // Include all schedules for today regardless of status label (normalize date)
            const getDateStr = (val: any) => {
              if (!val) return '';
              if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
              const d = new Date(val);
              if (!isNaN(d.getTime())) return d.toISOString().slice(0, 10);
              return String(val).slice(0, 10);
            };
            todaySchedule = dataArray.filter((schedule: any) => {
              const scheduleDateStr = getDateStr(schedule.tanggal_jaga || schedule.start || schedule.date || schedule.start_time);
              return scheduleDateStr === today;
            });
          }

          // IMPROVED LOGIC: Always show the nearest schedule (upcoming or current)
          if (!currentShift && todaySchedule && todaySchedule.length > 0) {
            const now = new Date();
            const toSeconds = (hhmm: string) => {
              if (!hhmm || typeof hhmm !== 'string') return NaN;
              const parts = hhmm.split(':');
              const h = parseInt(parts[0], 10);
              const m = parseInt(parts[1], 10) || 0;
              if (isNaN(h) || isNaN(m)) return NaN;
              return h * 3600 + m * 60;
            };
            const getStart = (s: any) => s?.shift_template?.jam_masuk || s?.shift_info?.jam_masuk || s?.shift_info?.jam_masuk_format;
            const getEnd = (s: any) => s?.shift_template?.jam_pulang || s?.shift_info?.jam_pulang || s?.shift_info?.jam_pulang_format;
            const nowSec = now.getHours() * 3600 + now.getMinutes() * 60;
            // Use tolerance from work location if available, otherwise default to 30 minutes
            const toleranceMinutes = wl?.checkin_before_shift_minutes || wl?.tolerance_settings?.checkin_before_shift_minutes || 30;
            const bufferSec = toleranceMinutes * 60;

            // Ensure morning-first ordering for display and selection
            todaySchedule = [...todaySchedule].sort((a: any, b: any) => (toSeconds(getStart(a)) || 0) - (toSeconds(getStart(b)) || 0));

            const normalized = todaySchedule
              .map((s: any) => {
                const stRaw = getStart(s);
                const etRaw = getEnd(s);
                const st = toSeconds(stRaw);
                const et = toSeconds(etRaw);
                if (isNaN(st) || isNaN(et)) return null;
                
                // Calculate if shift is overnight
                const overnight = et < st;
                const startWithBuffer = st - bufferSec; // pre-shift buffer allowed
                const endStrict = et; // DO NOT extend after end; prevents stale current shift
                
                // Handle overnight shifts
                // If overnight and now before start, map end on next day context for comparison only
                const endCompare = overnight && nowSec < st ? (et + 24 * 3600) : endStrict;
                
                const currentNow = overnight && endCompare < st ? nowSec + 24 * 3600 : nowSec;
                
                // Check if currently in shift time (with buffer)
                const isCurrent = currentNow >= startWithBuffer && currentNow <= endCompare;
                
                // Check if shift hasn't started yet
                const isUpcoming = !isCurrent && (st > nowSec);
                
                // Calculate distance to shift start time for sorting
                const distanceToStart = Math.abs(st - nowSec);
                
                return { 
                  raw: s, 
                  st, 
                  et, 
                  isCurrent, 
                  isUpcoming,
                  distanceToStart,
                  startTime: stRaw,
                  endTime: etRaw
                };
              })
              .filter(Boolean) as Array<{ 
                raw: any; 
                st: number; 
                et: number; 
                isCurrent: boolean; 
                isUpcoming: boolean;
                distanceToStart: number;
                startTime: string;
                endTime: string;
              }>;

            // PRIORITY LOGIC:
            // 1. First check if we're currently in a shift (within buffer time)
            const current = normalized.find(n => n.isCurrent);
            if (current) {
              console.log('📍 Current shift found:', current.startTime, '-', current.endTime);
              currentShift = current.raw;
            } else {
              // 2. If not in a shift, find the NEAREST upcoming shift
              const upcoming = normalized
                .filter(n => n.isUpcoming)
                .sort((a, b) => a.distanceToStart - b.distanceToStart)[0];
              
              if (upcoming) {
                console.log('⏰ Next upcoming shift:', upcoming.startTime, '-', upcoming.endTime);
                currentShift = upcoming.raw;
              } else {
                // 3. If no upcoming shifts today, show the most recent past shift (for reference)
                const pastShifts = normalized
                  .filter(n => !n.isUpcoming && !n.isCurrent)
                  .sort((a, b) => b.st - a.st); // Sort by latest first
                
                if (pastShifts.length > 0) {
                  const mostRecent = pastShifts[0];
                  console.log('📋 Showing most recent past shift:', mostRecent.startTime, '-', mostRecent.endTime);
                  currentShift = mostRecent.raw;
                } else {
                  // Fallback to first schedule if nothing else
                  currentShift = todaySchedule[0];
                }
              }
            }
            
            // Log all schedules for debugging
            console.log('📅 All schedules for today:', normalized.map(n => ({
              time: `${n.startTime} - ${n.endTime}`,
              isCurrent: n.isCurrent,
              isUpcoming: n.isUpcoming,
              distance: n.distanceToStart
            })));
          }
          
          // Compute final values to be applied and used immediately for validation
          const finalTodaySchedule = (Array.isArray(todaySchedule) && todaySchedule.length > 0)
            ? todaySchedule
            : (Array.isArray(scheduleData.todaySchedule) ? scheduleData.todaySchedule : []);
          const finalCurrentShift = currentShift || scheduleData.currentShift;

          setScheduleData(prev => ({
            ...prev,
            todaySchedule: finalTodaySchedule,
            currentShift: finalCurrentShift
          }));
        } else {
          console.error('Failed to load schedule data:', scheduleResponse.status, scheduleResponse.statusText);
        }

        // Fetch work location status with session credentials
        const workLocationResponse = await fetch('/api/v2/dashboards/dokter/work-location/status', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });

        console.log('🔍 Work location response status:', workLocationResponse.status);

        // Check content type for work location response
        const workLocationContentType = workLocationResponse.headers.get("content-type");
        console.log('🔍 Work Location Content-Type:', workLocationContentType);
        
        if (!workLocationContentType || !workLocationContentType.includes("application/json")) {
          console.error('❌ Work Location API returned non-JSON response. Content-Type:', workLocationContentType);
          throw new Error(`Work Location API returned non-JSON response: ${workLocationContentType}`);
        }

        if (workLocationResponse.ok) {
          const workLocationData = await workLocationResponse.json();
          console.log('Work Location API Response:', workLocationData);
          wl = workLocationData.data?.work_location || null;
          // Update schedule data
          setScheduleData(prev => ({
            ...prev,
            workLocation: wl
          }));
          // CRITICAL: Sync hospitalLocation used for distance checks and map pin
          if (wl && wl.coordinates && typeof wl.coordinates.latitude === 'number' && typeof wl.coordinates.longitude === 'number') {
            setHospitalLocation(prev => ({
              lat: wl.coordinates.latitude,
              lng: wl.coordinates.longitude,
              name: wl.name || prev.name,
              address: wl.address || prev.address,
              radius: typeof wl.radius_meters === 'number' ? wl.radius_meters : prev.radius
            }));
          }
        } else {
          console.error('Failed to load work location data:', workLocationResponse.status, workLocationResponse.statusText);
        }

        // Validate current status using freshly computed values to avoid stale state
        validateCurrentStatus({
          currentShift: currentShift || scheduleData.currentShift,
          todaySchedule: (Array.isArray(todaySchedule) && todaySchedule.length > 0)
            ? todaySchedule
            : (Array.isArray(scheduleData.todaySchedule) ? scheduleData.todaySchedule : []),
          workLocation: (typeof wl !== 'undefined' ? wl : scheduleData.workLocation)
        });
      } catch (error) {
        console.error('Error loading schedule and work location:', error);
        // Preserve previous state to avoid UI regressions during transient failures
        setScheduleData(prev => ({
          ...prev,
          validationMessage: 'Gagal memuat data jadwal dan lokasi kerja'
        }));
      }
    };

  // Use the function in useEffect
  useEffect(() => {
    // Force fresh load on first mount to avoid any stale data
    loadScheduleAndWorkLocation(true);
    loadTodayAttendance();
    loadAttendanceHistory(filterPeriod); // Load attendance history on mount

    // Lightweight polling for near-realtime updates
    const intervalId = window.setInterval(() => {
      // Force refresh to avoid stale/empty response causing flicker
      loadScheduleAndWorkLocation(true);
      loadTodayAttendance();
      loadAttendanceHistory(filterPeriod); // Also refresh history periodically
    }, 30000); // 30s - reasonable interval to prevent excessive API calls

    return () => {
      window.clearInterval(intervalId);
    };
  }, []);

  // Validate status when schedule data changes - removed to prevent infinite loop
  // validateCurrentStatus is already called after loadScheduleAndWorkLocation
  // No need for separate useEffect that creates circular dependency

  // Load today's attendance (time_in/time_out) to sync UI with backend
  const loadTodayAttendance = useCallback(async () => {
    // Do not reset state pre-emptively to avoid disabling checkout briefly during refresh
    try {
      const resp = await fetch('/api/v2/dashboards/dokter/presensi?include_all=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!resp.ok) return;
      const json = await resp.json();
      // Backend already returns only today's records in today_records
      const allRecords = Array.isArray(json?.data?.today_records) ? json.data.today_records : [];
      const todayPayload = json?.data?.today || null;
      const localToday = getLocalDateStr();
      const records = allRecords; // trusted as today's by API contract
      // Persist today's records for checkout gating logic
      setTodayRecords(records);
      // Detect any open attendance strictly for today
      let hasOpen = records.some((r: any) => !!r.time_in && !r.time_out);
      // Fallback: if today payload indicates open, honor it
      if (!hasOpen && todayPayload && todayPayload.time_in && !todayPayload.time_out) {
        hasOpen = true;
      }
      // Pick the latest record for display, but do NOT auto-mark as checked-in unless hasOpen
      const latest = records.length ? records[records.length - 1] : null;
      if (latest) {
        const dateStr = (todayPayload?.date) || localToday;
        const toIso = (t?: string | null) => (t ? new Date(`${dateStr}T${t}:00`).toISOString() : null);
        setIsCheckedIn(hasOpen);
        setAttendanceData(prev => ({
          ...prev,
          checkInTime: toIso(latest.time_in),
          checkOutTime: toIso(latest.time_out)
        }));
        const canCheckOut = hasOpen;
        let matchedShift = null;
        if (hasOpen && latest.jadwal_jaga_id && scheduleData.todaySchedule) {
          matchedShift = scheduleData.todaySchedule.find((s: any) => s.id === latest.jadwal_jaga_id);
        }
        setScheduleData(prev => ({
          ...prev,
          canCheckOut,
          currentShift: matchedShift || prev.currentShift
        }));
      } else {
        // If today payload shows open without explicit today_records, honor it
        if (todayPayload && todayPayload.time_in && !todayPayload.time_out) {
          const dateStr = todayPayload.date || localToday;
          const toIso = (t?: string | null) => (t ? new Date(`${dateStr}T${t}:00`).toISOString() : null);
          setIsCheckedIn(true);
          setAttendanceData(prev => ({
            ...prev,
            checkInTime: toIso(todayPayload.time_in),
            checkOutTime: toIso(todayPayload.time_out)
          }));
          setScheduleData(prev => ({ ...prev, canCheckOut: true }));
        } else {
          // No records for today → ensure not considered checked-in
          setIsCheckedIn(false);
          setScheduleData(prev => ({ ...prev, canCheckOut: false }));
        }
      }
    } catch (e) {
      // ignore
    }
  }, []);

  // Validate current status based on schedule and work location
  async function validateCurrentStatus(overrides?: {
    currentShift?: any;
    todaySchedule?: any[];
    workLocation?: any;
  }) {
    // Hoisted helper to avoid any temporal dead zone issues in minified builds
    function computeValidationMessage(
      duty: boolean,
      withinShift: boolean,
      hasWL: boolean,
      mayCheckOut?: boolean,
      checkedIn?: boolean
    ) {
      if (checkedIn) {
        if (mayCheckOut === false) {
          return '⏰ Waktu check-out sudah melewati batas (jam jaga + 30 menit)';
        }
        return '';
      }
      if (!duty) return 'Anda tidak memiliki jadwal jaga hari ini';
      if (!withinShift) return 'Saat ini bukan jam jaga Anda';
      if (!hasWL) return 'Work location belum ditugaskan';
      return '';
    }
    try {
      // Get server time for accurate validation
      let serverTime = null;
      try {
        const serverTimeResponse = await fetch('/api/v2/server-time', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          credentials: 'same-origin'
        });
        
        if (serverTimeResponse.ok) {
          const serverTimeData = await serverTimeResponse.json();
          serverTime = new Date(serverTimeData.data.current_time);
        }
      } catch (error) {
        console.warn('Failed to get server time, using client time:', error);
      }

      // Use server time if available, otherwise use client time
      const now = serverTime || new Date();
      const currentTime = now.toTimeString().slice(0, 8); // HH:MM:SS format
      const currentHour = now.getHours();
      const currentMinute = now.getMinutes();
      // Store server offset for live clock
      if (serverTime) {
        serverOffsetRef.current = serverTime.getTime() - Date.now();
      }

      // Prefer currentShift; if missing but there are schedules today, use earliest upcoming shift as fallback for windowing
      const sourceCurrentShift = overrides?.currentShift ?? scheduleData.currentShift;
      const sourceTodaySchedule = overrides?.todaySchedule ?? scheduleData.todaySchedule;
      const sourceWorkLocation = overrides?.workLocation ?? scheduleData.workLocation;

      let effectiveShift = sourceCurrentShift;
      if ((!effectiveShift || !effectiveShift.shift_template) && Array.isArray(sourceTodaySchedule) && sourceTodaySchedule.length > 0) {
        const sorted = [...sourceTodaySchedule].sort((a: any, b: any) => {
          const [ah, am] = (a?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
          const [bh, bm] = (b?.shift_template?.jam_masuk || '00:00').split(':').map(Number);
          return (ah * 60 + am) - (bh * 60 + bm);
        });
        effectiveShift = sorted[0];
      }

      // Check if doctor is on duty today: consider either shift_template or shift_info
      const isOnDutyToday = !!effectiveShift && (!!(effectiveShift as any).shift_template || !!(effectiveShift as any).shift_info);

      // Check if current time is within allowed check-in window (respect Work Location tolerances)
      let isWithinCheckinWindow = false;

      if (effectiveShift) {
        // Support multiple possible time sources to avoid undefined access
        const startTime: any = effectiveShift?.shift_template?.jam_masuk
          || effectiveShift?.shift_info?.jam_masuk
          || effectiveShift?.shift_info?.jam_masuk_format;
        const endTime: any = effectiveShift?.shift_template?.jam_pulang
          || effectiveShift?.shift_info?.jam_pulang
          || effectiveShift?.shift_info?.jam_pulang_format;

        if (
          typeof startTime === 'string' && typeof endTime === 'string' &&
          startTime.includes(':') && endTime.includes(':')
        ) {
          // Parse shift times and compute buffered window for UI hint
          const [startHour, startMinute] = startTime.split(':').map(Number);
          const [endHour, endMinute] = endTime.split(':').map(Number);
          const currentDate = now;
          const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), startHour || 0, startMinute || 0, 0);
          let endDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), endHour || 0, endMinute || 0, 0);
          if (endDate.getTime() < startDate.getTime()) {
            endDate = new Date(endDate.getTime() + 24 * 60 * 60 * 1000);
          }

          // Compute allowed check-in window from Work Location tolerances
          const wl: any = sourceWorkLocation || {};
          const earlyBeforeMin = Number.isFinite(Number(wl?.checkin_before_shift_minutes))
            ? Number(wl.checkin_before_shift_minutes)
            : 30;
          const lateTolMin = Number.isFinite(Number(wl?.late_tolerance_minutes))
            ? Number(wl.late_tolerance_minutes)
            : 15;
          const earliestCheckin = new Date(startDate.getTime() - earlyBeforeMin * 60 * 1000);
          const latestCheckin = new Date(startDate.getTime() + lateTolMin * 60 * 1000);
          // Allow check-in until shift end (not blocking after late tolerance)
          const allowedCheckinEnd = endDate;

          // Keep visualization buffer for hints (no gating)
          const durationMs = endDate.getTime() - startDate.getTime();
          const vizBufferMin = durationMs <= 30 * 60 * 1000 ? 60 : 30;
          const startBuf = new Date(startDate.getTime() - vizBufferMin * 60 * 1000);
          const endBuf = new Date(endDate.getTime() + vizBufferMin * 60 * 1000);
          shiftTimesRef.current = {
            startMs: startDate.getTime(),
            endMs: endDate.getTime(),
            startBufMs: startBuf.getTime(),
            endBufMs: endBuf.getTime(),
          };
          // Gate check-in based on allowed window (earliest to shift end)
          isWithinCheckinWindow = now.getTime() >= earliestCheckin.getTime() && now.getTime() <= allowedCheckinEnd.getTime();
        } else {
          // Missing or malformed time strings; avoid throwing and keep safe defaults
          shiftTimesRef.current = null;
          isWithinCheckinWindow = false;
        }
      }

      // Check if work location is assigned
      const hasWorkLocation = sourceWorkLocation && sourceWorkLocation.id;
      
      // Compute overdue info for styling only (do not block checkout)
      let isCheckoutOverdue = false;
      if (isCheckedIn) {
        const rawEnd = scheduleData.currentShift?.shift_template?.jam_pulang
          || (scheduleData.currentShift as any)?.shift_info?.jam_pulang
          || (scheduleData.currentShift as any)?.shift_info?.jam_pulang_format;
        if (typeof rawEnd === 'string' && rawEnd.includes(':')) {
          const [endHour, endMinute] = rawEnd.split(':').map(n => Number(n) || 0);
          const shiftEndTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endHour, endMinute, 0);
          const checkoutBuffer = 30 * 60 * 1000; // 30 minutes
          const maxCheckoutTime = new Date(shiftEndTime.getTime() + checkoutBuffer);
          isCheckoutOverdue = now > maxCheckoutTime;
        }
      }
      
      // Determine if can check in/out
      const canCheckIn = isOnDutyToday && isWithinCheckinWindow && !isCheckedIn;
      // Always allow checkout on client if UI considers user checked-in; server will enforce correctness
      const canCheckOut = isCheckedIn;
      
      // Ensure canCheckOut is true if checked in (redundant but explicit)
      if (isCheckedIn && !canCheckOut) {
        console.warn('⚠️ Inconsistent state: isCheckedIn is true but canCheckOut is false');
      }

      // Comprehensive debug logging (v2 with fix)
      console.log('🔍 Schedule Validation Debug [FIXED]:', {
        currentTime: now.toISOString(),
        currentTimeFormatted: currentTime,
        serverTimeUsed: !!serverTime,
        shiftStart: (sourceCurrentShift as any)?.shift_template?.jam_masuk,
        shiftEnd: (sourceCurrentShift as any)?.shift_template?.jam_pulang,
        isOnDutyToday,
        isWithinCheckinWindow,
        hasWorkLocation,
        canCheckIn,
        canCheckOut,
        todaySchedule: Array.isArray(sourceTodaySchedule) ? sourceTodaySchedule.length : 0,
        workLocation: sourceWorkLocation,
        isCheckedIn: isCheckedIn,
        currentShift: sourceCurrentShift,
        todayScheduleDetails: sourceTodaySchedule
      });

      setScheduleData(prev => ({
        ...prev,
        isOnDuty: isOnDutyToday && (isWithinCheckinWindow || isCheckedIn), // If already checked in, still on duty
        canCheckIn,
        canCheckOut,
        checkoutOverdue: isCheckoutOverdue,
        validationMessage: computeValidationMessage(
          isOnDutyToday,
          isWithinCheckinWindow,
          hasWorkLocation,
          canCheckOut,
          isCheckedIn
        )
      }));
    } catch (error) {
      console.error('Error in validateCurrentStatus:', error, {
        snapshot: {
          currentShift: scheduleData.currentShift,
          workLocation: scheduleData.workLocation,
          todayScheduleCount: scheduleData.todaySchedule?.length || 0,
          isCheckedIn
        }
      });
      // Fallback: preserve previous UI state to avoid flashing error on transient issues
      setScheduleData(prev => ({
        ...prev,
        // Keep previous gating flags; only update message if previously empty
        validationMessage: prev.validationMessage || 'Error validating schedule, please try again'
      }));
    }
  }

  // Get validation message
  const getValidationMessage = (isOnDutyToday: boolean, isWithinShiftHours: boolean, hasWorkLocation: boolean, canCheckOut?: boolean, isCheckedIn?: boolean) => {
    // If already checked in, don't show "no schedule" message
    if (isCheckedIn) {
      if (canCheckOut === false) {
        return '⏰ Waktu check-out sudah melewati batas (jam jaga + 30 menit)';
      }
      return ''; // Already checked in, ready to check out
    }
    
    if (!isOnDutyToday) {
      return 'Anda tidak memiliki jadwal jaga hari ini';
    }
    if (!isWithinShiftHours) {
      return 'Saat ini bukan jam jaga Anda';
    }
    if (!hasWorkLocation) {
      return 'Work location belum ditugaskan';
    }
    return '';
  };

  // Calculate distance between two points
  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  };

  const [showLeaveForm, setShowLeaveForm] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [isTablet, setIsTablet] = useState(false);
  const [isDesktop, setIsDesktop] = useState(false);
  
  // Check screen size on mount and resize
  useEffect(() => {
    const checkScreenSize = () => {
      const width = window.innerWidth;
      setIsMobile(width < 768);
      setIsTablet(width >= 768 && width < 1024);
      setIsDesktop(width >= 1024);
    };
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    return () => window.removeEventListener('resize', checkScreenSize);
  }, []);
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(5);
  const [filterPeriod, setFilterPeriod] = useState('weekly');
  
  // Leave form state
  const [leaveForm, setLeaveForm] = useState({
    type: 'annual',
    startDate: '',
    endDate: '',
    reason: '',
    days: 1
  });

  // Attendance History Data - Now using real data from API
  const [attendanceHistory, setAttendanceHistory] = useState<Array<{
    date: string;
    checkIn: string;
    checkOut: string;
    status: string;
    hours: string;
  }>>([]);
  
  // Loading state for history
  const [historyLoading, setHistoryLoading] = useState(false);
  const [historyError, setHistoryError] = useState<string | null>(null);

  // Monthly Statistics
  const [monthlyStats] = useState({
    totalDays: 22,
    presentDays: 20,
    lateDays: 2,
    absentDays: 0,
    overtimeHours: 15.5,
    leaveBalance: 12
  });

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
      
      // Calculate working hours with shift schedule constraints
      const calculateWorkingHours = () => {
        if (!attendanceData.checkInTime) return '00:00:00';
        
        // Get shift schedule times
        const shiftStart = scheduleData.currentShift?.shift_template?.jam_masuk;
        const shiftEnd = scheduleData.currentShift?.shift_template?.jam_pulang;
        
        if (!shiftStart || !shiftEnd) {
          // Fallback to simple calculation if no shift schedule
          const endTime = attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime) : new Date();
          const workingTime = endTime - new Date(attendanceData.checkInTime);
        const hours = Math.floor(workingTime / (1000 * 60 * 60));
        const minutes = Math.floor((workingTime % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((workingTime % (1000 * 60)) / 1000);
          return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Parse shift times
        const [startHour, startMinute] = shiftStart.split(':').map(Number);
        const [endHour, endMinute] = shiftEnd.split(':').map(Number);
        
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        
        // Create shift boundary times
        const shiftStartTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), startHour, startMinute, 0);
        let shiftEndTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), endHour, endMinute, 0);
        
        // Handle overnight shifts
        if (shiftEndTime <= shiftStartTime) {
          shiftEndTime = new Date(shiftEndTime.getTime() + 24 * 60 * 60 * 1000);
        }
        
        // Get actual check-in and check-out times
        const checkInTime = new Date(attendanceData.checkInTime);
        const checkOutTime = attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime) : new Date();
        
        // Apply constraints: working hours only count within shift schedule
        // Effective start = max(checkIn, shiftStart)
        const effectiveStart = checkInTime < shiftStartTime ? shiftStartTime : checkInTime;
        
        // Effective end = min(checkOut/now, shiftEnd)
        const effectiveEnd = checkOutTime > shiftEndTime ? shiftEndTime : checkOutTime;
        
        // Calculate working time only if effective period is positive
        let workingTime = 0;
        if (effectiveEnd > effectiveStart) {
          workingTime = effectiveEnd - effectiveStart;
        }
        
        const hours = Math.floor(workingTime / (1000 * 60 * 60));
        const minutes = Math.floor((workingTime % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((workingTime % (1000 * 60)) / 1000);
        
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
      };
      
      // Update working hours
      const newWorkingHours = calculateWorkingHours();
        setAttendanceData(prev => ({
          ...prev,
        workingHours: newWorkingHours
        }));
    }, 1000);

    return () => clearInterval(timer);
  }, [isCheckedIn, attendanceData.checkInTime, attendanceData.checkOutTime, scheduleData.currentShift]);

  const formatDate = (date: Date | string) => {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  // Filter attendance data based on period
  const getFilteredData = () => {
    const now = new Date();
    const filtered = attendanceHistory.filter(record => {
      const recordDate = new Date(record.date);
      
      if (filterPeriod === 'weekly') {
        const weekAgo = new Date(now);
        weekAgo.setDate(now.getDate() - 7);
        return recordDate >= weekAgo;
      } else if (filterPeriod === 'monthly') {
        const monthAgo = new Date(now);
        monthAgo.setMonth(now.getMonth() - 1);
        return recordDate >= monthAgo;
      }
      return true;
    });
    
    return filtered;
  };

  // Pagination logic
  const filteredData = getFilteredData();
  const totalPages = Math.ceil(filteredData.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentData = filteredData.slice(startIndex, endIndex);

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  const handleFilterChange = (period: string) => {
    setFilterPeriod(period);
    setCurrentPage(1);
    // Load history with new period
    loadAttendanceHistory(period);
  };
  
  // Function to load attendance history from API
  const loadAttendanceHistory = async (period: string = 'weekly') => {
    setHistoryLoading(true);
    setHistoryError(null);
    
    try {
      // Use UnifiedAuth for proper authentication
      const unifiedAuth = getUnifiedAuthInstance();
      const authHeaders = unifiedAuth.getAuthHeaders();
      
      // Calculate date range based on period
      const endDate = new Date();
      const startDate = new Date();
      
      if (period === 'weekly') {
        startDate.setDate(endDate.getDate() - 7);
      } else if (period === 'monthly') {
        startDate.setDate(endDate.getDate() - 30);
      }
      
      // Fetch attendance history from API
      const response = await fetch(`/api/v2/dashboards/dokter/presensi?start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}`, {
        method: 'GET',
        headers: {
          ...authHeaders,
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });
      
      if (!response.ok) {
        throw new Error(`Failed to fetch history: ${response.status}`);
      }
      
      const data = await response.json();
      
      // Transform API data to component format
      const history = data?.data?.history || [];
      
      const formattedHistory = history.map((record: any) => {
        // Format date
        const date = new Date(record.date || record.tanggal);
        const formattedDate = date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        });
        
        // Format check-in time
        const checkIn = record.time_in || record.check_in || record.jam_masuk;
        const formattedCheckIn = checkIn ? 
          (typeof checkIn === 'string' && checkIn.includes(':') ? 
            checkIn.substring(0, 5) : 
            new Date(checkIn).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
          ) : '-';
        
        // Format check-out time
        const checkOut = record.time_out || record.check_out || record.jam_pulang;
        const formattedCheckOut = checkOut ? 
          (typeof checkOut === 'string' && checkOut.includes(':') ? 
            checkOut.substring(0, 5) : 
            new Date(checkOut).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
          ) : '-';
        
        // Determine status
        let status = 'Present';
        if (record.status) {
          if (record.status === 'late' || record.status === 'Terlambat') {
            status = 'Late';
          } else if (record.status === 'on_time' || record.status === 'Tepat Waktu') {
            status = 'Present';
          } else if (record.status === 'absent' || record.status === 'Tidak Hadir') {
            status = 'Absent';
          } else if (record.status.toLowerCase().includes('leave') || record.status.toLowerCase().includes('cuti')) {
            status = 'Leave';
          } else {
            status = record.status;
          }
        }
        
        // Calculate duration
        let hours = '0h 0m';
        if (checkIn !== '-' && checkOut !== '-') {
          try {
            const start = new Date(`2000-01-01 ${formattedCheckIn}`);
            const end = new Date(`2000-01-01 ${formattedCheckOut}`);
            const diff = end.getTime() - start.getTime();
            
            if (diff > 0) {
              const totalMinutes = Math.floor(diff / (1000 * 60));
              const h = Math.floor(totalMinutes / 60);
              const m = totalMinutes % 60;
              hours = `${h}h ${m}m`;
            }
          } catch (e) {
            // If duration calculation fails, try using provided duration
            if (record.work_duration || record.durasi) {
              hours = record.work_duration || record.durasi;
            }
          }
        }
        
        return {
          date: formattedDate,
          checkIn: formattedCheckIn,
          checkOut: formattedCheckOut,
          status: status,
          hours: hours
        };
      });
      
      // Sort by date (most recent first)
      formattedHistory.sort((a: any, b: any) => {
        const dateA = new Date(a.date.split('/').reverse().join('-'));
        const dateB = new Date(b.date.split('/').reverse().join('-'));
        return dateB.getTime() - dateA.getTime();
      });
      
      setAttendanceHistory(formattedHistory);
      
    } catch (error) {
      console.error('Failed to load attendance history:', error);
      setHistoryError('Gagal memuat riwayat presensi. Silakan coba lagi.');
      
      // Fallback to empty array
      setAttendanceHistory([]);
    } finally {
      setHistoryLoading(false);
    }
  };

  const handleCheckIn = async () => {
    // Validate schedule and work location first
    if (!scheduleData.canCheckIn) {
      alert(`❌ Tidak dapat melakukan check-in: ${scheduleData.validationMessage}`);
      return;
    }

    try {
      // Use GPSManager with fallback strategies
      const gpsManager = (await import('@/utils/GPSManager')).default;
      
      // Configure GPSManager for better reliability
      gpsManager.updateConfig({
        enableLogging: true,
        maxRetries: 3,
        timeoutProgression: [5000, 3000, 2000],
        strategies: [
          GPSStrategy.HIGH_ACCURACY_GPS,
          GPSStrategy.NETWORK_BASED,
          GPSStrategy.CACHED_LOCATION,
          GPSStrategy.DEFAULT_FALLBACK
        ]
      });

      // Show loading indicator
      const loadingAlert = document.createElement('div');
      loadingAlert.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
      loadingAlert.textContent = '📍 Mendapatkan lokasi GPS...';
      document.body.appendChild(loadingAlert);

      let location: LocationResult;
      
      try {
        // Try to get location with GPSManager's intelligent fallback
        location = await gpsManager.getCurrentLocation(true);
        
        // Log the GPS strategy used for debugging
        console.log(`GPS Strategy used: ${location.source}, Confidence: ${location.confidence}`);
        
        // Remove loading indicator
        loadingAlert.remove();
        
        // Warn if using fallback location
        if (location.source === GPSStrategy.DEFAULT_FALLBACK) {
          const useDefault = confirm('⚠️ GPS tidak tersedia. Gunakan lokasi default rumah sakit?');
          if (!useDefault) {
          return;
        }
        } else if (location.source === GPSStrategy.CACHED_LOCATION) {
          console.log('📍 Using cached location (GPS temporarily unavailable)');
        }
      } catch (gpsError) {
        // Remove loading indicator
        loadingAlert.remove();
        
        // If all GPS strategies fail, offer manual input
        const useManual = confirm('❌ GPS tidak dapat diakses. Apakah Anda berada di lokasi kerja dan ingin melanjutkan check-in?');
        if (!useManual) {
          return;
        }
        
        // Use hospital default location as fallback
        location = {
          latitude: hospitalLocation.lat,
          longitude: hospitalLocation.lng,
          accuracy: 50,
          source: GPSStrategy.USER_MANUAL_INPUT,
          timestamp: Date.now(),
          cached: false,
          confidence: 0.5
        };
      }

      const { latitude, longitude, accuracy } = location;
      
      // Validate distance to work location
      const distance = calculateDistance(latitude, longitude, hospitalLocation.lat, hospitalLocation.lng);
      if (distance > hospitalLocation.radius) {
        alert(`❌ Anda terlalu jauh dari lokasi kerja. Jarak: ${Math.round(distance)}m (maksimal ${hospitalLocation.radius}m)`);
        return;
      }

      // Get authentication token
      let token = localStorage.getItem('auth_token');
      if (!token) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        token = csrfMeta?.getAttribute('content') || '';
      }

      if (!token) {
        alert('❌ Tidak dapat melakukan check-in: Token autentikasi tidak ditemukan');
        return;
      }

      // Call API for check-in with proper authentication
      const checkinUrl = new URL('/api/v2/dashboards/dokter/checkin', window.location.origin);
      const response = await fetch(checkinUrl.toString(), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          latitude: latitude,
          longitude: longitude,
          accuracy: accuracy,
          location_name: hospitalLocation.name,
          schedule_id: scheduleData.currentShift?.id,
          work_location_id: scheduleData.workLocation?.id
        })
      });

      if (!response.ok) {
        let errJson: any = null;
        try { errJson = await response.json(); } catch {}
        const msg = (errJson?.message || '').toString();
        // Treat both cases as an existing open attendance that needs checkout first
        const alreadyCheckedIn = /sudah\s*check-?in/i.test(msg);
        const hasUnclosedToday = /belum\s*check-?out/i.test(msg) || /masih ada presensi yang belum check-?out/i.test(msg);
        if (response.status === 422 && (alreadyCheckedIn || hasUnclosedToday)) {
          setIsCheckedIn(true);
          setScheduleData(prev => ({ ...prev, canCheckOut: true }));
          await loadTodayAttendance();
          alert('ℹ️ Anda sudah memiliki presensi terbuka hari ini. Silakan lakukan check-out terlebih dahulu.');
          return;
        }
        throw new Error(`HTTP ${response.status}${msg ? ' - ' + msg : ''}`);
      }

      const result = await response.json();

      if (result.success) {
        // Optimistically reflect check-in immediately for UI (card will show Live now)
        const nowIso = new Date().toISOString();
        setIsCheckedIn(true);
        setAttendanceData(prev => ({ ...prev, checkInTime: nowIso, checkOutTime: null }));
        setScheduleData(prev => ({ ...prev, canCheckOut: true }));
        // Then sync with backend to ensure accurate persisted times
        await loadTodayAttendance();
        
        // Show success message
        alert('✅ Check-in berhasil!');
      } else {
        alert(`❌ Check-in gagal: ${result.message || 'Unknown error'}`);
      }
    } catch (error) {
      console.error('Check-in error:', error);
      if (error instanceof Error) {
        alert(`❌ Check-in gagal: ${error.message}`);
      } else {
        alert('❌ Check-in gagal: Terjadi kesalahan yang tidak diketahui');
      }
    }
  };

  const handleCheckOut = async () => {
    // Allow checkout whenever there is an open attendance; do not hard-block by time (server will enforce rules)

    try {
      // Try get GPS with better error handling
      // Use GPSManager with fallback strategies for checkout
      const gpsManager = (await import('@/utils/GPSManager')).default;
      
      // Configure for checkout (more lenient than check-in)
      gpsManager.updateConfig({
        enableLogging: true,
        maxRetries: 2,
        timeoutProgression: [3000, 2000],
        strategies: [
          GPSStrategy.HIGH_ACCURACY_GPS,
          GPSStrategy.NETWORK_BASED,
          GPSStrategy.CACHED_LOCATION,
          GPSStrategy.DEFAULT_FALLBACK
        ]
      });

      let latitude: number | null = null;
      let longitude: number | null = null;
      let accuracy: number | null = null;
      
      try {
        // Try to get location with GPSManager (don't block checkout if GPS fails)
        const location = await gpsManager.getCurrentLocation(false);
        
        latitude = location.latitude;
        longitude = location.longitude;
        accuracy = location.accuracy;
        
        console.log(`✅ Checkout GPS obtained - Strategy: ${location.source}, Confidence: ${location.confidence}`);
        console.log('Location:', { latitude, longitude, accuracy });
      } catch (geoError) {
        console.warn('⚠️ GPS not available for checkout, proceeding without location:', geoError);
        // Continue without GPS data - checkout is allowed without GPS
      }

      let token = localStorage.getItem('auth_token');
      if (!token) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        token = csrfMeta?.getAttribute('content') || '';
      }
      if (!token) {
        alert('❌ Token autentikasi tidak ditemukan');
        return;
      }

      const checkoutUrl = new URL('/api/v2/dashboards/dokter/checkout', window.location.origin);
      const response = await fetch(checkoutUrl.toString(), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ latitude, longitude, accuracy })
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        if (payload?.code === 'ALREADY_CHECKED_OUT') {
          setScheduleData(prev => ({ ...prev, canCheckOut: false, canCheckIn: false }));
        setIsCheckedIn(false);
          alert('ℹ️ Anda sudah melakukan check-out hari ini.');
          return;
        }
        if (payload?.code === 'NOT_CHECKED_IN') {
          setScheduleData(prev => ({ ...prev, canCheckOut: false }));
          setIsCheckedIn(false);
          alert('❌ Belum check-in.');
          return;
        }
        alert(`❌ Check-out gagal: ${payload?.message || 'Unknown error'}`);
        return;
      }

      if (payload?.success) {
        setIsCheckedIn(false);
        setScheduleData(prev => ({ ...prev, canCheckOut: false, canCheckIn: false }));
        setAttendanceData(prev => ({ ...prev, checkOutTime: new Date().toISOString() }));
        alert('✅ Check-out berhasil!');
      } else {
        alert(`❌ Check-out gagal: ${payload?.message || 'Unknown error'}`);
      }
    } catch (e) {
      console.error('Check-out error:', e);
      alert('❌ Check-out gagal. Coba lagi.');
    }
  };

  const handleLeaveSubmit = () => {
    alert(`Pengajuan cuti ${leaveForm.type} telah dikirim untuk persetujuan`);
    setShowLeaveForm(false);
    setLeaveForm({
      type: 'annual',
      startDate: '',
      endDate: '',
      reason: '',
      days: 1
    });
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Present': return 'text-green-400';
      case 'Late': return 'text-yellow-400';
      case 'Absent': return 'text-red-400';
      case 'Sick Leave': return 'text-blue-400';
      case 'Annual Leave': return 'text-purple-400';
      default: return 'text-gray-400';
    }
  };

  const tabItems = [
    { id: 'checkin', icon: Clock, label: 'Check In' },
    { id: 'history', icon: History, label: 'History' },
    { id: 'stats', icon: TrendingUp, label: 'Stats' },
    { id: 'leave', icon: Calendar, label: 'Leave' }
  ];

  const renderTabContent = () => {
    switch (activeTab) {
      case 'checkin':
        return (
          <div className="space-y-4 sm:space-y-6 md:space-y-8">
            {/* Current Date and Time - Responsive Typography */}
            <div className="text-center">
              <div className="text-base sm:text-lg md:text-xl text-purple-200 mb-2">{formatDate(currentTime)}</div>
              <div className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white">{formatTime(currentTime)}</div>
            </div>

            {/* Attendance Status Card - Responsive Padding and Layout */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-white/20">
              <div className="text-center mb-4">
                <div className={`inline-flex items-center space-x-2 sm:space-x-3 px-4 sm:px-6 py-2 sm:py-3 rounded-xl sm:rounded-2xl transition-all duration-500 ${
                  isCheckedIn 
                    ? 'bg-gradient-to-r from-green-500/30 to-emerald-500/30 border border-green-400/50' 
                    : 'bg-gradient-to-r from-gray-500/30 to-purple-500/30 border border-purple-400/50'
                }`}>
                  <div className={`w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full ${isCheckedIn ? 'bg-green-400 animate-pulse' : 'bg-purple-400'}`}></div>
                  <span className="text-sm sm:text-base md:text-lg text-white font-semibold">
                    {isCheckedIn ? '🚀 Sedang Bekerja' : '😴 Belum Check-in'}
                  </span>
                </div>
              </div>

              {/* Working Hours Display - Responsive Grid */}
              {isCheckedIn && (
                <div className="grid grid-cols-3 gap-2 sm:gap-4 md:gap-6 mt-4 sm:mt-6">
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-green-400">{attendanceData.workingHours}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Jam Kerja</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-blue-400">{attendanceData.breakTime}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Istirahat</div>
                  </div>
                  <div className="text-center">
                    <div className="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-purple-400">{attendanceData.overtimeHours}</div>
                    <div className="text-xs sm:text-sm md:text-base text-gray-300">Overtime</div>
                  </div>
                </div>
              )}

              {/* Check-in/out times - Responsive Font Sizes */}
              {(attendanceData.checkInTime || attendanceData.checkOutTime) && (
                <div className="mt-4 p-3 sm:p-4 md:p-5 bg-black/20 rounded-xl sm:rounded-2xl">
                  <div className="grid grid-cols-2 gap-2 sm:gap-4 text-xs sm:text-sm md:text-base">
                    <div>
                      <span className="text-gray-400">Check-in: </span>
                      <span className="text-green-400">
                        {attendanceData.checkInTime ? new Date(attendanceData.checkInTime).toLocaleTimeString('id-ID') : '-'}
                      </span>
                    </div>
                    <div>
                      <span className="text-gray-400">Check-out: </span>
                      <span className="text-red-400">
                        {attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime).toLocaleTimeString('id-ID') : '-'}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Schedule and Work Location Status */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl border border-white/20 p-4 mb-4">
              <div className="flex items-center justify-between mb-3">
                <h4 className="text-lg font-semibold text-white flex items-center space-x-2">
                  <Calendar className="w-5 h-5 text-blue-400" />
                  <span>Status Jadwal & Lokasi</span>
                </h4>
                <div className="flex items-center space-x-2">
                  {/* Refresh Button */}
                  <button
                    onClick={() => loadScheduleAndWorkLocation(true)}
                    className="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
                    title="Refresh jadwal"
                  >
                    <svg className="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </button>
                <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                  scheduleData.isOnDuty 
                    ? 'bg-green-500/20 text-green-300 border border-green-400/30' 
                    : 'bg-red-500/20 text-red-300 border border-red-400/30'
                }`}>
                  {scheduleData.isOnDuty ? '🟢 Siap Jaga' : '🔴 Tidak Jaga'}
                  </div>
                </div>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Schedule Status */}
                <div className="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl p-3 border border-blue-400/30">
                  <div className="flex items-center space-x-2 mb-2">
                    <Clock className="w-4 h-4 text-blue-400" />
                    <span className="text-sm font-medium text-blue-300">Jadwal Jaga</span>
                  </div>
                                     {scheduleData.currentShift ? (
                     <div className="text-white text-sm">
                       <div>🕐 {scheduleData.currentShift.shift_template?.jam_masuk || '08:00'} - {scheduleData.currentShift.shift_template?.jam_pulang || '16:00'}</div>
                       <div>👨‍⚕️ {scheduleData.currentShift.peran || 'Dokter'}</div>
                       <div>⭐ {scheduleData.currentShift.shift_template?.nama_shift || 'Shift'}</div>
                       {clockNow ? (
                         <div>🕒 Sekarang: {clockNow}</div>
                       ) : null}
                       {shiftTimeHint ? (
                         <div>ℹ️ {shiftTimeHint}</div>
                       ) : null}
                       {scheduleData.workLocation?.name ? (
                         <div>📍 {scheduleData.workLocation.name}</div>
                       ) : null}
                     </div>
                   ) : (
                     <div className="text-red-300 text-sm">❌ Tidak ada jadwal jaga hari ini</div>
                   )}
                </div>

                {/* Work Location Status */}
                <div className="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-xl p-3 border border-green-400/30">
                  <div className="flex items-center space-x-2 mb-2">
                    <MapPin className="w-4 h-4 text-green-400" />
                    <span className="text-sm font-medium text-green-300">Work Location</span>
                  </div>
                  {scheduleData.workLocation ? (
                    <div className="text-white text-sm">
                      <div>🏥 {scheduleData.workLocation.name}</div>
                      <div>📍 {scheduleData.workLocation.address}</div>
                    </div>
                  ) : (
                    <div className="text-red-300 text-sm">❌ Work location belum ditugaskan</div>
                  )}
                </div>
              </div>

              {/* Validation Message */}
              {scheduleData.validationMessage && (
                <div className="mt-3 p-3 bg-red-500/20 border border-red-400/30 rounded-xl">
                  <div className="flex items-center space-x-2">
                    <AlertTriangle className="w-4 h-4 text-red-400" />
                    <span className="text-red-300 text-sm">{scheduleData.validationMessage}</span>
                  </div>
                </div>
              )}
            </div>

            {/* Check-in/out Buttons - Responsive Grid and Sizing */}
            <div className="grid grid-cols-2 gap-3 sm:gap-4 md:gap-6 lg:gap-8">
              <button 
                onClick={handleCheckIn}
                disabled={isCheckedIn || !scheduleData.canCheckIn}
                className={`relative group p-4 sm:p-5 md:p-6 lg:p-8 rounded-2xl sm:rounded-3xl transition-all duration-500 transform ${
                  isCheckedIn || !scheduleData.canCheckIn
                    ? 'opacity-50 cursor-not-allowed' 
                    : 'hover:scale-105 active:scale-95'
                }`}
              >
                <div className="absolute inset-0 bg-gradient-to-br from-green-500/30 to-emerald-600/30 rounded-2xl sm:rounded-3xl"></div>
                <div className="absolute inset-0 bg-white/5 rounded-2xl sm:rounded-3xl border border-green-400/30"></div>
                {!isCheckedIn && (
                  <div className="absolute inset-0 bg-gradient-to-br from-green-400/0 to-green-400/20 rounded-2xl sm:rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-20 lg:h-20 mx-auto mb-2 sm:mb-3 md:mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl sm:rounded-2xl flex items-center justify-center">
                    <Sun className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-white" />
                  </div>
                  <div className="text-white font-bold text-sm sm:text-base md:text-lg lg:text-xl">Check In</div>
                  <div className="text-green-300 text-xs sm:text-sm md:text-base">Mulai bekerja</div>
                </div>
              </button>
              
              <button 
                onClick={handleCheckOut}
                disabled={!isCheckedIn || !scheduleData.canCheckOut}
                className={`relative group p-4 sm:p-5 md:p-6 lg:p-8 rounded-2xl sm:rounded-3xl transition-all duration-500 transform ${
                  !isCheckedIn || !scheduleData.canCheckOut
                    ? 'opacity-50 cursor-not-allowed' 
                    : 'hover:scale-105 active:scale-95'
                }`}
              >
                  <div className={`absolute inset-0 rounded-3xl bg-gradient-to-br from-purple-500/30 to-pink-600/30`}></div>
                  <div className={`absolute inset-0 bg-white/5 rounded-3xl border border-purple-400/30`}></div>
                {isCheckedIn && scheduleData.canCheckOut && (
                  <div className="absolute inset-0 bg-gradient-to-br from-purple-400/0 to-purple-400/20 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className={`w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-20 lg:h-20 mx-auto mb-2 sm:mb-3 md:mb-4 rounded-xl sm:rounded-2xl flex items-center justify-center ${
                    isCheckedIn && !scheduleData.canCheckOut
                      ? 'bg-gradient-to-br from-red-400 to-orange-500'
                      : 'bg-gradient-to-br from-purple-400 to-pink-500'
                  }`}>
                    <Moon className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-white" />
                  </div>
                  <div className="text-white font-bold text-sm sm:text-base md:text-lg lg:text-xl">Check Out</div>
                  <div className={`text-xs sm:text-sm md:text-base text-purple-300`}>Selesai bekerja</div>
                </div>
              </button>
            </div>

            {/* Dynamic Location Map with Leaflet.js & OSM */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl border border-white/20 overflow-hidden">
              <div className="p-4 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center">
                      <MapPin className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="font-semibold text-white">{hospitalLocation.name}</div>
                      <div className="text-green-300 text-sm">{hospitalLocation.address}</div>
                      {distanceToHospital && (
                        <div className="text-xs text-cyan-300">
                          📏 {distanceToHospital < 1000 ? `${Math.round(distanceToHospital)}m` : `${(distanceToHospital / 1000).toFixed(1)}km`} dari Anda
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center justify-between w-full">
                    <div className="flex items-center space-x-2">
                      <div className={`w-2 h-2 rounded-full ${
                        gpsStatus === GPSStatus.SUCCESS ? 'bg-green-400 animate-pulse' :
                        gpsStatus === GPSStatus.ERROR ? 'bg-red-400' :
                        gpsStatus === GPSStatus.REQUESTING ? 'bg-yellow-400 animate-pulse' :
                        gpsStatus === GPSStatus.FALLBACK ? 'bg-orange-400' :
                        gpsStatus === GPSStatus.PERMISSION_REQUIRED ? 'bg-purple-400' :
                        'bg-gray-400'
                      }`}></div>
                      <div className="flex flex-col">
                        <span className="text-xs text-gray-300">
                          {gpsStatus === GPSStatus.SUCCESS ? `GPS Aktif (${gpsSource})` :
                           gpsStatus === GPSStatus.ERROR ? 'GPS Error' :
                           gpsStatus === GPSStatus.REQUESTING ? 'Mendeteksi...' :
                           gpsStatus === GPSStatus.FALLBACK ? 'GPS Fallback' :
                           gpsStatus === GPSStatus.PERMISSION_REQUIRED ? 'Izin Diperlukan' :
                           'GPS Off'}
                        </span>
                        {gpsAccuracy && (
                          <span className="text-xs text-gray-400">
                            ±{gpsAccuracy.toFixed(0)}m • {(gpsConfidence * 100).toFixed(0)}% confidence
                          </span>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center space-x-1">
                      {gpsStatus === GPSStatus.PERMISSION_REQUIRED && (
                        <button
                          onClick={handleRequestPermission}
                          className="p-1 bg-purple-500/20 hover:bg-purple-500/30 rounded-lg transition-colors"
                          title="Request GPS Permission"
                        >
                          <Navigation className="w-3 h-3 text-purple-400" />
                        </button>
                      )}
                      <button
                        onClick={handleRefreshGPS}
                        className="p-1 bg-blue-500/20 hover:bg-blue-500/30 rounded-lg transition-colors"
                        title="Refresh GPS"
                      >
                        <RefreshCw className={`w-3 h-3 text-blue-400 ${gpsLoading ? 'animate-spin' : ''}`} />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="h-64 sm:h-80">
                <DynamicMap
                  hospitalLocation={hospitalLocation}
                  userLocation={userLocation}
                  onLocationUpdate={(location) => {
                    // Only update if we have valid coordinates (not loading state)
                    if (location.lat !== 0 && location.lng !== 0) {
                      setUserLocation(location);
                      
                      // Calculate distance
                      const R = 6371e3; // Earth's radius in meters
                      const φ1 = location.lat * Math.PI / 180;
                      const φ2 = hospitalLocation.lat * Math.PI / 180;
                      const Δφ = (hospitalLocation.lat - location.lat) * Math.PI / 180;
                      const Δλ = (hospitalLocation.lng - location.lng) * Math.PI / 180;

                      const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                                Math.cos(φ1) * Math.cos(φ2) *
                                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                      const distance = R * c;
                      setDistanceToHospital(distance);
                    } else {
                      // Loading state
                    }
                  }}
                  showUserLocation={true}
                  className="h-full w-full"
                />
              </div>
            </div>

            {/* Today's Work Summary Card */}
            <div className="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-lg font-semibold text-white flex items-center space-x-2">
                  <Clock className="w-5 h-5 text-blue-400" />
                  <span>Jam Kerja Hari Ini</span>
                </h4>
                <div className="flex items-center space-x-1">
                  <div className={`w-2 h-2 rounded-full ${attendanceData.checkOutTime ? 'bg-gray-400' : 'bg-green-400 animate-pulse'}`}></div>
                  <span className="text-xs text-green-300">{attendanceData.checkOutTime ? 'Selesai' : 'Live'}</span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-4">
                {/* Check In Time */}
                <div className="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-2xl p-4 border border-green-400/30">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center">
                      <Sun className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="text-xl font-bold text-green-400">
                        {displayCheckInDate ? displayCheckInDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
                      </div>
                      <div className="text-xs text-green-300">Check In</div>
                    </div>
                  </div>
                </div>

                {/* Check Out Time */}
                <div className="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-2xl p-4 border border-purple-400/30">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
                      <Moon className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="text-xl font-bold text-purple-400">
                        {displayCheckOutDate ? displayCheckOutDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
                      </div>
                      <div className="text-xs text-purple-300">Check Out</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Progress Bar */}
              <div className="mb-4">
                <div className="flex justify-between text-sm mb-2">
                  <span className="text-gray-300">Progress Hari Ini</span>
                  <span className="text-cyan-400">{computeProgressPercent().toFixed(1)}%</span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-3">
                  <div 
                    className="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 h-3 rounded-full transition-all duration-500 relative overflow-hidden"
                    style={{ width: `${computeProgressPercent().toFixed(1)}%` }}
                  >
                    {!attendanceData.checkOutTime && (
                    <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
                    )}
                  </div>
                </div>
              </div>

              {/* Shortage/Overtime Indicator */}
              <div className="grid grid-cols-2 gap-3">
                <div className={`p-3 rounded-xl border ${
                  (() => {
                    // Calculate based on shift schedule, not check-in time
                    const shiftStart = scheduleData.currentShift?.shift_template?.jam_masuk;
                    const shiftEnd = scheduleData.currentShift?.shift_template?.jam_pulang;
                    
                    if (!shiftStart || !shiftEnd) return 'bg-red-500/10 border-red-400/30';
                    
                    // Parse shift times
                    const [startHour, startMinute] = shiftStart.split(':').map(Number);
                    const [endHour, endMinute] = shiftEnd.split(':').map(Number);
                    
                    const now = new Date();
                    const shiftStartTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startHour, startMinute, 0);
                    let shiftEndTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endHour, endMinute, 0);
                    
                    // Handle overnight shifts
                    if (shiftEndTime < shiftStartTime) {
                      shiftEndTime = new Date(shiftEndTime.getTime() + 24 * 60 * 60 * 1000);
                    }
                    
                    // Calculate total shift duration
                    const totalShiftMs = shiftEndTime.getTime() - shiftStartTime.getTime();
                    const totalShiftHours = totalShiftMs / (1000 * 60 * 60);
                    
                    // Use checkout time if available, otherwise use current time
                    const currentTime = attendanceData.checkOutTime 
                      ? new Date(attendanceData.checkOutTime) 
                      : new Date();
                    
                    // Calculate elapsed time from shift start
                    let elapsedMs = 0;
                    if (currentTime >= shiftStartTime) {
                      const effectiveEndTime = currentTime < shiftEndTime ? currentTime : shiftEndTime;
                      elapsedMs = effectiveEndTime.getTime() - shiftStartTime.getTime();
                    }
                    
                    const elapsedHours = elapsedMs / (1000 * 60 * 60);
                    const shortage = Math.max(totalShiftHours - elapsedHours, 0);
                    
                    return shortage > 0 ? 'bg-red-500/10 border-red-400/30' : 'bg-green-500/10 border-green-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className={`text-lg font-bold ${
                      (() => {
                        // Calculate shortage based on shift schedule, not check-in time
                        const shiftStart = scheduleData.currentShift?.shift_template?.jam_masuk;
                        const shiftEnd = scheduleData.currentShift?.shift_template?.jam_pulang;
                        
                        if (!shiftStart || !shiftEnd) return 'text-red-400';
                        
                        // Parse shift times
                        const [startHour, startMinute] = shiftStart.split(':').map(Number);
                        const [endHour, endMinute] = shiftEnd.split(':').map(Number);
                        
                        const now = new Date();
                        const shiftStartTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startHour, startMinute, 0);
                        let shiftEndTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endHour, endMinute, 0);
                        
                        // Handle overnight shifts
                        if (shiftEndTime < shiftStartTime) {
                          shiftEndTime = new Date(shiftEndTime.getTime() + 24 * 60 * 60 * 1000);
                        }
                        
                        // Calculate total shift duration in hours
                        const totalShiftMs = shiftEndTime.getTime() - shiftStartTime.getTime();
                        const totalShiftHours = totalShiftMs / (1000 * 60 * 60);
                        
                        // Use checkout time if available, otherwise use current time
                        const currentTime = attendanceData.checkOutTime 
                          ? new Date(attendanceData.checkOutTime) 
                          : new Date();
                        
                        // Calculate elapsed time from shift start
                        let elapsedMs = 0;
                        if (currentTime >= shiftStartTime) {
                          // If we're past shift start, calculate elapsed time
                          const effectiveEndTime = currentTime < shiftEndTime ? currentTime : shiftEndTime;
                          elapsedMs = effectiveEndTime.getTime() - shiftStartTime.getTime();
                        }
                        
                        const elapsedHours = elapsedMs / (1000 * 60 * 60);
                        const shortage = Math.max(totalShiftHours - elapsedHours, 0);
                        
                        return shortage > 0 ? 'text-red-400' : 'text-green-400';
                      })()
                    }`}>
                      {(() => {
                        // Calculate shortage based on shift schedule, not check-in time
                        const shiftStart = scheduleData.currentShift?.shift_template?.jam_masuk;
                        const shiftEnd = scheduleData.currentShift?.shift_template?.jam_pulang;
                        
                        if (!shiftStart || !shiftEnd) return '8:00:00';
                        
                        // Parse shift times
                        const [startHour, startMinute] = shiftStart.split(':').map(Number);
                        const [endHour, endMinute] = shiftEnd.split(':').map(Number);
                        
                        const now = new Date();
                        const shiftStartTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startHour, startMinute, 0);
                        let shiftEndTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endHour, endMinute, 0);
                        
                        // Handle overnight shifts
                        if (shiftEndTime < shiftStartTime) {
                          shiftEndTime = new Date(shiftEndTime.getTime() + 24 * 60 * 60 * 1000);
                        }
                        
                        // Calculate total shift duration in hours
                        const totalShiftMs = shiftEndTime.getTime() - shiftStartTime.getTime();
                        const totalShiftHours = totalShiftMs / (1000 * 60 * 60);
                        
                        // Use checkout time if available, otherwise use current time
                        const currentTime = attendanceData.checkOutTime 
                          ? new Date(attendanceData.checkOutTime) 
                          : new Date();
                        
                        // Calculate elapsed time from shift start (not check-in)
                        let elapsedMs = 0;
                        if (currentTime >= shiftStartTime) {
                          // If we're past shift start, calculate elapsed time
                          const effectiveEndTime = currentTime < shiftEndTime ? currentTime : shiftEndTime;
                          elapsedMs = effectiveEndTime.getTime() - shiftStartTime.getTime();
                        }
                        
                        const elapsedHours = elapsedMs / (1000 * 60 * 60);
                        const shortage = Math.max(totalShiftHours - elapsedHours, 0);
                        
                        const shortageHours = Math.floor(shortage);
                        const shortageMinutes = Math.floor((shortage % 1) * 60);
                        const shortageSeconds = Math.floor(((shortage % 1) * 60 % 1) * 60);
                        
                        return shortage > 0 ? 
                          `${shortageHours.toString().padStart(2, '0')}:${shortageMinutes.toString().padStart(2, '0')}:${shortageSeconds.toString().padStart(2, '0')}` :
                          '0:00:00';
                      })()}
                    </div>
                    <div className="text-xs text-gray-300 flex items-center justify-center space-x-1">
                      <AlertTriangle className="w-3 h-3" />
                      <span>Kekurangan</span>
                    </div>
                    {(() => {
                      const shiftStart = scheduleData.currentShift?.shift_template?.jam_masuk;
                      if (!shiftStart) return null;
                      
                      const [startHour, startMinute] = shiftStart.split(':').map(Number);
                      const now = new Date();
                      const shiftStartTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startHour, startMinute, 0);
                      
                      if (now >= shiftStartTime && !attendanceData.checkOutTime) {
                        return (
                          <div className="text-xs text-yellow-400 mt-1 flex items-center justify-center">
                            <div className="w-2 h-2 bg-yellow-400 rounded-full animate-pulse mr-1"></div>
                            <span>Shift dimulai {shiftStart}</span>
                          </div>
                        );
                      }
                      return null;
                    })()}
                  </div>
                </div>

                <div className={`p-3 rounded-xl border ${
                  (() => {
                    if (!attendanceData.checkInTime) return 'bg-blue-500/10 border-blue-400/30';
                    
                    // Get target hours from shift schedule
                    const targetHours = scheduleData?.currentShift?.shift_template?.durasi_jam || 
                                      (() => {
                                        const jamMasuk = scheduleData?.currentShift?.shift_template?.jam_masuk;
                                        const jamPulang = scheduleData?.currentShift?.shift_template?.jam_pulang;
                                        if (jamMasuk && jamPulang) {
                                          const [startHour, startMin] = jamMasuk.split(':').map(Number);
                                          const [endHour, endMin] = jamPulang.split(':').map(Number);
                                          let duration = (endHour + endMin/60) - (startHour + startMin/60);
                                          if (duration < 0) duration += 24;
                                          return duration;
                                        }
                                        return 8; // Default fallback
                                      })();
                    
                    // Use checkout time if available, otherwise use current time
                    const endTime = attendanceData.checkOutTime 
                      ? new Date(attendanceData.checkOutTime) 
                      : new Date();
                    
                    const workingTime = endTime - new Date(attendanceData.checkInTime);
                    const hours = workingTime / (1000 * 60 * 60);
                    return hours > targetHours ? 'bg-green-500/10 border-green-400/30' : 'bg-blue-500/10 border-blue-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className="text-lg font-bold text-blue-400">
                      {attendanceData.checkInTime ? attendanceData.workingHours : '0:00:00'}
                    </div>
                    <div className="text-xs text-gray-300 flex items-center justify-center space-x-1">
                      <Clock className="w-3 h-3" />
                      <span>Jam Kerja</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Quick Tips */}
              <div className="mt-4 p-3 bg-gradient-to-r from-cyan-500/10 via-blue-500/10 to-purple-500/10 rounded-xl border border-cyan-400/20">
                <div className="flex items-start space-x-2">
                  <div className="w-4 h-4 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-xs text-white font-bold">💡</span>
                  </div>
                  <div className="text-xs text-cyan-200">
                    {(() => {
                      if (!attendanceData.checkInTime) return 'Jangan lupa check-in untuk memulai perhitungan jam kerja!';
                      
                      // If checked out, show completion message
                      if (attendanceData.checkOutTime) {
                        const workingTime = new Date(attendanceData.checkOutTime) - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        // Get target hours from shift schedule if available
                        const targetHours = scheduleData?.currentShift?.shift_template?.durasi_jam || 
                                          (() => {
                                            // Calculate from shift times if durasi_jam not available
                                            const jamMasuk = scheduleData?.currentShift?.shift_template?.jam_masuk;
                                            const jamPulang = scheduleData?.currentShift?.shift_template?.jam_pulang;
                                            if (jamMasuk && jamPulang) {
                                              const [startHour, startMin] = jamMasuk.split(':').map(Number);
                                              const [endHour, endMin] = jamPulang.split(':').map(Number);
                                              const startMinutes = startHour * 60 + startMin;
                                              const endMinutes = endHour * 60 + endMin;
                                              let duration = endMinutes - startMinutes;
                                              if (duration < 0) duration += 24 * 60; // Handle overnight shifts
                                              return duration / 60;
                                            }
                                            return 8; // Only use 8 as last resort if no schedule data
                                          })();
                        
                        if (hours >= targetHours) {
                          return `✅ Selesai! Anda telah bekerja ${hours.toFixed(1)} jam hari ini. Istirahat yang cukup!`;
                        } else {
                          return `✅ Selesai! Total ${hours.toFixed(1)} jam dari target ${targetHours} jam. Terima kasih atas kerja hari ini!`;
                        }
                      }
                      
                      // If still working (not checked out)
                      const workingTime = new Date() - new Date(attendanceData.checkInTime);
                      const hours = workingTime / (1000 * 60 * 60);
                      
                      // Get target hours from shift schedule if available
                      const targetHours = scheduleData?.currentShift?.shift_template?.durasi_jam || 
                                        (() => {
                                          // Calculate from shift times if durasi_jam not available
                                          const jamMasuk = scheduleData?.currentShift?.shift_template?.jam_masuk;
                                          const jamPulang = scheduleData?.currentShift?.shift_template?.jam_pulang;
                                          if (jamMasuk && jamPulang) {
                                            const [startHour, startMin] = jamMasuk.split(':').map(Number);
                                            const [endHour, endMin] = jamPulang.split(':').map(Number);
                                            const startMinutes = startHour * 60 + startMin;
                                            const endMinutes = endHour * 60 + endMin;
                                            let duration = endMinutes - startMinutes;
                                            if (duration < 0) duration += 24 * 60; // Handle overnight shifts
                                            return duration / 60;
                                          }
                                          return 8; // Only use 8 as last resort if no schedule data
                                        })();
                      if (hours < targetHours * 0.5) return `Semangat! Hari masih panjang untuk mencapai target ${targetHours} jam.`;
                      if (hours < targetHours * 0.75) return 'Kerja bagus! Sudah setengah perjalanan menuju target harian.';
                      if (hours < targetHours) return 'Hampir sampai target! Pertahankan semangat kerja Anda.';
                      if (hours < 9) return 'Target tercapai! Jam kerja tambahan akan dihitung sebagai overtime.';
                      return 'Luar biasa! Anda sudah bekerja melebihi jam standar hari ini.';
                    })()}
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'history':
        return (
          <div className="space-y-4">
            {/* Header with Filter - Responsive Layout */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
              <h3 className="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Riwayat Presensi
              </h3>
              <div className="flex items-center space-x-2">
                <Filter className="w-3 h-3 sm:w-4 sm:h-4 text-purple-300" />
                <select 
                  value={filterPeriod}
                  onChange={(e) => {
                    handleFilterChange(e.target.value);
                    loadAttendanceHistory(e.target.value);
                  }}
                  className="bg-white/10 backdrop-blur-xl border border-white/20 rounded-lg sm:rounded-xl px-2 sm:px-3 py-1 text-xs sm:text-sm text-white focus:outline-none focus:border-purple-400"
                  disabled={historyLoading}
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            {/* Loading State */}
            {historyLoading && (
              <div className="flex flex-col items-center justify-center py-12">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-400 mb-4"></div>
                <p className="text-gray-300 text-sm">Memuat riwayat presensi...</p>
              </div>
            )}

            {/* Error State */}
            {!historyLoading && historyError && (
              <div className="bg-red-500/10 backdrop-blur-xl rounded-xl p-4 border border-red-500/20">
                <div className="flex items-center space-x-3">
                  <AlertCircle className="w-5 h-5 text-red-400 flex-shrink-0" />
                  <div>
                    <p className="text-red-400 font-medium">Gagal memuat riwayat presensi</p>
                    <p className="text-gray-300 text-sm mt-1">{historyError}</p>
                    <button
                      onClick={() => loadAttendanceHistory(filterPeriod)}
                      className="mt-2 text-sm text-purple-400 hover:text-purple-300 underline"
                    >
                      Coba lagi
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Empty State */}
            {!historyLoading && !historyError && attendanceHistory.length === 0 && (
              <div className="text-center py-12">
                <Calendar className="w-16 h-16 text-gray-500 mx-auto mb-4" />
                <p className="text-gray-300 text-lg font-medium mb-2">Belum ada riwayat presensi</p>
                <p className="text-gray-400 text-sm">Riwayat presensi Anda akan muncul di sini setelah Anda melakukan check-in.</p>
              </div>
            )}

            {/* History Cards - Only show when data exists and not loading */}
            {!historyLoading && !historyError && currentData.length > 0 && currentData.map((record, index) => (
              <div key={index} className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="flex justify-between items-center mb-2">
                  <span className="text-sm sm:text-base md:text-lg text-white font-semibold">{record.date}</span>
                  <span className={`text-xs sm:text-sm font-medium ${getStatusColor(record.status)}`}>
                    {record.status}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-1 sm:gap-2 text-xs sm:text-sm md:text-base">
                  <div>
                    <span className="text-gray-400">Masuk: </span>
                    <span className="text-white">{record.checkIn || '-'}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Keluar: </span>
                    <span className="text-white">{record.checkOut || '-'}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Durasi: </span>
                    <span className="text-white">{record.hours || '-'}</span>
                  </div>
                </div>
              </div>
            ))}

            {/* Pagination - Only show when there are multiple pages and data exists */}
            {!historyLoading && !historyError && totalPages > 1 && (
              <div className="flex items-center justify-center space-x-2 mt-6">
                <button
                  onClick={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  className={`p-2 rounded-xl transition-all ${
                    currentPage === 1 
                      ? 'bg-gray-500/20 text-gray-500 cursor-not-allowed' 
                      : 'bg-white/10 text-white hover:bg-white/20'
                  }`}
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>

                {[...Array(totalPages)].map((_, index) => {
                  const page = index + 1;
                  return (
                    <button
                      key={page}
                      onClick={() => handlePageChange(page)}
                      className={`w-8 h-8 rounded-xl transition-all text-sm font-medium ${
                        currentPage === page
                          ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white'
                          : 'bg-white/10 text-gray-300 hover:bg-white/20'
                      }`}
                    >
                      {page}
                    </button>
                  );
                })}

                <button
                  onClick={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className={`p-2 rounded-xl transition-all ${
                    currentPage === totalPages 
                      ? 'bg-gray-500/20 text-gray-500 cursor-not-allowed' 
                      : 'bg-white/10 text-white hover:bg-white/20'
                  }`}
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            )}

            {!historyLoading && !historyError && attendanceHistory.length > 0 && (
              <div className="text-center text-sm text-gray-300 mt-4">
                Menampilkan {startIndex + 1}-{Math.min(endIndex, filteredData.length)} dari {filteredData.length} data
              </div>
            )}
          </div>
        );

      case 'stats':
        return (
          <div className="space-y-4 sm:space-y-6">
            <h3 className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 text-center bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
              Statistik Bulanan
            </h3>
            
            {/* Stats Grid - Responsive Columns */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-green-400">{monthlyStats.presentDays}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Hari Hadir</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-yellow-400">{monthlyStats.lateDays}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Hari Terlambat</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-blue-400">{monthlyStats.overtimeHours}h</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Overtime</div>
                </div>
              </div>
              <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 border border-white/20">
                <div className="text-center">
                  <div className="text-xl sm:text-2xl md:text-3xl font-bold text-purple-400">{monthlyStats.leaveBalance}</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Sisa Cuti</div>
                </div>
              </div>
            </div>

            {/* Attendance Rate - Responsive Padding */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Tingkat Kehadiran</h4>
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-300">Kehadiran</span>
                  <span className="text-green-400">{((monthlyStats.presentDays / monthlyStats.totalDays) * 100).toFixed(1)}%</span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-2">
                  <div 
                    className="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full"
                    style={{ width: `${(monthlyStats.presentDays / monthlyStats.totalDays) * 100}%` }}
                  ></div>
                </div>
              </div>
            </div>

            {/* Achievement Rings */}
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
              <h4 className="text-lg font-semibold text-white mb-6 text-center">Achievement Rings</h4>
              
              <div className="flex justify-center space-x-8">
                {/* Days Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient1)"
                      strokeWidth="2"
                      strokeDasharray="85, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#10B981" />
                        <stop offset="100%" stopColor="#34D399" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">28</div>
                      <div className="text-xs text-green-300">Days</div>
                    </div>
                  </div>
                </div>

                {/* Hours Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient2)"
                      strokeWidth="2"
                      strokeDasharray="72, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient2" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#3B82F6" />
                        <stop offset="100%" stopColor="#60A5FA" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">7.2</div>
                      <div className="text-xs text-blue-300">Hours</div>
                    </div>
                  </div>
                </div>

                {/* Performance Ring */}
                <div className="relative">
                  <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="rgba(255,255,255,0.1)"
                      strokeWidth="2"
                    />
                    <path
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="url(#gradient3)"
                      strokeWidth="2"
                      strokeDasharray="96, 100"
                      className="animate-pulse"
                    />
                    <defs>
                      <linearGradient id="gradient3" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#8B5CF6" />
                        <stop offset="100%" stopColor="#A78BFA" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-xl font-bold text-white">96%</div>
                      <div className="text-xs text-purple-300">Score</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'leave':
        return (
          <div className="space-y-4 sm:space-y-6">
            {/* Header with Add Button - Responsive Layout */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
              <h3 className="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Manajemen Cuti
              </h3>
              <button
                onClick={() => setShowLeaveForm(true)}
                className="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg sm:rounded-xl flex items-center space-x-1.5 sm:space-x-2 transition-all"
              >
                <Plus className="w-3 h-3 sm:w-4 sm:h-4" />
                <span className="text-xs sm:text-sm font-medium">Ajukan Cuti</span>
              </button>
            </div>

            {/* Leave Balance Card - Responsive Grid and Typography */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Saldo Cuti</h4>
              <div className="grid grid-cols-3 gap-2 sm:gap-3 md:gap-4">
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-blue-400">12</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Tahunan</div>
                </div>
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-green-400">5</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Sakit</div>
                </div>
                <div className="text-center">
                  <div className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-purple-400">3</div>
                  <div className="text-xs sm:text-sm md:text-base text-gray-300">Cuti Khusus</div>
                </div>
              </div>
            </div>

            {/* Recent Leave Requests - Responsive Cards */}
            <div className="bg-white/10 backdrop-blur-xl rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 border border-white/20">
              <h4 className="text-base sm:text-lg md:text-xl font-semibold text-white mb-3 sm:mb-4">Pengajuan Terakhir</h4>
              <div className="space-y-3">
                {[
                  { date: '15-20 Jul 2025', type: 'Cuti Tahunan', status: 'Approved', days: 4 },
                  { date: '28 Jun 2025', type: 'Cuti Sakit', status: 'Approved', days: 1 },
                  { date: '10-11 Jun 2025', type: 'Cuti Khusus', status: 'Pending', days: 2 }
                ].map((leave, index) => (
                  <div key={index} className="flex justify-between items-center p-3 bg-black/20 rounded-xl">
                    <div>
                      <div className="text-white font-medium">{leave.type}</div>
                      <div className="text-sm text-gray-300">{leave.date} • {leave.days} hari</div>
                    </div>
                    <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                      leave.status === 'Approved' ? 'bg-green-500/20 text-green-400' :
                      leave.status === 'Pending' ? 'bg-yellow-500/20 text-yellow-400' :
                      'bg-red-500/20 text-red-400'
                    }`}>
                      {leave.status}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
      {/* Responsive Container - Mobile First with Tablet/Desktop Breakpoints */}
      <div className="w-full max-w-full sm:max-w-sm md:max-w-2xl lg:max-w-4xl xl:max-w-6xl 2xl:max-w-7xl mx-auto min-h-screen relative overflow-hidden">
        
        {/* Animated Background Elements - Responsive Sizing */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-10 w-24 h-24 sm:w-32 sm:h-32 md:w-40 md:h-40 lg:w-48 lg:h-48 bg-blue-400/10 rounded-full blur-xl animate-pulse"></div>
          <div className="absolute top-40 right-5 w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 lg:w-40 lg:h-40 bg-purple-400/10 rounded-full blur-lg animate-bounce"></div>
          <div className="absolute bottom-40 left-5 w-32 h-32 sm:w-40 sm:h-40 md:w-48 md:h-48 lg:w-56 lg:h-56 bg-pink-400/10 rounded-full blur-2xl animate-pulse"></div>
        </div>

        {/* Status Bar - Hide on Desktop */}
        <div className={`flex justify-between items-center px-4 sm:px-6 pt-3 pb-2 text-white text-sm font-semibold relative z-10 ${isDesktop ? 'lg:hidden' : ''}`}>
          <span className="text-xs sm:text-sm">{formatTime(currentTime)}</span>
          <div className="flex items-center space-x-1">
            <Wifi className="w-3 h-3 sm:w-4 sm:h-4" />
            <div className="w-5 h-2.5 sm:w-6 sm:h-3 border border-white rounded-sm relative">
              <div className="w-3.5 h-1.5 sm:w-4 sm:h-2 bg-green-500 rounded-sm absolute top-0.5 left-0.5"></div>
            </div>
          </div>
        </div>

        {/* Hero Section - Responsive Typography and Spacing */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 pt-4 sm:pt-6 md:pt-8 lg:pt-10 pb-4 sm:pb-6 relative z-10">
          <div className="text-center mb-6 sm:mb-8">
            <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-2 bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
              Smart Attendance
            </h1>
            <p className="text-sm sm:text-base md:text-lg lg:text-xl text-purple-200">
              {userData?.name || 'Loading...'}
            </p>
          </div>
        </div>

        {/* Tab Navigation - Responsive Layout */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 mb-4 sm:mb-6 relative z-10">
          <div className="flex bg-gradient-to-r from-slate-800/40 via-slate-700/40 to-slate-800/40 backdrop-blur-sm rounded-lg border border-cyan-400/20 p-0.5 sm:p-1 shadow-lg shadow-cyan-500/10">
            
            {/* Active Tab Indicator */}
            <div 
              className={`absolute top-0.5 bottom-0.5 bg-gradient-to-r from-cyan-500/30 via-purple-500/30 to-pink-500/30 backdrop-blur-xl rounded-md border border-cyan-400/40 transition-all duration-300 ease-out ${
                activeTab === 'checkin' ? 'left-0.5 w-[calc(25%-2px)]' :
                activeTab === 'history' ? 'left-[calc(25%+1px)] w-[calc(25%-2px)]' :
                activeTab === 'stats' ? 'left-[calc(50%+1px)] w-[calc(25%-2px)]' :
                'left-[calc(75%+1px)] w-[calc(25%-2px)]'
              }`}
            >
              {/* Glowing edge */}
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-400/20 to-purple-400/20 rounded-md animate-pulse"></div>
            </div>

            {tabItems.map((item, index) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;
              
              return (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className={`relative z-10 flex-1 flex items-center justify-center space-x-1 sm:space-x-1.5 md:space-x-2 px-1 sm:px-2 md:px-3 py-1.5 sm:py-2 md:py-2.5 rounded-md transition-all duration-200 group ${
                    isActive 
                      ? 'text-cyan-300 scale-105' 
                      : 'text-gray-400 hover:text-cyan-400 hover:scale-102'
                  }`}
                >
                  {/* Icon with gaming glow - Responsive Sizing */}
                  <div className="relative">
                    <Icon className={`w-3 h-3 sm:w-3.5 sm:h-3.5 md:w-4 md:h-4 lg:w-5 lg:h-5 flex-shrink-0 transition-all duration-200 ${
                      isActive ? 'filter drop-shadow-sm drop-shadow-cyan-400/50' : 'group-hover:drop-shadow-sm group-hover:drop-shadow-cyan-400/30'
                    }`} />
                    
                    {/* Gaming particles */}
                    {isActive && (
                      <>
                        <div className="absolute -top-0.5 -right-0.5 w-1 h-1 bg-cyan-400 rounded-full animate-ping opacity-60"></div>
                        <div className="absolute -bottom-0.5 -left-0.5 w-0.5 h-0.5 bg-purple-400 rounded-full animate-ping delay-200 opacity-60"></div>
                      </>
                    )}
                  </div>
                  
                  {/* Tab Label - Hide on small mobile, show on larger screens */}
                  <span className={`hidden sm:inline text-xs md:text-sm lg:text-base font-medium truncate transition-all duration-200 ${
                    isActive ? 'text-cyan-300 font-semibold' : 'group-hover:text-cyan-400'
                  }`}>
                    {item.label}
                  </span>
                  
                  {/* Level indicator for active tab */}
                  {isActive && (
                    <div className="absolute -top-1 -right-1 w-2 h-2 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full border border-slate-800 text-[6px] font-bold text-black flex items-center justify-center">
                      •
                    </div>
                  )}
                </button>
              );
            })}
          </div>
          
          {/* Gaming ambient glow */}
          <div className="absolute inset-0 bg-gradient-to-r from-cyan-500/5 via-purple-500/5 to-pink-500/5 rounded-lg blur-xl -z-10"></div>
        </div>

        {/* Tab Content - Responsive Padding and Layout */}
        <div className="px-4 sm:px-6 md:px-8 lg:px-12 pb-16 sm:pb-20 md:pb-24 relative z-10">
          {renderTabContent()}
        </div>

        {/* Leave Form Modal - Responsive Sizing */}
        {showLeaveForm && (
          <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 sm:p-6">
            <div className="bg-white/10 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-white/20 w-full max-w-sm sm:max-w-md md:max-w-lg">
              <h3 className="text-xl font-bold text-white mb-6 text-center">Pengajuan Cuti</h3>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Jenis Cuti</label>
                  <select 
                    value={leaveForm.type}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, type: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                  >
                    <option value="annual" className="bg-gray-800">Cuti Tahunan</option>
                    <option value="sick" className="bg-gray-800">Cuti Sakit</option>
                    <option value="special" className="bg-gray-800">Cuti Khusus</option>
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai</label>
                    <input 
                      type="date"
                      value={leaveForm.startDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, startDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">Tanggal Selesai</label>
                    <input 
                      type="date"
                      value={leaveForm.endDate}
                      onChange={(e) => setLeaveForm(prev => ({ ...prev, endDate: e.target.value }))}
                      className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Alasan</label>
                  <textarea 
                    value={leaveForm.reason}
                    onChange={(e) => setLeaveForm(prev => ({ ...prev, reason: e.target.value }))}
                    className="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-400 h-20 resize-none"
                    placeholder="Jelaskan alasan pengajuan cuti..."
                  ></textarea>
                </div>
              </div>

              <div className="flex space-x-3 mt-6">
                <button
                  onClick={() => setShowLeaveForm(false)}
                  className="flex-1 bg-gray-500/20 hover:bg-gray-500/30 px-4 py-3 rounded-xl text-white transition-colors"
                >
                  Batal
                </button>
                <button
                  onClick={handleLeaveSubmit}
                  className="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 px-4 py-3 rounded-xl text-white transition-colors flex items-center justify-center space-x-2"
                >
                  <Send className="w-4 h-4" />
                  <span>Kirim</span>
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CreativeAttendanceDashboard;