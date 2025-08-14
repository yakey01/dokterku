{{-- White Glass Morphism View for Petugas Pasien List --}}
<x-filament-panels::page>
    {{-- Force White Glass Morphism Styles --}}
    <style>
        /* 🤍 FORCE WHITE GLASSMORPHISM OVERRIDE */
        
        /* Clean white page background */
        body,
        body[class*="fi-panel-petugas"],
        [data-filament-panel-id="petugas"] {
            background: #f8f9fa !important;
            min-height: 100vh !important;
        }
        
        /* Main content area */
        .fi-main,
        .fi-main-ctn {
            background: transparent !important;
            padding: 1rem !important;
        }
        
        /* Force tabs white glass effect */
        [data-filament-panel-id="petugas"] .fi-tabs {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.06),
                0 1px 2px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
            padding: 0.75rem !important;
            margin-bottom: 1.5rem !important;
            position: relative !important;
            overflow: visible !important;
        }
        
        /* Glass reflection effect */
        [data-filament-panel-id="petugas"] .fi-tabs::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40%;
            background: linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0.4) 0%,
                transparent 100%
            );
            border-radius: 16px 16px 0 0;
            pointer-events: none;
            z-index: 1;
        }
        
        /* Tabs navigation */
        [data-filament-panel-id="petugas"] .fi-tabs-nav {
            display: flex !important;
            gap: 0.5rem !important;
            align-items: center !important;
            position: relative !important;
            z-index: 2 !important;
        }
        
        /* Individual tab white glass style */
        [data-filament-panel-id="petugas"] .fi-tabs-item,
        [data-filament-panel-id="petugas"] .fi-tabs-tab {
            background: rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            border-radius: 12px !important;
            padding: 0.75rem 1.5rem !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #6b7280 !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            overflow: hidden !important;
            min-height: 2.75rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
        }
        
        /* Hover effect */
        [data-filament-panel-id="petugas"] .fi-tabs-item:hover,
        [data-filament-panel-id="petugas"] .fi-tabs-tab:hover {
            background: rgba(255, 255, 255, 0.9) !important;
            border-color: #ffffff !important;
            color: #374151 !important;
            transform: translateY(-2px) !important;
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.08),
                0 2px 6px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 1) !important;
        }
        
        /* Active tab */
        [data-filament-panel-id="petugas"] .fi-tabs-item[aria-selected="true"],
        [data-filament-panel-id="petugas"] .fi-tabs-tab.active,
        [data-filament-panel-id="petugas"] .fi-tabs-item.fi-active {
            background: #ffffff !important;
            border-color: #ffffff !important;
            color: #3b82f6 !important;
            font-weight: 600 !important;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.12),
                0 2px 6px rgba(0, 0, 0, 0.06),
                inset 0 -3px 0 #3b82f6 !important;
            transform: translateY(-1px) !important;
        }
        
        /* Subtle shimmer effect */
        [data-filament-panel-id="petugas"] .fi-tabs-item::after,
        [data-filament-panel-id="petugas"] .fi-tabs-tab::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            transition: left 0.6s ease;
            pointer-events: none;
        }
        
        [data-filament-panel-id="petugas"] .fi-tabs-item:hover::after,
        [data-filament-panel-id="petugas"] .fi-tabs-tab:hover::after {
            left: 100%;
        }
        
        /* Icons styling */
        [data-filament-panel-id="petugas"] .fi-tabs-item svg,
        [data-filament-panel-id="petugas"] .fi-tabs-tab svg {
            width: 1.125rem !important;
            height: 1.125rem !important;
            margin-right: 0.5rem !important;
            color: currentColor !important;
            opacity: 0.8 !important;
            transition: all 0.2s ease !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-tabs-item:hover svg,
        [data-filament-panel-id="petugas"] .fi-tabs-tab:hover svg {
            opacity: 1 !important;
            transform: scale(1.1) !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-tabs-item[aria-selected="true"] svg,
        [data-filament-panel-id="petugas"] .fi-tabs-tab.active svg {
            color: #3b82f6 !important;
            opacity: 1 !important;
        }
        
        /* Badges */
        [data-filament-panel-id="petugas"] .fi-badge {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 999px !important;
            padding: 0.125rem 0.5rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
            margin-left: 0.5rem !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        }
        
        /* Warning badge */
        [data-filament-panel-id="petugas"] .fi-badge.fi-color-warning {
            background: #fef3c7 !important;
            color: #92400e !important;
            border-color: #fde68a !important;
        }
        
        /* Success badge */
        [data-filament-panel-id="petugas"] .fi-badge.fi-color-success {
            background: #d1fae5 !important;
            color: #065f46 !important;
            border-color: #a7f3d0 !important;
        }
        
        /* Danger badge */
        [data-filament-panel-id="petugas"] .fi-badge.fi-color-danger {
            background: #fee2e2 !important;
            color: #991b1b !important;
            border-color: #fecaca !important;
        }
        
        /* Table container glass effect */
        [data-filament-panel-id="petugas"] .fi-ta {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.06),
                0 1px 2px rgba(0, 0, 0, 0.04) !important;
            overflow: hidden !important;
        }
        
        /* Page header glass effect */
        [data-filament-panel-id="petugas"] .fi-header {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.06),
                0 1px 2px rgba(0, 0, 0, 0.04) !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-header-heading {
            color: #111827 !important;
            font-weight: 600 !important;
        }
        
        /* Buttons glass effect */
        [data-filament-panel-id="petugas"] .fi-btn {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-btn:hover {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* Primary button */
        [data-filament-panel-id="petugas"] .fi-btn-color-primary {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-btn-color-primary:hover {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            [data-filament-panel-id="petugas"] .fi-tabs {
                padding: 0.5rem !important;
                border-radius: 12px !important;
            }
            
            [data-filament-panel-id="petugas"] .fi-tabs-item,
            [data-filament-panel-id="petugas"] .fi-tabs-tab {
                padding: 0.5rem 1rem !important;
                font-size: 0.8125rem !important;
                min-height: 2.5rem !important;
            }
        }
    </style>
    
    {{-- JavaScript for Enhanced Glass Effects --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🤍 White Glass Morphism Tabs Active!');
            
            // Add glass badge
            const badge = document.createElement('div');
            badge.innerHTML = '🤍 White Glass UI Active';
            badge.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(255, 255, 255, 0.9);
                border-radius: 8px;
                padding: 8px 16px;
                color: #374151;
                font-weight: 600;
                font-size: 14px;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(badge);
            
            setTimeout(() => {
                badge.style.opacity = '0';
                badge.style.transition = 'opacity 0.3s ease';
                setTimeout(() => badge.remove(), 300);
            }, 3000);
            
            // Add slide in animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</x-filament-panels::page>