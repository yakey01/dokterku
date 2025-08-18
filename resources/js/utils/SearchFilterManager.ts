/**
 * Advanced Search & Filter Manager for Dashboard
 * Provides intelligent search, filtering, and sorting capabilities
 */

import React, { useState, useMemo, useCallback, useEffect } from 'react';

interface SearchableItem {
  id: string | number;
  [key: string]: any;
}

interface FilterConfig {
  key: string;
  label: string;
  type: 'text' | 'select' | 'date' | 'range' | 'boolean' | 'tags';
  options?: Array<{ value: any; label: string }>;
  multiple?: boolean;
  placeholder?: string;
}

interface SortConfig {
  key: string;
  direction: 'asc' | 'desc';
}

interface SearchFilterState {
  searchQuery: string;
  filters: Record<string, any>;
  sortBy: SortConfig;
  resultsPerPage: number;
  currentPage: number;
}

interface SearchResults<T> {
  items: T[];
  totalItems: number;
  totalPages: number;
  currentPage: number;
  hasNextPage: boolean;
  hasPreviousPage: boolean;
  searchTime: number;
  suggestions: string[];
}

const DEFAULT_STATE: SearchFilterState = {
  searchQuery: '',
  filters: {},
  sortBy: { key: 'id', direction: 'desc' },
  resultsPerPage: 10,
  currentPage: 1
};

class SearchFilterManager<T extends SearchableItem> {
  private items: T[] = [];
  private searchableFields: string[] = [];
  private filterConfigs: FilterConfig[] = [];
  private stopWords = new Set(['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by']);

  constructor(
    items: T[] = [],
    searchableFields: string[] = [],
    filterConfigs: FilterConfig[] = []
  ) {
    this.items = items;
    this.searchableFields = searchableFields;
    this.filterConfigs = filterConfigs;
  }

  /**
   * Update data source
   */
  updateItems(items: T[]): void {
    this.items = items;
  }

  /**
   * Perform search and filtering
   */
  search(state: SearchFilterState): SearchResults<T> {
    const startTime = performance.now();
    
    let results = [...this.items];
    
    // Apply text search
    if (state.searchQuery.trim()) {
      results = this.performTextSearch(results, state.searchQuery);
    }
    
    // Apply filters
    results = this.applyFilters(results, state.filters);
    
    // Apply sorting
    results = this.applySorting(results, state.sortBy);
    
    // Calculate pagination
    const totalItems = results.length;
    const totalPages = Math.ceil(totalItems / state.resultsPerPage);
    const startIndex = (state.currentPage - 1) * state.resultsPerPage;
    const endIndex = startIndex + state.resultsPerPage;
    const paginatedResults = results.slice(startIndex, endIndex);
    
    const searchTime = performance.now() - startTime;
    
    // Generate search suggestions
    const suggestions = this.generateSuggestions(state.searchQuery);
    
    return {
      items: paginatedResults,
      totalItems,
      totalPages,
      currentPage: state.currentPage,
      hasNextPage: state.currentPage < totalPages,
      hasPreviousPage: state.currentPage > 1,
      searchTime,
      suggestions
    };
  }

  /**
   * Perform fuzzy text search across searchable fields
   */
  private performTextSearch(items: T[], query: string): T[] {
    if (!query.trim()) return items;
    
    const normalizedQuery = this.normalizeSearchTerm(query);
    const queryTerms = normalizedQuery.split(' ').filter(term => 
      term.length > 1 && !this.stopWords.has(term)
    );
    
    if (queryTerms.length === 0) return items;
    
    return items
      .map(item => ({
        item,
        score: this.calculateSearchScore(item, queryTerms)
      }))
      .filter(result => result.score > 0)
      .sort((a, b) => b.score - a.score)
      .map(result => result.item);
  }

