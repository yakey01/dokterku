import { useCallback } from 'react';

/**
 * Hook for managing screen reader announcements
 * Provides accessible feedback for dynamic content changes
 */
export const useScreenReader = () => {
  /**
   * Announce message to screen readers using aria-live regions
   * @param message - The message to announce
   * @param priority - 'polite' or 'assertive' (default: 'polite')
   * @param element - Target element ID (default: 'sr-announcements')
   */
  const announce = useCallback((
    message: string, 
    priority: 'polite' | 'assertive' = 'polite',
    element: string = 'sr-announcements'
  ) => {
    const announceElement = document.getElementById(element);
    if (announceElement) {
      // Clear existing content first
      announceElement.textContent = '';
      
      // Use timeout to ensure screen readers pick up the change
      setTimeout(() => {
        announceElement.textContent = message;
        announceElement.setAttribute('aria-live', priority);
      }, 100);
      
      // Clear the announcement after it's been read
      setTimeout(() => {
        announceElement.textContent = '';
      }, 5000);
    }
  }, []);

  /**
   * Announce loading states
   * @param isLoading - Current loading state
   * @param operation - Description of what's loading
   */
  const announceLoading = useCallback((isLoading: boolean, operation: string = 'data') => {
    if (isLoading) {
      announce(`Loading ${operation}, please wait...`, 'assertive', 'sr-loading');
    } else {
      announce(`${operation} loaded successfully`, 'polite');
    }
  }, [announce]);

  /**
   * Announce search results
   * @param count - Number of results found
   * @param query - Search query
   */
  const announceSearchResults = useCallback((count: number, query: string) => {
    const message = count === 0 
      ? `No results found for "${query}"`
      : `Found ${count} result${count !== 1 ? 's' : ''} for "${query}"`;
    announce(message, 'polite');
  }, [announce]);

  /**
   * Announce filter changes
   * @param filterType - Type of filter applied
   * @param value - Filter value
   */
  const announceFilterChange = useCallback((filterType: string, value: string | null) => {
    const message = value 
      ? `${filterType} filter applied: ${value}`
      : `${filterType} filter removed`;
    announce(message, 'polite');
  }, [announce]);

  /**
   * Announce navigation changes
   * @param section - New section name
   */
  const announceNavigation = useCallback((section: string) => {
    announce(`Navigated to ${section} section`, 'polite');
  }, [announce]);

  /**
   * Announce errors
   * @param error - Error message
   */
  const announceError = useCallback((error: string) => {
    announce(`Error: ${error}`, 'assertive');
  }, [announce]);

  /**
   * Announce success messages
   * @param message - Success message
   */
  const announceSuccess = useCallback((message: string) => {
    announce(`Success: ${message}`, 'polite');
  }, [announce]);

  /**
   * Announce form validation errors
   * @param field - Field name
   * @param error - Error message
   */
  const announceValidationError = useCallback((field: string, error: string) => {
    announce(`${field}: ${error}`, 'assertive');
  }, [announce]);

  /**
   * Clear all announcements
   */
  const clearAnnouncements = useCallback(() => {
    const elements = ['sr-announcements', 'sr-loading'];
    elements.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = '';
      }
    });
  }, []);

  return {
    announce,
    announceLoading,
    announceSearchResults,
    announceFilterChange,
    announceNavigation,
    announceError,
    announceSuccess,
    announceValidationError,
    clearAnnouncements
  };
};

export default useScreenReader;