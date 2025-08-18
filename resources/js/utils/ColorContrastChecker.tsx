/**
 * Color Contrast Checker Utility
 * Provides tools for validating and improving color contrast ratios for WCAG 2.1 AA compliance
 */

export interface ContrastResult {
  ratio: number;
  passes: {
    AA: boolean;
    AAA: boolean;
    AALarge: boolean;
    AAALarge: boolean;
  };
  level: 'Pass' | 'AA Large' | 'AA' | 'AAA' | 'Fail';
  suggestion?: string;
}

export interface ColorInfo {
  hex: string;
  rgb: { r: number; g: number; b: number };
  luminance: number;
}

export class ColorContrastChecker {
  /**
   * Convert hex color to RGB
   */
  private hexToRgb(hex: string): { r: number; g: number; b: number } | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null;
  }

  /**
   * Convert RGB color to CSS rgba string
   */
  private rgbToRgba(rgb: { r: number; g: number; b: number }, alpha = 1): string {
    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
  }

  /**
   * Calculate relative luminance of a color
   * Formula from WCAG 2.1 guidelines
   */
  private getLuminance(rgb: { r: number; g: number; b: number }): number {
    const getRsRGB = (color: number): number => {
      const sRGB = color / 255;
      return sRGB <= 0.03928 ? sRGB / 12.92 : Math.pow((sRGB + 0.055) / 1.055, 2.4);
    };

    const r = getRsRGB(rgb.r);
    const g = getRsRGB(rgb.g);
    const b = getRsRGB(rgb.b);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }

  /**
   * Calculate contrast ratio between two colors
   */
  public calculateContrastRatio(color1: string, color2: string): number {
    const rgb1 = this.hexToRgb(color1);
    const rgb2 = this.hexToRgb(color2);

    if (!rgb1 || !rgb2) {
      throw new Error('Invalid color format. Use hex colors like #ffffff');
    }

    const luminance1 = this.getLuminance(rgb1);
    const luminance2 = this.getLuminance(rgb2);

    const brightest = Math.max(luminance1, luminance2);
    const darkest = Math.min(luminance1, luminance2);

    return (brightest + 0.05) / (darkest + 0.05);
  }

  /**
   * Check if color combination meets WCAG standards
   */
  public checkContrast(foreground: string, background: string, isLargeText = false): ContrastResult {
    const ratio = this.calculateContrastRatio(foreground, background);

    const passes = {
      AA: ratio >= 4.5,
      AAA: ratio >= 7,
      AALarge: ratio >= 3,
      AAALarge: ratio >= 4.5
    };

    let level: ContrastResult['level'] = 'Fail';
    if (isLargeText) {
      if (passes.AAALarge) level = 'AAA';
      else if (passes.AALarge) level = 'AA Large';
    } else {
      if (passes.AAA) level = 'AAA';
      else if (passes.AA) level = 'AA';
    }

    // Generate suggestions for improvement
    let suggestion: string | undefined;
    if (level === 'Fail') {
      const requiredRatio = isLargeText ? 3 : 4.5;
      if (ratio < requiredRatio) {
        suggestion = `Current ratio: ${ratio.toFixed(2)}:1. Need ${requiredRatio}:1 minimum. Consider using darker text or lighter background.`;
      }
    }

    return {
      ratio: Math.round(ratio * 100) / 100,
      passes,
      level,
      suggestion
    };
  }

  /**
   * Generate accessible color alternatives
   */
  public generateAccessibleColors(baseColor: string, targetBackground: string): {
    darkText: string;
    lightText: string;
    recommendedText: string;
  } {
    const darkText = '#000000';
    const lightText = '#ffffff';
    
    const darkContrast = this.checkContrast(darkText, targetBackground);
    const lightContrast = this.checkContrast(lightText, targetBackground);

    // Recommend based on which has better contrast
    const recommendedText = darkContrast.ratio > lightContrast.ratio ? darkText : lightText;

    return {
      darkText,
      lightText,
      recommendedText
    };
  }

  /**
   * Validate color contrast for common UI patterns
   */
  public validateUIPattern(pattern: {
    background: string;
    primaryText: string;
    secondaryText: string;
    links: string;
    buttons: string;
  }): {
    isValid: boolean;
    issues: string[];
    recommendations: string[];
  } {
    const issues: string[] = [];
    const recommendations: string[] = [];

    // Check primary text
    const primaryCheck = this.checkContrast(pattern.primaryText, pattern.background);
    if (primaryCheck.level === 'Fail') {
      issues.push(`Primary text contrast ratio: ${primaryCheck.ratio}:1 (needs 4.5:1)`);
      recommendations.push('Increase contrast for primary text - consider #ffffff on dark backgrounds');
    }

    // Check secondary text
    const secondaryCheck = this.checkContrast(pattern.secondaryText, pattern.background);
    if (secondaryCheck.level === 'Fail') {
      issues.push(`Secondary text contrast ratio: ${secondaryCheck.ratio}:1 (needs 4.5:1)`);
      recommendations.push('Improve secondary text contrast - consider #e5e7eb for better readability');
    }

    // Check links
    const linkCheck = this.checkContrast(pattern.links, pattern.background);
    if (linkCheck.level === 'Fail') {
      issues.push(`Link contrast ratio: ${linkCheck.ratio}:1 (needs 4.5:1)`);
      recommendations.push('Use more contrasting link colors - consider #60a5fa for better visibility');
    }

    // Check buttons
    const buttonCheck = this.checkContrast('#ffffff', pattern.buttons);
    if (buttonCheck.level === 'Fail') {
      issues.push(`Button text contrast ratio: ${buttonCheck.ratio}:1 (needs 4.5:1)`);
      recommendations.push('Ensure button backgrounds provide sufficient contrast for white text');
    }

    return {
      isValid: issues.length === 0,
      issues,
      recommendations
    };
  }

  /**
   * Get WCAG compliant color palette for dark theme
   */
  public getAccessibleDarkPalette(): {
    backgrounds: { [key: string]: string };
    text: { [key: string]: string };
    accent: { [key: string]: string };
    status: { [key: string]: string };
  } {
    return {
      backgrounds: {
        primary: '#1f2937',      // Gray-800
        secondary: '#111827',    // Gray-900
        tertiary: '#374151',     // Gray-700
        surface: '#000000'       // Black for maximum contrast
      },
      text: {
        primary: '#ffffff',      // White - 21:1 contrast
        secondary: '#e5e7eb',    // Gray-200 - 16.1:1 contrast
        muted: '#d1d5db',        // Gray-300 - 12.6:1 contrast
        disabled: '#9ca3af'      // Gray-400 - 7.0:1 contrast
      },
      accent: {
        primary: '#3b82f6',      // Blue-500
        secondary: '#8b5cf6',    // Violet-500
        success: '#10b981',      // Emerald-500
        warning: '#f59e0b',      // Amber-500
        error: '#ef4444'         // Red-500
      },
      status: {
        online: '#10b981',       // Green
        offline: '#6b7280',      // Gray
        error: '#ef4444',        // Red
        warning: '#f59e0b'       // Amber
      }
    };
  }

  /**
   * Apply accessible colors to DOM elements
   */
  public applyAccessibleColors(element: HTMLElement, colorScheme: 'auto' | 'light' | 'dark' = 'auto'): void {
    const palette = this.getAccessibleDarkPalette();
    
    // Apply to text elements
    const textElements = element.querySelectorAll('p, span, div, h1, h2, h3, h4, h5, h6');
    textElements.forEach((el) => {
      const htmlEl = el as HTMLElement;
      if (!htmlEl.style.color) {
        htmlEl.style.color = palette.text.primary;
      }
    });

    // Apply to buttons
    const buttons = element.querySelectorAll('button');
    buttons.forEach((button) => {
      if (!button.style.backgroundColor) {
        button.style.backgroundColor = palette.backgrounds.tertiary;
        button.style.color = palette.text.primary;
        button.style.border = `1px solid ${palette.backgrounds.tertiary}`;
      }
    });

    // Apply to inputs
    const inputs = element.querySelectorAll('input, textarea, select');
    inputs.forEach((input) => {
      const htmlInput = input as HTMLElement;
      if (!htmlInput.style.backgroundColor) {
        htmlInput.style.backgroundColor = palette.backgrounds.secondary;
        htmlInput.style.color = palette.text.primary;
        htmlInput.style.border = `1px solid ${palette.backgrounds.tertiary}`;
      }
    });
  }
}

