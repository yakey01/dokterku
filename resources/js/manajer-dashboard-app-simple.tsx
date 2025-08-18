import React from 'react';
import { createRoot } from 'react-dom/client';

// ============================================
// SIMPLE MANAJER DASHBOARD - MINIMAL TEST
// ============================================

const SimpleManagerDashboard: React.FC = () => {
  return (
    <div className="min-h-screen bg-white dark:bg-neutral-900 p-6">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-3xl font-bold text-neutral-900 dark:text-white mb-6">
          🏢 Manager Dashboard - Simple Version
        </h1>
        
        <div className="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-xl font-semibold text-neutral-800 dark:text-neutral-200 mb-4">
            📊 Dashboard Status
          </h2>
          <p className="text-neutral-600 dark:text-neutral-400">
            ✅ React app loaded successfully!
          </p>
          <p className="text-neutral-600 dark:text-neutral-400 mt-2">
            🚀 Manager dashboard is working properly.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 className="font-semibold text-blue-800 dark:text-blue-200">Revenue</h3>
            <p className="text-2xl font-bold text-blue-600">Rp 5,000,000</p>
          </div>
          
          <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <h3 className="font-semibold text-green-800 dark:text-green-200">Patients</h3>
            <p className="text-2xl font-bold text-green-600">125</p>
          </div>
          
          <div className="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <h3 className="font-semibold text-purple-800 dark:text-purple-200">Doctors</h3>
            <p className="text-2xl font-bold text-purple-600">8</p>
          </div>
        </div>
      </div>
    </div>
  );
};

// ============================================
// APP INITIALIZATION - ROBUST MOUNTING
// ============================================

const initializeDashboard = () => {
  console.log('🔍 Initializing dashboard...');
  
  const container = document.getElementById('manajer-dashboard-root');
  const loading = document.getElementById('dashboard-loading');
  
  console.log('📍 Container found:', !!container);
  console.log('📍 Loading element found:', !!loading);
  
  if (container) {
    // Show dashboard container immediately
    container.classList.remove('hidden');
    container.style.display = 'block';
    container.style.minHeight = '100vh';
    
    // Hide loading state
    if (loading) {
      loading.style.display = 'none';
    }
    
    // Mount React app
    try {
      const root = createRoot(container);
      root.render(<SimpleManagerDashboard />);
      console.log('✅ Simple Manager Dashboard mounted successfully');
    } catch (error) {
      console.error('❌ React mounting error:', error);
      container.innerHTML = `
        <div class="flex items-center justify-center min-h-screen bg-red-50">
          <div class="text-center p-8">
            <h1 class="text-2xl font-bold text-red-600 mb-4">React Mounting Error</h1>
            <p class="text-red-500">${error.message}</p>
          </div>
        </div>
      `;
    }
  } else {
    console.error('❌ Container #manajer-dashboard-root not found');
    console.log('📝 Available elements:', document.querySelectorAll('[id*="dashboard"], [id*="manajer"]'));
  }
};

// Multiple initialization strategies
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeDashboard);
} else {
  // DOM already loaded
  initializeDashboard();
}

// Fallback after 1 second
setTimeout(() => {
  const container = document.getElementById('manajer-dashboard-root');
  if (container && !container.children.length) {
    console.log('🔄 Fallback initialization...');
    initializeDashboard();
  }
}, 1000);

export default SimpleManagerDashboard;