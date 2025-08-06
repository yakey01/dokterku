/**
 * Enhanced Theme System
 * Healthcare Management System
 * 
 * Provides dynamic theme switching, role-based themes,
 * and dark/light mode support across all panels.
 */

class ThemeSystem {
    constructor() {
        this.themes = {
            light: 'light',
            dark: 'dark',
            auto: 'auto'
        };

        this.roleThemes = {
            admin: {
                primary: '#dc2626',
                accent: '#fca5a5',
                name: 'admin-theme'
            },
            manajer: {
                primary: '#7c3aed',
                accent: '#c4b5fd',
                name: 'manajer-theme'
            },
            bendahara: {
                primary: '#059669',
                accent: '#6ee7b7',
                name: 'bendahara-theme'
            },
            petugas: {
                primary: '#2563eb',
                accent: '#93c5fd',
                name: 'petugas-theme'
            },
            paramedis: {
                primary: '#ea580c',
                accent: '#fdba74',
                name: 'paramedis-theme'
            }
        };

        this.currentTheme = this.getStoredTheme() || 'auto';
        this.currentRole = this.getUserRole();
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        this.init();
    }

    /**
     * Initialize theme system
     */
    init() {
        this.applyTheme();
        this.applyRoleTheme();
        this.setupEventListeners();
        this.createThemeToggle();
        this.observeSystemTheme();
    }

    /**
     * Get stored theme preference
     */
    getStoredTheme() {
        return localStorage.getItem('dokterku-theme');
    }

    /**
     * Store theme preference
     */
    setStoredTheme(theme) {
        localStorage.setItem('dokterku-theme', theme);
    }

    /**
     * Get user role from document or localStorage
     */
    getUserRole() {
        const userRole = document.querySelector('meta[name="user-role"]')?.content ||
                        localStorage.getItem('dokterku-user-role') ||
                        'petugas';
        return userRole.toLowerCase();
    }

    /**
     * Determine if dark mode should be active
     */
    isDarkMode() {
        if (this.currentTheme === 'dark') return true;
        if (this.currentTheme === 'light') return false;
        return this.mediaQuery.matches; // auto mode
    }

    /**
     * Apply current theme
     */
    applyTheme() {
        const isDark = this.isDarkMode();
        const root = document.documentElement;
        
        // Set theme attribute
        root.setAttribute('data-theme', isDark ? 'dark' : 'light');
        
        // Apply theme classes
        root.classList.toggle('dark', isDark);
        root.classList.toggle('light', !isDark);
        
        // Update meta theme-color for mobile browsers
        this.updateMetaThemeColor(isDark);
        
        // Emit theme change event
        this.emitThemeChangeEvent(isDark ? 'dark' : 'light');
    }

    /**
     * Apply role-based theme
     */
    applyRoleTheme() {
        const roleTheme = this.roleThemes[this.currentRole];
        if (!roleTheme) return;

        const root = document.documentElement;
        
        // Apply role theme class
        Object.keys(this.roleThemes).forEach(role => {
            root.classList.remove(`role-${role}`);
        });
        root.classList.add(`role-${this.currentRole}`);
        
        // Set role-specific CSS custom properties
        root.style.setProperty('--role-primary', roleTheme.primary);
        root.style.setProperty('--role-accent', roleTheme.accent);
        
        // Update Filament panel ID attribute if present
        const panelElement = document.querySelector('[data-filament-panel-id]');
        if (panelElement) {
            panelElement.setAttribute('data-role-theme', this.currentRole);
        }
    }

    /**
     * Update meta theme-color for mobile browsers
     */
    updateMetaThemeColor(isDark) {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        
        const color = isDark ? '#1f2937' : '#ffffff';
        metaThemeColor.content = color;
    }

    /**
     * Set theme
     */
    setTheme(theme) {
        if (!Object.values(this.themes).includes(theme)) return;
        
        this.currentTheme = theme;
        this.setStoredTheme(theme);
        this.applyTheme();
    }

