/**
 * Unified Data Transformation Layer
 * Standardized data transformation and normalization for all Jaspel variants
 */

import { 
  BaseJaspelItem,
  DokterJaspelItem,
  ParamedisJaspelItem,
  JaspelSummary,
  DashboardData,
  JaspelVariant,
  JaspelStatus,
  ComplexityLevel,
  UnifiedJaspelItem,
  TransformationOptions,
  JaspelAPIResponse
} from './types';
import { 
  safeNumber, 
  safeString, 
  calculateSummaryFromItems 
} from './utils';

// Transformation result interface
export interface TransformationResult<T = UnifiedJaspelItem> {
  items: T[];
  summary: JaspelSummary;
  metadata: TransformationMetadata;
  warnings: string[];
  errors: string[];
}

export interface TransformationMetadata {
  totalInputRecords: number;
  successfulTransformations: number;
  failedTransformations: number;
  skippedRecords: number;
  transformationTime: number;
  dataQualityScore: number;
  variant: JaspelVariant;
  sourceFormat: string;
  transformationRules: string[];
}

// Data quality metrics
export interface DataQualityMetrics {
  completeness: number;        // % of non-null required fields
  consistency: number;         // % of data following expected patterns
  validity: number;           // % of data passing validation rules
  accuracy: number;           // % of data within expected ranges
  overall: number;            // Combined quality score
}

/**
 * Base transformer class with common functionality
 */
abstract class BaseJaspelTransformer {
  protected warnings: string[] = [];
  protected errors: string[] = [];
  protected startTime: number = 0;

  protected abstract variant: JaspelVariant;

  protected logWarning(message: string, context?: any): void {
    this.warnings.push(`${message}${context ? ` - Context: ${JSON.stringify(context)}` : ''}`);
  }

  protected logError(message: string, context?: any): void {
    this.errors.push(`${message}${context ? ` - Context: ${JSON.stringify(context)}` : ''}`);
  }

  protected startTransformation(): void {
    this.startTime = performance.now();
    this.warnings = [];
    this.errors = [];
  }

  protected endTransformation(): number {
    return performance.now() - this.startTime;
  }

  protected validateRequiredFields(item: any, requiredFields: string[]): boolean {
    for (const field of requiredFields) {
      if (item[field] === undefined || item[field] === null || item[field] === '') {
        this.logWarning(`Missing required field: ${field}`, item);
        return false;
      }
    }
    return true;
  }

  protected normalizeStatus(status: string): JaspelStatus {
    const normalizedStatus = safeString(status).toLowerCase().trim();
    
    const statusMap: Record<string, JaspelStatus> = {
      'disetujui': 'disetujui',
      'approved': 'disetujui',
      'paid': 'paid',
      'completed': 'completed',
      'success': 'disetujui',
      'validated': 'disetujui',
      'pending': 'pending',
      'waiting': 'pending',
      'scheduled': 'scheduled',
      'ditolak': 'ditolak',
      'rejected': 'rejected',
      'failed': 'ditolak',
      'denied': 'ditolak'
    };

    return statusMap[normalizedStatus] || 'pending';
  }

  protected normalizeComplexity(complexity: string): ComplexityLevel {
    const normalizedComplexity = safeString(complexity).toLowerCase().trim();
    
    const complexityMap: Record<string, ComplexityLevel> = {
      'low': 'low',
      'rendah': 'low',
      'simple': 'low',
      'mudah': 'low',
      'medium': 'medium',
      'sedang': 'medium',
      'normal': 'medium',
      'standard': 'medium',
      'high': 'high',
      'tinggi': 'high',
      'complex': 'high',
      'rumit': 'high',
      'critical': 'critical',
      'kritis': 'critical',
      'urgent': 'critical',
      'darurat': 'critical'
    };

    return complexityMap[normalizedComplexity] || 'low';
  }

