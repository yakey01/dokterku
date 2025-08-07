import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './vendor/filament/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
    ],
    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                mono: ['ui-monospace', 'SF Mono', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
                display: ['SF Pro Display', '-apple-system', 'system-ui', 'BlinkMacSystemFont', 'sans-serif'],
            },
            screens: {
                'xs': '475px',
                'mobile': {'max': '640px'},
                'tablet': {'min': '641px', 'max': '1024px'},
                'desktop': {'min': '1025px'},
            },
            spacing: {
                'touch': '44px',
                'mobile-safe': 'env(safe-area-inset-bottom)',
            },
            colors: {
                'dokterku': {
                    'primary': '#667eea',
                    'primary-light': '#8b94f0',
                    'primary-dark': '#4d5bc7',
                    'secondary': '#764ba2',
                    'accent': '#10b981',
                    'neutral': '#3d4451',
                    'surface': '#ffffff',
                    'success': '#10b981',
                    'warning': '#fbbd23',
                    'error': '#ef4444',
                    'info': '#3abff8',
                },
                // Filament color mappings
                'danger': colors.red,
                'gray': colors.gray,
                'info': colors.blue,
                'primary': colors.blue,
                'success': colors.green,
                'warning': colors.yellow,
            },
            fontSize: {
                'mobile-xs': '0.75rem',
                'mobile-sm': '0.875rem',
                'mobile-base': '1rem',
                'mobile-lg': '1.125rem',
                'mobile-xl': '1.25rem',
            },
            boxShadow: {
                'mobile': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                'mobile-lg': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            },
            animation: {
                'shimmer': 'shimmer 2s linear infinite',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-gentle': 'bounce-gentle 2s ease-in-out infinite',
                'glow': 'glow 2s ease-in-out infinite alternate',
            },
            keyframes: {
                shimmer: {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(100%)' }
                },
                'bounce-gentle': {
                    '0%, 100%': { 
                        transform: 'translateY(0)',
                        animationTimingFunction: 'cubic-bezier(0.8, 0, 1, 1)'
                    },
                    '50%': { 
                        transform: 'translateY(-5%)',
                        animationTimingFunction: 'cubic-bezier(0, 0, 0.2, 1)'
                    }
                },
                glow: {
                    '0%': { boxShadow: '0 0 5px rgba(59, 130, 246, 0.5)' },
                    '100%': { boxShadow: '0 0 20px rgba(59, 130, 246, 0.8)' }
                }
            },
            animationDelay: {
                '75': '75ms',
                '100': '100ms',
                '150': '150ms',
                '200': '200ms',
                '300': '300ms',
                '500': '500ms',
                '700': '700ms',
                '1000': '1000ms',
            },
        },
    },

    plugins: [forms, require('daisyui')],
    
    daisyui: {
        themes: [
            {
                dokterku: {
                    "primary": "#667eea",
                    "secondary": "#764ba2", 
                    "accent": "#10b981",
                    "neutral": "#3d4451",
                    "base-100": "#ffffff",
                    "info": "#3abff8",
                    "success": "#10b981",
                    "warning": "#fbbd23",
                    "error": "#ef4444",
                },
            },
            "light",
            "dark",
        ],
        base: false,  // Disable global CSS resets to prevent Filament conflicts
        styled: true,
        utils: true,
    },
};
