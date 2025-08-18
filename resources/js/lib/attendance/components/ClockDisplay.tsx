/**
 * Unified Clock Display Component
 * Variant-aware clock displays for attendance systems preserving original UI
 */

import React from 'react';
import { Clock, Timer, Activity } from 'lucide-react';
import { AttendanceVariant } from '../types';
import { formatTime, formatDate } from '../timeUtils';

// Clock display variant types
export type ClockDisplayType = 'current' | 'working_hours' | 'checkin_checkout' | 'large_main';

export interface ClockDisplayProps {
  variant: AttendanceVariant;
  type: ClockDisplayType;
  currentTime?: Date;
  workingHours?: string;
  checkInTime?: string | null;
  checkOutTime?: string | null;
  showSeconds?: boolean;
  showIcon?: boolean;
  includeDate?: boolean;
  className?: string;
  label?: string;
}

/**
 * Dokter variant styles (dark gaming theme)
 */
const getDokterClockStyles = (type: ClockDisplayType) => {
  const baseClasses = "font-bold";
  
  switch (type) {
    case 'large_main':
      return `${baseClasses} text-5xl bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent`;
    case 'current':
      return `${baseClasses} text-white text-lg`;
    case 'working_hours':
      return `${baseClasses} text-white text-2xl`;
    case 'checkin_checkout':
      return `${baseClasses} text-white font-semibold`;
    default:
      return `${baseClasses} text-white`;
  }
};

/**
 * Paramedis variant styles (light modern theme)
 */
const getParamedisClockStyles = (type: ClockDisplayType) => {
  const baseClasses = "font-bold";
  
  switch (type) {
    case 'large_main':
      return `${baseClasses} text-5xl bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent`;
    case 'current':
      return `${baseClasses} text-gray-800 dark:text-gray-200 text-lg`;
    case 'working_hours':
      return `${baseClasses} text-gray-800 dark:text-gray-200 text-3xl`;
    case 'checkin_checkout':
      return `${baseClasses} text-gray-800 dark:text-gray-200 text-xl`;
    default:
      return `${baseClasses} text-gray-800 dark:text-gray-200`;
  }
};

/**
 * Get container styles for clock types
 */
const getContainerStyles = (variant: AttendanceVariant, type: ClockDisplayType) => {
  if (type === 'working_hours') {
    return variant === 'dokter' 
      ? "p-4 bg-gradient-to-r from-cyan-900/30 to-purple-900/30 rounded-lg"
      : "text-center";
  }
  
  if (type === 'checkin_checkout') {
    return variant === 'dokter'
      ? "bg-slate-900/40 rounded-lg p-3"
      : "text-center p-4 rounded-xl";
  }
  
  return "";
};

/**
 * Get label styles
 */
const getLabelStyles = (variant: AttendanceVariant, type: ClockDisplayType) => {
  const base = "text-sm font-medium mb-1";
  
  if (type === 'working_hours') {
    return variant === 'dokter' 
      ? `${base} text-cyan-300`
      : `${base} text-gray-600 dark:text-gray-300`;
  }
  
  if (type === 'checkin_checkout') {
    return variant === 'dokter'
      ? `${base} text-xs text-cyan-400`
      : `${base} text-sm font-medium`;
  }
  
  return `${base} text-gray-600 dark:text-gray-300`;
};

/**
 * Dokter variant clock display (preserve original dark gaming theme)
 */
