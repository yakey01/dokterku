/**
 * CSRF Token Helper
 * Provides consistent CSRF token handling across the application
 */

export class CSRFHelper {
  private static instance: CSRFHelper;
  private token: string | null = null;

  private constructor() {
    this.refreshToken();
  }

  static getInstance(): CSRFHelper {
    if (!CSRFHelper.instance) {
      CSRFHelper.instance = new CSRFHelper();
    }
    return CSRFHelper.instance;
  }

  /**
   * Get CSRF token from meta tag
   */
  private refreshToken(): void {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    this.token = metaTag?.getAttribute('content') || null;
    
    if (!this.token) {
      console.warn('⚠️ CSRF token not found in meta tag');
    }
  }

  /**
   * Get current CSRF token
   */
  getToken(): string {
    if (!this.token) {
      this.refreshToken();
    }
    return this.token || '';
  }

  /**
   * Get headers with CSRF token included
   */
  getHeaders(additionalHeaders: Record<string, string> = {}): Record<string, string> {
    return {
      'X-CSRF-TOKEN': this.getToken(),
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...additionalHeaders
    };
  }

  /**
   * Setup axios defaults if axios is available
   */
  setupAxiosDefaults(): void {
    if (typeof window !== 'undefined' && (window as any).axios) {
      const axios = (window as any).axios;
      axios.defaults.headers.common['X-CSRF-TOKEN'] = this.getToken();
      axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    }
  }

  /**
   * Add CSRF token to form
   */
  addTokenToForm(form: HTMLFormElement): void {
    const existingInput = form.querySelector('input[name="_token"]');
    if (existingInput) {
      (existingInput as HTMLInputElement).value = this.getToken();
    } else {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_token';
      input.value = this.getToken();
      form.appendChild(input);
    }
  }

  /**
   * Intercept fetch to automatically add CSRF token
   */
  interceptFetch(): void {
    const originalFetch = window.fetch;
    window.fetch = async (input: RequestInfo | URL, init?: RequestInit) => {
      // Only add CSRF token for same-origin requests
      const url = typeof input === 'string' ? input : input.toString();
      const isSameOrigin = url.startsWith('/') || url.startsWith(window.location.origin);
      
      if (isSameOrigin && init?.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(init.method)) {
        init.headers = {
          ...init.headers,
          'X-CSRF-TOKEN': this.getToken(),
        };
      }
      
      return originalFetch(input, init);
    };
  }
}

// Export singleton instance
export const csrfHelper = CSRFHelper.getInstance();