    /**
     * Toggle theme between light and dark
     */
    toggleTheme() {
        const newTheme = this.isDarkMode() ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    /**
     * Set role theme
     */
    setRoleTheme(role) {
        if (!this.roleThemes[role]) return;
        
        this.currentRole = role;
        localStorage.setItem('dokterku-user-role', role);
        this.applyRoleTheme();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for storage changes (theme sync across tabs)
        window.addEventListener('storage', (e) => {
            if (e.key === 'dokterku-theme') {
                this.currentTheme = e.newValue || 'auto';
                this.applyTheme();
            }
        });

        // Listen for role changes
        document.addEventListener('role-changed', (e) => {
            this.setRoleTheme(e.detail.role);
        });
    }

    /**
     * Observe system theme changes
     */
    observeSystemTheme() {
        this.mediaQuery.addEventListener('change', () => {
            if (this.currentTheme === 'auto') {
                this.applyTheme();
            }
        });
    }

    /**
     * Create theme toggle button
     */
    createThemeToggle() {
        // Only create if toggle doesn't exist
        if (document.querySelector('.theme-toggle')) return;

        const toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.innerHTML = this.getToggleIcon();
        toggle.setAttribute('aria-label', 'Toggle theme');
        toggle.addEventListener('click', () => this.toggleTheme());

        // Add to navigation or header
        const nav = document.querySelector('.fi-topbar') || 
                   document.querySelector('.main-navigation') ||
                   document.querySelector('header');
        
        if (nav) {
            nav.appendChild(toggle);
        }
    }

    /**
     * Get theme toggle icon
     */
    getToggleIcon() {
        const isDark = this.isDarkMode();
        
        if (isDark) {
            return `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
            `;
        } else {
            return `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            `;
        }
    }

    /**
     * Emit theme change event
     */
    emitThemeChangeEvent(theme) {
        const event = new CustomEvent('theme-changed', {
            detail: { 
                theme: theme,
                role: this.currentRole,
                isDark: theme === 'dark'
            }
        });
        document.dispatchEvent(event);
    }

    /**
     * Get current theme info
     */
    getCurrentTheme() {
        return {
            theme: this.currentTheme,
            resolved: this.isDarkMode() ? 'dark' : 'light',
            role: this.currentRole,
            roleTheme: this.roleThemes[this.currentRole]
        };
    }

    /**
     * Add theme transition class to prevent flash
     */
    enableTransitions() {
        document.documentElement.classList.add('theme-transition');
        
        // Remove after transition completes
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transition');
        }, 300);
    }

    /**
     * Auto-detect role from current panel
     */
    detectRoleFromPanel() {
        const panelId = document.querySelector('[data-filament-panel-id]')?.getAttribute('data-filament-panel-id');
        
        if (panelId) {
            const roleMap = {
                'admin': 'admin',
                'manajer': 'manajer', 
                'bendahara': 'bendahara',
                'petugas': 'petugas',
                'paramedis': 'paramedis'
            };
            
            const detectedRole = roleMap[panelId];
            if (detectedRole && detectedRole !== this.currentRole) {
                this.setRoleTheme(detectedRole);
            }
        }
    }

    /**
     * Apply high contrast mode for accessibility
     */
    setHighContrast(enabled) {
        document.documentElement.classList.toggle('high-contrast', enabled);
        localStorage.setItem('dokterku-high-contrast', enabled.toString());
    }

    /**
     * Apply reduced motion for accessibility
     */
    setReducedMotion(enabled) {
        document.documentElement.classList.toggle('reduce-motion', enabled);
        localStorage.setItem('dokterku-reduced-motion', enabled.toString());
    }

    /**
     * Initialize accessibility preferences
     */
    initAccessibility() {
        // High contrast
        const highContrast = localStorage.getItem('dokterku-high-contrast') === 'true';
        this.setHighContrast(highContrast);

        // Reduced motion
        const reducedMotion = localStorage.getItem('dokterku-reduced-motion') === 'true' ||
                             window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.setReducedMotion(reducedMotion);
    }
}

/**
 * CSS for theme transitions and toggle button
 */
const themeStyles = `
    .theme-transition * {
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease !important;
    }

    .theme-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border: none;
        background: transparent;
        border-radius: 0.375rem;
        color: var(--color-gray-600);
        cursor: pointer;
        transition: var(--transition-default);
    }

    .theme-toggle:hover {
        background-color: var(--color-gray-100);
        color: var(--color-gray-900);
    }

    [data-theme="dark"] .theme-toggle:hover {
        background-color: var(--color-gray-700);
        color: var(--color-gray-100);
    }

    /* Role-based accent colors */
    .role-admin {
        --role-primary: #dc2626;
        --role-accent: #fca5a5;
    }

    .role-manajer {
        --role-primary: #7c3aed;
        --role-accent: #c4b5fd;
    }

    .role-bendahara {
        --role-primary: #059669;
        --role-accent: #6ee7b7;
    }

    .role-petugas {
        --role-primary: #2563eb;
        --role-accent: #93c5fd;
    }

    .role-paramedis {
        --role-primary: #ea580c;
        --role-accent: #fdba74;
    }

    /* High contrast mode */
    .high-contrast {
        --color-gray-50: #ffffff;
        --color-gray-900: #000000;
        filter: contrast(1.2);
    }

    /* Reduced motion */
    .reduce-motion *,
    .reduce-motion *::before,
    .reduce-motion *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }

    /* Dark mode optimizations */
    [data-theme="dark"] {
        color-scheme: dark;
    }

    [data-theme="light"] {
        color-scheme: light;
    }
`;

/**
 * Initialize theme system when DOM is ready
 */
function initThemeSystem() {
    // Add theme styles to document
    const styleSheet = document.createElement('style');
    styleSheet.textContent = themeStyles;
    document.head.appendChild(styleSheet);

    // Initialize theme system
    window.themeSystem = new ThemeSystem();
    window.themeSystem.initAccessibility();
    window.themeSystem.detectRoleFromPanel();
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeSystem);
} else {
    initThemeSystem();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeSystem;
}