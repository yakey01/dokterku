/**
 * Enhanced Error Boundary - TDZ-Safe React Error Handling
 * Comprehensive error boundary with recovery mechanisms and user feedback
 */

import React, { Component, ReactNode, ErrorInfo } from 'react';

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
    errorInfo: ErrorInfo | null;
    errorId: string | null;
    retryCount: number;
    isRecovering: boolean;
}

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ReactNode;
    onError?: (error: Error, errorInfo: ErrorInfo, errorId: string) => void;
    maxRetries?: number;
    enableRecovery?: boolean;
    showErrorDetails?: boolean;
    className?: string;
}

interface ErrorDetails {
    message: string;
    stack?: string;
    componentStack?: string;
    timestamp: number;
    userAgent: string;
    url: string;
    errorBoundary: string;
}

export class ErrorBoundaryEnhanced extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    private retryTimeoutId: number | null = null;
    private errorReportingEnabled: boolean = true;

    constructor(props: ErrorBoundaryProps) {
        super(props);
        
        this.state = {
            hasError: false,
            error: null,
            errorInfo: null,
            errorId: null,
            retryCount: 0,
            isRecovering: false
        };
    }

    static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
        // TDZ-safe error state update
        return {
            hasError: true,
            error,
            errorId: `error_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`
        };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        // Enhanced error information collection
        this.setState({
            errorInfo,
            error
        });

        // Log error with comprehensive details
        this.logError(error, errorInfo);

        // Report to error tracking service if available
        this.reportError(error, errorInfo);

        // Notify parent component
        if (this.props.onError) {
            this.props.onError(error, errorInfo, this.state.errorId!);
        }
    }

    componentDidUpdate(prevProps: ErrorBoundaryProps, prevState: ErrorBoundaryState) {
        // Detect when children change after error state
        if (prevState.hasError && !this.state.hasError) {
            console.log('✅ Error boundary recovered successfully');
        }

        // Auto-recovery mechanism
        if (this.state.hasError && !this.state.isRecovering && 
            this.props.enableRecovery !== false && 
            this.state.retryCount < (this.props.maxRetries || 3)) {
            
            this.scheduleRecovery();
        }
    }

    componentWillUnmount() {
        if (this.retryTimeoutId) {
            clearTimeout(this.retryTimeoutId);
        }
    }

    /**
     * Log error with comprehensive details
     */
    private logError(error: Error, errorInfo: ErrorInfo): void {
        const errorDetails: ErrorDetails = {
            message: error.message,
            stack: error.stack,
            componentStack: errorInfo.componentStack,
            timestamp: Date.now(),
            userAgent: navigator.userAgent,
            url: window.location.href,
            errorBoundary: this.constructor.name
        };

        console.group('🚨 React Error Boundary Caught Error');
        console.error('Error:', error);
        console.error('Component Stack:', errorInfo.componentStack);
        console.table(errorDetails);
        console.groupEnd();
    }

    /**
     * Report error to external service
     */
    private reportError(error: Error, errorInfo: ErrorInfo): void {
        if (!this.errorReportingEnabled) return;

        try {
            // Could integrate with services like Sentry, LogRocket, etc.
            const errorReport = {
                errorId: this.state.errorId,
                message: error.message,
                stack: error.stack,
                componentStack: errorInfo.componentStack,
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent,
                userId: (window as any).currentUserId || 'anonymous'
            };

            // Send to error reporting service
            if (typeof fetch !== 'undefined') {
                fetch('/api/error-report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(errorReport)
                }).catch(reportingError => {
                    console.warn('Failed to report error:', reportingError);
                });
            }
        } catch (reportingError) {
            console.warn('Error reporting failed:', reportingError);
        }
    }

    /**
     * Schedule automatic recovery attempt
     */
    private scheduleRecovery(): void {
        const delay = Math.min(1000 * Math.pow(2, this.state.retryCount), 10000); // Exponential backoff
        
        console.log(`🔄 Scheduling recovery attempt #${this.state.retryCount + 1} in ${delay}ms`);
        
        this.setState({ isRecovering: true });
        
        this.retryTimeoutId = window.setTimeout(() => {
            this.attemptRecovery();
        }, delay);
    }

    /**
     * Attempt to recover from error
     */
    private attemptRecovery = (): void => {
        console.log(`🔄 Attempting error recovery #${this.state.retryCount + 1}`);
        
        this.setState(prevState => ({
            hasError: false,
            error: null,
            errorInfo: null,
            errorId: null,
            retryCount: prevState.retryCount + 1,
            isRecovering: false
        }));
    };

    /**
     * Manual retry handler
     */
    private handleManualRetry = (): void => {
        console.log('🔄 Manual retry requested');
        this.attemptRecovery();
    };

    /**
     * Reload page handler
     */
    private handleReload = (): void => {
        console.log('🔄 Page reload requested');
        window.location.reload();
    };

    /**
     * Report issue handler
     */
    private handleReportIssue = (): void => {
        const subject = encodeURIComponent(`Error Report: ${this.state.error?.message}`);
        const body = encodeURIComponent(`
Error ID: ${this.state.errorId}
Error Message: ${this.state.error?.message}
URL: ${window.location.href}
Timestamp: ${new Date().toISOString()}

Please describe what you were doing when this error occurred:
`);
        
        window.open(`mailto:support@dokterku.com?subject=${subject}&body=${body}`);
    };

    /**
     * Render error fallback UI
     */
    private renderErrorFallback(): ReactNode {
        const { error, retryCount, isRecovering } = this.state;
        const maxRetries = this.props.maxRetries || 3;
        const canRetry = retryCount < maxRetries;

        return (
            <div className={`error-boundary-fallback ${this.props.className || ''}`}>
                <div className="error-boundary-container">
                    <div className="error-boundary-header">
                        <h2 className="error-boundary-title">
                            🚨 Terjadi Kesalahan Aplikasi
                        </h2>
                        <p className="error-boundary-subtitle">
                            Maaf, terjadi kesalahan yang tidak terduga. Tim kami telah diberitahu.
                        </p>
                    </div>

                    {isRecovering && (
                        <div className="error-boundary-recovery">
                            <div className="recovery-spinner">🔄</div>
                            <p>Mencoba memulihkan aplikasi...</p>
                        </div>
                    )}

                    <div className="error-boundary-actions">
                        {canRetry && !isRecovering && (
                            <button 
                                onClick={this.handleManualRetry}
                                className="btn-retry"
                            >
                                🔄 Coba Lagi ({maxRetries - retryCount} tersisa)
                            </button>
                        )}

                        <button 
                            onClick={this.handleReload}
                            className="btn-reload"
                        >
                            🔃 Muat Ulang Halaman
                        </button>

                        <button 
                            onClick={this.handleReportIssue}
                            className="btn-report"
                        >
                            📧 Laporkan Masalah
                        </button>
                    </div>

                    {this.props.showErrorDetails && error && (
                        <details className="error-boundary-details">
                            <summary>Detail Teknis (untuk pengembang)</summary>
                            <div className="error-details">
                                <div className="error-detail-item">
                                    <strong>Error ID:</strong> {this.state.errorId}
                                </div>
                                <div className="error-detail-item">
                                    <strong>Message:</strong> {error.message}
                                </div>
                                <div className="error-detail-item">
                                    <strong>Retry Count:</strong> {retryCount}
                                </div>
                                {error.stack && (
                                    <div className="error-detail-item">
                                        <strong>Stack:</strong>
                                        <pre>{error.stack}</pre>
                                    </div>
                                )}
                                {this.state.errorInfo?.componentStack && (
                                    <div className="error-detail-item">
                                        <strong>Component Stack:</strong>
                                        <pre>{this.state.errorInfo.componentStack}</pre>
                                    </div>
                                )}
                            </div>
                        </details>
                    )}

                    <div className="error-boundary-footer">
                        <p className="error-boundary-help">
                            Jika masalah berlanjut, hubungi support@dokterku.com
                        </p>
                    </div>
                </div>

                <style jsx>{`
                    .error-boundary-fallback {
                        padding: 20px;
                        margin: 20px;
                        border: 1px solid #f5c6cb;
                        border-radius: 8px;
                        background-color: #f8d7da;
                        color: #721c24;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
                    }

                    .error-boundary-container {
                        max-width: 600px;
                        margin: 0 auto;
                        text-align: center;
                    }

                    .error-boundary-header {
                        margin-bottom: 20px;
                    }

                    .error-boundary-title {
                        font-size: 1.5em;
                        margin: 0 0 10px 0;
                    }

                    .error-boundary-subtitle {
                        margin: 0 0 20px 0;
                        opacity: 0.8;
                    }

                    .error-boundary-recovery {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        margin: 20px 0;
                        padding: 10px;
                        background-color: #fff3cd;
                        border: 1px solid #ffeaa7;
                        border-radius: 4px;
                        color: #856404;
                    }

                    .recovery-spinner {
                        animation: spin 1s linear infinite;
                    }

                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }

                    .error-boundary-actions {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        align-items: center;
                        margin: 20px 0;
                    }

                    .error-boundary-actions button {
                        padding: 10px 20px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                        min-width: 200px;
                        transition: all 0.2s ease;
                    }

                    .btn-retry {
                        background-color: #28a745;
                        color: white;
                    }

                    .btn-retry:hover {
                        background-color: #218838;
                    }

                    .btn-reload {
                        background-color: #007bff;
                        color: white;
                    }

                    .btn-reload:hover {
                        background-color: #0056b3;
                    }

                    .btn-report {
                        background-color: #6c757d;
                        color: white;
                    }

                    .btn-report:hover {
                        background-color: #545b62;
                    }

                    .error-boundary-details {
                        margin: 20px 0;
                        text-align: left;
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        padding: 15px;
                    }

                    .error-boundary-details summary {
                        cursor: pointer;
                        font-weight: bold;
                        margin-bottom: 10px;
                    }

                    .error-details {
                        margin-top: 10px;
                    }

                    .error-detail-item {
                        margin: 10px 0;
                    }

                    .error-detail-item strong {
                        display: inline-block;
                        width: 140px;
                        vertical-align: top;
                    }

                    .error-detail-item pre {
                        background-color: #2d3748;
                        color: #e2e8f0;
                        padding: 10px;
                        border-radius: 4px;
                        overflow-x: auto;
                        font-size: 12px;
                        margin: 5px 0;
                    }

                    .error-boundary-footer {
                        margin-top: 20px;
                        padding-top: 20px;
                        border-top: 1px solid #dee2e6;
                    }

                    .error-boundary-help {
                        margin: 0;
                        font-size: 14px;
                        opacity: 0.8;
                    }

                    @media (max-width: 600px) {
                        .error-boundary-fallback {
                            margin: 10px;
                            padding: 15px;
                        }

                        .error-boundary-actions {
                            flex-direction: column;
                        }

                        .error-boundary-actions button {
                            width: 100%;
                            min-width: auto;
                        }
                    }
                `}</style>
            </div>
        );
    }

    render(): ReactNode {
        if (this.state.hasError) {
            // Use custom fallback if provided, otherwise use built-in
            return this.props.fallback || this.renderErrorFallback();
        }

        return this.props.children;
    }
}

// Higher-Order Component wrapper
export function withErrorBoundary<P extends object>(
    Component: React.ComponentType<P>,
    errorBoundaryProps?: Omit<ErrorBoundaryProps, 'children'>
) {
    const WrappedComponent = (props: P) => (
        <ErrorBoundaryEnhanced {...errorBoundaryProps}>
            <Component {...props} />
        </ErrorBoundaryEnhanced>
    );

    WrappedComponent.displayName = `withErrorBoundary(${Component.displayName || Component.name})`;
    return WrappedComponent;
}

// Hook for programmatic error handling
export function useErrorHandler() {
    return (error: Error, errorInfo?: any) => {
        console.error('🚨 Programmatic error caught:', error, errorInfo);
        
        // Could integrate with error boundary or reporting service
        if (typeof (window as any).errorBoundaryInstance !== 'undefined') {
            (window as any).errorBoundaryInstance.handleError(error, errorInfo);
        }
    };
}

// Export default
export default ErrorBoundaryEnhanced;