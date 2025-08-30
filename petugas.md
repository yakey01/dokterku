# Petugas Panel Documentation

## Executive Summary

The Petugas Panel is a comprehensive healthcare data management system built with Laravel Filament 3, featuring an elegant black glassmorphic theme and sophisticated UI/UX patterns. This panel serves as the primary data entry interface for medical staff to manage patient information, medical procedures, and financial data with real-time validation and workflow management.

## Architecture Overview

### Panel Configuration
- **Panel ID**: `petugas`
- **URL Path**: `/petugas`
- **Theme**: Elegant black glassmorphic design with custom CSS overrides
- **Navigation**: Top navigation with collapsible sidebar (280px width)
- **Authentication**: Custom login with unified auth system

### Core Components Structure
```
app/Filament/Petugas/
├── Resources/           # Data resource management
├── Pages/              # Custom dashboard and pages
├── Widgets/            # Dashboard widgets and components
└── Providers/          # Panel configuration and theme
```

## Design System & Styling

### Theme Implementation

#### Color Palette
The panel uses a sophisticated black/dark theme with high contrast accessibility:

```css
/* Primary Colors */
--card-primary-black: #0a0a0b     /* Deep black base */
--card-secondary-black: #111118    /* Dark black accent */
--card-charcoal: #1a1a20          /* Charcoal backgrounds */
--card-border: #333340            /* Border colors */

/* Text Hierarchy */
--card-text-primary: #fafafa      /* High contrast white */
--card-text-secondary: #e4e4e7    /* Secondary text */
--card-text-muted: #a1a1aa        /* Muted content */
--card-text-subtle: #71717a       /* Subtle details */
```

#### Glassmorphic Effects
Advanced blur and transparency effects create depth:

```css
.glassmorphic-card {
    background: linear-gradient(135deg, 
        rgba(10, 10, 11, 0.9) 0%, 
        rgba(17, 17, 24, 0.8) 100%);
    backdrop-filter: blur(16px) saturate(150%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 
        0 4px 24px rgba(0, 0, 0, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.06);
}
```

#### Theme Override Strategy
The panel implements comprehensive CSS overrides to ensure consistent theming:

1. **Panel Provider CSS Injection**: Direct style injection in `PetugasPanelProvider`
2. **Theme File Override**: `/resources/css/filament/petugas/theme.css` (25K+ lines)
3. **Component-Level Styling**: Individual Blade components with embedded CSS
4. **Runtime Style Injection**: JavaScript-based dynamic styling

### UI Component System

#### Statistical Cards
```php
// Dashboard stat cards with glassmorphic effects
<div class="stat-card stat-card-patients">
    <div class="stat-icon-container">
        <svg class="stat-icon"><!-- Healthcare icon --></svg>
    </div>
    <div class="stat-content">
        <h3 class="stat-label">Total Pasien</h3>
        <p class="stat-value">{{ number_format($count) }}</p>
        <div class="stat-change positive">
            <span>{{ $percentage }}%</span>
        </div>
    </div>
</div>
```

#### Navigation Cards
Interactive navigation with hover effects:
- **Manajemen Pasien**: Patient data management
- **Tindakan Medis**: Medical procedures
- **Manajemen Keuangan**: Financial operations

#### Activity Feed
Real-time activity tracking with categorized icons and animations.

## Resource Management

### Core Resources

#### 1. PasienResource (Patient Management)
**Purpose**: Complete patient data lifecycle management

**Form Components**:
- **Identity Section**: Name, medical record number, birth date, gender
- **Personal Information**: Occupation, marital status (collapsible)
- **Validation**: Real-time form validation with custom messages

**Table Features**:
- **Advanced Search**: Multi-field search with saved searches
- **Export/Import**: Excel, CSV, JSON support with validation
- **Bulk Operations**: Status updates, user assignment, data export
- **Action Groups**: View, Edit, Delete, Copy medical record number

**Key Features**:
- Auto-generated medical record numbers
- Age calculation from birth date
- Status verification workflow
- Advanced search with filter persistence
- Comprehensive audit logging

#### 2. TindakanResource (Medical Procedures)
**Purpose**: Medical procedure documentation and JASPEL calculation

**Complex Form Logic**:
```php
// Dynamic JASPEL calculation based on staff selection
->afterStateUpdated(function ($state, callable $set, callable $get) {
    if ($dokterId = $get('dokter_id')) {
        // Doctor gets JASPEL
        $set('jasa_dokter', $calculatedJaspel);
        $set('jasa_paramedis', 0);
        $set('jasa_non_paramedis', 0);
    } elseif ($paramedisId = $get('paramedis_id')) {
        // Paramedis gets JASPEL if no doctor
        $set('jasa_paramedis', $calculatedJaspel);
        $set('jasa_dokter', 0);
    }
})
```

