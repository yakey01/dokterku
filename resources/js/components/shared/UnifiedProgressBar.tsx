import React, { useState, useEffect } from 'react';

interface UnifiedProgressBarProps {
  percentage: number;
  delay?: number;
  className?: string;
  gradientColors: string;
  barClassName?: string;
  animated?: boolean;
  showPercentage?: boolean;
  size?: 'sm' | 'md' | 'lg';
  ariaLabel?: string;
}

/**
 * UNIFIED Progress Bar Component
 * Ensures consistent visual representation across Dashboard and Presensi components
 */
const UnifiedProgressBar: React.FC<UnifiedProgressBarProps> = ({
  percentage,
  delay = 0,
  className = "",
  gradientColors,
  barClassName = "",
  animated = true,
  showPercentage = true,
  size = 'md',
  ariaLabel
}) => {
  const [width, setWidth] = useState(0);
  const [animationDuration, setAnimationDuration] = useState(750);

  // Size configurations
  const sizeConfig = {
    sm: { height: 'h-1', text: 'text-xs' },
    md: { height: 'h-2', text: 'text-sm' },
    lg: { height: 'h-3', text: 'text-base' }
  };

  const config = sizeConfig[size];

  // Calculate animation duration based on percentage value
  const calculateDuration = (value: number): number => {
    if (value <= 25) return 300 + Math.random() * 100;
    if (value <= 50) return 500 + Math.random() * 100;
    if (value <= 75) return 700 + Math.random() * 100;
    return 900 + Math.random() * 300;
  };

  useEffect(() => {
    // Ensure percentage is within valid range
    const validPercentage = Math.min(Math.max(percentage || 0, 0), 100);
    
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion || !animated) {
      // Instant animation for accessibility or disabled animation
      const timer = setTimeout(() => {
        setWidth(validPercentage);
      }, delay);
      setAnimationDuration(0);
      return () => clearTimeout(timer);
    }

    // Calculate dynamic duration
    const duration = calculateDuration(validPercentage);
    setAnimationDuration(duration);

    // Animate with calculated delay and duration
    const timer = setTimeout(() => {
      setWidth(validPercentage);
    }, delay);

    return () => clearTimeout(timer);
  }, [percentage, delay, animated]);

  const progressBarStyle = {
    width: `${width}%`,
    transitionDuration: `${animationDuration}ms`
  };

  const finalAriaLabel = ariaLabel || `Progress: ${Math.round(width)}%`;

  return (
    <div className="space-y-2">
      {showPercentage && (
        <div className={`flex justify-between items-center ${config.text}`}>
          <span className="text-gray-300">Tingkat Kehadiran</span>
          <span className="text-green-400 font-semibold">{Math.round(width)}%</span>
        </div>
      )}
      
      <div className={`w-full rounded-full ${config.height} overflow-hidden bg-gray-700/50 ${className}`}>
        <div 
          className={`${config.height} rounded-full transition-all ease-out shadow-sm ${gradientColors} ${barClassName}`}
          style={progressBarStyle}
          role="progressbar"
          aria-valuenow={Math.round(width)}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label={finalAriaLabel}
        />
      </div>
      
      {/* Optional accessibility text */}
      <div className="sr-only">
        {finalAriaLabel}
      </div>
    </div>
  );
};

export default UnifiedProgressBar;