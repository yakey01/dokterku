{{-- World-Class Form Page for Jumlah Pasien Harian --}}
<x-filament-panels::page>
    {{-- Inject World-Class UI Styling --}}
    @include('filament.petugas.world-class-2025-ui')
    
    {{-- Force Ultra-Modern CSS Inline --}}
    <link rel="stylesheet" href="{{ asset('build/assets/css/ultra-world-class-2025-nkfUzcQm.css') }}">
    
    {{-- IMPORTANT: Render the actual Filament form --}}
    {{ $this->form }}
    
    {{-- Minimalist White World-Class UI 2025 --}}
    <style>
        /* 🤍 MINIMALIST WHITE WORLD-CLASS UI 2025 - INSPIRED BY DRIBBBLE & PINTEREST */
        
        /* Clean White Page Background */
        body[class*="fi-panel-petugas"],
        [data-filament-panel-id="petugas"],
        .fi-main,
        .fi-page,
        .fi-sidebar {
            background: #ffffff !important;
            min-height: 100vh !important;
        }
        
        /* Remove all gradient backgrounds */
        body[class*="fi-panel-petugas"]::before,
        [data-filament-panel-id="petugas"]::before {
            display: none !important;
        }
        
        /* Clean Minimalist Form Container */
        .fi-form,
        .fi-resource-create form,
        [data-filament-panel-id="petugas"] form {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            padding: 24px !important;
            margin: 16px auto !important;
            max-width: 800px !important;
        }
        
        /* Clean Minimalist Sections */
        .fi-section,
        .fi-fo-section,
        .fi-form-component-container > div {
            background: #ffffff !important;
            border: 1px solid #f3f4f6 !important;
            border-radius: 6px !important;
            padding: 20px !important;
            margin-bottom: 16px !important;
            box-shadow: none !important;
        }
        
        /* Clean Minimalist Input Fields */
        input,
        select,
        textarea,
        .fi-input {
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            color: #374151 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            transition: border-color 0.15s ease !important;
        }
        
        /* Clean Focus Effect */
        input:focus,
        select:focus,
        textarea:focus,
        .fi-input:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Clean Minimalist Buttons */
        button,
        .fi-btn {
            padding: 8px 16px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            transition: all 0.15s ease !important;
        }
        
        /* Primary buttons */
        .fi-btn-color-primary,
        .fi-btn-color-success,
        button[type="submit"] {
            background: #3b82f6 !important;
            color: white !important;
            border: 1px solid #3b82f6 !important;
        }
        
        .fi-btn-color-primary:hover,
        .fi-btn-color-success:hover,
        button[type="submit"]:hover {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
        
        /* Secondary buttons */
        .fi-btn-color-gray,
        .fi-btn-color-warning {
            background: #ffffff !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
        }
        
        .fi-btn-color-gray:hover,
        .fi-btn-color-warning:hover {
            background: #f9fafb !important;
            border-color: #9ca3af !important;
        }
        
        /* Clean Section Headers */
        .fi-section-heading,
        .fi-fo-section-heading {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #111827 !important;
            margin-bottom: 8px !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        
        /* Clean Labels */
        label,
        .fi-fo-field-wrp-label {
            font-size: 14px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            margin-bottom: 4px !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        
        
        /* Remove all gradient backgrounds and colorful effects */
        [data-filament-panel-id="petugas"] .fi-resource-create {
            background: #ffffff !important;
            min-height: 100vh !important;
        }
        
        /* Remove all hover effects and animations */
        [data-filament-panel-id="petugas"] .fi-section::before,
        [data-filament-panel-id="petugas"] .fi-fo-section::before {
            display: none !important;
        }
        
        /* Remove emojis and special styling */
        [data-filament-panel-id="petugas"] .fi-section-heading::before,
        [data-filament-panel-id="petugas"] .fi-fo-section-heading::before {
            display: none !important;
        }
        
        /* Remove shimmer and gradient effects from buttons */
        [data-filament-panel-id="petugas"] .fi-btn::before,
        [data-filament-panel-id="petugas"] .world-class-save-btn::before {
            display: none !important;
        }
        
        /* Clean form actions */
        [data-filament-panel-id="petugas"] .fi-form-actions {
            background: #ffffff !important;
            padding: 16px !important;
            border-radius: 6px !important;
            margin-top: 16px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: none !important;
        }
        
        /* Clean page header */
        [data-filament-panel-id="petugas"] .fi-header {
            background: #ffffff !important;
            color: #111827 !important;
            padding: 24px !important;
            border-radius: 6px !important;
            margin-bottom: 16px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: none !important;
        }
        
        [data-filament-panel-id="petugas"] .fi-header-heading {
            font-size: 24px !important;
            font-weight: 600 !important;
            color: #111827 !important;
        }
    </style>

    {{-- Additional JavaScript for Enhanced Interactions --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add world-class form identifier
            const form = document.querySelector('.fi-form');
            if (form) {
                form.classList.add('world-class-form');
                console.log('World-Class Form Styling Applied Successfully! 🎨');
            }

            // Enhance input interactions
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                // Add floating label effect
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });

                // Add haptic feedback simulation
                input.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 100);
                });
            });

            // Enhance button clicks with ripple effect
            const buttons = document.querySelectorAll('.fi-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    this.appendChild(ripple);

                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Auto-save indicator
            let saveTimeout;
            const showSaveIndicator = () => {
                clearTimeout(saveTimeout);
                const indicator = document.createElement('div');
                indicator.className = 'auto-save-indicator';
                indicator.textContent = 'Auto-saving...';
                indicator.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-weight: 600;
                    z-index: 9999;
                    animation: slideIn 0.3s ease;
                `;
                document.body.appendChild(indicator);

                saveTimeout = setTimeout(() => {
                    indicator.textContent = '✓ Saved';
                    setTimeout(() => {
                        indicator.remove();
                    }, 2000);
                }, 1000);
            };

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Cmd/Ctrl + S to save
                if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                    e.preventDefault();
                    const saveBtn = document.querySelector('.world-class-save-btn');
                    if (saveBtn) saveBtn.click();
                }

                // Escape to cancel
                if (e.key === 'Escape') {
                    const cancelBtn = document.querySelector('.world-class-cancel-btn');
                    if (cancelBtn) cancelBtn.click();
                }
            });

            // Progress indicator for form completion
            const updateProgress = () => {
                const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
                const filledInputs = Array.from(requiredInputs).filter(input => input.value);
                const progress = (filledInputs.length / requiredInputs.length) * 100;

                let progressBar = document.querySelector('.form-progress');
                if (!progressBar) {
                    progressBar = document.createElement('div');
                    progressBar.className = 'form-progress';
                    progressBar.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        height: 4px;
                        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                        transition: width 0.3s ease;
                        z-index: 9999;
                    `;
                    document.body.appendChild(progressBar);
                }
                progressBar.style.width = progress + '%';
            };

            // Monitor form changes
            document.addEventListener('input', updateProgress);
            document.addEventListener('change', updateProgress);

            // Initial progress check
            updateProgress();

            console.log('✨ World-Class Form Enhancement Complete!');
            
            // APPLY CLEAN MINIMALIST STYLES
            function applyMinimalistStyles() {
                console.log('🤍 APPLYING CLEAN MINIMALIST STYLES...');
                
                // Clean white background
                document.body.style.cssText += `
                    background: #ffffff !important;
                    min-height: 100vh !important;
                `;
                
                // Clean minimalist inputs
                document.querySelectorAll('input, select, textarea').forEach(input => {
                    input.style.cssText += `
                        background: #ffffff !important;
                        border: 1px solid rgba(209, 213, 219, 0.8) !important;
                        border-radius: 6px !important;
                        padding: 12px 16px !important;
                        font-size: 14px !important;
                        font-weight: 400 !important;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
                        transition: all 0.15s ease !important;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                    `;
                });
                
                // Clean minimalist buttons
                document.querySelectorAll('button, .fi-btn').forEach(button => {
                    if (button.type === 'submit' || button.classList.contains('fi-btn-color-primary')) {
                        button.style.cssText += `
                            background: #4285f4 !important;
                            color: white !important;
                            border: none !important;
                            border-radius: 6px !important;
                            padding: 10px 20px !important;
                            font-size: 14px !important;
                            font-weight: 500 !important;
                            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
                            transition: all 0.15s ease !important;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                        `;
                    } else {
                        button.style.cssText += `
                            background: #ffffff !important;
                            color: #374151 !important;
                            border: 1px solid rgba(209, 213, 219, 0.8) !important;
                            border-radius: 6px !important;
                            padding: 10px 20px !important;
                            font-size: 14px !important;
                            font-weight: 500 !important;
                            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
                            transition: all 0.15s ease !important;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                        `;
                    }
                });
                
                // Clean sections
                document.querySelectorAll('.fi-section, .fi-fo-section, .fi-form-component-container > div').forEach(section => {
                    section.style.cssText += `
                        background: #ffffff !important;
                        border: 1px solid rgba(226, 232, 240, 0.6) !important;
                        border-radius: 8px !important;
                        padding: 24px !important;
                        margin-bottom: 16px !important;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
                        transition: all 0.15s ease !important;
                    `;
                });
                
                console.log('✅ CLEAN MINIMALIST STYLES APPLIED!');
            }
            
            // Apply immediately and on mutations
            applyMinimalistStyles();
            setTimeout(applyMinimalistStyles, 100);
            setTimeout(applyMinimalistStyles, 500);
            
            // Watch for changes
            const observer = new MutationObserver(() => {
                setTimeout(applyMinimalistStyles, 50);
            });
            observer.observe(document.body, { childList: true, subtree: true });

            // Add Minimalist Badge
            const minimalBadge = document.createElement('div');
            minimalBadge.innerHTML = '🤍 Minimalist White UI 2025';
            minimalBadge.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: #ffffff;
                color: #374151;
                padding: 8px 16px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
                font-weight: 500;
                font-size: 14px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            `;
            document.body.appendChild(minimalBadge);
            
            setTimeout(() => {
                minimalBadge.style.opacity = '0';
                minimalBadge.style.transition = 'opacity 0.3s ease';
                setTimeout(() => minimalBadge.remove(), 300);
            }, 5000);
        });
    </script>

    {{-- Ripple Effect CSS --}}
    <style>
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .focused label {
            color: #667eea !important;
            transform: translateY(-2px);
        }
    </style>
</x-filament-panels::page>