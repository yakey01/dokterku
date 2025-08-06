import React, { useState, useEffect } from 'react';
import { Activity, Heart, TrendingUp, Award, Clock, CheckCircle } from 'lucide-react';
import { 
  DynamicProgressBar, 
  AttendanceProgressBar, 
  JaspelProgressBar, 
  PerformanceProgressBar,
  useProgressBar 
} from './DynamicProgressBar';

interface MedicalProgressDashboardProps {
  attendanceRate?: number;
  currentJaspel?: number;
  targetJaspel?: number;
  patientSatisfaction?: number;
  procedureSuccess?: number;
  onProgressComplete?: (metric: string) => void;
}

export const MedicalProgressDashboard: React.FC<MedicalProgressDashboardProps> = ({
  attendanceRate = 96.7,
  currentJaspel = 2847000,
  targetJaspel = 3000000,
  patientSatisfaction = 94.2,
  procedureSuccess = 98.1,
  onProgressComplete
}) => {
  const [completedAnimations, setCompletedAnimations] = useState<Set<string>>(new Set());
  const [showCelebration, setShowCelebration] = useState(false);

  const handleAnimationComplete = (metric: string) => {
    setCompletedAnimations(prev => new Set([...prev, metric]));
    onProgressComplete?.(metric);
    
    // Show celebration for high performance metrics
    if ((metric === 'attendance' && attendanceRate > 95) ||
        (metric === 'satisfaction' && patientSatisfaction > 90) ||
        (metric === 'success' && procedureSuccess > 95)) {
      setShowCelebration(true);
      setTimeout(() => setShowCelebration(false), 3000);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header with celebration */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">
          Medical Performance Dashboard
        </h2>
        {showCelebration && (
          <div className="flex items-center space-x-2 text-yellow-400 animate-bounce-gentle">
            <Award className="w-6 h-6" />
            <span className="text-sm font-semibold">Excellent Performance!</span>
          </div>
        )}
      </div>

      {/* Primary Metrics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {/* Attendance Progress Card */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
              <Clock className="w-6 h-6 text-white" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-white">Attendance Rate</h3>
              <p className="text-blue-300 text-sm">Monthly Performance</p>
            </div>
            {completedAnimations.has('attendance') && (
              <CheckCircle className="w-5 h-5 text-green-400 animate-pulse" />
            )}
          </div>
          
          <AttendanceProgressBar
            attendanceRate={attendanceRate}
            delay={500}
          />
          
          <div className="mt-3 text-sm text-gray-300">
            Target: 95% • Current: {attendanceRate}%
          </div>
        </div>

        {/* Patient Satisfaction Card */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl flex items-center justify-center">
              <Heart className="w-6 h-6 text-white" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-white">Patient Satisfaction</h3>
              <p className="text-pink-300 text-sm">Feedback Score</p>
            </div>
            {completedAnimations.has('satisfaction') && (
              <CheckCircle className="w-5 h-5 text-green-400 animate-pulse" />
            )}
          </div>
          
          <PerformanceProgressBar
            performance={patientSatisfaction}
            label="Satisfaction Score"
            delay={800}
            variant="info"
          />
          
          <div className="mt-3 text-sm text-gray-300">
            Excellent rating above 90%
          </div>
        </div>
      </div>

      {/* Secondary Metrics */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* Jaspel Progress */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
              <TrendingUp className="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 className="font-semibold text-white">Jaspel Progress</h4>
              <p className="text-green-300 text-xs">Monthly Target</p>
            </div>
          </div>
          
          <JaspelProgressBar
            currentJaspel={currentJaspel}
            targetJaspel={targetJaspel}
            delay={1100}
          />
        </div>

        {/* Procedure Success Rate */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center">
              <Activity className="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 className="font-semibold text-white">Success Rate</h4>
              <p className="text-emerald-300 text-xs">Procedures</p>
            </div>
          </div>
          
          <PerformanceProgressBar
            performance={procedureSuccess}
            label="Procedure Success"
            delay={1400}
            variant="success"
          />
        </div>

        {/* Custom Complex Progress Example */}
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
              <Award className="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 className="font-semibold text-white">Overall Score</h4>
              <p className="text-yellow-300 text-xs">Composite Rating</p>
            </div>
          </div>
          
          <CustomCompositeProgress
            metrics={[
              { value: attendanceRate, weight: 0.3 },
              { value: patientSatisfaction, weight: 0.4 },
              { value: procedureSuccess, weight: 0.3 }
            ]}
            delay={1700}
            onComplete={() => handleAnimationComplete('composite')}
          />
        </div>
      </div>

      {/* Animation Status Indicators (Dev Mode) */}
      {process.env.NODE_ENV === 'development' && (
        <div className="bg-gray-800/50 rounded-lg p-4 border border-gray-600">
          <h4 className="text-sm font-semibold text-gray-300 mb-2">Animation Status</h4>
          <div className="flex flex-wrap gap-2">
            {['attendance', 'satisfaction', 'jaspel', 'success', 'composite'].map(metric => (
              <div
                key={metric}
                className={`px-2 py-1 rounded text-xs ${
                  completedAnimations.has(metric)
                    ? 'bg-green-500/20 text-green-300'
                    : 'bg-yellow-500/20 text-yellow-300'
                }`}
              >
                {metric}: {completedAnimations.has(metric) ? 'Complete' : 'Animating'}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

// Custom Composite Progress Component
const CustomCompositeProgress: React.FC<{
  metrics: Array<{ value: number; weight: number }>;
  delay?: number;
  onComplete?: () => void;
}> = ({ metrics, delay = 0, onComplete }) => {
  const compositeScore = metrics.reduce((sum, metric) => sum + (metric.value * metric.weight), 0);
  
  const {
    percentage,
    hasCompleted,
    start,
    dynamicDuration
  } = useProgressBar(compositeScore, 100, delay);

  useEffect(() => {
    start();
  }, [start]);

  useEffect(() => {
    if (hasCompleted && onComplete) {
      onComplete();
    }
  }, [hasCompleted, onComplete]);

  return (
    <div className="space-y-3">
      <div className="flex justify-between text-sm">
        <span className="text-gray-300">Composite Score</span>
        <span className="text-white font-semibold">{compositeScore.toFixed(1)}%</span>
      </div>
      
      <div className="w-full bg-gray-700/50 rounded-full h-3 overflow-hidden">
        <div 
          className="bg-gradient-to-r from-yellow-400 via-orange-400 to-red-400 h-3 rounded-full transition-all shadow-lg relative overflow-hidden"
          style={{
            width: `${percentage}%`,
            transition: `width ${dynamicDuration.duration}ms ${dynamicDuration.easing}`,
          }}
        >
          {/* Special effects for excellent composite scores */}
          {compositeScore > 95 && (
            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer"></div>
          )}
        </div>
      </div>
      
      <div className="text-xs text-gray-400">
        Weighted average of all performance metrics
      </div>
    </div>
  );
};

export default MedicalProgressDashboard;