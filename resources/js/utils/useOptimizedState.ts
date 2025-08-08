import { useState, useCallback, useRef } from 'react';

/**
 * Custom hook for optimized state management that prevents unnecessary re-renders
 * by comparing previous and new values before updating state
 */
export function useOptimizedState<T>(initialValue: T) {
  const [state, setState] = useState<T>(initialValue);
  const previousValueRef = useRef<T>(initialValue);

  const setOptimizedState = useCallback((newValue: T | ((prev: T) => T)) => {
    setState(prevState => {
      const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(prevState) : newValue;
      
      // Only update if value actually changed
      if (JSON.stringify(previousValueRef.current) !== JSON.stringify(nextValue)) {
        previousValueRef.current = nextValue;
        return nextValue;
      }
      
      return prevState;
    });
  }, []);

  return [state, setOptimizedState] as const;
}

/**
 * Custom hook for API data fetching with optimized state management
 */
export function useApiData<T>(
  fetchFunction: () => Promise<T>,
  dependencies: any[] = []
) {
  const [data, setData] = useOptimizedState<T | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastFetch, setLastFetch] = useState(0);

  const fetchData = useCallback(async (forceRefresh = false) => {
    try {
      // Cache for 30 seconds unless forced refresh
      if (!forceRefresh && Date.now() - lastFetch < 30000) {
        return;
      }

      setLoading(true);
      setError(null);

      const result = await fetchFunction();
      setData(result);
      setLastFetch(Date.now());
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Unknown error occurred';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  }, [fetchFunction, lastFetch, setData]);

  return {
    data,
    loading,
    error,
    fetchData,
    lastFetch
  };
}

/**
 * Custom hook for time-based state updates with optimization
 */
export function useOptimizedTime(intervalMs: number = 1000) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const lastUpdateRef = useRef<number>(0);

  const updateTime = useCallback(() => {
    const now = Date.now();
    if (now - lastUpdateRef.current >= intervalMs) {
      setCurrentTime(new Date());
      lastUpdateRef.current = now;
    }
  }, [intervalMs]);

  return { currentTime, updateTime };
}

/**
 * Custom hook for debounced state updates
 */
export function useDebouncedState<T>(initialValue: T, delay: number = 300) {
  const [state, setState] = useState<T>(initialValue);
  const timeoutRef = useRef<NodeJS.Timeout>();

  const setDebouncedState = useCallback((value: T) => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    timeoutRef.current = setTimeout(() => {
      setState(value);
    }, delay);
  }, [delay]);

  return [state, setDebouncedState] as const;
}

/**
 * Custom hook for retry logic with exponential backoff
 */
export function useRetry<T>(
  asyncFunction: () => Promise<T>,
  maxRetries: number = 3,
  baseDelay: number = 1000
) {
  const [retryCount, setRetryCount] = useState(0);
  const [isRetrying, setIsRetrying] = useState(false);

  const executeWithRetry = useCallback(async (): Promise<T> => {
    let lastError: Error | null = null;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        setIsRetrying(attempt > 0);
        setRetryCount(attempt);
        
        const result = await asyncFunction();
        
        // Reset retry state on success
        setRetryCount(0);
        setIsRetrying(false);
        
        return result;
      } catch (error) {
        lastError = error instanceof Error ? error : new Error(String(error));
        
        if (attempt === maxRetries) {
          break;
        }

        // Exponential backoff
        const delay = baseDelay * Math.pow(2, attempt);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }

    setIsRetrying(false);
    throw lastError;
  }, [asyncFunction, maxRetries, baseDelay]);

  return {
    executeWithRetry,
    retryCount,
    isRetrying
  };
}
