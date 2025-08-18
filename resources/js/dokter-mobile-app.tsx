import React from 'react';
import { createRoot } from 'react-dom/client';
import OriginalDokterDashboard from './components/dokter/OriginalDashboard';
import RefactoredDashboard from './components/dokter/RefactoredDashboard';
import HolisticMedicalDashboard from './components/dokter/HolisticMedicalDashboard';
import HolisticMedicalDashboardOptimized from './components/dokter/HolisticMedicalDashboardOptimized';
import OptimizedOriginalDashboard from './components/dokter/OptimizedOriginalDashboard';
import ErrorBoundary from './components/ErrorBoundary';
import getUnifiedAuth from './utils/UnifiedAuth';
import { performanceMonitor } from './utils/PerformanceMonitor';
import '../css/app.css';
import '../css/responsive-typography.css';
import './setup-csrf';

// 🚀 Import Echo for real-time features
import './echo-bootstrap.js';

// Simple DOM utilities
class SafeDOM {
    static safeRemove(element: Element | HTMLElement | null | undefined): boolean {
        if (!element) return false;
        try {
            if (element.remove) {
                element.remove();
            } else if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
            return true;
        } catch (error) {
            console.warn('SafeDOM removal failed:', error);
            return false;
        }
    }
}

// Store React root globally to prevent multiple roots
let globalRoot: any = null;

// Main application initialization
if (typeof window !== 'undefined') {
    // Initialize authentication
    const auth = getUnifiedAuth();
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
        initializeApp();
    }
    
    function initializeApp() {
        try {
            const appContainer = document.getElementById('dokter-app');
            if (!appContainer) {
                console.error('Dokter app container not found');
                return;
            }
            
            // Check if root already exists to prevent React Error #306
            if (globalRoot) {
                console.warn('React root already exists, reusing existing root');
                return;
            }
            
            // Check for performance comparison mode (dev only)
            const isComparisonMode = window.location.search.includes('compare=true') || 
                                    localStorage.getItem('dashboard-comparison') === 'true';

            // Create React root and render (only once)
            const root = createRoot(appContainer);
            globalRoot = root;
            
            // Start performance monitoring
            performanceMonitor.start('app-initialization');
            
            if (isComparisonMode) {
                // Import and use comparison component
                import('./components/dokter/DashboardComparison').then((module) => {
                    const DashboardComparison = module.default;
                    root.render(
                        <ErrorBoundary>
                            <DashboardComparison userData={undefined} enableComparison={true} />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('✅ DOKTERKU Mobile App initialized with comparison mode');
                });
            } else {
                // 🔄 RESTORED: Use dashboard with bottom navigation
                const useOriginal = window.location.search.includes('original=true') || 
                                  localStorage.getItem('dashboard-mode') === 'original';
                
                // 🚀 NEW: Check for optimized version (default to optimized for better performance)
                const useOptimized = window.location.search.includes('optimized=true') || 
                                    localStorage.getItem('dashboard-mode') === 'optimized' ||
                                    (!useOriginal && !window.location.search.includes('legacy=true')); // Default to optimized
                
                if (useOriginal) {
                    root.render(
                        <ErrorBoundary>
                            <OriginalDokterDashboard />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('✅ DOKTERKU Mobile App initialized with ORIGINAL Dashboard (Jaspel, Presensi, Profil)');
                } else if (useOptimized) {
                    // Use OPTIMIZED Dashboard with ORIGINAL UI (bottom navigation)
                    root.render(
                        <ErrorBoundary>
                            <OptimizedOriginalDashboard />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('🚀 DOKTERKU Mobile App initialized with OPTIMIZED Dashboard + Original UI (Bottom Navigation)');
                } else {
                    // Use legacy HolisticMedicalDashboard (fallback)
                    root.render(
                        <ErrorBoundary>
                            <HolisticMedicalDashboard userData={undefined} />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('✅ DOKTERKU Mobile App initialized with HolisticMedicalDashboard (Legacy Version)');
                }
            }
            
        } catch (error) {
            console.error('❌ Failed to initialize DOKTERKU Mobile App:', error);
            
            // Fallback to basic HTML if React fails
            const appContainer = document.getElementById('dokter-app');
            if (appContainer) {
                appContainer.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <h2>DOKTERKU Mobile App</h2>
                        <p>Initialization failed. Please refresh the page.</p>
                        <button onclick="location.reload()">Refresh Page</button>
                    </div>
                `;
            }
        }
    }
}