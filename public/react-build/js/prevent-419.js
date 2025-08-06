// Prevent 419 Page Expired Errors
(function() {
    'use strict';
    
    // Auto-refresh CSRF token every 30 minutes
    setInterval(function() {
        fetch('/refresh-csrf', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update all CSRF tokens on the page
            document.querySelectorAll('input[name="_token"]').forEach(function(input) {
                input.value = data.token;
            });
            
            // Update meta tag
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                metaToken.setAttribute('content', data.token);
            }
            
            console.log('CSRF token refreshed');
        })
        .catch(error => console.error('Error refreshing CSRF token:', error));
    }, 30 * 60 * 1000); // 30 minutes
    
    // Handle form submission errors
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const tokenInput = form.querySelector('input[name="_token"]');
                
                if (!tokenInput || !tokenInput.value) {
                    e.preventDefault();
                    alert('Security token missing. Page will refresh.');
                    window.location.reload();
                    return false;
                }
                
                // Store form data in session storage in case of 419
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (key !== '_token' && key !== 'password') {
                        data[key] = value;
                    }
                }
                sessionStorage.setItem('form_data', JSON.stringify(data));
            });
        });
        
        // Restore form data if available
        const savedData = sessionStorage.getItem('form_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(function(key) {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                sessionStorage.removeItem('form_data');
            } catch (e) {
                console.error('Error restoring form data:', e);
            }
        }
    });
    
    // Detect 419 errors and handle gracefully
    window.addEventListener('load', function() {
        if (document.body.textContent.includes('419') && 
            document.body.textContent.includes('Page Expired')) {
            sessionStorage.setItem('encountered_419', 'true');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        }
    });
})();