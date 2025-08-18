/**
 * ScheduleCard Component
 * Unified schedule card with variant support for dokter and paramedis
 */

import React from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent } from '../../ui/card';
import { Badge } from '../../ui/badge';
import { Button } from '../../ui/button';
import { 
  Clock, 
  MapPin, 
  Edit, 
  X, 
  ChevronRight,
  User,
  Calendar,
  Activity
} from 'lucide-react';

import { ScheduleCardProps, isDokterSchedule, isParamedisSchedule } from '../types';
import { 
  formatDateForDisplay, 
  getShiftColor, 
  getScheduleStatus,
  hasAttendanceData,
  formatAttendanceTime
} from '../utils';
import { GamingBadgeWithGlow } from './GamingBadge';
import { useTouchOptimization } from '../hooks';

export const ScheduleCard: React.FC<ScheduleCardProps> = ({
  schedule,
  variant,
  onEdit,
  onCancel,
  onTouchStart,
  onTouchEnd,
  className = ''
}) => {
  const { getTouchClasses } = useTouchOptimization();
  const scheduleStatus = getScheduleStatus(schedule);
  const hasAttendance = hasAttendanceData(schedule);

  // Get variant-specific display data
  const displayData = isDokterSchedule(schedule) ? {
    title: schedule.title,
    subtitle: schedule.subtitle,
    time: schedule.time,
    location: schedule.location,
    shiftType: schedule.shift_template?.nama_shift || 'Regular',
    description: schedule.description,
    status: schedule.status_jaga || 'Terjadwal'
  } : {
    title: formatDateForDisplay(schedule.tanggal),
    subtitle: `Shift ${schedule.jenis}`,
    time: schedule.waktu,
    location: schedule.lokasi,
    shiftType: schedule.jenis,
    description: `${schedule.peran} - ${schedule.employee_name}`,
    status: schedule.status
  };

  const cardClasses = `
    shadow-lg hover:shadow-xl transition-all duration-300 border-0 
    bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced 
    group-hover:bg-white/90 dark:group-hover:bg-gray-900/90 overflow-hidden
    ${getTouchClasses()} ${className}
  `;

  return (
    <motion.div
      whileHover={{ scale: 1.01, y: -2 }}
      transition={{ duration: 0.2 }}
      className="relative group"
    >
      <Card 
        className={cardClasses}
        role="article"
        aria-labelledby={`schedule-title-${schedule.id}`}
        aria-describedby={`schedule-details-${schedule.id}`}
        tabIndex={0}
        onTouchStart={onTouchStart ? (e) => onTouchStart(e, schedule.id) : undefined}
        onTouchEnd={onTouchEnd}
      >
        {/* Gaming Badge */}
        <GamingBadgeWithGlow
          status={scheduleStatus}
          attendance={schedule.attendance}
          variant={variant}
        />

        <CardContent className="p-6 relative z-10">
          {/* Header Section */}
          <div className="flex justify-between items-start mb-4 pr-20">
            <div>
              <h3 
                id={`schedule-title-${schedule.id}`}
                className="text-lg font-semibold text-high-contrast"
              >
                {displayData.title}
              </h3>
              <p 
                className="text-sm text-muted-foreground font-medium"
                aria-label={displayData.subtitle}
              >
                {displayData.subtitle}
              </p>
              {isDokterSchedule(schedule) && (
                <div className="mt-1 flex items-center gap-2">
                  <Badge 
                    variant="outline" 
                    className="text-xs"
                    aria-label={`Difficulty: ${schedule.difficulty}`}
                  >
                    {schedule.difficulty.toUpperCase()}
                  </Badge>
                  <Badge 
                    variant="outline" 
                    className="text-xs"
                    aria-label={`Type: ${schedule.type}`}
                  >
                    {schedule.type.toUpperCase()}
                  </Badge>
                </div>
              )}
            </div>
          </div>

          {/* Details Section */}
          <div id={`schedule-details-${schedule.id}`} className="space-y-3">
            {/* Time */}
            <div className="flex items-center gap-3" role="group" aria-label="Waktu shift">
              <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center" aria-hidden="true">
                <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
              </div>
              <span 
                className="text-sm font-medium text-high-contrast"
                aria-label={`Waktu shift: ${displayData.time}`}
              >
                {displayData.time}
              </span>
              <Badge 
                className={`${getShiftColor(displayData.shiftType)} text-xs font-semibold ml-auto`}
                aria-label={`Jenis shift: ${displayData.shiftType}`}
              >
                {displayData.shiftType.charAt(0).toUpperCase() + displayData.shiftType.slice(1)}
              </Badge>
            </div>
            
            {/* Location */}
            <div className="flex items-center gap-3" role="group" aria-label="Lokasi tugas">
              <div className="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center" aria-hidden="true">
                <MapPin className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              </div>
              <span 
                className="text-sm font-medium text-high-contrast flex-1"
                aria-label={`Lokasi: ${displayData.location}`}
              >
                {displayData.location}
              </span>
              <ChevronRight className="w-4 h-4 text-muted-foreground" aria-hidden="true" />
            </div>

            {/* Employee Info */}
            <div className="flex items-center gap-3" role="group" aria-label="Informasi pegawai">
              <div className="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center" aria-hidden="true">
                <User className="w-4 h-4 text-purple-600 dark:text-purple-400" />
              </div>
              <span 
                className="text-sm font-medium text-high-contrast"
                aria-label={`Pegawai: ${schedule.employee_name}`}
              >
                {schedule.employee_name}
              </span>
              <Badge variant="outline" className="text-xs">
                {schedule.peran}
              </Badge>
            </div>

            {/* Attendance Info (if available) */}
            {hasAttendance && (
              <div className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 space-y-2">
                <div className="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                  <Activity className="w-3 h-3" />
                  <span>Data Presensi</span>
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <span className="text-gray-500 dark:text-gray-400">Masuk:</span>
                    <span className="ml-1 font-medium">
                      {schedule.attendance?.check_in_time 
                        ? formatAttendanceTime(schedule.attendance.check_in_time)
                        : '--:--'
                      }
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-500 dark:text-gray-400">Keluar:</span>
                    <span className="ml-1 font-medium">
                      {schedule.attendance?.check_out_time 
                        ? formatAttendanceTime(schedule.attendance.check_out_time)
                        : '--:--'
                      }
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Description (for dokter variant) */}
            {isDokterSchedule(schedule) && schedule.description && (
              <div className="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-2">
                {schedule.description}
              </div>
            )}
          </div>
          
          {/* Action Buttons */}
          {(
            (isDokterSchedule(schedule) && schedule.status === 'available') ||
            (isParamedisSchedule(schedule) && schedule.status === 'scheduled')
          ) && (onEdit || onCancel) && (
            <motion.div 
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              transition={{ duration: 0.3 }}
              className="flex gap-3 pt-4 mt-4 border-t border-gray-100 dark:border-gray-700"
              role="group"
              aria-label="Aksi jadwal"
            >
              {onEdit && (
                <motion.div
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  className="flex-1"
                >
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={() => onEdit(schedule.id)}
                    className="w-full border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-300 dark:hover:border-blue-600 gap-2 font-medium transition-colors duration-300 btn-primary-accessible focus-outline touch-target"
                    aria-label={`Ubah jadwal ${displayData.title}`}
                    aria-describedby={`schedule-details-${schedule.id}`}
                  >
                    <Edit className="w-4 h-4" aria-hidden="true" />
                    Ubah
                  </Button>
                </motion.div>
              )}
              
              {onCancel && (
                <motion.div
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  className="flex-1"
                >
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={() => onCancel(schedule.id)}
                    className="w-full border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300 dark:hover:border-red-600 gap-2 font-medium transition-colors duration-300 btn-error-accessible focus-outline touch-target"
                    aria-label={`Batalkan jadwal ${displayData.title}`}
                    aria-describedby={`schedule-details-${schedule.id}`}
                  >
                    <X className="w-4 h-4" aria-hidden="true" />
                    Batalkan
                  </Button>
                </motion.div>
              )}
            </motion.div>
          )}
        </CardContent>
      </Card>
    </motion.div>
  );
};

/**
 * Compact ScheduleCard for mobile or list views
 */
export const CompactScheduleCard: React.FC<ScheduleCardProps> = ({
  schedule,
  variant,
  onEdit,
  onCancel,
  className = ''
}) => {
  const scheduleStatus = getScheduleStatus(schedule);
  const displayData = isDokterSchedule(schedule) ? {
    title: schedule.title,
    time: schedule.time,
    location: schedule.location
  } : {
    title: formatDateForDisplay(schedule.tanggal),
    time: schedule.waktu,
    location: schedule.lokasi
  };

  return (
    <Card className={`p-4 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm ${className}`}>
      <div className="flex items-center justify-between">
        <div className="flex-1 min-w-0">
          <h4 className="font-medium text-sm truncate">{displayData.title}</h4>
          <div className="flex items-center gap-2 text-xs text-muted-foreground mt-1">
            <Clock className="w-3 h-3" />
            <span>{displayData.time}</span>
            <span>•</span>
            <MapPin className="w-3 h-3" />
            <span className="truncate">{displayData.location}</span>
          </div>
        </div>
        <div className="flex items-center gap-2 ml-2">
          <div className="w-2 h-2 rounded-full bg-green-500" aria-hidden="true" />
          {onEdit && (
            <Button variant="ghost" size="sm" onClick={() => onEdit(schedule.id)}>
              <Edit className="w-3 h-3" />
            </Button>
          )}
        </div>
      </div>
    </Card>
  );
};

export default ScheduleCard;