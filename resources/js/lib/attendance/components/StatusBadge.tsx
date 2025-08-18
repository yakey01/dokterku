/**
 * Unified Status Badge Component
 * Variant-aware status badges for attendance systems
 */

import React from 'react';
import { CheckCircle, XCircle, Clock, AlertCircle, Calendar, MapPin } from 'lucide-react';
import { AttendanceVariant, AttendanceStatus } from '../types';

// Badge variant types
export type BadgeVariant = 'status' | 'location' | 'time' | 'warning' | 'success' | 'error';

export interface StatusBadgeProps {
  variant: AttendanceVariant;
  type: BadgeVariant;
  status?: AttendanceStatus;
  text: string;
  icon?: React.ReactNode;
  size?: 'sm' | 'md' | 'lg';
  showIcon?: boolean;
  className?: string;
}

/**
 * Get icon for status
 */
const getStatusIcon = (status: AttendanceStatus, type: BadgeVariant) => {
  if (type === 'location') return <MapPin className="w-4 h-4" />;
  if (type === 'time') return <Clock className="w-4 h-4" />;
  if (type === 'warning') return <AlertCircle className="w-4 h-4" />;
  
  switch (status) {
    case 'present':
    case 'completed':
      return <CheckCircle className="w-4 h-4" />;
    case 'late':
      return <Clock className="w-4 h-4" />;
    case 'absent':
      return <XCircle className="w-4 h-4" />;
    case 'pending':
      return <AlertCircle className="w-4 h-4" />;
    default:
      return <Calendar className="w-4 h-4" />;
  }
};

/**
 * Dokter variant styles (dark gaming theme)
 */
const getDokterStyles = (type: BadgeVariant, status?: AttendanceStatus) => {
  const baseClasses = "inline-flex items-center gap-2 px-3 py-1 rounded-lg text-sm font-medium border backdrop-blur-sm";
  
  switch (type) {
    case 'status':
      switch (status) {
        case 'present':
        case 'completed':
          return `${baseClasses} bg-green-900/30 border-green-500/20 text-green-300`;
        case 'late':
          return `${baseClasses} bg-yellow-900/30 border-yellow-500/20 text-yellow-300`;
        case 'absent':
          return `${baseClasses} bg-red-900/30 border-red-500/20 text-red-300`;
        case 'pending':
          return `${baseClasses} bg-blue-900/30 border-blue-500/20 text-blue-300`;
        default:
          return `${baseClasses} bg-slate-900/30 border-slate-500/20 text-slate-300`;
      }
    case 'location':
      return `${baseClasses} bg-purple-900/30 border-purple-500/20 text-purple-300`;
    case 'time':
      return `${baseClasses} bg-cyan-900/30 border-cyan-500/20 text-cyan-300`;
    case 'warning':
      return `${baseClasses} bg-orange-900/30 border-orange-500/20 text-orange-300`;
    case 'success':
      return `${baseClasses} bg-emerald-900/30 border-emerald-500/20 text-emerald-300`;
    case 'error':
      return `${baseClasses} bg-red-900/30 border-red-500/20 text-red-300`;
    default:
      return `${baseClasses} bg-slate-900/30 border-slate-500/20 text-slate-300`;
  }
};

/**
 * Paramedis variant styles (light modern theme)
 */
const getParamedisStyles = (type: BadgeVariant, status?: AttendanceStatus) => {
  const baseClasses = "inline-flex items-center gap-2 px-3 py-1 rounded-lg text-sm font-medium border";
  
  switch (type) {
    case 'status':
      switch (status) {
        case 'present':
        case 'completed':
          return `${baseClasses} bg-green-100 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300`;
        case 'late':
          return `${baseClasses} bg-yellow-100 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-300`;
        case 'absent':
          return `${baseClasses} bg-red-100 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300`;
        case 'pending':
          return `${baseClasses} bg-blue-100 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300`;
        default:
          return `${baseClasses} bg-gray-100 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300`;
      }
    case 'location':
      return `${baseClasses} bg-purple-100 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300`;
    case 'time':
      return `${baseClasses} bg-blue-100 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300`;
    case 'warning':
      return `${baseClasses} bg-orange-100 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-300`;
    case 'success':
      return `${baseClasses} bg-green-100 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300`;
    case 'error':
      return `${baseClasses} bg-red-100 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300`;
    default:
      return `${baseClasses} bg-gray-100 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300`;
  }
};

/**
 * Get size classes
 */
const getSizeClasses = (size: 'sm' | 'md' | 'lg') => {
  switch (size) {
    case 'sm':
      return 'px-2 py-0.5 text-xs';
    case 'lg':
      return 'px-4 py-2 text-base';
    default:
      return 'px-3 py-1 text-sm';
  }
};

/**
 * Main Status Badge Component
 */
export const StatusBadge: React.FC<StatusBadgeProps> = ({
  variant,
  type,
  status,
  text,
  icon,
  size = 'md',
  showIcon = true,
  className = ''
}) => {
  const styles = variant === 'dokter' 
    ? getDokterStyles(type, status)
    : getParamedisStyles(type, status);
  
  const sizeClasses = getSizeClasses(size);
  const displayIcon = icon || (showIcon ? getStatusIcon(status || 'pending', type) : null);
  
  return (
    <span className={`${styles} ${sizeClasses} ${className}`}>
      {displayIcon}
      <span>{text}</span>
    </span>
  );
};

/**
 * Preset badge components for common use cases
 */
export const AttendanceStatusBadge: React.FC<{
  variant: AttendanceVariant;
  status: AttendanceStatus;
  size?: 'sm' | 'md' | 'lg';
}> = ({ variant, status, size }) => {
  const statusText = {
    present: 'Hadir',
    late: 'Terlambat', 
    completed: 'Selesai',
    pending: 'Menunggu',
    absent: 'Tidak Hadir'
  };

  return (
    <StatusBadge
      variant={variant}
      type="status"
      status={status}
      text={statusText[status]}
      size={size}
    />
  );
};

export const LocationBadge: React.FC<{
  variant: AttendanceVariant;
  locationName: string;
  distance?: number;
  size?: 'sm' | 'md' | 'lg';
}> = ({ variant, locationName, distance, size }) => {
  const text = distance ? `${locationName} (${Math.round(distance)}m)` : locationName;
  
  return (
    <StatusBadge
      variant={variant}
      type="location"
      text={text}
      size={size}
    />
  );
};

export const TimeBadge: React.FC<{
  variant: AttendanceVariant;
  time: string;
  label?: string;
  size?: 'sm' | 'md' | 'lg';
}> = ({ variant, time, label, size }) => {
  const text = label ? `${label}: ${time}` : time;
  
  return (
    <StatusBadge
      variant={variant}
      type="time"
      text={text}
      size={size}
    />
  );
};

export const GPSBadge: React.FC<{
  variant: AttendanceVariant;
  accuracy?: number;
  isLoading?: boolean;
  error?: boolean;
  size?: 'sm' | 'md' | 'lg';
}> = ({ variant, accuracy, isLoading, error, size }) => {
  let text = 'GPS Siap';
  let type: BadgeVariant = 'success';
  
  if (isLoading) {
    text = 'Mencari lokasi...';
    type = 'warning';
  } else if (error) {
    text = 'GPS Error';
    type = 'error';
  } else if (accuracy) {
    text = `GPS Akurat (${Math.round(accuracy)}m)`;
    type = accuracy <= 50 ? 'success' : 'warning';
  }
  
  return (
    <StatusBadge
      variant={variant}
      type={type}
      text={text}
      size={size}
    />
  );
};

export default StatusBadge;