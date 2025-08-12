/**
 * Direct Injection Fix for Checkout Button
 * Run this directly in the browser console on the Presensi page
 */

(function() {
    console.log('🔧 APPLYING DIRECT CHECKOUT FIX...');
    
    // Method 1: Direct button manipulation
    function fixCheckoutButton() {
        // Find all buttons
        const buttons = Array.from(document.querySelectorAll('button'));
        
        // Find checkout button by multiple methods
        let checkoutButton = null;
        
        // Try method 1: Text content
        checkoutButton = buttons.find(b => 
            b.textContent.includes('Check Out') || 
            b.textContent.includes('Checkout')
        );
        
        // Try method 2: Icon class (Moon icon)
        if (!checkoutButton) {
            checkoutButton = buttons.find(b => 
                b.querySelector('[class*="moon" i]') || 
                b.querySelector('svg path[d*="M12 3"]') // Moon icon path
            );
        }
        
        // Try method 3: Click handler
        if (!checkoutButton) {
            checkoutButton = buttons.find(b => {
                const onclick = b.getAttribute('onclick') || '';
                const handlers = b._events || {};
                return onclick.includes('checkout') || 
                       onclick.includes('CheckOut') ||
                       Object.keys(handlers).some(k => k.includes('click'));
            });
        }
        
        // Try method 4: Parent container
        if (!checkoutButton) {
            const presensiContainer = document.querySelector('[class*="presensi" i]');
            if (presensiContainer) {
                checkoutButton = presensiContainer.querySelector('button:last-of-type');
            }
        }
        
        if (checkoutButton) {
            console.log('✅ Found checkout button:', checkoutButton);
            
            // Force enable the button
            checkoutButton.disabled = false;
            checkoutButton.removeAttribute('disabled');
            
            // Remove all disable-related classes
            checkoutButton.classList.remove('opacity-50', 'cursor-not-allowed', 'disabled');
            checkoutButton.classList.add('cursor-pointer');
            
            // Force styles
            checkoutButton.style.opacity = '1';
            checkoutButton.style.cursor = 'pointer';
            checkoutButton.style.pointerEvents = 'auto';
            
            // Make sure button is clickable
            if (!checkoutButton.onclick) {
                checkoutButton.onclick = function() {
                    console.log('🚀 Checkout clicked!');
                    // Try to find and call the original handler
                    handleCheckOut();
                };
            }
            
            console.log('✅ Checkout button has been enabled!');
            return true;
        } else {
            console.log('❌ Could not find checkout button');
            return false;
        }
    }
    
    // Method 2: Create global checkout function
    window.handleCheckOut = function() {
        console.log('📤 Processing checkout...');
        
        // Try to find the API endpoint and call it
        const token = localStorage.getItem('auth_token') || 
                     sessionStorage.getItem('auth_token') ||
                     document.querySelector('meta[name="api-token"]')?.content;
        
        if (!token) {
            console.error('❌ No auth token found');
            alert('Authentication token not found. Please refresh the page.');
            return;
        }
        
        // Call the checkout API
        fetch('/api/v2/dashboards/dokter/attendance/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                timestamp: new Date().toISOString()
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Checkout successful:', data);
            alert('Check-out berhasil!');
            // Reload to update the UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            console.error('❌ Checkout failed:', error);
            alert('Check-out gagal: ' + error.message);
        });
    };
    
    // Method 3: Override React state if possible
    function overrideReactState() {
        // Try to access React DevTools
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
            const hook = window.__REACT_DEVTOOLS_GLOBAL_HOOK__;
            const renderers = hook.renderers || new Map();
            
            for (const [id, renderer] of renderers) {
                try {
                    const fiber = renderer.getFiberRoots(id);
                    if (fiber && fiber.size > 0) {
                        console.log('🔍 Found React fiber roots');
                        // Try to modify state
                        for (const root of fiber) {
                            traverseFiber(root.current);
                        }
                    }
                } catch (e) {
                    console.log('Could not access React internals:', e);
                }
            }
        }
        
        // Also try window globals
        if (window.__dokterState) {
            window.__dokterState.scheduleData = window.__dokterState.scheduleData || {};
            window.__dokterState.scheduleData.canCheckOut = true;
            window.__dokterState.isCheckedIn = true;
            console.log('✅ Updated global state');
        }
    }
    
    function traverseFiber(fiber) {
        if (!fiber) return;
        
        // Check if this component has attendance-related state
        if (fiber.memoizedState) {
            if (fiber.memoizedState.scheduleData) {
                fiber.memoizedState.scheduleData.canCheckOut = true;
                console.log('✅ Modified scheduleData in React component');
            }
            if (fiber.memoizedState.isCheckedIn !== undefined) {
                fiber.memoizedState.isCheckedIn = true;
                console.log('✅ Set isCheckedIn to true');
            }
        }
        
        // Traverse children
        if (fiber.child) traverseFiber(fiber.child);
        if (fiber.sibling) traverseFiber(fiber.sibling);
    }
    
    // Execute all methods
    console.log('🚀 Starting fix process...');
    
    // Try to fix the button
    const buttonFixed = fixCheckoutButton();
    
    // Try to override React state
    overrideReactState();
    
    // Set up a watcher to keep the button enabled
    if (buttonFixed) {
        console.log('📍 Setting up button watcher...');
        setInterval(() => {
            const btn = document.querySelector('button:contains("Check Out")') ||
                       document.querySelector('button:has([class*="moon" i])') ||
                       Array.from(document.querySelectorAll('button')).find(b => 
                           b.textContent.includes('Check Out'));
            if (btn && btn.disabled) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                console.log('🔄 Re-enabled checkout button');
            }
        }, 1000);
    }
    
    // Add keyboard shortcut for checkout (Ctrl+Shift+O)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'O') {
            console.log('⌨️ Checkout shortcut triggered');
            handleCheckOut();
        }
    });
    
    console.log('✅ Fix process complete!');
    console.log('💡 TIP: You can also press Ctrl+Shift+O to checkout');
    
    // Return status
    return {
        buttonFixed: buttonFixed,
        checkoutFunction: 'handleCheckOut',
        shortcut: 'Ctrl+Shift+O'
    };
})();