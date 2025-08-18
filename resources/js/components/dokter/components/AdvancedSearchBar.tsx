import React, { useState, useRef, useEffect, useCallback } from 'react';
import { 
  Search, 
  X, 
  Filter, 
  SlidersHorizontal, 
  Clock, 
  TrendingUp,
  ChevronDown,
  Zap
} from 'lucide-react';

interface SearchSuggestion {
  text: string;
  type: 'recent' | 'suggestion' | 'filter' | 'quick';
  icon?: React.ComponentType<any>;
  count?: number;
}

interface QuickFilter {
  id: string;
  label: string;
  icon: React.ComponentType<any>;
  active: boolean;
  count?: number;
}

interface AdvancedSearchBarProps {
  value: string;
  placeholder?: string;
  suggestions?: SearchSuggestion[];
  quickFilters?: QuickFilter[];
  recentSearches?: string[];
  isSearching?: boolean;
  showFilterButton?: boolean;
  onSearch: (query: string) => void;
  onFilterToggle?: () => void;
  onQuickFilter?: (filterId: string) => void;
  onClear?: () => void;
  className?: string;
}

const AdvancedSearchBar: React.FC<AdvancedSearchBarProps> = ({
  value,
  placeholder = "Search dashboard data...",
  suggestions = [],
  quickFilters = [],
  recentSearches = [],
  isSearching = false,
  showFilterButton = true,
  onSearch,
  onFilterToggle,
  onQuickFilter,
  onClear,
  className = ''
}) => {
  const [isFocused, setIsFocused] = useState(false);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [selectedSuggestionIndex, setSelectedSuggestionIndex] = useState(-1);
  const [localValue, setLocalValue] = useState(value);
  
  const inputRef = useRef<HTMLInputElement>(null);
  const suggestionsRef = useRef<HTMLDivElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  // Sync external value with local value
  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  // Close suggestions when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setShowSuggestions(false);
        setIsFocused(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Generate combined suggestions
  const combinedSuggestions = React.useMemo(() => {
    const allSuggestions: SearchSuggestion[] = [];
    
    // Add recent searches
    if (recentSearches.length > 0 && localValue.length < 2) {
      allSuggestions.push(
        ...recentSearches.slice(0, 3).map(search => ({
          text: search,
          type: 'recent' as const,
          icon: Clock
        }))
      );
    }
    
    // Add AI suggestions
    if (suggestions.length > 0) {
      allSuggestions.push(...suggestions.slice(0, 5));
    }
    
    // Add quick action suggestions
    if (localValue.length > 2) {
      allSuggestions.push(
        {
          text: `Search for "${localValue}" in attendance`,
          type: 'quick',
          icon: Zap
        },
        {
          text: `Filter by "${localValue}"`,
          type: 'filter',
          icon: Filter
        }
      );
    }
    
    return allSuggestions.slice(0, 8);
  }, [suggestions, recentSearches, localValue]);

  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setLocalValue(newValue);
    setSelectedSuggestionIndex(-1);
    
    // Show suggestions if there's input
    setShowSuggestions(newValue.length > 0 || recentSearches.length > 0);
    
    // Debounced search
    const timeoutId = setTimeout(() => {
      onSearch(newValue);
    }, 300);
    
    return () => clearTimeout(timeoutId);
  }, [onSearch, recentSearches.length]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (!showSuggestions) return;
    
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedSuggestionIndex(prev => 
          Math.min(prev + 1, combinedSuggestions.length - 1)
        );
        break;
        
      case 'ArrowUp':
        e.preventDefault();
        setSelectedSuggestionIndex(prev => Math.max(prev - 1, -1));
        break;
        
      case 'Enter':
        e.preventDefault();
        if (selectedSuggestionIndex >= 0) {
          const suggestion = combinedSuggestions[selectedSuggestionIndex];
          handleSuggestionClick(suggestion);
        } else {
          handleSearch();
        }
        break;
        
      case 'Escape':
        setShowSuggestions(false);
        setSelectedSuggestionIndex(-1);
        inputRef.current?.blur();
        break;
    }
  }, [showSuggestions, selectedSuggestionIndex, combinedSuggestions]);

  const handleSuggestionClick = useCallback((suggestion: SearchSuggestion) => {
    setLocalValue(suggestion.text);
    onSearch(suggestion.text);
    setShowSuggestions(false);
    setSelectedSuggestionIndex(-1);
    
    // Save to recent searches
    if (suggestion.type !== 'recent') {
      // This would typically update recent searches in localStorage or state
      console.log('Add to recent searches:', suggestion.text);
    }
  }, [onSearch]);

  const handleSearch = useCallback(() => {
    onSearch(localValue);
    setShowSuggestions(false);
    inputRef.current?.blur();
  }, [localValue, onSearch]);

  const handleClear = useCallback(() => {
    setLocalValue('');
    onSearch('');
    onClear?.();
    setShowSuggestions(false);
    inputRef.current?.focus();
  }, [onSearch, onClear]);

  const handleFocus = useCallback(() => {
    setIsFocused(true);
    setShowSuggestions(localValue.length > 0 || recentSearches.length > 0);
  }, [localValue.length, recentSearches.length]);

  const getSuggestionIcon = (suggestion: SearchSuggestion) => {
    const IconComponent = suggestion.icon;
    const iconClass = {
      recent: 'text-gray-400',
      suggestion: 'text-blue-400',
      filter: 'text-purple-400',
      quick: 'text-green-400'
    }[suggestion.type];
    
    return IconComponent ? <IconComponent className={`w-4 h-4 ${iconClass}`} /> : null;
  };

  return (
    <div ref={containerRef} className={`relative ${className}`}>
      {/* Main Search Input */}
      <div className={`
        relative flex items-center bg-gray-800 rounded-lg border transition-all duration-200
        ${isFocused ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-gray-600 hover:border-gray-500'}
      `}>
        {/* Search Icon */}
        <div className="absolute left-3 pointer-events-none">
          {isSearching ? (
            <div className="w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin" />
          ) : (
            <Search className="w-5 h-5 text-gray-400" />
          )}
        </div>

        {/* Input Field */}
        <input
          ref={inputRef}
          type="text"
          value={localValue}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          onFocus={handleFocus}
          placeholder={placeholder}
          className="
            w-full pl-10 pr-20 py-3 bg-transparent text-white placeholder-gray-400
            focus:outline-none text-sm focus-visible:outline-2 focus-visible:outline-blue-500
          "
          autoComplete="off"
          spellCheck="false"
          aria-label="Search dashboard data"
          aria-describedby="search-description search-instructions"
          aria-expanded={showSuggestions}
          aria-autocomplete="list"
          aria-controls={showSuggestions ? "search-suggestions" : undefined}
          aria-activedescendant={
            selectedSuggestionIndex >= 0 
              ? `suggestion-${selectedSuggestionIndex}` 
              : undefined
          }
          role="combobox"
        />

        {/* Right Actions */}
        <div className="absolute right-2 flex items-center space-x-1">
          {/* Clear Button */}
          {localValue && (
            <button
              onClick={handleClear}
              className="p-1.5 text-gray-400 hover:text-white transition-colors rounded-md hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-blue-500 touch-target"
              title="Clear search"
              aria-label="Clear search input"
              type="button"
            >
              <X className="w-4 h-4" aria-hidden="true" />
            </button>
          )}

          {/* Filter Toggle */}
          {showFilterButton && onFilterToggle && (
            <button
              onClick={onFilterToggle}
              className="p-1.5 text-gray-400 hover:text-white transition-colors rounded-md hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-blue-500 touch-target"
              title="Toggle filters"
              aria-label="Toggle advanced filters panel"
              type="button"
            >
              <SlidersHorizontal className="w-4 h-4" aria-hidden="true" />
            </button>
          )}
        </div>
      </div>

      {/* Quick Filters */}
      {quickFilters.length > 0 && (
        <div className="flex flex-wrap gap-2 mt-2">
          {quickFilters.map((filter) => {
            const IconComponent = filter.icon;
            return (
              <button
                key={filter.id}
                onClick={() => onQuickFilter?.(filter.id)}
                className={`
                  flex items-center space-x-1 px-3 py-1.5 rounded-full text-xs font-medium
                  transition-all duration-200 hover:scale-105
                  ${filter.active 
                    ? 'bg-blue-600 text-white shadow-lg' 
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                  }
                `}
              >
                <IconComponent className="w-3 h-3" />
                <span>{filter.label}</span>
                {filter.count !== undefined && (
                  <span className={`
                    px-1.5 py-0.5 rounded-full text-xs
                    ${filter.active ? 'bg-blue-500' : 'bg-gray-600'}
                  `}>
                    {filter.count}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      )}

      {/* Hidden Instructions for Screen Readers */}
      <div id="search-instructions" className="sr-only">
        Use arrow keys to navigate suggestions, Enter to select, Escape to close. 
        Type to search through attendance, JASPEL income, leaderboard, and performance metrics.
      </div>

      {/* Suggestions Dropdown */}
      {showSuggestions && combinedSuggestions.length > 0 && (
        <div 
          ref={suggestionsRef}
          id="search-suggestions"
          className="
            absolute top-full left-0 right-0 mt-1 z-50
            bg-gray-800 border border-gray-600 rounded-lg shadow-xl
            max-h-80 overflow-y-auto
          "
          role="listbox"
          aria-label="Search suggestions"
        >
          {/* Suggestions Header */}
          <div className="px-4 py-2 border-b border-gray-700">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium text-gray-400 uppercase tracking-wide">
                Suggestions
              </span>
              {combinedSuggestions.length > 0 && (
                <span className="text-xs text-gray-500">
                  {combinedSuggestions.length} results
                </span>
              )}
            </div>
          </div>

          {/* Suggestion Items */}
          <div className="py-1" role="group" aria-label="Search suggestions list">
            {combinedSuggestions.map((suggestion, index) => (
              <button
                key={`${suggestion.type}-${index}`}
                id={`suggestion-${index}`}
                onClick={() => handleSuggestionClick(suggestion)}
                className={`
                  w-full flex items-center space-x-3 px-4 py-2.5 text-left
                  hover:bg-gray-700 transition-colors focus-visible:outline-2 focus-visible:outline-blue-500
                  ${index === selectedSuggestionIndex ? 'bg-gray-700' : ''}
                `}
                role="option"
                aria-selected={index === selectedSuggestionIndex}
                aria-label={`${suggestion.text}${suggestion.count ? `, ${suggestion.count} results` : ''}`}
                type="button"
              >
                {/* Icon */}
                <div className="flex-shrink-0">
                  {getSuggestionIcon(suggestion)}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="text-sm text-white truncate">
                    {suggestion.text}
                  </div>
                  {suggestion.type === 'recent' && (
                    <div className="text-xs text-gray-500">Recent search</div>
                  )}
                </div>

                {/* Count or Type Badge */}
                <div className="flex-shrink-0">
                  {suggestion.count !== undefined && (
                    <span className="px-2 py-1 bg-gray-600 text-gray-300 text-xs rounded-full">
                      {suggestion.count}
                    </span>
                  )}
                  {suggestion.type === 'quick' && (
                    <TrendingUp className="w-3 h-3 text-green-400" />
                  )}
                </div>
              </button>
            ))}
          </div>

          {/* Search Tips */}
          {localValue.length === 0 && (
            <div className="px-4 py-3 border-t border-gray-700">
              <div className="text-xs text-gray-500">
                💡 <strong>Tip:</strong> Use quotes for exact matches, or try filtering by date, status, or type
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default React.memo(AdvancedSearchBar);