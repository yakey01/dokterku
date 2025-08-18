/**
 * Unified Jaspel Type System
 * Shared types and interfaces for dokter and paramedis Jaspel components
 */

// Base Jaspel interface - common fields across all variants
export interface BaseJaspelItem {
  id: string | number;
  tanggal: string;           // Display date
  jenis: string;             // Jaspel type/category
  jumlah: number;            // Amount/nominal value
  status: JaspelStatus;      // Validation status
  keterangan?: string;       // Description/notes
  validated_by?: string;     // Validator name
  validated_at?: string;     // Validation timestamp
}

// Dokter-specific Jaspel interface
export interface DokterJaspelItem extends BaseJaspelItem {
  jenis_jaspel: string;      // Specific Jaspel type
  nominal: number;           // Amount (dokter format)
  status_validasi: string;   // Validation status (dokter format)
  shift?: string;            // Work shift
  jam?: string;              // Time range
  lokasi?: string;           // Location
  tindakan?: string;         // Medical action
  durasi?: string;           // Duration
  complexity?: ComplexityLevel;  // Task complexity
  tim?: string[];            // Team members
  total_pasien?: number;     // Patient count
  validation_guaranteed?: boolean;  // Bendahara validation flag
  jaspel_breakdown?: JaspelBreakdown[];  // Detailed breakdown
  tindakan_id?: number;      // Medical action ID
}

// Paramedis-specific Jaspel interface
export interface ParamedisJaspelItem extends BaseJaspelItem {
  // Paramedis uses base interface structure
  // Additional paramedis-specific fields can be added here
}

// Dashboard-specific data interface
export interface DashboardData {
  jaspel_monthly: number;
  pending_jaspel: number;
  approved_jaspel: number;
  growth_percent: number;
  paramedis_name: string;
  last_month_total: number;
  daily_average?: number;
  jaspel_weekly?: number;
  attendance_rate?: number;
  shifts_this_month?: number;
  period_info?: PeriodInfo;
}

// Period information for dashboard
export interface PeriodInfo {
  month_progress: number;
  days_passed: number;
  days_in_month: number;
  current_month: number;
  current_year: number;
}

// Jaspel summary statistics
export interface JaspelSummary {
  total: number;
  approved: number;
  pending: number;
  rejected: number;
  count: {
    total: number;
    approved: number;
    pending: number;
    rejected: number;
  };
  // Additional fields for different variants
  total_paid?: number;      // Paramedis format
  total_pending?: number;   // Paramedis format
  total_rejected?: number;  // Paramedis format
  count_paid?: number;      // Paramedis format
  count_pending?: number;   // Paramedis format
  count_rejected?: number;  // Paramedis format
  jumlah_pasien_total?: number;  // Patient count coordination
}

// Jaspel breakdown for detailed view
export interface JaspelBreakdown {
  jenis_jaspel: string;
  nominal: number;
  source: string;
}

// API response structures
export interface JaspelAPIResponse {
  success: boolean;
  message: string;
  data: {
    jaspel_items?: BaseJaspelItem[];
    summary?: JaspelSummary;
    gaming_stats?: GamingStats;
    jaga_quests?: DokterJaspelItem[];
    achievement_tindakan?: DokterJaspelItem[];
    validation_guarantee?: ValidationGuarantee;
    // Legacy format support
    jaspel?: ParamedisJaspelItem[];
  };
  meta?: {
    month: number;
    year: number;
    user_name: string;
    endpoint_used?: string;
  };
}

// Gaming-related types
export interface GamingStats {
  total_rewards: number;
  achievements_unlocked: number;
  quest_completion_rate: number;
  current_level: number;
  xp_points: number;
}

// Validation guarantee for financial accuracy
export interface ValidationGuarantee {
  all_amounts_validated: boolean;
  financial_accuracy: 'guaranteed' | 'pending' | 'partial';
  bendahara_approved: boolean;
  validation_timestamp?: string;
}

