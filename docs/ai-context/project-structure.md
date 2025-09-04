# Dokterku - Project Structure Documentation

## Overview

Dokterku is a Laravel 11 clinic financial management system built with FilamentPHP v3.3, featuring multi-panel architecture for role-based access control and comprehensive financial workflow management.

## Core Technology Stack

- **Framework**: Laravel 11.x
- **Admin Panel**: FilamentPHP v3.3 (Multi-panel architecture)
- **Frontend**: Vite + Tailwind CSS 4.0
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Testing**: Pest Framework
- **Authentication**: Laravel Sanctum + Spatie Laravel Permission
- **Themes**: TomatoPHP Simple Theme + Hasnayeen Themes

## Directory Structure

```
Dokterku/
├── app/
│   ├── Filament/                    # FilamentPHP Components
│   │   ├── Resources/              # Admin panel resources
│   │   ├── Widgets/                # Admin panel widgets
│   │   ├── Bendahara/              # Bendahara (Treasurer) panel
│   │   │   ├── Resources/
│   │   │   └── Widgets/
│   │   ├── Paramedis/              # Paramedis (Medical staff) panel
│   │   │   ├── Pages/
│   │   │   ├── Resources/
│   │   │   └── Widgets/
│   │   └── Petugas/                # Petugas (Administrative staff) panel
│   │       ├── Resources/
│   │       └── Widgets/
│   ├── Http/Controllers/           # HTTP Controllers
│   ├── Models/                     # Eloquent Models
│   │   ├── User.php               # User model with role-based panel access
│   │   ├── Pasien.php             # Patient records
│   │   ├── Tindakan.php           # Medical procedures
│   │   ├── JenisTindakan.php      # Procedure types with fee structures
│   │   ├── Jaspel.php             # Service fees
│   │   ├── Pendapatan.php         # Income transactions
│   │   ├── Pengeluaran.php        # Expense transactions
│   │   ├── CutiPegawai.php        # Employee leave management
│   │   └── KalenderKerja.php      # Work calendar
│   └── Providers/
│       └── Filament/              # Filament Panel Providers
│           ├── AdminPanelProvider.php
│           ├── BendaharaPanelProvider.php
│           ├── ParamedisPanelProvider.php
│           └── PetugasPanelProvider.php
├── database/
│   ├── migrations/                # Database migrations
│   ├── seeders/                   # Data seeders
│   └── database.sqlite           # SQLite database file
├── docs/
│   └── ai-context/               # AI Assistant documentation
│       ├── project-structure.md  # This file
│       └── docs-overview.md      # Documentation overview
├── resources/
│   ├── css/                      # Styling assets
│   ├── js/                       # JavaScript assets
│   └── views/                    # Blade templates
├── tests/                        # Pest testing framework
│   ├── Feature/                  # Feature tests
│   └── Unit/                     # Unit tests
├── CLAUDE.md                     # AI Assistant project instructions
└── package.json                  # Node.js dependencies & scripts
```

## Multi-Panel Architecture

### Panel Access Control

Each panel is accessed through role-based authentication:

```php
// User model - app/Models/User.php:294-301
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'admin') return $this->hasRole('admin');
    if ($panel->getId() === 'petugas') return $this->hasRole('petugas');
    if ($panel->getId() === 'paramedis') return $this->hasRole('paramedis');
    if ($panel->getId() === 'bendahara') return $this->hasRole('bendahara');
    return false;
}
```

### Panel Configurations

#### 1. Admin Panel (`/admin`)
- **Provider**: `app/Providers/Filament/AdminPanelProvider.php`
- **Theme**: Blue primary color + TomatoPHP Simple Theme + Hasnayeen Themes
- **Features**: Full system administration, user management, comprehensive financial oversight

#### 2. Petugas Panel (`/petugas`)  
- **Provider**: `app/Providers/Filament/PetugasPanelProvider.php`
- **Theme**: Blue primary color + TomatoPHP Simple Theme + Hasnayeen Themes
- **Features**: Patient registration, daily transactions, medical procedure logging

#### 3. Paramedis Panel (`/paramedis`)
- **Provider**: `app/Providers/Filament/ParamedisPanelProvider.php`
- **Theme**: Green primary color + TomatoPHP Simple Theme
- **Features**: Attendance tracking, location-based check-in/out, Jaspel management

