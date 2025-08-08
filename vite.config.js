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
                'resources/js/test-welcome-login.tsx',
                'resources/js/welcome-login-app.tsx',
                'resources/js/welcome-login-new.tsx',
                'resources/js/widget-animations.js',
                // 'resources/js/leaflet-utilities.ts', // Removed - using simple map component instead
                'resources/js/test-presensi.tsx',
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
        jsxFactory: 'React.createElement',
        jsxFragment: 'React.Fragment',
        jsxImportSource: 'react',
        jsx: 'automatic',
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
                manualChunks: undefined,
                sourcemapFileNames: 'assets/js/[name]-[hash].js.map',
            },
            onwarn(warning, warn) {
                if (warning.code === 'CIRCULAR_DEPENDENCY') {
                    console.warn('🔄 Circular dependency detected:', warning.message);
                    return;
                }
                if (warning.code === 'THIS_IS_UNDEFINED') {
                    console.warn('⚠️ TDZ issue detected (handled by esbuild):', warning.message);
                    return;
                }
                warn(warning);
            },
        },
        sourcemap: process.env.NODE_ENV === 'development',
        minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
        esbuild: {
            keepNames: true,
            minifyIdentifiers: process.env.NODE_ENV === 'production',
            target: 'esnext',
            sourcemap: process.env.NODE_ENV === 'development',
            jsxFactory: 'React.createElement',
            jsxFragment: 'React.Fragment',
            jsxImportSource: 'react',
            jsx: 'automatic',
        },
        assetsInclude: ['**/*.tsx', '**/*.ts', '**/*.jsx'],
        define: {
            __BUILD_TIME__: JSON.stringify(new Date().toISOString()),
            __BUILD_HASH__: JSON.stringify(Date.now().toString(36)),
            __DEV_MODE__: JSON.stringify(process.env.NODE_ENV === 'development'),
            __SOURCE_MAP_ENABLED__: JSON.stringify(true),
        },
        chunkSizeWarningLimit: 1600,
        manifest: true,
        reportCompressedSize: true,
    },
});
