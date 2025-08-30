/**
 * Enhanced validation tabs functionality
 * Provides real-time updates and better user experience
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize validation tabs enhancement
    initializeValidationTabs();
    
    // Setup real-time updates
    setupRealTimeUpdates();
    
    // Setup accessibility enhancements
    setupAccessibilityEnhancements();
});

/**
 * Initialize validation tabs with enhanced functionality
 */
function initializeValidationTabs() {
    const tabs = document.querySelectorAll('.validation-tab-pending, .validation-tab-validated, .validation-tab-all');
    
    tabs.forEach(tab => {
        // Add loading state support
        tab.addEventListener('click', function() {
            showTabLoading(this);
        });
        
        // Add keyboard navigation
        tab.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Add visual feedback for pending validation count
    updatePendingBadgeAnimation();
}

/**
 * Show loading state for tab
 */
function showTabLoading(tab) {
    const badge = tab.querySelector('.fi-badge');
    if (badge) {
        badge.classList.add('loading');
        
        // Remove loading state after a delay
        setTimeout(() => {
            badge.classList.remove('loading');
        }, 500);
    }
}

/**
 * Update pending badge animation based on count
 */
function updatePendingBadgeAnimation() {
    const pendingTab = document.querySelector('.validation-tab-pending');
    if (pendingTab) {
        const badge = pendingTab.querySelector('.fi-badge');
        const count = parseInt(badge?.textContent) || 0;
        
        if (count > 0) {
            badge?.classList.add('fi-color-warning');
            // Add urgent animation for high counts
            if (count > 10) {
                badge?.style.setProperty('animation-duration', '1s');
            }
        } else {
            badge?.classList.remove('fi-color-warning');
            badge?.classList.add('fi-color-gray');
        }
    }
}

/**
 * Setup real-time updates for tab counts
 */
function setupRealTimeUpdates() {
    // Update counts every 30 seconds
    setInterval(updateTabCounts, 30000);
    
    // Listen for validation events
    if (window.Echo) {
        window.Echo.channel('validation-updates')
            .listen('ValidationStatusChanged', (e) => {
                updateTabCounts();
                showNotificationToast(e.message);
            });
    }
    
    // Update counts when returning to tab/window
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateTabCounts();
        }
    });
}

/**
 * Update tab counts via AJAX
 */
async function updateTabCounts() {
    try {
        const response = await fetch('/bendahara/api/validation-counts', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        if (response.ok) {
            const counts = await response.json();
            updateTabBadges(counts);
            console.log('Tab counts updated successfully:', counts);
        } else {
            console.warn('Failed to fetch tab counts:', response.status);
        }
    } catch (error) {
        console.warn('Failed to update tab counts:', error);
    }
}

/**
 * Update tab badges with new counts
 */
function updateTabBadges(counts) {
    const tabs = {
        '.validation-tab-all': counts.total,
        '.validation-tab-pending': counts.pending,
        '.validation-tab-validated': counts.validated
    };
    
    Object.entries(tabs).forEach(([selector, count]) => {
        const tab = document.querySelector(selector);
        const badge = tab?.querySelector('.fi-badge');
        
        if (badge && badge.textContent !== count.toString()) {
            // Animate badge update
            badge.style.transform = 'scale(1.2)';
            badge.textContent = count;
            
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
            
            // Update badge colors based on count
            if (selector === '.validation-tab-pending') {
                if (count > 0) {
                    badge.classList.remove('fi-color-gray');
                    badge.classList.add('fi-color-warning');
                } else {
                    badge.classList.remove('fi-color-warning');
                    badge.classList.add('fi-color-gray');
                }
            } else if (selector === '.validation-tab-validated') {
                if (count > 0) {
                    badge.classList.remove('fi-color-gray');
                    badge.classList.add('fi-color-success');
                } else {
                    badge.classList.remove('fi-color-success');
                    badge.classList.add('fi-color-gray');
                }
            }
        }
    });
    
    updatePendingBadgeAnimation();
}

/**
 * Setup accessibility enhancements
 */
function setupAccessibilityEnhancements() {
    const tabs = document.querySelectorAll('[role="tab"]');
    
    tabs.forEach(tab => {
        // Ensure proper ARIA attributes
        if (!tab.getAttribute('aria-label')) {
            const label = tab.querySelector('.fi-tabs-tab-label')?.textContent;
            const badge = tab.querySelector('.fi-badge')?.textContent;
            
            if (label && badge) {
                tab.setAttribute('aria-label', `${label}, ${badge} items`);
            }
        }
        
        // Add keyboard navigation hints
        tab.setAttribute('title', tab.getAttribute('title') + ' (Gunakan Enter atau Space untuk mengaktifkan)');
    });
}

/**
 * Show notification toast for real-time updates
 */
function showNotificationToast(message) {
    // Check if Filament notification system is available
    if (window.Livewire && window.Livewire.emit) {
        window.Livewire.emit('notify', {
            type: 'info',
            title: 'Update Real-time',
            body: message,
            duration: 3000
        });
    } else {
        // Fallback to basic notification
        console.info('Validation update:', message);
    }
}

/**
 * Utility function to refresh current tab data
 */
function refreshCurrentTab() {
    const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
    if (activeTab) {
        showTabLoading(activeTab);
        
        // Trigger Livewire refresh if available
        if (window.Livewire) {
            window.Livewire.emit('refreshTable');
        } else {
            // Fallback to page reload
            window.location.reload();
        }
    }
}

// Expose functions for global use
window.validationTabs = {
    updateCounts: updateTabCounts,
    refresh: refreshCurrentTab,
    showLoading: showTabLoading
};