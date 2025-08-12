# Dokterku Healthcare Management System

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-blue.svg)](https://reactjs.org)
[![Filament](https://img.shields.io/badge/Filament-3.x-orange.svg)](https://filamentphp.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Dokterku is a comprehensive healthcare management system designed for modern medical facilities. It provides role-based dashboards, attendance tracking, medical procedure management, and incentive payment calculations.

## 🏥 Features

### Multi-Role Dashboard System
- **Admin Panel**: Complete system management and oversight
- **Doctor Dashboard**: Patient management and medical procedures
- **Paramedic Dashboard**: Attendance tracking and procedure assistance
- **Staff Dashboard**: Financial and operational data entry
- **Manager Dashboard**: Analytics and performance monitoring
- **Treasurer Dashboard**: Financial validation and payment processing

### Core Functionality
- **Attendance System**: GPS-based check-in/out with location validation
- **Medical Procedures (Tindakan)**: Comprehensive procedure tracking and billing
- **Incentive Payments (Jaspel)**: Automated calculation and validation
- **Financial Management**: Revenue and expense tracking
- **Reporting System**: Dynamic reports with filtering capabilities
- **Mobile-First Design**: Responsive UI optimized for mobile devices

## 🛠️ Technology Stack

### Backend
- **Laravel 11.x**: PHP framework with robust API capabilities
- **Filament 3.x**: Modern admin panel builder
- **MySQL/MariaDB**: Primary database
- **Laravel Sanctum**: API authentication

### Frontend
- **React 18.x**: Component-based UI library
- **TypeScript**: Type-safe JavaScript
- **Tailwind CSS**: Utility-first CSS framework
- **Framer Motion**: Animation library
- **Lucide React**: Icon library

### Development Tools
- **Vite**: Fast build tool and development server
- **Git Worktrees**: Parallel development workflow
- **Laravel Sail**: Docker development environment

## 🚀 Quick Start

### Prerequisites
- PHP 8.3 or higher (required for Filament 3.x dependencies)
- Node.js 18.x or higher
- Composer
- Git

### Installation
1. **Clone the repository**
   ```bash
   git clone https://github.com/yakey01/klinik-app.git
   cd klinik-app
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate --seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

## 📁 Project Structure

```
dokterku/
├── app/
│   ├── Filament/                    # Admin panel resources
│   │   ├── Admin/                   # Admin-specific panels
│   │   ├── Paramedis/              # Paramedic panels
│   │   ├── Petugas/                # Staff panels
│   │   └── Resources/              # Shared resources
│   ├── Http/Controllers/           # API and web controllers
│   │   ├── Api/V2/                 # API version 2
│   │   └── Admin/                  # Admin controllers
│   ├── Models/                     # Eloquent models
│   └── Services/                   # Business logic services
├── resources/
│   ├── js/components/              # React components
│   │   ├── paramedis/             # Paramedic dashboard
│   │   ├── dokter/                # Doctor dashboard
│   │   └── ui/                    # Shared UI components
│   └── views/                     # Blade templates
├── docs/                          # Documentation
├── scripts/                       # Helper scripts
└── storage/
    └── backups/                   # System backups
```

## 🔄 Git Worktree Workflow

This project uses Git worktrees for parallel development:

```bash
# List worktrees
./scripts/worktree-helper.sh list

# Create feature worktree
./scripts/worktree-helper.sh feature new-feature

# Switch between worktrees
./scripts/worktree-helper.sh switch develop
```

### Worktree Structure
- `Dokterku/` - Main production branch
- `Dokterku-develop/` - Development integration
- `Dokterku-staging/` - Pre-production testing
- `Dokterku-hotfix/` - Emergency fixes
- `Dokterku-feature-*/` - Feature development

📖 [Complete Worktree Workflow Documentation](docs/WORKTREE_WORKFLOW.md)

## 🎯 Development Guidelines

### Code Standards
- Follow Laravel coding standards
- Use TypeScript for all React components
- Implement mobile-first responsive design
- Maintain comprehensive test coverage

### Security
- Never commit sensitive credentials
- Use Laravel's built-in security features
- Implement proper input validation
- Follow healthcare data protection standards

### Performance
- Optimize database queries
- Use Laravel caching where appropriate
- Minimize JavaScript bundle size
- Implement lazy loading for large datasets

## 🧪 Testing

```bash
# Run PHP tests
php artisan test

# Run JavaScript tests
npm test

# Run full test suite
npm run test:full
```

## 📊 API Documentation

### Authentication
All API endpoints require authentication via Laravel Sanctum:

```bash
# Login
POST /api/login
Content-Type: application/json
{
    "email": "user@example.com",
    "password": "password"
}
```

### Key Endpoints
- `GET /api/v2/dashboards/paramedis/` - Paramedic dashboard data
- `GET /api/v2/dashboards/paramedis/presensi` - Attendance data
- `POST /api/v2/attendance/checkin` - Check-in endpoint
- `GET /api/v2/dashboards/dokter/` - Doctor dashboard data

## 🔧 Configuration

### Environment Variables
Key environment variables for configuration:

```env
APP_NAME=Dokterku
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dokterku
DB_USERNAME=root
DB_PASSWORD=

# Add your specific configurations
```

### Feature Flags
The system supports feature flags for gradual rollouts:

```php
// Enable new attendance features
'attendance_v2' => env('FEATURE_ATTENDANCE_V2', false),

// Enable mobile notifications
'mobile_notifications' => env('FEATURE_MOBILE_NOTIFICATIONS', true),
```

## 🚀 Deployment

### Production Checklist
1. Run `php artisan config:cache`
2. Run `php artisan route:cache`
3. Run `php artisan view:cache`
4. Set `APP_DEBUG=false`
5. Configure proper file permissions
6. Set up SSL certificates
7. Configure database backups

### Docker Deployment
```bash
# Using Laravel Sail
./vendor/bin/sail up -d

# Or custom Docker setup
docker-compose up -d
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Pull Request Guidelines
- Ensure all tests pass
- Update documentation as needed
- Follow conventional commit messages
- Include screenshots for UI changes

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Worktree Workflow](docs/WORKTREE_WORKFLOW.md)
- [API Documentation](docs/API.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

### Getting Help
- Create an issue for bugs or feature requests
- Check existing documentation
- Review commit history for recent changes

## 🙏 Acknowledgments

- Laravel community for the excellent framework
- Filament team for the admin panel builder
- React team for the UI library
- Healthcare professionals who provided domain expertise

---

**Dokterku** - Modern Healthcare Management System
Built with ❤️ for healthcare professionals