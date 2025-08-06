import React, { useState, useEffect, useCallback, useRef } from 'react';

// Types
interface ProgressBarProps {
  value: number;
  maxValue?: number;
  label?: string;
  className?: string;
  gradientFrom: string;
  gradientTo: string;
  gradientVia?: string;
  showPercentage?: boolean;
  showValue?: boolean;
  accessibilityLabel?: string;
  onAnimationComplete?: () => void;
  delay?: number;
}

interface DynamicDuration {
  duration: number;
  easing: string;
  stagger: number;
}

// Dynamic duration calculation algorithm
const calculateDynamicDuration = (percentage: number): DynamicDuration => {
  // Professional UX patterns for medical dashboards
  // Higher percentages = more impactful, slower animations
  // Lower percentages = immediate feedback, faster animations
  
  if (percentage <= 25) {
    return {
      duration: Math.max(300, 200 + (percentage * 8)), // 300-400ms
      easing: 'cubic-bezier(0.4, 0, 0.2, 1)', // ease-out for quick feedback
      stagger: 50
    };
  } else if (percentage <= 50) {
    return {
      duration: Math.max(500, 400 + (percentage * 4)), // 500-600ms
      easing: 'cubic-bezier(0.25, 0.1, 0.25, 1)', // ease for balanced feel
      stagger: 100
    };
  } else if (percentage <= 75) {
    return {
      duration: Math.max(700, 600 + (percentage * 2.67)), // 700-800ms
      easing: 'cubic-bezier(0.165, 0.84, 0.44, 1)', // ease-out-quart for satisfaction
      stagger: 150
    };
  } else {
    // 75-100%: Impactful, celebratory animations
    const impactFactor = Math.min(1.5, 1 + ((percentage - 75) * 0.02)); // Scale impact
    return {
      duration: Math.max(900, 800 + (percentage * 4) * impactFactor), // 900-1200ms
      easing: 'cubic-bezier(0.19, 1, 0.22, 1)', // ease-out-expo for impact
      stagger: 200
    };
  }
};

