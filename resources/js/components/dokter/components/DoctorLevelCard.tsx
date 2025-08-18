import React from 'react';
import { Crown, HeartCrack, Flame, Star, Award, Sun, Moon } from 'lucide-react';

interface DoctorLevelCardProps {
  // User info
  userData?: {
    name: string;
    email: string;
    role?: string;
  };
  
  // Level and XP
  doctorLevel: number;
  experiencePoints: number;
  dailyStreak: number;
  
  // Metrics
  attendanceRate: number;
  attendanceDisplayText: string;
  patientsThisMonth: number;
  
  // Time-based greeting
  greeting: string;
  timeIcon: typeof Sun | typeof Moon;
  colorGradient: string;
  
  // Loading states
  isLoading?: boolean;
}

const DoctorLevelCard: React.FC<DoctorLevelCardProps> = React.memo(({
  userData,
  doctorLevel,
  experiencePoints,
  dailyStreak,
  attendanceRate,
  attendanceDisplayText,
  patientsThisMonth,
  greeting,
  timeIcon: TimeIcon,
  colorGradient,
  isLoading = false
}) => {
  // Loading skeleton
  if (isLoading) {
    return (
      <div className="px-6 pt-8 pb-6 relative z-10">
        <div className="relative">
          <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
          <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
          <div className="relative p-8">
            {/* Skeleton content */}
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center space-x-4">
                <div className="w-20 h-20 bg-gray-600/50 rounded-2xl animate-pulse"></div>
                <div>
                  <div className="h-8 bg-gray-600/50 rounded w-48 mb-2 animate-pulse"></div>
                  <div className="h-6 bg-gray-600/50 rounded w-32 animate-pulse"></div>
                </div>
              </div>
            </div>
            <div className="mb-6">
              <div className="h-4 bg-gray-600/50 rounded w-full mb-2 animate-pulse"></div>
            </div>
            <div className="grid grid-cols-3 gap-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="text-center">
                  <div className="h-8 bg-gray-600/50 rounded mb-2 animate-pulse"></div>
                  <div className="h-4 bg-gray-600/50 rounded animate-pulse"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="px-6 pt-8 pb-6 relative z-10">
      <div className="relative">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-purple-600/30 to-pink-600/30 rounded-3xl backdrop-blur-2xl"></div>
        <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
        <div className="relative p-8">
          
          {/* Level Badge & Avatar */}
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center space-x-4">
              <div className="relative">
                <div className="w-20 h-20 bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 rounded-2xl flex items-center justify-center relative overflow-hidden">
                  <div className="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                  {/* Dynamic icon based on attendance rate */}
                  {attendanceRate >= 100 ? (
                    <Crown className="w-10 h-10 text-white relative z-10 animate-bounce" />
                  ) : (
                    <HeartCrack className="w-10 h-10 text-white relative z-10 animate-bounce" />
                  )}
                </div>
                <div className="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-black text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-lg animate-pulse">
                  Lv.{doctorLevel}
                </div>
              </div>
              <div>
                <h1 className={`text-2xl md:text-3xl lg:text-4xl font-bold bg-gradient-to-r ${colorGradient} bg-clip-text text-transparent mb-1 flex items-center gap-3`}>
                  <TimeIcon className="w-8 h-8 text-yellow-400 drop-shadow-lg" />
                  {greeting.split(' ').slice(0, 2).join(' ')}
                  <Crown className="w-8 h-8 text-purple-400 drop-shadow-lg" />
                </h1>
                <p className="text-purple-200 text-lg md:text-xl">{userData?.name || 'Doctor'}</p>
              </div>
            </div>
          </div>

          {/* Clinic Info */}
          <div className="mb-6">
            <div className="flex justify-between text-sm mb-2">
              <span className="text-cyan-300">Klinik Dokterku</span>
              <span className="text-white font-semibold">Akreditasi Paripurna</span>
            </div>
          </div>

          {/* Daily Stats */}
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center">
              <div className="flex items-center justify-center mb-2">
                <Flame className="w-5 h-5 text-orange-400 mr-2" />
                <span className="text-xl font-bold text-white">{dailyStreak}</span>
              </div>
              <span className="text-orange-300 text-sm">Jumlah Jaga</span>
            </div>
            
            <div className="text-center">
              <div className="flex items-center justify-center mb-2">
                <Star className="w-5 h-5 text-yellow-400 mr-2" />
                <span className="text-xl font-bold text-white">
                  {attendanceDisplayText}
                </span>
              </div>
              <span className="text-yellow-300 text-sm">Tingkat Kehadiran</span>
            </div>
            
            <div className="text-center">
              <div className="flex items-center justify-center mb-2">
                <Award className="w-5 h-5 text-purple-400 mr-2" />
                <span className="text-xl font-bold text-white">
                  {patientsThisMonth}
                </span>
              </div>
              <span className="text-purple-300 text-sm">Jumlah Pasien</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}, (prevProps, nextProps) => {
  // Custom comparison for optimal re-rendering
  return (
    prevProps.doctorLevel === nextProps.doctorLevel &&
    prevProps.experiencePoints === nextProps.experiencePoints &&
    prevProps.dailyStreak === nextProps.dailyStreak &&
    prevProps.attendanceRate === nextProps.attendanceRate &&
    prevProps.attendanceDisplayText === nextProps.attendanceDisplayText &&
    prevProps.patientsThisMonth === nextProps.patientsThisMonth &&
    prevProps.greeting === nextProps.greeting &&
    prevProps.colorGradient === nextProps.colorGradient &&
    prevProps.isLoading === nextProps.isLoading &&
    prevProps.userData?.name === nextProps.userData?.name
  );
});

DoctorLevelCard.displayName = 'DoctorLevelCard';

export default DoctorLevelCard;