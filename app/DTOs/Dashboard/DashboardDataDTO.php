<?php

namespace App\DTOs\Dashboard;

/**
 * Dashboard Data Transfer Object
 * 
 * Standardized data structure for all dashboard implementations.
 * Ensures consistent API responses across different user roles and contexts.
 */
class DashboardDataDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $role,
        public readonly array $permissions,
        public readonly MetricsDTO $metrics,
        public readonly array $widgets,
        public readonly array $quickActions,
        public readonly array $notifications,
        public readonly array $schedule,
        public readonly array $attendance,
        public readonly ?array $financial = null,
        public readonly ?array $management = null,
        public readonly array $metadata = []
    ) {}
    
    /**
     * Convert DTO to array for API responses
     */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'role' => $this->role,
                'permissions' => $this->permissions,
            ],
            'metrics' => $this->metrics->toArray(),
            'widgets' => $this->widgets,
            'quick_actions' => $this->quickActions,
            'notifications' => $this->notifications,
            'schedule' => $this->schedule,
            'attendance' => $this->attendance,
            'financial' => $this->financial,
            'management' => $this->management,
            'metadata' => array_merge([
                'generated_at' => now()->toISOString(),
                'cache_ttl' => 300,
                'api_version' => 'v3',
            ], $this->metadata),
        ];
    }
    
    /**
     * Create DTO instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            role: $data['role'],
            permissions: $data['permissions'] ?? [],
            metrics: MetricsDTO::fromArray($data['metrics'] ?? []),
            widgets: $data['widgets'] ?? [],
            quickActions: $data['quick_actions'] ?? [],
            notifications: $data['notifications'] ?? [],
            schedule: $data['schedule'] ?? [],
            attendance: $data['attendance'] ?? [],
            financial: $data['financial'] ?? null,
            management: $data['management'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
    
    /**
     * Filter data based on user permissions
     */
    public function filterByPermissions(array $userPermissions): self
    {
        $filteredFinancial = null;
        $filteredManagement = null;
        
        // Only include financial data if user has permission
        if (in_array('view_financial_data', $userPermissions)) {
            $filteredFinancial = $this->financial;
        }
        
        // Only include management data for management roles
        if (in_array('view_management_dashboard', $userPermissions)) {
            $filteredManagement = $this->management;
        }
        
        return new self(
            userId: $this->userId,
            role: $this->role,
            permissions: $userPermissions,
            metrics: $this->metrics,
            widgets: $this->widgets,
            quickActions: $this->quickActions,
            notifications: $this->notifications,
            schedule: $this->schedule,
            attendance: $this->attendance,
            financial: $filteredFinancial,
            management: $filteredManagement,
            metadata: $this->metadata
        );
    }
    
    /**
     * Get dashboard sections available to the user
     */
    public function getAvailableSections(): array
    {
        $sections = ['metrics', 'widgets', 'notifications', 'schedule', 'attendance'];
        
        if ($this->financial !== null) {
            $sections[] = 'financial';
        }
        
        if ($this->management !== null) {
            $sections[] = 'management';
        }
        
        return $sections;
    }
    
    /**
     * Check if the dashboard has specific data section
     */
    public function hasSection(string $section): bool
    {
        return match ($section) {
            'metrics' => true,
            'widgets' => !empty($this->widgets),
            'notifications' => !empty($this->notifications),
            'schedule' => !empty($this->schedule),
            'attendance' => !empty($this->attendance),
            'financial' => $this->financial !== null,
            'management' => $this->management !== null,
            default => false,
        };
    }
    
    /**
     * Get summary statistics for the dashboard
     */
    public function getSummary(): array
    {
        return [
            'total_widgets' => count($this->widgets),
            'unread_notifications' => count(array_filter(
                $this->notifications, 
                fn($n) => !($n['read'] ?? false)
            )),
            'quick_actions_available' => count($this->quickActions),
            'has_financial_access' => $this->financial !== null,
            'has_management_access' => $this->management !== null,
            'sections_available' => count($this->getAvailableSections()),
        ];
    }
}