**Validation Workflow**:
- **Submit for Validation**: Staff submits completed procedures
- **Approval Process**: Supervisor/manager approval with comments
- **Status Tracking**: Comprehensive status badges with descriptions
- **Rejection Handling**: Revision requests with detailed feedback

**Financial Integration**:
- Automatic tariff calculation from master data
- JASPEL distribution based on staff hierarchy
- Percentage-based calculation (configurable)
- Real-time financial updates

#### 3. JumlahPasienHarianResource (Daily Patient Count)
**Purpose**: Daily patient volume tracking for JASPEL calculations

**Smart Form Features**:
- **Date Validation**: Max date = today, duplicate prevention
- **Poli Selection**: General practice vs. dental
- **Shift Integration**: Automatic shift-based formula selection
- **Doctor Assignment**: Active doctor filtering with search
- **Schedule Sync**: Integration with jadwal jaga system

#### 4. Financial Resources (Pendapatan/Pengeluaran)
**Purpose**: Daily financial transaction management

**Validation Rules**:
- Date-based uniqueness constraints
- Amount validation with currency formatting
- Category-based organization
- User-specific edit permissions

### Navigation Structure

#### Navigation Groups
```php
NavigationGroup::make('Manajemen Pasien')
    ->collapsed(false)
    ->collapsible(false),
NavigationGroup::make('Tindakan Medis')
    ->collapsed(false),
NavigationGroup::make('Keuangan')
    ->collapsed(false),
NavigationGroup::make('Laporan & Analytics'),
NavigationGroup::make('Quick Actions'),
NavigationGroup::make('System')
```

## Dashboard Implementation

### Main Dashboard (PetugasDashboard)
**View**: `elegant-glassmorphic-dashboard.blade.php`

#### Data Layer
```php
// Real-time operational metrics
public function getOperationalSummary(): array {
    return [
        'current' => [
            'pendapatan' => $currentRevenue,
            'pengeluaran' => $currentExpenses,
            'pasien' => $currentPatients,
            'tindakan' => $currentProcedures,
        ],
        'changes' => [
            'pendapatan' => $this->calculatePercentageChange($current, $last),
            // ... other metrics
        ]
    ];
}
```

#### Statistical Grid
Four main stat cards with glassmorphic effects:
1. **Patient Statistics**: Total patients with trend indicators
2. **Medical Procedures**: Completed procedures count
3. **Revenue Tracking**: Monthly revenue with changes
4. **Data Entry Progress**: Daily completion vs. targets

#### Interactive Elements
- **Hover Effects**: 3D transforms and parallax effects
- **Progress Bars**: Animated progress indicators
- **Ripple Effects**: Material Design-inspired interactions
- **Real-time Updates**: 30-second polling for live data

### Header Widget (PetugasHeaderWidget)
**Purpose**: Contextual user information and system status

**Features**:
- **Time-based Greetings**: Indonesian time-sensitive greetings
- **User Context**: Role display and last login tracking
- **System Status**: Database, cache, queue monitoring
- **Quick Stats**: Today's patient count and system health

**Responsive Design**:
```css
@media (max-width: 768px) {
    .header-content-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }
    .enhanced-quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

## User Experience Patterns

### Form Design Philosophy

#### Progressive Disclosure
Forms use collapsible sections to reduce cognitive load:
- **Essential fields first**: Required information prominently displayed
- **Secondary data collapsed**: Optional fields hidden by default
- **Context-sensitive help**: Inline help text and validation messages

#### Smart Validation
```php
// Real-time validation with contextual messages
Forms\Components\TextInput::make('no_rekam_medis')
    ->unique(ignoreRecord: true)
    ->validationMessages([
        'unique' => 'Nomor rekam medis sudah digunakan.',
        'max' => 'Nomor rekam medis maksimal 20 karakter.',
    ])
```

#### Reactive Forms
Dynamic form updates based on user selections:
- **Dependent Dropdowns**: Options change based on previous selections
- **Calculated Fields**: Automatic calculations (JASPEL, totals)
- **Conditional Logic**: Show/hide fields based on context

### Table Interface Design

#### Enhanced Data Tables
**Features**:
- **Elegant Dark Theme**: Consistent with overall design
- **Row Hover Effects**: Subtle animations and highlighting
- **Action Grouping**: Organized action buttons with tooltips
- **Status Badges**: Color-coded status indicators
- **Search Persistence**: Session-based search and filter memory

#### Advanced Search Capabilities
```php
// Multi-criteria search with saved searches
Tables\Actions\Action::make('advanced_search')
    ->form([
        Repeater::make('filters')->schema([
            Select::make('field')->options([...]),
            Select::make('operator')->options([...]),
            TextInput::make('value'),
        ])
    ])
