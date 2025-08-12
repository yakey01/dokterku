// Attendance Card Component for Check-in/Check-out UI

import React from 'react';
import { Clock, MapPin, CheckCircle, XCircle, AlertCircle, Navigation } from 'lucide-react';
import { AttendanceData, ScheduleData } from '../../utils/dokter/attendanceTypes';
import { formatTime, formatDuration, getGreeting } from '../../utils/dokter/attendanceHelpers';

interface AttendanceCardProps {
  isCheckedIn: boolean;
  attendanceData: AttendanceData;
  scheduleData: ScheduleData;
  currentTime: Date;
  gpsStatus: {
    isLoading: boolean;
    error: any;
    accuracy: number | null;
  };
  onCheckIn: () => void;
  onCheckOut: () => void;
  onRefreshGPS: () => void;
  isOperationInProgress: boolean;
}

export const AttendanceCard: React.FC<AttendanceCardProps> = ({
  isCheckedIn,
  attendanceData,
  scheduleData,
  currentTime,
  gpsStatus,
  onCheckIn,
  onCheckOut,
  onRefreshGPS,
  isOperationInProgress
}) => {
  const greeting = getGreeting();
  
  return (
    <div className="bg-gradient-to-br from-slate-800/60 via-slate-700/60 to-slate-800/60 backdrop-blur-xl rounded-2xl p-6 border border-cyan-400/30 shadow-xl">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-white mb-1">{greeting} 👋</h2>
          <p className="text-cyan-300 text-sm">
            {formatTime(currentTime)} - {scheduleData.workLocation?.name || attendanceData.location}
          </p>
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
      {scheduleData.currentShift && (
        <div className="mb-6 p-4 bg-slate-900/40 rounded-lg border border-purple-500/20">
          <div className="flex items-center justify-between mb-2">
            <span className="text-purple-300 text-sm">Jadwal Jaga Aktif</span>
            <Clock className="w-4 h-4 text-purple-400" />
          </div>
          <div className="text-white font-semibold">
            {scheduleData.currentShift.shift_template?.nama || 'Shift'}
          </div>
          <div className="text-cyan-300 text-sm mt-1">
            {scheduleData.currentShift.shift_template?.jam_masuk} - {scheduleData.currentShift.shift_template?.jam_pulang}
          </div>
        </div>
      )}

      {/* Status Display */}
      <div className="grid grid-cols-2 gap-4 mb-6">
        <div className="bg-slate-900/40 rounded-lg p-3 border border-cyan-500/20">
          <div className="text-cyan-400 text-xs mb-1">Check-in</div>
          <div className="text-white font-semibold">
            {attendanceData.checkInTime ? 
              new Date(attendanceData.checkInTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 
              '--:--'}
          </div>
        </div>
        <div className="bg-slate-900/40 rounded-lg p-3 border border-purple-500/20">
          <div className="text-purple-400 text-xs mb-1">Check-out</div>
          <div className="text-white font-semibold">
            {attendanceData.checkOutTime ? 
              new Date(attendanceData.checkOutTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 
              '--:--'}
          </div>
        </div>
      </div>

      {/* Working Hours */}
      {isCheckedIn && (
        <div className="mb-6 p-4 bg-gradient-to-r from-cyan-900/30 to-purple-900/30 rounded-lg">
          <div className="text-cyan-300 text-sm mb-1">Waktu Kerja</div>
          <div className="text-white text-2xl font-bold">
            {attendanceData.workingHours}
          </div>
        </div>
      )}

      {/* GPS Status */}
      <div className="mb-6 p-3 bg-slate-900/40 rounded-lg border border-cyan-500/20">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Navigation className={`w-4 h-4 ${gpsStatus.error ? 'text-red-400' : 'text-green-400'}`} />
            <span className="text-sm text-gray-300">
              GPS {gpsStatus.isLoading ? 'Mencari...' : gpsStatus.error ? 'Error' : 'Aktif'}
            </span>
          </div>
          {gpsStatus.accuracy && (
            <span className="text-xs text-cyan-400">
              ±{gpsStatus.accuracy.toFixed(0)}m
            </span>
          )}
        </div>
      </div>

      {/* Validation Message */}
      {scheduleData.validationMessage && (
        <div className="mb-6 p-3 bg-red-900/20 border border-red-500/30 rounded-lg">
          <div className="flex items-start space-x-2">
            <AlertCircle className="w-4 h-4 text-red-400 mt-0.5" />
            <p className="text-red-300 text-sm">{scheduleData.validationMessage}</p>
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="space-y-3">
        {!isCheckedIn ? (
          <button
            onClick={onCheckIn}
            disabled={!scheduleData.canCheckIn || isOperationInProgress}
            className={`w-full py-4 px-6 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center space-x-2 ${
              scheduleData.canCheckIn && !isOperationInProgress
                ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white shadow-lg shadow-green-500/30'
                : 'bg-gray-700/50 text-gray-400 cursor-not-allowed'
            }`}
          >
            <CheckCircle className="w-5 h-5" />
            <span>{isOperationInProgress ? 'Processing...' : 'Check In'}</span>
          </button>
        ) : (
          <button
            onClick={onCheckOut}
            disabled={!scheduleData.canCheckOut || isOperationInProgress}
            className={`w-full py-4 px-6 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center space-x-2 ${
              scheduleData.canCheckOut && !isOperationInProgress
                ? 'bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white shadow-lg shadow-red-500/30'
                : 'bg-gray-700/50 text-gray-400 cursor-not-allowed'
            }`}
          >
            <XCircle className="w-5 h-5" />
            <span>{isOperationInProgress ? 'Processing...' : 'Check Out'}</span>
          </button>
        )}
        
        {/* GPS Refresh Button */}
        <button
          onClick={onRefreshGPS}
          disabled={gpsStatus.isLoading}
          className="w-full py-3 px-4 rounded-lg bg-slate-800/50 hover:bg-slate-700/50 text-cyan-400 border border-cyan-500/30 transition-all duration-300 flex items-center justify-center space-x-2"
        >
          <Navigation className={`w-4 h-4 ${gpsStatus.isLoading ? 'animate-pulse' : ''}`} />
          <span>Refresh GPS</span>
        </button>
      </div>
    </div>
  );
};