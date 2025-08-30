<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardDataDTO;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Dashboard Service Interface
 * 
 * Defines the contract for unified dashboard services across all user roles.
 * This interface ensures consistent behavior and data structures regardless
 * of the specific dashboard implementation.
 */
interface DashboardServiceInterface
{
    /**
     * Get comprehensive dashboard data for the authenticated user
     * 
     * @param User $user The authenticated user
     * @param Request $request Request with optional filters and parameters
     * @return DashboardDataDTO Structured dashboard data
     */
    public function getDashboardData(User $user, Request $request): DashboardDataDTO;
    
    /**
     * Get role-specific content based on user permissions
     * 
     * @param User $user The authenticated user
     * @param array $permissions User's effective permissions
     * @return array Role-specific dashboard content
     */
    public function getRoleBasedContent(User $user, array $permissions): array;
    
    /**
     * Get user's key performance indicators and metrics
     * 
     * @param User $user The authenticated user
     * @param string $period Time period ('today', 'week', 'month', 'quarter')
     * @return array KPIs and metrics data
     */
    public function getUserMetrics(User $user, string $period = 'today'): array;
    
    /**
     * Get dashboard statistics for management roles
     * 
     * @param User $user The authenticated user (must have management role)
     * @param array $filters Optional filters for data
     * @return array Management statistics
     */
    public function getManagementStats(User $user, array $filters = []): array;
    
    /**
     * Get attendance-related dashboard data
     * 
     * @param User $user The authenticated user
     * @param string $date Target date (defaults to today)
     * @return array Attendance data and status
     */
    public function getAttendanceData(User $user, string $date = null): array;
    
    /**
     * Get financial overview for authorized users
     * 
     * @param User $user The authenticated user
     * @param string $period Time period for financial data
     * @return array Financial metrics and summaries
     */
    public function getFinancialOverview(User $user, string $period = 'month'): array;
    
    /**
     * Get schedule and shift information
     * 
     * @param User $user The authenticated user
     * @param string $startDate Start date for schedule lookup
     * @param string $endDate End date for schedule lookup
     * @return array Schedule data
     */
    public function getScheduleData(User $user, string $startDate = null, string $endDate = null): array;
    
    /**
     * Get notification summary for the dashboard
     * 
     * @param User $user The authenticated user
     * @param int $limit Maximum number of notifications to return
     * @return array Notification data
     */
    public function getNotificationSummary(User $user, int $limit = 10): array;
    
    /**
     * Get quick actions available to the user
     * 
     * @param User $user The authenticated user
     * @return array Available quick actions based on role and permissions
     */
    public function getQuickActions(User $user): array;
    
    /**
     * Get dashboard widgets configuration for the user
     * 
     * @param User $user The authenticated user
     * @return array Widget configuration and data
     */
    public function getWidgetConfiguration(User $user): array;
    
    /**
     * Check if user can access specific dashboard features
     * 
     * @param User $user The authenticated user
     * @param string $feature Feature identifier
     * @return bool Whether the user can access the feature
     */
    public function canAccessFeature(User $user, string $feature): bool;
    
    /**
     * Get dashboard cache key for the user
     * 
     * @param User $user The authenticated user
     * @param string $section Dashboard section identifier
     * @return string Cache key
     */
    public function getCacheKey(User $user, string $section): string;
    
    /**
     * Invalidate dashboard cache for the user
     * 
     * @param User $user The authenticated user
     * @param string|null $section Specific section to invalidate (null for all)
     * @return bool Success status
     */
    public function invalidateCache(User $user, string $section = null): bool;
}