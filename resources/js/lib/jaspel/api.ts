/**
 * Unified Jaspel API Management Layer
 * Standardized API response handling patterns with multi-endpoint fallback
 */

import { 
  JaspelAPIResponse, 
  BaseJaspelItem, 
  DokterJaspelItem, 
  ParamedisJaspelItem,
  JaspelSummary, 
  JaspelVariant, 
  TransformationOptions,
  DashboardData,
  UnifiedJaspelItem
} from './types';
import { 
  safeNumber, 
  safeString, 
  calculateSummaryFromItems 
} from './utils';

/**
 * API Error class for better error handling
 */
export class JaspelAPIError extends Error {
  constructor(
    message: string,
    public status?: number,
    public endpoint?: string,
    public userMessage?: string
  ) {
    super(message);
    this.name = 'JaspelAPIError';
    this.userMessage = userMessage || message;
  }
}

/**
 * Unified Jaspel Data Manager
 */
export class JaspelDataManager {
  private static instance: JaspelDataManager;
  private cache: Map<string, any> = new Map();
  private requestInProgress: Map<string, Promise<any>> = new Map();

  private constructor() {}

  static getInstance(): JaspelDataManager {
    if (!JaspelDataManager.instance) {
      JaspelDataManager.instance = new JaspelDataManager();
    }
    return JaspelDataManager.instance;
  }

