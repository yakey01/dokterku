/**
 * Vite Build Debug & Source Map Diagnostics
 * 
 * This configuration helps diagnose and fix source map issues in production builds.
 * Run with: NODE_ENV=development vite build --config debug-vite-config.js
 */

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        react({
            jsxRuntime: 'automatic',
            fastRefresh: true,
            babel: {
                plugins: [
                    // Enhanced debugging support
                    ['@babel/plugin-transform-react-jsx-development', {
                        runtime: 'automatic'
                    }]
                ],
                sourceMaps: true,
                retainLines: true,
                // Preserve original component names
                minified: false,
            },
        }),
        laravel({
            input: [
                'resources/js/dokter-mobile-app.tsx',
                'resources/js/paramedis-mobile-app.tsx',
                'resources/js/app.js',
                'resources/css/app.css',
            ],
            refresh: true,
            detectTls: false,
            buildDirectory: 'build',
        }),
        // Debug plugin to verify source map generation
        {
            name: 'source-map-debug',
            generateBundle(options, bundle) {
                console.log('🔍 Debug: Analyzing bundle generation...');
                
                Object.keys(bundle).forEach(fileName => {
                    const chunk = bundle[fileName];
                    if (chunk.type === 'chunk' && chunk.map) {
                        console.log(`✅ Source map generated for: ${fileName}`);
                        console.log(`   → Map file: ${fileName}.map`);
                        console.log(`   → Sources: ${chunk.map.sources.length} files`);
                    } else if (chunk.type === 'chunk' && !chunk.map) {
                        console.warn(`⚠️  No source map for: ${fileName}`);
                    }
                });
            }
        }
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
            '@': path.resolve(process.cwd(), 'resources/js'),
        },
    },
    build: {
        // Force source map generation
        sourcemap: 'external',
        // Disable minification for debugging
        minify: false,
        // Keep original names for debugging
        rollupOptions: {
            output: {
                // Simplified naming for debugging
                entryFileNames: `assets/js/[name].js`,
                chunkFileNames: `assets/js/[name].js`,
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name].[ext]`;
                },
                // Explicit source map file naming
                sourcemapFileNames: 'assets/js/[name].js.map',
                // Preserve module structure
                preserveModules: false,
                // Manual chunking for better debugging
                manualChunks: (id) => {
                    if (id.includes('node_modules/react')) {
                        return 'react-vendor';
                    }
                    if (id.includes('node_modules/@radix-ui')) {
                        return 'radix-vendor';
                    }
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                    return undefined;
                },
            },
            // Enhanced debugging warnings
            onwarn(warning, warn) {
                console.log(`🔧 Build warning: ${warning.code} - ${warning.message}`);
                if (warning.code === 'SOURCEMAP_ERROR') {
                    console.error('🚨 Source map error:', warning);
                }
                warn(warning);
            },
        },
        // Debug build metadata
        define: {
            __BUILD_TIME__: JSON.stringify(new Date().toISOString()),
            __BUILD_MODE__: JSON.stringify('DEBUG'),
            __SOURCE_MAP_DEBUG__: JSON.stringify(true),
        },
        // Detailed reporting
        reportCompressedSize: true,
        chunkSizeWarningLimit: 2000,
    },
    // Enhanced logging
    logLevel: 'info',
    clearScreen: false,
});