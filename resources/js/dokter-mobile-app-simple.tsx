import React from 'react';
import { createRoot } from 'react-dom/client';
import HolisticMedicalDashboardSimple from './components/dokter/HolisticMedicalDashboardSimple';
import getUnifiedAuth from './utils/UnifiedAuth';
import '../css/app.css';
import '../css/responsive-typography.css';
import './setup-csrf';

// Simple initialization function
function initializeApp() {
    console.log('🚀 DOKTERKU: Starting simple app initialization');
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
        return;
    }
    
    // Check if container exists
    const container = document.getElementById('dokter-app');
    if (!container) {
        console.error('❌ Container element #dokter-app not found');
        
        // Wait a bit and try again
        setTimeout(() => {
            const retryContainer = document.getElementById('dokter-app');
            if (retryContainer) {
                console.log('✅ Container found on retry');
                mountApp(retryContainer);
            } else {
                console.error('❌ Container still not found after retry');
                showError('Container element not found. Please refresh the page.');
            }
        }, 1000);
        return;
    }
    
    console.log('✅ Container element found, mounting app');
    mountApp(container);
}

function mountApp(container: HTMLElement) {
    try {
        // Initialize authentication
        const auth = getUnifiedAuth();
        
        // Create React root
        const root = createRoot(container);
        
        // Render the app with simplified dashboard
        root.render(
            <React.StrictMode>
                <HolisticMedicalDashboardSimple />
            </React.StrictMode>
        );
        
        console.log('✅ DOKTERKU: Simple app mounted successfully');
        
        // Hide loading screen
        setTimeout(() => {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.style.display = 'none';
            }
        }, 1000);
        
    } catch (error) {
        console.error('❌ Failed to mount app:', error);
        showError('Failed to initialize application. Please refresh the page.');
    }
}

function showError(message: string) {
    const container = document.getElementById('dokter-app');
    if (container) {
        container.innerHTML = `
            <div style="padding: 20px; text-align: center; background: #fee2e2; color: #991b1b; margin: 20px; border-radius: 8px;">
                <h3>Application Error</h3>
                <p>${message}</p>
                <button onclick="window.location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Refresh Page
                </button>
            </div>
        `;
    }
    
    // Hide loading screen
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'none';
    }
}

// Start initialization
console.log('🚀 DOKTERKU: Simple app script loaded');
initializeApp();
