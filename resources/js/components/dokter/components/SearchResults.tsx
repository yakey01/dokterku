import React, { useMemo } from 'react';
import { 
  Clock, 
  Calendar, 
  TrendingUp, 
  Users, 
  DollarSign, 
  BarChart3,
  ChevronRight,
  Search,
  Filter,
  SortAsc,
  SortDesc
} from 'lucide-react';
import type { SearchResults as SearchResultsType } from '../../../utils/SearchFilterManager';

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

interface SearchResultsProps {
  results: SearchResultsType<DashboardSearchableItem>;
  groupedResults: SearchSection[];
  searchQuery: string;
  sortBy: { key: string; direction: 'asc' | 'desc' };
  onItemClick?: (item: DashboardSearchableItem) => void;
  onSortChange?: (key: string, direction: 'asc' | 'desc') => void;
  isLoading?: boolean;
  className?: string;
}

const SearchResults: React.FC<SearchResultsProps> = ({
  results,
  groupedResults,
  searchQuery,
  sortBy,
  onItemClick,
  onSortChange,
  isLoading = false,
  className = ''
}) => {
  // Highlight search terms in text
  const highlightText = (text: string, query: string) => {
    if (!query.trim()) return text;
    
    const terms = query.trim().split(' ').filter(term => term.length > 1);
    let highlightedText = text;
    
    terms.forEach(term => {
      const regex = new RegExp(`(${term})`, 'gi');
      highlightedText = highlightedText.replace(regex, '<mark class="bg-yellow-400 text-black px-1 rounded">$1</mark>');
    });
    
    return highlightedText;
  };

  // Get icon for item type
  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'attendance':
        return <Calendar className="w-4 h-4 text-blue-400" />;
      case 'leaderboard':
        return <Users className="w-4 h-4 text-purple-400" />;
      case 'jaspel':
        return <DollarSign className="w-4 h-4 text-green-400" />;
      case 'metric':
        return <BarChart3 className="w-4 h-4 text-orange-400" />;
      default:
        return <Search className="w-4 h-4 text-gray-400" />;
    }
  };

  // Get status color
  const getStatusColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'hadir':
      case 'present':
      case 'top':
        return 'text-green-400 bg-green-400/10';
      case 'terlambat':
      case 'late':
        return 'text-yellow-400 bg-yellow-400/10';
      case 'tidak hadir':
      case 'absent':
        return 'text-red-400 bg-red-400/10';
      default:
        return 'text-gray-400 bg-gray-400/10';
    }
  };

  // Format value display
  const formatValue = (item: DashboardSearchableItem) => {
    if (!item.value) return null;
    
    switch (item.type) {
      case 'jaspel':
        return `Rp ${item.value.toLocaleString('id-ID')}`;
      case 'metric':
        return item.category === 'attendance' ? `${item.value}%` : item.value.toString();
      case 'leaderboard':
        return `Rank #${item.value}`;
      default:
        return item.value.toString();
    }
  };

  // Sort options
  const sortOptions = [
    { key: 'title', label: 'Name' },
    { key: 'date', label: 'Date' },
    { key: 'value', label: 'Value' },
    { key: 'type', label: 'Type' }
  ];

  if (isLoading) {
    return (
      <div className={`space-y-4 ${className}`}>
        {[...Array(5)].map((_, i) => (
          <div key={i} className="bg-gray-800 rounded-lg p-4 animate-pulse">
            <div className="flex items-start space-x-3">
              <div className="w-8 h-8 bg-gray-700 rounded-full" />
              <div className="flex-1 space-y-2">
                <div className="h-4 bg-gray-700 rounded w-3/4" />
                <div className="h-3 bg-gray-700 rounded w-1/2" />
                <div className="h-3 bg-gray-700 rounded w-2/3" />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (results.totalItems === 0) {
    return (
      <div className={`text-center py-12 ${className}`}>
        <Search className="w-16 h-16 text-gray-600 mx-auto mb-4" />
        <h3 className="text-lg font-semibold text-white mb-2">No results found</h3>
        <p className="text-gray-400 mb-4">
          {searchQuery 
            ? `No results for "${searchQuery}". Try different keywords or filters.`
            : 'Start typing to search through your dashboard data.'
          }
        </p>
        {searchQuery && (
          <div className="text-sm text-gray-500">
            <p>💡 Try searching for:</p>
            <div className="flex flex-wrap justify-center gap-2 mt-2">
              <span className="px-2 py-1 bg-gray-700 rounded">attendance</span>
              <span className="px-2 py-1 bg-gray-700 rounded">JASPEL</span>
              <span className="px-2 py-1 bg-gray-700 rounded">leaderboard</span>
              <span className="px-2 py-1 bg-gray-700 rounded">performance</span>
            </div>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Results Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <div className="text-white">
            <span className="font-semibold">{results.totalItems}</span>
            <span className="text-gray-400 ml-1">
              result{results.totalItems !== 1 ? 's' : ''}
            </span>
            {searchQuery && (
              <span className="text-gray-400">
                {' '}for "<span className="text-white">{searchQuery}</span>"
              </span>
            )}
          </div>
          
          <div className="text-xs text-gray-500">
            {results.searchTime.toFixed(1)}ms
          </div>
        </div>

        {/* Sort Controls */}
        <div className="flex items-center space-x-2">
          <select
            value={sortBy.key}
            onChange={(e) => onSortChange?.(e.target.value, sortBy.direction)}
            className="
              bg-gray-700 border border-gray-600 rounded-md px-3 py-1 text-sm text-white
              focus:outline-none focus:ring-2 focus:ring-blue-500
            "
          >
            {sortOptions.map(option => (
              <option key={option.key} value={option.key}>
                {option.label}
              </option>
            ))}
          </select>
          
          <button
            onClick={() => onSortChange?.(sortBy.key, sortBy.direction === 'asc' ? 'desc' : 'asc')}
            className="
              p-2 text-gray-400 hover:text-white transition-colors
              hover:bg-gray-700 rounded-md
            "
            title={`Sort ${sortBy.direction === 'asc' ? 'descending' : 'ascending'}`}
          >
            {sortBy.direction === 'asc' ? (
              <SortAsc className="w-4 h-4" />
            ) : (
              <SortDesc className="w-4 h-4" />
            )}
          </button>
        </div>
      </div>

      {/* Grouped Results */}
      {groupedResults.map((section) => (
        <div key={section.id} className="space-y-3">
          {/* Section Header */}
          <div className="flex items-center space-x-2 pb-2 border-b border-gray-700">
            <span className="text-lg">{section.icon}</span>
            <h3 className="text-white font-semibold">{section.title}</h3>
            <span className="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded-full">
              {section.count}
            </span>
          </div>

          {/* Section Items */}
          <div className="space-y-2">
            {section.items.map((item) => (
              <div
                key={item.id}
                onClick={() => onItemClick?.(item)}
                className="
                  bg-gray-800 hover:bg-gray-750 rounded-lg p-4 transition-all duration-200
                  cursor-pointer border border-transparent hover:border-gray-600
                  group
                "
              >
                <div className="flex items-start space-x-3">
                  {/* Type Icon */}
                  <div className="flex-shrink-0 mt-1">
                    {getTypeIcon(item.type)}
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0 space-y-1">
                    {/* Title */}
                    <div className="flex items-center justify-between">
                      <h4 
                        className="text-white font-medium text-sm leading-tight"
                        dangerouslySetInnerHTML={{ 
                          __html: highlightText(item.title, searchQuery) 
                        }}
                      />
                      
                      {/* Value */}
                      {item.value && (
                        <span className="text-blue-400 text-sm font-semibold">
                          {formatValue(item)}
                        </span>
                      )}
                    </div>

                    {/* Subtitle */}
                    {item.subtitle && (
                      <p 
                        className="text-gray-300 text-sm"
                        dangerouslySetInnerHTML={{ 
                          __html: highlightText(item.subtitle, searchQuery) 
                        }}
                      />
                    )}

                    {/* Description */}
                    {item.description && (
                      <p 
                        className="text-gray-400 text-xs leading-relaxed"
                        dangerouslySetInnerHTML={{ 
                          __html: highlightText(item.description, searchQuery) 
                        }}
                      />
                    )}

                    {/* Metadata */}
                    <div className="flex items-center justify-between mt-2">
                      <div className="flex items-center space-x-2">
                        {/* Status */}
                        {item.status && (
                          <span className={`
                            px-2 py-1 rounded-full text-xs font-medium
                            ${getStatusColor(item.status)}
                          `}>
                            {item.status}
                          </span>
                        )}

                        {/* Date */}
                        {item.date && (
                          <div className="flex items-center space-x-1 text-gray-500 text-xs">
                            <Clock className="w-3 h-3" />
                            <span>{item.date}</span>
                          </div>
                        )}

                        {/* Tags */}
                        {item.tags && item.tags.length > 0 && (
                          <div className="flex items-center space-x-1">
                            {item.tags.slice(0, 2).map((tag, index) => (
                              <span
                                key={index}
                                className="px-1.5 py-0.5 bg-gray-700 text-gray-300 text-xs rounded"
                              >
                                {tag}
                              </span>
                            ))}
                            {item.tags.length > 2 && (
                              <span className="text-gray-500 text-xs">
                                +{item.tags.length - 2}
                              </span>
                            )}
                          </div>
                        )}
                      </div>

                      {/* Action Indicator */}
                      <ChevronRight className="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" />
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      ))}

      {/* Pagination */}
      {results.totalPages > 1 && (
        <div className="flex items-center justify-center space-x-2 pt-6">
          <button
            disabled={!results.hasPreviousPage}
            className="
              px-3 py-2 bg-gray-700 text-white rounded-md text-sm
              disabled:opacity-50 disabled:cursor-not-allowed
              hover:bg-gray-600 transition-colors
            "
          >
            Previous
          </button>
          
          <span className="px-4 py-2 text-sm text-gray-400">
            Page {results.currentPage} of {results.totalPages}
          </span>
          
          <button
            disabled={!results.hasNextPage}
            className="
              px-3 py-2 bg-gray-700 text-white rounded-md text-sm
              disabled:opacity-50 disabled:cursor-not-allowed
              hover:bg-gray-600 transition-colors
            "
          >
            Next
          </button>
        </div>
      )}

      {/* Search Suggestions */}
      {results.suggestions && results.suggestions.length > 0 && (
        <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <h4 className="text-white font-medium mb-2">You might also try:</h4>
          <div className="flex flex-wrap gap-2">
            {results.suggestions.map((suggestion, index) => (
              <button
                key={index}
                className="
                  px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 
                  rounded-full text-sm transition-colors
                "
              >
                {suggestion}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default React.memo(SearchResults);