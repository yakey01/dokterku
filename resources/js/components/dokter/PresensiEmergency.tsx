import React, { useState, useEffect, useCallback } from 'react';
import { Calendar, Clock, DollarSign, User, Home, MapPin, CheckCircle, XCircle, Zap, Heart, Brain, Shield, Target, Award, TrendingUp, Sun, Moon, Coffee, Star, Crown, Hand, Camera, Wifi, WifiOff, AlertTriangle, History, UserCheck, FileText, Settings, Bell, ChevronLeft, ChevronRight, Filter, Plus, Send, RefreshCw, Navigation } from 'lucide-react';
import DynamicMap from './DynamicMap';
import { useGPSLocation, useGPSAvailability, useGPSPermission } from '../../hooks/useGPSLocation';
import { GPSStatus, GPSStrategy } from '../../utils/GPSManager';
import '../../../css/map-styles.css';

const CreativeAttendanceDashboardEmergency = () => {
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
  
  // Hospital Location Data (Dynamic - dari API)
  const [hospitalLocation, setHospitalLocation] = useState({
    lat: -7.8481, // Default Kediri coordinates
    lng: 112.0178,
    name: 'Loading...',
    address: 'Loading...',
    radius: 50 // meters
  });
  
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
    fallbackLocation: { 
      lat: hospitalLocation.lat, 
      lng: hospitalLocation.lng 
    },
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
  
  // Load hospital data from API
  useEffect(() => {
    const loadHospitalData = async () => {
      try {
        // Try to get from API first
        const response = await fetch('/api/v2/hospital/location', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        
        if (response.ok) {
          const responseData = await response.json();
          const data = responseData.data || responseData;
          setHospitalLocation({
            lat: data.latitude || -7.8481,
            lng: data.longitude || 112.0178,
            name: data.name || 'RS. Kediri Medical Center',
            address: data.address || 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
            radius: data.radius || 50
          });
        } else {
          // Fallback to default data if API fails
          console.log('Using default hospital location data');
          setHospitalLocation({
            lat: -7.8481,
            lng: 112.0178,
            name: 'RS. Kediri Medical Center',
            address: 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
            radius: 50
          });
        }
      } catch (error) {
        console.error('Error loading hospital data:', error);
        // Fallback to default data
        setHospitalLocation({
          lat: -7.8481,
          lng: 112.0178,
          name: 'RS. Kediri Medical Center',
          address: 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
          radius: 50
        });
      }
    };
    
    loadHospitalData();
  }, []);
  
  // Old GPS implementation removed - now using world-class GPS Manager

  // Load user data
  useEffect(() => {
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
          setUserData({
            name: 'Guest User',
            email: 'guest@example.com',
            role: 'guest'
          });
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

        // Check content type before parsing
        const contentType = response.headers.get("content-type");
        console.log('🔍 Content-Type:', contentType);
        
        if (!contentType || !contentType.includes("application/json")) {
          console.error('❌ Server returned non-JSON response. Content-Type:', contentType);
          console.error('❌ This usually means a 404/500 error page was returned instead of JSON');
          throw new Error(`Server returned non-JSON response: ${contentType}`);
        }

        if (response.ok) {
          const data = await response.json();
          console.log('🔍 Response data:', data);
          
          if (data.success && data.data?.user) {
            console.log('🔍 Setting user data:', data.data.user);
            setUserData(data.data.user);
          } else {
            console.warn('User data not found in response:', data);
            setUserData({
              name: 'API User',
              email: 'api@example.com',
              role: 'api_user'
            });
          }
        } else {
          console.error('Failed to load user data:', response.status, response.statusText);
          setUserData({
            name: 'Error User',
            email: 'error@example.com',
            role: 'error_user'
          });
        }
              } catch (error) {
          console.error('Error loading user data:', error);
          
          if (error instanceof SyntaxError) {
            console.error('❌ Invalid JSON response from server - likely HTML error page');
            console.error('❌ Check if API endpoint exists and returns JSON');
          }
          
          console.error('Error details:', {
            name: (error as Error).name,
            message: (error as Error).message,
            stack: (error as Error).stack
          });
          
          // Set default user data if API fails
          setUserData({
            name: 'Fallback User',
            email: 'fallback@example.com',
            role: 'fallback'
          });
        }
    };

    loadUserData();
  }, []);

  // Load schedule and work location data
  useEffect(() => {
    const loadScheduleAndWorkLocation = async () => {
      try {
        // Get token with better error handling
        let token = localStorage.getItem('auth_token');
        if (!token) {
          const csrfMeta = document.querySelector('meta[name="csrf-token"]');
          token = csrfMeta?.getAttribute('content') || '';
        }

        // Validate token before making request
        if (!token) {
          console.warn('No authentication token found for schedule/work location');
          return;
        }

        const headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token
        };

        // TEMPORARY FIX: Use test endpoint that bypasses authentication issues
        // This will show real jadwal jaga data including today's schedule
        const scheduleResponse = await fetch('/api/v2/dashboards/dokter/jadwal-jaga', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
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
          console.log('🔍 Schedule API Response:', scheduleData);
          
          // Ensure scheduleData.data is an array before filtering
          let dataArray = [];
          if (Array.isArray(scheduleData.data)) {
            dataArray = scheduleData.data;
          } else if (scheduleData.data && typeof scheduleData.data === 'object') {
            // If it's an object with schedule arrays (weekly_schedule, calendar_events, etc.)
            if (scheduleData.data.weekly_schedule) {
              dataArray = Array.isArray(scheduleData.data.weekly_schedule) ? scheduleData.data.weekly_schedule : [];
            } else if (scheduleData.data.calendar_events) {
              dataArray = Array.isArray(scheduleData.data.calendar_events) ? scheduleData.data.calendar_events : [];
            }
          }
          
          console.log('📊 Schedule data structure:', {
            hasData: !!scheduleData.data,
            dataType: typeof scheduleData.data,
            isArray: Array.isArray(scheduleData.data),
            hasWeeklySchedule: !!(scheduleData.data?.weekly_schedule),
            hasCalendarEvents: !!(scheduleData.data?.calendar_events),
            arrayLength: dataArray.length
          });
          
          // Filter today's schedule from the response
          const today = new Date().toISOString().split('T')[0];
          console.log('🔍 Today:', today);
          
          const todaySchedule = dataArray.filter((schedule: any) => {
            console.log('🔍 Checking schedule:', schedule);
            return schedule && schedule.tanggal_jaga === today && schedule.status_jaga === 'Aktif';
          });
          
          console.log('🔍 Today Schedule:', todaySchedule);
          
          // Get current shift (first active schedule for today)
          const currentShift = todaySchedule.length > 0 ? todaySchedule[0] : null;
          console.log('🔍 Current Shift:', currentShift);
          
          setScheduleData(prev => ({
            ...prev,
            todaySchedule: todaySchedule,
            currentShift: currentShift
          }));
        } else {
          console.error('Failed to load schedule data:', scheduleResponse.status, scheduleResponse.statusText);
        }

        // TEMPORARY FIX: Use test endpoint for work location
        const workLocationResponse = await fetch('/api/v2/dashboards/dokter/work-location-test', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
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
          setScheduleData(prev => ({
            ...prev,
            workLocation: workLocationData.data?.work_location || null
          }));
        } else {
          console.error('Failed to load work location data:', workLocationResponse.status, workLocationResponse.statusText);
        }

        // Validate current status
        validateCurrentStatus();
      } catch (error) {
        console.error('Error loading schedule and work location:', error);
        // Set default schedule data if API fails
        setScheduleData(prev => ({
          ...prev,
          todaySchedule: [],
          currentShift: null,
          workLocation: null,
          validationMessage: 'Gagal memuat data jadwal dan lokasi kerja'
        }));
      }
    };

    loadScheduleAndWorkLocation();
  }, []);

  // Validate status when schedule data changes
  useEffect(() => {
    validateCurrentStatus();
  }, [scheduleData.todaySchedule, scheduleData.currentShift, scheduleData.workLocation, isCheckedIn]);

  // Validate current status based on schedule and work location
  const validateCurrentStatus = () => {
    const now = new Date();
    const currentTime = now.toTimeString().slice(0, 8); // HH:MM:SS format
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();

    // Check if doctor is on duty today
    const isOnDutyToday = scheduleData.todaySchedule && scheduleData.todaySchedule.length > 0;
    
    // Check if current time is within shift hours
    let isWithinShiftHours = false;
    if (scheduleData.currentShift && scheduleData.currentShift.shift_template) {
      const shiftTemplate = scheduleData.currentShift.shift_template;
      const startTime = shiftTemplate.jam_masuk; // Format: "08:00"
      const endTime = shiftTemplate.jam_pulang; // Format: "16:00"
      
      // Parse shift times
      const [startHour, startMinute] = startTime.split(':').map(Number);
      const [endHour, endMinute] = endTime.split(':').map(Number);
      
      // Convert to minutes for easier comparison
      const currentMinutes = currentHour * 60 + currentMinute;
      const startMinutes = startHour * 60 + startMinute;
      const endMinutes = endHour * 60 + endMinute;
      
      // Handle overnight shifts (end time < start time)
      if (endMinutes < startMinutes) {
        // For overnight shifts, check if current time is after start OR before end
        isWithinShiftHours = currentMinutes >= startMinutes || currentMinutes <= endMinutes;
      } else {
        // For regular shifts, check if current time is within shift hours
        isWithinShiftHours = currentMinutes >= startMinutes && currentMinutes <= endMinutes;
      }
    }

    // Check if work location is assigned
    const hasWorkLocation = scheduleData.workLocation && scheduleData.workLocation.id;
    console.log('Work Location Data:', scheduleData.workLocation);
    console.log('Has Work Location:', hasWorkLocation);

    // Determine if can check in/out
    const canCheckIn = isOnDutyToday && isWithinShiftHours && !isCheckedIn;
    const canCheckOut = isCheckedIn;

    setScheduleData(prev => ({
      ...prev,
      isOnDuty: isOnDutyToday && isWithinShiftHours,
      canCheckIn,
      canCheckOut,
      validationMessage: getValidationMessage(isOnDutyToday, isWithinShiftHours, hasWorkLocation)
    }));
  };

  // Get validation message
  const getValidationMessage = (isOnDutyToday: boolean, isWithinShiftHours: boolean, hasWorkLocation: boolean) => {
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

  // Extended Attendance History Data
  const [attendanceHistory] = useState([
    { date: '2025-08-02', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-08-01', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-31', checkIn: '07:45', checkOut: '16:15', status: 'Present', hours: '8h 30m' },
    { date: '2025-07-30', checkIn: '08:00', checkOut: '16:30', status: 'Late', hours: '8h 30m' },
    { date: '2025-07-29', checkIn: '-', checkOut: '-', status: 'Sick Leave', hours: '0h 0m' },
    { date: '2025-07-28', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-27', checkIn: '07:35', checkOut: '16:25', status: 'Present', hours: '8h 50m' },
    { date: '2025-07-26', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-25', checkIn: '08:15', checkOut: '16:30', status: 'Late', hours: '8h 15m' },
    { date: '2025-07-24', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-23', checkIn: '-', checkOut: '-', status: 'Annual Leave', hours: '0h 0m' },
    { date: '2025-07-22', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-21', checkIn: '07:40', checkOut: '16:20', status: 'Present', hours: '8h 40m' },
    { date: '2025-07-20', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' },
    { date: '2025-07-19', checkIn: '07:30', checkOut: '16:30', status: 'Present', hours: '9h 0m' }
  ]);

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
      
      // Update working hours if checked in
      if (isCheckedIn && attendanceData.checkInTime) {
        const workingTime = new Date() - new Date(attendanceData.checkInTime);
        const hours = Math.floor(workingTime / (1000 * 60 * 60));
        const minutes = Math.floor((workingTime % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((workingTime % (1000 * 60)) / 1000);
        
        setAttendanceData(prev => ({
          ...prev,
          workingHours: `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
        }));
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [isCheckedIn, attendanceData.checkInTime]);

  const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit'
    });
  };

  const formatDate = (date) => {
    return date.toLocaleDateString('id-ID', {
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

  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  const handleFilterChange = (period) => {
    setFilterPeriod(period);
    setCurrentPage(1);
  };

  const handleCheckIn = async () => {
    // Validate schedule and work location first
    if (!scheduleData.canCheckIn) {
      alert(`❌ Tidak dapat melakukan check-in: ${scheduleData.validationMessage}`);
      return;
    }

    try {
      // Get current GPS location using world-class GPS Manager
      console.log('📍 Getting location for check-in...');
      await getCurrentLocation();
      
      // Verify we have a valid location
      if (!gpsLocation) {
        alert('⚠️ Tidak dapat mendapatkan lokasi GPS. Silakan coba lagi atau aktifkan GPS.');
        return;
      }
      
      // Use the location from GPS Manager
      const latitude = gpsLocation.latitude;
      const longitude = gpsLocation.longitude;
      const accuracy = gpsLocation.accuracy;
      
      console.log(`🌍 GPS Location obtained: Source=${gpsSource}, Confidence=${(gpsConfidence * 100).toFixed(0)}%`);
      
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
          location: hospitalLocation.name,
          schedule_id: scheduleData.currentShift?.id,
          work_location_id: scheduleData.workLocation?.id
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        setIsCheckedIn(true);
        const now = new Date();
        setAttendanceData(prev => ({
          ...prev,
          checkInTime: now.toISOString(),
          checkOutTime: null
        }));
        
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
    // Validate if can check out
    if (!scheduleData.canCheckOut) {
      alert('❌ Anda belum melakukan check-in atau tidak dapat melakukan check-out saat ini');
      return;
    }

    try {
      // Get current GPS location using world-class GPS Manager
      console.log('📍 Getting location for check-out...');
      await getCurrentLocation();
      
      // Verify we have a valid location
      if (!gpsLocation) {
        alert('⚠️ Tidak dapat mendapatkan lokasi GPS. Silakan coba lagi atau aktifkan GPS.');
        return;
      }

      // Use the location from GPS Manager
      const latitude = gpsLocation.latitude;
      const longitude = gpsLocation.longitude;
      const accuracy = gpsLocation.accuracy;
      
      console.log(`🌍 GPS Location obtained for checkout: Source=${gpsSource}, Confidence=${(gpsConfidence * 100).toFixed(0)}%`);
      
      // Get authentication token for check-out
      let token = localStorage.getItem('auth_token');
      if (!token) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        token = csrfMeta?.getAttribute('content') || '';
      }

      if (!token) {
        alert('❌ Tidak dapat melakukan check-out: Token autentikasi tidak ditemukan');
        return;
      }

      // Call API for check-out with proper authentication
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
        body: JSON.stringify({
          latitude: latitude,
          longitude: longitude,
          accuracy: accuracy,
          location: hospitalLocation.name,
          schedule_id: scheduleData.currentShift?.id,
          work_location_id: scheduleData.workLocation?.id
        })
      });

      const result = await response.json();

      if (result.success) {
        setIsCheckedIn(false);
        const now = new Date();
        setAttendanceData(prev => ({
          ...prev,
          checkOutTime: now.toISOString()
        }));
        
        // Show success message
        alert('✅ Check-out berhasil!');
      } else {
        alert(`❌ Check-out gagal: ${result.message}`);
      }
    } catch (error) {
      console.error('Check-out error:', error);
      alert('❌ Gagal mendapatkan lokasi GPS. Pastikan GPS aktif dan izin lokasi diberikan.');
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
                <div className={`px-3 py-1 rounded-full text-xs font-medium ${
                  scheduleData.isOnDuty 
                    ? 'bg-green-500/20 text-green-300 border border-green-400/30' 
                    : 'bg-red-500/20 text-red-300 border border-red-400/30'
                }`}>
                  {scheduleData.isOnDuty ? '🟢 Siap Jaga' : '🔴 Tidak Jaga'}
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
                       <div>📍 {scheduleData.currentShift.unit_kerja || 'Dokter Jaga'}</div>
                       <div>👨‍⚕️ {scheduleData.currentShift.shift_template?.nama_shift || 'Shift'}</div>
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
                <div className="absolute inset-0 bg-gradient-to-br from-purple-500/30 to-pink-600/30 rounded-3xl"></div>
                <div className="absolute inset-0 bg-white/5 rounded-3xl border border-purple-400/30"></div>
                {isCheckedIn && (
                  <div className="absolute inset-0 bg-gradient-to-br from-purple-400/0 to-purple-400/20 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                )}
                <div className="relative text-center">
                  <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-20 lg:h-20 mx-auto mb-2 sm:mb-3 md:mb-4 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl sm:rounded-2xl flex items-center justify-center">
                    <Moon className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-white" />
                  </div>
                  <div className="text-white font-bold text-sm sm:text-base md:text-lg lg:text-xl">Check Out</div>
                  <div className="text-purple-300 text-xs sm:text-sm md:text-base">Selesai bekerja</div>
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
                      // GPS status is now managed by the hook
                      
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
                      setGpsStatus('loading');
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
                  <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                  <span className="text-xs text-green-300">Live</span>
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
                        {attendanceData.checkInTime ? new Date(attendanceData.checkInTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
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
                        {attendanceData.checkOutTime ? new Date(attendanceData.checkOutTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
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
                  <span className="text-cyan-400">
                    {(() => {
                      if (!isCheckedIn || !attendanceData.checkInTime) return '0%';
                      const workingTime = new Date() - new Date(attendanceData.checkInTime);
                      const hours = workingTime / (1000 * 60 * 60);
                      const percentage = Math.min((hours / 8) * 100, 100);
                      return `${percentage.toFixed(1)}%`;
                    })()}
                  </span>
                </div>
                <div className="w-full bg-gray-700/50 rounded-full h-3">
                  <div 
                    className="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 h-3 rounded-full transition-all duration-500 relative overflow-hidden"
                    style={{ 
                      width: `${(() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return 0;
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        return Math.min((hours / 8) * 100, 100);
                      })()}%` 
                    }}
                  >
                    <div className="absolute inset-0 bg-white/20 animate-pulse"></div>
                  </div>
                </div>
              </div>

              {/* Shortage/Overtime Indicator */}
              <div className="grid grid-cols-2 gap-3">
                <div className={`p-3 rounded-xl border ${
                  (() => {
                    if (!isCheckedIn || !attendanceData.checkInTime) return 'bg-red-500/10 border-red-400/30';
                    const workingTime = new Date() - new Date(attendanceData.checkInTime);
                    const hours = workingTime / (1000 * 60 * 60);
                    return hours >= 8 ? 'bg-green-500/10 border-green-400/30' : 'bg-red-500/10 border-red-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className={`text-lg font-bold ${
                      (() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return 'text-red-400';
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        const shortage = Math.max(8 - hours, 0);
                        return shortage > 0 ? 'text-red-400' : 'text-green-400';
                      })()
                    }`}>
                      {(() => {
                        if (!isCheckedIn || !attendanceData.checkInTime) return '8:00:00';
                        const workingTime = new Date() - new Date(attendanceData.checkInTime);
                        const hours = workingTime / (1000 * 60 * 60);
                        const shortage = Math.max(8 - hours, 0);
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
                  </div>
                </div>

                <div className={`p-3 rounded-xl border ${
                  (() => {
                    if (!isCheckedIn || !attendanceData.checkInTime) return 'bg-blue-500/10 border-blue-400/30';
                    const workingTime = new Date() - new Date(attendanceData.checkInTime);
                    const hours = workingTime / (1000 * 60 * 60);
                    return hours > 8 ? 'bg-blue-500/10 border-blue-400/30' : 'bg-blue-500/10 border-blue-400/30';
                  })()
                }`}>
                  <div className="text-center">
                    <div className="text-lg font-bold text-blue-400">
                      {isCheckedIn ? attendanceData.workingHours : '0:00:00'}
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
                      if (!isCheckedIn) return 'Jangan lupa check-in untuk memulai perhitungan jam kerja!';
                      if (!attendanceData.checkInTime) return 'Mulai hari kerja Anda dengan semangat!';
                      
                      const workingTime = new Date() - new Date(attendanceData.checkInTime);
                      const hours = workingTime / (1000 * 60 * 60);
                      
                      const targetHours = 8; // fixed target 8 jam (no fallback)
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
                  onChange={(e) => handleFilterChange(e.target.value)}
                  className="bg-white/10 backdrop-blur-xl border border-white/20 rounded-lg sm:rounded-xl px-2 sm:px-3 py-1 text-xs sm:text-sm text-white focus:outline-none focus:border-purple-400"
                >
                  <option value="weekly" className="bg-gray-800">7 Hari</option>
                  <option value="monthly" className="bg-gray-800">30 Hari</option>
                </select>
              </div>
            </div>

            {/* History Cards - Responsive Spacing and Typography */}
            {currentData.map((record, index) => (
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
                    <span className="text-white">{record.checkIn}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Keluar: </span>
                    <span className="text-white">{record.checkOut}</span>
                  </div>
                  <div>
                    <span className="text-gray-400">Durasi: </span>
                    <span className="text-white">{record.hours}</span>
                  </div>
                </div>
              </div>
            ))}

            {/* Pagination */}
            {totalPages > 1 && (
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

            <div className="text-center text-sm text-gray-300 mt-4">
              Menampilkan {startIndex + 1}-{Math.min(endIndex, filteredData.length)} dari {filteredData.length} data
            </div>
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

export default CreativeAttendanceDashboardEmergency;