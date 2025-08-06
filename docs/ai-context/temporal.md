# 🚨 Temporal Dead Zone (TDZ) Error - Dokumentasi Perbaikan

## Deskripsi Masalah

**Error**: `ReferenceError: Cannot access uninitialized variable`

**Lokasi**: Production build di `dokter-mobile-app-*.js`

**Tanggal**: 5 Agustus 2025

## Root Cause Analysis

### 1. Penyebab Utama
Temporal Dead Zone (TDZ) terjadi ketika variabel diakses sebelum deklarasi/inisialisasi dalam ES6 modules. Dalam kasus ini:

```typescript
// NavigationIcons.tsx - PROBLEMATIC PATTERN
export const NavigationIcons = {
  Crown: (...) => {...},
  Calendar: (...) => {...},
  // ...
};

// MedicalRPGBottomNav.tsx - ACCESSING BEFORE INIT
const navigationItems = [
  { icon: NavigationIcons.Crown }, // TDZ ERROR!
];
```

### 2. Mengapa Hanya di Production?
- **Development**: Module loading sequential, NavigationIcons initialized first
- **Production**: Vite bundler optimization menyebabkan module initialization order berubah
- **Minification**: Variable hoisting dan dead code elimination memperburuk masalah

## Kronologi Perbaikan

### Attempt #1: Refactor NavigationIcons (FAILED)
```typescript
// Mengubah dari object export ke individual exports
export const Crown = (...) => {...};
export const Calendar = (...) => {...};

// Re-export sebagai namespace
export const NavigationIcons = { Crown, Calendar, Shield, Star, Brain };
```
**Hasil**: Masih error karena circular dependency

### Attempt #2: Getter Function Pattern (FAILED)
```typescript
const getNavigationItems = () => [
  { id: 'dashboard', icon: NavigationIcons.Crown },
  // ...
];
const navigationItems = getNavigationItems();
```
**Hasil**: Masih error karena getter dipanggil saat module evaluation

### Attempt #3: Complete Rewrite (SUCCESS) ✅
```typescript
// HolisticMedicalDashboard.tsx - NO OBJECT EXPORTS
import { Crown, Calendar, Shield, Star, Brain } from 'lucide-react';

const navigationItems = [
  { id: 'dashboard', icon: Crown, label: 'Dashboard' },
  { id: 'attendance', icon: Calendar, label: 'Presensi' },
  { id: 'jaspel', icon: Shield, label: 'Jaspel' },
  { id: 'jadwal', icon: Star, label: 'Jadwal' },
  { id: 'profile', icon: Brain, label: 'Profil' }
];
```

## Langkah-Langkah Perbaikan

### 1. Buat Component Baru
```bash
# Tulis ulang component tanpa complex object exports
resources/js/components/dokter/HolisticMedicalDashboard.tsx
```

### 2. Update Entry Point
```typescript
// dokter-mobile-app.tsx
import HolisticMedicalDashboard from './components/dokter/HolisticMedicalDashboard';

// Render component baru
<HolisticMedicalDashboard />
```

### 3. Backup File Lama
```bash
mv App.tsx App.tsx.backup
mv MedicalRPGBottomNav.tsx MedicalRPGBottomNav.tsx.backup
mv NavigationIcons.tsx NavigationIcons.tsx.backup
```

### 4. Clear Cache & Rebuild
```bash
rm -rf public/build/
rm -rf node_modules/.vite/
rm public/hot 2>/dev/null || true
npm run build
```

### 5. Verifikasi Browser
- Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
- Atau gunakan Incognito/Private mode
- Pastikan load file JS baru (bukan cache lama)

## Best Practices untuk Menghindari TDZ

### 1. ❌ HINDARI Pattern Ini:
```typescript
// Complex object exports
export const Icons = {
  Icon1: Component1,
  Icon2: Component2
};

// Circular dependencies
import { A } from './a';
export const B = { a: A };

// Module-level side effects
const items = buildItems(); // Called during module init
```

### 2. ✅ GUNAKAN Pattern Ini:
```typescript
// Individual exports
export const Icon1 = Component1;
export const Icon2 = Component2;

// Direct imports
import { Icon1, Icon2 } from 'library';

// Lazy initialization
const getItems = () => buildItems();
```

### 3. Vite-Specific Tips:
- Test production build secara rutin: `npm run build`
- Gunakan `vite preview` untuk test production locally
- Perhatikan warning tentang circular dependencies
- Hindari complex re-exports di barrel files

## Testing Checklist

- [ ] Development mode berjalan normal
- [ ] Production build berhasil tanpa error
- [ ] Tidak ada TDZ error di browser console
- [ ] Bottom navigation berfungsi dengan baik
- [ ] Semua icon tampil dengan benar
- [ ] Performance tidak terpengaruh

## Error Patterns untuk Monitoring

Jika melihat error berikut, kemungkinan TDZ:
- `Cannot access 'X' before initialization`
- `Cannot access uninitialized variable`
- `ReferenceError` di minified code dengan pattern seperti `C — file.js:1:7787`

## Kesimpulan

TDZ adalah masalah subtle yang sering muncul hanya di production build. Solusi terbaik adalah:
1. Hindari complex object exports
2. Gunakan direct imports
3. Test production build secara rutin
4. Monitor browser console untuk early detection

**Status**: RESOLVED ✅
**Build Hash**: Changed from `Dqba2vuv` to `B2nicDon`