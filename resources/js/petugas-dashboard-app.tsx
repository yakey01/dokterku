import React from 'react';
import ReactDOM from 'react-dom/client';
import PetugasDashboardCharts from './components/petugas/PetugasDashboardCharts';

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

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePetugasDashboard);
} else {
    initializePetugasDashboard();
}

// Also listen for Livewire navigation events
document.addEventListener('livewire:navigated', initializePetugasDashboard);
document.addEventListener('livewire:load', initializePetugasDashboard);

// Export for global access if needed
(window as any).initializePetugasDashboard = initializePetugasDashboard;