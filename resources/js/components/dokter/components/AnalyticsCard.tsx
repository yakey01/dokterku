import React from 'react';
import { DollarSign, Calendar } from 'lucide-react';

interface ProgressBarAnimationProps {
  percentage: number;
  delay?: number;
  className?: string;
  gradientColors: string;
  barClassName?: string;
}

/**
 * Calculate animation duration based on percentage value
 * Higher percentages get longer durations for better visual impact
 */
const calculateDuration = (percentage: number): number => {
  if (percentage <= 25) return 300 + Math.random() * 100; // 300-400ms
  if (percentage <= 50) return 500 + Math.random() * 100; // 500-600ms 
  if (percentage <= 75) return 700 + Math.random() * 100; // 700-800ms
  return 900 + Math.random() * 300; // 900-1200ms
};

/**
 * Progress Bar Animation Component with dynamic duration and accessibility
 */
const ProgressBarAnimation: React.FC<ProgressBarAnimationProps> = React.memo(({ 
  percentage, 
  delay = 0, 
  className = "", 
  gradientColors,
  barClassName = "" 
}) => {
  const [width, setWidth] = React.useState(0);
  const [animationDuration, setAnimationDuration] = React.useState(750);

  React.useEffect(() => {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion) {
      // Instant animation for accessibility
      const timer = setTimeout(() => {
        setWidth(percentage);
      }, delay);
      setAnimationDuration(0);
      return () => clearTimeout(timer);
    }

    // Calculate dynamic duration
    const duration = calculateDuration(percentage);
    setAnimationDuration(duration);

    // Animate with calculated delay and duration
    const timer = setTimeout(() => {
      setWidth(percentage);
    }, delay);

    return () => clearTimeout(timer);
  }, [percentage, delay]);

  const progressBarStyle = {
    width: `${width}%`,
    transitionDuration: `${animationDuration}ms`
  };

  return (
    <div className={`w-full rounded-full h-2 overflow-hidden ${className}`}>
      <div 
        className={`h-2 rounded-full transition-all ease-out shadow-lg ${gradientColors} ${barClassName}`}
        style={progressBarStyle}
        role="progressbar"
        aria-valuenow={Math.round(width)}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label={`Progress: ${Math.round(width)}%`}
      ></div>
    </div>
  );
});

ProgressBarAnimation.displayName = 'ProgressBarAnimation';

interface AnalyticsCardProps {
  // JASPEL metrics
  jaspelGrowthPercentage: number;
  jaspelProgressPercentage: number;
  
  // Attendance metrics
  attendanceRate: number;
  attendanceDisplayText: string;
  
  // Loading state
  isLoading?: boolean;
}

const AnalyticsCard: React.FC<AnalyticsCardProps> = React.memo(({
  jaspelGrowthPercentage,
  jaspelProgressPercentage,
  attendanceRate,
  attendanceDisplayText,
  isLoading = false
}) => {
  // Loading skeleton
  if (isLoading) {
    return (
      <div className="px-6 mb-8 relative z-10">
        <div className="h-6 bg-gray-600/50 rounded w-48 mb-6 mx-auto animate-pulse"></div>
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="space-y-4">
            <div className="h-4 bg-gray-600/50 rounded w-32 mb-4 animate-pulse"></div>
            {[1, 2].map((i) => (
              <div key={i} className="p-4 bg-gray-600/20 rounded-2xl border border-gray-500/30">
                <div className="flex items-center space-x-4 mb-3">
                  <div className="w-10 h-10 bg-gray-600/50 rounded-xl animate-pulse"></div>
                  <div className="flex-1">
                    <div className="h-4 bg-gray-600/50 rounded w-32 animate-pulse"></div>
                  </div>
                  <div className="w-8 h-8 bg-gray-600/50 rounded animate-pulse"></div>
                </div>
                <div className="mb-2">
                  <div className="h-3 bg-gray-600/50 rounded w-16 mb-1 animate-pulse"></div>
                  <div className="h-2 bg-gray-600/50 rounded w-full animate-pulse"></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="px-6 mb-8 relative z-10">
      <h3 className="text-xl md:text-2xl font-bold mb-6 text-center bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
        Doctor Analytics
      </h3>
      
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        {/* Achievement Timeline */}
        <div className="space-y-4">
          <h4 className="font-semibold text-white mb-4">Recent Achievements</h4>
          
          {/* JASPEL Achievement */}
          <div className="p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-2xl border border-green-400/30">
            <div className="flex items-center space-x-4 mb-3">
              <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                <DollarSign className="w-5 h-5 text-white" />
              </div>
              <div className="flex-1">
                <div className="font-medium text-white">Jaspel vs Bulan Lalu</div>
              </div>
              <div className="text-2xl">🟡</div>
            </div>
            <div className="mb-2">
              <div className="text-right text-white font-semibold text-sm mb-1">
                {jaspelGrowthPercentage >= 0 
                  ? `+${jaspelGrowthPercentage}%`
                  : `${jaspelGrowthPercentage}%`
                }
              </div>
              <ProgressBarAnimation
                percentage={jaspelProgressPercentage}
                delay={200}
                className="bg-green-900/30"
                gradientColors="bg-gradient-to-r from-green-400 via-emerald-400 to-yellow-400"
              />
            </div>
          </div>

          {/* Attendance Achievement */}
          <div className="p-4 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-2xl border border-blue-400/30">
            <div className="flex items-center space-x-4 mb-3">
              <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                <Calendar className="w-5 h-5 text-white" />
              </div>
              <div className="flex-1">
                <div className="font-medium text-white">Tingkat Kehadiran</div>
              </div>
              <div className="text-2xl">📅</div>
            </div>
            <div className="mb-2">
              <div className="text-right text-white font-semibold text-sm mb-1">
                {attendanceDisplayText}
              </div>
              <ProgressBarAnimation
                percentage={attendanceRate}
                delay={100}
                className="bg-blue-900/30"
                gradientColors="bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}, (prevProps, nextProps) => {
  // Custom comparison for optimal re-rendering
  return (
    prevProps.jaspelGrowthPercentage === nextProps.jaspelGrowthPercentage &&
    prevProps.jaspelProgressPercentage === nextProps.jaspelProgressPercentage &&
    prevProps.attendanceRate === nextProps.attendanceRate &&
    prevProps.attendanceDisplayText === nextProps.attendanceDisplayText &&
    prevProps.isLoading === nextProps.isLoading
  );
});

AnalyticsCard.displayName = 'AnalyticsCard';

export default AnalyticsCard;