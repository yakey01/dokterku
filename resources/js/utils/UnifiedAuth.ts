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
   * Get stored API token
   */
  getToken(): string | null {
    // Check multiple possible token storage keys for compatibility
    return localStorage.getItem('dokterku_auth_token') || 
           sessionStorage.getItem('dokterku_auth_token') ||
           localStorage.getItem('api_token') || 
           sessionStorage.getItem('api_token') || 
           null;
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
      const response = await this.makeRequest('/api/user');
      return response.ok;
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
        },
        body: JSON.stringify({
          login: email,
          password: password,
          device_id: deviceId || `web-${Date.now()}`,
          device_name: 'Web Browser'
        })
      });

      const result = await response.json();

      if (response.ok && result.success && result.data?.token) {
        this.setToken(result.data.token);
        return { success: true, data: result.data };
      } else {
        return { success: false, error: result.message || 'Login failed' };
      }
    } catch (error) {
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
   * Make authenticated request and return JSON
   */
  async makeJsonRequest<T = any>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await this.makeRequest(url, options);
    
    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || `Request failed: ${response.status}`);
    }

    return response.json();
  }
}

export default UnifiedAuth.getInstance();