  /**
   * Calculate search relevance score
   */
  private calculateSearchScore(item: T, queryTerms: string[]): number {
    let score = 0;
    
    for (const field of this.searchableFields) {
      const fieldValue = this.getNestedValue(item, field);
      if (!fieldValue) continue;
      
      const normalizedValue = this.normalizeSearchTerm(String(fieldValue));
      
      for (const term of queryTerms) {
        // Exact match bonus
        if (normalizedValue.includes(term)) {
          score += 10;
          
          // Start of word bonus
          if (normalizedValue.startsWith(term)) {
            score += 5;
          }
          
          // Full word match bonus
          const words = normalizedValue.split(' ');
          if (words.includes(term)) {
            score += 8;
          }
          
          // Fuzzy match
          const fuzzyScore = this.calculateFuzzyMatch(term, normalizedValue);
          score += fuzzyScore;
        }
      }
    }
    
    return score;
  }

  /**
   * Calculate fuzzy match score using Levenshtein distance
   */
  private calculateFuzzyMatch(term: string, text: string): number {
    const words = text.split(' ');
    let bestScore = 0;
    
    for (const word of words) {
      if (word.length < term.length * 0.7) continue;
      
      const distance = this.levenshteinDistance(term, word);
      const similarity = 1 - (distance / Math.max(term.length, word.length));
      
      if (similarity > 0.6) {
        bestScore = Math.max(bestScore, similarity * 3);
      }
    }
    
    return bestScore;
  }

  /**
   * Levenshtein distance for fuzzy matching
   */
  private levenshteinDistance(a: string, b: string): number {
    const matrix = Array(b.length + 1).fill(null).map(() => Array(a.length + 1).fill(null));
    
    for (let i = 0; i <= a.length; i++) matrix[0][i] = i;
    for (let j = 0; j <= b.length; j++) matrix[j][0] = j;
    
    for (let j = 1; j <= b.length; j++) {
      for (let i = 1; i <= a.length; i++) {
        const indicator = a[i - 1] === b[j - 1] ? 0 : 1;
        matrix[j][i] = Math.min(
          matrix[j][i - 1] + 1,
          matrix[j - 1][i] + 1,
          matrix[j - 1][i - 1] + indicator
        );
      }
    }
    
    return matrix[b.length][a.length];
  }

  /**
   * Apply filters to results
   */
  private applyFilters(items: T[], filters: Record<string, any>): T[] {
    return items.filter(item => {
      for (const [filterKey, filterValue] of Object.entries(filters)) {
        if (filterValue === null || filterValue === undefined || filterValue === '') {
          continue;
        }
        
        const config = this.filterConfigs.find(c => c.key === filterKey);
        if (!config) continue;
        
        const itemValue = this.getNestedValue(item, filterKey);
        
        if (!this.matchesFilter(itemValue, filterValue, config.type)) {
          return false;
        }
      }
      return true;
    });
  }

  /**
   * Check if item value matches filter
   */
  private matchesFilter(itemValue: any, filterValue: any, filterType: string): boolean {
    switch (filterType) {
      case 'text':
        return String(itemValue).toLowerCase().includes(String(filterValue).toLowerCase());
      
      case 'select':
        if (Array.isArray(filterValue)) {
          return filterValue.includes(itemValue);
        }
        return itemValue === filterValue;
      
      case 'date':
        const itemDate = new Date(itemValue);
        const filterDate = new Date(filterValue);
        return itemDate.toDateString() === filterDate.toDateString();
      
      case 'range':
        const { min, max } = filterValue;
        const numValue = Number(itemValue);
        return numValue >= (min || -Infinity) && numValue <= (max || Infinity);
      
      case 'boolean':
        return Boolean(itemValue) === Boolean(filterValue);
      
      case 'tags':
        if (!Array.isArray(itemValue)) return false;
        if (Array.isArray(filterValue)) {
          return filterValue.some(tag => itemValue.includes(tag));
        }
        return itemValue.includes(filterValue);
      
      default:
        return true;
    }
  }

  /**
   * Apply sorting to results
   */
  private applySorting(items: T[], sortBy: SortConfig): T[] {
    return items.sort((a, b) => {
      const aValue = this.getNestedValue(a, sortBy.key);
      const bValue = this.getNestedValue(b, sortBy.key);
      
      let comparison = 0;
      
      if (aValue < bValue) comparison = -1;
      else if (aValue > bValue) comparison = 1;
      
      return sortBy.direction === 'desc' ? -comparison : comparison;
    });
  }

