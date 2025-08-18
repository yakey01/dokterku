import React from 'react';
import ReactDOM from 'react-dom/client';
import ManajerDashboard from './components/manajer/ManajerDashboard';

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('manajer-dashboard-root');
    
    if (container) {
        // Check if we have an auth token from Filament
        const authMeta = document.querySelector('meta[name="auth-token"]');
        if (authMeta) {
            const token = authMeta.getAttribute('content');
            if (token) {
                localStorage.setItem('auth_token', token);
            }
        }
        
        const root = ReactDOM.createRoot(container);
        root.render(
            <React.StrictMode>
                <ManajerDashboard />
            </React.StrictMode>
        );
        console.log('✅ Manajer Dashboard mounted successfully');
    } else {
        console.warn('⚠️ Manajer dashboard root element not found');
    }
});