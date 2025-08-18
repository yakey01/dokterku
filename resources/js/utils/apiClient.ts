/**
 * Universal API Client with Robust Error Handling
 * Handles authentication, CSRF tokens, and JSON parsing errors
 */

import { RobustJsonParser } from './robustJsonParser';

interface ApiClientOptions extends RequestInit {
  skipAuth?: boolean;
  timeout?: number;
  retries?: number;
}

interface ApiResponse<T = any> {
  success: boolean;
  data: T | null;
  error?: string;
  status?: number;
  repaired?: boolean;
}

export class ApiClient {
  private static getAuthToken(): string | null {
    // Try multiple token sources in priority order
    const sources = [
      { name: 'api-token', getter: () => document.querySelector('meta[name="api-token"]')?.getAttribute('content') },
      { name: 'localStorage auth_token', getter: () => localStorage.getItem('auth_token') },
      { name: 'localStorage token', getter: () => localStorage.getItem('token') },
      { name: 'csrf-token', getter: () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
      { name: 'form _token', getter: () => document.querySelector('input[name="_token"]')?.getAttribute('value') }
    ];

    for (const source of sources) {
      try {
        const token = source.getter();
        if (token && token.length > 10) {
          console.log(`🔑 Using token from: ${source.name}`, {
            tokenLength: token.length,
            tokenPrefix: token.substring(0, 8) + '...'
          });
          return token;
        } else if (token) {
          console.warn(`⚠️ Token too short from ${source.name}:`, token.length);
        }
      } catch (e) {
        console.warn(`❌ Error getting token from ${source.name}:`, e);
        continue;
      }
    }

    console.error('❌ CRITICAL: No valid authentication token found from any source');
    return null;
  }

  private static getDefaultHeaders(skipAuth: boolean = false): Record<string, string> {
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };

    if (!skipAuth) {
      const token = this.getAuthToken();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
        headers['X-CSRF-TOKEN'] = token;
      }
    }

    return headers;
  }

  /**
   * Make an API request with robust error handling
   */
  static async request<T = any>(
    url: string, 
    options: ApiClientOptions = {}
  ): Promise<ApiResponse<T>> {
    const {
      skipAuth = false,
      timeout = 10000,
      retries = 1,
      ...fetchOptions
    } = options;

    // Prepare headers
    const headers = {
      ...this.getDefaultHeaders(skipAuth),
      ...fetchOptions.headers
    };

    // Create abort controller for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
      for (let attempt = 0; attempt <= retries; attempt++) {
        try {
          console.log(`🌐 API Call: ${url.split('/').pop()}`, {
            attempt: attempt + 1,
            headers: Object.keys(headers),
            method: fetchOptions.method || 'GET'
          });

          const response = await fetch(url, {
            ...fetchOptions,
            headers,
            signal: controller.signal
          });

          clearTimeout(timeoutId);

          // Get response text first to handle both JSON and HTML responses
          const responseText = await response.text();

          console.log(`📡 Response received: ${url.split('/').pop()}`, {
            status: response.status,
            contentType: response.headers.get('content-type'),
            textLength: responseText.length
          });

          // Parse using robust JSON parser
          const parseResult = await RobustJsonParser.parseJson<T>(responseText);

          if (!parseResult.success) {
            console.error(`❌ JSON Parse Error: ${url}`, parseResult.error);
            
            if (attempt < retries) {
              console.log(`🔄 Retrying request (${attempt + 1}/${retries})`);
              await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1)));
              continue;
            }

            return {
              success: false,
              data: null,
              error: parseResult.error,
              status: response.status,
              repaired: parseResult.repaired
            };
          }

          // Success case
          console.log(`✅ API Success: ${url.split('/').pop()}`, {
            status: response.status,
            repaired: parseResult.repaired
          });

          return {
            success: true,
            data: parseResult.data,
            status: response.status,
            repaired: parseResult.repaired
          };

        } catch (requestError: any) {
          if (requestError.name === 'AbortError') {
            return {
              success: false,
              data: null,
              error: `Request timeout after ${timeout}ms`,
              status: 408
            };
          }

          if (attempt < retries) {
            console.log(`🔄 Retrying after error (${attempt + 1}/${retries}):`, requestError.message);
            await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1)));
            continue;
          }

          return {
            success: false,
            data: null,
            error: `Network error: ${requestError.message}`,
            status: 0
          };
        }
      }

    } finally {
      clearTimeout(timeoutId);
    }

    return {
      success: false,
      data: null,
      error: 'Max retries exceeded',
      status: 0
    };
  }

  /**
   * GET request helper
   */
  static async get<T = any>(url: string, options: ApiClientOptions = {}): Promise<ApiResponse<T>> {
    return this.request<T>(url, { ...options, method: 'GET' });
  }

  /**
   * POST request helper
   */
  static async post<T = any>(url: string, data?: any, options: ApiClientOptions = {}): Promise<ApiResponse<T>> {
    return this.request<T>(url, {
      ...options,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined
    });
  }

  /**
   * PUT request helper
   */
  static async put<T = any>(url: string, data?: any, options: ApiClientOptions = {}): Promise<ApiResponse<T>> {
    return this.request<T>(url, {
      ...options,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined
    });
  }

  /**
   * DELETE request helper
   */
  static async delete<T = any>(url: string, options: ApiClientOptions = {}): Promise<ApiResponse<T>> {
    return this.request<T>(url, { ...options, method: 'DELETE' });
  }
}

export default ApiClient;