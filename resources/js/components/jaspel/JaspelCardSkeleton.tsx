/**
 * Jaspel Card Loading Skeleton
 * Provides smooth loading states for JaspelCard components
 */

import React from 'react';
import { JaspelVariant } from '../../lib/jaspel/types';

interface JaspelCardSkeletonProps {
  variant: JaspelVariant;
  compact?: boolean;
  count?: number;
  className?: string;
}

const JaspelCardSkeleton: React.FC<Omit<JaspelCardSkeletonProps, 'count'>> = ({
  variant,
  compact = false,
  className = ''
}) => {
  const isDokter = variant === 'dokter';

  // Card styling based on variant
  const cardClasses = [
    'rounded-xl overflow-hidden animate-pulse',
    isDokter 
      ? 'bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50' 
      : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
    compact ? 'p-3' : 'p-4 lg:p-6',
    className
  ].filter(Boolean).join(' ');

  const shimmerClasses = isDokter 
    ? 'bg-slate-700 animate-pulse'
    : 'bg-gray-200 dark:bg-gray-700 animate-pulse';

  return (
    <div className={cardClasses}>
      {/* Header Section */}
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          {/* Title */}
          <div className={`h-5 ${shimmerClasses} rounded mb-2`} style={{ width: '70%' }}></div>
          {/* Subtitle */}
          <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '50%' }}></div>
        </div>
        {/* Status Badge */}
        <div className={`h-6 w-20 ${shimmerClasses} rounded-full`}></div>
      </div>

      {/* Amount Section */}
      <div className={`mb-3 ${compact ? 'mb-2' : ''}`}>
        <div className="flex items-center justify-between">
          {/* Amount */}
          <div className={`h-8 ${shimmerClasses} rounded`} style={{ width: '60%' }}></div>
          {/* Complexity badge (dokter only) */}
          {!compact && isDokter && (
            <div className={`h-5 w-16 ${shimmerClasses} rounded-full`}></div>
          )}
        </div>
      </div>

      {/* Details Section */}
      {!compact && (
        <div className="space-y-2">
          {/* Date line */}
          <div className="flex items-center gap-2">
            <div className={`h-4 w-4 ${shimmerClasses} rounded`}></div>
            <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '40%' }}></div>
          </div>

          {/* Additional details for dokter */}
          {isDokter && (
            <>
              {/* Shift/Time line */}
              <div className="flex items-center gap-2">
                <div className={`h-4 w-4 ${shimmerClasses} rounded`}></div>
                <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '35%' }}></div>
              </div>
              
              {/* Location line */}
              <div className="flex items-center gap-2">
                <div className={`h-4 w-4 ${shimmerClasses} rounded`}></div>
                <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '30%' }}></div>
              </div>
            </>
          )}

          {/* Description area */}
          <div className={`mt-3 p-2 rounded-lg ${
            isDokter 
              ? 'bg-slate-800/50' 
              : 'bg-gray-50 dark:bg-gray-700'
          }`}>
            <div className={`h-4 ${shimmerClasses} rounded mb-1`}></div>
            <div className={`h-4 ${shimmerClasses} rounded`} style={{ width: '80%' }}></div>
          </div>
        </div>
      )}

      {/* Footer */}
      {!compact && (
        <div className={`mt-3 pt-3 border-t ${
          isDokter ? 'border-slate-700' : 'border-gray-200 dark:border-gray-700'
        }`}>
          <div className="flex justify-between">
            <div className={`h-3 ${shimmerClasses} rounded`} style={{ width: '40%' }}></div>
            <div className={`h-3 ${shimmerClasses} rounded`} style={{ width: '25%' }}></div>
          </div>
        </div>
      )}

      {/* Gaming validation indicator for dokter */}
      {isDokter && (
        <div className="absolute top-2 left-2">
          <div className={`w-3 h-3 ${shimmerClasses} rounded-full`}></div>
        </div>
      )}
    </div>
  );
};

/**
 * Multiple card skeletons for list/grid loading states
 */
const JaspelCardSkeletonList: React.FC<JaspelCardSkeletonProps> = ({
  variant,
  compact = false,
  count = 6,
  className = ''
}) => {
  return (
    <div className={`grid gap-4 ${
      compact 
        ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' 
        : 'grid-cols-1 lg:grid-cols-2 xl:grid-cols-3'
    } ${className}`}>
      {Array.from({ length: count }, (_, index) => (
        <JaspelCardSkeleton
          key={index}
          variant={variant}
          compact={compact}
        />
      ))}
    </div>
  );
};

export { JaspelCardSkeleton, JaspelCardSkeletonList };
export default JaspelCardSkeleton;