import React from 'react';
import ReactDOM from 'react-dom/client';
import WelcomeLogin from './components/WelcomeLogin';

const TestWelcomeLogin = () => {
  const handleLogin = () => {
    console.log('🎯 Login callback triggered');
    alert('Login success callback executed!');
  };

  return (
    <div>
      <WelcomeLogin onLogin={handleLogin} />
    </div>
  );
};

// Mount the component
const container = document.getElementById('test-welcome-login-root');
if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(<TestWelcomeLogin />);
}