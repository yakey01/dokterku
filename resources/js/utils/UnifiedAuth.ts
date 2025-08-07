/**
 * Unified Authentication Utility
 * Handles both session-based and token-based authentication seamlessly
 */

export interface AuthToken {
  token: string;
  type: 'Bearer';
  expires_at?: string;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: string[];
}

class UnifiedAuth {
  private static instance: UnifiedAuth;

  static getInstance(): UnifiedAuth {
    if (!UnifiedAuth.instance) {
      UnifiedAuth.instance = new UnifiedAuth();
    }
    return UnifiedAuth.instance;
  }

  /**
   * Get authentication headers for API requests
   */
  getAuthHeaders(): HeadersInit {
    const headers: HeadersInit = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    // Try to get API token first
    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
  }

  /**
   * Get stored API token with enhanced extraction
   */
  getToken(): string | null {
    // Check multiple possible token storage keys for compatibility
    const apiToken = localStorage.getItem('dokterku_auth_token') || 
                     sessionStorage.getItem('dokterku_auth_token') ||
                     localStorage.getItem('api_token') || 
                     sessionStorage.getItem('api_token');
    
    // If we have a stored token, validate and return it
    if (apiToken && apiToken.trim()) {
      return apiToken.trim();
    }
    
    // Try to extract token from meta tag
    const metaToken = this.extractTokenFromMeta();
    if (metaToken) {
      // Store the meta token for future use
      this.setToken(metaToken);
      console.log('✅ Token extracted and stored from meta tag');
      return metaToken;
    }
    
    return null;
  }
  
  /**
   * Extract token from meta tag with enhanced validation
   */
  private extractTokenFromMeta(): string | null {
    try {
      const metaTokenElement = document.querySelector('meta[name="api-token"]');
      if (!metaTokenElement) {
        console.warn('⚠️ No api-token meta tag found');
        return null;
      }
      
      const metaToken = metaTokenElement.getAttribute('content');
      if (!metaToken || !metaToken.trim()) {
        console.warn('⚠️ api-token meta tag is empty');
        return null;
      }
      
      const cleanToken = metaToken.trim();
      
      // Basic token format validation
      if (cleanToken.length < 10) {
        console.warn('⚠️ api-token appears to be too short:', cleanToken.substring(0, 5) + '...');
        return null;
      }
      
      console.log('✅ Valid token extracted from meta tag:', cleanToken.substring(0, 10) + '...');
      return cleanToken;
    } catch (error) {
      console.error('🔥 Error extracting token from meta tag:', error);
      return null;
    }
  }

  /**
   * Store API token
   */
  setToken(token: string, persistent: boolean = true): void {
    if (persistent) {
      localStorage.setItem('dokterku_auth_token', token);
      localStorage.setItem('api_token', token); // Keep both for compatibility
    } else {
      sessionStorage.setItem('dokterku_auth_token', token);
      sessionStorage.setItem('api_token', token); // Keep both for compatibility
    }
  }

  /**
   * Remove stored token
   */
  clearToken(): void {
    localStorage.removeItem('dokterku_auth_token');
    sessionStorage.removeItem('dokterku_auth_token');
    localStorage.removeItem('api_token');
    sessionStorage.removeItem('api_token');
  }

  /**
   * Check if user is authenticated (session or token)
   */
  async isAuthenticated(): Promise<boolean> {
    try {
      // First check if we have a token
      const token = this.getToken();
      if (!token) {
        return false;
      }
      
      // Try the V2 API first
      const response = await this.makeRequest('/api/v2/auth/me');
      if (response.ok) {
        return true;
      }
      
      // Fallback to session-based auth check
      const sessionResponse = await this.makeRequest('/api/user');
      return sessionResponse.ok;
    } catch {
      return false;
    }
  }

