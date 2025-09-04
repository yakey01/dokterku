# 🏥 Dokterku - Clinic Management System

A comprehensive clinic financial management system built with Laravel 11 and FilamentPHP, featuring mobile-first design and advanced GPS-based attendance tracking.

## ✨ Features

### 🎯 Core Features
- **Multi-Panel Architecture** - Role-based dashboards (Admin, Manajer, Bendahara, Petugas, Paramedis, Dokter)
- **Financial Management** - Complete income/expense tracking with validation workflows
- **GPS-Based Attendance** - Auto-detect location with geofencing (100m radius)
- **Mobile-First Design** - Optimized for Android & iOS devices
- **Real-time Notifications** - Badge indicators and instant updates

### 📱 Mobile Dashboard (Paramedis)
- **🎯 Auto GPS Detection** - Using Leaflet + leaflet-locatecontrol
- **🗺️ Interactive Maps** - OpenStreetMap without API requirements
- **📊 Performance Tracking** - Patients served, procedures, service fees
- **📅 Smart Scheduling** - Today's shifts and weekly overview
- **🔔 Notification System** - Real-time updates with unread badges

### 💰 Financial Management
- **Pendapatan Harian** - Daily income tracking with validation
- **Pengeluaran Harian** - Expense management with approval workflow
- **Jaspel (Service Fees)** - Automatic calculation for medical staff
- **Validation System** - Multi-level approval process

## 🚀 Technology Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: FilamentPHP v3.3, Tailwind CSS 4.0
- **Database**: SQLite (development), MySQL (production)
- **Maps**: Leaflet.js + leaflet-locatecontrol
- **Testing**: Pest Framework
- **Build**: Vite

## 📦 Installation

```bash
# Clone repository
git clone https://github.com/yakey01/klinik-app.git
cd klinik-app

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Start development
composer dev  # Starts server, queue, logs, and vite
```

## 🔧 Development Commands

```bash
# Start all services
composer dev

# Individual commands
php artisan serve          # Development server
npm run dev                # Vite development
php artisan test           # Run tests with Pest
./vendor/bin/pint          # Code formatting
```

## 👥 Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@dokterku.com | admin123 |
| Manajer | manajer@dokterku.com | manajer123 |
| Bendahara | bendahara@dokterku.com | bendahara123 |
| Petugas | petugas@dokterku.com | petugas123 |
| Paramedis | perawat@dokterku.com | perawat123 |
| Dokter | dokter@dokterku.com | dokter123 |

## 🌐 Access Points

- **Admin Panel**: `/admin`
- **Manajer Panel**: `/manajer`
- **Bendahara Panel**: `/bendahara`
- **Petugas Panel**: `/petugas`
- **Paramedis Panel**: `/paramedis` (Mobile-optimized)
- **Dokter Panel**: `/dokter`

## 📱 Mobile Features

### Auto GPS Detection
- Automatic location detection on page load
- Real-time tracking with 100m geofencing
- Accuracy validation with visual feedback
- Fallback to manual detection if needed

### Mobile API Endpoints
```
GET  /api/paramedis/dashboard      # Dashboard summary
GET  /api/paramedis/schedule       # Weekly schedule
POST /api/paramedis/attendance/checkin   # GPS check-in
POST /api/paramedis/attendance/checkout  # GPS check-out
```

## 🔧 Configuration

### GPS Settings (.env)
```env
APP_CLINIC_LATITUDE=-6.2088
APP_CLINIC_LONGITUDE=106.8456
APP_CLINIC_RADIUS=100
```

## 📁 Project Structure

```
app/
├── Filament/
│   ├── Resources/          # Admin resources
│   ├── Paramedis/         # Mobile-first paramedis panel
│   ├── Bendahara/         # Financial management panel
│   └── ...
├── Models/                # Eloquent models
├── Services/              # Business logic services
└── Http/
    ├── Controllers/Api/   # Mobile API endpoints
    └── Middleware/        # Custom middleware

docs/
├── reports/               # System reports & assessments
├── analysis/              # Technical analysis & fixes
└── README.md             # Documentation overview

resources/
├── views/filament/        # Custom Filament views
└── css/                   # Tailwind CSS

database/
├── migrations/            # Database schema
└── seeders/              # Sample data

storage/old-files/
├── json/                 # Archived JSON files (audit/mapping)
└── yaml/                 # Archived YAML files (analysis)
```

### 📁 Documentation Organization

#### `/docs/reports/` - System Reports
- `comprehensive_audit_report.md` - Complete system audit
- `QA_ASSESSMENT_REPORT.md` - Quality assurance assessment  
- `PRODUCTION_SECURITY_CHECKLIST.md` - Security checklist
- `NONPARAMEDIS_TEST_REPORT.md` - Non-paramedis testing report

#### `/docs/analysis/` - Technical Analysis
- `GPS_LOCATION_ANALYSIS_AND_FIXES.md` - GPS implementation analysis
- `GPS_TIMEOUT_FIXED.md` - GPS timeout issue resolution
- `GPS_LOCATION_FIXED.md` - GPS location fixes
- `explore_audit.md` - System exploration audit
- `explore_report.md` - Exploration findings

#### `/storage/old-files/` - Archived Files
- **JSON files**: Resource mappings, audit data, test results
- **YAML files**: Navigation structure, audit summaries

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test
php artisan test tests/Feature/AttendanceTest.php

# Test with coverage
php artisan test --coverage
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
