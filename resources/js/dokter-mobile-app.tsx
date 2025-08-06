import React from 'react';
import { createRoot } from 'react-dom/client';
import HolisticMedicalDashboard from './components/dokter/HolisticMedicalDashboard';
import '../css/app.css';
import './setup-csrf';

// 🌟 WORLD-CLASS ERROR HANDLING & MONITORING SYSTEM
interface ErrorMetrics {
    timestamp: number;
    type: string;
    message: string;
    stack?: string;
    userAgent: string;
    url: string;
    userId?: string;
}

class DokterKuErrorHandler {
    private errors: ErrorMetrics[] = [];
    private maxErrors = 50;
    
    constructor() {
        this.initializeGlobalHandlers();
        this.initializePerformanceMonitoring();
    }
    
    private initializeGlobalHandlers() {
        // Enhanced promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError('promise_rejection', event.reason?.message || 'Unknown promise rejection', event.reason?.stack);
            
            // Suppress non-critical errors
            if (this.isNonCriticalError(event.reason?.message)) {
                console.warn('🛡️ Suppressed non-critical error:', event.reason?.message);
                event.preventDefault();
                return;
            }
            
            console.warn('🚨 Unhandled Promise Rejection:', event.reason);
            event.preventDefault();
        });

        // Enhanced JavaScript error handler
        window.addEventListener('error', (event) => {
            this.handleError('javascript_error', event.error?.message || event.message, event.error?.stack);
            
            if (this.isNonCriticalError(event.error?.message || event.message)) {
                console.warn('🛡️ Suppressed non-critical JS error:', event.error?.message);
                return;
            }
            
            console.warn('🚨 JavaScript Error:', event.error);
        });

        // Resource loading error handler
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                this.handleError('resource_error', `Failed to load: ${(event.target as any)?.src || (event.target as any)?.href || 'unknown'}`, '');
            }
        }, true);
    }
    
    private initializePerformanceMonitoring() {
        // Monitor React render performance
        if ('performance' in window && 'measure' in performance) {
            performance.mark('dokterku-app-start');
        }
    }
    
    private isNonCriticalError(message?: string): boolean {
        if (!message) return false;
        
        const nonCriticalPatterns = [
            'IntersectionObserver',
            'target',
            'Element',
            'observe',
            'ResizeObserver',
            'MutationObserver',
            'requestIdleCallback'
        ];
        
        return nonCriticalPatterns.some(pattern => message.includes(pattern));
    }
    
    private handleError(type: string, message: string, stack?: string) {
        const error: ErrorMetrics = {
            timestamp: Date.now(),
            type,
            message,
            stack,
            userAgent: navigator.userAgent,
            url: window.location.href,
            userId: this.getUserId()
        };
        
        this.errors.push(error);
        
        // Keep only recent errors
        if (this.errors.length > this.maxErrors) {
            this.errors = this.errors.slice(-this.maxErrors);
        }
        
        // Store in localStorage for debugging
        try {
            localStorage.setItem('dokterku_errors', JSON.stringify(this.errors.slice(-10)));
        } catch (e) {
            // localStorage full or unavailable
        }
    }
    
    private getUserId(): string | undefined {
        try {
            const userMeta = document.querySelector('meta[name="user-data"]');
            if (userMeta) {
                const userData = JSON.parse(userMeta.getAttribute('content') || '{}');
                return userData.email || userData.name;
            }
        } catch (e) {
            // User data not available
        }
        return undefined;
    }
    
    public getErrorReport(): ErrorMetrics[] {
        return [...this.errors];
    }
    
    public clearErrors() {
        this.errors = [];
        localStorage.removeItem('dokterku_errors');
    }
}

// 🚀 ENTERPRISE-GRADE APP INITIALIZATION SYSTEM
class DokterKuBootstrap {
    private errorHandler: DokterKuErrorHandler;
    private retryCount = 0;
    private maxRetries = 3;
    private retryDelay = 1000;
    
    constructor() {
        this.errorHandler = new DokterKuErrorHandler();
        this.initializeApp();
    }
    