```

### Workflow Management

#### Medical Procedure Workflow
1. **Creation**: Staff creates procedure record
2. **Completion**: Status updated to 'selesai'
3. **Submission**: Submitted for validation
4. **Review**: Supervisor/manager review
5. **Approval/Rejection**: Final decision with feedback

#### Data Entry Workflow
1. **Patient Registration**: Complete patient information
2. **Procedure Documentation**: Link procedures to patients
3. **Financial Recording**: Track revenue and expenses
4. **Validation Process**: Multi-level approval system

## Technical Implementation Details

### Security Features

#### Access Control
- **Role-based permissions**: Petugas role enforcement
- **Record ownership**: Users can only edit their own records
- **Validation workflow**: Multi-level approval process
- **Audit logging**: Comprehensive change tracking

#### Data Validation
```php
// Multi-field uniqueness constraints
->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
    return $rule->where('poli', $get('poli'))
               ->where('shift', $get('shift'))
               ->where('dokter_id', $get('dokter_id'));
})
```

### Performance Optimizations

#### Query Optimization
```php
// Eager loading for reduced N+1 queries
public static function getEloquentQuery(): Builder {
    return parent::getEloquentQuery()
        ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'shift', 'inputBy']);
}
```

#### Caching Strategy
- **Session-based persistence**: Search, filters, and pagination
- **Widget caching**: Dashboard data caching
- **Real-time polling**: 30-second updates for live data

#### Asset Management
- **CSS Minification**: Optimized stylesheets
- **JavaScript Bundling**: Efficient script loading
- **Image Optimization**: Compressed assets

### Integration Points

#### Model Relationships
```php
// Complex relationships for comprehensive data linking
public function jenisTindakan(): BelongsTo
public function pasien(): BelongsTo  
public function dokter(): BelongsTo
public function shift(): BelongsTo
public function inputBy(): BelongsTo
```

#### Service Integration
- **BulkOperationService**: Mass data operations
- **ExportImportService**: Data exchange capabilities
- **ValidationWorkflowService**: Approval process management
- **TelegramService**: Notification system integration

## Advanced Features

### Bulk Operations
Comprehensive bulk operation support:
- **Export Selection**: Multiple format support (Excel, CSV, JSON)
- **Status Updates**: Bulk status changes with validation
- **User Assignment**: Mass assignment to different users
- **Approval Workflows**: Batch approval processes

### Advanced Search System
```php
// Sophisticated search with multiple operators
$searchParams = [
    'filters' => [
        ['field' => 'nama', 'operator' => 'contains', 'value' => 'John'],
        ['field' => 'tanggal_lahir', 'operator' => 'date_after', 'value' => '1990-01-01']
    ],
    'search' => 'global search term'
];
```

### Real-time Features
- **Live Updates**: Polling-based data refresh
- **Status Indicators**: Real-time status changes
- **Notification System**: Instant feedback for actions
- **Progress Tracking**: Live progress indicators

## Responsive Design Strategy

### Breakpoint System
```css
/* Mobile-first responsive design */
@media (max-width: 480px) { /* Mobile */ }
@media (max-width: 768px) { /* Tablet */ }
@media (max-width: 1200px) { /* Desktop */ }
```

### Adaptive Layouts
- **Flexible Grids**: CSS Grid with auto-fit columns
- **Collapsible Navigation**: Mobile-optimized navigation
- **Touch-friendly UI**: Appropriate touch targets
- **Readable Typography**: Scalable text and spacing

## Accessibility Implementation

### WCAG Compliance
- **High Contrast**: AAA-level color contrast ratios
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Reader Support**: Semantic HTML and ARIA labels
- **Reduced Motion**: Respect for prefers-reduced-motion

### Inclusive Design
```css
/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .stat-card, .nav-card { 
        transition: none; 
        animation: none; 
    }
}

@media (prefers-contrast: high) {
    .stat-card { border-width: 2px; }
}
```

## Future Enhancement Opportunities

### Performance Improvements
1. **Lazy Loading**: Implement lazy loading for large datasets
2. **Virtual Scrolling**: For extremely large tables
3. **Progressive Web App**: Offline capabilities
4. **Background Sync**: Queue heavy operations

### Feature Enhancements
1. **Advanced Analytics**: Business intelligence dashboards
2. **Mobile App**: Native mobile application
3. **Voice Input**: Voice-to-text data entry
4. **AI Integration**: Predictive analytics and automation

### User Experience Enhancements
1. **Dark/Light Mode Toggle**: User preference system
2. **Customizable Dashboards**: User-configured layouts
3. **Advanced Filtering**: More sophisticated filter options
4. **Collaboration Tools**: Team-based workflow features

## Conclusion

The Petugas Panel represents a sophisticated healthcare data management system with modern UI/UX patterns, comprehensive functionality, and robust technical architecture. The elegant black glassmorphic theme provides a premium user experience while maintaining high accessibility standards. The modular design and advanced features support complex healthcare workflows with efficiency and reliability.

The implementation showcases best practices in Laravel Filament development, including advanced form handling, complex table interfaces, real-time features, and comprehensive validation workflows. The system is well-positioned for future enhancements and scalability requirements.