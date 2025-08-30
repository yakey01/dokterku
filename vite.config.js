import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/map-styles.css',
                // Filament base theme - LOAD FIRST to prevent override issues
                'vendor/filament/filament/resources/css/theme.css',
                'resources/js/app.js',
                // Manager Dashboard - Isolated Entry Point
                'resources/js/manager-isolated.js',
                'resources/js/paramedis-mobile-app.tsx',
                'resources/js/dokter-mobile-app.tsx',
                'resources/js/dokter-mobile-app-simple.tsx',
                // 'resources/js/test-welcome-login.tsx', // File not found, commented out
                'resources/js/welcome-login-app.tsx',
                'resources/js/welcome-login-new.tsx',
                'resources/js/widget-animations.js',
                // 'resources/js/leaflet-utilities.ts', // Removed - using simple map component instead
                // 'resources/js/test-presensi.tsx', // File not found, commented out
                'resources/css/petugas-table-ux.css',
                'resources/css/filament/admin/theme-pure.css',
                'resources/css/filament/manajer/theme.css',
                'resources/css/filament/paramedis/theme.css',
                'resources/css/filament/petugas/theme.css',
                'resources/css/filament/petugas/world-class-dashboard.css',
                'resources/css/filament/petugas/world-class-crud.css',
                'resources/css/filament/petugas/world-class-2025.css',
                'resources/css/filament/petugas/world-class-crud-enhanced.css',
                'resources/css/filament/petugas/world-class-patient-table.css',
                'resources/css/filament/petugas/world-class-forms.css',
                'resources/css/filament/petugas/glassmorphism-tabs.css',
                'resources/css/filament/petugas/white-glass-tabs.css',
                'resources/css/filament/petugas/ultra-world-class-2025.css',
                'resources/js/components/dashboard-interactivity.js',
                'resources/js/petugas-dashboard-app.tsx',
                'resources/js/world-class-form-enhancer.js',
                // New unified Manajer Dashboard
                'resources/js/manajer-dashboard.tsx',
                'resources/css/manajer-white-smoke-ui.css',
                // Bendahara panel CSS - Isolated Architecture
                'resources/css/filament/bendahara/isolated-theme.css',
                // Bendahara validation tabs enhancement - CSS merged into theme.css
                'resources/js/validation-tabs.js',
            ],
            refresh: true,
            detectTls: false,
            buildDirectory: 'build',
            // ✅ OPTIMIZE PRELOADING: Only preload critical chunks
            valetTls: false,
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5174,
            protocol: 'ws',
            overlay: true,
            timeout: 30000,
        },
        cors: true,
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json'],
    },
    esbuild: {
        jsx: 'automatic',
        jsxImportSource: 'react',
        target: 'es2020', // ✅ SYNTAX FIX: es2020 supports dynamic import()
    },
    build: {
        rollupOptions: {
            output: {
                format: 'es',
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
                entryFileNames: `assets/js/[name]-[hash].js`,
                chunkFileNames: `assets/js/[name]-[hash].js`,
                manualChunks: {
                    // Bundle icon chunks together to reduce preload overhead
                    'icons': ['lucide-react']
                }
            },
        },
        sourcemap: process.env.NODE_ENV === 'development',
        minify: 'esbuild',
        target: 'es2020',
        chunkSizeWarningLimit: 1600,
        manifest: true,
        modulePreload: {
            // Only preload critical dependencies, not icon chunks
            polyfill: false,
            resolveDependencies: (filename, deps, { moduleGraph }) => {
                return deps.filter(dep => !dep.includes('icon') && !dep.includes('lucide'));
            }
        },
    },
});
