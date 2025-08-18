/**
 * Reusable Gaming Badge Component
 * Unified gaming-style badges with animations and variant support
 */

import React from 'react';
import { LucideIcon } from 'lucide-react';
import { BadgeConfig, JaspelVariant } from '../../lib/jaspel/types';

interface GamingBadgeProps {
  config: BadgeConfig;
  variant?: JaspelVariant;
  children?: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
  onClick?: () => void;
  disabled?: boolean;
}

const GamingBadge: React.FC<GamingBadgeProps> = ({
  config,
  variant = 'dokter',
  children,
  size = 'md',
  className = '',
  onClick,
  disabled = false
}) => {
  const {
    text,
    color,
    bgColor,
    borderColor,
    icon: Icon,
    animated = false,
    gradient,
    textColor,
    glowColor,
    bgGlow,
    pulse,
    priority = 'normal'
  } = config;

  // Size configurations
  const sizeClasses = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-3 py-1.5 text-sm',
    lg: 'px-4 py-2 text-base',
    xl: 'px-6 py-3 text-lg'
  };

  const iconSizes = {
    sm: 12,
    md: 16,
    lg: 20,
    xl: 24
  };

  // Priority-based effects
  const priorityEffects = {
    low: '',
    normal: animated ? 'hover:scale-105 transition-transform duration-200' : '',
    high: animated ? 'hover:scale-110 transition-all duration-300 hover:shadow-lg' : '',
    critical: animated ? 'animate-pulse hover:scale-110 transition-all duration-300' : 'animate-pulse'
  };

  // Variant-specific styles
  const variantStyles = variant === 'dokter' 
    ? 'backdrop-blur-md border-2 shadow-lg' 
    : 'border shadow-sm';

  // Build CSS classes
  const baseClasses = [
    'inline-flex items-center justify-center gap-2 rounded-full font-medium',
    'transition-all duration-200 ease-in-out',
    sizeClasses[size],
    variantStyles,
    color,
    bgColor,
    borderColor,
    priorityEffects[priority],
    onClick && !disabled ? 'cursor-pointer hover:brightness-110' : '',
    disabled ? 'opacity-50 cursor-not-allowed' : '',
    className
  ].filter(Boolean).join(' ');

  // Gaming glow effect for dokter variant
  const glowEffect = variant === 'dokter' && glowColor && animated ? {
    boxShadow: `0 0 20px ${glowColor}, 0 0 40px ${glowColor}50`
  } : {};

  // Background glow for enhanced gaming effect
  const backgroundGlow = bgGlow ? {
    background: `radial-gradient(circle, ${bgGlow} 0%, transparent 70%)`
  } : {};

  // Gradient text effect
  const gradientStyle = gradient && textColor ? {
    background: `linear-gradient(45deg, ${gradient})`,
    WebkitBackgroundClip: 'text',
    WebkitTextFillColor: 'transparent',
    backgroundClip: 'text'
  } : {};

  // Pulse animation for critical items
  const pulseStyle = pulse ? {
    animation: `pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite`
  } : {};

  return (
    <div
      className={baseClasses}
      style={{
        ...glowEffect,
        ...backgroundGlow,
        ...pulseStyle
      }}
      onClick={!disabled ? onClick : undefined}
      role={onClick ? 'button' : 'status'}
      tabIndex={onClick && !disabled ? 0 : -1}
      onKeyDown={(e) => {
        if (onClick && !disabled && (e.key === 'Enter' || e.key === ' ')) {
          e.preventDefault();
          onClick();
        }
      }}
    >
      {/* Icon */}
      {Icon && (
        <Icon 
          size={iconSizes[size]} 
          className={`flex-shrink-0 ${animated ? 'transition-transform duration-200' : ''}`}
          style={gradient && textColor ? gradientStyle : {}}
        />
      )}
      
      {/* Text content */}
      <span 
        className={`flex-1 ${gradient && textColor ? '' : color}`}
        style={gradient && textColor ? gradientStyle : {}}
      >
        {children || text}
      </span>

      {/* Gaming sparkle effect for high priority items */}
      {variant === 'dokter' && priority === 'high' && animated && (
        <div className="absolute -inset-0.5 bg-gradient-to-r from-pink-600 to-purple-600 rounded-full opacity-30 group-hover:opacity-100 transition duration-1000 group-hover:duration-200 animate-tilt blur"></div>
      )}
    </div>
  );
};