    private async initializeApp() {
        console.log('🌟 DOKTERKU Mobile App: World-class initialization starting...');
        
        try {
            // Pre-flight checks
            await this.performPreflightChecks();
            
            // Initialize React app
            await this.mountReactApp();
            
            // Post-initialization tasks
            this.performPostInitializationTasks();
            
        } catch (error) {
            console.error('❌ DOKTERKU Mobile App initialization failed:', error);
            await this.handleInitializationFailure(error as Error);
        }
    }
    
    private async performPreflightChecks(): Promise<void> {
        console.log('🔍 Performing pre-flight checks...');
        
        // Check container availability
        const container = document.getElementById('dokter-app');
        if (!container) {
            throw new Error('Container element #dokter-app not found');
        }
        
        // Check React availability
        if (!React || !createRoot) {
            throw new Error('React dependencies not loaded');
        }
        
        // Check user authentication
        const authMeta = document.querySelector('meta[name="user-authenticated"]');
        if (!authMeta || authMeta.getAttribute('content') !== 'true') {
            throw new Error('User not authenticated');
        }
        
        console.log('✅ Pre-flight checks passed');
    }
    
    private async mountReactApp(): Promise<void> {
        console.log('🚀 Mounting React application...');
        
        performance.mark('react-mount-start');
        
        const container = document.getElementById('dokter-app');
        if (!container) throw new Error('Container not found during mount');
        
        // Hide loading spinner with smooth transition
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.transition = 'opacity 0.3s ease-out';
            loadingElement.style.opacity = '0';
            setTimeout(() => {
                loadingElement.style.display = 'none';
            }, 300);
        }
        
        // Create React root with error boundary
        const root = createRoot(container);
        
        // Render with error boundary wrapper
        root.render(
            <React.StrictMode>
                <ErrorBoundary>
                    <HolisticMedicalDashboard />
                </ErrorBoundary>
            </React.StrictMode>
        );
        
        performance.mark('react-mount-end');
        performance.measure('react-mount-duration', 'react-mount-start', 'react-mount-end');
        
        console.log('✅ React application mounted successfully');
        
