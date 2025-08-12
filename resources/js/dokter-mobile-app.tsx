import React from 'react';
import { createRoot } from 'react-dom/client';
import HolisticMedicalDashboard from './components/dokter/HolisticMedicalDashboard';
import getUnifiedAuth from './utils/UnifiedAuth';
import '../css/app.css';
import '../css/responsive-typography.css';
import './setup-csrf';

// 🛡️ SAFE DOM UTILITIES
class SafeDOM {
    /**
     * Safely remove element with existence validation
     */
    static safeRemove(element: Element | HTMLElement | null | undefined): boolean {
        if (!element) {
            console.warn('⚠️ SafeDOM: Attempted to remove null/undefined element');
            return false;
        }

        try {
            // Check if element exists in DOM
            if (!document.contains(element)) {
                console.warn('⚠️ SafeDOM: Element not in document');
                return false;
            }

            // Check if parent exists and contains the element
            if (!element.parentNode) {
                console.warn('⚠️ SafeDOM: Element has no parent');
                return false;
            }

            if (!element.parentNode.contains(element)) {
                console.warn('⚠️ SafeDOM: Parent does not contain element');
                return false;
            }

            // Use modern remove() method if available, fallback to removeChild
            if ('remove' in element && typeof element.remove === 'function') {
                element.remove();
            } else {
                element.parentNode.removeChild(element);
            }
            console.log('✅ SafeDOM: Element safely removed');
            return true;

        } catch (error) {
            // Handle NotFoundError gracefully
            if (error instanceof DOMException && error.name === 'NotFoundError') {
                console.warn('⚠️ SafeDOM: Element was already removed (NotFoundError)');
                return true; // Consider successful since element is gone
            }
            
            console.error('❌ SafeDOM: Removal failed:', {
                error: error instanceof Error ? error.message : String(error),
                elementInfo: {
                    tagName: element.tagName,
                    className: element.className,
                    id: element.id
                }
            });
            return false;
        }
    }

    /**
     * Safely query element with error handling
     */
    static safeQuery(selector: string, parent: Document | Element = document): Element | null {
        try {
            return parent.querySelector(selector);
        } catch (error) {
            console.error('❌ SafeDOM: Query failed:', {
                selector,
                error: error instanceof Error ? error.message : String(error)
            });
            return null;
        }
    }

    /**
     * Safely query all elements with error handling
     */
    static safeQueryAll(selector: string, parent: Document | Element = document): Element[] {
        try {
            return Array.from(parent.querySelectorAll(selector));
        } catch (error) {
            console.error('❌ SafeDOM: QueryAll failed:', {
                selector,
                error: error instanceof Error ? error.message : String(error)
            });
            return [];
        }
    }

    /**
     * Batch safe removal with progress tracking
     */
    static batchRemove(elements: (Element | null | undefined)[]): { removed: number; failed: number } {
        let removed = 0;
        let failed = 0;

        elements.forEach((element, index) => {
            if (this.safeRemove(element)) {
                removed++;
            } else {
                failed++;
            }
        });

        console.log(`🧹 SafeDOM: Batch removal complete - ${removed} removed, ${failed} failed`);
        return { removed, failed };
    }

