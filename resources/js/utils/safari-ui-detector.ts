/**
 * Safari UI Height Detector
 * Dynamically detects and adjusts for Safari's browser UI on iOS
 */

export class SafariUIDetector {
  private static instance: SafariUIDetector;
  private callbacks: ((height: number) => void)[] = [];
  private currentUIHeight: number = 0;
  private isIOSSafari: boolean = false;

  private constructor() {
    this.isIOSSafari = this.detectIOSSafari();
    if (this.isIOSSafari) {
      this.setupDetection();
    }
  }

  static getInstance(): SafariUIDetector {
    if (!SafariUIDetector.instance) {
      SafariUIDetector.instance = new SafariUIDetector();
    }
    return SafariUIDetector.instance;
  }

  private detectIOSSafari(): boolean {
    const ua = window.navigator.userAgent;
    const iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
    const webkit = !!ua.match(/WebKit/i);
    const iOSSafari = iOS && webkit && !ua.match(/CriOS/i) && !ua.match(/FxiOS/i);
    
    return iOSSafari;
  }

  private setupDetection(): void {
    // Initial detection
    this.detectUIHeight();

    // Listen for viewport changes
    window.addEventListener('resize', () => this.detectUIHeight());
    window.addEventListener('scroll', () => this.detectUIHeight());
    window.addEventListener('orientationchange', () => {
      setTimeout(() => this.detectUIHeight(), 500);
    });

    // Visual viewport API for more accurate detection
    if ('visualViewport' in window) {
      window.visualViewport?.addEventListener('resize', () => this.detectUIHeight());
      window.visualViewport?.addEventListener('scroll', () => this.detectUIHeight());
    }
  }

  private detectUIHeight(): void {
    if (!this.isIOSSafari) return;

    let uiHeight = 0;

    // Method 1: Visual Viewport API (most accurate)
    if ('visualViewport' in window && window.visualViewport) {
      const vv = window.visualViewport;
      const windowHeight = window.innerHeight;
      const viewportHeight = vv.height;
      
      // Safari UI height is the difference
      uiHeight = windowHeight - viewportHeight;
    } else {
      // Method 2: Fallback calculation
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.clientHeight;
      uiHeight = windowHeight - documentHeight;
    }

    // Only update if changed significantly (more than 5px)
    if (Math.abs(uiHeight - this.currentUIHeight) > 5) {
      this.currentUIHeight = uiHeight;
      this.notifyCallbacks(uiHeight);
      this.updateCSSVariable(uiHeight);
    }
  }

  private updateCSSVariable(height: number): void {
    // Set CSS custom property for easy usage
    document.documentElement.style.setProperty('--safari-ui-height', `${height}px`);
    
    // Also update a class for different UI states
    document.body.classList.remove('safari-ui-minimal', 'safari-ui-full');
    
    if (height < 20) {
      document.body.classList.add('safari-ui-minimal');
    } else {
      document.body.classList.add('safari-ui-full');
    }
  }

  private notifyCallbacks(height: number): void {
    this.callbacks.forEach(callback => callback(height));
  }

  public onUIHeightChange(callback: (height: number) => void): () => void {
    this.callbacks.push(callback);
    
    // Call immediately with current value
    if (this.isIOSSafari) {
      callback(this.currentUIHeight);
    }

    // Return unsubscribe function
    return () => {
      this.callbacks = this.callbacks.filter(cb => cb !== callback);
    };
  }

  public getCurrentUIHeight(): number {
    return this.currentUIHeight;
  }

  public isInIOSSafari(): boolean {
    return this.isIOSSafari;
  }

  // Helper to add extra bottom padding dynamically
  public adjustBottomNavigation(element: HTMLElement): void {
    if (!this.isIOSSafari || !element) return;

    const adjust = () => {
      const baseHeight = 80; // Base navigation height
      const safariUI = this.currentUIHeight;
      const safeArea = parseInt(getComputedStyle(document.documentElement).getPropertyValue('padding-bottom')) || 0;
      
      // Calculate total bottom spacing needed
      const totalBottom = Math.max(baseHeight, safariUI + 20); // 20px extra buffer
      
      element.style.paddingBottom = `${totalBottom}px`;
    };

    // Initial adjustment
    adjust();

    // Subscribe to changes
    return this.onUIHeightChange(adjust);
  }
}

// Auto-initialize on import
if (typeof window !== 'undefined') {
  SafariUIDetector.getInstance();
}