  /**
   * Login with email/password and get token
   */
  async login(email: string, password: string, deviceId?: string): Promise<{success: boolean, data?: any, error?: string}> {
    try {
      const response = await fetch('/api/v2/auth/login', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          login: email,
          password: password,
          device_id: deviceId || `web-${Date.now()}`,
          device_name: 'Web Browser',
          client_type: 'web_app',
          device_type: 'desktop',
          platform: 'web'
        })
      });

      const result = await response.json();

      if (response.ok && result.success && result.data?.authentication?.token) {
        // Store the access token from the new API response structure
        this.setToken(result.data.authentication.token);
        return { success: true, data: result.data };
      } else if (response.ok && result.success && result.data?.token) {
        // Fallback for old API structure
        this.setToken(result.data.token);
        return { success: true, data: result.data };
      } else {
        return { success: false, error: result.message || 'Login failed' };
      }
    } catch (error) {
      console.error('Login error:', error);
      return { success: false, error: 'Network error during login' };
    }
  }

  /**
   * Logout and clear tokens
   */
  async logout(): Promise<void> {
    try {
      await this.makeRequest('/api/v2/auth/logout', { method: 'POST' });
    } catch {
      // Ignore logout errors
    } finally {
      this.clearToken();
    }
  }

  /**
   * Make authenticated request
   */
  async makeRequest(url: string, options: RequestInit = {}): Promise<Response> {
    const headers = {
      ...this.getAuthHeaders(),
      ...options.headers,
    };

    return fetch(url, {
      ...options,
      headers,
      credentials: 'include', // Include cookies for session auth
    });
  }

  /**
   * Make authenticated request and return JSON with enhanced error handling
   */
  async makeJsonRequest<T = any>(url: string, options: RequestInit = {}, retryCount = 0): Promise<T> {
    const maxRetries = 2;
    
    try {
      const response = await this.makeRequest(url, options);
      
      if (!response.ok) {
        // Handle 401 Unauthorized - token might be invalid
        if (response.status === 401 && retryCount < maxRetries) {
          console.log('🔄 401 Unauthorized - attempting token refresh and retry');
          
          // Clear current token
          this.clearToken();
          
          // Try to get fresh token from meta tag
          const metaToken = this.extractTokenFromMeta();
          if (metaToken) {
            this.setToken(metaToken);
            console.log('✅ Fresh token extracted from meta tag, retrying request');
            // Retry the request with new token
            return this.makeJsonRequest<T>(url, options, retryCount + 1);
          }
          
          // Check if we're on a dokter mobile page
          if (window.location.pathname.includes('/dokter')) {
            console.log('🚨 No valid token found, redirecting to login');
            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
            throw new Error('Authentication required - redirecting to login');
          }
        }
        
        // Try to parse error response
        let errorData: any = {};
        try {
          errorData = await response.json();
        } catch {
          errorData = { message: `HTTP ${response.status}: ${response.statusText}` };
        }
        
        // Enhanced error parsing for better user feedback
        let errorMessage = errorData.message || errorData.error || `Request failed: ${response.status}`;
        
        // Include error code for better error handling downstream
        if (errorData.code) {
          errorMessage = `${errorData.code}: ${errorMessage}`;
        }
        
        console.error('🔥 API Request failed:', {
          url,
          status: response.status,
          statusText: response.statusText,
          error: errorMessage,
          errorCode: errorData.code,
          errorDetails: errorData,
          retryCount
        });
        
        const error = new Error(errorMessage);
        // Preserve additional error data for downstream handling
        (error as any).code = errorData.code;
        (error as any).details = errorData;
        throw error;
      }

      return response.json();
    } catch (error) {
      // Network or other errors
      if (retryCount < maxRetries && this.isRetryableError(error)) {
        console.log('🔄 Network error - retrying request');
        await this.delay(1000 * (retryCount + 1)); // Progressive delay
        return this.makeJsonRequest<T>(url, options, retryCount + 1);
      }
      
      throw error;
    }
  }

  /**
   * Check if error is retryable
   */
  private isRetryableError(error: any): boolean {
    if (error instanceof TypeError && error.message.includes('fetch')) {
      return true; // Network errors
    }
    if (error.message?.includes('timeout')) {
      return true;
    }
    return false;
  }
  
  /**
   * Simple delay utility for retries
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
  
  /**
   * Initialize token from meta tag if available
   */
  initializeFromMetaTag(): void {
    if (!this.getToken()) {
      const metaToken = this.extractTokenFromMeta();
      if (metaToken) {
        this.setToken(metaToken);
        console.log('🔧 UnifiedAuth initialized with meta tag token');
      } else {
        console.warn('⚠️ No token found in storage or meta tags during initialization');
      }
    } else {
      console.log('🔧 UnifiedAuth initialized with existing stored token');
    }
  }
}

// Create singleton instance using lazy initialization to prevent TDZ violations
let unifiedAuthInstance: UnifiedAuth | null = null;

// Safe getter for singleton instance with TDZ protection
const getUnifiedAuthInstance = (): UnifiedAuth => {
  if (!unifiedAuthInstance) {
    unifiedAuthInstance = UnifiedAuth.getInstance();
  }
  return unifiedAuthInstance;
};

// Auto-initialize when DOM is ready with comprehensive safety checks
if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  // Ensure we're in a browser environment with safe initialization
  const safeInitialize = () => {
    try {
      // Use lazy getter to avoid TDZ issues
      const unifiedAuth = getUnifiedAuthInstance();
      if (unifiedAuth && typeof unifiedAuth.initializeFromMetaTag === 'function') {
        unifiedAuth.initializeFromMetaTag();
        console.log('✅ UnifiedAuth initialized successfully');
      } else {
        console.warn('⚠️ UnifiedAuth instance not ready, deferring initialization');
        throw new Error('UnifiedAuth instance not ready');
      }
    } catch (error) {
      console.warn('⚠️ UnifiedAuth initialization deferred due to error:', error);
      // Progressive retry with exponential backoff
      let retryCount = 0;
      const maxRetries = 3;
      const retryInitialization = () => {
        retryCount++;
        const delay = Math.min(100 * Math.pow(2, retryCount - 1), 1000);
        
        setTimeout(() => {
          try {
            const unifiedAuth = getUnifiedAuthInstance();
            if (unifiedAuth && typeof unifiedAuth.initializeFromMetaTag === 'function') {
              unifiedAuth.initializeFromMetaTag();
              console.log(`✅ UnifiedAuth initialized successfully on retry ${retryCount}`);
            } else if (retryCount < maxRetries) {
              retryInitialization();
            } else {
              console.error('❌ UnifiedAuth initialization failed after all retries');
            }
          } catch (retryError) {
            if (retryCount < maxRetries) {
              console.warn(`⚠️ UnifiedAuth retry ${retryCount} failed, trying again...`, retryError);
              retryInitialization();
            } else {
              console.error('❌ UnifiedAuth initialization failed after all retries:', retryError);
            }
          }
        }, delay);
      };
      
      retryInitialization();
    }
  };
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', safeInitialize);
  } else {
    // DOM already loaded - defer to next tick to avoid temporal dead zone
    setTimeout(safeInitialize, 0);
  }
} else {
  console.warn('⚠️ Browser environment not detected - UnifiedAuth initialization skipped');
}

// Export the safe getter function instead of direct instance
export default getUnifiedAuthInstance;