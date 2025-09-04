# Dashboard Sync Documentation

## Overview
This document explains how to sync dashboard changes from local development to production (Hostinger) using GitHub Actions.

## Automatic Sync

### How it works
1. **Automatic Trigger**: Any push to `main` branch that modifies dashboard files will trigger the sync
2. **File Detection**: The workflow monitors these paths:
   - `resources/views/**/dashboard*.blade.php`
   - `app/Http/Controllers/**/*Dashboard*.php`
   - `app/Filament/**/*Dashboard*.php`
   - `resources/react/**dashboard*/**`
   - `resources/css/*dashboard*.css`
   - `public/css/*dashboard*.css`

### Workflow Features
- 🔄 Automatic detection of dashboard changes
- 💾 Backup creation before sync
- 🔨 Asset building (npm run build)
- 📦 Selective file sync (only dashboard files)
- ✅ Post-sync verification
- 📊 Detailed sync reports

## Manual Sync

### Using GitHub Actions UI
1. Go to your repository on GitHub
2. Click on "Actions" tab
3. Select "Sync Dashboard to Production"
4. Click "Run workflow"
5. Choose `sync_all: true` for full sync
6. Click "Run workflow"

### Using GitHub CLI
```bash
# Install GitHub CLI first (if not installed)
brew install gh  # macOS
# or
sudo apt install gh  # Ubuntu

# Login to GitHub
gh auth login

# Trigger full dashboard sync
gh workflow run sync-dashboard.yml -f sync_all=true
```

### Using Local Script
```bash
# Run the sync script
./scripts/sync-dashboard-local.sh

# Options:
# 1) Find all dashboard files
# 2) Sync dashboard changes now
# 3) Watch for changes (auto-sync)
# 4) Trigger full dashboard sync
# 5) Exit
```

## Local Development Workflow

### Basic Workflow
1. Make changes to dashboard files locally
2. Test changes in local environment
3. Commit and push to main branch
4. Workflow automatically syncs to production

### Quick Sync Commands
```bash
# Quick commit and sync
git add .
git commit -m "Update paramedis dashboard"
git push origin main
# Dashboard will sync automatically

# Force full sync
gh workflow run sync-dashboard.yml -f sync_all=true
```

### Watch Mode (Auto-sync on save)
```bash
# Install fswatch first
brew install fswatch  # macOS
# or
sudo apt install fswatch  # Ubuntu

# Run watch mode
./scripts/sync-dashboard-local.sh
# Select option 3
```

## Dashboard File Structure

### Blade Templates
```
resources/views/
├── dashboard.blade.php                 # Main dashboard
├── admin/dashboard.blade.php          # Admin dashboard
├── paramedis/
│   └── dashboards/
│       ├── modern-dashboard.blade.php
│       └── ujicoba-dashboard.blade.php
└── [role]/dashboard.blade.php         # Role-specific dashboards
```

### Controllers
```
app/Http/Controllers/
├── DashboardController.php            # Main controller
├── Admin/AdminDashboardController.php
├── Paramedis/ParamedisDashboardController.php
└── [Role]/[Role]DashboardController.php
```

### Filament Dashboards
```
app/Filament/
├── Pages/EnhancedAdminDashboard.php
├── Manajer/Pages/EnhancedManajerDashboard.php
└── [Role]/Pages/[Role]Dashboard.php
```

## Troubleshooting

### Sync Failed
1. Check GitHub Actions logs
2. Verify file permissions on server
3. Check if assets built successfully
4. Ensure database connection (if needed)

### Dashboard Not Updated
1. Clear Laravel caches:
   ```bash
   php artisan view:clear
   php artisan route:clear
   php artisan config:clear
   ```
2. Check if files were actually changed
3. Verify workflow ran successfully

### Manual Recovery
If automatic sync fails, you can manually sync:
```bash
# SSH to server
ssh user@host

# Navigate to project
cd domains/dokterkuklinik.com/public_html/dokterku

# Pull latest changes
git pull origin main

# Clear caches
php artisan view:clear
php artisan route:clear
php artisan config:clear

# Rebuild caches
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

## Best Practices

1. **Test Locally First**: Always test dashboard changes locally before pushing
2. **Descriptive Commits**: Use clear commit messages for dashboard changes
3. **Backup Important Changes**: The workflow creates automatic backups
4. **Monitor Sync Status**: Check GitHub Actions for sync results
5. **Use Specific Sync**: Only sync what's changed to minimize deployment time

## Security Considerations

1. **Credentials**: Never commit sensitive data in dashboard files
2. **Access Control**: Ensure proper role-based access in controllers
3. **File Permissions**: Workflow sets appropriate permissions automatically
4. **Backup Retention**: Old backups are kept in `~/backups/dashboard/`

## Support

For issues or questions:
1. Check GitHub Actions logs first
2. Review this documentation
3. Check Laravel logs on server
4. Contact development team

---

🤖 Generated automatically
🚀 Automated dashboard sync system