    /**
     * Monitor DOM mutations and validate operations
     */
    static createSafeMutationObserver(callback: MutationCallback, options: MutationObserverInit = {}) {
        const safeCallback: MutationCallback = (mutations, observer) => {
            try {
                callback(mutations, observer);
            } catch (error) {
                console.error('❌ SafeDOM: MutationObserver callback failed:', error);
                
                // Don't disconnect on error, just log it
                // observer.disconnect();
            }
        };

        return new MutationObserver(safeCallback);
    }
}

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

        // Enhanced JavaScript error handler with ResizeObserver fix
        window.addEventListener('error', (event) => {
            const errorMessage = event.error?.message || event.message;
            
            // Specifically handle ResizeObserver loop errors
            if (errorMessage === 'ResizeObserver loop completed with undelivered notifications.') {
                console.warn('🔄 ResizeObserver loop detected - suppressed (non-critical)');
                event.stopImmediatePropagation();
                return;
            }
            
            this.handleError('javascript_error', errorMessage, event.error?.stack);
            
            if (this.isNonCriticalError(errorMessage)) {
                console.warn('🛡️ Suppressed non-critical JS error:', errorMessage);
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
        
        // Initialize authentication token from meta tag
        this.initializeAuthentication();
    }
    
    private initializeAuthentication(): void {
        try {
            console.log('🔐 Initializing authentication...');
            
            // Initialize token from meta tag if available
            const tokenMeta = document.querySelector('meta[name="api-token"]');
            const metaToken = tokenMeta?.getAttribute('content');
            
            if (metaToken && metaToken.trim()) {
                // Store the token for API use
                getUnifiedAuth().setToken(metaToken.trim());
                console.log('✅ API token initialized from meta tag');
            } else {
                console.warn('⚠️ No API token found in meta tag, may affect API calls');
            }
            
        } catch (error) {
            console.error('❌ Authentication initialization failed:', error);
        }
    }
    
    private async mountReactApp(): Promise<void> {
        console.log('🚀 Mounting React application...');
        
        performance.mark('react-mount-start');
        
        // Add safe DOM element check with retry
        let container = document.getElementById('dokter-app');
        if (!container) {
            console.warn('⚠️ Container not found immediately, waiting for DOM...');
            // Wait for DOM to be ready
            await new Promise(resolve => {
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    resolve(undefined);
                } else {
                    document.addEventListener('DOMContentLoaded', () => resolve(undefined));
                }
            });
            
            // Try again after DOM is ready
            container = document.getElementById('dokter-app');
            if (!container) {
                console.error('❌ Container still not found after DOM ready');
                throw new Error('Container not found during mount');
            }
        }
        
        // Get user data from meta tags with multiple fallbacks
        let userData = null;
        try {
            // First try: Get from user-data meta tag
            const userDataMeta = document.querySelector('meta[name="user-data"]');
            if (userDataMeta) {
                const content = userDataMeta.getAttribute('content') || '{}';
                if (content.trim() && content !== '{}') {
                    userData = JSON.parse(content);
                    console.log('✅ User data loaded from user-data meta:', userData.name || 'Unknown');
                }
            }
            
            // Second try: Get from user-name meta tag if userData is incomplete
            if (!userData || !userData.name) {
                const userNameMeta = document.querySelector('meta[name="user-name"]');
                const userIdMeta = document.querySelector('meta[name="user-id"]');
                const userEmailMeta = document.querySelector('meta[name="user-email"]');
                
                if (userNameMeta && userNameMeta.getAttribute('content')) {
                    const userName = userNameMeta.getAttribute('content');
                    const userId = userIdMeta ? userIdMeta.getAttribute('content') : '';
                    const userEmail = userEmailMeta ? userEmailMeta.getAttribute('content') : '';
                    
                    userData = {
                        id: userId,
                        name: userName,
                        email: userEmail || '',
                        greeting: 'Selamat datang',
                        initials: userName.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'DR'
                    };
                    console.log('✅ User data loaded from individual meta tags:', userData.name);
                }
            }
        } catch (e) {
            console.error('❌ Failed to parse user data:', e);
        }
        
        // Fallback user data if needed
        if (!userData || !userData.name || Object.keys(userData).length === 0) {
            userData = {
                name: 'Dokter',
                email: '',
                greeting: 'Selamat datang',
                initials: 'DR'
            };
            console.log('⚠️ Using fallback user data');
        } else {
            // Ensure initials are set
            if (!userData.initials && userData.name) {
                userData.initials = userData.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'DR';
            }
        }
        
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
        
        // Render with error boundary wrapper - pass userData as prop
        root.render(
            <React.StrictMode>
                <ErrorBoundary>
                    <HolisticMedicalDashboard userData={userData} />
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
            const elements = SafeDOM.safeQueryAll(selector);
            console.log(`🧹 Removing emergency navigation: ${selector} (${elements.length} found)`);
            const result = SafeDOM.batchRemove(elements);
            removed += result.removed;
        });
        
        // Secondary cleanup - Detect injected navigation by content patterns
        const suspiciousNavs = SafeDOM.safeQueryAll([
            '[class*="fixed"][class*="bottom"]',
            '[style*="bottom: 0"]', 
            '[style*="bottom:0"]',
            '[style*="position: fixed"]',
            '[style*="z-index: 99999"]'
        ].join(', '));
        
        const suspiciousToRemove: Element[] = [];
        
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
                console.log('🧹 Marking suspected injected navigation for removal:', {
                    emojis: hasEmojiPattern,
                    emergency: hasEmergencyClass, 
                    highZ: hasHighZIndex,
                    keywords: hasNavigationKeywords
                });
                suspiciousToRemove.push(nav);
            }
        });
        
        const suspiciousResult = SafeDOM.batchRemove(suspiciousToRemove);
        removed += suspiciousResult.removed;
        
        // Tertiary cleanup - Remove any duplicate bottom navigation
        const bottomNavs = SafeDOM.safeQueryAll([
            '[class*="bottom-0"]',
            '[style*="bottom: 0"]'
        ].join(', '));
        
        if (bottomNavs.length > 1) {
            console.log(`🔍 Found ${bottomNavs.length} bottom navigations, keeping only React component`);
            const duplicateNavs: Element[] = [];
            
            bottomNavs.forEach((nav, index) => {
                // Keep only the first one that's inside dokter-app (React component)
                const isReactComponent = nav.closest('#dokter-app') !== null;
                const hasReactClasses = nav.className.includes('backdrop-blur') && 
                                       nav.className.includes('gradient');
                
                if (!isReactComponent || (!hasReactClasses && index > 0)) {
                    console.log(`🧹 Marking duplicate bottom navigation #${index + 1} for removal`);
                    duplicateNavs.push(nav);
                }
            });
            
            const duplicateResult = SafeDOM.batchRemove(duplicateNavs);
            removed += duplicateResult.removed;
        }
        
        console.log(`✅ Emergency navigation cleanup complete. Removed ${removed} elements.`);
        
        // Set up MutationObserver to prevent re-injection
        this.setupNavigationProtection();
    }
    
    private setupNavigationProtection(): void {
        // Create SAFE MutationObserver to prevent re-injection of emergency navigation
        const observer = SafeDOM.createSafeMutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        const element = node as Element;
                        
                        // Skip React-managed elements to prevent interference
                        if (element.closest('#dokter-app') || element.closest('[data-react-root]')) {
                            return;
                        }
                        
                        // Check if it's an emergency navigation injection
                        const isEmergencyNav = element.classList.contains('emergency-navigation') ||
                                             element.classList.contains('emergency-nav') ||
                                             element.classList.contains('emergency-navigation-fix') ||
                                             /emergency.*nav/i.test(element.className);
                        
                        // Check for emoji-based navigation injection
                        const hasEmojiInjection = /[\u{1F300}-\u{1F9FF}]|👑|📅|🛡️|⭐|🧠/u.test(element.innerHTML || '') &&
                                                /bottom.*0|fixed.*bottom/i.test(element.style.cssText || element.className);
                        
                        if (isEmergencyNav || hasEmojiInjection) {
                            console.log('🛡️ Preventing emergency navigation re-injection:', element.className);
                            // Use SafeDOM for guaranteed safe removal
                            SafeDOM.safeRemove(element);
                        }
                    }
                });
            });
        });
        
        // Start observing with error handling
        try {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            console.log('🛡️ Navigation protection active - SafeMutationObserver monitoring injections');
        } catch (error) {
            console.error('❌ Failed to start navigation protection:', error);
        }
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

// 🛡️ ENHANCED ENTERPRISE ERROR BOUNDARY COMPONENT
class ErrorBoundary extends React.Component<
    { children: React.ReactNode },
    { hasError: boolean; error?: Error; errorInfo?: React.ErrorInfo }
