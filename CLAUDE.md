# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 11 application called "Dokterku" - a clinic financial management system using:
- **PHP 8.2+** with Laravel 11 framework
- **Pest** for testing (instead of PHPUnit)
- **Vite** for frontend asset compilation
- **Tailwind CSS 4.0** for styling
- **SQLite** database for local development

## Development Commands

### Starting Development Environment
```bash
# Start all development services (server, queue, logs, vite)
composer dev
```
This command runs:
- PHP development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen --tries=1`) 
- Log monitoring (`php artisan pail --timeout=0`)
- Vite development server (`npm run dev`)

### Individual Commands
```bash
# Start Laravel development server
php artisan serve

# Start Vite development server
npm run dev

# Build frontend assets for production
npm run build

# Run queue worker
php artisan queue:work

# Monitor logs
php artisan pail
```

### Testing
```bash
# Run all tests using Pest
composer test
# OR
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run specific test by name
php artisan test --filter="example test name"
```

### Code Quality
```bash
# Format code using Laravel Pint
./vendor/bin/pint

# Run code analysis
./vendor/bin/pint --test
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create new seeder
php artisan make:seeder TableSeeder

# Seed specific data
php artisan db:seed --class=WorkLocationSeeder
```

### Filament Maintenance
```bash
# Clear Filament caches after panel changes
php artisan config:clear
php artisan view:clear  
php artisan filament:clear-cached-components

# Upgrade Filament after updates
php artisan filament:upgrade
```

## Architecture Overview

### Multi-Panel FilamentPHP Architecture

This application uses **FilamentPHP v3.3** with role-based panel separation:

#### Panel Structure
- **Admin Panel** (`/admin`) - Complete system management, user management, geofencing
- **Manajer Panel** (`/manajer`) - Management dashboard with analytics and reports
- **Bendahara Panel** (`/bendahara`) - Financial validation, Chart.js dark theme reports
- **Petugas Panel** (`/petugas`) - Patient registration, procedure entry
- **Paramedis Panel** (`/paramedis`) - Mobile-first attendance with GPS integration
- **Dokter Panel** (`/dokter`) - Medical staff interface

#### Panel Provider Locations
- `app/Providers/Filament/[Panel]PanelProvider.php`
- Each panel has dedicated directories: `app/Filament/[Panel]/`

### Core Business Logic

#### Financial Workflow
The system implements a **validation-based financial workflow**:
1. **Data Entry** - Staff input transactions (Pendapatan/Pengeluaran)
2. **Validation Queue** - Transactions require approval (pending → approved/rejected)
3. **Jaspel Generation** - Service fees automatically calculated from validated procedures
4. **Financial Reports** - Real-time dashboards with Chart.js integration

#### Medical Procedure Chain
- **Pasien** (Patients) receive **Tindakan** (Medical Procedures)
- **Tindakan** links **JenisTindakan** (Procedure Types) with **User** (Medical Staff)
- **Jaspel** (Service Fees) automatically generated based on procedure tariffs
- Each procedure can involve: Dokter, Paramedis, and Non-Paramedis staff

### Location & Attendance Architecture

#### WorkLocation Integration
The system uses **admin geofencing** as the single source of truth:
- **Admin Geofencing** (`WorkLocation` model) - Configure clinic locations with GPS coordinates
- **Paramedis Attendance** - Integrates with WorkLocation instead of hardcoded coordinates
- **Multiple Location Support** - Main office, branch office, project sites, mobile locations

#### GPS-Based Attendance Flow
1. **Load WorkLocation** - Get active locations from admin geofencing
2. **Location Detection** - HTML5 Geolocation API with continuous tracking
3. **Distance Validation** - Haversine formula calculation against clinic radius
4. **Attendance Recording** - Store GPS coordinates, device info, accuracy data

#### Map Implementation Strategy
**2024 Updated Architecture**:
- **Admin Geofencing**: Interactive maps using FilamentGoogleMapsPlugin for location setup
- **Mobile Attendance**: Lightweight static maps with HTML5 Geolocation API (HTTPS required)
- **Fallback Strategy**: Placeholder maps when API fails + manual coordinate input for testing

```php
// Admin map integration (interactive)
FilamentGoogleMapsPlugin::make() // in ParamedisPanelProvider  
dotswan/filament-map-picker      // Interactive map fields

