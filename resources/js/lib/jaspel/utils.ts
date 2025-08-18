/**
 * Unified Jaspel Utility Library
 * Shared utility functions extracted from dokter and paramedis components
 */

import React from 'react';
import { 
  TrendingUp, 
  TrendingDown, 
  CheckCircle, 
  Clock, 
  XCircle, 
  AlertTriangle,
  Crown,
  Trophy,
  Medal,
  Gem,
  Coins,
  Gift,
  Sparkles,
  Zap,
  Award
} from 'lucide-react';

import { 
  JaspelStatus, 
  ComplexityLevel, 
  GrowthDirection, 
  BadgeConfig, 
  BaseJaspelItem,
  JaspelSummary,
  JaspelVariant
} from './types';

/**
 * Format currency amounts with Indonesian locale
 */
export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

/**
 * Format date with Indonesian locale
 */
export const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

/**
 * Format date for period display (month year)
 */
export const formatDatePeriod = (month: number, year: number): string => {
  return new Date(year, month - 1).toLocaleDateString('id-ID', { 
    month: 'long', 
    year: 'numeric' 
  });
};

/**
 * Get status color classes for different variants
 */
export const getStatusColor = (status: string, variant: JaspelVariant = 'dokter'): string => {
  const normalizedStatus = status.toLowerCase();
  
  // Gaming-style colors for dokter variant
  if (variant === 'dokter') {
    switch (normalizedStatus) {
      case 'disetujui':
      case 'paid':
      case 'completed':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'pending':
      case 'scheduled':
        return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
      case 'ditolak':
      case 'rejected':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  }
  
  // Modern gradient colors for paramedis variant
  switch (normalizedStatus) {
    case 'pending':
      return 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700';
    case 'paid':
    case 'disetujui':
    case 'completed':
      return 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
    case 'rejected':
    case 'ditolak':
      return 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
    default:
      return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
  }
};

/**
 * Get status badge configuration with gaming elements
 */
export const getStatusBadge = (status: string, variant: JaspelVariant = 'dokter'): BadgeConfig => {
  const normalizedStatus = status.toLowerCase();
  
  if (variant === 'dokter') {
    // Gaming-style badges for dokter
    switch (normalizedStatus) {
      case 'disetujui':
      case 'paid':
      case 'completed':
        return {
          text: 'Disetujui',
          color: 'text-green-400',
          bgColor: 'bg-green-500/20',
          borderColor: 'border-green-500/30',
          icon: CheckCircle,
          animated: true,
          gradient: 'from-green-500 to-emerald-500',
          glowColor: 'shadow-green-500/30'
        };
      case 'pending':
      case 'scheduled':
        return {
          text: 'Tertunda',
          color: 'text-yellow-400',
          bgColor: 'bg-yellow-500/20',
          borderColor: 'border-yellow-500/30',
          icon: Clock,
          animated: true,
          gradient: 'from-yellow-500 to-orange-500',
          glowColor: 'shadow-yellow-500/30'
        };
      case 'ditolak':
      case 'rejected':
        return {
          text: 'Ditolak',
          color: 'text-red-400',
          bgColor: 'bg-red-500/20',
          borderColor: 'border-red-500/30',
          icon: XCircle,
          gradient: 'from-red-500 to-pink-500',
          glowColor: 'shadow-red-500/30'
        };
      default:
        return {
          text: 'Unknown',
          color: 'text-gray-400',
          bgColor: 'bg-gray-500/20',
          borderColor: 'border-gray-500/30',
          icon: AlertTriangle
        };
    }
  }
  
  // Standard badges for paramedis
  switch (normalizedStatus) {
    case 'pending':
      return {
        text: 'Menunggu',
        color: 'text-yellow-800 dark:text-yellow-300',
        bgColor: 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50',
        borderColor: 'border-yellow-200 dark:border-yellow-700'
      };
    case 'paid':
    case 'disetujui':
    case 'completed':
      return {
        text: 'Tervalidasi',
        color: 'text-green-800 dark:text-green-300',
        bgColor: 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50',
        borderColor: 'border-green-200 dark:border-green-700'
      };
    case 'rejected':
    case 'ditolak':
      return {
        text: 'Ditolak',
        color: 'text-red-800 dark:text-red-300',
        bgColor: 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50',
        borderColor: 'border-red-200 dark:border-red-700'
      };
    default:
      return {
        text: 'Unknown',
        color: 'text-gray-800 dark:text-gray-200',
        bgColor: 'bg-gray-100 dark:bg-gray-800',
        borderColor: 'border-gray-200 dark:border-gray-700'
      };
  }
};

/**
 * Get complexity badge configuration
 */
export const getComplexityBadge = (complexity: string): BadgeConfig => {
  const complexityConfig = {
    low: { 
      bg: 'bg-emerald-500/20', 
      text: 'text-emerald-400', 
      border: 'border-emerald-500/30', 
      label: 'Rendah', 
      icon: '●',
      priority: 'low' as const
    },
    medium: { 
      bg: 'bg-yellow-500/20', 
      text: 'text-yellow-400', 
      border: 'border-yellow-500/30', 
      label: 'Sedang', 
      icon: '●●',
      priority: 'normal' as const
    },
    high: { 
      bg: 'bg-orange-500/20', 
      text: 'text-orange-400', 
      border: 'border-orange-500/30', 
      label: 'Tinggi', 
      icon: '●●●',
      priority: 'high' as const
    },
    critical: { 
      bg: 'bg-red-500/20', 
      text: 'text-red-400', 
      border: 'border-red-500/30', 
      label: 'Kritis', 
      icon: '●●●●',
      priority: 'critical' as const
    }
  };
  
  const config = complexityConfig[complexity as ComplexityLevel] || complexityConfig.low;
  
  return {
    text: config.label,
    color: config.text,
    bgColor: config.bg,
    borderColor: config.border,
    priority: config.priority
  };
};

/**
 * Get growth direction and appropriate icon
 */
export const getGrowthIcon = (percent: number): React.ComponentType<any> | null => {
  if (percent > 0) return TrendingUp;
  if (percent < 0) return TrendingDown;
  return null;
};

/**
 * Get growth color based on percentage
 */
export const getGrowthColor = (percent: number): string => {
  if (percent > 0) return 'text-emerald-500';
  if (percent < 0) return 'text-red-500';
  return 'text-gray-500';
};

/**
 * Get gaming-style badge configuration based on achievement type
 */
export const getGamingBadgeConfig = (type: string, animated: boolean = true): BadgeConfig => {
  switch (type.toLowerCase()) {
    case 'gold':
    case 'earned':
    case 'approved':
      return {
        text: 'Gold Earned',
        color: 'text-yellow-300',
        bgColor: 'bg-gradient-to-r from-yellow-500/10 to-orange-500/10',
        borderColor: 'border-yellow-400/20',
        icon: Coins,
        animated,
        gradient: 'from-yellow-500 via-orange-500 to-yellow-600',
        textColor: 'text-yellow-100',
        glowColor: 'shadow-yellow-500/30'
      };
    case 'quest':
    case 'pending':
      return {
        text: 'Quest Pending',
        color: 'text-purple-300',
        bgColor: 'bg-gradient-to-r from-purple-500/10 to-pink-500/10',
        borderColor: 'border-purple-400/20',
        icon: Gift,
        animated,
        gradient: 'from-purple-500 via-pink-500 to-purple-600',
        textColor: 'text-purple-100',
        glowColor: 'shadow-purple-500/30'
      };
    case 'achievement':
    case 'legendary':
      return {
        text: 'Legendary Achievement',
        color: 'text-purple-300',
        bgColor: 'bg-gradient-to-r from-purple-500/10 to-pink-500/10',
        borderColor: 'border-purple-400/20',
        icon: Crown,
        animated,
        gradient: 'from-purple-500 via-pink-500 to-purple-600',
        textColor: 'text-purple-100',
        glowColor: 'shadow-purple-500/30'
      };
    case 'reward':
    case 'trophy':
      return {
        text: 'Reward Claimed',
        color: 'text-emerald-300',
        bgColor: 'bg-gradient-to-r from-emerald-500/10 to-teal-500/10',
        borderColor: 'border-emerald-400/20',
        icon: Trophy,
        animated,
        gradient: 'from-emerald-500 via-teal-500 to-emerald-600',
        textColor: 'text-emerald-100',
        glowColor: 'shadow-emerald-500/30'
      };
    default:
      return {
        text: 'Achievement',
        color: 'text-blue-300',
        bgColor: 'bg-gradient-to-r from-blue-500/10 to-cyan-500/10',
        borderColor: 'border-blue-400/20',
        icon: Award,
        animated,
        gradient: 'from-blue-500 via-cyan-500 to-blue-600',
        textColor: 'text-blue-100',
        glowColor: 'shadow-blue-500/30'
      };
  }
};

/**
 * Calculate percentage change between two values
 */
export const calculatePercentageChange = (current: number, previous: number): number => {
  if (previous === 0) return current > 0 ? 100 : 0;
  return Math.round(((current - previous) / previous) * 100);
};

/**
 * Calculate completion percentage from summary data
 */
export const calculateCompletionPercentage = (summary: JaspelSummary): number => {
  const total = summary.count?.total || (summary.count_paid || 0) + (summary.count_pending || 0) + (summary.count_rejected || 0);
  const completed = summary.count?.approved || summary.count_paid || 0;
  
  if (total === 0) return 0;
  return Math.round((completed / total) * 100);
};

/**
 * Sort Jaspel items by date (newest first)
 */
export const sortJaspelByDate = (items: BaseJaspelItem[]): BaseJaspelItem[] => {
  return [...items].sort((a, b) => new Date(b.tanggal).getTime() - new Date(a.tanggal).getTime());
};

/**
 * Sort Jaspel items by amount (highest first)
 */
export const sortJaspelByAmount = (items: BaseJaspelItem[]): BaseJaspelItem[] => {
  return [...items].sort((a, b) => b.jumlah - a.jumlah);
};

/**
 * Filter Jaspel items by status
 */
export const filterJaspelByStatus = (items: BaseJaspelItem[], status: JaspelStatus): BaseJaspelItem[] => {
  return items.filter(item => item.status === status);
};

/**
 * Filter Jaspel items by date range
 */
export const filterJaspelByDateRange = (
  items: BaseJaspelItem[], 
  startDate: string, 
  endDate: string
): BaseJaspelItem[] => {
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  return items.filter(item => {
    const itemDate = new Date(item.tanggal);
    return itemDate >= start && itemDate <= end;
  });
};

/**
 * Get current month and year
 */
export const getCurrentPeriod = (): { month: number; year: number } => {
  const now = new Date();
  return {
    month: now.getMonth() + 1,
    year: now.getFullYear()
  };
};

/**
 * Calculate period progress (percentage of month completed)
 */
export const calculatePeriodProgress = (): {
  progress: number;
  daysPassed: number;
  daysInMonth: number;
} => {
  const now = new Date();
  const daysPassed = now.getDate();
  const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
  const progress = Math.round((daysPassed / daysInMonth) * 100);
  
  return { progress, daysPassed, daysInMonth };
};

/**
 * Generate gaming achievement text based on amount
 */
export const getAchievementText = (amount: number): string => {
  if (amount >= 5000000) return 'Legendary Master!';
  if (amount >= 2000000) return 'Epic Achiever!';
  if (amount >= 1000000) return 'Gold Champion!';
  if (amount >= 500000) return 'Silver Warrior!';
  if (amount >= 100000) return 'Bronze Fighter!';
  return 'Quest Starter!';
};

/**
 * Get random gaming motivational message
 */
export const getMotivationalMessage = (): string => {
  const messages = [
    'Keep earning those rewards! 🏆',
    'Your dedication is paying off! ✨',
    'Achievement unlocked! 🎮',
    'You\'re on fire! 🔥',
    'Legendary performance! 👑',
    'Quest completed successfully! ⚡',
    'Another milestone reached! 🎯',
    'Excellence in progress! 💎'
  ];
  
  return messages[Math.floor(Math.random() * messages.length)];
};

/**
 * Debounce function for performance optimization
 */
export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout;
  
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

/**
 * Format time for display (HH:MM format)
 */
export const formatTime = (timeString: string | undefined): string => {
  if (!timeString) return '--:--';
  
  try {
    const date = new Date(timeString);
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: false 
    });
  } catch (error) {
    return '--:--';
  }
};