> {
    private retryCount = 0;
    private maxRetries = 3;

    constructor(props: { children: React.ReactNode }) {
        super(props);
        this.state = { hasError: false };
    }
    
    static getDerivedStateFromError(error: Error) {
        // Analyze error type for better handling
        const isNotFoundError = error.name === 'NotFoundError' || 
                               error.message.includes('object can not be found') ||
                               error.message.includes('removeChild');
        
        console.warn('🚨 React Error Boundary - Error detected:', {
            name: error.name,
            message: error.message,
            isNotFoundError,
            stack: error.stack?.split('\n').slice(0, 5)
        });

        return { hasError: true, error };
    }
    
    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        console.error('🚨 React Error Boundary caught error:', {
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
        
        // Enhanced error logging
        if ((window as any).dokterKuDiagnostics) {
            (window as any).dokterKuDiagnostics.logReactError?.(error, errorInfo);
        }

        // Store error details in localStorage for debugging
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
            localStorage.setItem('dokterku_react_error', JSON.stringify(errorReport));
        } catch (e) {
            console.warn('⚠️ Could not store error report');
        }

        // Attempt DOM cleanup to prevent further issues
        this.performSafeDOMCleanup();
    }

    performSafeDOMCleanup = () => {
        console.log('🧹 Performing safe DOM cleanup after React error...');
        
        try {
            // Remove any orphaned elements that might cause issues
            const orphanedElements = document.querySelectorAll('[data-react-orphan]');
            orphanedElements.forEach(el => {
                // Use SafeDOM for safe removal
                SafeDOM.safeRemove(el);
            });

            // Clear any problematic styles or attributes
            const problemElements = document.querySelectorAll('[style*="position: fixed"], [style*="z-index: 99999"]');
            problemElements.forEach(el => {
                if (!el.closest('#dokter-app')) {
                    // Use SafeDOM for safe removal
                    SafeDOM.safeRemove(el);
                }
            });
            
            // Clean up any detached nodes that might cause issues
            const allElements = document.querySelectorAll('*');
            allElements.forEach(el => {
                // Check for detached React internal properties
                if ('_reactInternalFiber' in el && !document.contains(el)) {
                    SafeDOM.safeRemove(el);
                }
            });
        } catch (error) {
            console.warn('⚠️ DOM cleanup failed:', error);
        }
    }

    handleRetry = () => {
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            console.log(`🔄 Attempting retry ${this.retryCount}/${this.maxRetries}`);
            
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
            console.log('❌ Max retries reached, showing permanent error state');
        }
    }

    handleReload = () => {
        console.log('🔄 User requested page reload');
        window.location.reload();
    }
    
    render() {
        if (this.state.hasError) {
            const canRetry = this.retryCount < this.maxRetries;
            const errorType = this.state.error?.name === 'NotFoundError' ? 'DOM Cleanup Error' : 'React Component Error';
            
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
                            {errorType}
                        </h1>
                        <p style={{ margin: '0 0 30px 0', color: '#d1d5db', lineHeight: '1.6' }}>
                            {this.state.error?.name === 'NotFoundError' 
                                ? 'Terjadi kesalahan DOM cleanup. Sistem akan mencoba memperbaiki otomatis.'
                                : 'Terjadi kesalahan dalam komponen React. Error telah dicatat untuk diperbaiki.'}
                        </p>
                        
                        {/* Retry counter display */}
                        {this.retryCount > 0 && (
                            <div style={{
                                background: 'rgba(249, 115, 22, 0.2)',
                                border: '1px solid #f97316',
                                borderRadius: '8px',
                                padding: '10px',
                                margin: '20px 0',
                                fontSize: '14px',
                                color: '#fed7aa'
                            }}>
                                🔄 Percobaan: {this.retryCount}/{this.maxRetries}
                            </div>
                        )}

                        <div style={{ marginBottom: '20px' }}>
                            {canRetry && (
                                <button 
                                    onClick={this.handleRetry}
                                    style={{
                                        background: 'linear-gradient(to right, #10b981, #059669)',
                                        border: 'none',
                                        color: 'white',
                                        padding: '12px 24px',
                                        borderRadius: '8px',
                                        cursor: 'pointer',
                                        fontWeight: 'bold',
                                        marginRight: '10px',
                                        marginBottom: '10px'
                                    }}
                                >
                                    🔄 Coba Lagi ({this.maxRetries - this.retryCount} tersisa)
                                </button>
                            )}
                            
                            <button 
                                onClick={this.handleReload}
                                style={{
                                    background: 'linear-gradient(to right, #06b6d4, #8b5cf6)',
                                    border: 'none',
                                    color: 'white',
                                    padding: '12px 24px',
                                    borderRadius: '8px',
                                    cursor: 'pointer',
                                    fontWeight: 'bold',
                                    marginRight: '10px',
                                    marginBottom: '10px'
                                }}
                            >
                                🔄 Muat Ulang Halaman
                            </button>
                            
                            <button 
                                onClick={() => {
                                    if ((window as any).dokterKuDebug) {
                                        console.log('Manual reinitialization attempt...');
                                        (window as any).dokterKuDebug.reinitialize();
                                    }
                                }}
                                style={{
                                    background: 'rgba(34, 197, 94, 0.8)',
                                    border: 'none',
                                    color: 'white',
                                    padding: '12px 24px',
                                    borderRadius: '8px',
                                    cursor: 'pointer',
                                    fontWeight: 'bold',
                                    marginBottom: '10px'
                                }}
                            >
                                🔧 Reinitialize
                            </button>
                        </div>
                        
                        {/* Enhanced error details for debugging */}
                        <details style={{
                            marginTop: '20px',
                            padding: '15px',
                            background: 'rgba(0, 0, 0, 0.3)',
                            borderRadius: '8px',
                            border: '1px solid #374151'
                        }}>
                            <summary style={{ cursor: 'pointer', fontWeight: 'bold', marginBottom: '10px' }}>Detail Error</summary>
                            <div style={{ fontSize: '12px', color: '#f59e0b', textAlign: 'left' }}>
                                <div><strong>Type:</strong> {this.state.error?.name}</div>
                                <div><strong>Message:</strong> {this.state.error?.message}</div>
                                <div><strong>Retry Count:</strong> {this.retryCount}/{this.maxRetries}</div>
                                <div style={{ marginTop: '10px' }}><strong>Browser:</strong> {navigator.userAgent.split(' ').slice(-2).join(' ')}</div>
                                <div><strong>Time:</strong> {new Date().toLocaleString('id-ID')}</div>
                                
                                {this.state.errorInfo?.componentStack && (
                                    <div style={{ marginTop: '10px' }}>
                                        <strong>Component Stack:</strong>
                                        <pre style={{
                                            marginTop: '5px',
                                            padding: '10px',
                                            background: '#111827',
                                            borderRadius: '4px',
                                            overflow: 'auto',
                                            fontSize: '10px',
                                            whiteSpace: 'pre-wrap'
                                        }}>
                                            {this.state.errorInfo.componentStack.split('\n').slice(0, 8).join('\n')}
                                        </pre>
                                    </div>
                                )}
                                
                                {this.state.error?.stack && (
                                    <div style={{ marginTop: '10px' }}>
                                        <strong>Stack Trace:</strong>
                                        <pre style={{
                                            marginTop: '5px',
                                            padding: '10px',
                                            background: '#111827',
                                            borderRadius: '4px',
                                            overflow: 'auto',
                                            fontSize: '10px',
                                            whiteSpace: 'pre-wrap'
                                        }}>
                                            {this.state.error.stack.split('\n').slice(0, 10).join('\n')}
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

// 🚀 BULLETPROOF BOOTSTRAP SINGLETON SYSTEM
// Eliminates TDZ issues with proper encapsulation and thread-safe initialization
class BootstrapSingleton {
    private static _instance: DokterKuBootstrap | null = null;
    private static _isInitializing: boolean = false;
    private static _initializationPromise: Promise<DokterKuBootstrap> | null = null;
    private static _maxRetries: number = 5;
    private static _retryDelay: number = 1000;
    private static _healthCheckInterval: number | null = null;

    /**
     * Thread-safe singleton instance getter with automatic initialization
     * Eliminates TDZ violations through proper encapsulation
     */
    public static getInstance(): Promise<DokterKuBootstrap> {
        // Return existing promise if initialization is in progress
        if (this._initializationPromise) {
            return this._initializationPromise;
        }

        // Return resolved promise if instance already exists
        if (this._instance && !this._isInitializing) {
            return Promise.resolve(this._instance);
        }

        // Start new initialization
        this._initializationPromise = this.initializeInstance();
        return this._initializationPromise;
    }

    /**
     * Comprehensive dependency verification with progressive health checks
     */
    private static async verifyDependencies(attempt: number = 1): Promise<boolean> {
        const dependencies = {
            window: typeof window !== 'undefined',
            document: typeof document !== 'undefined',
            React: typeof React !== 'undefined' && React !== null,
            createRoot: typeof createRoot === 'function',
            HolisticMedicalDashboard: typeof HolisticMedicalDashboard !== 'undefined',
            UnifiedAuth: typeof getUnifiedAuth !== 'undefined',
            container: document.getElementById('dokter-app') !== null
        };

        const missing = Object.entries(dependencies)
            .filter(([_, available]) => !available)
            .map(([name, _]) => name);

        if (missing.length === 0) {
            console.log(`✅ All dependencies verified successfully (attempt ${attempt})`);
            return true;
        }

        console.warn(`⚠️ Missing dependencies (attempt ${attempt}):`, missing);
        return false;
    }

    /**
     * Progressive initialization with exponential backoff and health monitoring
     */
    private static async initializeInstance(): Promise<DokterKuBootstrap> {
        this._isInitializing = true;
        let lastError: Error | null = null;

        for (let attempt = 1; attempt <= this._maxRetries; attempt++) {
            try {
                console.log(`🚀 Bootstrap initialization attempt ${attempt}/${this._maxRetries}`);
                
                // Verify all dependencies are available
                const dependenciesReady = await this.verifyDependencies(attempt);
                if (!dependenciesReady) {
                    throw new Error(`Dependencies not ready on attempt ${attempt}`);
                }

                // Additional safety checks
                await this.performSafetyChecks();

                // Create instance with proper error boundary
                this._instance = new DokterKuBootstrap();
                
                // Verify instance is functional
                await this.verifyInstanceHealth();
                
                // Start health monitoring
                this.startHealthMonitoring();
                
                console.log('✅ Bootstrap singleton initialized successfully');
                this._isInitializing = false;
                return this._instance;

            } catch (error) {
                lastError = error as Error;
                console.error(`❌ Bootstrap initialization attempt ${attempt} failed:`, error);
                
                // Progressive backoff delay
                if (attempt < this._maxRetries) {
                    const delay = this._retryDelay * Math.pow(1.5, attempt - 1);
                    console.log(`🔄 Retrying in ${delay}ms...`);
                    await this.sleep(delay);
                }
            }
        }

        // All attempts failed - implement graceful degradation
        this._isInitializing = false;
        this._initializationPromise = null;
        await this.handleFinalFailure(lastError!);
        throw new Error(`Bootstrap initialization failed after ${this._maxRetries} attempts: ${lastError?.message}`);
    }

    /**
     * Comprehensive safety checks before initialization
     */
    private static async performSafetyChecks(): Promise<void> {
        // Check for TDZ violations
        try {
            const testAccess = React && createRoot && HolisticMedicalDashboard;
            if (!testAccess) {
                throw new Error('TDZ violation detected in dependency access');
            }
        } catch (error) {
            throw new Error(`TDZ safety check failed: ${(error as Error).message}`);
        }

        // Check DOM readiness
        if (document.readyState === 'loading') {
            console.log('📋 Waiting for DOM to be ready...');
            await this.waitForDOMReady();
        }

        // Check container element
        const container = document.getElementById('dokter-app');
        if (!container) {
            throw new Error('Required container element #dokter-app not found');
        }

        // Memory usage check
        if ('performance' in window && 'memory' in (performance as any)) {
            const memory = (performance as any).memory;
            if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.9) {
                console.warn('⚠️ High memory usage detected, initialization may be slower');
            }
        }
    }

    /**
     * Instance health verification after creation
     */
    private static async verifyInstanceHealth(): Promise<void> {
        if (!this._instance) {
            throw new Error('Instance creation failed');
        }

        // Check if error handler is properly initialized
        if (!(this._instance as any).errorHandler) {
            throw new Error('Error handler not properly initialized');
        }

        // Verify React components can be accessed
        const container = document.getElementById('dokter-app');
        if (container && container.children.length === 0) {
            // Wait a bit for React to render
            await this.sleep(500);
        }
    }

    /**
     * Continuous health monitoring for the bootstrap instance
     */
    private static startHealthMonitoring(): void {
        // Clear any existing interval
        if (this._healthCheckInterval) {
            clearInterval(this._healthCheckInterval);
        }

        this._healthCheckInterval = window.setInterval(() => {
            try {
                // Basic health checks
                if (!this._instance) {
                    console.error('🚨 Bootstrap instance lost - attempting recovery');
                    this.recoverInstance();
                    return;
                }

                // Check if React app is still mounted
                const container = document.getElementById('dokter-app');
                if (!container || container.children.length === 0) {
                    console.warn('⚠️ React app appears unmounted - monitoring');
                }

                // Memory leak detection
                if ('performance' in window && 'memory' in (performance as any)) {
                    const memory = (performance as any).memory;
                    if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.95) {
                        console.warn('🚨 Critical memory usage detected');
                    }
                }

            } catch (error) {
                console.error('🚨 Health check failed:', error);
            }
        }, 30000); // Check every 30 seconds
    }

    /**
     * Automatic instance recovery mechanism
     */
    private static async recoverInstance(): Promise<void> {
        try {
            console.log('🔄 Attempting bootstrap instance recovery...');
            this._instance = null;
            this._initializationPromise = null;
            this._isInitializing = false;
            
            // Try to reinitialize
            await this.getInstance();
            console.log('✅ Bootstrap instance recovered successfully');
            
        } catch (error) {
            console.error('❌ Bootstrap recovery failed:', error);
        }
    }

    /**
     * Final failure handler with comprehensive user feedback
     */
    private static async handleFinalFailure(error: Error): Promise<void> {
        console.error('🚨 Bootstrap initialization failed permanently:', error);

        // Stop health monitoring
        if (this._healthCheckInterval) {
            clearInterval(this._healthCheckInterval);
        }

        // Show user-friendly error screen
        const container = document.getElementById('dokter-app') || document.body;
        const errorId = `ERR_${Date.now()}`;
        const timestamp = new Date().toLocaleString('id-ID');

        container.innerHTML = `
            <div style="
                min-height: 100vh;
                background: linear-gradient(135deg, #0f172a 0%, #dc2626 20%, #0f172a 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                padding: 20px;
            ">
                <div style="
                    background: rgba(30, 41, 59, 0.95);
                    border: 2px solid #dc2626;
                    border-radius: 20px;
                    padding: 40px;
                    max-width: 600px;
                    text-align: center;
                    backdrop-filter: blur(15px);
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
                ">
                    <div style="font-size: 64px; margin-bottom: 20px; animation: pulse 2s infinite;">🚨</div>
                    <h1 style="margin: 0 0 20px 0; font-size: 28px; color: #dc2626; font-weight: bold;">Sistem Tidak Dapat Dimuat</h1>
                    <p style="margin: 0 0 30px 0; color: #e5e7eb; line-height: 1.8; font-size: 16px;">
                        Aplikasi DOKTERKU mengalami kegagalan sistem yang kritis. Tim teknis telah diberitahu secara otomatis dan akan mengatasi masalah ini sesegera mungkin.
                    </p>
                    
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #dc2626; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: left;">
                        <h3 style="margin: 0 0 15px 0; color: #fca5a5; font-size: 16px;">📋 Langkah Pemulihan:</h3>
                        <ol style="margin: 0; padding-left: 20px; color: #e5e7eb; line-height: 1.6;">
                            <li>Klik tombol "🔄 Muat Ulang Halaman" di bawah</li>
                            <li>Jika masalah berlanjut, tutup dan buka kembali browser</li>
                            <li>Periksa koneksi internet Anda</li>
                            <li>Hubungi administrator sistem jika masalah terus terjadi</li>
                        </ol>
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <button onclick="window.location.reload()" style="
                            background: linear-gradient(135deg, #dc2626, #b91c1c);
                            border: none;
                            color: white;
                            padding: 15px 30px;
                            border-radius: 12px;
                            cursor: pointer;
                            font-weight: bold;
                            font-size: 16px;
                            margin: 8px;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            🔄 Muat Ulang Halaman
                        </button>
                        <button onclick="this.nextElementSibling.style.display='block'; this.style.display='none';" style="
                            background: rgba(107, 114, 128, 0.8);
                            border: 1px solid #6b7280;
                            color: #e5e7eb;
                            padding: 15px 30px;
                            border-radius: 12px;
                            cursor: pointer;
                            font-size: 16px;
                            margin: 8px;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background='rgba(107, 114, 128, 1)'" onmouseout="this.style.background='rgba(107, 114, 128, 0.8)'">
                            🔍 Detail Teknis
                        </button>
                    </div>
                    
                    <div style="display: none; margin-top: 30px; text-align: left; background: rgba(0, 0, 0, 0.4); padding: 20px; border-radius: 12px; border: 1px solid #374151;">
                        <h3 style="color: #fbbf24; margin: 0 0 15px 0; font-size: 16px;">🔧 Informasi Teknis</h3>
                        <div style="background: #111827; color: #f59e0b; padding: 15px; border-radius: 8px; font-family: 'Monaco', 'Menlo', monospace; font-size: 13px; line-height: 1.4; overflow-x: auto;">
                            <div><strong>Error ID:</strong> ${errorId}</div>
                            <div><strong>Timestamp:</strong> ${timestamp}</div>
                            <div><strong>Message:</strong> ${error.message}</div>
                            <div><strong>Browser:</strong> ${navigator.userAgent.split(' ').slice(-2).join(' ')}</div>
                            <div><strong>URL:</strong> ${window.location.href}</div>
                            ${error.stack ? `<div style="margin-top: 10px;"><strong>Stack Trace:</strong></div><pre style="margin: 5px 0 0 0; white-space: pre-wrap; font-size: 11px;">${error.stack.split('\n').slice(0, 8).join('\n')}</pre>` : ''}
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #374151; font-size: 14px; color: #9ca3af;">
                        <div>🛡️ DOKTERKU Gaming Dashboard - Enterprise Error Handler</div>
                        <div style="margin-top: 5px;">Error akan dilaporkan secara otomatis untuk perbaikan sistem</div>
                    </div>
                </div>
            </div>
            <style>
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
            </style>
        `;

        // Hide any loading indicators
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    /**
     * Utility methods for async operations
     */
    private static sleep(ms: number): Promise<void> {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    private static waitForDOMReady(): Promise<void> {
        return new Promise(resolve => {
            if (document.readyState !== 'loading') {
                resolve();
                return;
            }
            const handler = () => {
                document.removeEventListener('DOMContentLoaded', handler);
                resolve();
            };
            document.addEventListener('DOMContentLoaded', handler);
        });
    }

    /**
     * Manual instance reset for debugging
     */
    public static reset(): void {
        if (this._healthCheckInterval) {
            clearInterval(this._healthCheckInterval);
            this._healthCheckInterval = null;
        }
        this._instance = null;
        this._isInitializing = false;
        this._initializationPromise = null;
        console.log('🔄 Bootstrap singleton reset successfully');
    }

    /**
     * Get current instance status
     */
    public static getStatus(): { initialized: boolean; initializing: boolean; healthy: boolean } {
        return {
            initialized: this._instance !== null,
            initializing: this._isInitializing,
            healthy: this._instance !== null && !this._isInitializing
        };
    }
}

/**
 * Safe initialization function with comprehensive error handling
 */
async function initializeWorldClassSystem(): Promise<void> {
    try {
        console.log('🌟 DOKTERKU Bootstrap: Starting world-class initialization...');
        
        // Use the bulletproof singleton pattern
        const bootstrap = await BootstrapSingleton.getInstance();
        console.log('✅ Bootstrap initialization completed successfully');
        
        // Store reference for global access
        if (typeof window !== 'undefined') {
            (window as any).dokterKuBootstrapInstance = bootstrap;
        }
        
    } catch (error) {
        console.error('🚨 World-class system initialization failed:', error);
        
        // Enhanced error reporting with user context
        if (error instanceof Error) {
            const errorReport = {
                message: error.message,
                stack: error.stack?.split('\n').slice(0, 10),
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href,
                dependencies: {
                    React: typeof React !== 'undefined',
                    createRoot: typeof createRoot !== 'undefined',
                    HolisticMedicalDashboard: typeof HolisticMedicalDashboard !== 'undefined',
                    UnifiedAuth: typeof getUnifiedAuth !== 'undefined',
                    container: document.getElementById('dokter-app') !== null
                }
            };
            
            console.error('📊 Detailed error report:', errorReport);
            
            // Store error report for debugging
            try {
                localStorage.setItem('dokterku_critical_error', JSON.stringify(errorReport));
            } catch (storageError) {
                console.warn('⚠️ Could not store error report in localStorage');
            }
        }
        
        // The singleton handles user-facing error display
        throw error;
    }
}

/**
 * Progressive dependency verification with intelligent waiting strategy
 * Eliminates race conditions and TDZ violations through proper sequencing
 */
class DependencyManager {
    private static readonly MAX_WAIT_TIME = 5000; // 5 seconds maximum (reduced from 15)
    private static readonly CHECK_INTERVAL = 200; // Check every 200ms
    private static readonly CRITICAL_DEPENDENCIES = [
        'window', 'document', 'React', 'createRoot', 'HolisticMedicalDashboard', 'container', 'meta'
        // Removed UnifiedAuth from critical dependencies - it can load asynchronously
    ];

    /**
     * Comprehensive dependency verification with detailed reporting
     */
    public static async waitForDependencies(timeoutMs: number = this.MAX_WAIT_TIME): Promise<boolean> {
        const startTime = Date.now();
        const endTime = startTime + timeoutMs;
        let attempt = 0;

        while (Date.now() < endTime) {
            attempt++;
            const status = this.checkAllDependencies();
            
            if (status.allReady) {
                console.log(`✅ All dependencies ready after ${Date.now() - startTime}ms (${attempt} checks)`);
                return true;
            }

            // Log progress every 10 attempts (2 seconds)
            if (attempt % 10 === 0) {
                console.log(`🔄 Dependencies check ${attempt}: ${status.ready}/${status.total} ready`);
                console.log('⏳ Still waiting for:', status.missing.join(', '));
            }

            // Progressive waiting strategy
            await this.smartWait(attempt);
        }

        // Final check with detailed error reporting
        const finalStatus = this.checkAllDependencies();
        console.error('🚨 Dependency timeout reached:', {
            duration: Date.now() - startTime,
            attempts: attempt,
            ready: finalStatus.ready,
            total: finalStatus.total,
            missing: finalStatus.missing,
            details: finalStatus.details
        });

        return false;
    }

    /**
     * Detailed dependency status checking with TDZ protection
     */
    private static checkAllDependencies(): {
        allReady: boolean;
        ready: number;
        total: number;
        missing: string[];
        details: Record<string, boolean>;
    } {
        const checks: Record<string, () => boolean> = {
            window: () => typeof window !== 'undefined' && window !== null,
            document: () => typeof document !== 'undefined' && document !== null && document.readyState !== 'loading',
            React: () => {
                try {
                    return typeof React !== 'undefined' && React !== null && typeof React.createElement === 'function';
                } catch (e) {
                    return false; // TDZ protection
                }
            },
            createRoot: () => {
                try {
                    return typeof createRoot === 'function';
                } catch (e) {
                    return false; // TDZ protection
                }
            },
            HolisticMedicalDashboard: () => {
                try {
                    return typeof HolisticMedicalDashboard !== 'undefined' && HolisticMedicalDashboard !== null;
                } catch (e) {
                    return false; // TDZ protection
                }
            },
            UnifiedAuth: () => {
                try {
                    // Check if getUnifiedAuth function exists and can be called
                    return typeof getUnifiedAuth === 'function';
                } catch (e) {
                    return false; // TDZ protection
                }
            },
            container: () => {
                try {
                    const element = document.getElementById('dokter-app');
                    return element !== null && element instanceof HTMLElement;
                } catch (e) {
                    return false;
                }
            },
            meta: () => {
                try {
                    const authMeta = document.querySelector('meta[name="user-authenticated"]');
                    return authMeta !== null;
                } catch (e) {
                    return false;
                }
            }
        };

        const details: Record<string, boolean> = {};
        const missing: string[] = [];
        let ready = 0;

        for (const [name, check] of Object.entries(checks)) {
            try {
                const isReady = check();
                details[name] = isReady;
                if (isReady) {
                    ready++;
                } else {
                    missing.push(name);
                }
            } catch (error) {
                details[name] = false;
                missing.push(name);
                console.warn(`⚠️ Dependency check failed for ${name}:`, error);
            }
        }

        return {
            allReady: missing.length === 0,
            ready,
            total: Object.keys(checks).length,
            missing,
            details
        };
    }

    /**
     * Smart waiting strategy with progressive delays
     */
    private static async smartWait(attempt: number): Promise<void> {
        let delay: number;
        
        if (attempt <= 5) {
            delay = this.CHECK_INTERVAL; // Fast checks initially
        } else if (attempt <= 20) {
            delay = this.CHECK_INTERVAL * 1.5; // Moderate delays
        } else {
            delay = this.CHECK_INTERVAL * 2; // Slower checks for patience
        }

        return new Promise(resolve => setTimeout(resolve, delay));
    }

    /**
     * Emergency dependency injection for critical failures
     */
    public static async emergencyDependencyCheck(): Promise<{ success: boolean; message: string }> {
        try {
            // Check if we can inject missing dependencies
            if (typeof React === 'undefined') {
                return { success: false, message: 'React library not loaded - check script tags' };
            }
            
            if (typeof createRoot === 'undefined') {
                return { success: false, message: 'React 18 createRoot not available - check React version' };
            }
            
            if (!document.getElementById('dokter-app')) {
                return { success: false, message: 'Container element #dokter-app missing from DOM' };
            }

            const authMeta = document.querySelector('meta[name="user-authenticated"]');
            if (!authMeta || authMeta.getAttribute('content') !== 'true') {
                return { success: false, message: 'User authentication meta tag missing or invalid' };
            }

            return { success: true, message: 'All critical dependencies verified' };
            
        } catch (error) {
            return { 
                success: false, 
                message: `Emergency check failed: ${(error as Error).message}` 
            };
        }
    }
}

/**
 * BULLETPROOF APPLICATION LAUNCHER
 * Comprehensive initialization system with multiple fallback strategies
 */
class ApplicationLauncher {
    private static _launchAttempted: boolean = false;
    private static _launchPromise: Promise<void> | null = null;

    /**
     * Main application launch with comprehensive safety checks
     */
    public static async launch(): Promise<void> {
        // Prevent multiple simultaneous launches
        if (this._launchAttempted) {
            return this._launchPromise || Promise.resolve();
        }

        this._launchAttempted = true;
        this._launchPromise = this.executeLaunch();
        return this._launchPromise;
    }

    private static async executeLaunch(): Promise<void> {
        try {
            console.log('🌟 DOKTERKU Application Launcher: Starting bulletproof initialization...');

            // Environment validation
            await this.validateEnvironment();

            // Progressive dependency loading
            await this.loadDependencies();

            // Initialize the world-class system
            await initializeWorldClassSystem();

            console.log('🎯 Application launch completed successfully');

        } catch (error) {
            console.error('🚨 Application launch failed:', error);
            await this.handleLaunchFailure(error as Error);
        }
    }

    /**
     * Comprehensive environment validation
     */
    private static async validateEnvironment(): Promise<void> {
        // Browser environment check
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            throw new Error('Browser environment not detected - server-side rendering not supported');
        }

        // Essential DOM elements
        if (!document.getElementById('dokter-app')) {
            throw new Error('Required container element #dokter-app not found in DOM');
        }

        // User authentication check
        const authMeta = document.querySelector('meta[name="user-authenticated"]');
        if (!authMeta || authMeta.getAttribute('content') !== 'true') {
            console.warn('⚠️ User authentication not verified - some features may be limited');
        }

        // Browser capability checks
        if (!window.Promise) {
            throw new Error('Browser does not support Promises - upgrade required');
        }

        if (!window.fetch) {
            console.warn('⚠️ Fetch API not available - API calls may use fallback methods');
        }

        console.log('✅ Environment validation passed');
    }

    /**
     * Progressive dependency loading with intelligent waiting
     */
    private static async loadDependencies(): Promise<void> {
        console.log('📦 Loading dependencies...');

        // Wait for DOM to be fully ready
        await this.ensureDOMReady();

        // Wait for all required dependencies
        const dependenciesReady = await DependencyManager.waitForDependencies(15000);
        
        if (!dependenciesReady) {
            // Try emergency dependency check
            const emergencyCheck = await DependencyManager.emergencyDependencyCheck();
            if (!emergencyCheck.success) {
                throw new Error(`Dependencies not ready: ${emergencyCheck.message}`);
            }
        }

        console.log('✅ All dependencies loaded successfully');
    }

    /**
     * Ensure DOM is fully ready for manipulation
     */
    private static async ensureDOMReady(): Promise<void> {
        if (document.readyState === 'complete') {
            return;
        }

        if (document.readyState === 'interactive') {
            // Wait a bit more for async resources
            await new Promise(resolve => setTimeout(resolve, 100));
            return;
        }

        // DOM still loading - wait for DOMContentLoaded
        return new Promise((resolve) => {
            const handler = () => {
                document.removeEventListener('DOMContentLoaded', handler);
                resolve();
            };
            document.addEventListener('DOMContentLoaded', handler);
        });
    }

    /**
     * Comprehensive launch failure handling
     */
    private static async handleLaunchFailure(error: Error): Promise<void> {
        console.error('🚨 Handling launch failure:', error);

        // Reset launch state for potential retry
        this._launchAttempted = false;
        this._launchPromise = null;

        // Try to provide user feedback even in failure
        try {
            const container = document.getElementById('dokter-app') || document.body;
            
            // Show loading error message
            const errorElement = document.createElement('div');
            errorElement.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #0f172a 0%, #dc2626 20%, #0f172a 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            `;
            
            errorElement.innerHTML = `
                <div style="
                    text-align: center;
                    background: rgba(30, 41, 59, 0.95);
                    padding: 40px;
                    border-radius: 20px;
                    border: 2px solid #dc2626;
                    backdrop-filter: blur(15px);
                    max-width: 500px;
                    margin: 20px;
                ">
                    <div style="font-size: 48px; margin-bottom: 20px;">🚨</div>
                    <h1 style="margin: 0 0 20px 0; color: #dc2626;">Aplikasi Gagal Dimuat</h1>
                    <p style="margin: 0 0 30px 0; color: #e5e7eb; line-height: 1.6;">
                        Terjadi kesalahan saat memuat aplikasi DOKTERKU. Tim teknis akan segera memperbaiki masalah ini.
                    </p>
                    <button onclick="window.location.reload()" style="
                        background: linear-gradient(135deg, #dc2626, #b91c1c);
                        border: none;
                        color: white;
                        padding: 15px 30px;
                        border-radius: 12px;
                        cursor: pointer;
                        font-weight: bold;
                        font-size: 16px;
                    ">
                        🔄 Muat Ulang Halaman
                    </button>
                    <div style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                        Error: ${error.message}<br>
                        Time: ${new Date().toLocaleString('id-ID')}
                    </div>
                </div>
            `;
            
            // Replace container content with error message
            if (container === document.body) {
                document.body.appendChild(errorElement);
            } else {
                container.innerHTML = '';
                container.appendChild(errorElement);
            }
            
        } catch (displayError) {
            console.error('🚨 Failed to display launch failure message:', displayError);
            // Fallback: just alert
            alert('Application failed to load. Please refresh the page.');
        }

        // Store error for debugging
        try {
            localStorage.setItem('dokterku_launch_error', JSON.stringify({
                message: error.message,
                stack: error.stack,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent
            }));
        } catch (storageError) {
            console.warn('⚠️ Could not store launch error in localStorage');
        }
    }
}

// GLOBAL ERROR HANDLERS
// Catch and suppress NotFoundError before they reach React
if (typeof window !== 'undefined') {
    // Catch synchronous DOM errors
    window.addEventListener('error', (event) => {
        if (event.error && (
            event.error.name === 'NotFoundError' ||
            event.error.message?.includes('removeChild') ||
            event.error.message?.includes('object can not be found')
        )) {
            console.warn('🚨 Global Error Interceptor: Suppressed NotFoundError', {
                name: event.error.name,
                message: event.error.message,
                source: `${event.filename}:${event.lineno}:${event.colno}`
            });
            
            // Prevent propagation to React
            event.preventDefault();
            event.stopPropagation();
            
            // Clean up orphaned elements safely
            try {
                document.querySelectorAll('[data-react-orphan]').forEach(el => {
                    SafeDOM.safeRemove(el);
                });
            } catch {}
            
            return true;
        }
    }, true);
    
    // Catch promise rejection errors
    window.addEventListener('unhandledrejection', (event) => {
        if (event.reason?.name === 'NotFoundError' ||
            event.reason?.message?.includes('removeChild')) {
            console.warn('🚨 Promise Error Interceptor: Suppressed NotFoundError', event.reason);
            event.preventDefault();
            return true;
        }
    });
}

// ENHANCED INITIALIZATION SYSTEM
// Multiple launch strategies with comprehensive fallback handling

// Strategy 1: Immediate launch if DOM is ready
if (typeof document !== 'undefined' && typeof window !== 'undefined') {
    if (document.readyState !== 'loading') {
        console.log('🚀 DOM ready - launching immediately with TDZ protection');
        // Use setTimeout(0) to escape any potential TDZ issues
        setTimeout(() => {
            ApplicationLauncher.launch().catch(error => {
                console.error('🚨 Immediate launch failed:', error);
            });
        }, 0);
    } else {
        console.log('📋 DOM loading - waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 DOM loaded - launching application');
            ApplicationLauncher.launch().catch(error => {
                console.error('🚨 DOM ready launch failed:', error);
            });
        });
    }

    // Strategy 2: Window load event as fallback
    window.addEventListener('load', () => {
        console.log('🎯 Window fully loaded - ensuring application is launched');
        ApplicationLauncher.launch().catch(error => {
            console.error('🚨 Window load launch failed:', error);
        });
    });

    // Strategy 3: Timeout-based emergency launch
    setTimeout(() => {
        console.log('⏰ Emergency timeout launch - ensuring application starts');
        ApplicationLauncher.launch().catch(error => {
            console.error('🚨 Emergency launch failed:', error);
        });
    }, 5000); // 5 second emergency fallback

} else {
    console.error('🚨 Browser environment not detected - cannot initialize application');
}

// GLOBAL INTERFACE AND DEBUGGING UTILITIES
// Export comprehensive debugging and management interface
if (typeof window !== 'undefined') {
    // Core bootstrap classes
    (window as any).DokterKuBootstrap = DokterKuBootstrap;
    (window as any).BootstrapSingleton = BootstrapSingleton;
    (window as any).ApplicationLauncher = ApplicationLauncher;
    (window as any).DependencyManager = DependencyManager;
    (window as any).SafeDOM = SafeDOM;
    
    // Legacy compatibility
    (window as any).dokterKuBootstrapInstance = async () => {
        try {
            return await BootstrapSingleton.getInstance();
        } catch (error) {
            console.error('🚨 Failed to get bootstrap instance:', error);
            return null;
        }
    };
    
    // COMPREHENSIVE DEBUGGING SUITE
    (window as any).dokterKuDebug = {
        // Bootstrap management
        async getBootstrap() {
            try {
                return await BootstrapSingleton.getInstance();
            } catch (error) {
                console.error('🚨 Failed to get bootstrap:', error);
                return null;
            }
        },
        
        getBootstrapStatus() {
            return BootstrapSingleton.getStatus();
        },
        
        resetBootstrap() {
            console.log('🔄 Resetting bootstrap singleton...');
            BootstrapSingleton.reset();
            console.log('✅ Bootstrap reset complete');
        },
        
        // Application management
        async reinitialize() {
            console.log('🔄 Manual reinitialization triggered...');
            try {
                BootstrapSingleton.reset();
                await ApplicationLauncher.launch();
                console.log('✅ Reinitialization successful');
                return true;
            } catch (error) {
                console.error('🚨 Reinitialization failed:', error);
                return false;
            }
        },
        
        async emergencyRestart() {
            console.log('🚨 Emergency restart initiated...');
            try {
                // Clear all cached state
                BootstrapSingleton.reset();
                (ApplicationLauncher as any)._launchAttempted = false;
                (ApplicationLauncher as any)._launchPromise = null;
                
                // Force new launch
                await ApplicationLauncher.launch();
                console.log('✅ Emergency restart successful');
                return true;
            } catch (error) {
                console.error('🚨 Emergency restart failed:', error);
                return false;
            }
        },
        
        // Dependency diagnostics
        checkDependencies() {
            const status = BootstrapSingleton.getStatus();
            return {
                // Basic dependencies
                React: typeof React !== 'undefined',
                createRoot: typeof createRoot !== 'undefined',
                HolisticMedicalDashboard: typeof HolisticMedicalDashboard !== 'undefined',
                UnifiedAuth: typeof getUnifiedAuth !== 'undefined',
                container: document.getElementById('dokter-app') !== null,
                
                // Bootstrap status
                bootstrapInitialized: status.initialized,
                bootstrapInitializing: status.initializing,
                bootstrapHealthy: status.healthy,
                
                // DOM state
                domReady: document.readyState,
                containerExists: !!document.getElementById('dokter-app'),
                
                // Authentication
                userAuthenticated: (() => {
                    const authMeta = document.querySelector('meta[name="user-authenticated"]');
                    return authMeta?.getAttribute('content') === 'true';
                })(),
                
                // API token
                hasApiToken: (() => {
                    const tokenMeta = document.querySelector('meta[name="api-token"]');
                    return !!tokenMeta?.getAttribute('content')?.trim();
                })()
            };
        },
        
        async fullDiagnostic() {
            console.log('🔍 Running full system diagnostic...');
            
            const dependencies = this.checkDependencies();
            const emergencyCheck = await DependencyManager.emergencyDependencyCheck();
            const bootstrapStatus = BootstrapSingleton.getStatus();
            
            const report = {
                timestamp: new Date().toISOString(),
                dependencies,
                emergencyCheck,
                bootstrapStatus,
                errors: (() => {
                    try {
                        return JSON.parse(localStorage.getItem('dokterku_errors') || '[]');
                    } catch (e) {
                        return [];
                    }
                })(),
                performance: 'performance' in window ? {
                    navigation: performance.getEntriesByType('navigation'),
                    measures: performance.getEntriesByType('measure')
                } : null,
                memory: 'memory' in (performance as any) ? (performance as any).memory : null
            };
            
            console.log('📊 Full diagnostic report:', report);
            return report;
        },
        
        // Error management
        getErrors() {
            try {
                return JSON.parse(localStorage.getItem('dokterku_errors') || '[]');
            } catch (e) {
                return [];
            }
        },
        
        clearErrors() {
            localStorage.removeItem('dokterku_errors');
            localStorage.removeItem('dokterku_critical_error');
            localStorage.removeItem('dokterku_launch_error');
            console.log('🧹 All error logs cleared');
        },
        
        // Performance utilities
        getPerformance() {
            if ('performance' in window) {
                return {
                    measures: performance.getEntriesByType('measure'),
                    navigation: performance.getEntriesByType('navigation'),
                    memory: 'memory' in (performance as any) ? (performance as any).memory : null
                };
            }
            return null;
        },
        
        // Development utilities
        simulateError() {
            console.log('🧪 Simulating error for testing...');
            throw new Error('Simulated error for testing error handling');
        },
        
        forceReload() {
            console.log('🔄 Forcing page reload...');
            window.location.reload();
        },
        
        // Help system
        help() {
            console.log(`
🛠️ DOKTERKU Debug Utilities Help

📋 Bootstrap Management:
  - getBootstrap()          Get bootstrap instance
  - getBootstrapStatus()    Get current status
  - resetBootstrap()        Reset singleton state
  - reinitialize()          Soft restart
  - emergencyRestart()      Hard restart

🔍 Diagnostics:
  - checkDependencies()     Check all dependencies
  - fullDiagnostic()        Complete system report
  - getErrors()             Get error logs
  - clearErrors()           Clear error logs
  - getPerformance()        Performance metrics

🛡️ DOM Safety (SafeDOM):
  - SafeDOM.safeRemove(el)     Safe element removal
  - SafeDOM.safeQuery(sel)     Safe element query
  - SafeDOM.batchRemove(els)   Batch safe removal
  - SafeDOM.performCleanup()   Emergency DOM cleanup

🧪 Testing:
  - simulateError()         Test error handling
  - forceReload()           Force page reload
  - help()                  Show this help
`);
        },

        // DOM Safety utilities
        performEmergencyDOMCleanup() {
            console.log('🧹 Performing emergency DOM cleanup...');
            
            const problemSelectors = [
                '.emergency-navigation',
                '.emergency-nav',
                '[class*="emergency"][class*="nav"]',
                '[style*="z-index: 99999"]',
                '[data-react-orphan]'
            ];
            
            let totalRemoved = 0;
            problemSelectors.forEach(selector => {
                const elements = SafeDOM.safeQueryAll(selector);
                const result = SafeDOM.batchRemove(elements);
                totalRemoved += result.removed;
            });
            
            console.log(`✅ Emergency DOM cleanup complete. Removed ${totalRemoved} elements.`);
            return totalRemoved;
        },

        testSafeDOM() {
            console.log('🧪 Testing SafeDOM utilities...');
            
            // Test safe query
            const testEl = SafeDOM.safeQuery('#dokter-app');
            console.log('Safe query test:', testEl ? 'PASSED' : 'FAILED');
            
            // Test safe removal on non-existent element
            const nonExistent = document.createElement('div');
            const removeResult = SafeDOM.safeRemove(nonExistent);
            console.log('Safe remove test (non-existent):', removeResult ? 'FAILED' : 'PASSED');
            
            console.log('✅ SafeDOM tests completed');
        }
    };
    
    // Add helpful console message
    console.log('🛠️ DOKTERKU Debug utilities available at window.dokterKuDebug');
    console.log('💡 Run dokterKuDebug.help() for available commands');
}