  /**
   * Get authentication headers for API requests
   */
  private getAuthHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };

    // Get authentication tokens from multiple sources
    const token = localStorage.getItem('auth_token') || 
                  localStorage.getItem('dokterku_auth_token') ||
                  localStorage.getItem('api_token') ||
                  localStorage.getItem('token') ||
                  sessionStorage.getItem('auth_token') ||
                  sessionStorage.getItem('dokterku_auth_token') ||
                  sessionStorage.getItem('api_token') ||
                  sessionStorage.getItem('token') ||
                  document.querySelector('meta[name="api-token"]')?.getAttribute('content');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return headers;
  }

  /**
   * Get endpoint URLs for different variants
   */
  private getEndpointUrls(variant: JaspelVariant, month?: number, year?: number): string[] {
    const params = new URLSearchParams();
    if (month) params.append('month', month.toString());
    if (year) params.append('year', year.toString());
    const paramString = params.toString() ? `?${params.toString()}` : '';

    if (variant === 'dokter') {
      return [
        `/api/v2/jaspel/validated/gaming-data${paramString}`,
        `/api/v2/jaspel/dokter-data${paramString}`,
        `/api/v2/dashboards/dokter/jaspel${paramString}`
      ];
    } else {
      return [
        `/paramedis/api/v2/jaspel/mobile-data${paramString}`,
        `/api/v2/jaspel/mobile-data-alt${paramString}`,
        `/api/v2/dashboards/paramedis/jaspel${paramString}`
      ];
    }
  }

  /**
   * Normalize different API response formats to unified format
   */
  private normalizeAPIResponse(
    response: any, 
    variant: JaspelVariant, 
    endpoint: string
  ): { items: BaseJaspelItem[]; summary: JaspelSummary } {
    let items: BaseJaspelItem[] = [];
    let summary: JaspelSummary = {
      total: 0,
      approved: 0,
      pending: 0,
      rejected: 0,
      count: { total: 0, approved: 0, pending: 0, rejected: 0 }
    };

    try {
      // Handle dokter gaming format
      if (variant === 'dokter' && response.data) {
        const { jaga_quests = [], achievement_tindakan = [], summary: apiSummary } = response.data;
        
        // Transform jaga quests
        const jagaItems: DokterJaspelItem[] = jaga_quests.map((item: any) => ({
          id: safeNumber(item.id, Math.random()),
          tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
          jenis: safeString(item.jenis_jaspel, 'jaga_umum'),
          jenis_jaspel: safeString(item.jenis_jaspel, 'jaga_umum'),
          jumlah: safeNumber(item.nominal, 0),
          nominal: safeNumber(item.nominal, 0),
          status: 'disetujui' as const,
          status_validasi: 'disetujui',
          keterangan: safeString(item.keterangan, 'Validated by Bendahara'),
          validated_by: safeString(item.validated_by),
          shift: this.mapJenisToShift(item.jenis_jaspel),
          jam: this.getShiftTime(item.jenis_jaspel),
          lokasi: this.getLocationFromJenis(item.jenis_jaspel),
          tindakan: this.mapJenisToTindakan(item.jenis_jaspel),
          durasi: this.getDurationFromJenis(item.jenis_jaspel),
          complexity: this.getComplexityFromJenis(item.jenis_jaspel),
          tim: [`dr. ${safeString(item.user_name, 'Dokter')}`],
          validation_guaranteed: true,
          total_pasien: safeNumber(item.total_pasien)
        }));

        // Transform achievement tindakan
        const tindakanItems: DokterJaspelItem[] = achievement_tindakan.map((item: any) => ({
          id: safeNumber(item.id, Math.random()),
          tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
          jenis: safeString(item.jenis_jaspel, 'tindakan'),
          jenis_jaspel: safeString(item.jenis_jaspel, 'tindakan'),
          jumlah: safeNumber(item.nominal, 0),
          nominal: safeNumber(item.nominal, 0),
          status: 'disetujui' as const,
          status_validasi: 'disetujui',
          keterangan: safeString(item.keterangan, 'Medical achievement'),
          tindakan: safeString(item.jenis, 'Medical procedure'),
          tindakan_id: safeNumber(item.tindakan_id),
          validation_guaranteed: true
        }));

        items = [...jagaItems, ...tindakanItems];
        summary = apiSummary || calculateSummaryFromItems(items);
      }
      
      // Handle paramedis format
      else if (variant === 'paramedis' && response.data?.jaspel_items) {
        items = response.data.jaspel_items.map((item: any) => ({
          id: safeString(item.id, Math.random().toString()),
          tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
          jenis: safeString(item.jenis_jaspel || item.jenis, ''),
          jumlah: safeNumber(item.nominal || item.jumlah, 0),
          status: safeString(item.status_validasi || item.status, 'pending'),
          keterangan: safeString(item.keterangan, ''),
          validated_by: item.validated_by || null,
          validated_at: item.validated_at || null
        }));
        summary = response.data.summary || calculateSummaryFromItems(items);
      }
      
      // Handle legacy paramedis format
      else if (variant === 'paramedis' && response.jaspel) {
        items = response.jaspel.map((item: any) => ({
          id: safeString(item.id, Math.random().toString()),
          tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
          jenis: safeString(item.jenis, ''),
          jumlah: safeNumber(item.jumlah, 0),
          status: safeString(item.status, 'pending'),
          keterangan: safeString(item.keterangan, ''),
          validated_by: item.validated_by || null,
          validated_at: item.validated_at || null
        }));
        summary = calculateSummaryFromItems(items);
      }

      // Handle direct data array
      else if (Array.isArray(response.data)) {
        items = response.data.map((item: any) => ({
          id: safeString(item.id, Math.random().toString()),
          tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
          jenis: safeString(item.jenis_jaspel || item.jenis, ''),
          jumlah: safeNumber(item.nominal || item.jumlah, 0),
          status: safeString(item.status_validasi || item.status, 'pending'),
          keterangan: safeString(item.keterangan, ''),
          validated_by: item.validated_by || null,
          validated_at: item.validated_at || null
        }));
        summary = calculateSummaryFromItems(items);
      }

      console.log(`✅ Normalized ${items.length} items from ${endpoint}`, {
        variant,
        endpoint,
        summary
      });

    } catch (error) {
      console.error('❌ Normalization error:', error, { response, variant, endpoint });
      throw new JaspelAPIError(
        'Failed to normalize API response',
        0,
        endpoint,
        'Data format error - please try again'
      );
    }

    return { items, summary };
  }

  /**
   * Fetch Jaspel data with multi-endpoint fallback
   */
  async fetchJaspelData(
    variant: JaspelVariant,
    month?: number,
    year?: number,
    options: TransformationOptions = {}
  ): Promise<{ items: UnifiedJaspelItem[]; summary: JaspelSummary; meta: any }> {
    const cacheKey = `${variant}-jaspel-${month || 'current'}-${year || 'current'}`;
    
    // Check if request is already in progress
    if (this.requestInProgress.has(cacheKey)) {
      return this.requestInProgress.get(cacheKey);
    }

    // Check cache first
    if (options.enableCache !== false) {
      const cached = this.cache.get(cacheKey);
      if (cached && Date.now() - cached.timestamp < 300000) { // 5 minute TTL
        return cached.data;
      }
    }

    const requestPromise = this.performJaspelRequest(variant, month, year, options);
    this.requestInProgress.set(cacheKey, requestPromise);

    try {
      const result = await requestPromise;
      
      // Cache the successful result
      if (options.enableCache !== false) {
        this.cache.set(cacheKey, {
          data: result,
          timestamp: Date.now()
        });
      }

      return result;
    } finally {
      this.requestInProgress.delete(cacheKey);
    }
  }

  /**
   * Perform the actual API request with fallback endpoints
   */
  private async performJaspelRequest(
    variant: JaspelVariant,
    month?: number,
    year?: number,
    options: TransformationOptions = {}
  ): Promise<{ items: UnifiedJaspelItem[]; summary: JaspelSummary; meta: any }> {
    const endpoints = this.getEndpointUrls(variant, month, year);
    const headers = this.getAuthHeaders();
    
    let lastError: JaspelAPIError | null = null;

    for (const endpoint of endpoints) {
      try {
        console.log(`📡 Trying Jaspel endpoint: ${endpoint}`);

        const response = await fetch(endpoint, {
          method: 'GET',
          headers,
          credentials: 'include'
        });

        if (!response.ok) {
          const errorText = await response.text();
          console.error(`❌ API Error ${response.status}:`, errorText);
          
          if (response.status === 401) {
            throw new JaspelAPIError(
              'Authentication failed',
              401,
              endpoint,
              'Sesi telah berakhir, silakan login kembali'
            );
          }
          
          if (response.status === 403) {
            throw new JaspelAPIError(
              'Permission denied',
              403,
              endpoint,
              'Tidak memiliki izin untuk mengakses data Jaspel'
            );
          }
          
          lastError = new JaspelAPIError(
            `HTTP error! status: ${response.status}`,
            response.status,
            endpoint,
            'Gagal mengambil data dari server'
          );
          continue; // Try next endpoint
        }

        const result = await response.json();
        console.log(`✅ Jaspel API Success with ${endpoint}:`, result);

        if (result.success === false) {
          lastError = new JaspelAPIError(
            result.message || 'API request unsuccessful',
            0,
            endpoint,
            result.message || 'Gagal mengambil data Jaspel'
          );
          continue; // Try next endpoint
        }

        // Normalize the response
        const { items, summary } = this.normalizeAPIResponse(result, variant, endpoint);

        return {
          items: items as UnifiedJaspelItem[],
          summary,
          meta: {
            endpoint_used: endpoint,
            month: month || new Date().getMonth() + 1,
            year: year || new Date().getFullYear(),
            variant,
            timestamp: Date.now()
          }
        };

      } catch (error) {
        console.error(`🔄 Endpoint ${endpoint} failed:`, error);
        
        if (error instanceof JaspelAPIError) {
          lastError = error;
        } else {
          lastError = new JaspelAPIError(
            error instanceof Error ? error.message : 'Network error',
            0,
            endpoint,
            'Gagal terhubung ke server'
          );
        }
        continue; // Try next endpoint
      }
    }

    // All endpoints failed
    console.error('🚨 All Jaspel API endpoints failed');
    throw lastError || new JaspelAPIError(
      'All endpoints failed',
      0,
      'multiple',
      'Semua endpoint gagal diakses'
    );
  }

  /**
   * Fetch dashboard data (primarily for paramedis)
   */
  async fetchDashboardData(): Promise<DashboardData> {
    const headers = this.getAuthHeaders();
    const endpoints = [
      '/test-paramedis-dashboard-api',
      '/api/new-paramedis/dashboard'
    ];

    for (const endpoint of endpoints) {
      try {
        const response = await fetch(endpoint, {
          method: 'GET',
          headers,
          credentials: 'include'
        });

        if (response.ok) {
          const result = await response.json();
          console.log('✅ Dashboard data:', result);
          return result;
        }
      } catch (error) {
        console.error(`Dashboard endpoint ${endpoint} failed:`, error);
      }
    }

    throw new JaspelAPIError(
      'Failed to fetch dashboard data',
      0,
      'dashboard',
      'Gagal mengambil data dashboard'
    );
  }

  /**
   * Clear cache
   */
  clearCache(): void {
    this.cache.clear();
    console.log('🧹 Jaspel cache cleared');
  }

  // Helper methods for data transformation
  private mapJenisToShift(jenis: string): string {
    if (!jenis) return 'Pagi';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('pagi')) return 'Pagi';
    if (safeJenis.includes('siang')) return 'Siang';
    if (safeJenis.includes('malam')) return 'Malam';
    return 'Pagi';
  }

  private getShiftTime(jenis: string): string {
    if (!jenis) return '07:00 - 14:00';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('pagi')) return '07:00 - 14:00';
    if (safeJenis.includes('siang')) return '14:00 - 20:00';
    if (safeJenis.includes('malam')) return '20:00 - 07:00';
    return '07:00 - 14:00';
  }

  private getLocationFromJenis(jenis: string): string {
    if (!jenis) return 'Klinik';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('igd') || safeJenis.includes('emergency')) return 'IGD';
    if (safeJenis.includes('icu')) return 'ICU';
    if (safeJenis.includes('poli')) return 'Poli Umum';
    if (safeJenis.includes('bedah')) return 'Ruang Bedah';
    return 'Klinik';
  }

  private mapJenisToTindakan(jenis: string): string {
    if (!jenis) return 'Tindakan Medis';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('konsultasi')) return 'Konsultasi Medis';
    if (safeJenis.includes('emergency')) return 'Tindakan Emergency';
    if (safeJenis.includes('operasi') || safeJenis.includes('bedah')) return 'Tindakan Bedah';
    return jenis.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  }

  private getDurationFromJenis(jenis: string): string {
    if (!jenis) return '1 jam';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('konsultasi')) return '30-45 menit';
    if (safeJenis.includes('emergency')) return '2-4 jam';
    if (safeJenis.includes('jaga')) return '7 jam';
    return '1 jam';
  }

  private getComplexityFromJenis(jenis: string): 'low' | 'medium' | 'high' | 'critical' {
    if (!jenis) return 'low';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('emergency') || safeJenis.includes('critical')) return 'critical';
    if (safeJenis.includes('khusus') || safeJenis.includes('operasi')) return 'high';
    if (safeJenis.includes('konsultasi')) return 'medium';
    return 'low';
  }
}

// Export singleton instance
export const jaspelDataManager = JaspelDataManager.getInstance();