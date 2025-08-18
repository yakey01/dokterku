/**
 * Shared Components Index
 * Centralized export for all reusable dashboard components
 */

// Component exports
export { default as MetricCard } from './MetricCard';
export { default as ProgressBar } from './ProgressBar';
export { default as StatGrid } from './StatGrid';

// Type exports
export type { StatItem } from './StatGrid';

// Utility exports
export { createStatItems, StatGridPresets } from './StatGrid';

// Preset exports
export { MetricCardPresets } from './MetricCard';
export { 
  ProgressBarPresets, 
  ProgressBarGroup,
  shimmerAnimation 
} from './ProgressBar';