# Blade Templates Guidelines

## Overview
Laravel Blade templates for server-side rendering. Organized by features and roles.

## Directory Structure
- `/layouts` - Master layouts and partials
- `/filament` - Filament-specific views for each panel
- `/mobile` - Mobile app views
- `/auth` - Authentication pages
- `/components` - Reusable Blade components

## Blade Best Practices

### Always Use Proper Escaping
```blade
{{-- Safe output (escaped) --}}
{{ $variable }}

{{-- Raw output (use sparingly) --}}
{!! $trustedHtml !!}
```

### Component Usage
```blade
{{-- Modern component syntax --}}
<x-alert type="success" :message="$message" />

{{-- Include partials --}}
@include('partials.header', ['title' => $pageTitle])
```

### Conditional Rendering
```blade
@if($user->hasRole('admin'))
    {{-- Admin content --}}
@elseif($user->hasRole('petugas'))
    {{-- Petugas content --}}
@else
    {{-- Default content --}}
@endif

@auth
    {{-- Authenticated user content --}}
@endauth

@guest
    {{-- Guest content --}}
@endguest
```

## Security Rules
- ALWAYS escape user input with `{{ }}`
- Use `@csrf` in all forms
- Validate permissions with `@can` directive
- Never expose sensitive data in views

## Performance Guidelines
- Use `@once` for one-time includes
- Implement view caching for static content
- Minimize database queries in views
- Use eager loading in controllers

## Mobile Views
- Separate mobile views in `/mobile` directory
- Use responsive design, not separate mobile sites
- Touch-optimized interfaces
- Minimal JavaScript for better performance

## Asset Management
```blade
{{-- Use Vite for assets --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Panel-specific assets --}}
@vite('resources/css/filament/petugas/theme.css')
```

## Common Patterns

### Form Validation Errors
```blade
@error('field_name')
    <span class="text-red-500 text-sm">{{ $message }}</span>
@enderror
```

### Flash Messages
```blade
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
```

### Pagination
```blade
{{ $items->links() }}
{{-- Or with Bootstrap --}}
{{ $items->links('pagination::bootstrap-5') }}
```

## Debugging Tips
- Use `@dd($variable)` for quick debugging
- `@dump($variable)` to dump without dying
- Check for undefined variables with `@isset()`