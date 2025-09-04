# Logging System Documentation

## Overview
The Dokterku application implements a comprehensive logging system that tracks all activities, errors, security events, and performance metrics. This system provides complete audit trails and monitoring capabilities for the application.

## Architecture

### Core Components

#### 1. LoggingService
The main service that handles all logging operations:
- **Activity Logging**: Tracks user actions and model changes
- **Error Logging**: Records errors and exceptions
- **Security Logging**: Monitors security events and threats
- **Performance Logging**: Tracks slow operations and resource usage
- **API Logging**: Records all API requests and responses

#### 2. Database Tables
- **audit_logs**: User activities and model changes
- **error_logs**: Application errors and exceptions
- **security_logs**: Security events and threats
- **performance_logs**: Performance metrics and slow operations

#### 3. Traits and Middleware
- **LogsActivity**: Automatically logs model changes
- **LogRequestsMiddleware**: Logs all HTTP requests
- **HandlesErrors**: Integrates logging with error handling

## Log Types

### 1. Activity Logs
Tracks all user actions and model changes:

```php
use App\Services\LoggingService;

$loggingService = new LoggingService();
$loggingService->logActivity(
    'created',
    $pasien,
    ['attributes' => $pasien->getAttributes()],
    'Membuat pasien baru'
);
```

**Automatically logged actions:**
- Model creation, updates, deletions
- User login/logout
- Data exports/imports
- Bulk operations
- Validation workflows

### 2. Error Logs
Records all application errors and exceptions:

```php
$loggingService->logError(
    'Database connection failed',
    $exception,
    ['query' => $query, 'bindings' => $bindings],
    'critical'
);
```

**Log levels:**
- `emergency`: System is unusable
- `alert`: Action must be taken immediately
- `critical`: Critical conditions
- `error`: Error conditions
- `warning`: Warning conditions
- `notice`: Normal but significant conditions
- `info`: Informational messages
- `debug`: Debug-level messages

### 3. Security Logs
Monitors security events and potential threats:

```php
$loggingService->logSecurity(
    'failed_login',
    $user,
    'Multiple failed login attempts',
    ['ip' => $request->ip(), 'attempts' => 5]
);
```

**Security events:**
- Failed login attempts
- Authorization failures
- Suspicious activity detection
- SQL injection attempts
- XSS attempts
- Path traversal attempts
- Account lockouts
- Role changes
- Two-factor authentication events

### 4. Performance Logs
Tracks system performance and slow operations:

```php
$loggingService->logPerformance(
    'database_query',
    $duration,
    ['query' => $query, 'memory' => memory_get_usage()],
    'warning'
);
```

**Performance metrics:**
- Request duration
- Memory usage
- Database query times
- File operations
- API response times

### 5. API Logs
Records all API requests and responses:

```php
$loggingService->logApiRequest(
    'POST',
    '/api/patients',
    $request->all(),
    $response->getData(),
    201,
    $duration
);
```

## Automatic Logging

### Model Activity Logging
Use the `LogsActivity` trait on models:

```php
use App\Traits\LogsActivity;

class Pasien extends Model
{
    use LogsActivity;
    
    // Optional: specify which actions to log
    protected $loggedActions = ['created', 'updated', 'deleted'];
    
    // Optional: specify attributes to hide from logs
    protected $hiddenLogAttributes = ['password', 'token'];
}
```

### Request Logging
Add the middleware to log all requests:

```php
// In kernel.php
protected $middleware = [
    \App\Http\Middleware\LogRequestsMiddleware::class,
];
```

## Usage Examples

### Basic Activity Logging
```php
use App\Services\LoggingService;

$loggingService = new LoggingService();

// Log user action
$loggingService->logActivity(
    'exported',
    null,
    ['format' => 'xlsx', 'records' => 100],
    'Export data pasien ke Excel'
);

// Log model changes
$loggingService->logActivity(
    'updated',
    $pasien,
    ['old' => $oldData, 'new' => $newData],
    'Memperbarui data pasien'
);
```

### Error Logging
```php
try {
    // Risky operation
    $result = $this->performOperation();
} catch (Exception $e) {
    $loggingService->logError(
        'Operation failed',
        $e,
        ['operation' => 'data_import', 'file' => $filename],
        'error'
    );
    throw $e;
}
```

### Security Logging
```php
// Log failed authentication
$loggingService->logSecurity(
    'authentication_failed',
    null,
    'Failed login attempt with invalid credentials',
    ['username' => $username, 'ip' => $request->ip()]
);

// Log suspicious activity
$loggingService->logSecurity(
    'suspicious_activity',
    auth()->user(),
    'Potential SQL injection attempt detected',
    ['query' => $suspiciousQuery, 'url' => $request->url()]
);
```

### Performance Logging
```php
$startTime = microtime(true);

// Perform operation
$result = $this->expensiveOperation();

$duration = microtime(true) - $startTime;

$loggingService->logPerformance(
    'expensive_operation',
    $duration,
    ['records_processed' => count($result)],
    $duration > 5.0 ? 'warning' : 'info'
);
```

## Configuration

### Environment Variables
```env
LOG_LEVEL=debug
LOG_CHANNEL=stack
LOG_QUERIES=true
LOG_REQUESTS=true
LOG_PERFORMANCE=true
LOG_SECURITY=true
```

### Log Channels
Configure custom log channels in `config/logging.php`:

```php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 30,
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'info',
        'days' => 7,
    ],
    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

## Management Commands

### Cleanup Old Logs
```bash
# Cleanup logs older than 30 days
php artisan dokterku:cleanup-logs

# Cleanup specific log types
php artisan dokterku:cleanup-logs --type=activity --days=7

# Dry run to see what would be deleted
php artisan dokterku:cleanup-logs --dry-run
```

### Schedule Regular Cleanup
Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily cleanup of old logs
    $schedule->command('dokterku:cleanup-logs --days=30')
        ->daily()
        ->at('02:00');
    
    // Weekly cleanup of performance logs
    $schedule->command('dokterku:cleanup-logs --type=performance --days=7')
        ->weekly()
        ->sundays()
        ->at('03:00');
}
```

## Filament Integration

### Audit Log Resource
View and manage audit logs through the Filament interface:

- **Location**: Admin Panel > Administrasi Sistem > Audit Log
- **Features**:
  - View all activity logs
  - Filter by action, model, user, date
  - Search functionality
  - Export capabilities
  - Cleanup actions

### Log Viewing
```php
// In any Filament resource
use App\Services\LoggingService;

class MyResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        // Log when resource is accessed
        $loggingService = new LoggingService();
        $loggingService->logActivity(
            'viewed',
            null,
            ['resource' => static::class],
            'Mengakses resource ' . static::getModelLabel()
        );
        
        return parent::getEloquentQuery();
    }
}
```

## Security Features

### Data Sanitization
Sensitive data is automatically hidden from logs:

```php
protected $sensitiveFields = [
    'password',
    'password_confirmation',
    'current_password',
    'token',
    'api_token',
    'remember_token',
    'email_verified_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'telegram_token',
    'bot_token',
];
```

### Threat Detection
Automatic detection of suspicious activities:

- SQL injection attempts
- XSS attempts
- Path traversal attempts
- Multiple failed login attempts
- Unusual access patterns

### Audit Trail
Complete audit trail for compliance:

- Who performed the action
- What was changed
- When it happened
- Where it came from (IP, user agent)
- Why it was done (context)

## Performance Considerations

### Database Optimization
- Proper indexing on log tables
- Automatic cleanup of old logs
- Partitioning for large datasets

### Memory Usage
- Batch processing for large operations
- Lazy loading of log data
- Efficient serialization

### Storage
- Log rotation and archiving
- Compression of old logs
- External storage options

## Monitoring and Alerting

### Log Analysis
```php
// Get recent security events
$securityLogs = $loggingService->getRecentLogs('security', 100);

// Get error trends
$errorLogs = $loggingService->getRecentLogs('error', 50);

// Get performance metrics
$performanceLogs = $loggingService->getRecentLogs('performance', 25);
```

### Alert Thresholds
Monitor for:
- High error rates
- Security incidents
- Performance degradation
- Unusual activity patterns

## Best Practices

### 1. Log Meaningful Information
```php
// Good
$loggingService->logActivity(
    'patient_created',
    $patient,
    ['hospital_id' => $hospital->id, 'created_by' => auth()->id()],
    "Pasien {$patient->nama} berhasil didaftarkan"
);

// Bad
$loggingService->logActivity('action', null, [], 'Something happened');
```

### 2. Use Appropriate Log Levels
```php
// Critical system failure
$loggingService->logError('Database connection lost', $e, [], 'critical');

// User input validation
$loggingService->logError('Invalid patient data', $e, [], 'warning');

// Debug information
$loggingService->logError('Query executed', null, ['query' => $sql], 'debug');
```

### 3. Include Context
```php
$loggingService->logActivity(
    'bulk_update',
    null,
    [
        'affected_models' => 'Pasien',
        'record_count' => 150,
        'update_fields' => ['status', 'updated_at'],
        'duration' => $duration,
    ],
    'Bulk update 150 patient records'
);
```

### 4. Handle Logging Failures
```php
try {
    $loggingService->logActivity($action, $model, $properties);
} catch (Exception $e) {
    // Fallback to file logging
    Log::error('Failed to log activity', [
        'action' => $action,
        'error' => $e->getMessage(),
    ]);
}
```

## Troubleshooting

### Common Issues

1. **Log Storage Full**
   - Run cleanup commands
   - Increase disk space
   - Configure log rotation

2. **Performance Impact**
   - Adjust log levels
   - Optimize database queries
   - Use asynchronous logging

3. **Missing Logs**
   - Check database connections
   - Verify table structures
   - Review error logs

4. **Security Concerns**
   - Audit log access permissions
   - Encrypt sensitive log data
   - Monitor log integrity

### Debugging
```php
// Enable query logging
DB::enableQueryLog();

// Check log service status
$service = new LoggingService();
$recentLogs = $service->getRecentLogs('activity', 10);

// Verify database tables
Schema::hasTable('audit_logs');
Schema::hasTable('error_logs');
Schema::hasTable('security_logs');
Schema::hasTable('performance_logs');
```

## Future Enhancements

### Planned Features
1. **Real-time Log Streaming**: Live log monitoring dashboard
2. **Machine Learning**: Anomaly detection and predictive analytics
3. **External Integrations**: Send logs to external monitoring services
4. **Advanced Visualization**: Charts and graphs for log analysis
5. **Automated Responses**: Trigger actions based on log patterns