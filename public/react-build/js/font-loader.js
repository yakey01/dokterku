/**
 * Medical Dashboard Font Loader v3.0
 * CSS-first approach with enhanced compatibility
 * Avoids FontFace API issues in development environments
 */

(function() {
    'use strict';
    
    console.log('🔤 Font Loader v3.0 - CSS-first approach');
    
    // Configuration
    const FONT_CONFIG = {
        inter: {
            name: 'Inter',
            cssClass: 'font-inter-active'
        },
        geist: {
            name: 'Geist Sans', 
            cssClass: 'font-geist-active'
        }
    };
    
    // State management
    const state = {
        currentFont: 'inter',
        isLoaded: false,
        startTime: performance.now(),
        loadMethod: 'css' // Always use CSS loading
    };
    
    // Font system
    const FontSystem = {
        // Initialize font system
        init() {
            console.log('📥 Initializing CSS-first font system...');
            
            // Apply immediate system font fallbacks
            this.applySystemFonts();
            
            // Load fonts via CSS (reliable method)
            this.loadFontsViaCSS()
                .then(() => {
                    console.log('✅ CSS font loading successful');
                    this.onFontsLoaded();
                })
                .catch((error) => {
                    console.warn('⚠️ CSS loading failed, using system fonts:', error);
                    this.onFontsLoaded(); // Still mark as loaded to prevent loops
                });
        },
        
        // Apply system fonts immediately (prevents FOIT)
        applySystemFonts() {
            const fallbackCSS = `
                :root {
                    --font-fallback: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    --font-mono-fallback: "SF Mono", Monaco, Inconsolata, "Roboto Mono", Consolas, monospace;
                }
                
                /* Immediate fallbacks */
                body, html {
                    font-family: var(--font-fallback) !important;
                }
                
                .font-medical, .font-loading, .font-geist, .font-inter {
                    font-family: var(--font-fallback) !important;
                }
                
                .font-mono, .font-geist-mono {
                    font-family: var(--font-mono-fallback) !important;
                }
                
                /* Loading indicator */
                .fonts-loading .loading-indicator::after {
                    content: " (Loading fonts...)";
                    opacity: 0.7;
                    font-size: 0.8em;
                }
            `;
            
            const style = document.createElement('style');
            style.id = 'font-system-fallback';
            style.textContent = fallbackCSS;
            document.head.appendChild(style);
            
            // Add loading class
            document.documentElement.classList.add('fonts-loading');
            
            console.log('🔧 Applied system font fallbacks');
        },
        
        // Load fonts via CSS (most reliable method)
        loadFontsViaCSS() {
            return new Promise((resolve, reject) => {
                console.log('📥 Loading fonts via CSS...');
                
                // Check if CSS is already loaded
                const existingLink = document.querySelector('link[href*="medical-fonts.css"]');
                if (existingLink && existingLink.sheet) {
                    console.log('✅ CSS fonts already loaded');
                    resolve();
                    return;
                }
                
                // Create CSS link
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '/css/fonts/medical-fonts.css';
                link.crossOrigin = 'anonymous';
                
                // Handle loading
                link.onload = () => {
                    console.log('✅ Medical fonts CSS loaded successfully');
                    
                    // Wait a moment for fonts to be parsed
                    setTimeout(() => {
                        this.testFontAvailability()
                            .then(resolve)
                            .catch(resolve); // Resolve anyway, we have CSS
                    }, 100);
                };
                
                link.onerror = () => {
                    console.warn('❌ Failed to load medical fonts CSS');
                    reject(new Error('CSS loading failed'));
                };
                
                // Add timeout
                setTimeout(() => {
                    if (!link.sheet) {
                        console.warn('⏱️ CSS loading timeout');
                        reject(new Error('CSS loading timeout'));
                    }
                }, 5000);
                
                document.head.appendChild(link);
            });
        },
        
        // Test if fonts are actually available
        testFontAvailability() {
            return new Promise((resolve) => {
                console.log('🔍 Testing font availability...');
                
                if (!document.fonts || !document.fonts.check) {
                    console.log('📝 FontFace API not available, assuming CSS fonts work');
                    resolve();
                    return;
                }
                
                // Test if fonts are loaded
                const tests = [
                    () => document.fonts.check('1em Inter'),
                    () => document.fonts.check('1em "Geist Sans"')
                ];
                
                let attempts = 0;
                const maxAttempts = 10;
                
                const testFonts = () => {
                    attempts++;
                    
                    try {
                        const results = tests.map(test => {
                            try {
                                return test();
                            } catch (e) {
                                return false;
                            }
                        });
                        
                        const anyLoaded = results.some(r => r === true);
                        
                        if (anyLoaded) {
                            console.log('✅ Fonts detected as available');
                            resolve();
                        } else if (attempts >= maxAttempts) {
                            console.log('📝 Font detection timeout, proceeding anyway');
                            resolve();
                        } else {
                            setTimeout(testFonts, 100);
                        }
                    } catch (error) {
                        console.warn('Font detection error:', error);
                        resolve(); // Proceed anyway
                    }
                };
                
                testFonts();
            });
        },
        
        // Handle successful font loading
        onFontsLoaded() {
            if (state.isLoaded) return;
            
            state.isLoaded = true;
            const loadTime = performance.now() - state.startTime;
            
            // Remove fallback styles and add loaded styles
            const fallbackStyle = document.getElementById('font-system-fallback');
            if (fallbackStyle) {
                fallbackStyle.remove();
            }
            
            // Apply font system styles
            const config = FONT_CONFIG[state.currentFont];
            const loadedCSS = `
                :root {
                    --font-inter: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --font-geist: 'Geist Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --font-geist-mono: 'Geist Mono', "SF Mono", Monaco, Inconsolata, "Roboto Mono", monospace;
                    --font-current: var(--font-${state.currentFont});
                }
                
                /* Font system classes */
                .fonts-loaded {
                    font-family: var(--font-current) !important;
                }
                
                .fonts-loaded .font-medical,
                .fonts-loaded .font-loading,
                .fonts-loaded .font-inter {
                    font-family: var(--font-inter) !important;
                }
                
                .fonts-loaded .font-geist {
                    font-family: var(--font-geist) !important;
                }
                
                .fonts-loaded .font-geist-mono {
                    font-family: var(--font-geist-mono) !important;
                }
                
                /* Dynamic font switching */
                .font-inter-active .font-medical,
                .font-inter-active .font-loading {
                    font-family: var(--font-inter) !important;
                }
                
                .font-geist-active .font-medical,
                .font-geist-active .font-loading {
                    font-family: var(--font-geist) !important;
                }
                
                /* Enhanced typography */
                .fonts-loaded .medical-data,
                .fonts-loaded .dashboard-metric {
                    font-variant-numeric: tabular-nums;
                    font-feature-settings: 'tnum' 1;
                }
                
                /* Smooth transition */
                .fonts-loaded * {
                    font-family: inherit;
                    transition: font-family 0.3s ease;
                }
            `;
            
            const style = document.createElement('style');
            style.id = 'font-system-loaded';
            style.textContent = loadedCSS;
            document.head.appendChild(style);
            
            // Update document classes
            document.documentElement.classList.remove('fonts-loading');
            document.documentElement.classList.add('fonts-loaded');
            document.documentElement.classList.add(config.cssClass);
            document.body.classList.add('fonts-loaded');
            
            // Dispatch success event
            const event = new CustomEvent('fontsLoaded', {
                detail: {
                    fontFamily: config.name,
                    loadTime: Math.round(loadTime),
                    method: state.loadMethod,
                    success: true
                }
            });
            document.dispatchEvent(event);
            
            console.log(`🎉 Font system ready with ${config.name} via ${state.loadMethod} (${Math.round(loadTime)}ms)`);
        },
        
        // Switch font family
        switchFont(fontType) {
            if (!FONT_CONFIG[fontType]) {
                console.warn(`❌ Unknown font type: ${fontType}`);
                return;
            }
            
            const oldConfig = FONT_CONFIG[state.currentFont];
            const newConfig = FONT_CONFIG[fontType];
            
            console.log(`🔄 Switching from ${oldConfig.name} to ${newConfig.name}...`);
            
            // Update state
            state.currentFont = fontType;
            
            // Update CSS classes
            document.documentElement.classList.remove(oldConfig.cssClass);
            document.documentElement.classList.add(newConfig.cssClass);
            
            // Update CSS variable
            const rootStyle = document.documentElement.style;
            rootStyle.setProperty('--font-current', `var(--font-${fontType})`);
            
            // Dispatch switch event
            const event = new CustomEvent('fontSwitched', {
                detail: {
                    from: oldConfig.name,
                    to: newConfig.name,
                    fontType: fontType
                }
            });
            document.dispatchEvent(event);
            
            console.log(`✅ Switched to ${newConfig.name}`);
        },
        
        // Check font availability (simplified)
        checkAvailability() {
            const hasCSS = document.querySelector('link[href*="medical-fonts.css"]');
            const hasFontSystem = document.getElementById('font-system-loaded');
            
            return {
                css: !!hasCSS,
                system: !!hasFontSystem,
                inter: state.isLoaded,
                geist: state.isLoaded,
                method: state.loadMethod,
                available: state.isLoaded
            };
        },
        
        // Get current status
        getStatus() {
            return {
                loaded: state.isLoaded,
                currentFont: state.currentFont,
                fontName: FONT_CONFIG[state.currentFont]?.name,
                loadTime: state.isLoaded ? Math.round(performance.now() - state.startTime) : null,
                method: state.loadMethod,
                classes: Array.from(document.documentElement.classList).filter(c => c.includes('font'))
            };
        }
    };
    
    // Global API
    window.FontSystem = {
        switchFont: FontSystem.switchFont.bind(FontSystem),
        checkAvailability: FontSystem.checkAvailability.bind(FontSystem),
        getStatus: FontSystem.getStatus.bind(FontSystem),
        // Debug helpers
        reload: FontSystem.init.bind(FontSystem),
        test: FontSystem.testFontAvailability.bind(FontSystem)
    };
    
    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FontSystem.init());
    } else {
        FontSystem.init();
    }
    
    // Event listeners for debugging
    document.addEventListener('fontsLoaded', (event) => {
        console.log('📊 Font System Ready:', {
            detail: event.detail,
            availability: FontSystem.checkAvailability(),
            status: FontSystem.getStatus()
        });
    });
    
    document.addEventListener('fontSwitched', (event) => {
        console.log('🔄 Font Switched:', event.detail);
    });
    
})();