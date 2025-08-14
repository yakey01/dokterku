import React, { Component, ReactNode } from 'react';

interface ErrorBoundaryState {
  hasError: boolean;
  error?: Error;
  errorInfo?: React.ErrorInfo;
}

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void;
}

/**
 * Enhanced Error Boundary Component
 * Provides bulletproof error handling for React components with specific focus on
 * object access errors and DOM manipulation issues
 */
class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  private retryCount = 0;
  private readonly maxRetries = 3;

  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    // Analyze error type for better handling
    const isNotFoundError = error.name === 'NotFoundError' || 
                           error.message.includes('object can not be found') ||
                           error.message.includes('removeChild') ||
                           error.message.includes('shift_info') ||
                           error.message.includes('Cannot read prop');
    
    console.warn('🚨 ErrorBoundary - Error detected:', {
      name: error.name,
      message: error.message,
      isNotFoundError,
      stack: error.stack?.split('\n').slice(0, 5)
    });

    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('🚨 ErrorBoundary caught error:', {
      error: {
        name: error.name,
        message: error.message,
        stack: error.stack
      },
      errorInfo: {
        componentStack: errorInfo.componentStack
      },
      retryCount: this.retryCount
    });

    this.setState({ errorInfo });

    // Call onError callback if provided
    if (this.props.onError) {
      this.props.onError(error, errorInfo);
    }

    // Store error details for debugging
    try {
      const errorReport = {
        timestamp: new Date().toISOString(),
        error: {
          name: error.name,
          message: error.message,
          stack: error.stack
        },
        errorInfo: {
          componentStack: errorInfo.componentStack
        },
        retryCount: this.retryCount,
        userAgent: navigator.userAgent,
        url: window.location.href
      };
      localStorage.setItem('component_error_report', JSON.stringify(errorReport));
    } catch (e) {
      console.warn('⚠️ Could not store error report');
    }

    // Perform safe DOM cleanup
    this.performSafeDOMCleanup();
  }

  performSafeDOMCleanup = () => {
    try {
      // Remove any orphaned elements that might cause issues
      const orphanedElements = document.querySelectorAll('[data-react-orphan]');
      orphanedElements.forEach(el => {
        try {
          if (el.parentNode && document.contains(el)) {
            el.parentNode.removeChild(el);
          }
        } catch (e) {
          // Ignore individual removal failures
        }
      });

      // Clean up any problematic styles or attributes
      const problemElements = document.querySelectorAll('[style*="position: fixed"], [style*="z-index: 99999"]');
      problemElements.forEach(el => {
        if (!el.closest('#dokter-app')) {
          try {
            if (el.parentNode && document.contains(el)) {
              el.parentNode.removeChild(el);
            }
          } catch (e) {
            // Ignore individual removal failures
          }
        }
      });
    } catch (error) {
      console.warn('⚠️ DOM cleanup failed:', error);
    }
  }

  handleRetry = () => {
    if (this.retryCount < this.maxRetries) {
      this.retryCount++;
      console.log(`🔄 Attempting component retry ${this.retryCount}/${this.maxRetries}`);
      
      // Perform cleanup before retry
      this.performSafeDOMCleanup();
      
      // Reset state after a brief delay
      setTimeout(() => {
        this.setState({ 
          hasError: false, 
          error: undefined, 
          errorInfo: undefined 
        });
      }, 500);
    } else {
      console.log('❌ Max retries reached for component');
    }
  }

  handleReload = () => {
    console.log('🔄 User requested page reload from error boundary');
    window.location.reload();
  }

  render() {
    if (this.state.hasError) {
      // Return custom fallback UI if provided
      if (this.props.fallback) {
        return this.props.fallback;
      }

      const canRetry = this.retryCount < this.maxRetries;
      const errorType = this.state.error?.name === 'NotFoundError' ? 'Object Access Error' : 'Component Error';
      
      return (
        <div className="min-h-[200px] bg-gradient-to-br from-red-900/20 to-orange-900/20 rounded-2xl border border-red-500/20 p-6 text-white">
          <div className="text-center">
            <div className="text-4xl mb-4">⚠️</div>
            <h3 className="text-lg font-bold text-red-300 mb-2">
              {errorType}
            </h3>
            <p className="text-gray-300 mb-4 text-sm">
              {this.state.error?.name === 'NotFoundError' 
                ? 'Terjadi kesalahan dalam mengakses data. Sistem sedang memperbaiki otomatis.'
                : 'Komponen mengalami kesalahan. Error telah dicatat dan akan diperbaiki.'}
            </p>
            
            {/* Retry counter */}
            {this.retryCount > 0 && (
              <div className="bg-orange-500/20 border border-orange-500/30 rounded-lg p-2 mb-4 text-sm">
                🔄 Percobaan: {this.retryCount}/{this.maxRetries}
              </div>
            )}

            <div className="flex gap-2 justify-center">
              {canRetry && (
                <button 
                  onClick={this.handleRetry}
                  className="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                  🔄 Coba Lagi ({this.maxRetries - this.retryCount} tersisa)
                </button>
              )}
              
              <button 
                onClick={this.handleReload}
                className="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                🔄 Muat Ulang
              </button>
            </div>
            
            {/* Error details for debugging */}
            <details className="mt-4 text-left">
              <summary className="cursor-pointer text-sm text-gray-400 hover:text-gray-300">
                Detail Error (untuk debugging)
              </summary>
              <div className="mt-2 p-3 bg-black/30 rounded-lg text-xs font-mono">
                <div><strong>Type:</strong> {this.state.error?.name}</div>
                <div><strong>Message:</strong> {this.state.error?.message}</div>
                <div><strong>Time:</strong> {new Date().toLocaleString('id-ID')}</div>
                {this.state.error?.stack && (
                  <div className="mt-2">
                    <strong>Stack:</strong>
                    <pre className="mt-1 text-xs overflow-auto">
                      {this.state.error.stack.split('\n').slice(0, 5).join('\n')}
                    </pre>
                  </div>
                )}
              </div>
            </details>
          </div>
        </div>
      );
    }
    
    return this.props.children;
  }
}

export default ErrorBoundary;