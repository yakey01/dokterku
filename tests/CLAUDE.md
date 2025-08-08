# Testing Guidelines

## Overview
Comprehensive testing using PHPUnit and Laravel's testing utilities.

## Test Structure
- `/Unit` - Isolated unit tests for classes/methods
- `/Feature` - Integration tests for features
- `/Browser` - Dusk tests for E2E testing (if applicable)

## Writing Tests

### Feature Test Example
```php
class PatientManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->petugas = User::factory()->petugas()->create();
    }
    
    /** @test */
    public function petugas_can_create_patient()
    {
        $this->actingAs($this->petugas)
            ->post('/petugas/patients', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])
            ->assertRedirect('/petugas/patients')
            ->assertSessionHas('success');
            
        $this->assertDatabaseHas('patients', [
            'name' => 'John Doe',
        ]);
    }
}
```

### Unit Test Example
```php
class AttendanceValidationServiceTest extends TestCase
{
    private AttendanceValidationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceValidationService();
    }
    
    /** @test */
    public function it_validates_gps_coordinates_within_radius()
    {
        $result = $this->service->validateLocation(
            -7.898878, // latitude
            111.961884, // longitude
            100 // radius in meters
        );
        
        $this->assertTrue($result);
    }
}
```

## Testing Patterns

### Database Testing
```php
// Use transactions for speed
use RefreshDatabase;

// Or use DatabaseTransactions for faster tests
use DatabaseTransactions;

// Assert database state
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('users', 5);
```

### API Testing
```php
public function test_api_returns_json()
{
    $response = $this->getJson('/api/v2/users');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email']
            ]
        ]);
}
```

### Authentication Testing
```php
// Test as authenticated user
$this->actingAs($user)
    ->get('/dashboard')
    ->assertOk();

// Test with specific guard
$this->actingAs($admin, 'admin')
    ->get('/admin/dashboard')
    ->assertOk();

// Test unauthorized access
$this->get('/admin')
    ->assertRedirect('/login');
```

## Filament Testing

### Resource Testing
```php
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

/** @test */
public function can_render_patient_index()
{
    Livewire::test(ListPatients::class)
        ->assertSuccessful()
        ->assertSee('Patients');
}

/** @test */
public function can_create_patient()
{
    Livewire::test(CreatePatient::class)
        ->fillForm([
            'name' => 'Test Patient',
            'email' => 'patient@test.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
}
```

## Mocking & Fakes

### Using Fakes
```php
// Storage fake
Storage::fake('avatars');

// Mail fake
Mail::fake();

// Queue fake
Queue::fake();

// Event fake
Event::fake();

// HTTP fake
Http::fake([
    'github.com/*' => Http::response(['name' => 'Laravel'], 200),
]);
```

### Mocking Services
```php
$mock = $this->mock(PaymentService::class);
$mock->shouldReceive('charge')
    ->once()
    ->with(100)
    ->andReturn(true);
```

## Test Data Patterns

### Factories
```php
// Single model
$user = User::factory()->create();

// Multiple models
$users = User::factory()->count(5)->create();

// With relationships
$user = User::factory()
    ->has(Post::factory()->count(3))
    ->create();

// Specific attributes
$admin = User::factory()->create([
    'role' => 'admin',
]);
```

### Seeders in Tests
```php
// Run specific seeder
$this->seed(RoleSeeder::class);

// Run multiple seeders
$this->seed([
    RoleSeeder::class,
    PermissionSeeder::class,
]);
```

## Performance Testing
```php
/** @test */
public function dashboard_loads_within_acceptable_time()
{
    $start = microtime(true);
    
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk();
    
    $duration = microtime(true) - $start;
    
    $this->assertLessThan(2, $duration, 'Dashboard took too long to load');
}
```

## Code Coverage

### Running Coverage
```bash
# Generate HTML coverage report
php artisan test --coverage-html=coverage

# Show coverage in terminal
php artisan test --coverage

# With minimum coverage enforcement
php artisan test --coverage --min=80
```

### Coverage Annotations
```php
/**
 * @covers \App\Services\AttendanceService
 * @coversNothing
 */
class AttendanceServiceTest extends TestCase
{
    // Tests
}
```

## Best Practices

1. **Test Naming**: Use descriptive names that explain what is being tested
2. **One Assertion Per Test**: Keep tests focused on single behavior
3. **Arrange-Act-Assert**: Structure tests clearly
4. **Use Data Providers**: For testing multiple scenarios
5. **Test Happy Path & Edge Cases**: Cover both success and failure scenarios
6. **Keep Tests Fast**: Use database transactions, avoid external API calls
7. **Test Isolation**: Each test should be independent

## Useful Commands
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run specific test method
php artisan test --filter test_user_can_login

# Run tests in parallel
php artisan test --parallel

# Run with verbose output
php artisan test -v

# Stop on first failure
php artisan test --stop-on-failure
```