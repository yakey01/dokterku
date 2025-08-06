import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        react({
            jsxRuntime: 'automatic',
            fastRefresh: true,
        }),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/paramedis-mobile-app.tsx',
                'resources/js/dokter-mobile-app.tsx',
                'resources/js/test-welcome-login.tsx',
                'resources/js/welcome-login-app.tsx',
                'resources/js/welcome-login-new.tsx',
                'resources/js/widget-animations.js',
                'resources/css/petugas-table-ux.css',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/bendahara/theme.css',
                'resources/css/filament/manajer/theme.css',
                'resources/css/filament/paramedis/theme.css',
                'resources/css/filament/petugas/theme.css',
            ],
            refresh: true,
            detectTls: false,
            buildDirectory: 'build',
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
        cors: true,
        origin: 'http://127.0.0.1:8000',
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
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
            },
        },
    },
});
