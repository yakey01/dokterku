# SyntaxError Import Call Debugging Documentation

## Overview
Documentation for resolving SyntaxError: "Unexpected token '{'. import call expects one or two arguments" in React/TypeScript applications using Laravel + Vite.

## Problem Statement

### Initial Error
```
[Error] SyntaxError: Unexpected token '{'. import call expects one or two arguments.
(anonymous function) (dokter-mobile-app-qEa-pZo-.js:1)
```

### Context
- Laravel 8+ with Vite bundler
- React 18+ with TypeScript
- ES modules with dynamic imports
- Mobile-first healthcare application

## Root Cause Analysis

### 1. Hardcoded Bundle Path Issue
**Problem**: Outdated hardcoded fallback bundle path in Blade template
```javascript
// ❌ PROBLEMATIC CODE
const fallbackPath = '/build/assets/js/dokter-mobile-app-qEa-pZo-.js'; // OLD BUNDLE
```

**Impact**: 
- Browser attempts to load non-existent bundle (404)
- JavaScript parser tries to parse HTML 404 response as JavaScript
- Results in "Unexpected token '{'" error

### 2. Manual Bundle Loading Complexity
**Problem**: Complex manual bundle loading logic prone to failures
```javascript
// ❌ PROBLEMATIC APPROACH
function loadBundle() {
    fetch('/build/manifest.json')
        .then(response => response.json())
        .then(manifest => {
            // Complex logic with multiple failure points
        });
}
```

**Issues**:
- Multiple failure points in loading chain
- Inconsistent error handling
- Not aligned with Laravel best practices

### 3. ES Modules Configuration Issues
**Problem**: Vite configuration not explicit about ES modules format
```javascript
// ❌ INCOMPLETE CONFIG
build: {
    rollupOptions: {
        output: {
            // Missing format specification
        }
    }
}
```

## Solution Implementation

### Fix 1: Correct Bundle Path
**File**: `resources/views/mobile/dokter/app.blade.php`
```diff
- const fallbackPath = '/build/assets/js/dokter-mobile-app-qEa-pZo-.js';
+ const fallbackPath = '/build/assets/js/dokter-mobile-app-DRPaNIBb.js';
```

### Fix 2: Use Laravel Vite Helper
**File**: `resources/views/mobile/dokter/app.blade.php`
```diff
- <!-- Complex manual bundle loading -->
- <script>
-     function loadBundle() { /* complex logic */ }
-     loadBundle();
- </script>

+ <!-- Simple proven approach -->
+ @vite(['resources/js/dokter-mobile-app.tsx'])
```

### Fix 3: Enhanced Vite Configuration
**File**: `vite.config.js`
```javascript
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                format: 'es', // ✅ EXPLICIT ES MODULES FORMAT
                entryFileNames: `assets/js/[name]-[hash].js`,
                chunkFileNames: `assets/js/[name]-[hash].js`,
            },
        },
        target: 'es2020', // ✅ Supports dynamic import()
        minify: 'esbuild', // ✅ Better for import statements
        manifest: true,
    },
});
```

### Fix 4: Comprehensive Error Tracking
**File**: `resources/views/mobile/dokter/app.blade.php`
```javascript
// ✅ ENHANCED ERROR MONITORING
window.addEventListener('error', function(e) {
    console.error('🔥 GLOBAL ERROR:', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error
    });
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('🔥 UNHANDLED REJECTION:', {
        reason: e.reason,
        promise: e.promise
    });
});
```

## Error Flow Analysis

### Before Fix (Error Flow)
```
1. Browser loads page
2. Primary bundle loading fails/cached incorrectly
3. Fallback to hardcoded old bundle path
4. 404 - Bundle not found
5. Browser parses HTML error response as JavaScript
6. SyntaxError: Unexpected token '{'
```

### After Fix (Success Flow)
```
1. Browser loads page
2. Vite helper generates correct script tags
3. Bundle loaded with proper ES modules
4. All imports resolved correctly
5. React app initializes successfully
```

## Validation Steps

### 1. Bundle Syntax Validation
```bash
# Check if bundle has valid JavaScript syntax
node -c /path/to/dokter-mobile-app-[hash].js
```

### 2. Import Statements Analysis
```bash
# Analyze import statements in bundle
node -e "
const fs = require('fs');
const content = fs.readFileSync('bundle.js', 'utf8');
const imports = content.match(/import\s*\{[^}]*\}\s*from\s*[\"'][^\"']*[\"']/g);
console.log('Found imports:', imports ? imports.length : 0);
"
```

