import React from 'react';
import ReactDOM from 'react-dom/client';
import WelcomeLogin from './components/WelcomeLogin';
import '../css/app.css'; // CRITICAL: Import CSS for bundling
import '../css/mobile-login.css'; // Mobile-specific optimizations

const WelcomeLoginApp = () => {

  // Canvas animation handles everything - no overlay needed

  const handleLogin = () => {
    console.log('🎯 Login success - Canvas animation will handle redirect');
    // Canvas animation handles everything including redirect
  };


  return (
    <div className="relative min-h-screen w-full overflow-x-hidden">
      <WelcomeLogin onLogin={handleLogin} />
    </div>
  );
};

// Mount the component
const container = document.getElementById('welcome-login-root');
if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(<WelcomeLoginApp />);
  
  console.log('🚀 WelcomeLoginApp mounted successfully');
} else {
  console.error('❌ Could not find welcome-login-root element');
}

// Canvas animation handles all visual effects