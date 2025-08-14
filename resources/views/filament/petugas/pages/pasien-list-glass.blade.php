{{-- Enhanced Glassmorphism View for Petugas Pasien List --}}
<x-filament-panels::page>
    {{-- Glassmorphism Tab Enhancement --}}
    <style>
        /* 🌟 GLASSMORPHISM TABS ENHANCEMENT OVERRIDE */
        /* Force glassmorphism styles to take priority */
        
        /* Enhanced Background */
        body[class*="fi-panel-petugas"] {
            background: linear-gradient(135deg, 
                #667eea 0%, 
                #764ba2 25%,
                #f093fb 50%, 
                #f5576c 75%,
                #4facfe 100%) !important;
            background-size: 400% 400% !important;
            animation: gradientShift 15s ease infinite !important;
            min-height: 100vh !important;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            25% { background-position: 100% 50%; }
            50% { background-position: 100% 100%; }
            75% { background-position: 0% 100%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glass Container for the entire page */
        .fi-main {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37) !important;
            margin: 1rem !important;
            padding: 1.5rem !important;
        }
        
        /* Force tabs to show glassmorphism */
        [data-filament-panel-id="petugas"] .fi-tabs {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border-radius: 16px !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: 
                0 8px 32px rgba(31, 38, 135, 0.37),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            padding: 0.75rem !important;
            margin-bottom: 2rem !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        /* Force individual tabs glassmorphism */
        [data-filament-panel-id="petugas"] .fi-tabs-nav {
            display: flex !important;
            gap: 0.75rem !important;
            align-items: center !important;
            flex-wrap: wrap !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-tabs-item {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            padding: 0.75rem 1.5rem !important;
            font-weight: 500 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            min-height: 3rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 
                0 4px 16px rgba(31, 38, 135, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        /* Tab hover effects */
        [data-filament-panel-id="petugas"] .fi-tabs-item:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            transform: translateY(-2px) scale(1.02) !important;
            box-shadow: 
                0 8px 32px rgba(31, 38, 135, 0.4),
                0 0 20px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }
        
        /* Active tab */
        [data-filament-panel-id="petugas"] .fi-tabs-item[aria-selected="true"],
        [data-filament-panel-id="petugas"] .fi-tabs-item.active {
            background: rgba(255, 255, 255, 0.25) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
            color: white !important;
            font-weight: 600 !important;
            box-shadow: 
                0 12px 40px rgba(31, 38, 135, 0.5),
                0 0 30px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.4),
                inset 0 -1px 0 rgba(0, 0, 0, 0.1) !important;
            transform: translateY(-1px) !important;
        }
        
        /* Badge styling */
        [data-filament-panel-id="petugas"] .fi-badge {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 1rem !important;
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: white !important;
            margin-left: 0.5rem !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Icons */
        [data-filament-panel-id="petugas"] .fi-tabs-item svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            margin-right: 0.5rem !important;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1)) !important;
        }
        
        /* Shimmer effect */
        [data-filament-panel-id="petugas"] .fi-tabs-item::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            ) !important;
            transition: left 0.5s !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-tabs-item:hover::before {
            left: 100% !important;
        }
        
        /* Ensure text content is above shimmer */
        [data-filament-panel-id="petugas"] .fi-tabs-item > * {
            position: relative !important;
            z-index: 2 !important;
        }
        
        /* Table container glass effect */
        [data-filament-panel-id="petugas"] .fi-ta {
            background: rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border-radius: 16px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37) !important;
            overflow: hidden !important;
        }
        
        /* Page header glass effect */
        [data-filament-panel-id="petugas"] .fi-header {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border-radius: 16px !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37) !important;
            margin-bottom: 1.5rem !important;
            color: white !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-header-heading {
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        }
        
        /* Additional glass effects for various elements */
        [data-filament-panel-id="petugas"] .fi-btn {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            box-shadow: 0 4px 16px rgba(31, 38, 135, 0.2) !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-btn:hover {
            background: rgba(255, 255, 255, 0.25) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.4) !important;
        }
        
        /* Floating effect for tabs container */
        [data-filament-panel-id="petugas"] .fi-tabs::after {
            content: '' !important;
            position: absolute !important;
            top: -2px !important;
            left: -2px !important;
            right: -2px !important;
            bottom: -2px !important;
            background: linear-gradient(45deg, 
                rgba(138, 43, 226, 0.3),
                rgba(59, 130, 246, 0.3),
                rgba(16, 185, 129, 0.3),
                rgba(251, 191, 36, 0.3)
            ) !important;
            background-size: 400% 400% !important;
            border-radius: 18px !important;
            opacity: 0.5 !important;
            animation: breathingLight 4s ease-in-out infinite !important;
            z-index: -1 !important;
            pointer-events: none !important;
        }
        
        @keyframes breathingLight {
            0%, 100% { 
                opacity: 0.3; 
                background-position: 0% 50%; 
            }
            50% { 
                opacity: 0.7; 
                background-position: 100% 50%; 
            }
        }
        
        /* Accessibility focus states */
        [data-filament-panel-id="petugas"] .fi-tabs-item:focus {
            outline: none !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.3),
                0 8px 32px rgba(31, 38, 135, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            [data-filament-panel-id="petugas"] .fi-tabs {
                padding: 0.5rem !important;
                margin-bottom: 1rem !important;
            }
            
            [data-filament-panel-id="petugas"] .fi-tabs-nav {
                gap: 0.5rem !important;
            }
            
            [data-filament-panel-id="petugas"] .fi-tabs-item {
                padding: 0.5rem 1rem !important;
                font-size: 0.875rem !important;
                min-height: 2.5rem !important;
            }
        }
    </style>
    
    {{-- JavaScript for Enhanced Interactions --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌟 Glassmorphism Tabs Enhancement Loaded!');
            
            // Add ripple effect to tab clicks
            const tabs = document.querySelectorAll('.fi-tabs-item');
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.6);
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                        z-index: 10;
                    `;
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Add glassmorphism enhancement badge
            const badge = document.createElement('div');
            badge.innerHTML = '🌟 Glassmorphism Enhanced';
            badge.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 8px;
                padding: 8px 16px;
                color: white;
                font-weight: 600;
                font-size: 14px;
                box-shadow: 0 4px 16px rgba(31, 38, 135, 0.3);
                z-index: 9999;
                animation: fadeInRight 0.5s ease;
            `;
            document.body.appendChild(badge);
            
            // Remove badge after 5 seconds
            setTimeout(() => {
                badge.style.animation = 'fadeOutRight 0.5s ease';
                setTimeout(() => badge.remove(), 500);
            }, 5000);
        });
        
        // CSS animations for badge
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes fadeInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes fadeOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(styleSheet);
    </script>
</x-filament-panels::page>