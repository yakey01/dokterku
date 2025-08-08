# App Directory Guidelines

## Overview
This directory contains all Laravel application logic including Models, Controllers, Services, and Filament resources.

## Architecture Rules
- **Models**: Use Eloquent ORM with proper relationships, scopes, and casts
- **Controllers**: Keep thin, delegate business logic to Services
- **Services**: Single responsibility, testable, dependency injection
- **Policies**: Always check permissions before data access

## Naming Conventions
- Models: Singular PascalCase (e.g., `User`, `Patient`, `Tindakan`)
- Controllers: PascalCase with "Controller" suffix (e.g., `PatientController`)
- Services: PascalCase with "Service" suffix (e.g., `AttendanceValidationService`)

## Database Patterns
- Always use migrations for schema changes
- Soft deletes for critical data (patients, transactions)
- Use database transactions for multi-table operations
- Index foreign keys and frequently queried columns

## Common Imports
```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
```

## Security Requirements
- NEVER expose sensitive data in responses
- Always validate input data
- Use Laravel's built-in authentication
- Sanitize all user inputs
- Check permissions using policies

## Performance Guidelines
- Use eager loading to prevent N+1 queries
- Cache expensive queries
- Paginate large datasets
- Use chunking for batch operations