  /**
   * Generate search suggestions based on current query
   */
  private generateSuggestions(query: string): string[] {
    if (!query.trim()) return [];
    
    const suggestions = new Set<string>();
    const normalizedQuery = this.normalizeSearchTerm(query);
    
    // Extract common terms from searchable fields
    for (const item of this.items) {
      for (const field of this.searchableFields) {
        const value = this.getNestedValue(item, field);
        if (!value) continue;
        
        const normalizedValue = this.normalizeSearchTerm(String(value));
        const words = normalizedValue.split(' ');
        
        for (const word of words) {
          if (word.length > 2 && word.startsWith(normalizedQuery.split(' ')[0])) {
            suggestions.add(word);
          }
        }
      }
    }
    
    return Array.from(suggestions).slice(0, 5);
  }

  /**
   * Get nested object value by dot notation
   */
  private getNestedValue(obj: any, path: string): any {
    return path.split('.').reduce((current, key) => current?.[key], obj);
  }

  /**
   * Normalize search term for consistent matching
   */
  private normalizeSearchTerm(term: string): string {
    return term
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
      .trim();
  }

  /**
   * Get available filter values for a field
   */
  getFilterValues(filterKey: string): Array<{ value: any; label: string; count: number }> {
    const values = new Map<any, number>();
    
    for (const item of this.items) {
      const value = this.getNestedValue(item, filterKey);
      if (value !== null && value !== undefined) {
        values.set(value, (values.get(value) || 0) + 1);
      }
    }
    
    return Array.from(values.entries())
      .map(([value, count]) => ({
        value,
        label: String(value),
        count
      }))
      .sort((a, b) => b.count - a.count);
  }
}

// React hook for search and filtering
export const useSearchFilter = <T extends SearchableItem>(
  items: T[],
  searchableFields: string[],
  filterConfigs: FilterConfig[] = [],
  initialState: Partial<SearchFilterState> = {}
) => {
  const [state, setState] = useState<SearchFilterState>({
    ...DEFAULT_STATE,
    ...initialState
  });

  const [isSearching, setIsSearching] = useState(false);
  
  const manager = useMemo(() => 
    new SearchFilterManager(items, searchableFields, filterConfigs),
    [items, searchableFields, filterConfigs]
  );

  const results = useMemo(() => {
    setIsSearching(true);
    const searchResults = manager.search(state);
    setIsSearching(false);
    return searchResults;
  }, [manager, state]);

  const updateSearch = useCallback((query: string) => {
    setState(prev => ({
      ...prev,
      searchQuery: query,
      currentPage: 1 // Reset to first page on new search
    }));
  }, []);

  const updateFilter = useCallback((key: string, value: any) => {
    setState(prev => ({
      ...prev,
      filters: {
        ...prev.filters,
        [key]: value
      },
      currentPage: 1 // Reset to first page on filter change
    }));
  }, []);

  const updateSort = useCallback((key: string, direction: 'asc' | 'desc' = 'asc') => {
    setState(prev => ({
      ...prev,
      sortBy: { key, direction }
    }));
  }, []);

  const setPage = useCallback((page: number) => {
    setState(prev => ({
      ...prev,
      currentPage: Math.max(1, Math.min(page, results.totalPages))
    }));
  }, [results.totalPages]);

  const clearFilters = useCallback(() => {
    setState(prev => ({
      ...prev,
      searchQuery: '',
      filters: {},
      currentPage: 1
    }));
  }, []);

  const getFilterValues = useCallback((filterKey: string) => {
    return manager.getFilterValues(filterKey);
  }, [manager]);

  return {
    // State
    searchQuery: state.searchQuery,
    filters: state.filters,
    sortBy: state.sortBy,
    currentPage: state.currentPage,
    
    // Results
    results,
    isSearching,
    
    // Actions
    updateSearch,
    updateFilter,
    updateSort,
    setPage,
    clearFilters,
    getFilterValues,
    
    // Utilities
    hasActiveFilters: Object.keys(state.filters).some(key => 
      state.filters[key] !== null && 
      state.filters[key] !== undefined && 
      state.filters[key] !== ''
    ) || state.searchQuery.trim() !== ''
  };
};

export default SearchFilterManager;
export type { FilterConfig, SortConfig, SearchResults, SearchableItem };