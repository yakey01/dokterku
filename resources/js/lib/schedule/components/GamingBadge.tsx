/**
 * GamingBadge Component
 * Reusable gaming-style status badge with attendance awareness
 */

import React from 'react';
import { GamingBadgeProps } from '../types';
import { getBadgeConfig, getScheduleStatus } from '../utils';

export const GamingBadge: React.FC<GamingBadgeProps> = ({
  status,
  attendance,
  variant = 'dokter',
  className = ''
}) => {
  const badgeConfig = getBadgeConfig(status, attendance, variant);
  const BadgeIcon = badgeConfig.icon;

  return (
    <div className={`absolute top-3 right-3 z-20 ${className}`}>
      <div
        className={`
          bg-gradient-to-r ${badgeConfig.gradient} rounded-xl px-3 py-1.5
          border ${badgeConfig.borderColor} shadow-lg ${badgeConfig.glowColor}
          ${badgeConfig.pulse}
        `}
        role="status"
        aria-label={`Status: ${badgeConfig.text}`}
      >
        <div className="flex items-center space-x-1.5">
          <BadgeIcon 
            className="w-3.5 h-3.5 text-white" 
            aria-hidden="true" 
          />
          <span 
            className={`text-xs font-bold ${badgeConfig.textColor} tracking-wide`}
          >
            {badgeConfig.text}
          </span>
        </div>
      </div>
    </div>
  );
};

/**
 * Enhanced GamingBadge with background glow effect
 */
export const GamingBadgeWithGlow: React.FC<GamingBadgeProps & {
  showGlow?: boolean;
}> = ({
  status,
  attendance,
  variant = 'dokter',
  className = '',
  showGlow = true
}) => {
  const badgeConfig = getBadgeConfig(status, attendance, variant);

  return (
    <div className={`relative ${className}`}>
      <GamingBadge
        status={status}
        attendance={attendance}
        variant={variant}
        className=""
      />
      
      {/* Background Glow Effect */}
      {showGlow && (
        <div 
          className={`
            absolute inset-0 bg-gradient-to-br ${badgeConfig.bgGlow} opacity-0 
            group-hover:opacity-20 transition-opacity duration-400 pointer-events-none
          `}
          aria-hidden="true"
        />
      )}
    </div>
  );
};

/**
 * Compact GamingBadge for mobile or small spaces
 */
export const CompactGamingBadge: React.FC<GamingBadgeProps> = ({
  status,
  attendance,
  variant = 'dokter',
  className = ''
}) => {
  const badgeConfig = getBadgeConfig(status, attendance, variant);
  const BadgeIcon = badgeConfig.icon;

  return (
    <div 
      className={`
        inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold
        bg-gradient-to-r ${badgeConfig.gradient} ${badgeConfig.pulse}
        ${className}
      `}
      role="status"
      aria-label={`Status: ${badgeConfig.text}`}
    >
      <BadgeIcon className="w-3 h-3" aria-hidden="true" />
      <span className={badgeConfig.textColor}>
        {badgeConfig.text}
      </span>
    </div>
  );
};

/**
 * Icon-only GamingBadge for minimal displays
 */
export const IconOnlyGamingBadge: React.FC<GamingBadgeProps & {
  size?: 'sm' | 'md' | 'lg';
}> = ({
  status,
  attendance,
  variant = 'dokter',
  size = 'md',
  className = ''
}) => {
  const badgeConfig = getBadgeConfig(status, attendance, variant);
  const BadgeIcon = badgeConfig.icon;
  
  const sizeClasses = {
    sm: 'w-6 h-6 p-1',
    md: 'w-8 h-8 p-1.5',
    lg: 'w-10 h-10 p-2'
  };
  
  const iconSizes = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6'
  };

  return (
    <div
      className={`
        ${sizeClasses[size]} rounded-full flex items-center justify-center
        bg-gradient-to-r ${badgeConfig.gradient} shadow-lg ${badgeConfig.glowColor}
        ${badgeConfig.pulse} ${className}
      `}
      role="status"
      aria-label={`Status: ${badgeConfig.text}`}
      title={badgeConfig.text}
    >
      <BadgeIcon 
        className={`${iconSizes[size]} text-white`} 
        aria-hidden="true" 
      />
    </div>
  );
};

/**
 * Gaming Badge with priority indicator
 */
export const PriorityGamingBadge: React.FC<GamingBadgeProps & {
  showPriority?: boolean;
}> = ({
  status,
  attendance,
  variant = 'dokter',
  showPriority = true,
  className = ''
}) => {
  const badgeConfig = getBadgeConfig(status, attendance, variant);
  const BadgeIcon = badgeConfig.icon;

  const priorityIndicator = showPriority && badgeConfig.priority === 'critical' && (
    <div className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping" />
  );

  return (
    <div className={`relative ${className}`}>
      <div
        className={`
          bg-gradient-to-r ${badgeConfig.gradient} rounded-xl px-3 py-1.5
          border ${badgeConfig.borderColor} shadow-lg ${badgeConfig.glowColor}
          ${badgeConfig.pulse}
        `}
        role="status"
        aria-label={`Status: ${badgeConfig.text}${badgeConfig.priority === 'critical' ? ' (Critical Priority)' : ''}`}
      >
        <div className="flex items-center space-x-1.5">
          <BadgeIcon 
            className="w-3.5 h-3.5 text-white" 
            aria-hidden="true" 
          />
          <span 
            className={`text-xs font-bold ${badgeConfig.textColor} tracking-wide`}
          >
            {badgeConfig.text}
          </span>
        </div>
      </div>
      {priorityIndicator}
    </div>
  );
};

/**
 * Default export for most common use case
 */
export default GamingBadge;