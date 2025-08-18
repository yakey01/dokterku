/**
 * Robust JSON Parser with Error Recovery
 * Handles malformed JSON responses from server APIs
 */

interface JsonParseResult<T = any> {
  success: boolean;
  data: T | null;
  error?: string;
  originalResponse?: string;
  repaired?: boolean;
}

export class RobustJsonParser {
  /**
   * Parse JSON with automatic repair for common issues
   */
  static parseJson<T = any>(text: string): JsonParseResult<T> {
    if (!text || typeof text !== 'string') {
      return {
        success: false,
        data: null,
        error: 'Empty or invalid input',
        originalResponse: text
      };
    }

    // First attempt: standard JSON.parse
    try {
      const data = JSON.parse(text);
      return {
        success: true,
        data,
        originalResponse: text
      };
    } catch (originalError: any) {
      console.warn('Initial JSON parse failed:', originalError.message);
    }

    // Second attempt: repair common JSON issues
    try {
      const repairedJson = this.repairJson(text);
      const data = JSON.parse(repairedJson);
      return {
        success: true,
        data,
        originalResponse: text,
        repaired: true
      };
    } catch (repairError: any) {
      console.warn('JSON repair failed:', repairError.message);
    }

    // Third attempt: check if response is HTML and extract error
    if (text.includes('<html') || text.includes('<!DOCTYPE')) {
      const errorMessage = this.extractErrorFromHtml(text);
      return {
        success: false,
        data: null,
        error: `Server returned HTML instead of JSON: ${errorMessage}`,
        originalResponse: text
      };
    }

    // Final fallback
    return {
      success: false,
      data: null,
      error: 'Failed to parse JSON and repair attempts failed',
      originalResponse: text
    };
  }

  /**
   * Basic JSON repair functionality
   * Based on jsonrepair library patterns
   */
  private static repairJson(text: string): string {
    let repaired = text.trim();

    // Remove leading/trailing whitespace and control characters
    repaired = repaired.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');

    // Fix single quotes to double quotes
    repaired = repaired.replace(/'/g, '"');

    // Fix unquoted keys (basic pattern)
    repaired = repaired.replace(/([{,]\s*)([a-zA-Z_$][a-zA-Z0-9_$]*)\s*:/g, '$1"$2":');

    // Fix trailing commas
    repaired = repaired.replace(/,(\s*[}\]])/g, '$1');

    // Fix missing commas between object properties
    repaired = repaired.replace(/"\s*\n\s*"/g, '",\n"');

    // Fix common escape issues
    repaired = repaired.replace(/\\\n/g, '\\n');
    repaired = repaired.replace(/\\\r/g, '\\r');
    repaired = repaired.replace(/\\\t/g, '\\t');

    return repaired;
  }

  /**
   * Extract error message from HTML response
   */
  private static extractErrorFromHtml(html: string): string {
    // Try to extract Laravel error title
    const titleMatch = html.match(/<title[^>]*>([^<]*)<\/title>/i);
    if (titleMatch) {
      return titleMatch[1];
    }

    // Try to extract error message from body
    const bodyMatch = html.match(/<body[^>]*>[\s\S]*?<h1[^>]*>([^<]*)<\/h1>/i);
    if (bodyMatch) {
      return bodyMatch[1];
    }

    // Try to extract from meta description
    const metaMatch = html.match(/<meta\s+name="description"\s+content="([^"]*)"/i);
    if (metaMatch) {
      return metaMatch[1];
    }

    return 'Unknown server error (HTML response)';
  }

  /**
   * Enhanced fetch with automatic JSON parsing and error handling
   */
  static async fetchWithRobustJson<T = any>(
    url: string,
    options: RequestInit = {}
  ): Promise<JsonParseResult<T>> {
    try {
      // Ensure JSON headers
      const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
      };

      const response = await fetch(url, {
        ...options,
        headers
      });

      // Get response text first
      const responseText = await response.text();

      // Check if response is empty
      if (!responseText) {
        return {
          success: false,
          data: null,
          error: `Server returned empty response (HTTP ${response.status})`,
          originalResponse: responseText
        };
      }

      // Parse the JSON with robust handling
      const parseResult = this.parseJson<T>(responseText);

      // If parsing succeeded but HTTP status indicates error
      if (parseResult.success && !response.ok) {
        return {
          success: false,
          data: parseResult.data,
          error: `HTTP ${response.status}: ${response.statusText}`,
          originalResponse: responseText
        };
      }

      // If parsing failed and HTTP status indicates error
      if (!parseResult.success && !response.ok) {
        return {
          success: false,
          data: null,
          error: `HTTP ${response.status}: ${parseResult.error}`,
          originalResponse: responseText
        };
      }

      return parseResult;

    } catch (networkError: any) {
      return {
        success: false,
        data: null,
        error: `Network error: ${networkError.message}`,
        originalResponse: undefined
      };
    }
  }
}

// Export for easy importing
export default RobustJsonParser;