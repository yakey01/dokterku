import React from 'react';

interface LeaderboardDoctor {
  id: number;
  rank: number;
  name: string;
  level: number;
  xp: number;
  attendance_rate: number;
  streak_days: number;
  total_hours: string | number;
  total_days: number;
  total_patients?: number; // ✅ FIXED: Made optional to match backend
  consultation_hours?: number; // ✅ FIXED: Made optional
  procedures_count?: number; // ✅ FIXED: Made optional
  badge?: string; // ✅ FIXED: Made optional
  month?: number; // ✅ FIXED: Made optional
  year?: number; // ✅ FIXED: Made optional
  monthLabel?: string; // ✅ FIXED: Made optional
  // Additional backend fields
  role?: string;
  avatar?: string;
  department?: string;
}

interface LeaderboardPreviewProps {
  leaderboardData: LeaderboardDoctor[];
  isLoading: boolean;
  userData?: {
    name: string;
    email: string;
    role?: string;
  };
}

const LeaderboardPreview: React.FC<LeaderboardPreviewProps> = React.memo(({
  leaderboardData,
  isLoading,
  userData
}) => {
  // Loading skeleton
  const LoadingSkeleton = React.useMemo(() => (
    <>
      {[1, 2, 3].map((i) => (
        <div key={i} className="flex items-center space-x-4 bg-gradient-to-r from-gray-700/30 to-gray-600/30 rounded-2xl p-4 border-2 border-gray-500/30 animate-pulse">
          <div className="w-12 h-12 bg-gray-600/50 rounded-xl"></div>
          <div className="flex-1">
            <div className="h-5 bg-gray-600/50 rounded w-32 mb-2"></div>
            <div className="h-4 bg-gray-600/50 rounded w-24"></div>
          </div>
          <div className="text-right">
            <div className="h-6 bg-gray-600/50 rounded w-20"></div>
          </div>
        </div>
      ))}
    </>
  ), []);

  // Empty state
  const EmptyState = React.useMemo(() => (
    <div className="text-center py-8 text-gray-400">
      <div className="text-4xl mb-3">📊</div>
      <p>No leaderboard data available</p>
      <p className="text-sm mt-1">Check back later for rankings</p>
    </div>
  ), []);

  // Rank colors configuration
  const rankColors = React.useMemo(() => ({
    1: {
      bg: 'from-yellow-500/30 to-amber-500/30',
      border: 'border-yellow-400/50',
      iconBg: 'from-yellow-500 to-amber-500',
      textColor: 'text-yellow-300',
      xpColor: 'text-yellow-400',
      badge: '👑'
    },
    2: {
      bg: 'from-gray-400/30 to-slate-500/30',
      border: 'border-gray-400/50',
      iconBg: 'from-gray-500 to-slate-600',
      textColor: 'text-gray-300',
      xpColor: 'text-gray-400',
      badge: '🥈'
    },
    3: {
      bg: 'from-orange-600/30 to-amber-700/30',
      border: 'border-orange-500/50',
      iconBg: 'from-orange-600 to-amber-700',
      textColor: 'text-orange-300',
      xpColor: 'text-orange-400',
      badge: '🥉'
    }
  }), []);

  // ✅ ENHANCED: Data validation helper
  const validateDoctorData = React.useCallback((doctor: any): doctor is LeaderboardDoctor => {
    return doctor && 
           typeof doctor === 'object' &&
           typeof doctor.id === 'number' &&
           typeof doctor.name === 'string' &&
           doctor.name.trim().length > 0 &&
           typeof doctor.attendance_rate === 'number' &&
           doctor.attendance_rate >= 0;
  }, []);

  // Render leaderboard items
  const LeaderboardItems = React.useMemo(() => {
    if (!leaderboardData || leaderboardData.length === 0) {
      return EmptyState;
    }

    // ✅ ENHANCED: Filter and validate data before rendering
    const validDoctors = leaderboardData.filter(validateDoctorData);
    
    if (validDoctors.length === 0) {
      return (
        <div className="text-center py-8 text-orange-400">
          <div className="text-4xl mb-3">⚠️</div>
          <p>Data leaderboard tidak valid</p>
          <p className="text-sm mt-1">Harap periksa koneksi dan coba lagi</p>
        </div>
      );
    }

    return validDoctors.map((doctor) => {
      const isCurrentUser = doctor.name === userData?.name || 
        (doctor.name && userData?.name && 
         typeof doctor.name === 'string' && 
         typeof userData.name === 'string' && 
         doctor.name.includes(userData.name));
      
      const colors = rankColors[doctor.rank as keyof typeof rankColors] || rankColors[3];
      
      return (
        <div 
          key={doctor.id} 
          className={`flex items-center space-x-4 bg-gradient-to-r ${colors.bg} rounded-2xl p-4 border-2 ${colors.border} ${
            isCurrentUser ? 'ring-2 ring-green-400/50' : ''
          } transition-all duration-300 hover:scale-105`}
        >
          <div className={`w-12 h-12 bg-gradient-to-br ${colors.iconBg} rounded-xl flex items-center justify-center font-bold text-white text-lg`}>
            {doctor.badge || colors.badge}
          </div>
          <div className="flex-1">
            <div className="font-bold text-white flex items-center gap-2">
              {doctor.name}
              {isCurrentUser && (
                <span className="text-xs bg-green-500/30 px-2 py-1 rounded-full text-green-300">
                  You
                </span>
              )}
            </div>
            <div className={colors.textColor}>
              Tingkat Kehadiran • {Math.min(100, Math.max(0, doctor.attendance_rate || 0)).toFixed(1)}% Score
            </div>
            {/* ✅ ENHANCED: Show department if available */}
            {doctor.department && (
              <div className="text-xs text-gray-500 mt-1">
                {doctor.department}
              </div>
            )}
            {doctor.streak_days > 0 && (
              <div className="text-xs text-orange-300 mt-1">
                🔥 {doctor.streak_days} day streak
              </div>
            )}
          </div>
          <div className="text-right">
            <div className={`text-2xl font-bold ${colors.xpColor}`}>
              {/* ✅ FIXED: Properly handle 0 values vs undefined/null */}
              {doctor.total_patients !== undefined && doctor.total_patients !== null && doctor.total_patients > 0 ? 
                `${doctor.total_patients.toLocaleString()} Pasien` :
                `${doctor.xp?.toLocaleString() || '0'} XP`
              }
            </div>
            <div className="text-xs text-gray-400">
              {/* ✅ FIXED: Properly handle 0 procedures vs undefined/null */}
              {doctor.procedures_count !== undefined && doctor.procedures_count !== null && doctor.procedures_count > 0 ? 
                `${doctor.procedures_count} Tindakan` :
                `Tingkat ${doctor.level || 1}`
              }
            </div>
          </div>
        </div>
      );
    });
  }, [leaderboardData, userData, rankColors, EmptyState, validateDoctorData]);

  return (
    <div className="px-6 pb-32 relative z-10">
      <h3 className="text-xl md:text-2xl font-bold mb-3 text-center bg-gradient-to-r from-pink-400 to-purple-400 bg-clip-text text-transparent">
        Elite Doctor Leaderboard
      </h3>
      
      {/* Month Period Indicator */}
      <div className="text-center mb-6">
        <span className="text-xs text-purple-300 bg-purple-900/30 px-3 py-1 rounded-full border border-purple-500/30">
          📅 Periode: {new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
        </span>
        <p className="text-xs text-gray-400 mt-2">
          Data akumulasi bulanan • Reset otomatis setiap awal bulan
        </p>
      </div>
      
      <div className="space-y-4">
        {isLoading ? LoadingSkeleton : LeaderboardItems}
      </div>
    </div>
  );
}, (prevProps, nextProps) => {
  // Custom comparison for optimal re-rendering
  return (
    prevProps.isLoading === nextProps.isLoading &&
    prevProps.leaderboardData.length === nextProps.leaderboardData.length &&
    prevProps.userData?.name === nextProps.userData?.name &&
    // Deep comparison for leaderboard data (only if not loading)
    (!nextProps.isLoading && JSON.stringify(prevProps.leaderboardData) === JSON.stringify(nextProps.leaderboardData))
  );
});

LeaderboardPreview.displayName = 'LeaderboardPreview';

export default LeaderboardPreview;