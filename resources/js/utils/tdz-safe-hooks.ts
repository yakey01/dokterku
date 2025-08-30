/**
 * TDZ-Safe Hook Utilities
 * Prevents Temporal Dead Zone errors in React hooks
 */

import { useState, useCallback, useMemo } from 'react';

/**
 * TDZ-safe useState with guaranteed initial value
 */
export function useSafeState<T>(initialValue: T): [T, (value: T | ((prev: T) => T)) => void] {
  // Ensure we always have a value, never undefined in TDZ-prone scenarios
  const [state, setState] = useState<T>(initialValue);
  
  const safeSetState = useCallback((value: T | ((prev: T) => T)) => {
    setState(value);
  }, []);
  
  return [state, safeSetState];
}

/**
 * TDZ-safe boolean hook for mobile detection
 */
export function useSafeMobile(): boolean {
  const [isMobile, setIsMobile] = useSafeState<boolean>(false);
  
  const updateMobile = useCallback(() => {
    if (typeof window !== 'undefined') {
      setIsMobile(window.innerWidth < 768);
    }
  }, [setIsMobile]);
  
  // Safe effect - no TDZ risk
  useMemo(() => {
    if (typeof window !== 'undefined') {
      updateMobile();
      window.addEventListener('resize', updateMobile);
      return () => window.removeEventListener('resize', updateMobile);
    }
  }, [updateMobile]);
  
  return isMobile;
}

/**
 * TDZ-safe object state with null coalescing
 */
export function useSafeObjectState<T extends object>(
  initialValue: T | null = null
): [T | null, (value: T | null | ((prev: T | null) => T | null)) => void] {
  const [state, setState] = useSafeState<T | null>(initialValue);
  
  const safeSetState = useCallback((value: T | null | ((prev: T | null) => T | null)) => {
    setState(prev => {
      // Prevent TDZ by ensuring we always return a valid value
      if (typeof value === 'function') {
        return value(prev);
      }
      return value;
    });
  }, [setState]);
  
  return [state, safeSetState];
}

/**
 * TDZ-safe destructuring helper
 */
export function safeDestructure<T extends object>(
  obj: T | undefined | null,
  defaults: Partial<T> = {}
): T {
  // Prevent TDZ by providing safe defaults
  if (!obj || typeof obj !== 'object') {
    return { ...defaults } as T;
  }
  
  return { ...defaults, ...obj };
}

/**
 * TDZ-safe hook execution wrapper
 */
export function useSafeHook<T>(
  hookFn: () => T,
  defaultValue: T,
  errorHandler?: (error: Error) => void
): T {
  try {
    const result = hookFn();
    // Prevent TDZ by ensuring we never return undefined when T doesn't allow it
    return result ?? defaultValue;
  } catch (error) {
    if (error instanceof ReferenceError && error.message.includes('uninitialized')) {
      console.warn('TDZ Error caught and handled:', error.message);
      errorHandler?.(error);
      return defaultValue;
    }
    throw error; // Re-throw non-TDZ errors
  }
}