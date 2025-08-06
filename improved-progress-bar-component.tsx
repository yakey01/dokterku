/**
 * IMPROVED DYNAMIC PROGRESS BAR COMPONENT
 * Addresses all identified issues from testing analysis
 */

import React, { useState, useEffect, useRef, useCallback, memo } from 'react';

interface ProgressBarAnimationProps {
  percentage: number;
  delay?: number;
  className?: string;
  gradientColors: string;
  barClassName?: string;
  onComplete?: () => void;
  ariaLabel?: string;
  id?: string;
}

/**
 * Input validation and sanitization
 */
const validatePercentage = (value: number): number => {
  if (typeof value !== 'number' || isNaN(value)) {
    console.warn('ProgressBarAnimation: Invalid percentage value, defaulting to 0');
    return 0;
  }
  if (value < 0) {
    console.warn('ProgressBarAnimation: Negative percentage, clamping to 0');
    return 0;
  }
  if (value > 100) {
    console.warn('ProgressBarAnimation: Percentage over 100, clamping to 100');
    return 100;
  }
  return value;
};

/**
 * Enhanced duration calculation with caching
 */
const durationCache = new Map<number, number>();

const calculateDuration = (percentage: number): number => {
  const validPercentage = Math.round(percentage); // Cache key
  
  if (durationCache.has(validPercentage)) {
    return durationCache.get(validPercentage)!;
  }

  let duration: number;
  if (percentage <= 25) {
    duration = 300 + Math.random() * 100; // 300-400ms
  } else if (percentage <= 50) {
    duration = 500 + Math.random() * 100; // 500-600ms 
  } else if (percentage <= 75) {
    duration = 700 + Math.random() * 100; // 700-800ms
  } else {
    duration = 900 + Math.random() * 300; // 900-1200ms
  }

  durationCache.set(validPercentage, duration);
  return duration;
};

/**
 * Check for matchMedia support with fallback
 */
const supportsMatchMedia = typeof window !== 'undefined' && 'matchMedia' in window;
const prefersReducedMotion = supportsMatchMedia
  ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
  : false;

/**
 * Enhanced Progress Bar Animation Component
 * Addresses performance, accessibility, and robustness issues
 */
