import React from 'react';
import { createRoot } from 'react-dom/client';
import PetugasDashboard from './components/petugas/PetugasWorkerDashboard';
import ErrorBoundary from './components/petugas/ErrorBoundary';

// Debug logging
console.log('🚀 React app starting...');

// Initialize React app
const container = document.getElementById('petugas-worker-root');
console.log('📦 Container found:', container);

if (container) {
    console.log('🔧 Creating React root...');
    const root = createRoot(container);
    
    console.log('🎨 Rendering components...');
    root.render(
        <ErrorBoundary>
            <PetugasDashboard />
        </ErrorBoundary>
    );
    console.log('✅ React render complete');
} else {
    console.error('❌ Root container #petugas-worker-root not found');
}