#### 4. Bendahara Panel (`/bendahara`)
- **Provider**: `app/Providers/Filament/BendaharaPanelProvider.php`
- **Theme**: Emerald primary color + TomatoPHP Simple Theme + Hasnayeen Themes
- **Features**: Financial validation workflow, revenue tracking, expense approval

## Core Models & Relationships

### Financial Models
- **Pendapatan**: Income transactions with validation workflow
- **Pengeluaran**: Expense transactions with approval process
- **Tindakan**: Medical procedures that generate service fees
- **Jaspel**: Service fees automatically calculated from procedures

### Medical Models
- **Pasien**: Patient records with medical record numbers
- **JenisTindakan**: Procedure types with defined tariffs and fee structures
- **User**: Medical staff with role-based permissions

### HR Models
- **CutiPegawai**: Employee leave management
- **KalenderKerja**: Work calendar and scheduling

## Key Features

### 1. Financial Validation Workflow
All financial transactions require validation:
- **Pending** → **Approved/Rejected** status flow
- Role-based approval permissions
- Audit trails with input_by and validation_by fields

### 2. Location-Based Attendance
Paramedis panel includes sophisticated location tracking:
- **GPS integration** via filament-geolocate-me plugin
- **Interactive maps** via filament-map-picker with OpenStreetMap
- **Distance validation** for clinic proximity
- **Real-time clock** with Jakarta timezone

### 3. Multi-Theme Support
- **TomatoPHP Simple Theme**: Modern interface with dark mode
- **Hasnayeen Themes**: Multiple color schemes and customization options
- **Per-panel theming**: Different color schemes for each role

## Database Design

### Key Features
- **Soft deletes** on critical tables (users, patients, procedures)
- **Comprehensive indexing** for performance optimization
- **Audit trails** throughout the system
- **Financial precision** using decimal(15,2) for monetary values
- **Role-based permissions** stored as JSON arrays

### Default User Accounts
```
admin@dokterku.com / admin123
manajer@dokterku.com / manajer123  
bendahara@dokterku.com / bendahara123
petugas@dokterku.com / petugas123
dokter@dokterku.com / dokter123
perawat@dokterku.com / perawat123
asisten@dokterku.com / asisten123
```

## Development Commands

### Quick Start
```bash
composer dev  # Starts all services: server, queue, logs, vite
```

### Individual Services
```bash
php artisan serve        # Laravel development server
npm run dev             # Vite development server
php artisan queue:work  # Queue worker
php artisan pail        # Log monitoring
```

### Testing
```bash
composer test           # Run Pest tests
php artisan test        # Alternative test command
```

### Code Quality
```bash
./vendor/bin/pint       # Laravel Pint code formatting
./vendor/bin/pint --test # Code analysis
```

### Cache Management
```bash
php artisan config:clear
php artisan view:clear
php artisan filament:clear-cached-components
```

## Plugin Dependencies

### FilamentPHP Plugins
- **bezhansalleh/filament-shield** (v3.3): Role-based permissions
- **diogogpinto/filament-geolocate-me** (v0.1.1): GPS location detection
- **dotswan/filament-map-picker** (v1.8): Interactive maps with OpenStreetMap
- **hasnayeen/themes** (v1.0): Multi-theme support
- **tomatophp/filament-simple-theme** (v1.0.2): Modern UI theme

### Core Dependencies
- **spatie/laravel-permission** (v6.20): Permission management backend
- **laravel/sanctum**: API authentication
- **pestphp/pest**: Testing framework
- **barryvdh/laravel-dompdf**: PDF generation

## Project Memory & Context

- **Started**: February 2024 as solo project
- **Goal**: Streamline clinic financial management workflows
- **Focus**: Role-based access control and financial transparency
- **Architecture**: Multi-panel FilamentPHP for different user roles
- **Key Innovation**: Location-based attendance with distance validation

## Development Best Practices

### FilamentPHP Guidelines
1. **Copy structure from working panels** when creating new components
2. **Use standard Filament components** for consistency
3. **Follow exact namespace patterns** matching existing panels
4. **Clear caches after changes** to prevent stale component issues
5. **Avoid custom CSS overrides** that conflict with theme systems

### Code Standards
- **Pest testing** instead of PHPUnit
- **Laravel Pint** for code formatting
- **Soft deletes** for data integrity
- **Comprehensive indexing** for performance
- **Role-based access control** at model and panel levels