/**
 * ProgressBar Component
 * Reusable animated progress bar that maintains exact visual appearance
 * from current dashboard while providing flexible interface
 */

import React, { useEffect, useState, useRef } from 'react';

interface ProgressBarProps {
  // Core props
  value: number;
  max?: number;
  label?: string;
  showPercentage?: boolean;
  
  // Visual customization (preserves current styles)
  gradient?: string;
  backgroundColor?: string;
  height?: string;
  rounded?: string;
  
  // Animation
  animated?: boolean;
  animationDuration?: number;
  animationDelay?: number;
  pulseAnimation?: boolean;
  
  // Layout
  className?: string;
  labelClassName?: string;
  containerClassName?: string;
  
  // Callbacks
  onComplete?: () => void;
}

/**
 * ProgressBar - Animated progress bar with gaming-style visuals
 * Preserves exact animations and styles from current dashboard
 */
const ProgressBar: React.FC<ProgressBarProps> = ({
  value,
  max = 100,
  label,
  showPercentage = true,
  gradient = 'from-cyan-500 via-purple-500 to-pink-500',
  backgroundColor = 'bg-gray-800/50',
  height = 'h-3',
  rounded = 'rounded-full',
  animated = true,
  animationDuration = 1000,
  animationDelay = 0,
  pulseAnimation = true,
  className = '',
  labelClassName = '',
  containerClassName = '',
  onComplete,
}) => {
  const [currentValue, setCurrentValue] = useState(animated ? 0 : value);
  const [isAnimating, setIsAnimating] = useState(false);
  const animationRef = useRef<number>();
  const startTimeRef = useRef<number>();

  // Calculate percentage
  const percentage = Math.min((currentValue / max) * 100, 100);
  const targetPercentage = Math.min((value / max) * 100, 100);

  // Animate progress bar
  useEffect(() => {
    if (!animated) {
      setCurrentValue(value);
      return;
    }

    // Clear any existing animation
    if (animationRef.current) {
      cancelAnimationFrame(animationRef.current);
    }

    // Start animation after delay
    const startAnimation = () => {
      setIsAnimating(true);
      startTimeRef.current = performance.now();

      const animate = (currentTime: number) => {
        if (!startTimeRef.current) return;

        const elapsed = currentTime - startTimeRef.current;
        const progress = Math.min(elapsed / animationDuration, 1);

        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const newValue = easeOutQuart * value;

        setCurrentValue(newValue);

        if (progress < 1) {
          animationRef.current = requestAnimationFrame(animate);
        } else {
          setIsAnimating(false);
          if (onComplete) {
            onComplete();
          }
        }
      };

      animationRef.current = requestAnimationFrame(animate);
    };

    const timeoutId = setTimeout(startAnimation, animationDelay);

    return () => {
      clearTimeout(timeoutId);
      if (animationRef.current) {
        cancelAnimationFrame(animationRef.current);
      }
    };
  }, [value, max, animated, animationDuration, animationDelay, onComplete]);

  // Get color intensity based on percentage
  const getColorIntensity = () => {
    if (percentage >= 90) return 'shadow-lg shadow-pink-500/50';
    if (percentage >= 70) return 'shadow-md shadow-purple-500/40';
    if (percentage >= 50) return 'shadow-md shadow-cyan-500/30';
    return '';
  };

  return (
    <div className={containerClassName}>
      {/* Label and percentage */}
      {(label || showPercentage) && (
        <div className="flex items-center justify-between mb-2">
          {label && (
            <span className={`text-white font-semibold ${labelClassName}`}>
              {label}
            </span>
          )}
          {showPercentage && (
            <span className="text-cyan-400 font-bold">
              {Math.round(percentage)}%
            </span>
          )}
        </div>
      )}

      {/* Progress bar container */}
      <div className={`${backgroundColor} ${rounded} ${height} relative overflow-hidden ${className}`}>
        {/* Animated progress fill */}
        <div
          className={`
            absolute inset-0 bg-gradient-to-r ${gradient} ${rounded}
            transition-all duration-1000 ease-out
            ${getColorIntensity()}
          `}
          style={{ width: `${percentage}%` }}
        >
          {/* Pulse animation overlay */}
          {pulseAnimation && percentage > 0 && (
            <div className={`absolute inset-0 bg-white/20 ${isAnimating ? 'animate-pulse' : ''}`}></div>
          )}

          {/* Shimmer effect */}
          {percentage > 0 && percentage < 100 && (
            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-shimmer"></div>
          )}
        </div>

        {/* Completion celebration effect */}
        {percentage === 100 && (
          <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-pulse"></div>
        )}
      </div>
    </div>
  );
};

// Preset configurations for different progress bar types
export const ProgressBarPresets = {
  attendance: {
    gradient: 'from-emerald-500 via-green-500 to-teal-500',
    backgroundColor: 'bg-gray-800/50',
  },
  jaspel: {
    gradient: 'from-yellow-500 via-orange-500 to-red-500',
    backgroundColor: 'bg-gray-800/50',
  },
  performance: {
    gradient: 'from-blue-500 via-indigo-500 to-purple-500',
    backgroundColor: 'bg-gray-800/50',
  },
  experience: {
    gradient: 'from-cyan-500 via-purple-500 to-pink-500',
    backgroundColor: 'bg-gray-800/50',
  },
  health: {
    gradient: 'from-red-500 via-pink-500 to-rose-500',
    backgroundColor: 'bg-gray-800/50',
  },
};

// Compound component for progress bar group
interface ProgressBarGroupProps {
  children: React.ReactNode;
  className?: string;
  staggerDelay?: number;
}

export const ProgressBarGroup: React.FC<ProgressBarGroupProps> = ({
  children,
  className = 'space-y-4',
  staggerDelay = 100,
}) => {
  const childrenArray = React.Children.toArray(children);

  return (
    <div className={className}>
      {childrenArray.map((child, index) => {
        if (React.isValidElement(child) && child.type === ProgressBar) {
          return React.cloneElement(child as React.ReactElement<ProgressBarProps>, {
            animationDelay: (child.props.animationDelay || 0) + (index * staggerDelay),
          });
        }
        return child;
      })}
    </div>
  );
};

// Export shimmer animation CSS (to be added to global styles)
export const shimmerAnimation = `
  @keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
  }
  
  .animate-shimmer {
    animation: shimmer 2s infinite;
  }
`;

export default ProgressBar;