export default GamingBadge;

/**
 * Predefined gaming badge variants for common use cases
 */
export const GamingBadgeVariants = {
  goldEarned: (animated = true): BadgeConfig => ({
    text: 'Gold Earned',
    color: 'text-yellow-300',
    bgColor: 'bg-gradient-to-r from-yellow-500/10 to-orange-500/10',
    borderColor: 'border-yellow-400/20',
    animated,
    gradient: 'from-yellow-500 via-orange-500 to-yellow-600',
    textColor: 'text-yellow-100',
    glowColor: 'shadow-yellow-500/30'
  }),

  questPending: (animated = true): BadgeConfig => ({
    text: 'Quest Pending',
    color: 'text-purple-300',
    bgColor: 'bg-gradient-to-r from-purple-500/10 to-pink-500/10',
    borderColor: 'border-purple-400/20',
    animated,
    gradient: 'from-purple-500 via-pink-500 to-purple-600',
    textColor: 'text-purple-100',
    glowColor: 'shadow-purple-500/30'
  }),

  legendaryAchievement: (animated = true): BadgeConfig => ({
    text: 'Legendary',
    color: 'text-purple-300',
    bgColor: 'bg-gradient-to-r from-purple-500/10 to-pink-500/10',
    borderColor: 'border-purple-400/20',
    animated,
    gradient: 'from-purple-500 via-pink-500 to-purple-600',
    textColor: 'text-purple-100',
    glowColor: 'shadow-purple-500/30',
    priority: 'critical'
  }),

  rewardClaimed: (animated = true): BadgeConfig => ({
    text: 'Reward Claimed',
    color: 'text-emerald-300',
    bgColor: 'bg-gradient-to-r from-emerald-500/10 to-teal-500/10',
    borderColor: 'border-emerald-400/20',
    animated,
    gradient: 'from-emerald-500 via-teal-500 to-emerald-600',
    textColor: 'text-emerald-100',
    glowColor: 'shadow-emerald-500/30'
  }),

  statusApproved: (variant: JaspelVariant = 'dokter'): BadgeConfig => ({
    text: variant === 'dokter' ? 'Disetujui' : 'Tervalidasi',
    color: variant === 'dokter' ? 'text-green-400' : 'text-green-800 dark:text-green-300',
    bgColor: variant === 'dokter' 
      ? 'bg-green-500/20' 
      : 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50',
    borderColor: variant === 'dokter' 
      ? 'border-green-500/30' 
      : 'border-green-200 dark:border-green-700',
    animated: variant === 'dokter',
    gradient: variant === 'dokter' ? 'from-green-500 to-emerald-500' : undefined,
    glowColor: variant === 'dokter' ? 'shadow-green-500/30' : undefined
  }),

  statusPending: (variant: JaspelVariant = 'dokter'): BadgeConfig => ({
    text: variant === 'dokter' ? 'Tertunda' : 'Menunggu',
    color: variant === 'dokter' ? 'text-yellow-400' : 'text-yellow-800 dark:text-yellow-300',
    bgColor: variant === 'dokter' 
      ? 'bg-yellow-500/20' 
      : 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50',
    borderColor: variant === 'dokter' 
      ? 'border-yellow-500/30' 
      : 'border-yellow-200 dark:border-yellow-700',
    animated: variant === 'dokter',
    gradient: variant === 'dokter' ? 'from-yellow-500 to-orange-500' : undefined,
    glowColor: variant === 'dokter' ? 'shadow-yellow-500/30' : undefined
  }),

  statusRejected: (variant: JaspelVariant = 'dokter'): BadgeConfig => ({
    text: 'Ditolak',
    color: variant === 'dokter' ? 'text-red-400' : 'text-red-800 dark:text-red-300',
    bgColor: variant === 'dokter' 
      ? 'bg-red-500/20' 
      : 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50',
    borderColor: variant === 'dokter' 
      ? 'border-red-500/30' 
      : 'border-red-200 dark:border-red-700',
    gradient: variant === 'dokter' ? 'from-red-500 to-pink-500' : undefined,
    glowColor: variant === 'dokter' ? 'shadow-red-500/30' : undefined
  })
};