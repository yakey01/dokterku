/**
 * World-Class Form Enhancer for Petugas Panel
 * Forces world-class styling on Jumlah Pasien Harian forms
 */

(function() {
    'use strict';

    const applyWorldClassFormStyling = () => {
        // Check if we're on the jumlah-pasien-harians/create page
        if (!window.location.pathname.includes('jumlah-pasien-harians/create')) {
            return;
        }

        console.log('🎨 Applying World-Class Form Styling...');

        // Create style element if it doesn't exist
        let styleElement = document.getElementById('world-class-form-styles');
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'world-class-form-styles';
            document.head.appendChild(styleElement);
        }

        // Apply comprehensive form styling
        styleElement.textContent = `
            /* Force World-Class Form Styling */
            [data-filament-panel-id="petugas"] {
                --primary-color: #667eea;
                --secondary-color: #764ba2;
                --success-color: #48bb78;
                --warning-color: #f6ad55;
            }

            /* Form Container */
            [data-filament-panel-id="petugas"] .fi-resource-create,
            [data-filament-panel-id="petugas"] form {
                animation: fadeInUp 0.5s ease;
            }

            /* All Form Sections */
            [data-filament-panel-id="petugas"] .fi-section,
            [data-filament-panel-id="petugas"] [class*="fi-fo-section"],
            [data-filament-panel-id="petugas"] [class*="fi-form-component-container"] > div {
                background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%) !important;
                border-radius: 20px !important;
                box-shadow: 
                    0 4px 16px rgba(0, 0, 0, 0.04),
                    0 8px 32px rgba(0, 0, 0, 0.02) !important;
                padding: 2rem !important;
                margin-bottom: 1.5rem !important;
                border: 1px solid #e2e8f0 !important;
                position: relative !important;
                overflow: hidden !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            /* Section Accent Border */
            [data-filament-panel-id="petugas"] .fi-section::before,
            [data-filament-panel-id="petugas"] [class*="fi-fo-section"]::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            [data-filament-panel-id="petugas"] .fi-section:hover::before,
            [data-filament-panel-id="petugas"] [class*="fi-fo-section"]:hover::before {
                opacity: 1;
            }

            [data-filament-panel-id="petugas"] .fi-section:hover,
            [data-filament-panel-id="petugas"] [class*="fi-fo-section"]:hover {
                transform: translateX(8px) scale(1.01) !important;
                box-shadow: 
                    0 8px 24px rgba(102, 126, 234, 0.12),
                    0 16px 48px rgba(102, 126, 234, 0.08) !important;
                border-color: rgba(102, 126, 234, 0.2) !important;
            }

            /* All Input Fields */
            [data-filament-panel-id="petugas"] input:not([type="checkbox"]):not([type="radio"]),
            [data-filament-panel-id="petugas"] select,
            [data-filament-panel-id="petugas"] textarea,
            [data-filament-panel-id="petugas"] [class*="fi-input"] {
                background: white !important;
                border: 2px solid #e2e8f0 !important;
                border-radius: 14px !important;
                padding: 0.875rem 1.25rem !important;
                font-size: 0.95rem !important;
                font-weight: 500 !important;
                color: #2d3748 !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
                width: 100% !important;
            }

            /* Input Focus State */
            [data-filament-panel-id="petugas"] input:focus,
            [data-filament-panel-id="petugas"] select:focus,
            [data-filament-panel-id="petugas"] textarea:focus {
                border-color: var(--primary-color) !important;
                box-shadow: 
                    0 0 0 4px rgba(102, 126, 234, 0.1),
                    0 4px 16px rgba(102, 126, 234, 0.1) !important;
                transform: translateY(-2px) !important;
                outline: none !important;
            }

            /* Select Dropdown Styling */
            [data-filament-panel-id="petugas"] select {
                appearance: none !important;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
                background-repeat: no-repeat !important;
                background-position: right 1rem center !important;
                background-size: 1.5rem !important;
                padding-right: 3rem !important;
                cursor: pointer !important;
            }

            /* Labels */
            [data-filament-panel-id="petugas"] label,
            [data-filament-panel-id="petugas"] [class*="fi-fo-field-wrp-label"] {
                font-weight: 600 !important;
                color: #4a5568 !important;
                font-size: 0.95rem !important;
                margin-bottom: 0.5rem !important;
                display: block !important;
            }

            /* Helper Text */
            [data-filament-panel-id="petugas"] [class*="fi-fo-field-wrp-helper-text"],
            [data-filament-panel-id="petugas"] [class*="fi-fo-field-wrp-hint"] {
                color: #718096 !important;
                font-size: 0.875rem !important;
                margin-top: 0.5rem !important;
                font-style: italic !important;
            }

            /* Action Buttons Container */
            [data-filament-panel-id="petugas"] .fi-form-actions,
            [data-filament-panel-id="petugas"] [class*="fi-ac"] {
                background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%) !important;
                padding: 2rem !important;
                border-radius: 20px !important;
                box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.04) !important;
                margin-top: 2rem !important;
                display: flex !important;
                gap: 1rem !important;
                justify-content: flex-end !important;
                flex-wrap: wrap !important;
            }

            /* All Buttons */
            [data-filament-panel-id="petugas"] button,
            [data-filament-panel-id="petugas"] [class*="fi-btn"] {
                padding: 1rem 2rem !important;
                font-size: 1rem !important;
                font-weight: 600 !important;
                border-radius: 14px !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                position: relative !important;
                overflow: hidden !important;
                text-transform: none !important;
                letter-spacing: 0.025em !important;
            }

            /* Primary/Submit Button */
            [data-filament-panel-id="petugas"] button[type="submit"],
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-primary"],
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-success"] {
                background: linear-gradient(135deg, var(--success-color) 0%, #38a169 100%) !important;
                color: white !important;
                border: none !important;
                box-shadow: 
                    0 4px 16px rgba(72, 187, 120, 0.3),
                    0 2px 8px rgba(0, 0, 0, 0.1) !important;
            }

            [data-filament-panel-id="petugas"] button[type="submit"]:hover,
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-primary"]:hover,
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-success"]:hover {
                transform: translateY(-3px) scale(1.05) !important;
                box-shadow: 
                    0 8px 24px rgba(72, 187, 120, 0.4),
                    0 4px 12px rgba(0, 0, 0, 0.15) !important;
            }

            /* Secondary Button */
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-warning"] {
                background: linear-gradient(135deg, var(--warning-color) 0%, #ed8936 100%) !important;
                color: white !important;
                border: none !important;
                box-shadow: 
                    0 4px 16px rgba(237, 137, 54, 0.3),
                    0 2px 8px rgba(0, 0, 0, 0.1) !important;
            }

            /* Cancel Button */
            [data-filament-panel-id="petugas"] [class*="fi-btn-color-gray"] {
                background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%) !important;
                color: #2d3748 !important;
                border: 2px solid #cbd5e0 !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
            }

            /* Button Shimmer Effect */
            [data-filament-panel-id="petugas"] button::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                transition: left 0.5s ease;
            }

            [data-filament-panel-id="petugas"] button:hover::before {
                left: 100%;
            }

            /* Date Picker Enhancement */
            [data-filament-panel-id="petugas"] input[type="date"]::-webkit-calendar-picker-indicator {
                cursor: pointer;
                border-radius: 4px;
                padding: 4px;
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                filter: invert(1);
            }

            /* Textarea Enhancement */
            [data-filament-panel-id="petugas"] textarea {
                min-height: 100px !important;
                resize: vertical !important;
            }

            /* Grid Gap */
            [data-filament-panel-id="petugas"] .grid,
            [data-filament-panel-id="petugas"] [class*="fi-fo-grid"] {
                gap: 1.5rem !important;
            }

            /* Animation Keyframes */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Placeholder Styling */
            [data-filament-panel-id="petugas"] input::placeholder,
            [data-filament-panel-id="petugas"] textarea::placeholder {
                color: #a0aec0 !important;
                font-style: italic !important;
            }

            /* Section Heading Icons */
            [data-filament-panel-id="petugas"] [class*="fi-section-heading"] {
                font-size: 1.5rem !important;
                font-weight: 700 !important;
                color: #1a202c !important;
                margin-bottom: 0.5rem !important;
            }

            /* Total Display Placeholder */
            [data-filament-panel-id="petugas"] [class*="fi-fo-placeholder"] {
                background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%) !important;
                padding: 1.25rem !important;
                border-radius: 14px !important;
                border: 2px solid #cbd5e0 !important;
                font-size: 1.1rem !important;
                font-weight: 600 !important;
                color: #2d3748 !important;
                text-align: center !important;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06) !important;
            }

            /* Loading State */
            [data-filament-panel-id="petugas"] form.loading {
                opacity: 0.6;
                pointer-events: none;
            }

            /* Success State */
            [data-filament-panel-id="petugas"] .success-pulse {
                animation: successPulse 0.5s ease;
            }

            @keyframes successPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                [data-filament-panel-id="petugas"] .fi-form-actions {
                    flex-direction: column !important;
                }

                [data-filament-panel-id="petugas"] button {
                    width: 100% !important;
                }
            }
        `;

        // Apply additional enhancements
        enhanceFormInteractions();
        
        console.log('✅ World-Class Form Styling Applied Successfully!');
    };

    const enhanceFormInteractions = () => {
        // Add hover effects to form sections
        const sections = document.querySelectorAll('[class*="fi-section"], [class*="fi-fo-section"]');
        sections.forEach(section => {
            section.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s ease';
            });
        });

        // Enhance input focus interactions
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const wrapper = this.closest('[class*="fi-fo-field-wrp"]');
                if (wrapper) {
                    wrapper.style.transform = 'scale(1.02)';
                    wrapper.style.transition = 'transform 0.3s ease';
                }
            });

            input.addEventListener('blur', function() {
                const wrapper = this.closest('[class*="fi-fo-field-wrp"]');
                if (wrapper) {
                    wrapper.style.transform = 'scale(1)';
                }
            });
        });

        // Add safe ripple effect to buttons using DOMSafety utility
        const buttons = document.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Use the safe ripple creation method
                if (window.DOMSafety) {
                    window.DOMSafety.createRipple(this, e);
                } else {
                    // Fallback with manual safe implementation
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple-effect';
                    this.appendChild(ripple);

                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.6);
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;

                    // Safe ripple removal with comprehensive protection
                    setTimeout(() => {
                        try {
                            // Triple validation for safe removal
                            if (ripple && ripple.parentNode && document.contains(ripple)) {
                                ripple.remove();
                            }
                        } catch (error) {
                            if (error.name === 'NotFoundError') {
                                // Element already removed, this is expected
                                console.debug('✓ Ripple element already removed');
                            } else {
                                console.warn('⚠️ Ripple cleanup error:', error.message);
                            }
                        }
                    }, 600);
                }
            });
        });

        // Add ripple animation style
        if (!document.getElementById('ripple-animation-style')) {
            const rippleStyle = document.createElement('style');
            rippleStyle.id = 'ripple-animation-style';
            rippleStyle.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(rippleStyle);
        }
    };

    // Apply styling immediately
    applyWorldClassFormStyling();

    // Apply on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyWorldClassFormStyling);
    }

    // Apply on Livewire navigation
    document.addEventListener('livewire:navigated', applyWorldClassFormStyling);
    document.addEventListener('livewire:load', applyWorldClassFormStyling);
    
    // Apply on Filament events
    document.addEventListener('filament:mounted', applyWorldClassFormStyling);
    document.addEventListener('filament:loaded', applyWorldClassFormStyling);

    // Watch for dynamic changes
    if (document.body) {
        const observer = new MutationObserver(() => {
            if (window.location.pathname.includes('jumlah-pasien-harians/create')) {
                applyWorldClassFormStyling();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
    } else {
        // If body is not ready, wait for it
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new MutationObserver(() => {
                if (window.location.pathname.includes('jumlah-pasien-harians/create')) {
                    applyWorldClassFormStyling();
                }
            });

            if (document.body) {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        });
    }

    // Apply after delays to catch late-loading elements
    setTimeout(applyWorldClassFormStyling, 100);
    setTimeout(applyWorldClassFormStyling, 500);
    setTimeout(applyWorldClassFormStyling, 1000);
    setTimeout(applyWorldClassFormStyling, 2000);

    // Export for global access
    window.WorldClassFormEnhancer = {
        apply: applyWorldClassFormStyling,
        enhance: enhanceFormInteractions
    };

    console.log('🚀 World-Class Form Enhancer Initialized');
})();