/**
 * Unified Attendance Card Component
 * Variant-aware component that preserves original UI appearance for both dokter and paramedis
 */

import React from 'react';
import { Clock, MapPin, CheckCircle, XCircle, AlertCircle, Navigation, Timer, Activity } from 'lucide-react';
import { AttendanceVariant, LocationData, ShiftInfo, AttendanceCalculation } from '../types';
import { formatTime, formatDate } from '../timeUtils';

// Component props interface
export interface AttendanceCardProps {
  variant: AttendanceVariant;
  isCheckedIn: boolean;
  currentTime: Date;
  checkInTime?: string | null;
  checkOutTime?: string | null;
  workingHours?: AttendanceCalculation;
  currentShift?: ShiftInfo;
  workLocation?: string;
  userName?: string;
  gpsStatus?: {
    isLoading: boolean;
    error: any;
    accuracy: number | null;
  };
  onCheckIn: () => void;
  onCheckOut: () => void;
  onRefreshGPS?: () => void;
  isOperationInProgress?: boolean;
  canCheckIn?: boolean;
  canCheckOut?: boolean;
  statusMessage?: string;
}

/**
 * Get greeting based on time
 */
const getGreeting = (): string => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
};

/**
 * Dokter variant (preserve original dark gaming theme)
 */
