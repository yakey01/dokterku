import React, { useEffect, useState } from 'react';
import { Calendar, Clock, CheckCircle, XCircle, AlertTriangle, RefreshCw } from 'lucide-react';
import { useAttendanceHistoryUpdates } from '../../../utils/WebSocketManager';

interface AttendanceRecord {
  date: string;
  checkIn: string;
  checkOut: string;
  status: string;
  hours: string;
}

interface AttendanceHistoryCardProps {
  attendanceHistory: AttendanceRecord[];
  isLoading?: boolean;
  maxRecords?: number;
  onRefreshData?: () => Promise<void>; // NEW: Callback to refresh data
}

const AttendanceHistoryCard: React.FC<AttendanceHistoryCardProps> = React.memo(({
  attendanceHistory,
  isLoading = false,
  maxRecords = 7,
  onRefreshData
}) => {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [lastUpdateTime, setLastUpdateTime] = useState<string>('');

  // 🚀 REAL-TIME: Listen for attendance updates via WebSocket
  const wsStatus = useAttendanceHistoryUpdates((data) => {
    console.log('📡 Attendance History: Real-time update received', data);
    
    // Show update notification
    setLastUpdateTime(new Date().toLocaleTimeString('id-ID'));
    
    // Trigger data refresh if callback provided
    if (onRefreshData && !isRefreshing) {
      setIsRefreshing(true);
      onRefreshData()
        .then(() => {
          console.log('✅ Attendance History: Data refreshed successfully');
        })
        .catch((error) => {
          console.error('❌ Attendance History: Failed to refresh data', error);
        })
        .finally(() => {
          setIsRefreshing(false);
        });
    }
  });
  // Status icon mapping
  const getStatusIcon = (status: string) => {
    const statusLower = status.toLowerCase();
    if (statusLower.includes('hadir')) {
      return <CheckCircle className="w-4 h-4 text-green-400" />;
    } else if (statusLower.includes('terlambat')) {
      return <AlertTriangle className="w-4 h-4 text-yellow-400" />;
    } else {
      return <XCircle className="w-4 h-4 text-red-400" />;
    }
  };

  // Status styling
  const getStatusStyles = (status: string) => {
    const statusLower = status.toLowerCase();
    if (statusLower.includes('hadir')) {
      return 'bg-green-500/20 text-green-300 border-green-500/30';
    } else if (statusLower.includes('terlambat')) {
      return 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30';
    } else {
      return 'bg-red-500/20 text-red-300 border-red-500/30';
    }
  };

  // Loading skeleton
  const LoadingSkeleton = React.useMemo(() => (
    <div className="space-y-3">
      {[1, 2, 3, 4, 5].map((i) => (
        <div key={i} className="bg-gray-600/20 rounded-xl p-4 border border-gray-500/30 animate-pulse">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className="w-8 h-8 bg-gray-600/50 rounded-lg"></div>
              <div>
                <div className="h-4 bg-gray-600/50 rounded w-24 mb-2"></div>
                <div className="h-3 bg-gray-600/50 rounded w-16"></div>
              </div>
            </div>
            <div className="text-right">
              <div className="h-4 bg-gray-600/50 rounded w-16 mb-1"></div>
              <div className="h-3 bg-gray-600/50 rounded w-12"></div>
            </div>
          </div>
        </div>
      ))}
    </div>
  ), []);

  // Empty state
  const EmptyState = React.useMemo(() => (
    <div className="text-center py-12 text-gray-400">
      <Calendar className="w-16 h-16 mx-auto mb-4 text-gray-500" />
      <h3 className="text-lg font-semibold mb-2">Belum Ada Data Presensi</h3>
      <p className="text-sm">
        Riwayat presensi akan muncul setelah Anda melakukan check-in
      </p>
    </div>
  ), []);

  // Sort records by date (newest first) and limit
  const sortedRecords = React.useMemo(() => {
    if (!attendanceHistory || attendanceHistory.length === 0) return [];
    
    return [...attendanceHistory]
      .sort((a, b) => {
        // Parse dates for proper sorting
        const dateA = new Date(a.date.split('/').reverse().join('-'));
        const dateB = new Date(b.date.split('/').reverse().join('-'));
        return dateB.getTime() - dateA.getTime();
      })
      .slice(0, maxRecords);
  }, [attendanceHistory, maxRecords]);

  // Calculate summary stats
  const summaryStats = React.useMemo(() => {
    if (!attendanceHistory || attendanceHistory.length === 0) {
      return { totalDays: 0, presentDays: 0, lateDays: 0, absentDays: 0 };
    }

    const stats = attendanceHistory.reduce((acc, record) => {
      const statusLower = record.status.toLowerCase();
      if (statusLower.includes('hadir') && !statusLower.includes('terlambat')) {
        acc.presentDays++;
      } else if (statusLower.includes('terlambat')) {
        acc.lateDays++;
      } else {
        acc.absentDays++;
      }
      return acc;
    }, { presentDays: 0, lateDays: 0, absentDays: 0 });

    return {
      totalDays: attendanceHistory.length,
      ...stats
    };
  }, [attendanceHistory]);

  if (isLoading) {
    return (
      <div className="px-6 mb-8 relative z-10">
        <h3 className="text-xl md:text-2xl font-bold mb-6 text-center bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
          Riwayat Presensi
        </h3>
        <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
          {LoadingSkeleton}
        </div>
      </div>
    );
  }

  return (
    <div className="px-6 mb-8 relative z-10">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-xl md:text-2xl font-bold text-center bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent flex-1">
          Riwayat Presensi
        </h3>
        
        {/* Real-time status indicator */}
        <div className="flex items-center space-x-2 text-xs">
          {wsStatus.connected ? (
            <div className="flex items-center space-x-1 text-green-400">
              <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
              <span>Real-time</span>
            </div>
          ) : (
            <div className="flex items-center space-x-1 text-gray-400">
              <div className="w-2 h-2 bg-gray-400 rounded-full"></div>
              <span>Offline</span>
            </div>
          )}
          
          {lastUpdateTime && (
            <span className="text-gray-400">
              • Update: {lastUpdateTime}
            </span>
          )}
          
          {isRefreshing && (
            <RefreshCw className="w-4 h-4 text-blue-400 animate-spin" />
          )}
        </div>
      </div>
      
      <div className="bg-white/5 backdrop-blur-2xl rounded-3xl p-6 border border-white/10">
        {/* Summary Statistics */}
        {summaryStats.totalDays > 0 && (
          <div className="grid grid-cols-4 gap-4 mb-6 p-4 bg-gray-800/30 rounded-2xl border border-gray-600/30">
            <div className="text-center">
              <div className="text-2xl font-bold text-white">{summaryStats.totalDays}</div>
              <div className="text-xs text-gray-300">Total</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-400">{summaryStats.presentDays}</div>
              <div className="text-xs text-green-300">Hadir</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-yellow-400">{summaryStats.lateDays}</div>
              <div className="text-xs text-yellow-300">Terlambat</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-red-400">{summaryStats.absentDays}</div>
              <div className="text-xs text-red-300">Tidak Hadir</div>
            </div>
          </div>
        )}

        {/* Attendance Records */}
        {sortedRecords.length === 0 ? (
          EmptyState
        ) : (
          <div className="space-y-3">
            {sortedRecords.map((record, index) => (
              <div
                key={`${record.date}-${index}`}
                className="bg-gray-800/30 rounded-xl p-4 border border-gray-600/30 hover:bg-gray-700/30 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    {/* Date */}
                    <div className="flex items-center space-x-2">
                      <Calendar className="w-5 h-5 text-blue-400" />
                      <div>
                        <div className="font-semibold text-white">
                          {record.date}
                        </div>
                        <div className={`inline-flex items-center space-x-1 px-2 py-1 rounded-full text-xs border ${getStatusStyles(record.status)}`}>
                          {getStatusIcon(record.status)}
                          <span>{record.status}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Time and Duration */}
                  <div className="text-right">
                    <div className="flex items-center space-x-2 text-gray-300 text-sm mb-1">
                      <Clock className="w-4 h-4" />
                      <span>{record.checkIn} - {record.checkOut}</span>
                    </div>
                    <div className="text-white font-semibold">
                      {record.hours}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Show more button if there are more records */}
        {attendanceHistory && attendanceHistory.length > maxRecords && (
          <div className="text-center mt-6">
            <button className="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
              Lihat Semua Riwayat ({attendanceHistory.length} total)
            </button>
          </div>
        )}

        {/* Footer info */}
        <div className="text-center mt-6 text-xs text-gray-400">
          💡 Data presensi otomatis tersinkronisasi dari sistem kehadiran
        </div>
      </div>
    </div>
  );
});

AttendanceHistoryCard.displayName = 'AttendanceHistoryCard';

export default AttendanceHistoryCard;