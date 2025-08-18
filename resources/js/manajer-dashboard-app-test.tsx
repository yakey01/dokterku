import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

// Minimal test component to diagnose useState issue
const TestManagerDashboard: React.FC = () => {
  const [testState, setTestState] = useState('Initial State');
  
  useEffect(() => {
    console.log('✅ React hooks are working properly');
    setTestState('Updated State');
  }, []);

  return (
    <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900 p-6">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-3xl font-bold text-neutral-900 dark:text-white mb-6">
          🧪 React Hooks Test Dashboard
        </h1>
        
        <div className="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6">
          <h2 className="text-xl font-semibold text-neutral-800 dark:text-neutral-200 mb-4">
            useState Test
          </h2>
          <p className="text-neutral-600 dark:text-neutral-400">
            Current state: <strong>{testState}</strong>
          </p>
          <button 
            onClick={() => setTestState(`Updated at ${new Date().toLocaleTimeString()}`)}
            className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
          >
            Update State
          </button>
        </div>
      </div>
    </div>
  );
};

// Initialize test dashboard
const initializeTestDashboard = () => {
  console.log('🧪 Initializing React hooks test...');
  
  const container = document.getElementById('manajer-dashboard-root');
  
  if (container) {
    container.classList.remove('hidden');
    container.style.display = 'block';
    container.style.minHeight = '100vh';
    
    try {
      const root = createRoot(container);
      root.render(<TestManagerDashboard />);
      console.log('✅ Test dashboard mounted successfully');
    } catch (error) {
      console.error('❌ Test mounting error:', error);
    }
  }
};

// Initialize
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeTestDashboard);
} else {
  initializeTestDashboard();
}

export default TestManagerDashboard;