// Export singleton instance
export const colorContrastChecker = new ColorContrastChecker();

// Export utility functions
export const checkColorContrast = (foreground: string, background: string, isLargeText = false) => {
  return colorContrastChecker.checkContrast(foreground, background, isLargeText);
};

export const validateAccessibility = (element: HTMLElement): {
  contrastIssues: number;
  totalChecks: number;
  recommendations: string[];
} => {
  const recommendations: string[] = [];
  let contrastIssues = 0;
  let totalChecks = 0;

  // Check all text elements for contrast
  const textElements = element.querySelectorAll('*');
  
  textElements.forEach((el) => {
    const htmlEl = el as HTMLElement;
    const style = window.getComputedStyle(htmlEl);
    
    if (htmlEl.textContent?.trim() && style.color && style.backgroundColor) {
      totalChecks++;
      
      try {
        const result = colorContrastChecker.checkContrast(
          rgbToHex(style.color),
          rgbToHex(style.backgroundColor)
        );
        
        if (result.level === 'Fail') {
          contrastIssues++;
          if (result.suggestion) {
            recommendations.push(`${htmlEl.tagName.toLowerCase()}: ${result.suggestion}`);
          }
        }
      } catch (error) {
        // Ignore conversion errors for complex color formats
      }
    }
  });

  return {
    contrastIssues,
    totalChecks,
    recommendations: recommendations.slice(0, 10) // Limit recommendations
  };
};

// Helper function to convert RGB to hex
function rgbToHex(rgb: string): string {
  const match = rgb.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
  if (!match) return '#000000';
  
  const r = parseInt(match[1]);
  const g = parseInt(match[2]);
  const b = parseInt(match[3]);
  
  return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}