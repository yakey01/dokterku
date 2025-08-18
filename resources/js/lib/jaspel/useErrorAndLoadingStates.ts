/**
 * React Hooks for Error and Loading State Management
 * Integration hooks for standardized error and loading states
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { 
  JaspelVariant,
  JaspelError,
  ErrorState,
  LoadingState,
  JaspelStateManager,
  getJaspelStateManager
} from './errorHandler';

/**
 * Hook for managing error states in Jaspel components
 */
export const useJaspelErrors = (variant: JaspelVariant) => {
  const [errorState, setErrorState] = useState<ErrorState>({
    hasError: false,
    errors: [],
    errorCount: 0,
    recoverySuggestions: []
  });

  const stateManager = useRef(getJaspelStateManager(variant));

  useEffect(() => {
    const unsubscribe = stateManager.current.errorManager.subscribe((newErrorState) => {
      setErrorState(newErrorState);
    });

    // Initialize with current state
    setErrorState(stateManager.current.errorManager.getErrorState());

    return unsubscribe;
  }, []);

  const addError = useCallback((error: any, context?: Record<string, any>) => {
    return stateManager.current.handleError(error, context);
  }, []);

  const removeError = useCallback((errorId: string) => {
    stateManager.current.clearError(errorId);
  }, []);

  const clearAllErrors = useCallback(() => {
    stateManager.current.clearAllErrors();
  }, []);

  const retryError = useCallback((errorId: string, retryFunction: () => Promise<void>) => {
    return stateManager.current.retryError(errorId, retryFunction);
  }, []);

  const getErrorsByCategory = useCallback((category: string) => {
    return stateManager.current.errorManager.getErrorsByCategory(category as any);
  }, []);

  const getErrorsBySeverity = useCallback((severity: string) => {
    return stateManager.current.errorManager.getErrorsBySeverity(severity as any);
  }, []);

  return {
    errorState,
    addError,
    removeError,
    clearAllErrors,
    retryError,
    getErrorsByCategory,
    getErrorsBySeverity,
    hasErrors: errorState.hasError,
    lastError: errorState.lastError,
    errorCount: errorState.errorCount,
    recoverySuggestions: errorState.recoverySuggestions
  };
};

/**
 * Hook for managing loading states in Jaspel components
 */
export const useJaspelLoading = (variant: JaspelVariant) => {
  const [loadingStates, setLoadingStates] = useState<Map<string, LoadingState>>(new Map());

  const stateManager = useRef(getJaspelStateManager(variant));

  useEffect(() => {
    const unsubscribe = stateManager.current.loadingManager.subscribe((newLoadingStates) => {
      setLoadingStates(new Map(newLoadingStates));
    });

    // Initialize with current state
    setLoadingStates(stateManager.current.loadingManager.getAllLoadingStates());

    return unsubscribe;
  }, []);

  const setLoading = useCallback((
    key: string, 
    isLoading: boolean, 
    options?: {
      message?: string;
      progress?: number;
      stage?: string;
      estimatedDuration?: number;
    }
  ) => {
    stateManager.current.setLoading(key, isLoading, options);
  }, []);

  const updateProgress = useCallback((key: string, progress: number, stage?: string) => {
    stateManager.current.loadingManager.updateProgress(key, progress, stage);
  }, []);

  const clearLoading = useCallback((key?: string) => {
    if (key) {
      stateManager.current.loadingManager.clearLoading(key);
    } else {
      stateManager.current.clearAllLoading();
    }
  }, []);

  const isLoading = useCallback((key?: string) => {
    return stateManager.current.loadingManager.isLoading(key);
  }, []);

  const getLoadingState = useCallback((key: string) => {
    return stateManager.current.loadingManager.getLoadingState(key);
  }, []);

  const hasAnyLoading = loadingStates.size > 0;
  const loadingCount = loadingStates.size;

  return {
    loadingStates,
    setLoading,
    updateProgress,
    clearLoading,
    isLoading,
    getLoadingState,
    hasAnyLoading,
    loadingCount
  };
};

/**
 * Combined hook for both error and loading state management
 */
