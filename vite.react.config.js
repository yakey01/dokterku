import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    react({
      jsxRuntime: 'automatic',
      jsxImportSource: 'react',
    })
  ],
  build: {
    outDir: 'public/react-build',
    // Enhanced source map configuration - only in development
    sourcemap: process.env.NODE_ENV === 'development' ? 'inline' : false,
    // Consistent minification
    minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
    // EsBuild configuration for consistency
    esbuild: {
      keepNames: true,
      target: 'esnext',
      sourcemap: process.env.NODE_ENV === 'development',
    },
    rollupOptions: {
      input: {
        'paramedis-dashboard': resolve(__dirname, 'resources/react/paramedis-dashboard/main.jsx'),
        'paramedis-jaspel': resolve(__dirname, 'resources/react/paramedis-jaspel/main.jsx'),
      },
      output: {
        // Add hash for cache busting
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash].[ext]',
        // Generate source map files
        sourcemapFileNames: '[name]-[hash].js.map',
      }
    },
    // Consistent build options
    manifest: true,
    reportCompressedSize: true,
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/react'),
    },
  },
  server: {
    port: 5174,
    host: true,
    // Enhanced HMR configuration
    hmr: {
      port: 5174,
    },
    cors: true,
  }
});