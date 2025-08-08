# Filament Resources Guidelines

## Overview
This directory contains all Filament admin panel resources organized by user roles.

## Panel Structure
- `/Admin` - Super admin panel (full system access)
- `/Bendahara` - Treasury/finance panel
- `/Dokter` - Doctor panel
- `/Manajer` - Manager panel
- `/Paramedis` - Paramedic panel
- `/Petugas` - Staff/clerk panel
- `/Verifikator` - Verification panel

## Resource Requirements

### MANDATORY Methods for Navigation Visibility
Every resource MUST implement these methods to appear in sidebar:
```php
public static function shouldRegisterNavigation(): bool
{
    return true; // or conditional logic
}

public static function canViewAny(): bool
{
    return true; // or permission check
}
```

### Navigation Configuration
```php
protected static ?string $navigationIcon = 'heroicon-o-[icon-name]';
protected static ?string $navigationGroup = '📊 Group Name';
protected static ?string $navigationLabel = 'Menu Label';
protected static ?int $navigationSort = 1; // Order in group
```

## Form Components Best Practices
- Use Sections for logical grouping
- Add helpful descriptions and placeholders
- Validate all required fields
- Use reactive() for dependent fields
- Implement proper validation messages

## Table Configuration
- Always include search functionality
- Add appropriate filters
- Use bulk actions for efficiency
- Implement proper sorting
- Add action buttons (View, Edit, Delete)

## Common Issues & Solutions

### Navigation Items Not Showing
✅ Add `shouldRegisterNavigation()` returning `true`
✅ Add `canViewAny()` returning `true`
✅ Clear cache: `php artisan cache:clear`

### Permission Errors
✅ Check user role has required permissions
✅ Verify policy methods are implemented
✅ Ensure middleware is properly configured

## Performance Tips
- Use `->preload()` sparingly on Select fields
- Paginate tables (default 10-25 items)
- Optimize queries with eager loading
- Cache expensive computations