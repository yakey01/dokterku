import React, { useMemo, useCallback, useState, useEffect } from 'react';
import { Calendar, Trophy, DollarSign, Star } from 'lucide-react';
import { useSearchFilter } from '../../../utils/SearchFilterManager';
import type { FilterConfig } from '../../../utils/SearchFilterManager';
import { useDashboard } from '../providers/DashboardProvider';

interface DashboardSearchableItem {
  id: string;
  type: 'attendance' | 'leaderboard' | 'metric' | 'jaspel';
  title: string;
  subtitle?: string;
  description?: string;
  date?: string;
  status?: string;
  value?: number;
  category?: string;
  tags?: string[];
  searchableText?: string;
  metadata?: Record<string, any>;
}

interface SearchSection {
  id: string;
  title: string;
  icon: string;
  count: number;
  items: DashboardSearchableItem[];
}

export const useDashboardSearch = () => {
  const { state } = useDashboard();
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [searchHistory, setSearchHistory] = useState<Array<{ query: string; timestamp: number; results: number }>>([]);
  
  // Get computed leaderboard data (fallback to empty array if not available)
  const leaderboardData = state.leaderboard || [];

  // Load recent searches from localStorage
  useEffect(() => {
    try {
      const stored = localStorage.getItem('dokterku_recent_searches');
      if (stored) {
        setRecentSearches(JSON.parse(stored));
      }
      
      const historyStored = localStorage.getItem('dokterku_search_history');
      if (historyStored) {
        setSearchHistory(JSON.parse(historyStored));
      }
    } catch (error) {
      console.warn('Failed to load search history:', error);
    }
  }, []);

  // Convert dashboard data to searchable items
  const searchableItems = useMemo(() => {
    const items: DashboardSearchableItem[] = [];

    // Add attendance history items
    state.attendanceHistory.forEach((record, index) => {
      items.push({
        id: `attendance-${index}`,
        type: 'attendance',
        title: `Attendance - ${record.date}`,
        subtitle: `${record.checkIn} - ${record.checkOut}`,
        description: `Status: ${record.status}, Duration: ${record.hours}`,
        date: record.date,
        status: record.status,
        category: 'attendance',
        tags: ['attendance', record.status.toLowerCase().replace(' ', '-')],
        searchableText: `${record.date} ${record.status} ${record.checkIn} ${record.checkOut} ${record.hours} attendance presensi`,
        metadata: record
      });
    });

    // Add leaderboard items
    leaderboardData.forEach((doctor) => {
      items.push({
        id: `leaderboard-${doctor.id}`,
        type: 'leaderboard',
        title: doctor.name,
        subtitle: `Rank #${doctor.rank} - Level ${doctor.level}`,
        description: `${doctor.xp} XP, ${doctor.attendance_rate}% attendance, ${doctor.total_patients} patients`,
        value: doctor.rank,
        status: doctor.rank <= 3 ? 'top' : 'regular',
        category: 'leaderboard',
        tags: ['leaderboard', 'doctor', `level-${doctor.level}`, `rank-${doctor.rank}`],
        searchableText: `${doctor.name} rank ${doctor.rank} level ${doctor.level} ${doctor.xp} xp ${doctor.attendance_rate} attendance ${doctor.total_patients} patients doctor leaderboard`,
        metadata: doctor
      });
    });

    // Add JASPEL metrics
    if (state.metrics.jaspel.currentMonth > 0) {
      items.push({
        id: 'jaspel-current',
        type: 'jaspel',
        title: 'JASPEL Current Month',
        subtitle: `Rp ${state.metrics.jaspel.currentMonth.toLocaleString('id-ID')}`,
        description: `Growth: ${state.metrics.jaspel.growthPercentage}%, Progress: ${state.metrics.jaspel.progressPercentage}%`,
        value: state.metrics.jaspel.currentMonth,
        category: 'jaspel',
        tags: ['jaspel', 'income', 'current-month'],
        searchableText: `jaspel current month ${state.metrics.jaspel.currentMonth} rupiah growth ${state.metrics.jaspel.growthPercentage} progress ${state.metrics.jaspel.progressPercentage} income salary`,
        metadata: state.metrics.jaspel
      });
    }

    if (state.metrics.jaspel.previousMonth > 0) {
      items.push({
        id: 'jaspel-previous',
        type: 'jaspel',
        title: 'JASPEL Previous Month',
        subtitle: `Rp ${state.metrics.jaspel.previousMonth.toLocaleString('id-ID')}`,
        description: 'Previous month performance reference',
        value: state.metrics.jaspel.previousMonth,
        category: 'jaspel',
        tags: ['jaspel', 'income', 'previous-month'],
        searchableText: `jaspel previous month ${state.metrics.jaspel.previousMonth} rupiah income salary last month`,
        metadata: state.metrics.jaspel
      });
    }

    // Add attendance metrics
    items.push({
      id: 'attendance-metrics',
      type: 'metric',
      title: 'Attendance Rate',
      subtitle: `${state.metrics.attendance.rate}% (${state.metrics.attendance.daysPresent}/${state.metrics.attendance.totalDays} days)`,
      description: 'Monthly attendance performance',
      value: state.metrics.attendance.rate,
      category: 'metrics',
      tags: ['attendance', 'metrics', 'performance'],
      searchableText: `attendance rate ${state.metrics.attendance.rate} percent ${state.metrics.attendance.daysPresent} ${state.metrics.attendance.totalDays} days present metrics performance`,
      metadata: state.metrics.attendance
    });

    // Add patient metrics
    if (state.metrics.patients.thisMonth > 0) {
      items.push({
        id: 'patients-metrics',
        type: 'metric',
        title: 'Patients This Month',
        subtitle: `${state.metrics.patients.thisMonth} patients treated`,
        description: 'Monthly patient count performance',
        value: state.metrics.patients.thisMonth,
        category: 'metrics',
        tags: ['patients', 'metrics', 'monthly'],
        searchableText: `patients this month ${state.metrics.patients.thisMonth} treated count metrics performance monthly`,
        metadata: state.metrics.patients
      });
    }

    return items;
  }, [state, leaderboardData]);

  // Define searchable fields
  const searchableFields = [
    'title',
    'subtitle', 
    'description',
    'searchableText',
    'tags'
  ];

  // Define filter configurations
  const filterConfigs: FilterConfig[] = [
    {
      key: 'type',
      label: 'Content Type',
      type: 'select',
      multiple: true,
      options: [
        { value: 'attendance', label: 'Attendance Records' },
        { value: 'leaderboard', label: 'Leaderboard' },
        { value: 'jaspel', label: 'JASPEL Income' },
        { value: 'metric', label: 'Performance Metrics' }
      ]
    },
    {
      key: 'category',
      label: 'Category',
      type: 'select',
      multiple: true
    },
    {
      key: 'status',
      label: 'Status',
      type: 'select',
      multiple: true
    },
    {
      key: 'date',
      label: 'Date Range',
      type: 'date'
    },
    {
      key: 'value',
      label: 'Value Range',
      type: 'range'
    },
    {
      key: 'tags',
      label: 'Tags',
      type: 'tags'
    }
  ];

  // Initialize search hook
  const {
    searchQuery,
    filters,
    sortBy,
    currentPage,
    results,
    isSearching,
    updateSearch,
    updateFilter,
    updateSort,
    setPage,
    clearFilters,
    getFilterValues,
    hasActiveFilters
  } = useSearchFilter(searchableItems, searchableFields, filterConfigs, {
    resultsPerPage: 20,
    sortBy: { key: 'title', direction: 'asc' }
  });

  // Save search to history
  const saveSearchToHistory = useCallback((query: string, resultCount: number) => {
    if (!query.trim()) return;

    // Update recent searches
    const newRecentSearches = [query, ...recentSearches.filter(s => s !== query)].slice(0, 10);
    setRecentSearches(newRecentSearches);
    
    // Update search history
    const newHistoryEntry = {
      query,
      timestamp: Date.now(),
      results: resultCount
    };
    const newHistory = [newHistoryEntry, ...searchHistory.filter(h => h.query !== query)].slice(0, 50);
    setSearchHistory(newHistory);

    // Persist to localStorage
    try {
      localStorage.setItem('dokterku_recent_searches', JSON.stringify(newRecentSearches));
      localStorage.setItem('dokterku_search_history', JSON.stringify(newHistory));
    } catch (error) {
      console.warn('Failed to save search history:', error);
    }
  }, [recentSearches, searchHistory]);

  // Enhanced search function
  const performSearch = useCallback((query: string) => {
    updateSearch(query);
    if (query.trim()) {
      // Debounce the history save
      setTimeout(() => {
        saveSearchToHistory(query, results.totalItems);
      }, 1000);
    }
  }, [updateSearch, saveSearchToHistory, results.totalItems]);

  // Group results by type for better organization
  const groupedResults = useMemo(() => {
    const groups: Record<string, SearchSection> = {};
    
    results.items.forEach(item => {
      if (!groups[item.type]) {
        groups[item.type] = {
          id: item.type,
          title: {
            attendance: 'Attendance Records',
            leaderboard: 'Leaderboard',
            jaspel: 'JASPEL Income',
            metric: 'Performance Metrics'
          }[item.type] || item.type,
          icon: {
            attendance: '📅',
            leaderboard: '🏆',
            jaspel: '💰',
            metric: '📊'
          }[item.type] || '📋',
          count: 0,
          items: []
        };
      }
      
      groups[item.type].items.push(item);
      groups[item.type].count++;
    });

    return Object.values(groups).sort((a, b) => b.count - a.count);
  }, [results.items]);

  // Generate smart suggestions based on current data
  const generateSuggestions = useCallback((query: string) => {
    if (!query.trim()) return [];

    const suggestions = [];
    const normalizedQuery = query.toLowerCase();

    // Add type-based suggestions
    if (normalizedQuery.includes('attend')) {
      suggestions.push({
        text: 'attendance this month',
        type: 'suggestion' as const,
        count: state.attendanceHistory.length
      });
    }

    if (normalizedQuery.includes('jaspel') || normalizedQuery.includes('income')) {
      suggestions.push({
        text: 'JASPEL current month',
        type: 'suggestion' as const,
        count: 1
      });
    }

    if (normalizedQuery.includes('leader') || normalizedQuery.includes('rank')) {
      suggestions.push({
        text: 'leaderboard rankings',
        type: 'suggestion' as const,
        count: leaderboardData.length
      });
    }

    // Add recent successful searches
    searchHistory
      .filter(h => h.results > 0 && h.query.toLowerCase().includes(normalizedQuery))
      .slice(0, 3)
      .forEach(h => {
        suggestions.push({
          text: h.query,
          type: 'recent' as const,
          count: h.results
        });
      });

    return suggestions.slice(0, 8);
  }, [state, searchHistory, leaderboardData]);

  // Quick filter presets
  const quickFilters = useMemo(() => [
    {
      id: 'attendance-recent',
      label: 'Recent Attendance',
      icon: Calendar,
      active: Boolean(filters.type?.includes('attendance')),
      count: state.attendanceHistory.length
    },
    {
      id: 'top-performers',
      label: 'Top Performers',
      icon: Trophy,
      active: Boolean(filters.type?.includes('leaderboard') && filters.status?.includes('top')),
      count: leaderboardData.filter(d => d.rank <= 3).length
    },
    {
      id: 'jaspel-income',
      label: 'JASPEL Income',
      icon: DollarSign,
      active: Boolean(filters.type?.includes('jaspel')),
      count: state.metrics.jaspel.currentMonth > 0 ? 2 : 0
    },
    {
      id: 'high-performance',
      label: 'High Performance',
      icon: Star,
      active: Boolean(filters.value?.min && filters.value.min > 80),
      count: searchableItems.filter(item => (item.value || 0) > 80).length
    }
  ], [filters, state, searchableItems, leaderboardData]);

  // Handle quick filter clicks
  const handleQuickFilter = useCallback((filterId: string) => {
    switch (filterId) {
      case 'attendance-recent':
        updateFilter('type', filters.type?.includes('attendance') ? null : ['attendance']);
        break;
      case 'top-performers':
        updateFilter('type', ['leaderboard']);
        updateFilter('status', ['top']);
        break;
      case 'jaspel-income':
        updateFilter('type', filters.type?.includes('jaspel') ? null : ['jaspel']);
        break;
      case 'high-performance':
        updateFilter('value', filters.value?.min > 80 ? null : { min: 80 });
        break;
    }
  }, [filters, updateFilter]);

  return {
    // Search state
    searchQuery,
    filters,
    sortBy,
    currentPage,
    isSearching,
    hasActiveFilters,
    
    // Results
    results,
    groupedResults,
    
    // Actions
    updateSearch: performSearch,
    updateFilter,
    updateSort,
    setPage,
    clearFilters,
    
    // Suggestions and history
    recentSearches,
    searchHistory,
    generateSuggestions,
    
    // Quick filters
    quickFilters,
    handleQuickFilter,
    
    // Filter configuration
    filterConfigs,
    getFilterValues,
    
    // Metadata
    totalSearchableItems: searchableItems.length,
    searchableFields,
    lastSearchTime: results.searchTime
  };
};

export default useDashboardSearch;