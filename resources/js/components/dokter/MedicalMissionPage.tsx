import React, { useState, useEffect } from 'react';
import { Calendar, Clock, MapPin, Users, ArrowLeft, ArrowRight, Activity, Award, Target, TrendingUp } from 'lucide-react';

interface Mission {
  id: number;
  date: string;
  day: string;
  shift: string;
  location: string;
  team: string[];
  status: 'completed' | 'upcoming' | 'active';
  points: number;
  specialTask?: string;
}

const MedicalMissionPage: React.FC = () => {
  const [currentPage, setCurrentPage] = useState(0);
  const [isIPad, setIsIPad] = useState(false);

  useEffect(() => {
    const checkDevice = () => {
      const userAgent = navigator.userAgent.toLowerCase();
      const isIPadUA = /ipad/.test(userAgent) || 
                       (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 0);
      setIsIPad(isIPadUA);
    };
    
    checkDevice();
    window.addEventListener('resize', checkDevice);
    return () => window.removeEventListener('resize', checkDevice);
  }, []);

  const missions: Mission[] = [
    {
      id: 1,
      date: '2024-01-08',
      day: 'Senin',
      shift: 'Pagi (07:00 - 14:00)',
      location: 'Klinik Utama',
      team: ['Dr. Maya', 'Ns. Rina', 'Apt. Budi'],
      status: 'completed',
      points: 150,
      specialTask: 'Vaksinasi Massal'
    },
    {
      id: 2,
      date: '2024-01-09',
      day: 'Selasa',
      shift: 'Siang (14:00 - 21:00)',
      location: 'Klinik Cabang B',
      team: ['Dr. Ahmad', 'Ns. Siti'],
      status: 'completed',
      points: 120
    },
    {
      id: 3,
      date: '2024-01-10',
      day: 'Rabu',
      shift: 'Malam (21:00 - 07:00)',
      location: 'UGD',
      team: ['Dr. Fajar', 'Ns. Dewi', 'Ns. Eko'],
      status: 'active',
      points: 200,
      specialTask: 'Jaga Malam UGD'
    },
    {
      id: 4,
      date: '2024-01-11',
      day: 'Kamis',
      shift: 'Pagi (07:00 - 14:00)',
      location: 'Klinik Utama',
      team: ['Dr. Lisa', 'Ns. Andi'],
      status: 'upcoming',
      points: 150
    },
    {
      id: 5,
      date: '2024-01-12',
      day: 'Jumat',
      shift: 'Siang (14:00 - 21:00)',
      location: 'Poli Spesialis',
      team: ['Dr. Rahman', 'Ns. Fitri'],
      status: 'upcoming',
      points: 180,
      specialTask: 'Konsultasi Spesialis'
    },
    {
      id: 6,
      date: '2024-01-13',
      day: 'Sabtu',
      shift: 'Pagi (07:00 - 14:00)',
      location: 'Klinik Cabang A',
      team: ['Dr. Yanti', 'Ns. Hadi'],
      status: 'upcoming',
      points: 150
    }
  ];

  const itemsPerPage = isIPad ? 4 : 3;
  const totalPages = Math.ceil(missions.length / itemsPerPage);
  const currentMissions = missions.slice(
    currentPage * itemsPerPage,
    (currentPage + 1) * itemsPerPage
  );

  const stats = {
    totalShifts: missions.filter(m => m.status === 'completed').length,
    totalHours: missions.filter(m => m.status === 'completed').length * 7,
    totalPoints: missions.filter(m => m.status === 'completed').reduce((sum, m) => sum + m.points, 0),
    currentStreak: 15
  };

  const getStatusColor = (status: Mission['status']) => {
    switch (status) {
      case 'completed':
        return 'from-green-500 to-emerald-500';
      case 'active':
        return 'from-blue-500 to-cyan-500';
      case 'upcoming':
        return 'from-purple-500 to-pink-500';
    }
  };

  const getStatusEmoji = (status: Mission['status']) => {
    switch (status) {
      case 'completed':
        return '✅';
      case 'active':
        return '🏥';
      case 'upcoming':
        return '📅';
    }
  };

  const handlePrevPage = () => {
    setCurrentPage(prev => Math.max(0, prev - 1));
  };

  const handleNextPage = () => {
    setCurrentPage(prev => Math.min(totalPages - 1, prev + 1));
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white relative">
      <div className="w-full max-w-md md:max-w-4xl lg:max-w-6xl xl:max-w-7xl mx-auto px-4 py-8 pb-24">
        
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl md:text-4xl font-bold bg-gradient-to-r from-cyan-400 to-purple-500 bg-clip-text text-transparent mb-2">
            Medical Missions
          </h1>
          <p className="text-gray-300">Your scheduled shifts and special assignments</p>
        </div>

        {/* Stats Dashboard */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <div className="bg-gradient-to-br from-blue-600/30 to-blue-800/30 backdrop-blur-xl rounded-2xl p-4 border border-blue-400/20">
            <div className="flex items-center justify-between mb-2">
              <Activity className="w-6 h-6 text-blue-400" />
              <span className="text-2xl font-bold text-white">{stats.totalShifts}</span>
            </div>
            <p className="text-blue-300 text-sm">Total Shifts</p>
          </div>
          
          <div className="bg-gradient-to-br from-purple-600/30 to-purple-800/30 backdrop-blur-xl rounded-2xl p-4 border border-purple-400/20">
            <div className="flex items-center justify-between mb-2">
              <Clock className="w-6 h-6 text-purple-400" />
              <span className="text-2xl font-bold text-white">{stats.totalHours}h</span>
            </div>
            <p className="text-purple-300 text-sm">Hours Worked</p>
          </div>
          
          <div className="bg-gradient-to-br from-amber-600/30 to-amber-800/30 backdrop-blur-xl rounded-2xl p-4 border border-amber-400/20">
            <div className="flex items-center justify-between mb-2">
              <Award className="w-6 h-6 text-amber-400" />
              <span className="text-2xl font-bold text-white">{stats.totalPoints}</span>
            </div>
            <p className="text-amber-300 text-sm">Total Points</p>
          </div>
          
          <div className="bg-gradient-to-br from-green-600/30 to-green-800/30 backdrop-blur-xl rounded-2xl p-4 border border-green-400/20">
            <div className="flex items-center justify-between mb-2">
              <TrendingUp className="w-6 h-6 text-green-400" />
              <span className="text-2xl font-bold text-white">{stats.currentStreak}</span>
            </div>
            <p className="text-green-300 text-sm">Day Streak</p>
          </div>
        </div>

        {/* Mission Cards */}
        <div className={`grid grid-cols-1 ${isIPad ? 'md:grid-cols-2' : 'md:grid-cols-1 lg:grid-cols-3'} gap-4 mb-8`}>
          {currentMissions.map((mission) => (
            <div 
              key={mission.id}
              className={`relative bg-gradient-to-br ${getStatusColor(mission.status)} p-[1px] rounded-2xl transform transition-all duration-300 hover:scale-105`}
            >
              <div className="bg-slate-900/90 backdrop-blur-xl rounded-2xl p-6 h-full">
                {/* Status Badge */}
                <div className="absolute top-4 right-4 bg-black/30 backdrop-blur-sm px-3 py-1 rounded-full flex items-center gap-2">
                  <span className="text-2xl">{getStatusEmoji(mission.status)}</span>
                  <span className="text-xs font-medium capitalize">{mission.status}</span>
                </div>

                {/* Date & Day */}
                <div className="mb-4">
                  <div className="flex items-center gap-2 text-white mb-1">
                    <Calendar className="w-5 h-5" />
                    <span className="font-bold text-lg">{mission.day}</span>
                  </div>
                  <p className="text-gray-400 text-sm">{mission.date}</p>
                </div>

                {/* Shift Time */}
                <div className="mb-4">
                  <div className="flex items-center gap-2 text-cyan-400">
                    <Clock className="w-5 h-5" />
                    <span className="font-medium">{mission.shift}</span>
                  </div>
                </div>

                {/* Location */}
                <div className="mb-4">
                  <div className="flex items-center gap-2 text-purple-400">
                    <MapPin className="w-5 h-5" />
                    <span className="font-medium">{mission.location}</span>
                  </div>
                </div>

                {/* Special Task */}
                {mission.specialTask && (
                  <div className="mb-4 bg-gradient-to-r from-amber-500/20 to-orange-500/20 rounded-lg p-3 border border-amber-400/30">
                    <p className="text-amber-300 text-sm font-medium flex items-center gap-2">
                      <Target className="w-4 h-4" />
                      {mission.specialTask}
                    </p>
                  </div>
                )}

                {/* Team */}
                <div className="mb-4">
                  <div className="flex items-center gap-2 text-gray-400 mb-2">
                    <Users className="w-5 h-5" />
                    <span className="text-sm">Team Members</span>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {mission.team.map((member, idx) => (
                      <span 
                        key={idx}
                        className="bg-slate-800/50 backdrop-blur-sm px-3 py-1 rounded-full text-xs text-gray-300 border border-slate-700/50"
                      >
                        {member}
                      </span>
                    ))}
                  </div>
                </div>

                {/* Points */}
                <div className="flex items-center justify-between pt-4 border-t border-slate-700/50">
                  <span className="text-gray-400 text-sm">Mission Points</span>
                  <span className="text-2xl font-bold bg-gradient-to-r from-yellow-400 to-amber-500 bg-clip-text text-transparent">
                    +{mission.points}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-center gap-4">
            <button
              onClick={handlePrevPage}
              disabled={currentPage === 0}
              className={`p-3 rounded-xl transition-all duration-300 ${
                currentPage === 0 
                  ? 'bg-gray-800/50 text-gray-600 cursor-not-allowed' 
                  : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white transform hover:scale-110'
              }`}
            >
              <ArrowLeft className="w-5 h-5" />
            </button>
            
            <div className="flex gap-2">
              {Array.from({ length: totalPages }, (_, i) => (
                <button
                  key={i}
                  onClick={() => setCurrentPage(i)}
                  className={`w-10 h-10 rounded-full transition-all duration-300 ${
                    currentPage === i
                      ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white font-bold'
                      : 'bg-gray-800/50 text-gray-400 hover:bg-gray-700/50'
                  }`}
                >
                  {i + 1}
                </button>
              ))}
            </div>
            
            <button
              onClick={handleNextPage}
              disabled={currentPage === totalPages - 1}
              className={`p-3 rounded-xl transition-all duration-300 ${
                currentPage === totalPages - 1
                  ? 'bg-gray-800/50 text-gray-600 cursor-not-allowed' 
                  : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white transform hover:scale-110'
              }`}
            >
              <ArrowRight className="w-5 h-5" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default MedicalMissionPage;