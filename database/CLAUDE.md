# Database Guidelines

## Overview
Database migrations, seeders, and factories following Laravel best practices.

## Migration Rules

### Naming Convention
```bash
# Format: yyyy_mm_dd_hhmmss_action_description.php
2024_01_15_100000_create_users_table.php
2024_01_16_100000_add_role_id_to_users_table.php
2024_01_17_100000_modify_email_column_in_users_table.php
```

### Migration Structure
```php
public function up()
{
    Schema::create('table_name', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
        $table->softDeletes();
        
        // Indexes
        $table->index('user_id');
        $table->unique(['email', 'deleted_at']);
    });
}

public function down()
{
    Schema::dropIfExists('table_name');
}
```

## Table Design Patterns

### Primary Keys
- Always use `$table->id()` for auto-incrementing BIGINT
- Use UUIDs only when necessary: `$table->uuid('id')->primary()`

### Foreign Keys
```php
// Modern Laravel way
$table->foreignId('user_id')->constrained();

// With cascade
$table->foreignId('patient_id')
    ->constrained()
    ->cascadeOnDelete()
    ->cascadeOnUpdate();

// Nullable foreign key
$table->foreignId('doctor_id')->nullable()->constrained();
```

### Common Columns
```php
// Timestamps (created_at, updated_at)
$table->timestamps();

// Soft deletes
$table->softDeletes();

// Status flags
$table->boolean('is_active')->default(true);
$table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

// Money/Currency
$table->decimal('amount', 12, 2); // 12 digits total, 2 after decimal

// JSON data
$table->json('metadata')->nullable();
```

## Seeder Best Practices

### Development Seeds
```php
class DevelopmentSeeder extends Seeder
{
    public function run()
    {
        // Only run in non-production
        if (app()->environment('production')) {
            return;
        }
        
        // Create test data
        User::factory(10)->create();
    }
}
```

### Master Data Seeds
```php
class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator'],
            ['name' => 'petugas', 'display_name' => 'Petugas'],
            // ...
        ];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
```

## Factory Patterns
```php
class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
    
    // State modifications
    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);
    }
}
```

## Performance Optimization

### Indexes
```php
// Single column index
$table->index('email');

// Composite index
$table->index(['user_id', 'created_at']);

// Unique index
$table->unique('username');
```

### Query Optimization Tips
- Index foreign keys
- Index columns used in WHERE clauses
- Use composite indexes for multi-column queries
- Avoid indexing low-cardinality columns
- Consider partial indexes for soft-deleted records

## Common Gotchas

### SQLite Limitations
- Cannot drop columns
- Cannot modify columns
- Limited ALTER TABLE support
- Solution: Use fresh migrations in development

### Foreign Key Constraints
- Order matters: Create parent tables first
- Disable foreign key checks for seeding:
```php
Schema::disableForeignKeyConstraints();
// Seed data
Schema::enableForeignKeyConstraints();
```

### Rollback Safety
- Always test rollback: `php artisan migrate:rollback`
- Make down() methods comprehensive
- Consider data loss implications

## Useful Commands
```bash
# Run migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Fresh migration (drop all, migrate)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Make new migration
php artisan make:migration create_patients_table

# Make model with migration, factory, seeder
php artisan make:model Patient -mfs
```