const DokterClockDisplay: React.FC<ClockDisplayProps> = ({
  type,
  currentTime,
  workingHours,
  checkInTime,
  checkOutTime,
  showSeconds = false,
  showIcon = true,
  includeDate = false,
  className = '',
  label
}) => {
  const clockStyles = getDokterClockStyles(type);
  const containerStyles = getContainerStyles('dokter', type);
  const labelStyles = getLabelStyles('dokter', type);

  // Render based on type
  switch (type) {
    case 'large_main':
      return (
        <div className={`text-center ${className}`}>
          <div className={clockStyles}>
            {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--:--'}
          </div>
          {includeDate && currentTime && (
            <div className="text-cyan-300 text-sm mt-1">
              {formatDate(currentTime)}
            </div>
          )}
        </div>
      );

    case 'current':
      return (
        <div className={`flex items-center gap-2 ${className}`}>
          {showIcon && <Clock className="w-4 h-4 text-cyan-400" />}
          <span className={clockStyles}>
            {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--'}
            {includeDate && currentTime && ` - ${formatDate(currentTime)}`}
          </span>
        </div>
      );

    case 'working_hours':
      if (!workingHours) return null;
      
      return (
        <div className={`${containerStyles} ${className}`}>
          {label && <div className={labelStyles}>{label}</div>}
          <div className="flex items-center gap-2">
            {showIcon && <Timer className="w-5 h-5 text-cyan-400" />}
            <div className={clockStyles}>
              {workingHours}
            </div>
          </div>
        </div>
      );

    case 'checkin_checkout':
      return (
        <div className={`grid grid-cols-2 gap-4 ${className}`}>
          <div className={`${containerStyles} border-cyan-500/20`}>
            <div className={labelStyles}>Check-in</div>
            <div className={clockStyles}>
              {checkInTime ? formatTime(checkInTime) : '--:--'}
            </div>
          </div>
          <div className={`${containerStyles} border-purple-500/20`}>
            <div className={labelStyles}>Check-out</div>
            <div className={clockStyles}>
              {checkOutTime ? formatTime(checkOutTime) : '--:--'}
            </div>
          </div>
        </div>
      );

    default:
      return (
        <div className={`${clockStyles} ${className}`}>
          {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--'}
        </div>
      );
  }
};

/**
 * Paramedis variant clock display (preserve original light gradient theme)
 */
const ParamedisClockDisplay: React.FC<ClockDisplayProps> = ({
  type,
  currentTime,
  workingHours,
  checkInTime,
  checkOutTime,
  showSeconds = false,
  showIcon = true,
  includeDate = false,
  className = '',
  label
}) => {
  const clockStyles = getParamedisClockStyles(type);
  const containerStyles = getContainerStyles('paramedis', type);
  const labelStyles = getLabelStyles('paramedis', type);

  // Render based on type
  switch (type) {
    case 'large_main':
      return (
        <div className={`text-center space-y-2 ${className}`}>
          <div className="text-lg text-gray-600 dark:text-gray-300">
            Status: <span className="font-semibold text-blue-600 dark:text-blue-400">
              Waktu Saat Ini
            </span>
          </div>
          
          <div className={clockStyles}>
            {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--:--'}
          </div>
          
          {includeDate && currentTime && (
            <div className="text-gray-600 dark:text-gray-300 text-sm">
              {formatDate(currentTime)}
            </div>
          )}
        </div>
      );

    case 'current':
      return (
        <div className={`flex items-center gap-2 ${className}`}>
          {showIcon && <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />}
          <span className={clockStyles}>
            {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--'}
            {includeDate && currentTime && ` - ${formatDate(currentTime)}`}
          </span>
        </div>
      );

    case 'working_hours':
      if (!workingHours) return null;
      
      return (
        <div className={`${containerStyles} ${className}`}>
          <div className="flex items-center justify-center gap-2 mb-2">
            <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
              <Timer className="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>
            <span className="text-gray-600 dark:text-gray-300 font-medium">
              {label || 'Durasi Kerja'}
            </span>
          </div>
          <div className={clockStyles}>
            {workingHours}
          </div>
        </div>
      );

    case 'checkin_checkout':
      return (
        <div className={`grid grid-cols-2 gap-4 ${className}`}>
          <div className={`${containerStyles} bg-green-50 dark:bg-green-900/20`}>
            <div className="text-green-600 dark:text-green-400 text-sm font-medium mb-1">Check-in</div>
            <div className={clockStyles}>
              {checkInTime ? formatTime(checkInTime) : '--:--'}
            </div>
          </div>
          <div className={`${containerStyles} bg-red-50 dark:bg-red-900/20`}>
            <div className="text-red-600 dark:text-red-400 text-sm font-medium mb-1">Check-out</div>
            <div className={clockStyles}>
              {checkOutTime ? formatTime(checkOutTime) : '--:--'}
            </div>
          </div>
        </div>
      );

    default:
      return (
        <div className={`${clockStyles} ${className}`}>
          {currentTime ? formatTime(currentTime, { includeSeconds: showSeconds }) : '--:--'}
        </div>
      );
  }
};

/**
 * Main unified component with variant switching
 */
export const ClockDisplay: React.FC<ClockDisplayProps> = (props) => {
  return props.variant === 'dokter' ? (
    <DokterClockDisplay {...props} />
  ) : (
    <ParamedisClockDisplay {...props} />
  );
};

/**
 * Preset clock components for common use cases
 */
export const MainClock: React.FC<{
  variant: AttendanceVariant;
  currentTime: Date;
  showSeconds?: boolean;
  includeDate?: boolean;
}> = ({ variant, currentTime, showSeconds = true, includeDate = false }) => {
  return (
    <ClockDisplay
      variant={variant}
      type="large_main"
      currentTime={currentTime}
      showSeconds={showSeconds}
      includeDate={includeDate}
    />
  );
};

export const WorkingHoursClock: React.FC<{
  variant: AttendanceVariant;
  workingHours: string;
  label?: string;
}> = ({ variant, workingHours, label }) => {
  return (
    <ClockDisplay
      variant={variant}
      type="working_hours"
      workingHours={workingHours}
      label={label}
      showIcon={true}
    />
  );
};

export const CheckInOutClock: React.FC<{
  variant: AttendanceVariant;
  checkInTime?: string | null;
  checkOutTime?: string | null;
}> = ({ variant, checkInTime, checkOutTime }) => {
  return (
    <ClockDisplay
      variant={variant}
      type="checkin_checkout"
      checkInTime={checkInTime}
      checkOutTime={checkOutTime}
    />
  );
};

export const HeaderClock: React.FC<{
  variant: AttendanceVariant;
  currentTime: Date;
  includeDate?: boolean;
}> = ({ variant, currentTime, includeDate = true }) => {
  return (
    <ClockDisplay
      variant={variant}
      type="current"
      currentTime={currentTime}
      includeDate={includeDate}
      showIcon={true}
    />
  );
};

export default ClockDisplay;