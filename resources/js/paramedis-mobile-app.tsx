import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/paramedis/App';
import './components/paramedis/styles.css';

// 🚀 Real-time WebSocket integration for paramedis
import './echo-bootstrap.js';

// Global error handler for unhandled promise rejections
window.addEventListener('unhandledrejection', (event) => {
    // Suppress common Maps/React errors that don't affect functionality
    if (event.reason?.message?.includes('IntersectionObserver') ||
        event.reason?.message?.includes('target') ||
        event.reason?.message?.includes('Element') ||
        event.reason?.message?.includes('observe')) {
        console.warn('🛡️ Suppressed non-critical error:', event.reason?.message);
        event.preventDefault();
        return;
    }
    
    // Log other errors but prevent them from crashing the app
    console.warn('🚨 Unhandled Promise Rejection:', event.reason);
    event.preventDefault();
});

// Global error handler for JavaScript errors
window.addEventListener('error', (event) => {
    if (event.error?.message?.includes('IntersectionObserver') ||
        event.error?.message?.includes('target') ||
        event.error?.message?.includes('Element')) {
        console.warn('🛡️ Suppressed non-critical JS error:', event.error?.message);
        return;
    }
    
    console.warn('🚨 JavaScript Error:', event.error);
});

// Simple and safe app initialization
function initializeApp() {
    console.log('🚀 Paramedis Mobile App: Starting initialization...');
    
    try {
        const container = document.getElementById('paramedis-app');
        
        if (!container) {
            console.error('Container element #paramedis-app not found!');
            return;
        }
        
        // Hide loading spinner
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        // Create React root
        const root = createRoot(container);
        root.render(<App />);
        console.log('✅ React app rendered successfully!');
        
    } catch (e) {
        console.error('❌ App initialization failed:', e);
        // Show fallback content
        const container = document.getElementById('paramedis-app');
        if (container) {
            container.innerHTML = '<div style="padding: 20px; text-align: center; background: #f0f0f0; color: #333; border-radius: 8px; margin: 20px;">⚠️ App loading failed. Please refresh and try again.</div>';
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp, { once: true });
} else {
    initializeApp();
}