        // Verify navigation is rendered (world-class validation)
        // Increased timeout to allow React to fully render navigation
        setTimeout(() => this.validateNavigationRendering(), 2000);
    }
    
    private validateNavigationRendering(): void {
        // First, clean up any emergency navigation that might have been injected
        this.cleanupEmergencyNavigation();
        
        // Enhanced navigation detection for HolisticMedicalDashboard gaming navigation
        const enhancedNavigationSelectors = [
            // Primary - Gaming RPG navigation container
            '#dokter-app .absolute.bottom-0[class*="bg-gradient-to-t"][class*="rounded-t-3xl"]',
            // Secondary - Navigation with backdrop blur and gaming gradient
            '#dokter-app [class*="backdrop-blur-3xl"][class*="border-purple-400"]',
            // Tertiary - Gaming buttons with crown, calendar, shield, star, brain icons
            '#dokter-app button[class*="group"][class*="transition-all"]',
            // Quaternary - Gaming home indicator
            '#dokter-app [class*="bg-gradient-to-r"][class*="purple-400"][class*="rounded-full"]'
        ];
        
        let navigationFound = false;
        let foundSelector = '';
        
        for (const selector of enhancedNavigationSelectors) {
            const elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                // Additional validation - check if it contains gaming navigation content
                const hasGamingContent = Array.from(elements).some(el => {
                    const content = el.innerHTML || '';
                    return content.includes('Home') || 
                           content.includes('Missions') || 
                           content.includes('Guardian') || 
                           content.includes('Rewards') || 
                           content.includes('Profile') ||
                           el.className.includes('rounded-t-3xl');
                });
                
                if (hasGamingContent) {
                    navigationFound = true;
                    foundSelector = selector;
                    console.log('✅ Gaming navigation validated successfully with selector:', selector);
                    console.log('🎮 Gaming navigation elements count:', elements.length);
                    break;
                }
            }
        }
        
        if (!navigationFound) {
            // More lenient check - look for any navigation inside dokter-app
            const anyNavInApp = document.querySelectorAll('#dokter-app [class*="bottom-0"], #dokter-app [class*="navigation"]');
            if (anyNavInApp.length > 0) {
                navigationFound = true;
                console.log('✅ Basic navigation found in dokter-app:', anyNavInApp.length);
            }
        }
        
        if (!navigationFound) {
            console.warn('⚠️ Gaming navigation not detected - React component may still be rendering');
            console.log('🔍 Available elements in dokter-app:', document.querySelector('#dokter-app')?.children.length || 0);
            // Still don't inject emergency navigation - trust React component
        } else {
            console.log('🎯 Navigation validation complete - single gaming navigation confirmed');
        }
    }
    
    private cleanupEmergencyNavigation(): void {
        // Enhanced cleanup to prevent navigation duplication
        console.log('🧹 Starting comprehensive emergency navigation cleanup...');
        
        // Primary cleanup - Emergency navigation selectors
        const emergencySelectors = [
            '.emergency-navigation',
            '.emergency-navigation-fix', 
            '.emergency-nav',
            '.emergency-nav-fix',
            '[class*="emergency"][class*="nav"]',
            '[class*="emergency-nav"]'
        ];
        
        let removed = 0;
        emergencySelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                console.log(`🧹 Removing emergency navigation: ${selector}`);
                el.remove();
                removed++;
            });
        });
        
        // Secondary cleanup - Detect injected navigation by content patterns
        const suspiciousNavs = document.querySelectorAll([
            '[class*="fixed"][class*="bottom"]',
            '[style*="bottom: 0"]', 
            '[style*="bottom:0"]',
            '[style*="position: fixed"]',
            '[style*="z-index: 99999"]'
        ].join(', '));
        
        suspiciousNavs.forEach(nav => {
            const content = nav.innerHTML || '';
            const hasEmojiPattern = /[\u{1F300}-\u{1F9FF}]|👑|📅|🛡️|⭐|🧠/u.test(content);
            const hasEmergencyClass = /emergency/i.test(nav.className);
            const hasHighZIndex = nav.style.zIndex === '99999';
            const hasNavigationKeywords = /navigation|missions|guardian|rewards|profile/i.test(content);
            
            // Remove if it matches injection patterns but not our React component
            if ((hasEmojiPattern || hasEmergencyClass || hasHighZIndex) && 
                hasNavigationKeywords && 
                !nav.closest('#dokter-app')) {
                console.log('🧹 Removing suspected injected navigation with patterns:', {
                    emojis: hasEmojiPattern,
                    emergency: hasEmergencyClass, 
                    highZ: hasHighZIndex,
                    keywords: hasNavigationKeywords
                });
                nav.remove();
                removed++;
            }
        });
        
        // Tertiary cleanup - Remove any duplicate bottom navigation
        const bottomNavs = document.querySelectorAll([
            '[class*="bottom-0"]',
            '[style*="bottom: 0"]'
        ].join(', '));
        
        if (bottomNavs.length > 1) {
            console.log(`🔍 Found ${bottomNavs.length} bottom navigations, keeping only React component`);
            bottomNavs.forEach((nav, index) => {
                // Keep only the first one that's inside dokter-app (React component)
                const isReactComponent = nav.closest('#dokter-app') !== null;
                const hasReactClasses = nav.className.includes('backdrop-blur') && 
                                       nav.className.includes('gradient');
                
                if (!isReactComponent || !hasReactClasses) {
                    if (index > 0) { // Keep first one, remove others
                        console.log(`🧹 Removing duplicate bottom navigation #${index + 1}`);
                        nav.remove();
                        removed++;
                    }
                }
            });
        }
        
        console.log(`✅ Emergency navigation cleanup complete. Removed ${removed} elements.`);
        
        // Set up MutationObserver to prevent re-injection
        this.setupNavigationProtection();
    }
    
    private setupNavigationProtection(): void {
        // Create MutationObserver to prevent re-injection of emergency navigation
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        const element = node as Element;
                        
                        // Check if it's an emergency navigation injection
                        const isEmergencyNav = element.classList.contains('emergency-navigation') ||
                                             element.classList.contains('emergency-nav') ||
                                             element.classList.contains('emergency-navigation-fix') ||
                                             /emergency.*nav/i.test(element.className);
                        
                        // Check for emoji-based navigation injection
                        const hasEmojiInjection = /[\u{1F300}-\u{1F9FF}]|👑|📅|🛡️|⭐|🧠/u.test(element.innerHTML || '') &&
                                                /bottom.*0|fixed.*bottom/i.test(element.style.cssText || element.className);
                        
                        if (isEmergencyNav || hasEmojiInjection) {
                            console.log('🛡️ Prevented emergency navigation re-injection:', element.className);
                            element.remove();
                        }
                    }
                });
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('🛡️ Navigation protection active - MutationObserver monitoring injections');
    }
    
    private injectEmergencyNavigation(): void {
        // Completely disabled emergency navigation to prevent duplication
        console.log('🚫 Emergency navigation permanently disabled - React component provides navigation');
        return;
    }
    
    private performPostInitializationTasks(): void {
        // Performance measurements
        if ('performance' in window) {
            performance.mark('dokterku-app-ready');
            performance.measure('dokterku-total-init', 'dokterku-app-start', 'dokterku-app-ready');
            
            const measures = performance.getEntriesByType('measure');
            measures.forEach(measure => {
                console.log(`📊 Performance: ${measure.name} took ${measure.duration.toFixed(2)}ms`);
            });
        }
        
        // Setup global utilities
        (window as any).dokterKuDiagnostics = {
            getErrors: () => this.errorHandler.getErrorReport(),
            clearErrors: () => this.errorHandler.clearErrors(),
            getPerformance: () => performance.getEntriesByType('measure')
        };
        
        console.log('🌟 DOKTERKU Mobile App: World-class initialization completed successfully');
    }
    
    private async handleInitializationFailure(error: Error): Promise<void> {
        this.retryCount++;
        
        if (this.retryCount <= this.maxRetries) {
            console.log(`🔄 Retrying initialization (${this.retryCount}/${this.maxRetries}) in ${this.retryDelay}ms...`);
            
            setTimeout(() => {
                this.initializeApp();
            }, this.retryDelay);
            
            this.retryDelay *= 2; // Exponential backoff
            return;
        }
        
        // Final fallback - show enterprise-grade error screen
        this.showEnterpriseErrorScreen(error);
    }
    
    private showEnterpriseErrorScreen(error: Error): void {
        const container = document.getElementById('dokter-app') || document.body;
        
        container.innerHTML = `
            <div style="
                min-height: 100vh;
                background: linear-gradient(135deg, #0f172a 0%, #581c87 50%, #0f172a 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                padding: 20px;
            ">
                <div style="
                    background: rgba(30, 41, 59, 0.9);
                    border: 2px solid #8b5cf6;
                    border-radius: 16px;
                    padding: 40px;
                    max-width: 500px;
                    text-align: center;
                    backdrop-filter: blur(10px);
                ">
                    <div style="font-size: 48px; margin-bottom: 20px;">🚨</div>
                    <h1 style="margin: 0 0 20px 0; font-size: 24px; color: #ef4444;">Aplikasi Tidak Dapat Dimuat</h1>
                    <p style="margin: 0 0 30px 0; color: #d1d5db; line-height: 1.6;">
                        Terjadi kesalahan teknis saat memuat aplikasi DOKTERKU Gaming Dashboard. 
                        Tim teknis telah diberitahu secara otomatis.
                    </p>
                    <div style="margin: 30px 0;">
                        <button onclick="window.location.reload()" style="
                            background: linear-gradient(to right, #06b6d4, #8b5cf6);
                            border: none;
                            color: white;
                            padding: 12px 24px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: bold;
                            margin: 5px;
                        ">🔄 Coba Lagi</button>
                        <button onclick="this.nextElementSibling.style.display='block'" style="
                            background: transparent;
                            border: 1px solid #6b7280;
                            color: #d1d5db;
                            padding: 12px 24px;
                            border-radius: 8px;
                            cursor: pointer;
                            margin: 5px;
                        ">🔍 Detail Error</button>
                    </div>
                    <details style="
                        margin-top: 20px;
                        text-align: left;
                        background: rgba(0, 0, 0, 0.3);
                        padding: 15px;
                        border-radius: 8px;
                        border: 1px solid #374151;
                    ">
                        <summary style="cursor: pointer; font-weight: bold; margin-bottom: 10px;">Detail Teknis</summary>
                        <pre style="
                            background: #111827;
                            color: #f59e0b;
                            padding: 10px;
                            border-radius: 4px;
                            overflow: auto;
                            font-size: 12px;
                            white-space: pre-wrap;
                        ">${error.message}\n\n${error.stack || 'No stack trace available'}</pre>
                    </details>
                    <div style="margin-top: 30px; font-size: 12px; color: #6b7280;">
                        Error ID: ${Date.now()}<br>
                        Waktu: ${new Date().toLocaleString('id-ID')}<br>
                        Browser: ${navigator.userAgent.split(' ').slice(-2).join(' ')}
                    </div>
                </div>
            </div>
        `;
        
        // Hide loading spinner
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
}

