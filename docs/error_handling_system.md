# Error Handling System

## Overview
The Dokterku application implements a comprehensive error handling system that provides consistent error management, user-friendly messages, and detailed logging for debugging purposes.

## Architecture

### Core Components

#### 1. Exception Classes
- **`DokterkunException`**: Base exception class for all application errors
- **`ValidationException`**: Handles validation errors with detailed field information
- **`BusinessLogicException`**: Manages business rule violations
- **`SystemException`**: Handles system-level errors (database, filesystem, etc.)

#### 2. Error Handling Service
- **`ErrorHandlingService`**: Centralized error processing and conversion
- **`HandlesErrors` Trait**: Provides error handling methods for classes
- **`ValidationHelper`**: Standardized validation with Indonesian messages

#### 3. Middleware & Handler
- **`ErrorHandlingMiddleware`**: API error handling middleware
- **`Handler`**: Custom exception handler for consistent error responses

## Exception Types

### DokterkunException (Base)
```php
throw new DokterkunException(
    'Internal message for logs',
    'User-friendly message in Indonesian',
    400,
    'ERROR_CODE',
    ['context' => 'additional data'],
    'warning'
);
```

### ValidationException
```php
throw new ValidationException(
    ['field' => ['error message']],
    'Data validation failed',
    'Mohon periksa kembali data yang Anda masukkan.'
);
```

### BusinessLogicException
```php
// Patient not found
throw BusinessLogicException::pasienNotFound('RM-2024-001');

// Doctor not available
throw BusinessLogicException::dokterNotAvailable('Dr. Ahmad');

// Duplicate entry
throw BusinessLogicException::duplicateEntry('email', 'user@example.com');
```

### SystemException
```php
// Database error
throw SystemException::databaseError('insert_operation', 'Connection failed');

// File system error
throw SystemException::fileSystemError('file_upload', '/uploads/file.pdf');

// External service error
throw SystemException::externalServiceError('telegram_api', 'Rate limit exceeded');
```

## Usage in Services

### Using HandlesErrors Trait
```php
use App\Traits\HandlesErrors;

class MyService
{
    use HandlesErrors;

    public function processData($data)
    {
        return $this->wrapOperation(function() use ($data) {
            // Your operation here
            return $this->performOperation($data);
        }, 'process_data');
    }

    public function safeOperation($data)
    {
        return $this->safeExecute(function() use ($data) {
            // Operation that might fail
            return $this->riskyOperation($data);
        }, $default = null, 'safe_operation');
    }
}
```

### Direct Error Handling
```php
use App\Services\ErrorHandlingService;

try {
    // Your operation
} catch (Exception $e) {
    $errorHandler = new ErrorHandlingService();
    $dokterkunException = $errorHandler->handleException($e);
    
    // Handle the converted exception
}
```

## Validation System

### Using ValidationHelper
```php
use App\Helpers\ValidationHelper;

// Validate with predefined rules
$validated = ValidationHelper::validateDataType('pasien', $data);

// Custom validation
$validated = ValidationHelper::validate($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
], ValidationHelper::getMessages(), ValidationHelper::getAttributes());

// File upload validation
$validated = ValidationHelper::validateFileUpload($data, ['xlsx', 'csv'], 5120);
```

### Predefined Validation Rules
The system includes predefined validation rules for:
- `pasien` (Patient data)
- `dokter` (Doctor data)
- `tindakan` (Medical procedure data)
- `pendapatan` (Income data)
- `pengeluaran` (Expense data)
- `pendapatan_harian` (Daily income data)
- `pengeluaran_harian` (Daily expense data)
- `jumlah_pasien_harian` (Daily patient count data)

## Error Responses

### Web Responses
- Redirects back with error messages
- Filament notifications for UI feedback
- Form validation errors displayed inline

### API Responses
```json
{
    "error": true,
    "message": "User-friendly error message",
    "error_code": "VALIDATION_ERROR",
    "timestamp": "2024-01-15T10:30:00Z",
    "path": "/api/patients",
    "method": "POST"
}
```

## Error Codes

### Validation Errors
- `VALIDATION_ERROR`: General validation failure
- `REQUIRED_FIELD_MISSING`: Required field not provided
- `INVALID_FORMAT`: Data format validation failed