  protected calculateDataQuality(items: any[]): DataQualityMetrics {
    if (items.length === 0) {
      return {
        completeness: 0,
        consistency: 0,
        validity: 0,
        accuracy: 0,
        overall: 0
      };
    }

    let completenessScore = 0;
    let consistencyScore = 0;
    let validityScore = 0;
    let accuracyScore = 0;

    const requiredFields = ['id', 'tanggal', 'jenis', 'jumlah', 'status'];

    items.forEach(item => {
      // Completeness: check required fields
      const completeFields = requiredFields.filter(field => 
        item[field] !== undefined && item[field] !== null && item[field] !== ''
      ).length;
      completenessScore += (completeFields / requiredFields.length) * 100;

      // Consistency: check data format consistency
      let consistencyPoints = 0;
      
      // Date format consistency
      if (item.tanggal && /^\d{4}-\d{2}-\d{2}/.test(item.tanggal)) {
        consistencyPoints += 25;
      }
      
      // Amount consistency (should be positive number)
      if (typeof item.jumlah === 'number' && item.jumlah >= 0) {
        consistencyPoints += 25;
      }
      
      // Status consistency (should be known status)
      if (typeof item.status === 'string' && item.status.length > 0) {
        consistencyPoints += 25;
      }
      
      // ID consistency (should exist and be unique)
      if (item.id !== undefined && item.id !== null) {
        consistencyPoints += 25;
      }
      
      consistencyScore += consistencyPoints;

      // Validity: check business rules
      let validityPoints = 0;
      
      // Valid date range (not in future, not too old)
      const itemDate = new Date(item.tanggal);
      const now = new Date();
      const oneYearAgo = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
      
      if (itemDate <= now && itemDate >= oneYearAgo) {
        validityPoints += 50;
      }
      
      // Valid amount range (reasonable medical fees)
      if (item.jumlah >= 10000 && item.jumlah <= 10000000) {
        validityPoints += 50;
      }
      
      validityScore += validityPoints;

      // Accuracy: check data reasonableness
      let accuracyPoints = 0;
      
      // Reasonable description length
      if (item.keterangan && item.keterangan.length >= 3 && item.keterangan.length <= 500) {
        accuracyPoints += 50;
      } else if (!item.keterangan) {
        accuracyPoints += 25; // Optional field, partial credit
      }
      
      // Status matches expected patterns
      const validStatuses = ['pending', 'disetujui', 'ditolak', 'paid', 'completed', 'scheduled', 'rejected'];
      if (validStatuses.includes(this.normalizeStatus(item.status))) {
        accuracyPoints += 50;
      }
      
      accuracyScore += accuracyPoints;
    });

    const completeness = completenessScore / items.length;
    const consistency = consistencyScore / items.length;
    const validity = validityScore / items.length;
    const accuracy = accuracyScore / items.length;
    const overall = (completeness + consistency + validity + accuracy) / 4;

    return {
      completeness: Math.round(completeness * 100) / 100,
      consistency: Math.round(consistency * 100) / 100,
      validity: Math.round(validity * 100) / 100,
      accuracy: Math.round(accuracy * 100) / 100,
      overall: Math.round(overall * 100) / 100
    };
  }

  protected generateMetadata(
    inputCount: number,
    outputCount: number,
    transformationTime: number,
    sourceFormat: string,
    transformationRules: string[],
    qualityMetrics: DataQualityMetrics
  ): TransformationMetadata {
    return {
      totalInputRecords: inputCount,
      successfulTransformations: outputCount,
      failedTransformations: Math.max(0, inputCount - outputCount),
      skippedRecords: this.warnings.filter(w => w.includes('skipped')).length,
      transformationTime,
      dataQualityScore: qualityMetrics.overall,
      variant: this.variant,
      sourceFormat,
      transformationRules
    };
  }
}

/**
 * Dokter variant transformer
 */
class DokterJaspelTransformer extends BaseJaspelTransformer {
  protected variant: JaspelVariant = 'dokter';