### 3. Build Process Verification
```bash
npm run build
# Should complete without syntax errors
```

### 4. Runtime Testing
```javascript
// Browser console should show:
console.log('✅ Bundle loaded successfully');
// Without any syntax errors
```

## Best Practices

### 1. Use Laravel Vite Helper
```blade
{{-- ✅ RECOMMENDED --}}
@vite(['resources/js/dokter-mobile-app.tsx'])

{{-- ❌ AVOID --}}
<script src="/build/assets/js/dokter-mobile-app-hardcoded.js"></script>
```

### 2. Explicit Vite Configuration
```javascript
// ✅ EXPLICIT ES MODULES
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                format: 'es', // Always specify format
            },
        },
        target: 'es2020', // Modern target for dynamic imports
        minify: 'esbuild', // Better import handling
    },
});
```

### 3. Proper Error Handling
```javascript
// ✅ COMPREHENSIVE ERROR TRACKING
window.addEventListener('error', errorHandler);
window.addEventListener('unhandledrejection', rejectionHandler);
```

### 4. Avoid Hardcoded Paths
```javascript
// ❌ AVOID
const bundlePath = '/build/assets/js/app-specific-hash.js';

// ✅ USE MANIFEST
fetch('/build/manifest.json')
    .then(response => response.json())
    .then(manifest => {
        const entry = manifest['resources/js/app.tsx'];
        const bundlePath = '/build/' + entry.file;
    });
```

## Common Pitfalls

### 1. Cache Issues
**Problem**: Browser cache serving old bundle references
**Solution**: 
- Use proper cache headers
- Implement cache busting with hashes
- Clear browser cache during development

### 2. Manual Script Loading
**Problem**: Complex manual script tag creation
**Solution**: Use Laravel Vite helper for automatic handling

### 3. Missing ES Modules Support
**Problem**: Bundle not properly configured as ES module
**Solution**: Explicit `format: 'es'` in Vite config

### 4. Import Syntax Issues
**Problem**: Malformed import statements in source
**Solution**: 
- Use TypeScript for compile-time checking
- Enable proper linting rules
- Use `npx tsc --noEmit` for validation

## Debugging Checklist

When encountering similar syntax errors:

### 1. Check Bundle Existence
```bash
# Verify the bundle file actually exists
ls -la public/build/assets/js/
```

### 2. Validate Bundle Syntax
```bash
# Check if JavaScript is valid
node -c public/build/assets/js/your-bundle.js
```

### 3. Inspect Network Requests
- Open browser DevTools → Network tab
- Look for 404 errors on JavaScript files
- Check if correct bundle is being requested

### 4. Review Import Statements
```bash
# Search for malformed imports in source
grep -r "import.*{.*" resources/js/ | grep -v "from"
```

### 5. Verify Vite Configuration
```javascript
// Ensure proper ES modules config
format: 'es',
target: 'es2020',
minify: 'esbuild'
```

### 6. Check Manifest Alignment
```bash
# Ensure bundle name matches manifest
cat public/build/manifest.json | grep "dokter-mobile-app"
```

## Performance Impact

### Before Optimization
- Multiple failed network requests
- JavaScript parsing errors
- Application initialization failures
- Poor user experience

### After Optimization
- Single successful bundle load
- Proper ES modules execution
- Fast application initialization
- Improved error tracking

## Related Files

### Primary Files Modified
- `resources/views/mobile/dokter/app.blade.php` - Bundle loading logic
- `vite.config.js` - Build configuration
- `resources/js/dokter-mobile-app.tsx` - Entry point (validated)

### Configuration Files
- `package.json` - Dependencies and scripts
- `public/build/manifest.json` - Asset manifest (auto-generated)

### Error Tracking
- Browser console logs
- Laravel application logs
- Network request logs

## Conclusion

The SyntaxError was caused by a combination of:
1. Hardcoded outdated bundle paths
2. Complex manual bundle loading logic
3. Incomplete ES modules configuration

The solution involved:
1. Using Laravel Vite helper (proven approach)
2. Explicit ES modules configuration
3. Enhanced error tracking
4. Simplified bundle loading logic

**Key Takeaway**: Always use framework-provided solutions (Laravel Vite helper) over custom implementations for asset loading, and ensure explicit configuration for ES modules support.

## Version Information
- Laravel: 8+
- Vite: 6.3.5
- React: 18+
- TypeScript: Latest
- Node.js: 18+

## Last Updated
August 15, 2025 - Initial documentation after successful resolution