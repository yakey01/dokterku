/**
 * Error Boundary and Loading Components
 * Standardized error handling and loading states for schedule components
 */

import React, { Component, ReactNode } from 'react';
import { Card, CardContent } from '../../ui/card';
import { Button } from '../../ui/button';
import { 
  AlertCircle, 
  RefreshCw, 
  Wifi, 
  Calendar,
  Loader2,
  CheckCircle,
  XCircle,
  AlertTriangle
} from 'lucide-react';

// Error Boundary Types
interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: any;
}

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: any) => void;
  variant?: 'schedule' | 'generic';
}

/**
 * Schedule-specific Error Boundary
 */
export class ScheduleErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return {
      hasError: true,
      error,
      errorInfo: null
    };
  }

  componentDidCatch(error: Error, errorInfo: any) {
    this.setState({
      error,
      errorInfo
    });

    // Log error
    console.error('ScheduleErrorBoundary caught an error:', error, errorInfo);

    // Call onError prop if provided
    if (this.props.onError) {
      this.props.onError(error, errorInfo);
    }
  }

  handleRetry = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null
    });
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <ScheduleErrorFallback
          error={this.state.error}
          onRetry={this.handleRetry}
          variant={this.props.variant}
        />
      );
    }

    return this.props.children;
  }
}

/**
 * Error Fallback Component
 */
interface ErrorFallbackProps {
  error: Error | null;
  onRetry?: () => void;
  variant?: 'schedule' | 'generic';
  title?: string;
  description?: string;
}

export const ScheduleErrorFallback: React.FC<ErrorFallbackProps> = ({
  error,
  onRetry,
  variant = 'schedule',
  title,
  description
}) => {
  const isScheduleError = variant === 'schedule';
  const errorTitle = title || (isScheduleError ? 'Gagal Memuat Jadwal' : 'Terjadi Kesalahan');
  const errorDescription = description || error?.message || 'Terjadi kesalahan yang tidak terduga';

  return (
    <Card 
      className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced"
      role="alert"
      aria-labelledby="error-title"
    >
      <CardContent className="p-6 text-center">
        <div className="text-red-500 mb-4">
          <AlertCircle className="w-16 h-16 mx-auto mb-4" aria-hidden="true" />
          <h3 id="error-title" className="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">
            {errorTitle}
          </h3>
          <p className="text-red-500 dark:text-red-300 text-sm mb-4">
            {errorDescription}
          </p>
        </div>

        {/* Error details in development */}
        {process.env.NODE_ENV === 'development' && error && (
          <details className="text-left bg-gray-100 dark:bg-gray-800 rounded p-3 mb-4 text-xs">
            <summary className="cursor-pointer font-medium">Technical Details</summary>
            <pre className="mt-2 whitespace-pre-wrap text-gray-700 dark:text-gray-300">
              {error.stack}
            </pre>
          </details>
        )}

        <div className="flex gap-3 justify-center">
          {onRetry && (
            <Button 
              variant="outline" 
              onClick={onRetry}
              className="gap-2 btn-error-accessible focus-outline"
              aria-label="Coba muat ulang"
            >
              <RefreshCw className="w-4 h-4" aria-hidden="true" />
              Coba Lagi
            </Button>
          )}
          
          <Button 
            variant="ghost" 
            onClick={() => window.location.reload()}
            className="gap-2"
            aria-label="Muat ulang halaman"
          >
            <RefreshCw className="w-4 h-4" aria-hidden="true" />
            Refresh Halaman
          </Button>
        </div>
      </CardContent>
    </Card>
  );
};

/**
 * Network Error Component
 */
export const NetworkError: React.FC<{ onRetry?: () => void }> = ({ onRetry }) => (
  <Card className="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700">
    <CardContent className="p-4 text-center">
      <Wifi className="w-12 h-12 text-orange-500 mx-auto mb-3" aria-hidden="true" />
      <h3 className="font-medium text-orange-800 dark:text-orange-200 mb-2">
        Masalah Koneksi
      </h3>
      <p className="text-sm text-orange-600 dark:text-orange-300 mb-3">
        Tidak dapat terhubung ke server. Periksa koneksi internet Anda.
      </p>
      {onRetry && (
        <Button 
          variant="outline" 
          size="sm"
          onClick={onRetry}
          className="gap-2 border-orange-300 text-orange-700 hover:bg-orange-100"
        >
          <RefreshCw className="w-4 h-4" />
          Coba Lagi
        </Button>
      )}
    </CardContent>
  </Card>
);

/**
 * Loading States
 */
