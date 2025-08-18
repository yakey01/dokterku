/**
 * MetricCard Component
 * Reusable metric display card that maintains exact visual appearance
 * while providing flexible, type-safe interface
 */

import React from 'react';
import { LucideIcon } from 'lucide-react';

interface MetricCardProps {
  // Content
  title: string;
  value: string | number;
  subtitle?: string;
  change?: {
    value: number;
    text: string;
  };
  
  // Icons
  icon?: LucideIcon;
  trendIcon?: LucideIcon;
  
  // Styling (maintains exact current appearance)
  gradient?: string;
  iconGradient?: string;
  textColor?: string;
  subtitleColor?: string;
  changeColor?: string;
  
  // Layout
  className?: string;
  compact?: boolean;
  
  // Interaction
  onClick?: () => void;
  isLoading?: boolean;
}

/**
 * MetricCard - Displays a metric with icon and optional change indicator
 * Preserves exact visual design from current dashboard
 */
const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  subtitle,
  change,
  icon: Icon,
  trendIcon: TrendIcon,
  gradient = 'from-purple-600 to-pink-600',
  iconGradient = 'from-purple-500 to-pink-500',
  textColor = 'text-white',
  subtitleColor = 'text-gray-300',
  changeColor = 'text-green-300',
  className = '',
  compact = false,
  onClick,
  isLoading = false,
}) => {
  const containerClasses = `
    bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20
    transition-all duration-300 hover:border-white/30 hover:bg-white/15
    ${compact ? 'p-4' : 'p-6'}
    ${onClick ? 'cursor-pointer' : ''}
    ${className}
  `;

  const iconContainerClasses = `
    ${compact ? 'w-10 h-10' : 'w-12 h-12'}
    bg-gradient-to-br ${iconGradient}
    rounded-xl flex items-center justify-center
    shadow-lg
  `;

  const iconClasses = `
    ${compact ? 'w-5 h-5' : 'w-6 h-6'}
    text-white
  `;

  // Loading skeleton
  if (isLoading) {
    return (
      <div className={containerClasses}>
        <div className="animate-pulse">
          <div className="flex items-center justify-between mb-4">
            <div className={`${compact ? 'w-10 h-10' : 'w-12 h-12'} bg-gray-600/50 rounded-xl`}></div>
            {TrendIcon && <div className="w-5 h-5 bg-gray-600/50 rounded"></div>}
          </div>
          <div className="h-4 bg-gray-600/50 rounded w-2/3 mb-2"></div>
          <div className="h-8 bg-gray-600/50 rounded w-full mb-2"></div>
          {subtitle && <div className="h-3 bg-gray-600/50 rounded w-1/2"></div>}
        </div>
      </div>
    );
  }

  return (
    <div className={containerClasses} onClick={onClick}>
      {/* Header with icon and trend */}
      <div className="flex items-center justify-between mb-4">
        {Icon && (
          <div className={iconContainerClasses}>
            <Icon className={iconClasses} />
          </div>
        )}
        {TrendIcon && (
          <TrendIcon className={`w-5 h-5 ${changeColor}`} />
        )}
      </div>

      {/* Title */}
      <div className={`text-sm font-medium ${subtitleColor} mb-2`}>
        {title}
      </div>

      {/* Value */}
      <div className={`${compact ? 'text-xl' : 'text-2xl'} font-bold ${textColor}`}>
        {typeof value === 'number' ? value.toLocaleString('id-ID') : value}
      </div>

      {/* Subtitle or change indicator */}
      {(subtitle || change) && (
        <div className="mt-2">
          {subtitle && (
            <div className={`text-xs ${subtitleColor}`}>
              {subtitle}
            </div>
          )}
          {change && (
            <div className={`text-xs ${changeColor} flex items-center gap-1`}>
              <span>{change.value > 0 ? '+' : ''}{change.value}%</span>
              <span>{change.text}</span>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

// Preset configurations for common metric types
export const MetricCardPresets = {
  jaspel: {
    gradient: 'from-emerald-600 to-teal-600',
    iconGradient: 'from-emerald-500 to-teal-500',
    changeColor: 'text-emerald-300',
  },
  attendance: {
    gradient: 'from-blue-600 to-indigo-600',
    iconGradient: 'from-blue-500 to-indigo-500',
    changeColor: 'text-blue-300',
  },
  patients: {
    gradient: 'from-purple-600 to-pink-600',
    iconGradient: 'from-purple-500 to-pink-500',
    changeColor: 'text-purple-300',
  },
  performance: {
    gradient: 'from-orange-600 to-red-600',
    iconGradient: 'from-orange-500 to-red-500',
    changeColor: 'text-orange-300',
  },
};

export default MetricCard;