### Business Logic Errors
- `PATIENT_NOT_FOUND`: Patient record not found
- `DOCTOR_NOT_AVAILABLE`: Doctor not available for appointment
- `INVALID_TINDAKAN`: Medical procedure not valid
- `INSUFFICIENT_PERMISSION`: User lacks required permissions
- `DUPLICATE_ENTRY`: Duplicate data entry
- `RECORD_IN_USE`: Record cannot be deleted (foreign key constraint)

### System Errors
- `DATABASE_ERROR`: Database operation failed
- `FILESYSTEM_ERROR`: File system operation failed
- `EXTERNAL_SERVICE_ERROR`: External service unavailable
- `CONFIGURATION_ERROR`: System configuration issue
- `MEMORY_LIMIT_EXCEEDED`: Memory limit reached
- `TIMEOUT_ERROR`: Operation timeout

## Logging

### Automatic Logging
All exceptions are automatically logged with:
- Exception type and message
- File and line number
- Stack trace
- User context (ID, IP, user agent)
- Request context (URL, method, parameters)
- Database query log (if enabled)

### Log Levels
- `critical`: System failures requiring immediate attention
- `error`: Application errors that need investigation
- `warning`: Validation errors and business logic violations
- `info`: General information for debugging

## Best Practices

### 1. Use Appropriate Exception Types
```php
// Good
throw BusinessLogicException::pasienNotFound($rekamMedis);

// Bad
throw new Exception('Patient not found');
```

### 2. Provide User-Friendly Messages
```php
// Good
throw new ValidationException(
    $errors,
    'Validation failed',
    'Mohon periksa kembali data yang Anda masukkan.'
);

// Bad
throw new Exception('Array to string conversion error');
```

### 3. Use Error Handling Traits
```php
// Good
class MyService
{
    use HandlesErrors;
    
    public function operation()
    {
        return $this->executeWithNotification(
            fn() => $this->performOperation(),
            'Operasi berhasil dilakukan'
        );
    }
}

// Bad
class MyService
{
    public function operation()
    {
        try {
            return $this->performOperation();
        } catch (Exception $e) {
            // Manual error handling
        }
    }
}
```

### 4. Validate Early and Consistently
```php
// Good
$validated = ValidationHelper::validateDataType('pasien', $data);

// Bad
if (empty($data['name'])) {
    throw new Exception('Name is required');
}
```

## Configuration

### Environment Variables
```env
LOG_LEVEL=debug
DB_LOG_QUERIES=true
APP_DEBUG=true
```

### Error Reporting
- Production: Only log errors, show user-friendly messages
- Development: Show detailed error information
- Testing: Capture all errors for test assertions

## Integration with Filament

### Resource Error Handling
```php
use App\Traits\HandlesErrors;

class PasienResource extends Resource
{
    use HandlesErrors;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form fields with automatic validation
        ]);
    }
}
```

### Bulk Action Error Handling
```php
->action(function (Collection $records, array $data) {
    try {
        $bulkService = new BulkOperationService();
        $result = $bulkService->bulkUpdate(Model::class, $updates);
        
        Notification::make()
            ->success()
            ->title('✅ Berhasil')
            ->body("Berhasil update {$result['updated']} record.")
            ->send();
            
    } catch (Exception $e) {
        $errorHandler = new ErrorHandlingService();
        $dokterkunException = $errorHandler->handleException($e);
        
        Notification::make()
            ->danger()
            ->title('❌ Gagal')
            ->body($dokterkunException->getUserMessage())
            ->send();
    }
})
```

## Testing Error Handling

### Unit Tests
```php
public function test_validation_exception_thrown()
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Data tidak valid');
    
    ValidationHelper::validateDataType('pasien', []);
}
```

### Integration Tests
```php
public function test_api_error_response()
{
    $response = $this->postJson('/api/patients', []);
    
    $response->assertStatus(422)
        ->assertJson([
            'error' => true,
            'error_code' => 'VALIDATION_ERROR',
        ]);
}
```

## Monitoring and Alerts

### Error Monitoring
- Log aggregation with structured data
- Error rate monitoring
- Alert thresholds for critical errors
- Performance impact tracking

### Metrics
- Error count by type
- Response time impact
- User experience metrics
- System health indicators

## Future Enhancements

### Planned Features
1. **Error Analytics Dashboard**: Real-time error monitoring
2. **Automated Error Recovery**: Self-healing for common issues
3. **User Error Feedback**: Allow users to report errors
4. **Performance Monitoring**: Track error impact on performance
5. **Notification System**: Real-time error alerts for admins