/**
 * StatGrid Component
 * Reusable responsive grid for displaying statistics and metrics
 * Maintains exact gaming-style visual appearance from current dashboard
 */

import React from 'react';
import { LucideIcon } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

// Individual stat item interface
export interface StatItem {
  id: string;
  label: string;
  value: string | number;
  icon?: LucideIcon;
  iconColor?: string;
  valueColor?: string;
  labelColor?: string;
  trend?: {
    value: number;
    direction: 'up' | 'down' | 'stable';
  };
  suffix?: string;
  prefix?: string;
  onClick?: () => void;
}

// Stat card props for individual rendering
interface StatCardProps {
  stat: StatItem;
  index: number;
  variant?: 'default' | 'compact' | 'detailed';
  animated?: boolean;
  glassEffect?: boolean;
}

// Main StatGrid props
interface StatGridProps {
  stats: StatItem[];
  columns?: {
    sm?: number;
    md?: number;
    lg?: number;
    xl?: number;
  };
  gap?: string;
  variant?: 'default' | 'compact' | 'detailed';
  animated?: boolean;
  staggerDelay?: number;
  glassEffect?: boolean;
  className?: string;
  containerClassName?: string;
  title?: string;
  titleClassName?: string;
}

/**
 * StatCard - Individual stat display card
 */
const StatCard: React.FC<StatCardProps> = ({
  stat,
  index,
  variant = 'default',
  animated = true,
  glassEffect = true,
}) => {
  const Icon = stat.icon;
  
  const cardClasses = `
    ${glassEffect ? 'bg-white/10 backdrop-blur-xl' : 'bg-gray-800/50'}
    border border-white/20 rounded-2xl
    ${variant === 'compact' ? 'p-4' : variant === 'detailed' ? 'p-6' : 'p-5'}
    transition-all duration-300
    hover:border-white/30 hover:bg-white/15
    ${stat.onClick ? 'cursor-pointer' : ''}
  `;

  const iconClasses = `
    ${variant === 'compact' ? 'w-5 h-5' : 'w-6 h-6'}
    ${stat.iconColor || 'text-cyan-400'}
  `;

  const valueClasses = `
    ${variant === 'compact' ? 'text-xl' : variant === 'detailed' ? 'text-3xl' : 'text-2xl'}
    font-bold
    ${stat.valueColor || 'text-white'}
  `;

  const labelClasses = `
    ${variant === 'compact' ? 'text-xs' : 'text-sm'}
    ${stat.labelColor || 'text-gray-300'}
  `;

  // Animation variants
  const cardVariants = {
    hidden: { 
      opacity: 0, 
      y: 20,
      scale: 0.95
    },
    visible: { 
      opacity: 1, 
      y: 0,
      scale: 1,
      transition: {
        duration: 0.5,
        delay: index * 0.1,
        ease: [0.22, 1, 0.36, 1]
      }
    },
    hover: {
      scale: 1.02,
      transition: {
        duration: 0.2
      }
    }
  };

  const content = (
    <div className={cardClasses} onClick={stat.onClick}>
      <div className="flex items-start justify-between">
        {/* Icon */}
        {Icon && (
          <div className="flex-shrink-0">
            <Icon className={iconClasses} />
          </div>
        )}
        
        {/* Trend indicator */}
        {stat.trend && (
          <div className={`flex items-center ${stat.trend.direction === 'up' ? 'text-green-400' : stat.trend.direction === 'down' ? 'text-red-400' : 'text-gray-400'}`}>
            {stat.trend.direction === 'up' && (
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 11l5-5m0 0l5 5m-5-5v12" />
              </svg>
            )}
            {stat.trend.direction === 'down' && (
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 13l-5 5m0 0l-5-5m5 5V6" />
              </svg>
            )}
            <span className="text-xs ml-1">{Math.abs(stat.trend.value)}%</span>
          </div>
        )}
      </div>
      
      {/* Value */}
      <div className={`mt-3 ${valueClasses}`}>
        {stat.prefix}
        {typeof stat.value === 'number' ? stat.value.toLocaleString('id-ID') : stat.value}
        {stat.suffix}
      </div>
      
      {/* Label */}
      <div className={`mt-1 ${labelClasses}`}>
        {stat.label}
      </div>

      {/* Detailed variant extra info */}
      {variant === 'detailed' && stat.trend && (
        <div className="mt-3 pt-3 border-t border-white/10">
          <div className="flex items-center justify-between text-xs">
            <span className="text-gray-400">Trend</span>
            <span className={stat.trend.direction === 'up' ? 'text-green-400' : 'text-red-400'}>
              {stat.trend.direction === 'up' ? '+' : '-'}{Math.abs(stat.trend.value)}%
            </span>
          </div>
        </div>
      )}
    </div>
  );

  return animated ? (
    <motion.div
      variants={cardVariants}
      initial="hidden"
      animate="visible"
      whileHover="hover"
    >
      {content}
    </motion.div>
  ) : content;
};