export const useJaspelState = (variant: JaspelVariant) => {
  const errors = useJaspelErrors(variant);
  const loading = useJaspelLoading(variant);

  const stateManager = useRef(getJaspelStateManager(variant));

  // Combined operations
  const handleAsyncOperation = useCallback(async <T>(
    operationKey: string,
    operation: () => Promise<T>,
    options?: {
      loadingMessage?: string;
      errorContext?: Record<string, any>;
      retryable?: boolean;
      onProgress?: (progress: number, stage?: string) => void;
    }
  ): Promise<T> => {
    const { loadingMessage, errorContext, onProgress } = options || {};

    try {
      // Start loading
      loading.setLoading(operationKey, true, { 
        message: loadingMessage || 'Processing...' 
      });

      // Set up progress callback
      if (onProgress) {
        const progressHandler = (progress: number, stage?: string) => {
          loading.updateProgress(operationKey, progress, stage);
          onProgress(progress, stage);
        };
        
        // Store progress handler for potential use
        (operation as any).onProgress = progressHandler;
      }

      // Execute operation
      const result = await operation();

      // Clear loading on success
      loading.clearLoading(operationKey);
      
      return result;

    } catch (error) {
      // Clear loading
      loading.clearLoading(operationKey);
      
      // Add error
      const jaspelError = errors.addError(error, {
        operationKey,
        ...errorContext
      });

      // Re-throw for handling by caller
      throw jaspelError;
    }
  }, [loading, errors]);

  const retryOperation = useCallback(async <T>(
    errorId: string,
    operation: () => Promise<T>
  ): Promise<T> => {
    return errors.retryError(errorId, operation) as Promise<T>;
  }, [errors]);

  const clearAll = useCallback(() => {
    errors.clearAllErrors();
    loading.clearLoading();
  }, [errors, loading]);

  return {
    // Error management
    ...errors,
    
    // Loading management
    ...loading,

    // Combined operations
    handleAsyncOperation,
    retryOperation,
    clearAll,

    // State summary
    hasActivity: errors.hasErrors || loading.hasAnyLoading,
    isIdle: !errors.hasErrors && !loading.hasAnyLoading
  };
};

/**
 * Hook for API operation management with automatic error and loading handling
 */
export const useJaspelApiOperation = <T>(
  variant: JaspelVariant,
  operationName: string
) => {
  const { handleAsyncOperation, retryOperation, ...state } = useJaspelState(variant);
  const [data, setData] = useState<T | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);

  const execute = useCallback(async (
    operation: () => Promise<T>,
    options?: {
      loadingMessage?: string;
      errorContext?: Record<string, any>;
      onProgress?: (progress: number, stage?: string) => void;
      onSuccess?: (data: T) => void;
      onError?: (error: JaspelError) => void;
    }
  ): Promise<T> => {
    try {
      setIsSuccess(false);
      
      const result = await handleAsyncOperation(operationName, operation, {
        loadingMessage: options?.loadingMessage,
        errorContext: options?.errorContext,
        onProgress: options?.onProgress
      });

      setData(result);
      setIsSuccess(true);
      
      if (options?.onSuccess) {
        options.onSuccess(result);
      }

      return result;

    } catch (error) {
      setIsSuccess(false);
      
      if (options?.onError && error instanceof Object && 'id' in error) {
        options.onError(error as JaspelError);
      }

      throw error;
    }
  }, [handleAsyncOperation, operationName]);

  const retry = useCallback(async (
    errorId: string,
    operation: () => Promise<T>
  ): Promise<T> => {
    const result = await retryOperation(errorId, operation);
    setData(result);
    setIsSuccess(true);
    return result;
  }, [retryOperation]);

  const reset = useCallback(() => {
    setData(null);
    setIsSuccess(false);
    state.clearLoading(operationName);
    // Don't clear errors automatically - let user decide
  }, [state, operationName]);

  return {
    // Operation results
    data,
    isSuccess,
    
    // Operation methods
    execute,
    retry,
    reset,

    // State information
    isLoading: state.isLoading(operationName),
    loadingState: state.getLoadingState(operationName),
    hasErrors: state.hasErrors,
    lastError: state.lastError,
    
    // State management
    clearErrors: state.clearAllErrors,
    removeError: state.removeError
  };
};

/**
 * Hook for automatic retry logic with exponential backoff
 */
export const useJaspelAutoRetry = (
  variant: JaspelVariant,
  maxRetries: number = 3,
  baseDelay: number = 1000
) => {
  const { addError, retryError } = useJaspelErrors(variant);
  const retryTimeouts = useRef<Map<string, NodeJS.Timeout>>(new Map());

  const executeWithAutoRetry = useCallback(async <T>(
    operation: () => Promise<T>,
    operationKey: string,
    options?: {
      retryCondition?: (error: any) => boolean;
      onRetry?: (attempt: number, error: any) => void;
      onMaxRetriesReached?: (error: any) => void;
    }
  ): Promise<T> => {
    let lastError: any;
    
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        return await operation();
      } catch (error) {
        lastError = error;
        
        // Check if we should retry
        const shouldRetry = attempt < maxRetries && 
          (!options?.retryCondition || options.retryCondition(error));

        if (!shouldRetry) {
          if (attempt === maxRetries && options?.onMaxRetriesReached) {
            options.onMaxRetriesReached(error);
          }
          break;
        }

        // Calculate delay with exponential backoff
        const delay = baseDelay * Math.pow(2, attempt);
        
        if (options?.onRetry) {
          options.onRetry(attempt + 1, error);
        }

        // Wait before retry
        await new Promise(resolve => {
          const timeout = setTimeout(resolve, delay);
          retryTimeouts.current.set(`${operationKey}_${attempt}`, timeout);
        });
      }
    }

    // If we reach here, all retries failed
    throw addError(lastError, { 
      operationKey, 
      totalAttempts: maxRetries + 1 
    });
  }, [maxRetries, baseDelay, addError]);

  // Cleanup timeouts on unmount
  useEffect(() => {
    return () => {
      retryTimeouts.current.forEach(timeout => clearTimeout(timeout));
      retryTimeouts.current.clear();
    };
  }, []);

  return {
    executeWithAutoRetry
  };
};