  transformGamingAPIResponse(response: any): TransformationResult<DokterJaspelItem> {
    this.startTransformation();
    
    const transformationRules = [
      'gaming_api_format',
      'jaga_quest_mapping',
      'achievement_tindakan_mapping',
      'complexity_inference',
      'team_extraction'
    ];

    const items: DokterJaspelItem[] = [];

    try {
      const { jaga_quests = [], achievement_tindakan = [] } = response.data || {};
      
      // Transform jaga quests
      jaga_quests.forEach((item: any) => {
        try {
          if (!this.validateRequiredFields(item, ['id', 'tanggal', 'jenis_jaspel', 'nominal'])) {
            return;
          }

          const transformedItem: DokterJaspelItem = {
            id: safeNumber(item.id, Math.random()),
            tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
            jenis: safeString(item.jenis_jaspel, 'jaga_umum'),
            jenis_jaspel: safeString(item.jenis_jaspel, 'jaga_umum'),
            jumlah: safeNumber(item.nominal, 0),
            nominal: safeNumber(item.nominal, 0),
            status: this.normalizeStatus('disetujui'),
            status_validasi: 'disetujui',
            keterangan: safeString(item.keterangan, 'Validated by Bendahara'),
            validated_by: safeString(item.validated_by),
            shift: this.mapJenisToShift(item.jenis_jaspel),
            jam: this.getShiftTime(item.jenis_jaspel),
            lokasi: this.getLocationFromJenis(item.jenis_jaspel),
            tindakan: this.mapJenisToTindakan(item.jenis_jaspel),
            durasi: this.getDurationFromJenis(item.jenis_jaspel),
            complexity: this.getComplexityFromJenis(item.jenis_jaspel),
            tim: this.extractTeamMembers(item),
            validation_guaranteed: true,
            total_pasien: safeNumber(item.total_pasien)
          };

          items.push(transformedItem);
        } catch (error) {
          this.logError(`Failed to transform jaga quest item`, { item, error: error.message });
        }
      });

      // Transform achievement tindakan
      achievement_tindakan.forEach((item: any) => {
        try {
          if (!this.validateRequiredFields(item, ['id', 'tanggal', 'nominal'])) {
            return;
          }

          const transformedItem: DokterJaspelItem = {
            id: safeNumber(item.id, Math.random()),
            tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
            jenis: safeString(item.jenis_jaspel || item.jenis, 'tindakan'),
            jenis_jaspel: safeString(item.jenis_jaspel || item.jenis, 'tindakan'),
            jumlah: safeNumber(item.nominal, 0),
            nominal: safeNumber(item.nominal, 0),
            status: this.normalizeStatus('disetujui'),
            status_validasi: 'disetujui',
            keterangan: safeString(item.keterangan, 'Medical achievement'),
            tindakan: safeString(item.jenis, 'Medical procedure'),
            tindakan_id: safeNumber(item.tindakan_id),
            validation_guaranteed: true,
            complexity: this.getComplexityFromJenis(item.jenis)
          };

          items.push(transformedItem);
        } catch (error) {
          this.logError(`Failed to transform achievement item`, { item, error: error.message });
        }
      });

    } catch (error) {
      this.logError(`Failed to transform gaming API response`, { error: error.message });
    }

    const transformationTime = this.endTransformation();
    const qualityMetrics = this.calculateDataQuality(items);
    const summary = calculateSummaryFromItems(items);

    return {
      items,
      summary,
      metadata: this.generateMetadata(
        (response.data?.jaga_quests?.length || 0) + (response.data?.achievement_tindakan?.length || 0),
        items.length,
        transformationTime,
        'gaming_api',
        transformationRules,
        qualityMetrics
      ),
      warnings: this.warnings,
      errors: this.errors
    };
  }

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

  private getComplexityFromJenis(jenis: string): ComplexityLevel {
    if (!jenis) return 'low';
    const safeJenis = jenis.toLowerCase();
    if (safeJenis.includes('emergency') || safeJenis.includes('critical')) return 'critical';
    if (safeJenis.includes('khusus') || safeJenis.includes('operasi')) return 'high';
    if (safeJenis.includes('konsultasi')) return 'medium';
    return 'low';
  }

