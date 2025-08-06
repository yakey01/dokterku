# VITE.md - Vite Development Server & Asset Pipeline Documentation

*Panduan lengkap mengatasi masalah Vite server dan pipeline asset untuk Dokterku*

## 🚀 Overview

Dokterku menggunakan Vite sebagai build tool untuk mengkompilasi asset React/TypeScript. Dokumentasi ini mencakup penyelesaian masalah umum yang terjadi selama development dan production.

## ⚙️ Konfigurasi Vite

### Vite Config Utama (`vite.config.js`)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/dokter-mobile-app.tsx',
                'resources/js/paramedis-mobile-app.tsx'
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost'
        }
    },
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true
    }
});
```

### React Config (`vite.react.config.js`)
```javascript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'public/react-build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                'dokter-mobile-app': 'resources/js/dokter-mobile-app.tsx',
                'paramedis-mobile-app': 'resources/js/paramedis-mobile-app.tsx'
            }
        }
    }
});
```

## 🔧 Masalah Umum & Solusi

### 1. Vite Dev Server Error: "Not Running and No Production Build Found"

**Gejala:**
```
[Error] Vite dev server is not running and no production build found
```

**Penyebab:**
- Vite dev server tidak berjalan
- Build production tidak ada atau rusak
- Laravel mencari asset yang tidak tersedia

**Solusi:**

#### A. Pastikan Build Production Tersedia
```bash
# Build asset untuk production
npm run build

# Verifikasi build berhasil
ls -la public/build/
cat public/build/manifest.json
```

#### B. Gunakan @vite Directive yang Benar
```php
{{-- Blade template yang BENAR --}}
@vite(['resources/js/dokter-mobile-app.tsx'])

{{-- JANGAN gunakan custom vite-fallback --}}
{{-- <x-vite-fallback entry="dokter-mobile-app" /> --}}
```

#### C. Restart Vite Dev Server (Development Only)
```bash
# Stop existing server
pkill -f vite

# Start fresh server
npm run dev
```

### 2. 404 Resource Loading Errors ⭐ **UPDATED - 2025 PRODUCTION BUILD FIX**

**Gejala:**
```
[Error] Failed to load resource: the server responded with a status of 404 (Not Found)
- app.css (client, line 0)
- app.js (client, line 0)
- dokter-mobile-app.tsx (client, line 0)
- favicon.ico
```

**🔍 Root Cause Analysis (Agustus 2025):**
- **Hot File Issue**: File `public/hot` ada tanpa Vite dev server yang berjalan
- Laravel mencoba load dari `http://localhost:5173` (dev server) padahal dalam production mode
- Browser mencari `/app.css` dan `/app.js` langsung, bukan versi yang di-hash dari build
- Konflik antara `APP_ENV=production` dengan development artifacts

**✅ SOLUSI TERBUKTI BERHASIL:**

#### A. Remove Hot File & Clear Cache (PRIMARY FIX - August 2025)
```bash
# 1. Remove hot file yang menyebabkan Laravel cari dev server
rm public/hot

# 2. Clear semua Laravel cache
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# 3. Verifikasi hot file sudah tidak ada
ls -la public/hot || echo "Hot file removed successfully"

# 4. Test akses aplikasi - error 404 harusnya hilang
```

#### B. Complete Clean & Rebuild (jika masih error)
```bash
# 1. Kill semua Vite dev server yang konflik
pkill -f vite

# 2. Hapus semua cache (CRITICAL STEP)
rm -rf public/build/
rm -rf node_modules/.vite/
rm public/hot  # PENTING!

# 3. Force production build
npm run build

# 4. Verifikasi build berhasil
ls -la public/build/assets/
cat public/build/manifest.json | grep app.css
```

#### B. Browser Cache Resolution (WAJIB)
```bash
# CRITICAL: Clear browser cache COMPLETELY
# Chrome: Ctrl+Shift+Delete -> Clear everything
# Firefox: Ctrl+Shift+Delete -> Clear everything

# ATAU gunakan Incognito/Private browsing mode
# untuk testing tanpa cache interference
```

#### C. Correct Authentication Workflow (CRITICAL)
```bash
# ⚠️ PENTING: SELALU login dulu sebelum akses mobile app

# 1. Login terlebih dahulu
http://localhost:8000/login
Email: 3333@dokter.local
Password: password

# 2. BARU navigate ke mobile app
http://localhost:8000/dokter/mobile-app

# ❌ JANGAN langsung akses /dokter/mobile-app tanpa login
# Ini akan menyebabkan redirect dan asset loading context error
```

#### D. Success Verification (2024 Tested)
```javascript
// Browser console harus menampilkan pesan berikut:
// ✅ Success indicators:
🎯 DOKTERKU: Theme initialized safely with Alpine.js protection
✅ DOKTERKU Mobile App: Successfully initialized React root

// ❌ Jika masih error, ulangi Complete Clean & Rebuild
```

