import React from 'react';
import { createRoot } from 'react-dom/client';
import CreativeAttendanceDashboardEmergency from './components/dokter/PresensiEmergency';
import getUnifiedAuth from './utils/UnifiedAuth';
import '../css/app.css';
import '../css/responsive-typography.css';
import './setup-csrf';

// 🚨 EMERGENCY MODE - CREATIVE CACHE BUSTING SOLUTION
console.log('🚨 EMERGENCY MODE ACTIVATED - CREATIVE SOLUTION');
console.log('🕐 Timestamp:', Date.now());
console.log('🆔 Emergency ID:', Math.random().toString(36).substr(2, 9));

// Force clear all caches immediately
if ('caches' in window) {
    caches.keys().then(names => {
        console.log('🗑️ Emergency cache clear:', names);
        return Promise.all(names.map(name => caches.delete(name)));
    }).then(() => console.log('✅ All caches cleared'));
}

// Clear all storage
try {
    localStorage.clear();
    sessionStorage.clear();
    console.log('✅ All storage cleared');
} catch (e) {
    console.warn('⚠️ Storage clear failed:', e);
}

// Emergency bootstrap
class EmergencyBootstrap {
    private static instance: EmergencyBootstrap;
    private initialized = false;
    
    public static getInstance(): EmergencyBootstrap {
        if (!EmergencyBootstrap.instance) {
            EmergencyBootstrap.instance = new EmergencyBootstrap();
        }
        return EmergencyBootstrap.instance;
    }
    
    public async initialize(): Promise<void> {
        if (this.initialized) {
            console.log('🚨 Emergency bootstrap already initialized');
            return;
        }
        
        console.log('🚨 Initializing emergency bootstrap...');
        
        try {
            // Wait for DOM to be ready
            await this.waitForDOM();
            
            // Initialize authentication
            await this.initializeAuth();
            
            // Mount the emergency component
            await this.mountEmergencyComponent();
            
            this.initialized = true;
            console.log('✅ Emergency bootstrap completed successfully');
            
        } catch (error) {
            console.error('❌ Emergency bootstrap failed:', error);
            this.showEmergencyFallback();
        }
    }
    
    private async waitForDOM(): Promise<void> {
        return new Promise((resolve) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', resolve);
            } else {
                resolve();
            }
        });
    }
    
    private async initializeAuth(): Promise<void> {
        console.log('🔐 Initializing emergency authentication...');
        
        try {
            const auth = await getUnifiedAuth();
            console.log('✅ Emergency authentication initialized');
        } catch (error) {
            console.warn('⚠️ Emergency authentication failed:', error);
        }
    }
    
    private async mountEmergencyComponent(): Promise<void> {
        console.log('🚨 Mounting emergency component...');
        
        const container = document.getElementById('dokter-app');
        if (!container) {
            throw new Error('Container #dokter-app not found');
        }
        
        // Clear container
        container.innerHTML = '';
        
        // Create root and mount component
        const root = createRoot(container);
        root.render(
            <React.StrictMode>
                <CreativeAttendanceDashboardEmergency />
            </React.StrictMode>
        );
        
        console.log('✅ Emergency component mounted successfully');
    }
    
    private showEmergencyFallback(): void {
        console.log('🚨 Showing emergency fallback...');
        
        const container = document.getElementById('dokter-app');
        if (container) {
            container.innerHTML = `
                <div style="padding: 20px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; margin: 20px;">
                    <h2 style="color: #856404;">🚨 Emergency Mode Active</h2>
                    <p style="color: #856404;">The application is running in emergency mode due to cache issues.</p>
                    <p style="color: #856404;">If you see this message, the emergency component failed to load.</p>
                    <br>
                    <button onclick="window.location.reload()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
                        🔄 Retry Loading
                    </button>
                    <button onclick="window.location.href='/dokter/mobile-app'" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
                        🏠 Go to Main App
                    </button>
                    <button onclick="window.location.href='/test-cache-bust.php'" style="padding: 10px 20px; background: #ffc107; color: black; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
                        🧪 Test Cache Bust
                    </button>
                </div>
            `;
        }
    }
}

// Initialize emergency bootstrap
const emergencyBootstrap = EmergencyBootstrap.getInstance();
emergencyBootstrap.initialize().catch(error => {
    console.error('❌ Emergency bootstrap initialization failed:', error);
});

// Export for potential external use
(window as any).EmergencyBootstrap = EmergencyBootstrap;