/**
 * Check if value is valid number
 */
export const isValidNumber = (value: any): boolean => {
  return typeof value === 'number' && !isNaN(value) && isFinite(value);
};

/**
 * Safe number conversion with fallback
 */
export const safeNumber = (value: any, fallback: number = 0): number => {
  const num = Number(value);
  return isValidNumber(num) ? num : fallback;
};

/**
 * Safe string conversion with fallback
 */
export const safeString = (value: any, fallback: string = ''): string => {
  return value != null ? String(value) : fallback;
};

/**
 * Calculate summary statistics from items array
 */
export const calculateSummaryFromItems = (items: BaseJaspelItem[]): JaspelSummary => {
  const summary: JaspelSummary = {
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    count: {
      total: items.length,
      approved: 0,
      pending: 0,
      rejected: 0
    }
  };
  
  items.forEach(item => {
    const amount = safeNumber(item.jumlah, 0);
    summary.total += amount;
    
    const status = safeString(item.status).toLowerCase();
    
    if (status === 'disetujui' || status === 'paid' || status === 'completed') {
      summary.approved += amount;
      summary.count.approved++;
    } else if (status === 'pending' || status === 'scheduled') {
      summary.pending += amount;
      summary.count.pending++;
    } else if (status === 'ditolak' || status === 'rejected') {
      summary.rejected += amount;
      summary.count.rejected++;
    }
  });
  
  return summary;
};