  private extractTeamMembers(item: any): string[] {
    const team: string[] = [];
    
    if (item.user_name) {
      team.push(`dr. ${safeString(item.user_name, 'Dokter')}`);
    }
    
    if (item.team_members && Array.isArray(item.team_members)) {
      team.push(...item.team_members.map((member: any) => safeString(member.name || member)));
    }
    
    return team.length > 0 ? team : [`dr. ${safeString(item.user_name, 'Dokter')}`];
  }
}

/**
 * Paramedis variant transformer
 */
class ParamedisJaspelTransformer extends BaseJaspelTransformer {
  protected variant: JaspelVariant = 'paramedis';

  transformMobileAPIResponse(response: any): TransformationResult<ParamedisJaspelItem> {
    this.startTransformation();
    
    const transformationRules = [
      'mobile_api_format',
      'paramedis_data_mapping',
      'status_normalization',
      'amount_validation'
    ];

    const items: ParamedisJaspelItem[] = [];

    try {
      let dataArray: any[] = [];
      
      // Handle different response formats
      if (response.data?.jaspel_items) {
        dataArray = response.data.jaspel_items;
      } else if (response.jaspel) {
        dataArray = response.jaspel;
      } else if (Array.isArray(response.data)) {
        dataArray = response.data;
      } else if (Array.isArray(response)) {
        dataArray = response;
      }

      dataArray.forEach((item: any) => {
        try {
          if (!this.validateRequiredFields(item, ['id', 'tanggal', 'jenis'])) {
            return;
          }

          const transformedItem: ParamedisJaspelItem = {
            id: safeString(item.id, Math.random().toString()),
            tanggal: safeString(item.tanggal, new Date().toISOString().split('T')[0]),
            jenis: safeString(item.jenis_jaspel || item.jenis, ''),
            jumlah: safeNumber(item.nominal || item.jumlah, 0),
            status: this.normalizeStatus(item.status_validasi || item.status),
            keterangan: safeString(item.keterangan, ''),
            validated_by: item.validated_by || null,
            validated_at: item.validated_at || null
          };

          items.push(transformedItem);
        } catch (error) {
          this.logError(`Failed to transform paramedis item`, { item, error: error.message });
        }
      });

    } catch (error) {
      this.logError(`Failed to transform mobile API response`, { error: error.message });
    }

    const transformationTime = this.endTransformation();
    const qualityMetrics = this.calculateDataQuality(items);
    const summary = calculateSummaryFromItems(items);

    return {
      items,
      summary,
      metadata: this.generateMetadata(
        Array.isArray(response.data) ? response.data.length : 
        response.data?.jaspel_items?.length || 
        response.jaspel?.length || 0,
        items.length,
        transformationTime,
        'mobile_api',
        transformationRules,
        qualityMetrics
      ),
      warnings: this.warnings,
      errors: this.errors
    };
  }

  transformDashboardData(response: any): DashboardData {
    try {
      return {
        jaspel_monthly: safeNumber(response.jaspel_monthly, 0),
        pending_jaspel: safeNumber(response.pending_jaspel, 0),
        approved_jaspel: safeNumber(response.approved_jaspel, 0),
        growth_percent: safeNumber(response.growth_percent, 0),
        paramedis_name: safeString(response.paramedis_name, 'Paramedis'),
        last_month_total: safeNumber(response.last_month_total, 0),
        daily_average: safeNumber(response.daily_average),
        jaspel_weekly: safeNumber(response.jaspel_weekly),
        attendance_rate: safeNumber(response.attendance_rate),
        shifts_this_month: safeNumber(response.shifts_this_month)
      };
    } catch (error) {
      this.logError(`Failed to transform dashboard data`, { error: error.message });
      
      // Return default dashboard data
      return {
        jaspel_monthly: 0,
        pending_jaspel: 0,
        approved_jaspel: 0,
        growth_percent: 0,
        paramedis_name: 'Paramedis',
        last_month_total: 0
      };
    }
  }
}

/**
 * Universal data transformer factory
 */
