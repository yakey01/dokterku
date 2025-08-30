# Project Cleanup Plan

## 🔍 Analysis Summary

**Project Scale**: Large Laravel application
- 📊 20,630 PHP files | 16,402 JS/TS files | 54 Vue files
- 📁 1.3GB total size
- 🏗️ Complex architecture with multiple panels (Admin, Dokter, Paramedis, Manajer, Bendahara, Petugas)

## 🎯 Priority Cleanup Areas

### 1. Root-Level Test & Debug Files (HIGH PRIORITY)
```
✅ 25 test files in root directory
✅ 5 debug files in root directory  
✅ 31+ documentation markdown files
```

**Impact**: 🔴 Clutter main directory, security risk in production

### 2. Backup & Archive Cleanup (MEDIUM PRIORITY)
```
📁 ./storage/debug-archive/ (38 items)
📁 ./storage/backups/ (multiple directories)
📁 ./backups/ (root level)
📁 23 debug files in storage/debug-archive/
🗃️ 10+ .backup/.bak files scattered
```

**Impact**: 🟡 Storage bloat, potential sensitive data exposure

### 3. Build Artifacts & Logs (LOW PRIORITY)
```
📄 ./build_output.log
📄 ./vite-dev.log  
📄 ./vite.log
📦 37 built JS files in public/build/
```

**Impact**: 🟢 Minor storage usage, safe to clean

### 4. Technical Debt Code Issues (ONGOING)
```
🔍 10 files with TODO/FIXME/HACK comments
📝 5,706 use statements across 1,074 files
```

**Impact**: 🟡 Code maintainability, performance optimization

## 🚀 Recommended Actions

### Phase 1: Safe Cleanup (Immediate)
1. Move root test files → `tests/manual/`
2. Move root debug files → `storage/debug-archive/`
3. Remove build logs & temporary files
4. Consolidate backup directories

### Phase 2: Archive Cleanup (Review First)
1. Archive old debug files (>30 days)
2. Remove redundant backup files
3. Clean storage temporary files

### Phase 3: Code Optimization (Selective)
1. Review TODO/FIXME comments
2. Optimize unused imports
3. Remove duplicate code patterns

## ⚠️ Safety Considerations

**PRESERVE**:
- All production database files
- Configuration files (.env, configs)
- Active documentation (README, deployment guides)
- Current build artifacts

**REVIEW BEFORE REMOVAL**:
- Debug files in storage/debug-archive/
- Backup database files
- Test files with actual test cases

## 📊 Expected Benefits

- 🗂️ **Organization**: Cleaner root directory structure
- 💾 **Storage**: ~50-100MB space savings
- 🔒 **Security**: Remove potential sensitive debug data
- 🧹 **Maintainability**: Easier navigation and development