// Real-time notification types
export interface RealtimeNotification {
  id: number;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
  timestamp: string;
  data?: any;
}

// Enums and type unions
export type JaspelStatus = 
  | 'pending' 
  | 'paid' 
  | 'rejected' 
  | 'disetujui' 
  | 'ditolak' 
  | 'completed' 
  | 'scheduled';

export type ComplexityLevel = 'low' | 'medium' | 'high' | 'critical';

export type JaspelVariant = 'dokter' | 'paramedis';

export type GrowthDirection = 'up' | 'down' | 'stable';

// Badge configuration for gaming UI
export interface BadgeConfig {
  text: string;
  color: string;
  bgColor: string;
  borderColor: string;
  icon?: React.ComponentType<any>;
  animated?: boolean;
  gradient?: string;
  textColor?: string;
  glowColor?: string;
  bgGlow?: string;
  pulse?: string;
  priority?: 'low' | 'normal' | 'high' | 'critical';
}

// Hook return types
export interface UseJaspelDataReturn {
  data: BaseJaspelItem[];
  summary: JaspelSummary;
  loading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
  clearError: () => void;
}

export interface UseJaspelManagerReturn extends UseJaspelDataReturn {
  // Enhanced functionality
  refreshData: (force?: boolean) => Promise<void>;
  updateItem: (id: string | number, updates: Partial<BaseJaspelItem>) => void;
  removeItem: (id: string | number) => void;
  addItem: (item: BaseJaspelItem) => void;
  
  // Filtering and sorting
  filteredData: BaseJaspelItem[];
  sortedData: BaseJaspelItem[];
  setFilter: (filter: (item: BaseJaspelItem) => boolean) => void;
  setSortBy: (sort: (a: BaseJaspelItem, b: BaseJaspelItem) => number) => void;
  
  // Advanced features
  dashboardData?: DashboardData;
  realtimeConnected: boolean;
  notifications: RealtimeNotification[];
  lastUpdateTime: string;
  isRefreshing: boolean;
  
  // Utility functions
  retryFetch: () => Promise<void>;
  clearCache: () => void;
}

// Performance monitoring types
export interface JaspelPerformanceMetrics {
  apiResponseTime: number;
  totalRequests: number;
  cacheHits: number;
  errorRate: number;
  avgLoadTime: number;
  realtimeLatency: number;
}

// Cache configuration
export interface CacheConfig {
  ttl: number;              // Time to live in milliseconds
  maxSize: number;          // Maximum cache entries
  strategy: 'lru' | 'fifo'; // Cache eviction strategy
}

// Transformation options
export interface TransformationOptions {
  enableCache?: boolean;
  preserveRawData?: boolean;
  strictValidation?: boolean;
  includeMetrics?: boolean;
  variant?: JaspelVariant;
}

// Type guards for runtime type checking
export const isDokterJaspelItem = (item: BaseJaspelItem): item is DokterJaspelItem => {
  return 'jenis_jaspel' in item || 'nominal' in item || 'status_validasi' in item;
};

export const isParamedisJaspelItem = (item: BaseJaspelItem): item is ParamedisJaspelItem => {
  return !isDokterJaspelItem(item);
};

export const isValidJaspelStatus = (status: string): status is JaspelStatus => {
  const validStatuses: JaspelStatus[] = [
    'pending', 'paid', 'rejected', 'disetujui', 'ditolak', 'completed', 'scheduled'
  ];
  return validStatuses.includes(status as JaspelStatus);
};

// Utility type for component props
export type JaspelComponentProps<T extends BaseJaspelItem = BaseJaspelItem> = {
  data: T[];
  variant: JaspelVariant;
  onItemClick?: (item: T) => void;
  onRefresh?: () => void;
  className?: string;
  loading?: boolean;
  error?: string | null;
};

// Export unified type for external use
export type UnifiedJaspelItem = DokterJaspelItem | ParamedisJaspelItem;