#### E. Emergency Diagnostic Tool
```bash
# Jalankan diagnostic script untuk analisis mendalam
php debug-404-errors.php

# Atau jalankan comprehensive test
php test-dokter-mobile-access.php
```

#### F. Ganti External Fonts dengan System Fonts
```css
/* tailwind.config.js - BEFORE (bermasalah) */
fontFamily: {
  sans: ['Figtree', 'sans-serif'],
},

/* tailwind.config.js - AFTER (aman) */
fontFamily: {
  sans: [...defaultTheme.fontFamily.sans],
},
```

#### C. Pastikan Entry Point Benar
```typescript
// resources/js/dokter-mobile-app.tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/dokter/App';
import '../css/app.css'; // PENTING: Import CSS untuk bundling

// Render aplikasi
const container = document.getElementById('dokter-app');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
```

### 3. CSS Styling Tidak Terapply

**Gejala:**
- Gaming theme tampil sebagai text biasa
- Purple gradient tidak muncul
- Tailwind classes tidak bekerja

**Penyebab:**
- CSS tidak ter-bundle dengan JavaScript
- Import CSS missing di entry point

**Solusi:**

#### A. Import CSS di Entry Point
```typescript
// resources/js/dokter-mobile-app.tsx
import '../css/app.css'; // WAJIB ada
```

#### B. Pastikan CSS Config Benar
```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom gaming styles */
.gaming-gradient {
    background: linear-gradient(135deg, #0f172a 0%, #581c87 50%, #0f172a 100%);
}
```

#### C. Force Production Mode jika Perlu
```bash
# Set environment ke production
export NODE_ENV=production
npm run build
```

### 4. TypeScript Build Errors

**Gejala:**
```
ERROR: Unterminated regular expression
Transform failed with 1 error
```

**Penyebab:**
- Syntax error dalam JSX/TSX
- Missing closing tags
- Invalid characters

**Solusi:**

#### A. Validasi Syntax
```bash
# Check TypeScript syntax
npx tsc --noEmit --skipLibCheck resources/js/components/dokter/Dashboard.tsx
```

#### B. Rebuild File jika Korup
```bash
# Backup file bermasalah
cp Dashboard.tsx Dashboard.tsx.backup

# Tulis ulang dengan clean syntax
# (See implementation in previous conversation)
```

## 🔄 Workflow Development

### Development Mode
```bash
# 1. Start Laravel server
php artisan serve

# 2. Start Vite dev server (terminal baru)
npm run dev

# 3. Akses aplikasi
# http://localhost:8000/dokter/mobile-app
```

### Production Build
```bash
# 1. Build semua asset
npm run build

# 2. Verifikasi output
ls -la public/build/assets/

# 3. Test production
php artisan serve
```

### Debugging Asset Loading
```bash
# Check manifest content
cat public/build/manifest.json

# Verify file permissions
chmod -R 755 public/build/

# Check Laravel routes
php artisan route:list | grep dokter
```

## 📁 Struktur File Asset

```
public/build/
├── manifest.json                           # Asset mapping
└── assets/
    ├── css/
    │   ├── app-[hash].css                  # Main CSS bundle
    │   └── theme-[hash].css                # Panel themes
    └── js/
        ├── dokter-mobile-app-[hash].js     # Dokter React app
        ├── paramedis-mobile-app-[hash].js  # Paramedis React app
        └── label-[hash].js                 # Shared dependencies

resources/
├── css/
│   ├── app.css                             # Main Tailwind CSS
│   └── filament/                           # Panel-specific CSS
├── js/
│   ├── dokter-mobile-app.tsx               # Dokter entry point
│   ├── paramedis-mobile-app.tsx            # Paramedis entry point
│   └── components/
│       ├── dokter/                         # Dokter React components
│       └── paramedis/                      # Paramedis React components
└── views/
    └── mobile/
        ├── dokter/app.blade.php            # Dokter Blade template
        └── paramedis/app.blade.php         # Paramedis Blade template
```

## 🛡️ Error Prevention

### 1. Consistent Asset References
```php
{{-- SELALU gunakan @vite directive --}}
@vite(['resources/js/dokter-mobile-app.tsx'])

{{-- JANGAN gunakan hardcoded paths --}}
{{-- <script src="/build/assets/dokter-mobile-app.js"></script> --}}
```

### 2. Proper CSS Imports
```typescript
// SELALU import CSS di entry point
import '../css/app.css';

// JANGAN rely pada external CDN
// import 'https://fonts.googleapis.com/...'
```

### 3. Clean Build Process
```bash
# SELALU clear cache sebelum build
rm -rf public/build/
npm run build
```

## 🔍 Troubleshooting Checklist