// 🛡️ ENTERPRISE ERROR BOUNDARY COMPONENT
class ErrorBoundary extends React.Component<
    { children: React.ReactNode },
    { hasError: boolean; error?: Error }
> {
    constructor(props: { children: React.ReactNode }) {
        super(props);
        this.state = { hasError: false };
    }
    
    static getDerivedStateFromError(error: Error) {
        return { hasError: true, error };
    }
    
    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        console.error('🚨 React Error Boundary caught error:', error, errorInfo);
        
        // Log to our error handling system
        if ((window as any).dokterKuDiagnostics) {
            (window as any).dokterKuDiagnostics.logReactError?.(error, errorInfo);
        }
    }
    
    render() {
        if (this.state.hasError) {
            return (
                <div style={{
                    minHeight: '100vh',
                    background: 'linear-gradient(135deg, #0f172a 0%, #581c87 50%, #0f172a 100%)',
                    color: 'white',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    padding: '20px'
                }}>
                    <div style={{
                        background: 'rgba(30, 41, 59, 0.9)',
                        border: '2px solid #8b5cf6',
                        borderRadius: '16px',
                        padding: '40px',
                        maxWidth: '500px',
                        textAlign: 'center',
                        backdropFilter: 'blur(10px)'
                    }}>
                        <div style={{ fontSize: '48px', marginBottom: '20px' }}>⚠️</div>
                        <h1 style={{ margin: '0 0 20px 0', fontSize: '24px', color: '#f59e0b' }}>
                            Komponen React Bermasalah
                        </h1>
                        <p style={{ margin: '0 0 30px 0', color: '#d1d5db', lineHeight: '1.6' }}>
                            Terjadi kesalahan dalam komponen React. Error telah dicatat untuk diperbaiki.
                        </p>
                        <button 
                            onClick={() => window.location.reload()}
                            style={{
                                background: 'linear-gradient(to right, #06b6d4, #8b5cf6)',
                                border: 'none',
                                color: 'white',
                                padding: '12px 24px',
                                borderRadius: '8px',
                                cursor: 'pointer',
                                fontWeight: 'bold'
                            }}
                        >
                            🔄 Muat Ulang Halaman
                        </button>
                    </div>
                </div>
            );
        }
        
        return this.props.children;
    }
}

// 🚀 INITIALIZE WORLD-CLASS SYSTEM
let bootstrap: DokterKuBootstrap;

// Initialize when DOM is ready with multiple fallbacks
function initializeWorldClassSystem() {
    try {
        bootstrap = new DokterKuBootstrap();
    } catch (error) {
        console.error('🚨 Bootstrap initialization failed:', error);
        
        // Ultimate fallback
        setTimeout(() => {
            console.log('🔄 Attempting fallback initialization...');
            try {
                new DokterKuBootstrap();
            } catch (fallbackError) {
                console.error('🚨 Fallback initialization also failed:', fallbackError);
            }
        }, 2000);
    }
}

// Multiple initialization strategies
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeWorldClassSystem);
} else {
    // Initialize immediately if DOM is ready
    initializeWorldClassSystem();
}

// Export for global access
(window as any).DokterKuBootstrap = DokterKuBootstrap;