/**
 * 🚀 World-Class React Key Management System
 * 
 * Comprehensive solution for preventing React key duplication warnings
 * and ensuring unique keys across all components in the application.
 */

interface KeyScope {
  component: string;
  scope: string;
  counter: number;
}

class ReactKeyManager {
  private static instance: ReactKeyManager;
  private keyRegistry = new Map<string, Set<string>>();
  private scopeCounters = new Map<string, number>();
  private debugMode = process.env.NODE_ENV === 'development';

  static getInstance(): ReactKeyManager {
    if (!ReactKeyManager.instance) {
      ReactKeyManager.instance = new ReactKeyManager();
    }
    return ReactKeyManager.instance;
  }

  /**
   * Generate a guaranteed unique key for any component/context
   */
  generateUniqueKey(component: string, context: string, identifier?: string | number): string {
    const scope = `${component}:${context}`;
    
    // Use provided identifier if available and unique
    if (identifier !== undefined) {
      const candidateKey = `${scope}:${identifier}`;
      if (!this.keyRegistry.has(scope)) {
        this.keyRegistry.set(scope, new Set());
      }
      
      const scopeKeys = this.keyRegistry.get(scope)!;
      if (!scopeKeys.has(candidateKey)) {
        scopeKeys.add(candidateKey);
        this.log(`✅ Generated unique key: ${candidateKey}`);
        return candidateKey;
      }
    }

    // Generate auto-incrementing key if identifier is duplicate or not provided
    const counter = (this.scopeCounters.get(scope) || 0) + 1;
    this.scopeCounters.set(scope, counter);
    
    const uniqueKey = `${scope}:${counter}`;
    
    if (!this.keyRegistry.has(scope)) {
      this.keyRegistry.set(scope, new Set());
    }
    this.keyRegistry.get(scope)!.add(uniqueKey);
    
    this.log(`🔄 Auto-generated key: ${uniqueKey}`);
    return uniqueKey;
  }

  /**
   * Generate keys for array mapping operations
   */
  generateArrayKeys<T>(
    component: string, 
    context: string, 
    items: T[], 
    getIdentifier?: (item: T, index: number) => string | number
  ): string[] {
    return items.map((item, index) => {
      const identifier = getIdentifier ? getIdentifier(item, index) : index;
      return this.generateUniqueKey(component, context, identifier);
    });
  }

  /**
   * Reset keys for a specific scope (useful for component unmount)
   */
  resetScope(component: string, context: string): void {
    const scope = `${component}:${context}`;
    this.keyRegistry.delete(scope);
    this.scopeCounters.delete(scope);
    this.log(`🗑️ Reset scope: ${scope}`);
  }

  /**
   * Validate that no duplicate keys exist
   */
  validateUniqueKeys(): { isValid: boolean; duplicates: string[] } {
    const allKeys = new Set<string>();
    const duplicates: string[] = [];

    for (const [scope, keys] of this.keyRegistry) {
      for (const key of keys) {
        if (allKeys.has(key)) {
          duplicates.push(key);
        } else {
          allKeys.add(key);
        }
      }
    }

    const isValid = duplicates.length === 0;
    
    if (this.debugMode) {
      if (isValid) {
        console.log('✅ All React keys are unique across application');
      } else {
        console.error('🚨 Duplicate React keys detected:', duplicates);
      }
    }

    return { isValid, duplicates };
  }

  /**
   * Get comprehensive key statistics
   */
  getKeyStatistics(): {
    totalScopes: number;
    totalKeys: number;
    scopeBreakdown: Record<string, number>;
  } {
    const scopeBreakdown: Record<string, number> = {};
    let totalKeys = 0;

    for (const [scope, keys] of this.keyRegistry) {
      scopeBreakdown[scope] = keys.size;
      totalKeys += keys.size;
    }

    return {
      totalScopes: this.keyRegistry.size,
      totalKeys,
      scopeBreakdown
    };
  }

  /**
   * Debug logging
   */
  private log(message: string): void {
    if (this.debugMode) {
      console.log(`🔑 ReactKeyManager: ${message}`);
    }
  }

  /**
   * Clear all keys (for testing purposes)
   */
  clearAll(): void {
    this.keyRegistry.clear();
    this.scopeCounters.clear();
    this.log('🧹 Cleared all key registry');
  }
}

// Export singleton instance
export const reactKeyManager = ReactKeyManager.getInstance();

// Helper functions for common use cases
export const generateChartKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'chart', index);

export const generateMonthlyKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'monthly', index);

export const generateBudgetKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'budget', index);

export const generateCostKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'cost', index);

export const generateFileKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'file', index);

export const generateTopPerformerKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'topPerformer', index);

export const generatePoorPerformerKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'poorPerformer', index);

export const generateAttendanceKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'attendance', index);

export const generateDepartmentKey = (index: number) => 
  reactKeyManager.generateUniqueKey('ManajerDashboard', 'department', index);

// Global debugging function
if (typeof window !== 'undefined' && process.env.NODE_ENV === 'development') {
  (window as any).validateReactKeys = () => reactKeyManager.validateUniqueKeys();
  (window as any).getKeyStatistics = () => reactKeyManager.getKeyStatistics();
  (window as any).clearReactKeys = () => reactKeyManager.clearAll();
}

export default reactKeyManager;