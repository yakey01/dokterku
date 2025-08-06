/**
 * Global CSRF Setup
 * Initialize CSRF protection for all AJAX requests
 */

import { csrfHelper } from './utils/csrf-helper';

// Setup CSRF protection on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', setupCSRF);
} else {
  setupCSRF();
}

function setupCSRF() {
  // Setup axios defaults if available
  csrfHelper.setupAxiosDefaults();
  
  // Intercept all fetch requests to add CSRF token
  csrfHelper.interceptFetch();
  
  // Add CSRF token to all forms on submit
  document.addEventListener('submit', (e) => {
    const form = e.target as HTMLFormElement;
    if (form.method.toUpperCase() !== 'GET') {
      csrfHelper.addTokenToForm(form);
    }
  });
  
  console.log('✅ CSRF protection initialized');
}