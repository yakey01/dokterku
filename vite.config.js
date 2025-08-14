import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/map-styles.css',
                'resources/js/app.js',
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
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/bendahara/theme.css',
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
            ],
            refresh: true,
            detectTls: false,
            buildDirectory: 'build',
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
            protocol: 'ws',
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
        target: 'es2015',
    },
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
                entryFileNames: `assets/js/[name]-[hash].js`,
                chunkFileNames: `assets/js/[name]-[hash].js`,
            },
        },
        sourcemap: false,
        minify: 'esbuild',
        target: 'es2015',
        chunkSizeWarningLimit: 1600,
        manifest: true,
    },
});