// Mobile attendance (lightweight, 2024 optimized)
// Uses static map images + HTML5 Geolocation API
// No heavy JavaScript libraries for mobile performance
```

### Key Models & Relationships

#### Core Financial Models
- **Pendapatan/Pengeluaran** - Income/expense with validation workflow
- **Tindakan** - Medical procedures linking patients to staff
- **Jaspel** - Service fee calculations
- **Role/User** - Role-based access control

#### Location & Attendance Models
- **WorkLocation** - Admin-configurable geofencing locations  
- **Attendance** - GPS-tracked check-in/out records (Paramedis panel)
- **DokterPresensi** - Doctor-specific attendance records (Dokter panel)
- **LocationValidation** - Validation logs for location-based operations
- **GpsSpoofingDetection** - Anti-spoofing security measures
- **UserDevice** - Device fingerprinting for attendance security

#### Communication & Notification Models
- **TelegramSetting** - Telegram bot configuration for notifications
- **SystemConfig** - Global system configuration settings
- **LeaveType** - Leave/absence type definitions

#### Data Precision Features
- **Decimal(15,2)** for all monetary values
- **Soft deletes** on critical tables
- **Audit trails** with input_by/validation_by tracking
- **Comprehensive indexing** for date ranges and lookups

## Default User Accounts

After seeding, these accounts are available:
- **Admin**: admin@dokterku.com / admin123
- **Manajer**: manajer@dokterku.com / manajer123  
- **Bendahara**: bendahara@dokterku.com / bendahara123
- **Petugas**: petugas@dokterku.com / petugas123
- **Dokter**: dokter@dokterku.com / dokter123
- **Paramedis**: perawat@dokterku.com / perawat123
- **Non-Paramedis**: asisten@dokterku.com / asisten123

## Critical Integration Points

### Filament Plugin Dependencies
```php
"cheesegrits/filament-google-maps": "^3.0",     // Google Maps integration (admin geofencing)
"dotswan/filament-map-picker": "^1.8",          // Interactive map fields (admin setup)
"diogogpinto/filament-geolocate-me": "^0.1.1",  // Geolocation components (legacy)
"bezhansalleh/filament-shield": "^3.3",         // Role-based permissions & policies
"leandrocfe/filament-apex-charts": "^3.1",      // Chart widgets for financial reports
"saade/filament-fullcalendar": "^3.2",          // Calendar integration for scheduling
"hasnayeen/themes": "^3.0",                     // Filament theme management
"solution-forest/filament-access-management": "^2.2"  // Advanced access control
```

### Core Laravel Dependencies
```php
"irazasyed/telegram-bot-sdk": "^3.15",          // Telegram notifications
"barryvdh/laravel-dompdf": "^3.1",             // PDF generation for reports
"intervention/image": "^3.11",                 // Image processing
"spatie/laravel-permission": "^6.20"           // Role & permission management
```

### Panel Access Control
The `User` model implements `FilamentUser` with panel-specific access:
```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->role && $this->role->name === $panel->getId();
}
```

### Mobile-First Considerations
- **Paramedis & Dokter panels** optimized for mobile web and Android APK conversion
- **Touch-friendly controls** (44px minimum button size)
- **Progressive disclosure UI** (overview vs action separation)  
- **Lightweight geolocation** (2024): Static maps + HTML5 API instead of heavy libraries
- **HTTPS requirement**: Geolocation API mandatory security requirement
- **Progressive accuracy**: High → Medium → Low accuracy fallback strategies
- **Debug mode**: Manual coordinate input and GPS refresh for troubleshooting

## Development Best Practices

### Filament Panel Development
1. **Copy existing panel structure** - Use admin panel as template
2. **Use standard Filament components** - Avoid custom CSS overrides
3. **Follow namespace patterns** - `App\Filament\[Panel]\Resources`
4. **Clear caches frequently** during development
5. **Test panel access control** with different user roles

### Location System Development
1. **Never hardcode coordinates** - Always use WorkLocation model
2. **Dual architecture approach**:
   - **Admin geofencing**: Use Filament map plugins (interactive)
   - **Mobile attendance**: Use lightweight static maps + HTML5 Geolocation
3. **Server-side validation** - Distance calculations on backend with Haversine formula
4. **HTTPS enforcement** - Check `window.location.protocol === 'https:'` before geolocation
5. **Progressive fallback strategies**:
   - High accuracy (10s timeout) → Medium accuracy (15s) → Low accuracy (60s)
   - Manual coordinate input for testing and troubleshooting
6. **New Livewire methods** - Use `checkinWithLocation()` and `checkoutWithLocation()` instead of old methods

### Financial Workflow Development
1. **Respect validation states** - pending → approved/rejected workflow
2. **Automatic Jaspel generation** - Trigger from Tindakan validation
3. **Audit trail requirements** - Track input_by and validation_by
4. **Decimal precision** - Use decimal(15,2) for monetary values

## Common Issues & Solutions

### Geolocation Issues (2024 Updated)
**Issue**: "tidak berhasil untuk get locationnya" - GPS not working
**Solutions**:
1. **HTTPS Required**: Ensure development/production uses HTTPS (localhost exempted)
2. **Browser permissions**: Check `navigator.permissions.query({name: 'geolocation'})`
3. **Use debug mode**: Manual coordinate input + "🔄 Coba GPS" button for testing
4. **Progressive fallback**: System tries High → Medium → Low accuracy automatically
5. **Check browser console**: Enhanced logging shows detailed geolocation status

### Map Display Problems  
**Issue**: Empty map areas, coordinate mismatch
**Solution**: 
- **Admin maps**: Ensure Filament map plugins are properly configured
- **Mobile maps**: Static maps will fallback to placeholder if API fails
- Always use WorkLocation model, never hardcode coordinates

### Panel Access Errors
**Issue**: 403 Forbidden on panel access
**Solution**: Check `canAccessPanel()` method in User model and ensure user has correct role

### Performance Issues
**Issue**: Slow mobile loading
**Solution**: 
- Use lightweight static maps instead of interactive maps for mobile
- Build assets with `npm run build` after geolocation changes
- Avoid heavy JavaScript libraries on mobile panels

### Cache-Related Errors
**Issue**: Changes not reflecting in Filament panels
**Solution**: Run the full cache clearing sequence for Filament development

### Attendance Data Issues
**Issue**: Location data not reaching backend
**Solution**: Use new Livewire methods `checkinWithLocation()` and `checkoutWithLocation()` with location data parameter