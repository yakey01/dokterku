import React from 'react';
import ReactDOM from 'react-dom/client';
import PetugasDashboardCharts from './components/petugas/PetugasDashboardCharts';

// 🚀 Real-time WebSocket integration for petugas
import './echo-bootstrap.js';

// Real-time state management
let realtimeConnected = false;
let realtimeNotifications: any[] = [];
let lastUpdateTime = 'Never';

// Define the data interface
interface DashboardData {
    patientCategories?: Array<{
        name: string;
        value: number;
        color: string;
    }>;
    procedureTypes?: Array<{
        name: string;
        value: number;
        color: string;
    }>;
}

// 🚀 Real-time setup for petugas dashboard
function setupPetugasRealtimeConnection() {
    try {
        if (typeof window !== 'undefined' && window.Echo) {
            console.log('🔌 Setting up real-time connection for petugas...');
            
            // Get user ID from meta tag
            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') ||
                          localStorage.getItem('user_id');
            
            if (!userId) {
                console.warn('⚠️ User ID not found for petugas real-time connection');
                return;
            }
            
            // Listen to petugas private channel
            window.Echo.private(`petugas.${userId}`)
              .listen('tindakan.validated', (event: any) => {
                console.log('🎯 Petugas received validation feedback:', event);
                showPetugasNotification(event.notification);
                updatePetugasDashboard();
              });
            
            // Listen to validation updates channel
            window.Echo.channel('validation.updates')
              .listen('tindakan.validated', (event: any) => {
                console.log('📊 Validation update received by petugas:', event);
                
                // Show notification if this petugas input the tindakan
                if (event.tindakan.input_by_id == userId) {
                    showPetugasNotification({
                        title: event.validation.status === 'disetujui' ? '✅ Tindakan Disetujui' : '❌ Tindakan Ditolak',
                        message: `${event.tindakan.jenis_tindakan} yang Anda input telah ${event.validation.status === 'disetujui' ? 'disetujui' : 'ditolak'} bendahara`,
                        type: event.validation.status === 'disetujui' ? 'success' : 'error'
                    });
                }
              });
            
            // Connection status
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('✅ Petugas WebSocket connected');
                realtimeConnected = true;
                updateConnectionStatus();
            });
            
            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('❌ Petugas WebSocket disconnected');
                realtimeConnected = false;
                updateConnectionStatus();
            });
            
        } else {
            console.log('⚠️ Echo not available for petugas, using polling fallback...');
            realtimeConnected = false;
        }
    } catch (error) {
        console.error('❌ Failed to setup petugas WebSocket:', error);
        realtimeConnected = false;
    }
}

// Show real-time notifications for petugas
function showPetugasNotification(notification: any) {
    console.log('📢 Showing petugas notification:', notification);
    
    // Create notification element
    const notificationContainer = document.getElementById('petugas-notifications') || createNotificationContainer();
    
    const notificationElement = document.createElement('div');
    notificationElement.className = `p-3 mb-2 rounded-lg border transition-all duration-500 ${
        notification.type === 'success' 
            ? 'bg-green-500/10 border-green-500/30 text-green-300' 
            : notification.type === 'error'
            ? 'bg-red-500/10 border-red-500/30 text-red-300'
            : 'bg-blue-500/10 border-blue-500/30 text-blue-300'
    }`;
    
    notificationElement.innerHTML = `
        <div class="font-semibold text-sm">${notification.title}</div>
        <div class="text-xs opacity-90">${notification.message}</div>
        <div class="text-xs opacity-70 mt-1">${new Date().toLocaleTimeString()}</div>
    `;
    
    notificationContainer.appendChild(notificationElement);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (notificationElement.parentNode) {
            notificationElement.remove();
        }
    }, 10000);
}

// Create notification container if it doesn't exist
function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'petugas-notifications';
    container.className = 'fixed top-4 right-4 z-50 w-80 max-h-96 overflow-y-auto';
    document.body.appendChild(container);
    return container;
}

// Update connection status indicator
function updateConnectionStatus() {
    const statusElement = document.getElementById('petugas-realtime-status');
    if (statusElement) {
        statusElement.innerHTML = realtimeConnected 
            ? '<span class="text-green-400">🟢 Live</span>' 
            : '<span class="text-yellow-400">🟡 Polling</span>';
    }
}

// Update dashboard data (refresh charts/stats)
function updatePetugasDashboard() {
    console.log('🔄 Refreshing petugas dashboard data...');
    lastUpdateTime = new Date().toLocaleTimeString();
    
    // Trigger any dashboard refresh logic here
    // This could reload charts, refresh statistics, etc.
    window.dispatchEvent(new CustomEvent('petugas-dashboard-refresh'));
}

// Function to initialize the dashboard
function initializePetugasDashboard() {
    const chartContainer = document.getElementById('petugas-dashboard-charts');
    
    if (!chartContainer) {
        console.warn('Petugas dashboard chart container not found');
        return;
    }

    // Get data from data attributes or use defaults
    const dataAttribute = chartContainer.getAttribute('data-charts');
    let dashboardData: DashboardData = {};
    
    if (dataAttribute) {
        try {
            dashboardData = JSON.parse(dataAttribute);
        } catch (error) {
            console.error('Failed to parse dashboard data:', error);
        }
    }

    // Default data if not provided
    const defaultPatientData = [
        { name: 'Umum', value: 45, color: '#3B82F6' },
        { name: 'BPJS', value: 35, color: '#10B981' },
        { name: 'Asuransi', value: 20, color: '#F59E0B' }
    ];

    const defaultProcedureData = [
        { name: 'Pemeriksaan', value: 35, color: '#8B5CF6' },
        { name: 'Konsultasi', value: 25, color: '#EC4899' },
        { name: 'Tindakan', value: 20, color: '#06B6D4' },
        { name: 'Laboratorium', value: 15, color: '#14B8A6' },
        { name: 'Radiologi', value: 5, color: '#F97316' }
    ];

    const root = ReactDOM.createRoot(chartContainer);
    root.render(
        <React.StrictMode>
            <PetugasDashboardCharts 
                patientData={dashboardData.patientCategories || defaultPatientData}
                procedureData={dashboardData.procedureTypes || defaultProcedureData}
            />
        </React.StrictMode>
    );
}

// Initialize on DOM ready with real-time setup
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializePetugasDashboard();
        setupPetugasRealtimeConnection(); // 🚀 Setup real-time connection
    });
} else {
    initializePetugasDashboard();
    setupPetugasRealtimeConnection(); // 🚀 Setup real-time connection
}

// Also listen for Livewire navigation events
document.addEventListener('livewire:navigated', () => {
    initializePetugasDashboard();
    setupPetugasRealtimeConnection(); // Re-setup on navigation
});
document.addEventListener('livewire:load', () => {
    initializePetugasDashboard();
    setupPetugasRealtimeConnection(); // Re-setup on load
});

// Export for global access if needed
(window as any).initializePetugasDashboard = initializePetugasDashboard;
(window as any).setupPetugasRealtimeConnection = setupPetugasRealtimeConnection;