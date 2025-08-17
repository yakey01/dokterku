import React, { Suspense, useEffect, useState } from 'react';
import { Sun, Moon, Search, X } from 'lucide-react';
import { DashboardProvider } from './providers/DashboardProvider';
import { useDashboardData } from './hooks/useDashboardData';
import { useRealtimeDashboard } from './hooks/useRealtimeDashboard';
import { useDashboardSearch } from './hooks/useDashboardSearch';
import { usePullToRefresh } from '../../utils/TouchGestureManager';
import { useScreenReader } from '../../hooks/useScreenReader';
import DoctorLevelCard from './components/DoctorLevelCard';
import AnalyticsCard from './components/AnalyticsCard';
import LeaderboardPreview from './components/LeaderboardPreview';
import AttendanceHistoryCard from './components/AttendanceHistoryCard';
import RealtimeStatusIndicator from './components/RealtimeStatusIndicator';
import PullToRefreshIndicator from './components/PullToRefreshIndicator';
import MobileNavigation from './components/MobileNavigation';
import TouchOptimizedCard from './components/TouchOptimizedCard';
import AdvancedSearchBar from './components/AdvancedSearchBar';
import AdvancedFilterPanel from './components/AdvancedFilterPanel';
import SearchResults from './components/SearchResults';
import SearchPerformanceIndicator from './components/SearchPerformanceIndicator';
import { ProgressiveLoadingSkeleton, useProgressiveLoading } from './components/SkeletonUI';

// Error Boundary for robust error handling
class DashboardErrorBoundary extends React.Component<
  { children: React.ReactNode; fallback?: React.ReactNode },
  { hasError: boolean; error?: Error }