const DokterAttendanceCard: React.FC<AttendanceCardProps> = ({
  isCheckedIn,
  currentTime,
  checkInTime,
  checkOutTime,
  workingHours,
  currentShift,
  workLocation,
  userName,
  gpsStatus,
  onCheckIn,
  onCheckOut,
  onRefreshGPS,
  isOperationInProgress,
  canCheckIn = true,
  canCheckOut = true,
  statusMessage
}) => {
  const greeting = getGreeting();
  
  return (
    <div className="bg-gradient-to-br from-slate-800/60 via-slate-700/60 to-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/30 shadow-xl">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-white mb-1">{greeting} 👋</h2>
          <p className="text-cyan-300 text-sm">
            {formatTime(currentTime)} - {workLocation || 'RS. Kediri Medical Center'}
          </p>
          {userName && (
            <p className="text-slate-300 text-xs mt-1">dr. {userName}</p>
          )}
        </div>
        <div className={`p-3 rounded-full ${isCheckedIn ? 'bg-green-500/20' : 'bg-gray-500/20'}`}>
          {isCheckedIn ? (
            <CheckCircle className="w-6 h-6 text-green-400" />
          ) : (
            <XCircle className="w-6 h-6 text-gray-400" />
          )}
        </div>
      </div>

      {/* Current Shift Info */}
      {currentShift && (
        <div className="mb-6 p-4 bg-slate-900/40 rounded-lg border border-purple-500/20">
          <div className="flex items-center justify-between mb-2">
            <span className="text-purple-300 text-sm">Jadwal Jaga Aktif</span>
            <Clock className="w-4 h-4 text-purple-400" />
          </div>
          <div className="text-white font-semibold">
            {currentShift.nama_shift}
          </div>
          <div className="text-cyan-300 text-sm mt-1">
            {currentShift.jam_masuk} - {currentShift.jam_pulang}
          </div>
        </div>
      )}

      {/* Status Display */}
      <div className="grid grid-cols-2 gap-4 mb-6">
        <div className="bg-slate-900/40 rounded-lg p-3 border border-cyan-500/20">
          <div className="text-cyan-400 text-xs mb-1">Check-in</div>
          <div className="text-white font-semibold">
            {checkInTime ? formatTime(checkInTime) : '--:--'}
          </div>
        </div>
        <div className="bg-slate-900/40 rounded-lg p-3 border border-purple-500/20">
          <div className="text-purple-400 text-xs mb-1">Check-out</div>
          <div className="text-white font-semibold">
            {checkOutTime ? formatTime(checkOutTime) : '--:--'}
          </div>
        </div>
      </div>

      {/* Working Hours */}
      {isCheckedIn && workingHours && (
        <div className="mb-6 p-4 bg-gradient-to-r from-cyan-900/30 to-purple-900/30 rounded-lg">
          <div className="text-cyan-300 text-sm mb-1">Waktu Kerja</div>
          <div className="text-white text-2xl font-bold">
            {workingHours.workingHours}
          </div>
        </div>
      )}

      {/* GPS Status */}
      {gpsStatus && (
        <div className="mb-6 p-3 bg-slate-900/40 rounded-lg">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Navigation className="w-4 h-4 text-blue-400" />
              <span className="text-blue-300 text-sm">
                {gpsStatus.isLoading ? 'Mencari lokasi...' : 
                 gpsStatus.error ? 'GPS Error' : 
                 gpsStatus.accuracy ? `GPS Akurat (${Math.round(gpsStatus.accuracy)}m)` : 'GPS Siap'}
              </span>
            </div>
            {onRefreshGPS && (
              <button
                onClick={onRefreshGPS}
                className="text-xs text-cyan-400 hover:text-cyan-300 transition-colors"
                disabled={gpsStatus.isLoading}
              >
                🔄 Refresh
              </button>
            )}
          </div>
        </div>
      )}

      {/* Status Message */}
      {statusMessage && (
        <div className="mb-6 p-3 bg-yellow-900/30 rounded-lg border border-yellow-500/20">
          <div className="text-yellow-300 text-sm">{statusMessage}</div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="space-y-3">
        {!isCheckedIn ? (
          <button
            onClick={onCheckIn}
            disabled={!canCheckIn || isOperationInProgress}
            className="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-4 rounded-lg font-semibold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
          >
            {isOperationInProgress ? (
              <div className="flex items-center justify-center gap-2">
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Memproses...
              </div>
            ) : (
              <div className="flex items-center justify-center gap-2">
                <CheckCircle className="w-5 h-5" />
                Check In
              </div>
            )}
          </button>
        ) : (
          <button
            onClick={onCheckOut}
            disabled={!canCheckOut || isOperationInProgress}
            className="w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white py-4 rounded-lg font-semibold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
          >
            {isOperationInProgress ? (
              <div className="flex items-center justify-center gap-2">
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Memproses...
              </div>
            ) : (
              <div className="flex items-center justify-center gap-2">
                <XCircle className="w-5 h-5" />
                Check Out
              </div>
            )}
          </button>
        )}
      </div>
    </div>
  );
};

/**
 * Paramedis variant (preserve original light gradient theme)
 */
const ParamedisAttendanceCard: React.FC<AttendanceCardProps> = ({
  isCheckedIn,
  currentTime,
  checkInTime,
  checkOutTime,
  workingHours,
  userName,
  gpsStatus,
  onCheckIn,
  onCheckOut,
  isOperationInProgress,
  canCheckIn = true,
  canCheckOut = true,
  statusMessage
}) => {
  const greeting = getGreeting();
  
  return (
    <>
      {/* Header Card - Purple Gradient */}
      <div className="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 border-0 shadow-xl rounded-lg">
        <div className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-white mb-1">{greeting} 👋</h1>
              <p className="text-purple-100">
                {formatTime(currentTime)} - {formatDate(currentTime)}
              </p>
              {userName && (
                <p className="text-purple-200 text-sm mt-1">{userName}</p>
              )}
            </div>
            <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
              <Clock className="w-6 h-6 text-white" />
            </div>
          </div>
        </div>
      </div>

      {/* Main Content Card */}
      <div className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 dark:from-gray-900 dark:to-purple-950/30 backdrop-blur-sm rounded-lg">
        <div className="p-8 space-y-6">
          {/* Status and Time Display */}
          <div className="text-center space-y-2">
            <div className="text-lg text-gray-600 dark:text-gray-300">
              Status: <span className={`font-semibold ${isCheckedIn ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'}`}>
                {isCheckedIn ? 'Sedang Bekerja' : 'Belum Check-in'}
              </span>
            </div>
            
            <div className="text-5xl bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent font-bold">
              {formatTime(currentTime, { includeSeconds: true })}
            </div>
          </div>

          {/* Working Duration */}
          {isCheckedIn && workingHours && (
            <div className="text-center">
              <div className="flex items-center justify-center gap-2 mb-2">
                <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Timer className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                <span className="text-gray-600 dark:text-gray-300 font-medium">Durasi Kerja</span>
              </div>
              <div className="text-3xl font-bold text-gray-800 dark:text-gray-200">
                {workingHours.workingHours}
              </div>
            </div>
          )}

          {/* Attendance Times */}
          <div className="grid grid-cols-2 gap-4">
            <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
              <div className="text-green-600 dark:text-green-400 text-sm font-medium mb-1">Check-in</div>
              <div className="text-xl font-bold text-gray-800 dark:text-gray-200">
                {checkInTime ? formatTime(checkInTime) : '--:--'}
              </div>
            </div>
            <div className="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
              <div className="text-red-600 dark:text-red-400 text-sm font-medium mb-1">Check-out</div>
              <div className="text-xl font-bold text-gray-800 dark:text-gray-200">
                {checkOutTime ? formatTime(checkOutTime) : '--:--'}
              </div>
            </div>
          </div>

          {/* GPS Status */}
          {gpsStatus && (
            <div className="flex items-center justify-center gap-2 p-4 bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 rounded-xl">
              <MapPin className="w-5 h-5 text-green-600 dark:text-green-400" />
              <span className="text-green-700 dark:text-green-300 font-medium">
                {gpsStatus.isLoading ? 'Mencari lokasi...' : 
                 gpsStatus.error ? 'GPS Error' : 
                 gpsStatus.accuracy ? `Lokasi terdeteksi (${Math.round(gpsStatus.accuracy)}m)` : 'GPS Siap'}
              </span>
            </div>
          )}

          {/* Status Message */}
          {statusMessage && (
            <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
              <div className="text-yellow-700 dark:text-yellow-300 text-sm text-center">{statusMessage}</div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="space-y-3">
            {!isCheckedIn ? (
              <button
                onClick={onCheckIn}
                disabled={!canCheckIn || isOperationInProgress}
                className="w-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 hover:from-blue-600 hover:to-blue-700 dark:hover:from-blue-700 dark:hover:to-blue-800 text-white shadow-lg h-14 text-lg font-semibold transition-all duration-300 disabled:opacity-50 rounded-lg"
              >
                {isOperationInProgress ? (
                  <div className="flex items-center justify-center gap-2">
                    <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Memproses Check-in...
                  </div>
                ) : (
                  <div className="flex items-center justify-center gap-2">
                    <CheckCircle className="w-5 h-5" />
                    Check In Sekarang
                  </div>
                )}
              </button>
            ) : (
              <button
                onClick={onCheckOut}
                disabled={!canCheckOut || isOperationInProgress}
                className="w-full bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 hover:from-red-600 hover:to-red-700 dark:hover:from-red-700 dark:hover:to-red-800 text-white shadow-lg h-14 text-lg font-semibold transition-all duration-300 disabled:opacity-50 rounded-lg"
              >
                {isOperationInProgress ? (
                  <div className="flex items-center justify-center gap-2">
                    <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Memproses Check-out...
                  </div>
                ) : (
                  <div className="flex items-center justify-center gap-2">
                    <XCircle className="w-5 h-5" />
                    Check Out Sekarang
                  </div>
                )}
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
};

/**
 * Main unified component with variant switching
 */
export const UnifiedAttendanceCard: React.FC<AttendanceCardProps> = (props) => {
  return props.variant === 'dokter' ? (
    <DokterAttendanceCard {...props} />
  ) : (
    <ParamedisAttendanceCard {...props} />
  );
};

export default UnifiedAttendanceCard;