export const ScheduleLoader: React.FC<{
  variant?: 'full' | 'cards' | 'minimal';
  message?: string;
}> = ({ 
  variant = 'full',
  message = 'Memuat jadwal...' 
}) => {
  if (variant === 'minimal') {
    return (
      <div className="flex items-center justify-center py-4">
        <Loader2 className="w-5 h-5 animate-spin text-blue-500 mr-2" aria-hidden="true" />
        <span className="text-sm text-gray-600 dark:text-gray-300">{message}</span>
      </div>
    );
  }

  if (variant === 'cards') {
    return (
      <div className="space-y-4">
        {[...Array(3)].map((_, i) => (
          <Card key={i} className="animate-pulse">
            <CardContent className="p-6">
              <div className="flex justify-between items-start mb-4">
                <div className="space-y-2">
                  <div className="h-5 bg-gray-200 dark:bg-gray-700 rounded w-48"></div>
                  <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                </div>
                <div className="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
              </div>
              
              <div className="space-y-3">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                  <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                  <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    );
  }

  // Full variant
  return (
    <div 
      className="flex flex-col items-center justify-center py-12"
      role="status"
      aria-live="polite"
      aria-label={message}
    >
      <div className="relative">
        <Calendar className="w-16 h-16 text-blue-300 dark:text-blue-600" aria-hidden="true" />
        <Loader2 
          className="w-6 h-6 text-blue-500 animate-spin absolute bottom-0 right-0" 
          aria-hidden="true"
        />
      </div>
      <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
        {message}
      </h3>
      <p className="text-sm text-gray-500 dark:text-gray-400 text-center max-w-md">
        Sedang mengambil data jadwal terbaru dengan informasi presensi...
      </p>
    </div>
  );
};

/**
 * Empty State Component
 */
export const EmptyScheduleState: React.FC<{
  variant?: 'schedule' | 'search' | 'filter';
  title?: string;
  description?: string;
  actionButton?: ReactNode;
}> = ({ 
  variant = 'schedule',
  title,
  description,
  actionButton
}) => {
  const getContent = () => {
    switch (variant) {
      case 'search':
        return {
          icon: <AlertTriangle className="w-12 h-12 text-yellow-400" />,
          title: title || 'Tidak Ada Hasil',
          description: description || 'Tidak ditemukan jadwal yang sesuai dengan pencarian Anda'
        };
      case 'filter':
        return {
          icon: <XCircle className="w-12 h-12 text-gray-400" />,
          title: title || 'Tidak Ada Data',
          description: description || 'Tidak ada jadwal yang sesuai dengan filter yang dipilih'
        };
      default:
        return {
          icon: <Calendar className="w-12 h-12 text-gray-400" />,
          title: title || 'Belum Ada Jadwal',
          description: description || 'Hubungi administrator untuk penjadwalan jaga'
        };
    }
  };

  const content = getContent();

  return (
    <Card 
      className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced"
      role="status"
      aria-labelledby="empty-title"
    >
      <CardContent className="p-8 text-center">
        <div className="mb-4">
          {content.icon}
        </div>
        <h3 id="empty-title" className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">
          {content.title}
        </h3>
        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-md mx-auto">
          {content.description}
        </p>
        {actionButton}
      </CardContent>
    </Card>
  );
};

/**
 * Success Message Component
 */
export const SuccessMessage: React.FC<{
  title: string;
  description?: string;
  autoHide?: boolean;
  duration?: number;
  onHide?: () => void;
}> = ({ 
  title, 
  description, 
  autoHide = true, 
  duration = 5000,
  onHide 
}) => {
  React.useEffect(() => {
    if (autoHide && onHide) {
      const timer = setTimeout(onHide, duration);
      return () => clearTimeout(timer);
    }
  }, [autoHide, duration, onHide]);

  return (
    <Card className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700">
      <CardContent className="p-4">
        <div className="flex items-start gap-3">
          <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" />
          <div>
            <h4 className="font-medium text-green-800 dark:text-green-200">
              {title}
            </h4>
            {description && (
              <p className="text-sm text-green-600 dark:text-green-300 mt-1">
                {description}
              </p>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

/**
 * Inline Error Component
 */
export const InlineError: React.FC<{
  message: string;
  onDismiss?: () => void;
  variant?: 'error' | 'warning';
}> = ({ message, onDismiss, variant = 'error' }) => {
  const isError = variant === 'error';
  
  return (
    <div className={`p-3 rounded-lg border flex items-center justify-between ${
      isError 
        ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-700 dark:text-red-300'
        : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700 text-yellow-700 dark:text-yellow-300'
    }`}>
      <div className="flex items-center gap-2">
        {isError ? (
          <XCircle className="w-4 h-4" />
        ) : (
          <AlertTriangle className="w-4 h-4" />
        )}
        <span className="text-sm font-medium">{message}</span>
      </div>
      {onDismiss && (
        <Button
          variant="ghost"
          size="sm"
          onClick={onDismiss}
          className="h-6 w-6 p-0 hover:bg-transparent"
          aria-label="Tutup pesan"
        >
          <XCircle className="w-4 h-4" />
        </Button>
      )}
    </div>
  );
};