// Enhanced progress bar hook
export const useProgressBar = (targetValue: number, maxValue = 100, delay = 0) => {
  const [currentValue, setCurrentValue] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);
  const [hasCompleted, setHasCompleted] = useState(false);
  const animationRef = useRef<number>();
  const timeoutRef = useRef<NodeJS.Timeout>();

  const percentage = Math.min((currentValue / maxValue) * 100, 100);
  const targetPercentage = Math.min((targetValue / maxValue) * 100, 100);
  
  const animate = useCallback(() => {
    if (currentValue >= targetValue) {
      setIsAnimating(false);
      setHasCompleted(true);
      return;
    }

    const { duration } = calculateDynamicDuration(targetPercentage);
    const startTime = Date.now();
    const startValue = currentValue;
    const deltaValue = targetValue - startValue;

    const step = () => {
      const elapsed = Date.now() - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Enhanced easing function for medical dashboard feel
      const easeOutExpo = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      const newValue = startValue + (deltaValue * easeOutExpo);
      
      setCurrentValue(newValue);

      if (progress < 1) {
        animationRef.current = requestAnimationFrame(step);
      } else {
        setIsAnimating(false);
        setHasCompleted(true);
      }
    };

    setIsAnimating(true);
    animationRef.current = requestAnimationFrame(step);
  }, [currentValue, targetValue, targetPercentage]);

  const start = useCallback(() => {
    if (delay > 0) {
      timeoutRef.current = setTimeout(() => {
        animate();
      }, delay);
    } else {
      animate();
    }
  }, [animate, delay]);

  const reset = useCallback(() => {
    if (animationRef.current) {
      cancelAnimationFrame(animationRef.current);
    }
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }
    setCurrentValue(0);
    setIsAnimating(false);
    setHasCompleted(false);
  }, []);

  useEffect(() => {
    return () => {
      if (animationRef.current) {
        cancelAnimationFrame(animationRef.current);
      }
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  return {
    currentValue,
    percentage,
    targetPercentage,
    isAnimating,
    hasCompleted,
    start,
    reset,
    dynamicDuration: calculateDynamicDuration(targetPercentage)
  };
};

// Enhanced Progress Bar Component
export const DynamicProgressBar: React.FC<ProgressBarProps> = ({
  value,
  maxValue = 100,
  label,
  className = "",
  gradientFrom,
  gradientTo,
  gradientVia,
  showPercentage = false,
  showValue = false,
  accessibilityLabel,
  onAnimationComplete,
  delay = 0
}) => {
  const {
    currentValue,
    percentage,
    targetPercentage,
    isAnimating,
    hasCompleted,
    start,
    dynamicDuration
  } = useProgressBar(value, maxValue, delay);

  useEffect(() => {
    start();
  }, [start]);

  useEffect(() => {
    if (hasCompleted && onAnimationComplete) {
      onAnimationComplete();
    }
  }, [hasCompleted, onAnimationComplete]);

  const gradientClass = gradientVia 
    ? `from-${gradientFrom} via-${gradientVia} to-${gradientTo}`
    : `from-${gradientFrom} to-${gradientTo}`;

  return (
    <div className={`w-full ${className}`}>
      {/* Label */}
      {label && (
        <div className="flex justify-between text-sm mb-2">
          <span className="text-gray-300">{label}</span>
          {(showPercentage || showValue) && (
            <span className="text-white font-semibold">
              {showPercentage && `${targetPercentage.toFixed(1)}%`}
              {showPercentage && showValue && " • "}
              {showValue && `${value}/${maxValue}`}
            </span>
          )}
        </div>
      )}
      
      {/* Progress Track */}
      <div className="relative w-full bg-gray-700/50 rounded-full h-3 overflow-hidden">
        {/* Background glow effect for high percentages */}
        {targetPercentage > 80 && (
          <div className="absolute inset-0 bg-gradient-to-r from-transparent to-yellow-400/20 rounded-full animate-pulse"></div>
        )}
        
        {/* Progress Fill */}
        <div 
          className={`bg-gradient-to-r ${gradientClass} h-3 rounded-full shadow-lg relative overflow-hidden`}
          style={{
            width: `${percentage}%`,
            transition: `width ${dynamicDuration.duration}ms ${dynamicDuration.easing}`,
          }}
          role="progressbar"
          aria-valuenow={Math.round(currentValue)}
          aria-valuemin={0}
          aria-valuemax={maxValue}
          aria-label={accessibilityLabel || `${label || 'Progress'}: ${Math.round(percentage)}%`}
        >
          {/* Shimmer effect for active animation */}
          {isAnimating && (
            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
          )}
          
          {/* Celebration sparkles for high values */}
          {hasCompleted && targetPercentage > 90 && (
            <div className="absolute inset-0 overflow-hidden">
              <div className="absolute top-0 left-1/4 w-1 h-1 bg-white rounded-full animate-ping"></div>
              <div className="absolute top-1 right-1/3 w-0.5 h-0.5 bg-white rounded-full animate-pulse"></div>
              <div className="absolute bottom-0 right-1/4 w-1 h-1 bg-white rounded-full animate-ping animation-delay-300"></div>
            </div>
          )}
        </div>
      </div>
      
      {/* Performance indicator for development */}
      {process.env.NODE_ENV === 'development' && (
        <div className="text-xs text-gray-500 mt-1">
          Duration: {dynamicDuration.duration}ms • Easing: {dynamicDuration.easing.split('(')[0]}
        </div>
      )}
    </div>
  );
};

// Medical Dashboard Specific Progress Bars
export const AttendanceProgressBar: React.FC<{
  attendanceRate: number;
  label?: string;
  delay?: number;
}> = ({ attendanceRate, label = "Attendance Rate", delay = 0 }) => (
  <DynamicProgressBar
    value={attendanceRate}
    maxValue={100}
    label={label}
    gradientFrom="blue-400"
    gradientVia="cyan-400"
    gradientTo="emerald-400"
    showPercentage={true}
    accessibilityLabel={`Medical attendance rate: ${attendanceRate}%`}
    delay={delay}
    className="mb-4"
  />
);

export const JaspelProgressBar: React.FC<{
  currentJaspel: number;
  targetJaspel: number;
  label?: string;
  delay?: number;
}> = ({ currentJaspel, targetJaspel, label = "Jaspel Progress", delay = 0 }) => (
  <DynamicProgressBar
    value={currentJaspel}
    maxValue={targetJaspel}
    label={label}
    gradientFrom="green-400"
    gradientVia="emerald-400"
    gradientTo="yellow-400"
    showPercentage={true}
    showValue={true}
    accessibilityLabel={`Jaspel progress: ${currentJaspel} out of ${targetJaspel}`}
    delay={delay}
    className="mb-4"
  />
);

export const PerformanceProgressBar: React.FC<{
  performance: number;
  label?: string;
  delay?: number;
  variant?: 'success' | 'warning' | 'info';
}> = ({ performance, label = "Performance", delay = 0, variant = 'success' }) => {
  const gradientConfig = {
    success: { from: 'green-400', via: 'emerald-400', to: 'teal-400' },
    warning: { from: 'yellow-400', via: 'amber-400', to: 'orange-400' },
    info: { from: 'blue-400', via: 'indigo-400', to: 'purple-400' }
  };

  const config = gradientConfig[variant];

  return (
    <DynamicProgressBar
      value={performance}
      maxValue={100}
      label={label}
      gradientFrom={config.from}
      gradientVia={config.via}
      gradientTo={config.to}
      showPercentage={true}
      accessibilityLabel={`${label}: ${performance}%`}
      delay={delay}
      className="mb-4"
    />
  );
};

export default DynamicProgressBar;