export class JaspelDataTransformer {
  private static dokterTransformer = new DokterJaspelTransformer();
  private static paramedisTransformer = new ParamedisJaspelTransformer();

  static transformAPIResponse(
    response: any,
    variant: JaspelVariant,
    options: TransformationOptions = {}
  ): TransformationResult {
    const startTime = performance.now();
    
    try {
      let result: TransformationResult;

      if (variant === 'dokter') {
        result = this.dokterTransformer.transformGamingAPIResponse(response);
      } else {
        result = this.paramedisTransformer.transformMobileAPIResponse(response);
      }

      // Apply post-processing if specified
      if (options.strictValidation) {
        result = this.applyStrictValidation(result);
      }

      if (options.includeMetrics) {
        result.metadata.transformationTime = performance.now() - startTime;
      }

      return result;

    } catch (error) {
      console.error('Transformation failed:', error);
      
      // Return empty result with error
      return {
        items: [],
        summary: {
          total: 0,
          approved: 0,
          pending: 0,
          rejected: 0,
          count: { total: 0, approved: 0, pending: 0, rejected: 0 }
        },
        metadata: {
          totalInputRecords: 0,
          successfulTransformations: 0,
          failedTransformations: 1,
          skippedRecords: 0,
          transformationTime: performance.now() - startTime,
          dataQualityScore: 0,
          variant,
          sourceFormat: 'unknown',
          transformationRules: []
        },
        warnings: [],
        errors: [`Critical transformation error: ${error.message}`]
      };
    }
  }

  static transformDashboardData(
    response: any,
    variant: JaspelVariant
  ): DashboardData | null {
    if (variant === 'paramedis') {
      return this.paramedisTransformer.transformDashboardData(response);
    }
    return null;
  }

  private static applyStrictValidation(result: TransformationResult): TransformationResult {
    const validItems = result.items.filter(item => {
      // Strict validation rules
      if (item.jumlah <= 0) return false;
      if (!item.tanggal || new Date(item.tanggal) > new Date()) return false;
      if (!item.jenis || item.jenis.length < 2) return false;
      return true;
    });

    return {
      ...result,
      items: validItems,
      summary: calculateSummaryFromItems(validItems),
      metadata: {
        ...result.metadata,
        successfulTransformations: validItems.length,
        failedTransformations: result.items.length - validItems.length
      }
    };
  }

  static validateTransformationResult(result: TransformationResult): {
    isValid: boolean;
    issues: string[];
    recommendations: string[];
  } {
    const issues: string[] = [];
    const recommendations: string[] = [];

    // Check data quality
    if (result.metadata.dataQualityScore < 70) {
      issues.push(`Low data quality score: ${result.metadata.dataQualityScore}%`);
      recommendations.push('Review data source and improve data validation');
    }

    // Check transformation success rate
    const successRate = (result.metadata.successfulTransformations / result.metadata.totalInputRecords) * 100;
    if (successRate < 90) {
      issues.push(`Low transformation success rate: ${successRate.toFixed(1)}%`);
      recommendations.push('Investigate transformation failures and improve error handling');
    }

    // Check for errors
    if (result.errors.length > 0) {
      issues.push(`${result.errors.length} transformation errors occurred`);
      recommendations.push('Address transformation errors to improve data reliability');
    }

    // Check data consistency
    if (result.items.length === 0 && result.metadata.totalInputRecords > 0) {
      issues.push('No items were successfully transformed');
      recommendations.push('Check input data format and transformation rules');
    }

    return {
      isValid: issues.length === 0,
      issues,
      recommendations
    };
  }
}

// Export convenience functions
export const transformJaspelData = (
  response: any,
  variant: JaspelVariant,
  options?: TransformationOptions
): TransformationResult => {
  return JaspelDataTransformer.transformAPIResponse(response, variant, options);
};

export const transformDashboardData = (
  response: any,
  variant: JaspelVariant
): DashboardData | null => {
  return JaspelDataTransformer.transformDashboardData(response, variant);
};

// Export types
export type { TransformationResult, TransformationMetadata, DataQualityMetrics };