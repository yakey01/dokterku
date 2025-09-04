# Project Structure - Klinik Keuangan Backend

## Complete Directory Structure

```
klinik-keuangan-backend/
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Events/
│   │   ├── DataInputDisimpan.php
│   │   ├── ValidasiBerhasil.php
│   │   └── JaspelSelesai.php
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Master/
│   │   │   │   ├── RoleController.php
│   │   │   │   └── JenisTindakanController.php
│   │   │   ├── Transaksi/
│   │   │   │   ├── PendapatanController.php
│   │   │   │   ├── PengeluaranController.php
│   │   │   │   ├── PasienController.php
│   │   │   │   ├── TindakanController.php
│   │   │   │   └── ValidasiController.php
│   │   │   └── Jaspel/
│   │   │       └── JaspelController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Listeners/
│   │   ├── KirimNotifikasiTelegram.php
│   │   └── HitungJaspelJob.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Shift.php
│   │   ├── Pasien.php
│   │   ├── Tindakan.php
│   │   ├── JenisTindakan.php
│   │   ├── Pendapatan.php
│   │   ├── Pengeluaran.php
│   │   ├── UangDuduk.php
│   │   └── Jaspel.php
│   ├── Services/
│   │   ├── Jaspel/
│   │   │   └── JaspelService.php
│   │   └── Transaksi/
│   │       ├── PendapatanService.php
│   │       ├── PengeluaranService.php
│   │       └── TindakanService.php
│   ├── Repositories/
│   │   ├── JaspelRepository.php
│   │   ├── PasienRepository.php
│   │   └── TindakanRepository.php
│   ├── Jobs/
│   │   ├── ProsesJaspelJob.php
│   │   └── KirimNotifikasiTelegramJob.php
│   ├── Policies/
│   └── Providers/
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── PasienFactory.php
│   │   └── TindakanFactory.php
│   ├── migrations/
│   │   └── [all migration files]
│   └── seeders/
│       ├── Master/
│       │   ├── RoleSeeder.php
│       │   ├── ShiftSeeder.php
│       │   ├── JenisTindakanSeeder.php
│       │   ├── PengaturanJaspelSeeder.php
│       │   └── TarifUangDudukSeeder.php
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── storage/
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── docs/
│   ├── erd/
│   ├── seeder/
│   │   └── sample-seeder-output.json
│   ├── prompt/
│   │   └── ultrathink-backend.yaml
│   ├── testing/
│   │   └── test-cases.md
│   └── PROJECT_STRUCTURE.md
│
├── artisan
├── composer.json
├── CLAUDE.md
└── .env
```

## Architecture Overview

### Clean Architecture Layers

#### 1. **Controllers Layer (API)**
- **Location**: `app/Http/Controllers/`
- **Purpose**: Handle HTTP requests and responses
- **Structure**: Organized by domain (Auth, Master, Transaksi, Jaspel)
- **Responsibilities**: 
  - Request validation
  - Response formatting
  - HTTP status codes
  - Delegate business logic to services

#### 2. **Services Layer (Business Logic)**
- **Location**: `app/Services/`
- **Purpose**: Implement business rules and workflows
- **Structure**: Organized by domain (Jaspel, Transaksi)
- **Responsibilities**:
  - Business logic implementation
  - Transaction management
  - Event dispatching
  - Data validation

#### 3. **Repositories Layer (Data Access)**
- **Location**: `app/Repositories/`
- **Purpose**: Abstract database operations
- **Structure**: One repository per major entity
- **Responsibilities**:
  - Database queries
  - Data filtering
  - Pagination
  - Statistics and aggregations

#### 4. **Models Layer (Entities)**
- **Location**: `app/Models/`
- **Purpose**: Represent domain entities
- **Structure**: One model per database table
- **Responsibilities**:
  - Relationships definition
  - Attribute casting
  - Query scopes
  - Business rules validation

#### 5. **Events & Listeners Layer**
- **Location**: `app/Events/` and `app/Listeners/`
- **Purpose**: Handle domain events
- **Structure**: Event-driven architecture
- **Responsibilities**:
  - Event dispatching
  - Asynchronous processing
  - System notifications
  - Background job triggering

#### 6. **Jobs Layer (Background Processing)**
- **Location**: `app/Jobs/`
- **Purpose**: Handle asynchronous tasks
- **Structure**: Queue-based processing
- **Responsibilities**:
  - Background calculations
  - External service calls
  - Data processing
  - Email/notification sending

## Key Features

### 1. **Role-Based Access Control**
- 7 distinct user roles
- Permission-based authorization
- Role hierarchy management
- Audit trail for access control

### 2. **Financial Transaction Management**
- Income/expense tracking
- Multi-level validation workflow
- Automated calculations
- Financial reporting

### 3. **Medical Procedure Tracking**
- Patient record management
- Procedure documentation
- Service fee calculations
- Performance analytics

### 4. **Jaspel (Service Fee) System**
- Automatic fee calculation
- Dynamic fee distribution
- Validation workflow
- Payroll integration

### 5. **Validation Workflow**
- 3-state approval process (pending → approved/rejected)
- Role-based validation rights
- Audit trail for all validations
- Notification system

### 6. **Real-time Notifications**
- Telegram integration
- Event-driven notifications
- Background job processing
- Configurable notification rules

## Development Guidelines

### 1. **Naming Conventions**
- Controllers: `{Entity}Controller.php`
- Services: `{Entity}Service.php`
- Repositories: `{Entity}Repository.php`
- Models: `{Entity}.php`
- Events: `{Action}{Entity}.php`
- Jobs: `{Action}{Entity}Job.php`

### 2. **Code Organization**
- Group related functionality by domain
- Keep controllers thin, services fat
- Use repositories for data access
- Implement proper error handling
- Write comprehensive tests

### 3. **Database Design**
- Use migrations for schema changes
- Implement proper foreign key constraints
- Add indexes for performance
- Use soft deletes for critical data
- Maintain audit trails

### 4. **Testing Strategy**
- Unit tests for business logic
- Feature tests for API endpoints
- Integration tests for external services
- Performance tests for critical paths
- Security tests for vulnerable areas

## Deployment Considerations

### 1. **Environment Configuration**
- Separate configs for dev/staging/prod
- Environment-specific settings
- Secure credential management
- Database connection pooling

### 2. **Performance Optimization**
- Database query optimization
- Caching strategies
- Queue worker management
- Background job processing

### 3. **Security Measures**
- Input validation and sanitization
- Authentication and authorization
- CSRF protection
- SQL injection prevention
- Audit logging

### 4. **Monitoring and Logging**
- Application performance monitoring
- Error tracking and alerting
- Database performance monitoring
- Queue job monitoring
- Security event logging

## Future Enhancements

### 1. **API Improvements**
- GraphQL implementation
- API versioning
- Rate limiting
- API documentation

### 2. **Performance Enhancements**
- Redis caching
- Database read replicas
- CDN integration
- Image optimization

### 3. **Feature Additions**
- Advanced reporting
- Data export capabilities
- Integration with external systems
- Mobile app API support

### 4. **Security Enhancements**
- Two-factor authentication
- Role-based data encryption
- Advanced audit logging
- Security compliance reporting