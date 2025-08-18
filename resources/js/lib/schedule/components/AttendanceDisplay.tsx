/**
 * AttendanceDisplay Component
 * Unified attendance display component for both dokter and paramedis schedules
 */

import React from 'react';
import { Card, CardContent } from '../../ui/card';
import { Badge } from '../../ui/badge';
import { 
  LogIn, 
  LogOut, 
  Clock, 
  Activity, 
  CheckCircle, 
  AlertCircle,
  Calendar,
  Timer,
  Zap
} from 'lucide-react';

import { AttendanceRecord, ScheduleVariant } from '../types';
import { formatAttendanceTime } from '../utils';

interface AttendanceDisplayProps {
  attendance?: AttendanceRecord;
  variant?: ScheduleVariant;
  format?: 'full' | 'compact' | 'minimal';
  showIcon?: boolean;
  className?: string;
}

export const AttendanceDisplay: React.FC<AttendanceDisplayProps> = ({
  attendance,
  variant = 'dokter',
  format = 'full',
  showIcon = true,
  className = ''
}) => {
  if (!attendance) {
    return (
      <div className={`text-xs text-gray-500 dark:text-gray-400 ${className}`}>
        <span>Belum ada data presensi</span>
      </div>
    );
  }

  const hasCheckIn = !!attendance.check_in_time;
  const hasCheckOut = !!attendance.check_out_time;
  const isCompleted = hasCheckIn && hasCheckOut;

  // Get status information
  const getStatusInfo = () => {
    if (isCompleted) {
      return {
        text: 'Selesai',
        color: 'text-green-600 dark:text-green-400',
        bgColor: 'bg-green-50 dark:bg-green-900/30',
        borderColor: 'border-green-200 dark:border-green-700',
        icon: CheckCircle
      };
    } else if (hasCheckIn) {
      return {
        text: 'Sedang Berlangsung',
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-900/30',
        borderColor: 'border-blue-200 dark:border-blue-700',
        icon: Activity
      };
    } else {
      return {
        text: 'Belum Dimulai',
        color: 'text-gray-600 dark:text-gray-400',
        bgColor: 'bg-gray-50 dark:bg-gray-900/30',
        borderColor: 'border-gray-200 dark:border-gray-700',
        icon: Clock
      };
    }
  };

  const statusInfo = getStatusInfo();
  const StatusIcon = statusInfo.icon;

  // Calculate work duration if both times are available
  const getWorkDuration = () => {
    if (!hasCheckIn || !hasCheckOut) return null;

    try {
      const checkIn = new Date(attendance.check_in_time!);
      const checkOut = new Date(attendance.check_out_time!);
      const diffMs = checkOut.getTime() - checkIn.getTime();
      const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
      const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
      
      if (diffHours > 0) {
        return `${diffHours}j ${diffMinutes}m`;
      } else {
        return `${diffMinutes}m`;
      }
    } catch (error) {
      return null;
    }
  };

  const workDuration = getWorkDuration();

  // Minimal format
  if (format === 'minimal') {
    return (
      <div className={`flex items-center gap-1 text-xs ${statusInfo.color} ${className}`}>
        {showIcon && <StatusIcon className="w-3 h-3" />}
        <span>{statusInfo.text}</span>
      </div>
    );
  }

  // Compact format
  if (format === 'compact') {
    return (
      <div className={`flex items-center gap-2 text-xs ${className}`}>
        {showIcon && <StatusIcon className={`w-4 h-4 ${statusInfo.color}`} />}
        <div className="flex items-center gap-3">
          {hasCheckIn && (
            <div className="flex items-center gap-1">
              <LogIn className="w-3 h-3 text-green-600" />
              <span className="font-medium">
                {formatAttendanceTime(attendance.check_in_time)}
              </span>
            </div>
          )}
          {hasCheckOut && (
            <div className="flex items-center gap-1">
              <LogOut className="w-3 h-3 text-red-600" />
              <span className="font-medium">
                {formatAttendanceTime(attendance.check_out_time)}
              </span>
            </div>
          )}
          {!hasCheckIn && !hasCheckOut && (
            <span className="text-gray-500">--:-- / --:--</span>
          )}
        </div>
      </div>
    );
  }

  // Full format (default)
  return (
    <Card className={`${statusInfo.bgColor} border ${statusInfo.borderColor} ${className}`}>
      <CardContent className="p-3 space-y-3">
        {/* Header with status */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {showIcon && <StatusIcon className={`w-4 h-4 ${statusInfo.color}`} />}
            <span className={`text-sm font-medium ${statusInfo.color}`}>
              Data Presensi
            </span>
          </div>
          <Badge 
            variant="outline" 
            className={`text-xs ${statusInfo.color} border-current`}
          >
            {statusInfo.text}
          </Badge>
        </div>

        {/* Attendance times grid */}
        <div className="grid grid-cols-2 gap-3">
          {/* Check In */}
          <div className="flex items-center gap-2">
            <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${
              hasCheckIn 
                ? 'bg-green-100 dark:bg-green-900/50' 
                : 'bg-gray-100 dark:bg-gray-800/50'
            }`}>
              <LogIn className={`w-4 h-4 ${
                hasCheckIn 
                  ? 'text-green-600 dark:text-green-400' 
                  : 'text-gray-400'
              }`} />
            </div>
            <div>
              <div className="text-xs text-gray-500 dark:text-gray-400">Masuk</div>
              <div className="font-medium text-sm">
                {hasCheckIn 
                  ? formatAttendanceTime(attendance.check_in_time)
                  : '--:--'
                }
              </div>
            </div>
          </div>

          {/* Check Out */}
          <div className="flex items-center gap-2">
            <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${
              hasCheckOut 
                ? 'bg-red-100 dark:bg-red-900/50' 
                : 'bg-gray-100 dark:bg-gray-800/50'
            }`}>
              <LogOut className={`w-4 h-4 ${
                hasCheckOut 
                  ? 'text-red-600 dark:text-red-400' 
                  : 'text-gray-400'
              }`} />
            </div>
            <div>
              <div className="text-xs text-gray-500 dark:text-gray-400">Keluar</div>
              <div className="font-medium text-sm">
                {hasCheckOut 
                  ? formatAttendanceTime(attendance.check_out_time)
                  : '--:--'
                }
              </div>
            </div>
          </div>
        </div>

        {/* Work duration (if available) */}
        {workDuration && (
          <div className="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
            <Timer className="w-4 h-4 text-purple-600 dark:text-purple-400" />
            <span className="text-sm text-gray-600 dark:text-gray-300">
              Durasi kerja: 
            </span>
            <span className="font-medium text-sm text-purple-600 dark:text-purple-400">
              {workDuration}
            </span>
          </div>
        )}

        {/* Status indicator */}
        <div className="flex items-center gap-2 text-xs">
          <div className={`w-2 h-2 rounded-full ${
            isCompleted 
              ? 'bg-green-500' 
              : hasCheckIn 
                ? 'bg-blue-500 animate-pulse' 
                : 'bg-gray-400'
          }`} />
          <span className="text-gray-600 dark:text-gray-300">
            {isCompleted 
              ? 'Shift telah selesai'
              : hasCheckIn 
                ? 'Shift sedang berlangsung'
                : 'Shift belum dimulai'
            }
          </span>
        </div>
      </CardContent>
    </Card>
  );
};

