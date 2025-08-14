/**
 * Safe Object Access Utility
 * Provides bulletproof methods for accessing nested object properties
 * Specifically designed to prevent "object can not be found here" errors
 */

interface SafeAccessOptions {
  logWarnings?: boolean;
  defaultValue?: any;
  throwOnError?: boolean;
}

class SafeObjectAccess {
  /**
   * Safely access nested object properties with fallback
   * @param obj The object to access
   * @param path The property path (e.g., 'user.profile.name' or ['user', 'profile', 'name'])
   * @param options Configuration options
   * @returns The value or defaultValue if path doesn't exist
   */
  static get(obj: any, path: string | string[], options: SafeAccessOptions = {}): any {
    const { logWarnings = false, defaultValue = null, throwOnError = false } = options;
    
    try {
      // Handle null/undefined object
      if (!obj || typeof obj !== 'object') {
        if (logWarnings) {
          console.warn('⚠️ SafeObjectAccess: Invalid object provided', obj);
        }
        return defaultValue;
      }

      // Convert string path to array
      const pathArray = Array.isArray(path) ? path : path.split('.');
      
      // Navigate through the path
      let current = obj;
      for (let i = 0; i < pathArray.length; i++) {
        const key = pathArray[i];
        
        // Check if current is null/undefined
        if (current == null) {
          if (logWarnings) {
            console.warn(`⚠️ SafeObjectAccess: Path broken at step ${i}: ${pathArray.slice(0, i + 1).join('.')}`);
          }
          return defaultValue;
        }
        
        // Check if property exists
        if (!(key in current)) {
          if (logWarnings) {
            console.warn(`⚠️ SafeObjectAccess: Property '${key}' not found in object at path: ${pathArray.slice(0, i + 1).join('.')}`);
          }
          return defaultValue;
        }
        
        current = current[key];
      }
      
      return current;
    } catch (error) {
      if (logWarnings) {
        console.warn('⚠️ SafeObjectAccess: Error accessing path:', path, error);
      }
      
      if (throwOnError) {
        throw error;
      }
      
      return defaultValue;
    }
  }

  /**
   * Safely check if a nested property exists
   * @param obj The object to check
   * @param path The property path
   * @returns true if the path exists and is not null/undefined
   */
  static has(obj: any, path: string | string[]): boolean {
    try {
      const notFoundSymbol = Symbol('not-found');
      const value = this.get(obj, path, { defaultValue: notFoundSymbol });
      return value !== notFoundSymbol && value != null;
    } catch {
      return false;
    }
  }

  /**
   * Safely access array elements with bounds checking
   * @param arr The array to access
   * @param index The index to access
   * @param defaultValue Default value if index is out of bounds
   * @returns The array element or defaultValue
   */
  static getArrayElement(arr: any[], index: number, defaultValue: any = null): any {
    try {
      if (!Array.isArray(arr)) {
        console.warn('⚠️ SafeObjectAccess: Non-array provided to getArrayElement');
        return defaultValue;
      }
      
      if (index < 0 || index >= arr.length) {
        return defaultValue;
      }
      
      return arr[index];
    } catch (error) {
      console.warn('⚠️ SafeObjectAccess: Error accessing array element:', error);
      return defaultValue;
    }
  }

  /**
   * Safely extract multiple properties from an object
   * @param obj The source object
   * @param paths Array of property paths to extract
   * @param options Configuration options
   * @returns Object with extracted properties
   */
  static extract(obj: any, paths: string[], options: SafeAccessOptions = {}): Record<string, any> {
    const result: Record<string, any> = {};
    
    for (const path of paths) {
      const key = path.includes('.') ? path.split('.').pop() || path : path;
      result[key] = this.get(obj, path, options);
    }
    
    return result;
  }

  /**
   * Create a safe wrapper for an object that logs all property access attempts
   * @param obj The object to wrap
   * @param name Optional name for logging
   * @returns Proxied object with safe access
   */
  static createSafeWrapper(obj: any, name: string = 'object'): any {
    if (!obj || typeof obj !== 'object') {
      return obj;
    }

    return new Proxy(obj, {
      get(target, prop) {
        try {
          if (prop in target) {
            const value = target[prop];
            
            // If the value is an object, wrap it too
            if (value && typeof value === 'object' && !Array.isArray(value)) {
              return SafeObjectAccess.createSafeWrapper(value, `${name}.${String(prop)}`);
            }
            
            return value;
          } else {
            console.warn(`⚠️ SafeWrapper: Property '${String(prop)}' not found in ${name}`);
            return undefined;
          }
        } catch (error) {
          console.error(`❌ SafeWrapper: Error accessing '${String(prop)}' in ${name}:`, error);
          return undefined;
        }
      },
      
      has(target, prop) {
        try {
          return prop in target;
        } catch {
          return false;
        }
      }
    });
  }

  /**
   * Validate an object against a schema
   * @param obj The object to validate
   * @param schema The expected schema
   * @returns Validation result with missing properties
   */
  static validate(obj: any, schema: Record<string, string>): { valid: boolean; missing: string[]; errors: string[] } {
    const missing: string[] = [];
    const errors: string[] = [];
    
    try {
      if (!obj || typeof obj !== 'object') {
        return { valid: false, missing: Object.keys(schema), errors: ['Object is null or not an object'] };
      }
      
      for (const [path, expectedType] of Object.entries(schema)) {
        const value = this.get(obj, path);
        
        if (value == null) {
          missing.push(path);
        } else if (expectedType !== 'any' && typeof value !== expectedType) {
          errors.push(`${path}: expected ${expectedType}, got ${typeof value}`);
        }
      }
      
      return { valid: missing.length === 0 && errors.length === 0, missing, errors };
    } catch (error) {
      return { valid: false, missing: [], errors: [`Validation error: ${error}`] };
    }
  }
}

// Convenience functions for common use cases
export const safeGet = SafeObjectAccess.get;
export const safeHas = SafeObjectAccess.has;
export const safeExtract = SafeObjectAccess.extract;
export const safeArray = SafeObjectAccess.getArrayElement;
export const safeWrap = SafeObjectAccess.createSafeWrapper;
export const safeValidate = SafeObjectAccess.validate;

export default SafeObjectAccess;