const ProgressBarAnimation: React.FC<ProgressBarAnimationProps> = memo(({ 
  percentage, 
  delay = 0, 
  className = "", 
  gradientColors,
  barClassName = "",
  onComplete,
  ariaLabel,
  id
}) => {
  const [progress, setProgress] = useState(0);
  const [animationDuration, setAnimationDuration] = useState(750);
  const mountedRef = useRef(true);
  const timerRef = useRef<NodeJS.Timeout>();
  
  // Validate and sanitize input
  const validPercentage = validatePercentage(percentage);

  const animateProgress = useCallback(() => {
    if (!mountedRef.current) return;

    if (prefersReducedMotion) {
      // Instant animation for accessibility
      setProgress(validPercentage);
      setAnimationDuration(0);
      onComplete?.();
      return;
    }

    // Calculate dynamic duration
    const duration = calculateDuration(validPercentage);
    setAnimationDuration(duration);

    // Start animation
    setProgress(validPercentage);

    // Call onComplete callback after animation finishes
    if (onComplete) {
      timerRef.current = setTimeout(() => {
        if (mountedRef.current) {
          onComplete();
        }
      }, duration + delay);
    }
  }, [validPercentage, delay, onComplete]);

  useEffect(() => {
    mountedRef.current = true;

    // Start animation after delay
    timerRef.current = setTimeout(animateProgress, delay);

    return () => {
      mountedRef.current = false;
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, [animateProgress, delay]);

  useEffect(() => {
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // Generate accessible label
  const accessibleLabel = ariaLabel || `Progress: ${Math.round(progress)}%`;
  
  // Use transform for better performance instead of width transitions
  const progressStyle = {
    transform: `scaleX(${progress / 100})`,
    transformOrigin: 'left center',
    transitionProperty: 'transform',
    transitionDuration: `${animationDuration}ms`,
    transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)', // Enhanced easing
  };

  return (
    <div 
      className={`w-full rounded-full h-2 overflow-hidden bg-black/20 ${className}`}
      role="progressbar"
      aria-valuenow={Math.round(progress)}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={accessibleLabel}
      id={id}
    >
      <div 
        className={`h-full rounded-full shadow-lg ${gradientColors} ${barClassName}`}
        style={progressStyle}
      />
    </div>
  );
});

ProgressBarAnimation.displayName = 'ProgressBarAnimation';

/**
 * USAGE EXAMPLES WITH IMPROVED IMPLEMENTATION
 */

// Example 1: Basic usage with validation
const JaspelProgressExample = () => (
  <ProgressBarAnimation
    percentage={87.5} // Valid percentage - uses 900-1200ms duration
    delay={800}
    className="bg-green-900/30"
    gradientColors="bg-gradient-to-r from-green-400 via-emerald-400 to-yellow-400"
    onComplete={() => console.log('Jaspel animation complete')}
    ariaLabel="Jaspel progress compared to last month"
  />
);

// Example 2: Edge case handling
const EdgeCaseExample = () => (
  <ProgressBarAnimation
    percentage={150} // Will be clamped to 100
    delay={500}
    className="bg-blue-900/30"
    gradientColors="bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400"
    onComplete={() => console.log('Attendance animation complete')}
    ariaLabel="Attendance rate for this month"
  />
);

// Example 3: Error handling
const ErrorHandlingExample = () => (
  <ProgressBarAnimation
    percentage={NaN} // Will default to 0
    delay={300}
    className="bg-red-900/30"
    gradientColors="bg-gradient-to-r from-red-400 via-orange-400 to-yellow-400"
    onComplete={() => console.log('Error case handled gracefully')}
  />
);

/**
 * INTEGRATION WITH HOLISTIC MEDICAL DASHBOARD
 * Drop-in replacement for existing progress bars
 */

export const ImprovedDoctorAnalytics: React.FC = () => {
  return (
    <div className="space-y-4">
      <h4 className="font-semibold text-white mb-4">Recent Achievements</h4>
      
      {/* Jaspel Progress - Improved */}
      <div className="p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30">
        <div className="flex items-center space-x-4 mb-3">
          <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
            <span className="text-white">💰</span>
          </div>
          <div className="flex-1">
            <div className="font-medium text-white">Jaspel vs Bulan Lalu</div>
          </div>
          <div className="text-2xl">🟡</div>
        </div>
        <div className="mb-2">
          <div className="text-right text-white font-semibold text-sm mb-1">+21.5%</div>
          <ProgressBarAnimation
            percentage={87.5}
            delay={800}
            className="bg-green-900/30"
            gradientColors="bg-gradient-to-r from-green-400 via-emerald-400 to-yellow-400"
            ariaLabel="Jaspel increase: 87.5% progress, up 21.5% from last month"
            onComplete={() => console.log('🎉 Jaspel goal achieved!')}
          />
        </div>
      </div>

      {/* Attendance Progress - Improved */}
      <div className="p-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30">
        <div className="flex items-center space-x-4 mb-3">
          <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
            <span className="text-white">📅</span>
          </div>
          <div className="flex-1">
            <div className="font-medium text-white">Tingkat Kehadiran</div>
            <div className="text-blue-300 text-sm">29/30 hari bulan ini</div>
          </div>
          <div className="text-2xl">📅</div>
        </div>
        <div className="mb-2">
          <div className="text-right text-white font-semibold text-sm mb-1">96.7%</div>
          <ProgressBarAnimation
            percentage={96.7}
            delay={500}
            className="bg-blue-900/30"
            gradientColors="bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400"
            ariaLabel="Attendance rate: 96.7%, 29 out of 30 days this month"
            onComplete={() => console.log('🎯 Excellent attendance!')}
          />
        </div>
      </div>
    </div>
  );
};

/**
 * PERFORMANCE TESTING COMPONENT
 * For validating improvements
 */
export const ProgressBarStressTest: React.FC = () => {
  const [testScenarios] = useState([
    { percentage: 0, label: "Zero progress" },
    { percentage: 25, label: "Quarter progress" },
    { percentage: 50.5, label: "Half progress" },
    { percentage: 75.2, label: "Three quarters" },
    { percentage: 87.5, label: "Jaspel current" },
    { percentage: 96.7, label: "Attendance current" },
    { percentage: 100, label: "Complete" },
    { percentage: -10, label: "Negative (clamped)" },
    { percentage: 150, label: "Over 100 (clamped)" },
    { percentage: NaN, label: "NaN (handled)" },
  ]);

  return (
    <div className="space-y-4 p-6">
      <h3 className="text-xl font-bold text-white mb-4">Progress Bar Stress Test</h3>
      {testScenarios.map((scenario, index) => (
        <div key={index} className="space-y-2">
          <div className="text-white text-sm">{scenario.label}: {scenario.percentage}%</div>
          <ProgressBarAnimation
            percentage={scenario.percentage}
            delay={index * 200}
            className="bg-gray-800/50"
            gradientColors="bg-gradient-to-r from-purple-400 via-pink-400 to-red-400"
            ariaLabel={`Test scenario: ${scenario.label}`}
            onComplete={() => console.log(`✅ ${scenario.label} complete`)}
          />
        </div>
      ))}
    </div>
  );
};

export default ProgressBarAnimation;