### Ketika Aplikasi Tidak Load:
- [ ] Cek `public/build/manifest.json` ada dan valid
- [ ] Pastikan `@vite` directive benar di Blade template
- [ ] Verifikasi entry point import CSS
- [ ] Restart Vite dev server jika development mode
- [ ] Clear browser cache dan cookies

### Ketika Styling Tidak Muncul:
- [ ] Import CSS di entry point TypeScript
- [ ] Build ulang dengan `npm run build`
- [ ] Cek console browser untuk error CSS loading
- [ ] Pastikan Tailwind config tidak ada external dependencies

### Ketika Build Gagal:
- [ ] Cek syntax TypeScript dengan `npx tsc --noEmit`
- [ ] Hapus `node_modules/.vite/` cache directory
- [ ] Update dependencies dengan `npm install`
- [ ] Cek error log di terminal untuk detail

## 📚 Commands Reference

```bash
# Development
npm run dev                    # Start Vite dev server
npm run build                 # Build production assets
npm run react-dev             # Build React components only

# Debugging
npx tsc --noEmit              # Check TypeScript syntax
ls -la public/build/          # List built assets
cat public/build/manifest.json # View asset manifest

# Clean slate
rm -rf public/build/          # Remove build cache
rm -rf node_modules/.vite/    # Remove Vite cache
npm install                   # Reinstall dependencies
```

## 🚨 Emergency Recovery

Jika semua gagal dan aplikasi tidak bisa load:

```bash
# 1. Total clean
rm -rf public/build/
rm -rf node_modules/.vite/
rm -rf node_modules/

# 2. Fresh install
npm install

# 3. Force production build
NODE_ENV=production npm run build

# 4. Verify Laravel can serve assets
php artisan serve
curl -I http://localhost:8000/build/manifest.json

# 5. Test aplikasi
open http://localhost:8000/dokter/mobile-app
```

---

### 5. Migrasi dari Tailwind CDN ke Production Build ⭐ **NEW - August 2025**

**Gejala:**
```
[Warning] cdn.tailwindcss.com should not be used in production. 
To use Tailwind CSS in production, install it as a PostCSS plugin or use the Tailwind CLI
```

**Penyebab:**
- Blade templates menggunakan `<script src="https://cdn.tailwindcss.com"></script>`
- CDN tidak optimal untuk production (performance, reliability, customization)

**Solusi Lengkap:**

#### A. Update Semua Blade Templates
```bash
# 1. Cari semua file yang menggunakan CDN
grep -rl "cdn.tailwindcss.com" resources/views/

# 2. Ganti dengan Vite directive
# BEFORE:
<script src="https://cdn.tailwindcss.com"></script>

# AFTER:
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

#### B. Files yang Diupdate (August 2025)
- **Auth Views**: unified-login, forgot-password, reset-password, login
- **Petugas Views**: 14 files dalam enhanced directory
- **Paramedis Views**: welcome, jaspel, jadwal-jaga, dashboards, presensi
- **Static HTML**: Debug files dibiarkan pakai CDN (bukan production)

#### C. Verifikasi Tailwind Build
```bash
# 1. Build production assets
npm run build

# 2. Cek output CSS berisi Tailwind utilities
head -50 public/build/assets/css/app-*.css | grep -E "(tw-|bg-|text-)"

# 3. Pastikan manifest.json valid
cat public/build/manifest.json | jq '.["resources/css/app.css"]'
```

#### D. Configuration Files
```javascript
// tailwind.config.js - pastikan content paths benar
content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './app/Filament/**/*.php',
    './vendor/filament/**/*.blade.php',
    './resources/js/**/*.{js,jsx,ts,tsx}',
],

// postcss.config.js
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

## 📋 Troubleshooting Checklist Lengkap

### Pre-flight Checks
- [ ] `APP_ENV` sesuai dengan environment (local/production)
- [ ] Tidak ada `public/hot` file di production
- [ ] `npm run build` sudah dijalankan untuk production
- [ ] Tidak ada referensi CDN di blade templates production

### Ketika Error 404 Assets:
- [ ] Cek dan hapus `public/hot` file
- [ ] Clear semua Laravel cache (`config:clear`, `view:clear`, `cache:clear`)
- [ ] Verifikasi `public/build/manifest.json` exists
- [ ] Pastikan @vite directive di blade templates
- [ ] Browser hard refresh (Ctrl+Shift+R)

### Ketika Build Gagal:
- [ ] Update dependencies: `npm install`
- [ ] Clean build: `rm -rf public/build && npm run build`
- [ ] Check Node version compatibility
- [ ] Lihat error detail di terminal

### Development vs Production:
- [ ] Dev: `npm run dev` + `php artisan serve`
- [ ] Prod: `npm run build` + no hot file + proper .env

*Dokumentasi ini dibuat berdasarkan pengalaman troubleshooting Vite server issues di Dokterku - Updated August 2025*