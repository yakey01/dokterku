import React from 'react';
import { createRoot } from 'react-dom/client';
import OriginalDokterDashboard from './components/dokter/OriginalDashboard';
import RefactoredDashboard from './components/dokter/RefactoredDashboard';
import HolisticMedicalDashboard from './components/dokter/HolisticMedicalDashboard';
import HolisticMedicalDashboardOptimized from './components/dokter/HolisticMedicalDashboardOptimized';
import OptimizedOriginalDashboard from './components/dokter/OptimizedOriginalDashboard';
import SimpleDashboard from './components/dokter/SimpleDashboard';
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
        console.log('🚀 initializeApp() called');
        try {
            const appContainer = document.getElementById('dokter-app');
            console.log('📦 App container found:', !!appContainer);
            if (!appContainer) {
                console.error('❌ Dokter app container not found');
                return;
            }
            
            // Check if root already exists to prevent React Error #306
            if (globalRoot) {
                console.warn('⚠️ React root already exists, reusing existing root');
                return;
            }
            
            console.log('✅ Proceeding with React root creation...');
            
            // Check for performance comparison mode (dev only)
            const isComparisonMode = window.location.search.includes('compare=true') || 
                                    localStorage.getItem('dashboard-comparison') === 'true';

            // Create React root and render (only once)
            console.log('⚛️ Creating React root...');
            const root = createRoot(appContainer);
            globalRoot = root;
            console.log('✅ React root created successfully');
            
            // Start performance monitoring
            performanceMonitor.start('app-initialization');
            console.log('📊 Performance monitoring started');
            
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
                
                // 🔧 TEMPORARY FIX: Default to original for stability 
                const useOptimized = window.location.search.includes('optimized=true') || 
                                    localStorage.getItem('dashboard-mode') === 'optimized';
                                    // Disabled auto-default to optimized to prevent blank page
                
                console.log('🎯 Dashboard mode selection:', { useOriginal, useOptimized, isComparisonMode });
                
                if (useOriginal) {
                    console.log('🔄 Rendering OriginalDokterDashboard...');
                    root.render(
                        <ErrorBoundary>
                            <OriginalDokterDashboard />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('✅ DOKTERKU Mobile App initialized with ORIGINAL Dashboard (Jaspel, Presensi, Profil)');
                } else if (useOptimized) {
                    console.log('🚀 Rendering OptimizedOriginalDashboard...');
                    root.render(
                        <ErrorBoundary>
                            <OptimizedOriginalDashboard />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('🚀 DOKTERKU Mobile App initialized with OPTIMIZED Dashboard + Original UI (Bottom Navigation)');
                } else {
                    // 🔧 SAFE FALLBACK: Use SimpleDashboard for guaranteed working state
                    console.log('🔧 Rendering SimpleDashboard (safe fallback)...');
                    root.render(
                        <ErrorBoundary>
                            <SimpleDashboard />
                        </ErrorBoundary>
                    );
                    performanceMonitor.end('app-initialization');
                    console.log('✅ DOKTERKU Mobile App initialized with SimpleDashboard (Safe Working Version)');
                }
            }
            
        } catch (error) {
            console.error('❌ Failed to initialize DOKTERKU Mobile App:', error);
            console.error('❌ Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            // Enhanced fallback to basic HTML if React fails
            const appContainer = document.getElementById('dokter-app');
            if (appContainer) {
                appContainer.innerHTML = `
                    <div style="padding: 20px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh;">
                        <h2>🏥 DOKTERKU Mobile App</h2>
                        <p>❌ React initialization failed</p>
                        <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin: 20px 0; text-align: left;">
                            <strong>Error:</strong> ${error.message}<br>
                            <strong>Component:</strong> ${error.name}
                        </div>
                        <button onclick="location.reload()" style="background: #059669; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            🔄 Refresh Page
                        </button>
                        <div style="margin-top: 20px;">
                            <a href="/dokter/mobile-app-simple" style="background: #3b82f6; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; display: inline-block;">
                                📱 Try Simple Version
                            </a>
                        </div>
                    </div>
                `;
            }
        }
    }
}