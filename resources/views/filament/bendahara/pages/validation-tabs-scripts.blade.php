{{-- Enhanced Validation Tabs Scripts for Bendahara Panel --}}

@push('scripts')
{{-- Load validation tabs JavaScript --}}
@vite('resources/js/validation-tabs.js')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize validation tabs with Livewire integration
    if (window.validationTabs) {
        console.log('Validation tabs enhancement loaded');
        
        // Listen for Livewire events
        if (window.Livewire) {
            // Refresh counts when validation actions occur
            window.Livewire.on('refreshTabCounts', () => {
                if (window.validationTabs.updateCounts) {
                    window.validationTabs.updateCounts();
                }
            });
            
            // Update counts after table actions
            window.Livewire.hook('message.processed', (message, component) => {
                // Check if this is a validation-related action
                if (message.updateQueue.some(update => 
                    update.payload && 
                    (update.payload.method === 'approve' || 
                     update.payload.method === 'reject' ||
                     update.payload.method === 'bulkApprove' ||
                     update.payload.method === 'bulkReject')
                )) {
                    setTimeout(() => {
                        if (window.validationTabs.updateCounts) {
                            window.validationTabs.updateCounts();
                        }
                    }, 1000); // Delay to ensure database updates are complete
                }
            });
        }
    }
    
    // Add custom styles for better integration
    const style = document.createElement('style');
    style.textContent = `
        .fi-resource-tabs {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 1rem;
        }
        
        .validation-tab-pending .fi-badge.fi-color-warning {
            animation: validationPulse 2s infinite;
        }
        
        @keyframes validationPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        
        .fi-tabs .fi-tabs-tab {
            transition: all 0.2s ease-in-out;
        }
        
        .fi-tabs .fi-tabs-tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .fi-tabs .fi-tabs-tab[aria-selected="true"] {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 8px 8px 0 0;
        }
        
        @media (max-width: 768px) {
            .fi-tabs {
                overflow-x: auto;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            
            .fi-tabs::-webkit-scrollbar {
                display: none;
            }
        }
    `;
    document.head.appendChild(style);
});

// Add global error handling for AJAX requests
window.addEventListener('unhandledrejection', function(event) {
    if (event.reason && event.reason.message && event.reason.message.includes('validation-counts')) {
        console.warn('Validation counts update failed, will retry on next interval');
        event.preventDefault(); // Prevent console error spam
    }
});
</script>
@endpush

@push('styles')
{{-- Load validation tabs CSS --}}
@vite('resources/css/filament/bendahara/validation-tabs.css')

<style>
/* Additional responsive enhancements */
@media (max-width: 640px) {
    .fi-resource-tabs .fi-tabs-content {
        gap: 0.5rem;
    }
    
    .fi-tabs .fi-tabs-tab {
        min-width: auto;
        flex: 1;
        text-align: center;
    }
    
    .fi-tabs .fi-tabs-tab .fi-tabs-tab-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

/* Enhanced focus indicators for accessibility */
.fi-tabs .fi-tabs-tab:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    border-radius: 4px;
}

/* Loading state for better UX */
.fi-badge.loading {
    position: relative;
    overflow: hidden;
}

.fi-badge.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    100% {
        left: 100%;
    }
}
</style>
@endpush