/**
 * StatGrid - Responsive grid container for stats
 * Preserves exact gaming-style visuals from current dashboard
 */
const StatGrid: React.FC<StatGridProps> = ({
  stats,
  columns = { sm: 1, md: 2, lg: 3, xl: 4 },
  gap = 'gap-4',
  variant = 'default',
  animated = true,
  staggerDelay = 100,
  glassEffect = true,
  className = '',
  containerClassName = '',
  title,
  titleClassName = '',
}) => {
  // Build responsive grid classes
  const gridClasses = `
    grid
    ${columns.sm ? `grid-cols-${columns.sm}` : 'grid-cols-1'}
    ${columns.md ? `md:grid-cols-${columns.md}` : 'md:grid-cols-2'}
    ${columns.lg ? `lg:grid-cols-${columns.lg}` : 'lg:grid-cols-3'}
    ${columns.xl ? `xl:grid-cols-${columns.xl}` : 'xl:grid-cols-4'}
    ${gap}
    ${className}
  `;

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: staggerDelay / 1000,
        delayChildren: 0.1
      }
    }
  };

  const content = (
    <>
      {/* Optional title */}
      {title && (
        <div className={`mb-6 ${titleClassName}`}>
          <h2 className="text-2xl font-bold text-white">{title}</h2>
        </div>
      )}

      {/* Stats grid */}
      <div className={gridClasses}>
        {stats.map((stat, index) => (
          <StatCard
            key={stat.id}
            stat={stat}
            index={index}
            variant={variant}
            animated={animated}
            glassEffect={glassEffect}
          />
        ))}
      </div>
    </>
  );

  return (
    <div className={containerClassName}>
      {animated ? (
        <AnimatePresence>
          <motion.div
            variants={containerVariants}
            initial="hidden"
            animate="visible"
          >
            {content}
          </motion.div>
        </AnimatePresence>
      ) : content}
    </div>
  );
};

// Preset configurations for common stat grids
export const StatGridPresets = {
  dashboard: {
    columns: { sm: 1, md: 2, lg: 3, xl: 3 },
    gap: 'gap-6',
    variant: 'default' as const,
    glassEffect: true,
  },
  compact: {
    columns: { sm: 2, md: 3, lg: 4, xl: 6 },
    gap: 'gap-3',
    variant: 'compact' as const,
    glassEffect: true,
  },
  detailed: {
    columns: { sm: 1, md: 2, lg: 2, xl: 3 },
    gap: 'gap-6',
    variant: 'detailed' as const,
    glassEffect: true,
  },
  sidebar: {
    columns: { sm: 1, md: 1, lg: 1, xl: 1 },
    gap: 'gap-3',
    variant: 'compact' as const,
    glassEffect: false,
  },
};

// Helper function to create stat items from dashboard metrics
export const createStatItems = (metrics: any): StatItem[] => {
  return [
    {
      id: 'jaspel',
      label: 'JASPEL Bulan Ini',
      value: metrics.jaspel?.currentMonth || 0,
      prefix: 'Rp ',
      valueColor: 'text-emerald-400',
      trend: metrics.jaspel?.growthPercentage ? {
        value: metrics.jaspel.growthPercentage,
        direction: metrics.jaspel.growthPercentage > 0 ? 'up' : 'down'
      } : undefined,
    },
    {
      id: 'attendance',
      label: 'Kehadiran',
      value: `${metrics.attendance?.rate || 0}%`,
      valueColor: metrics.attendance?.rate >= 100 ? 'text-green-400' : 'text-yellow-400',
      trend: {
        value: metrics.attendance?.rate || 0,
        direction: metrics.attendance?.rate >= 90 ? 'up' : 'down'
      },
    },
    {
      id: 'patients',
      label: 'Pasien Hari Ini',
      value: metrics.patients?.today || 0,
      valueColor: 'text-purple-400',
    },
    {
      id: 'monthlyPatients',
      label: 'Pasien Bulan Ini',
      value: metrics.patients?.thisMonth || 0,
      valueColor: 'text-blue-400',
    },
  ];
};

export default StatGrid;