/**
 * AttendanceTimeline - Shows attendance in timeline format
 */
export const AttendanceTimeline: React.FC<AttendanceDisplayProps & {
  date?: string;
}> = ({
  attendance,
  date,
  className = ''
}) => {
  if (!attendance) return null;

  const hasCheckIn = !!attendance.check_in_time;
  const hasCheckOut = !!attendance.check_out_time;

  return (
    <div className={`space-y-3 ${className}`}>
      {date && (
        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
          <Calendar className="w-4 h-4" />
          <span>{date}</span>
        </div>
      )}

      <div className="relative">
        {/* Timeline line */}
        <div className="absolute left-4 top-6 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700" />

        {/* Check in event */}
        <div className="relative flex items-start gap-3">
          <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${
            hasCheckIn 
              ? 'bg-green-100 border-green-500 dark:bg-green-900/50 dark:border-green-400'
              : 'bg-gray-100 border-gray-300 dark:bg-gray-800 dark:border-gray-600'
          }`}>
            <LogIn className={`w-4 h-4 ${
              hasCheckIn 
                ? 'text-green-600 dark:text-green-400'
                : 'text-gray-400'
            }`} />
          </div>
          <div className="pb-4">
            <div className="font-medium text-sm">
              {hasCheckIn ? 'Check In' : 'Belum Check In'}
            </div>
            <div className="text-xs text-gray-500 dark:text-gray-400">
              {hasCheckIn 
                ? formatAttendanceTime(attendance.check_in_time)
                : 'Menunggu check in'
              }
            </div>
          </div>
        </div>

        {/* Check out event */}
        <div className="relative flex items-start gap-3">
          <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${
            hasCheckOut 
              ? 'bg-red-100 border-red-500 dark:bg-red-900/50 dark:border-red-400'
              : 'bg-gray-100 border-gray-300 dark:bg-gray-800 dark:border-gray-600'
          }`}>
            <LogOut className={`w-4 h-4 ${
              hasCheckOut 
                ? 'text-red-600 dark:text-red-400'
                : 'text-gray-400'
            }`} />
          </div>
          <div>
            <div className="font-medium text-sm">
              {hasCheckOut ? 'Check Out' : 'Belum Check Out'}
            </div>
            <div className="text-xs text-gray-500 dark:text-gray-400">
              {hasCheckOut 
                ? formatAttendanceTime(attendance.check_out_time)
                : 'Menunggu check out'
              }
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

/**
 * AttendanceSummaryCard - Compact summary for dashboard
 */
export const AttendanceSummaryCard: React.FC<{
  attendanceData: Array<{ date: string; attendance?: AttendanceRecord }>;
  title?: string;
  className?: string;
}> = ({
  attendanceData,
  title = "Ringkasan Presensi",
  className = ''
}) => {
  const totalDays = attendanceData.length;
  const completedDays = attendanceData.filter(
    item => item.attendance?.check_in_time && item.attendance?.check_out_time
  ).length;
  const partialDays = attendanceData.filter(
    item => item.attendance?.check_in_time && !item.attendance?.check_out_time
  ).length;
  
  const completionRate = totalDays > 0 ? Math.round((completedDays / totalDays) * 100) : 0;

  return (
    <Card className={`bg-white/5 backdrop-blur-2xl border border-white/10 ${className}`}>
      <CardContent className="p-4">
        <div className="flex items-center gap-2 mb-3">
          <Activity className="w-5 h-5 text-blue-400" />
          <h3 className="font-semibold text-white">{title}</h3>
        </div>

        <div className="grid grid-cols-3 gap-3 text-center">
          <div>
            <div className="text-2xl font-bold text-green-400">{completedDays}</div>
            <div className="text-xs text-green-300/80">Selesai</div>
          </div>
          <div>
            <div className="text-2xl font-bold text-yellow-400">{partialDays}</div>
            <div className="text-xs text-yellow-300/80">Parsial</div>
          </div>
          <div>
            <div className="text-2xl font-bold text-blue-400">{completionRate}%</div>
            <div className="text-xs text-blue-300/80">Rate</div>
          </div>
        </div>

        {/* Progress bar */}
        <div className="mt-3 bg-gray-700 rounded-full h-2">
          <div 
            className="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full transition-all duration-500"
            style={{ width: `${completionRate}%` }}
          />
        </div>
      </CardContent>
    </Card>
  );
};

export default AttendanceDisplay;