> {
  constructor(props: { children: React.ReactNode; fallback?: React.ReactNode }) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('Dashboard Error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback || (
        <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 flex items-center justify-center">
          <div className="text-center text-white p-8">
            <div className="text-6xl mb-4">⚠️</div>
            <h2 className="text-2xl font-bold mb-2">Oops! Something went wrong</h2>
            <p className="text-gray-300 mb-4">Dashboard mengalami kesalahan yang tidak terduga</p>
            <button 
              onClick={() => window.location.reload()} 
              className="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg transition-colors btn-primary-accessible focus-outline touch-target"
              aria-label="Refresh the dashboard page"
            >
              Refresh Page
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Performance monitoring HOC
const withPerformanceMonitoring = <P extends object>(
  Component: React.ComponentType<P>,
  componentName: string
) => {
  return React.memo((props: P) => {
    useEffect(() => {
      const startTime = performance.now();
      return () => {
        const endTime = performance.now();
        const renderTime = endTime - startTime;
        if (renderTime > 100) { // Log slow renders
          console.warn(`🐌 Slow render detected: ${componentName} took ${renderTime.toFixed(2)}ms`);
        }
      };
    });

    return <Component {...props} />;
  });
};

// Main dashboard content component
const DashboardContent: React.FC<{
  userData?: { name: string; email: string; role?: string };
}> = React.memo(({ userData }) => {
  const { computed, loading, errors, actions } = useDashboardData();
  const { loadingPhase, progress, updateProgress } = useProgressiveLoading();
  const [currentSection, setCurrentSection] = useState('dashboard');
  const [isSearchMode, setIsSearchMode] = useState(false);
  const [isFilterPanelOpen, setIsFilterPanelOpen] = useState(false);
  
  // Real-time dashboard integration
  const {
    connectionStatus,
    performanceMetrics,
    forceRefresh,
    isInitialized
  } = useRealtimeDashboard();

  // Search integration
  const {
    searchQuery,
    filters,
    sortBy,
    results,
    groupedResults,
    isSearching,
    hasActiveFilters,
    updateSearch,
    updateFilter,
    updateSort,
    clearFilters,
    recentSearches,
    generateSuggestions,
    quickFilters,
    handleQuickFilter,
    filterConfigs,
    getFilterValues
  } = useDashboardSearch();

  // Screen reader announcements
  const {
    announceLoading,
    announceSearchResults,
    announceNavigation,
    announceError
  } = useScreenReader();

  // Pull-to-refresh integration
  const { gestureRef, isRefreshing } = usePullToRefresh(async () => {
    await forceRefresh();
  }, {
    threshold: 80,
    snapBackDuration: 300,
    triggerDistance: 60
  });

  // Mobile navigation handler
  const handleNavigation = (sectionId: string) => {
    if (sectionId === 'search') {
      setIsSearchMode(true);
      announceNavigation('search');
      // Focus search input if it exists
      setTimeout(() => {
        const searchInput = document.querySelector('input[type="text"]') as HTMLInputElement;
        if (searchInput) {
          searchInput.focus();
        }
      }, 100);
    } else {
      setCurrentSection(sectionId);
      setIsSearchMode(false);
      announceNavigation(sectionId);
      // Close filter panel when navigating away from search
      setIsFilterPanelOpen(false);
    }
  };

  // Search handlers
  const handleSearchChange = (query: string) => {
    updateSearch(query);
    setIsSearchMode(query.length > 0 || hasActiveFilters);
    
    // Announce search results after a brief delay
    if (query.length > 0) {
      setTimeout(() => {
        announceSearchResults(results.totalItems, query);
      }, 500);
    }
  };

  const handleSearchItemClick = (item: any) => {
    console.log('Search item clicked:', item);
    // Navigate to relevant section based on item type
    switch (item.type) {
      case 'attendance':
        setCurrentSection('attendance');
        break;
      case 'leaderboard':
        setCurrentSection('leaderboard');
        break;
      case 'jaspel':
      case 'metric':
        setCurrentSection('analytics');
        break;
    }
    setIsSearchMode(false);
  };

  const toggleFilterPanel = () => {
    setIsFilterPanelOpen(!isFilterPanelOpen);
  };

  // Progressive loading simulation
  useEffect(() => {
    if (loading.isDashboardLoading && progress < 30) {
      updateProgress('dashboard', 30);
    } else if (loading.isLeaderboardLoading && progress < 60) {
      updateProgress('analytics', 60);
    } else if (!loading.isAnyLoading && computed.hasData) {
      updateProgress('complete', 100);
    }
  }, [loading, computed.hasData, progress, updateProgress]);

  // Announce loading states
  useEffect(() => {
    if (loading.isDashboardLoading) {
      announceLoading(true, 'dashboard data');
    } else if (loading.isLeaderboardLoading) {
      announceLoading(true, 'leaderboard data');
    } else if (loading.isAttendanceLoading) {
      announceLoading(true, 'attendance data');
    } else if (!loading.isAnyLoading && computed.hasData) {
      announceLoading(false, 'all dashboard data');
    }
  }, [loading, computed.hasData, announceLoading]);

  // Keyboard shortcuts for search
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      // Ctrl/Cmd + K to focus search
      if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        setIsSearchMode(true);
        setTimeout(() => {
          const searchInput = document.querySelector('input[type="text"]') as HTMLInputElement;
          if (searchInput) {
            searchInput.focus();
          }
        }, 100);
      }
      
      // Escape to exit search mode
      if (event.key === 'Escape' && isSearchMode) {
        setIsSearchMode(false);
        setIsFilterPanelOpen(false);
        updateSearch('');
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isSearchMode, updateSearch]);

  // Auto-hide search bar on scroll (mobile)
  useEffect(() => {
    let lastScrollY = window.scrollY;
    let ticking = false;

    const handleScroll = () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const currentScrollY = window.scrollY;
          const scrollingDown = currentScrollY > lastScrollY;
          
          // Hide search bar when scrolling down on mobile (only if not in search mode)
          if (window.innerWidth < 768 && !isSearchMode) {
            const searchHeader = document.querySelector('[data-search-header]') as HTMLElement;
            if (searchHeader) {
              searchHeader.style.transform = scrollingDown ? 'translateY(-100%)' : 'translateY(0)';
            }
          }
          
          lastScrollY = currentScrollY;
          ticking = false;
        });
        ticking = true;
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, [isSearchMode]);

  // Show progressive loading for initial load
  if (loading.isAnyLoading && !computed.hasData) {
    return (
      <ProgressiveLoadingSkeleton 
        loadingPhase={loadingPhase} 
        progress={progress}
      />
    );
  }

  // Handle critical errors
  if (errors.hasErrors && !computed.hasData) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 flex items-center justify-center">
        <div className="text-center text-white p-8">
          <div className="text-4xl mb-4">📡</div>
          <h2 className="text-xl font-bold mb-2">Unable to Load Dashboard</h2>
          <p className="text-high-contrast-secondary mb-4">
            {errors.allErrors[0] || 'Network connection issue'}
          </p>
          <button 
            onClick={actions.refreshAll}
            disabled={loading.isAnyLoading}
            className="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 px-6 py-2 rounded-lg transition-colors btn-primary-accessible focus-outline touch-target"
            aria-label={loading.isAnyLoading ? 'Retrying to load dashboard data' : 'Try to reload dashboard data again'}
          >
            {loading.isAnyLoading ? 'Retrying...' : 'Try Again'}
          </button>
        </div>
      </div>
    );
  }

  // Determine time-based icon
  const TimeIcon = computed.timeBasedGreeting.greeting.includes('Pagi') ? Sun : Moon;

  return (
    <div 
      ref={gestureRef}
      className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 text-white relative overflow-hidden pb-20"
      role="application"
      aria-label="Doctor Dashboard Application"
    >
      {/* Skip Links for Keyboard Navigation */}
      <div className="sr-only">
        <a 
          href="#main-content" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded"
        >
          Skip to main content
        </a>
        <a 
          href="#search-input" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 focus:left-32 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded"
        >
          Skip to search
        </a>
        <a 
          href="#navigation" 
          className="skip-link focus:not-sr-only focus:absolute focus:top-2 focus:left-48 focus:z-50 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded"
        >
          Skip to navigation
        </a>
      </div>

      {/* Screen Reader Announcements */}
      <div 
        id="sr-announcements" 
        className="sr-only" 
        aria-live="polite" 
        aria-atomic="true"
      ></div>
      
      {/* Loading announcements */}
      <div 
        id="sr-loading" 
        className="sr-only" 
        aria-live="assertive" 
        aria-atomic="true"
      >
        {loading.isAnyLoading && "Loading dashboard data, please wait..."}
      </div>
      {/* Pull-to-refresh indicator */}
      <PullToRefreshIndicator
        isRefreshing={isRefreshing}
        progress={0} // Will be managed by TouchGestureManager
        isReady={false}
      />

      {/* Animated background elements */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-1/4 left-1/4 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div className="absolute bottom-1/4 right-1/4 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
        <div className="absolute top-3/4 left-3/4 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl animate-pulse delay-2000"></div>
      </div>

      {/* Real-time Status Indicator */}
      {isInitialized && (
        <div className="fixed top-4 right-4 z-50">
          <RealtimeStatusIndicator
            connectionStatus={connectionStatus}
            performanceMetrics={performanceMetrics}
            onRefresh={forceRefresh}
            className="bg-black/30 backdrop-blur-sm rounded-lg px-3 py-2"
          />
        </div>
      )}

      {/* Search Bar Header */}
      <header 
        data-search-header
        className="sticky top-0 z-40 bg-gray-900/95 backdrop-blur-sm border-b border-gray-700 p-4 transition-transform duration-300"
        role="banner"
        aria-label="Dashboard search and navigation"
      >
        <div id="search-input">
          <AdvancedSearchBar
            value={searchQuery}
            placeholder="Search attendance, JASPEL, leaderboard..."
          suggestions={generateSuggestions(searchQuery)}
          quickFilters={quickFilters}
          recentSearches={recentSearches}
          isSearching={isSearching}
          showFilterButton={true}
          onSearch={handleSearchChange}
          onFilterToggle={toggleFilterPanel}
          onQuickFilter={handleQuickFilter}
          onClear={() => {
            updateSearch('');
            setIsSearchMode(false);
          }}
          />
        </div>
      </header>

      {/* Filter Panel Sidebar */}
      <AdvancedFilterPanel
        isOpen={isFilterPanelOpen}
        filters={filters}
        filterConfigs={filterConfigs}
        onFilterChange={updateFilter}
        onClearFilters={clearFilters}
        onClose={() => setIsFilterPanelOpen(false)}
        getFilterValues={getFilterValues}
      />

      {/* Main Content Container */}
      <main 
        id="main-content"
        className={`transition-all duration-300 ${isFilterPanelOpen ? 'lg:mr-80' : ''}`}
        role="main"
        aria-label="Dashboard main content"
      >
        {/* Search Results or Dashboard Content */}
        {isSearchMode ? (
          <div className="p-4">
            {/* Search Mode Header */}
            <div className="mb-6">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold text-white">Search Dashboard</h1>
                  <p className="text-high-contrast-muted text-sm mt-1" id="search-description">
                    Search through your attendance, JASPEL, metrics, and leaderboard data
                  </p>
                </div>
                
                {/* Quick Stats */}
                <div className="text-right">
                  <div className="text-sm text-high-contrast-secondary">Total searchable items</div>
                  <div className="text-lg font-semibold text-high-contrast">{results.totalItems}</div>
                </div>
              </div>
              
              {/* Search Tips */}
              {!searchQuery && (
                <div className="mt-4 p-3 card-accessible rounded-lg">
                  <div className="text-xs text-high-contrast-secondary mb-2">💡 Search Tips:</div>
                  <div className="flex flex-wrap gap-2 text-xs">
                    <span className="px-2 py-1 badge-accessible rounded">Try "attendance"</span>
                    <span className="px-2 py-1 badge-accessible rounded">Search "JASPEL current"</span>
                    <span className="px-2 py-1 badge-accessible rounded">Use Ctrl+K shortcut</span>
                  </div>
                </div>
              )}
            </div>
            
            {/* Search Performance Indicator */}
            {searchQuery && (
              <SearchPerformanceIndicator
                searchTime={results.searchTime}
                totalResults={results.totalItems}
                totalSearchableItems={computed.attendanceHistory.length + computed.leaderboardTop3.length + 3} // approximate total
                recentSearches={recentSearches.length}
                className="mb-4"
              />
            )}
            
            <SearchResults
              results={results}
              groupedResults={groupedResults}
              searchQuery={searchQuery}
              sortBy={sortBy}
              onItemClick={handleSearchItemClick}
              onSortChange={updateSort}
              isLoading={isSearching}
            />
          </div>
        ) : (
          /* Main Dashboard Content */
          <div className="p-4 space-y-4">
            {/* Dashboard Main Title - Hidden but accessible */}
            <h1 className="sr-only">Doctor Dashboard - {userData?.name || 'Doctor'}</h1>
            {/* Doctor Level Card */}
            <TouchOptimizedCard
              title={`${computed.timeBasedGreeting.greeting}, ${userData?.name || 'Doctor'}`}
              subtitle={`Level ${computed.performanceSummary.level} • ${computed.performanceSummary.experience} XP`}
              priority="high"
              onTap={() => console.log('Doctor profile tapped')}
              rightAction={{
                icon: () => <div>👤</div>,
                label: 'Profile',
                color: 'bg-blue-600',
                onClick: () => console.log('Profile action')
              }}
            >
              <DoctorLevelCard
                userData={userData}
                doctorLevel={computed.performanceSummary.level}
                experiencePoints={computed.performanceSummary.experience}
                dailyStreak={computed.performanceSummary.streak}
                attendanceRate={computed.attendanceDisplay.rate}
                attendanceDisplayText={computed.attendanceDisplay.text}
                patientsThisMonth={computed.performanceSummary.patientsThisMonth}
                greeting={computed.timeBasedGreeting.greeting}
                timeIcon={TimeIcon}
                colorGradient={computed.timeBasedGreeting.colorGradient}
                isLoading={loading.isDashboardLoading}
              />
            </TouchOptimizedCard>

            {/* Analytics Card */}
            <TouchOptimizedCard
              title="Performance Analytics"
              subtitle="JASPEL & Attendance Metrics"
              onTap={() => handleNavigation('analytics')}
              leftAction={{
                icon: () => <div>📊</div>,
                label: 'Details',
                color: 'bg-purple-600',
                onClick: () => console.log('Analytics details')
              }}
            >
              <AnalyticsCard
                jaspelGrowthPercentage={computed.jaspelMetrics.growth}
                jaspelProgressPercentage={computed.jaspelMetrics.progress}
                attendanceRate={computed.attendanceDisplay.rate}
                attendanceDisplayText={computed.attendanceDisplay.text}
                isLoading={loading.isDashboardLoading}
              />
            </TouchOptimizedCard>

            {/* Attendance History */}
            <TouchOptimizedCard
              title="Attendance History"
              subtitle={`${computed.attendanceHistory.length} recent records`}
              onTap={() => handleNavigation('attendance')}
              rightAction={{
                icon: () => <div>📅</div>,
                label: 'Full History',
                color: 'bg-green-600',
                onClick: () => console.log('Full attendance history')
              }}
            >
              <AttendanceHistoryCard
                attendanceHistory={computed.attendanceHistory}
                isLoading={loading.isDashboardLoading}
                maxRecords={5}
              />
            </TouchOptimizedCard>

            {/* Leaderboard Preview */}
            <TouchOptimizedCard
              title="Leaderboard"
              subtitle="Top performers this month"
              onTap={() => handleNavigation('leaderboard')}
              leftAction={{
                icon: () => <div>🏆</div>,
                label: 'Full Board',
                color: 'bg-yellow-600',
                onClick: () => console.log('Full leaderboard')
              }}
            >
              <LeaderboardPreview
                leaderboardData={computed.leaderboardTop3}
                isLoading={loading.isLeaderboardLoading}
                userData={userData}
              />
            </TouchOptimizedCard>
          </div>
        )}
      </main>

      {/* Mobile Navigation */}
      <nav id="navigation" aria-label="Main navigation">
        <MobileNavigation
          onNavigate={handleNavigation}
        items={[
          { id: 'dashboard', label: 'Dashboard', icon: () => <div>🏠</div>, isActive: currentSection === 'dashboard' },
          { id: 'search', label: 'Search', icon: () => <Search className="w-5 h-5" />, isActive: isSearchMode },
          { id: 'analytics', label: 'Analytics', icon: () => <div>📊</div>, isActive: currentSection === 'analytics' },
          { id: 'leaderboard', label: 'Leaderboard', icon: () => <div>🏆</div>, isActive: currentSection === 'leaderboard' },
          { id: 'attendance', label: 'Attendance', icon: () => <div>📅</div>, isActive: currentSection === 'attendance', badge: computed.attendanceHistory.length }
        ]}
        />
      </nav>

      {/* Refresh indicator */}
      {loading.isAnyLoading && computed.hasData && (
        <div className="fixed top-4 right-4 bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 text-sm text-white z-50">
          <div className="flex items-center space-x-2">
            <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            <span>Updating...</span>
          </div>
        </div>
      )}

      {/* Error toast for non-critical errors */}
      {errors.hasErrors && computed.hasData && (
        <div className="fixed bottom-4 left-4 bg-red-500/90 backdrop-blur-sm rounded-lg px-4 py-3 text-white text-sm max-w-sm z-50">
          <div className="flex items-start space-x-2">
            <div className="text-lg">⚠️</div>
            <div className="flex-1">
              <div className="font-semibold">Warning</div>
              <div>{errors.allErrors[0]}</div>
            </div>
            <button 
              onClick={actions.clearErrors}
              className="text-white/70 hover:text-white text-lg leading-none"
            >
              ×
            </button>
          </div>
        </div>
      )}
    </div>
  );
});

DashboardContent.displayName = 'DashboardContent';

// Enhanced components with performance monitoring
const EnhancedDoctorLevelCard = withPerformanceMonitoring(DoctorLevelCard, 'DoctorLevelCard');
const EnhancedAnalyticsCard = withPerformanceMonitoring(AnalyticsCard, 'AnalyticsCard');
const EnhancedLeaderboardPreview = withPerformanceMonitoring(LeaderboardPreview, 'LeaderboardPreview');

// Main dashboard component with all optimizations
const RefactoredDashboard: React.FC<{
  userData?: { name: string; email: string; role?: string };
}> = React.memo(({ userData }) => {
  return (
    <DashboardErrorBoundary>
      <DashboardProvider userData={userData}>
        <Suspense fallback={
          <ProgressiveLoadingSkeleton 
            loadingPhase="initial" 
            progress={0}
          />
        }>
          <DashboardContent userData={userData} />
        </Suspense>
      </DashboardProvider>
    </DashboardErrorBoundary>
  );
});

RefactoredDashboard.displayName = 'RefactoredDashboard';

export default RefactoredDashboard;