/**
 * Unified Jaspel Card Component
 * Supports both dokter and paramedis variants with shared styling patterns
 */

import React from 'react';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  Users, 
  Activity,
  ChevronRight,
  Zap
} from 'lucide-react';
import { motion } from 'framer-motion';
import { 
  BaseJaspelItem, 
  DokterJaspelItem, 
  ParamedisJaspelItem, 
  JaspelVariant,
  isDokterJaspelItem 
} from '../../lib/jaspel/types';
import { 
  formatCurrency, 
  formatDate, 
  getStatusBadge, 
  getComplexityBadge 
} from '../../lib/jaspel/utils';
import GamingBadge from '../ui/GamingBadge';

interface JaspelCardProps {
  item: BaseJaspelItem;
  variant: JaspelVariant;
  onClick?: (item: BaseJaspelItem) => void;
  showDetails?: boolean;
  compact?: boolean;
  animated?: boolean;
  className?: string;
}

const JaspelCard: React.FC<JaspelCardProps> = ({
  item,
  variant,
  onClick,
  showDetails = true,
  compact = false,
  animated = true,
  className = ''
}) => {
  const isDokter = variant === 'dokter';
  const dokterItem = isDokterJaspelItem(item) ? item as DokterJaspelItem : null;
  const isClickable = !!onClick;

  // Card styling based on variant
  const cardClasses = [
    'rounded-xl transition-all duration-300 overflow-hidden',
    isDokter 
      ? 'bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 hover:border-slate-600/80' 
      : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600',
    compact ? 'p-3' : 'p-4 lg:p-6',
    isClickable ? 'cursor-pointer hover:shadow-lg' : '',
    animated ? 'hover:scale-[1.02] hover:-translate-y-1' : '',
    className
  ].filter(Boolean).join(' ');

  // Gaming glow effect for dokter cards
  const glowEffect = isDokter && animated ? {
    boxShadow: '0 0 20px rgba(59, 130, 246, 0.15), 0 0 40px rgba(59, 130, 246, 0.1)'
  } : {};

  // Animation variants
  const cardVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0 },
    hover: { scale: 1.02, y: -4 }
  };

  const handleClick = () => {
    if (onClick) {
      onClick(item);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (onClick && (e.key === 'Enter' || e.key === ' ')) {
      e.preventDefault();
      onClick(item);
    }
  };

  return (
    <motion.div
      className={cardClasses}
      style={glowEffect}
      variants={animated ? cardVariants : undefined}
      initial={animated ? 'hidden' : undefined}
      animate={animated ? 'visible' : undefined}
      whileHover={animated && isClickable ? 'hover' : undefined}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      role={isClickable ? 'button' : 'article'}
      tabIndex={isClickable ? 0 : -1}
      aria-label={isClickable ? `View details for ${item.jenis}` : undefined}
    >
      {/* Header Section */}
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          {/* Title and Type */}
          <div className="flex items-center gap-2 mb-2">
            <h3 className={`font-semibold ${compact ? 'text-sm' : 'text-base lg:text-lg'} ${
              isDokter ? 'text-white' : 'text-gray-900 dark:text-white'
            }`}>
              {dokterItem?.tindakan || item.jenis}
            </h3>
            
            {/* Gaming sparkle for dokter */}
            {isDokter && (
              <Zap className="w-4 h-4 text-yellow-400 animate-pulse" />
            )}
          </div>

          {/* Subtitle/Type */}
          <p className={`text-sm ${
            isDokter ? 'text-slate-300' : 'text-gray-600 dark:text-gray-400'
          }`}>
            {dokterItem?.jenis_jaspel || item.jenis}
          </p>
        </div>

        {/* Status Badge */}
        <GamingBadge
          config={getStatusBadge(item.status, variant)}
          variant={variant}
          size={compact ? 'sm' : 'md'}
        />
      </div>

      {/* Amount Section */}
      <div className={`mb-3 ${compact ? 'mb-2' : ''}`}>
        <div className="flex items-center justify-between">
          <span className={`text-2xl font-bold ${
            isDokter ? 'text-green-400' : 'text-green-600 dark:text-green-400'
          }`}>
            {formatCurrency(item.jumlah)}
          </span>
          
          {/* Complexity badge for dokter */}
          {isDokter && dokterItem?.complexity && showDetails && !compact && (
            <GamingBadge
              config={getComplexityBadge(dokterItem.complexity)}
              variant={variant}
              size="sm"
            />
          )}
        </div>
      </div>

      {/* Details Section */}
      {showDetails && !compact && (
        <div className="space-y-2">
          {/* Date */}
          <div className="flex items-center gap-2 text-sm">
            <Calendar className={`w-4 h-4 ${
              isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'
            }`} />
            <span className={isDokter ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'}>
              {formatDate(item.tanggal)}
            </span>
          </div>

          {/* Dokter-specific details */}
          {isDokter && dokterItem && (
            <>
              {/* Shift and Time */}
              {(dokterItem.shift || dokterItem.jam) && (
                <div className="flex items-center gap-2 text-sm">
                  <Clock className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-300">
                    {dokterItem.shift} {dokterItem.jam && `(${dokterItem.jam})`}
                  </span>
                </div>
              )}

              {/* Location */}
              {dokterItem.lokasi && (
                <div className="flex items-center gap-2 text-sm">
                  <MapPin className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-300">{dokterItem.lokasi}</span>
                </div>
              )}

              {/* Team members */}
              {dokterItem.tim && dokterItem.tim.length > 0 && (
                <div className="flex items-center gap-2 text-sm">
                  <Users className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-300">
                    {dokterItem.tim.join(', ')}
                  </span>
                </div>
              )}

              {/* Patient count */}
              {dokterItem.total_pasien && (
                <div className="flex items-center gap-2 text-sm">
                  <Activity className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-300">
                    {dokterItem.total_pasien} pasien
                  </span>
                </div>
              )}
            </>
          )}

          {/* Description */}
          {item.keterangan && (
            <div className={`text-sm mt-3 p-2 rounded-lg ${
              isDokter 
                ? 'bg-slate-800/50 text-slate-300' 
                : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
            }`}>
              {item.keterangan}
            </div>
          )}
        </div>
      )}

      {/* Footer Section - Validation info */}
      {showDetails && (item.validated_by || item.validated_at) && (
        <div className={`mt-3 pt-3 border-t ${
          isDokter ? 'border-slate-700' : 'border-gray-200 dark:border-gray-700'
        }`}>
          <div className="flex items-center justify-between text-xs">
            {item.validated_by && (
              <span className={isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'}>
                Validated by {item.validated_by}
              </span>
            )}
            {item.validated_at && (
              <span className={isDokter ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'}>
                {new Date(item.validated_at).toLocaleDateString()}
              </span>
            )}
          </div>
        </div>
      )}

      {/* Click indicator */}
      {isClickable && (
        <div className={`absolute top-4 right-4 opacity-0 transition-opacity duration-200 ${
          'group-hover:opacity-100'
        }`}>
          <ChevronRight className={`w-5 h-5 ${
            isDokter ? 'text-slate-400' : 'text-gray-400'
          }`} />
        </div>
      )}

      {/* Gaming validation guarantee for dokter */}
      {isDokter && dokterItem?.validation_guaranteed && (
        <div className="absolute top-2 left-2">
          <div className="w-3 h-3 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
        </div>
      )}
    </motion.div>
  );
};

export default JaspelCard;

/**
 * Compact card variant for lists and grids
 */
export const JaspelCardCompact: React.FC<Omit<JaspelCardProps, 'compact'>> = (props) => (
  <JaspelCard {...props} compact={true} showDetails={false} />
);

/**
 * Detailed card variant for full information display
 */
export const JaspelCardDetailed: React.FC<Omit<JaspelCardProps, 'showDetails'>> = (props) => (
  <JaspelCard {...props} showDetails={true} />
);

/**
 * Card list wrapper with proper spacing and animations
 */
interface JaspelCardListProps {
  items: BaseJaspelItem[];
  variant: JaspelVariant;
  onItemClick?: (item: BaseJaspelItem) => void;
  compact?: boolean;
  animated?: boolean;
  className?: string;
}

export const JaspelCardList: React.FC<JaspelCardListProps> = ({
  items,
  variant,
  onItemClick,
  compact = false,
  animated = true,
  className = ''
}) => {
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  return (
    <motion.div
      className={`grid gap-4 ${
        compact 
          ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' 
          : 'grid-cols-1 lg:grid-cols-2 xl:grid-cols-3'
      } ${className}`}
      variants={animated ? containerVariants : undefined}
      initial={animated ? 'hidden' : undefined}
      animate={animated ? 'visible' : undefined}
    >
      {items.map((item) => (
        <JaspelCard
          key={`${item.id}`}
          item={item}
          variant={variant}
          onClick={onItemClick}
          compact={compact}
          animated={animated}
        />
      ))}
    </motion.div>
  );
};