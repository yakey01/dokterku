# Dokterku - Documentation Overview

## Purpose

This documentation provides comprehensive context for AI assistants working on the Dokterku clinic financial management system. It serves as a quick reference for understanding the project's architecture, conventions, and development workflow.

## Documentation Structure

### Core Documentation Files

#### 1. `/CLAUDE.md` (Project Root)
**Primary AI assistant instructions** - Contains project-specific guidance, development commands, and architectural notes for AI Assistant.

**Key Sections:**
- Project overview and technology stack
- Development commands (`composer dev`, testing, etc.)
- FilamentPHP multi-panel architecture
- Database models and relationships
- Default user accounts and roles
- Development best practices and troubleshooting

#### 2. `/docs/ai-context/project-structure.md`
**Detailed project architecture** - Comprehensive breakdown of the codebase structure, models, and relationships.

**Key Sections:**
- Directory structure and file organization
- Multi-panel FilamentPHP architecture
- Core models and database relationships
- Plugin dependencies and integrations
- Development workflow and best practices

#### 3. `/docs/ai-context/docs-overview.md` (This File)
**Documentation navigation guide** - Meta-documentation explaining how to use and navigate the documentation system.

## Quick Reference Guide

### Getting Started
1. **Read `/CLAUDE.md`** for immediate project context
2. **Check default user accounts** for testing different roles
3. **Run `composer dev`** to start all development services
4. **Access panels**: `/admin`, `/petugas`, `/paramedis`, `/bendahara`

### Key Concepts

#### Multi-Panel Architecture
```
/admin     → Full system administration (admin role)
/petugas   → Patient registration & transactions (petugas role)  
/paramedis → Attendance & medical staff features (paramedis role)
/bendahara → Financial validation & approval (bendahara role)
```

#### Core Workflow
```
Patient Registration → Medical Procedures → Service Fee Generation → Financial Validation → Approval
```

#### Technology Stack
```
Laravel 11 → FilamentPHP v3.3 → Pest Testing → Vite + Tailwind → SQLite/MySQL
```

## Common AI Assistant Tasks

### 1. Understanding the Codebase
- **Start with**: `/CLAUDE.md` project overview
- **Dive deeper**: `/docs/ai-context/project-structure.md` for architecture
- **Check models**: `app/Models/` for data relationships
- **Review panels**: `app/Providers/Filament/` for UI organization

### 2. Development Tasks
- **New features**: Follow existing panel structure patterns
- **Testing**: Use Pest framework (`composer test`)
- **Styling**: Stick to FilamentPHP components, avoid custom CSS
- **Database**: Check migrations in `database/migrations/`

### 3. Debugging Issues
- **Cache problems**: Run cache clear commands from `/CLAUDE.md`
- **FilamentPHP issues**: Check plugin compatibility and theme conflicts
- **Permission errors**: Verify role-based access in `User` model
- **Location features**: Review geolocation plugin documentation

### 4. Adding New Components
- **Copy existing patterns** from working panels
- **Use standard FilamentPHP components** (StatsWidget, ChartWidget, etc.)
- **Follow namespace conventions** matching existing structure
- **Clear caches** after creating new components

## File Locations Quick Reference

### Configuration
- **Panel Providers**: `app/Providers/Filament/`
- **User Roles**: `app/Models/User.php:294-301`
- **Database Config**: `.env` file
- **Package Dependencies**: `composer.json`, `package.json`

### UI Components
- **Admin Widgets**: `app/Filament/Widgets/`
- **Petugas Resources**: `app/Filament/Petugas/Resources/`
- **Paramedis Pages**: `app/Filament/Paramedis/Pages/`
- **Bendahara Resources**: `app/Filament/Bendahara/Resources/`

### Data Models
- **Financial**: `Pendapatan`, `Pengeluaran`, `Jaspel`
- **Medical**: `Pasien`, `Tindakan`, `JenisTindakan`
- **HR**: `User`, `CutiPegawai`, `KalenderKerja`

### Testing
- **Feature Tests**: `tests/Feature/`
- **Unit Tests**: `tests/Unit/`
- **Test Configuration**: `tests/Pest.php`

## Development Workflow

### Standard Process
1. **Understand requirement** → Check existing patterns
2. **Plan implementation** → Review similar components
3. **Implement solution** → Follow FilamentPHP conventions
4. **Test functionality** → Use Pest testing framework
5. **Clear caches** → Ensure changes are visible
6. **Verify across panels** → Check role-based access

### Theme and Styling
- **Current themes**: TomatoPHP Simple Theme + Hasnayeen Themes
- **Color schemes**: Blue (admin/petugas), Green (paramedis), Emerald (bendahara)
- **Dark mode**: Supported across all panels
- **Custom CSS**: Avoid - use FilamentPHP components instead

### Best Practices
- **Copy working code** rather than creating from scratch
- **Use exact namespace patterns** from existing components
- **Test with different user roles** to verify access control
- **Clear all caches** after structural changes
- **Stick to standard FilamentPHP patterns** for compatibility

## Troubleshooting Guide

### Common Issues

#### 1. Large Icons/Layout Problems
**Cause**: Custom CSS overrides conflicting with FilamentPHP
**Solution**: Remove custom CSS files, use standard FilamentPHP components

#### 2. Panel Access Denied
**Cause**: Role-based access control in `User::canAccessPanel()`
**Solution**: Verify user roles and panel ID matching

#### 3. Components Not Loading
**Cause**: Cached components or namespace issues
**Solution**: Run `php artisan filament:clear-cached-components`

#### 4. Location Features Not Working
**Cause**: Geolocation plugin configuration or browser permissions
**Solution**: Check plugin setup and HTTPS requirements

### Cache Clear Commands
```bash
php artisan config:clear
php artisan view:clear  
php artisan filament:clear-cached-components
npm run build  # If asset issues
```

## Additional Resources

### External Documentation
- **FilamentPHP Docs**: [filamentphp.com/docs](https://filamentphp.com/docs)
- **Laravel 11 Docs**: [laravel.com/docs/11.x](https://laravel.com/docs/11.x)
- **Pest Testing**: [pestphp.com](https://pestphp.com)
- **Tailwind CSS**: [tailwindcss.com](https://tailwindcss.com)

### Plugin Documentation
- **TomatoPHP Simple Theme**: GitHub repository and FilamentPHP plugins
- **Hasnayeen Themes**: FilamentPHP plugins directory
- **Filament Shield**: bezhansalleh/filament-shield documentation
- **Map Picker**: dotswan/filament-map-picker documentation

## Maintenance Notes

### Regular Tasks
- **Update dependencies** using `composer update` and `npm update`
- **Run tests** before major changes using `composer test`
- **Clear caches** after configuration changes
- **Backup database** before major migrations

### Version Compatibility
- **PHP**: 8.2+ required for Laravel 11
- **Node.js**: Latest LTS for Vite compatibility
- **FilamentPHP**: v3.3 - check plugin compatibility when updating
- **Theme plugins**: Verify compatibility with FilamentPHP version

## Contributing Guidelines

When working on this project:

1. **Read documentation first** - Understand existing patterns
2. **Follow conventions** - Match existing code style and structure  
3. **Test thoroughly** - Use Pest framework and test with different roles
4. **Document changes** - Update relevant documentation files
5. **Clear caches** - Ensure changes are properly applied

This documentation is designed to provide comprehensive context for AI assistants while maintaining clarity and actionable guidance for development tasks.