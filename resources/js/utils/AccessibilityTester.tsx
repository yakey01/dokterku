/**
 * Accessibility Testing Utilities
 * Provides tools for testing and validating WCAG 2.1 AA compliance
 */

export interface AccessibilityTestResult {
  passed: boolean;
  level: 'A' | 'AA' | 'AAA';
  criterion: string;
  message: string;
  element?: HTMLElement;
  severity: 'error' | 'warning' | 'info';
}

export class AccessibilityTester {
  private results: AccessibilityTestResult[] = [];

  /**
   * Test color contrast ratio
   * WCAG 2.1 AA requires 4.5:1 for normal text, 3:1 for large text
   */
  testColorContrast(element: HTMLElement): AccessibilityTestResult[] {
    const results: AccessibilityTestResult[] = [];
    const style = window.getComputedStyle(element);
    const backgroundColor = style.backgroundColor;
    const color = style.color;
    const fontSize = parseFloat(style.fontSize);
    const fontWeight = style.fontWeight;

    // Calculate contrast ratio (simplified - in production use a proper library)
    const contrastRatio = this.calculateContrastRatio(color, backgroundColor);
    const isLargeText = fontSize >= 18 || (fontSize >= 14 && (fontWeight === 'bold' || parseInt(fontWeight) >= 700));
    const requiredRatio = isLargeText ? 3 : 4.5;

    if (contrastRatio < requiredRatio) {
      results.push({
        passed: false,
        level: 'AA',
        criterion: '1.4.3 Contrast (Minimum)',
        message: `Insufficient color contrast: ${contrastRatio.toFixed(2)}:1 (required: ${requiredRatio}:1)`,
        element,
        severity: 'error'
      });
    } else {
      results.push({
        passed: true,
        level: 'AA',
        criterion: '1.4.3 Contrast (Minimum)',
        message: `Good color contrast: ${contrastRatio.toFixed(2)}:1`,
        element,
        severity: 'info'
      });
    }

    return results;
  }

  /**
   * Test keyboard accessibility
   */
  testKeyboardAccessibility(container: HTMLElement): AccessibilityTestResult[] {
    const results: AccessibilityTestResult[] = [];
    
    // Find all interactive elements
    const interactiveElements = container.querySelectorAll(
      'button, a, input, select, textarea, [tabindex], [role="button"], [role="link"]'
    );

    interactiveElements.forEach((element) => {
      const htmlElement = element as HTMLElement;
      
      // Check if element is focusable
      if (htmlElement.tabIndex < 0 && !htmlElement.hasAttribute('aria-hidden')) {
        results.push({
          passed: false,
          level: 'A',
          criterion: '2.1.1 Keyboard',
          message: 'Interactive element is not keyboard accessible',
          element: htmlElement,
          severity: 'error'
        });
      }

      // Check for visible focus indicator
      const hasVisibleFocus = this.hasVisibleFocusIndicator(htmlElement);
      if (!hasVisibleFocus) {
        results.push({
          passed: false,
          level: 'AA',
          criterion: '2.4.7 Focus Visible',
          message: 'Element lacks visible focus indicator',
          element: htmlElement,
          severity: 'warning'
        });
      }
    });

    return results;
  }

  /**
   * Test ARIA implementation
   */
  testARIA(container: HTMLElement): AccessibilityTestResult[] {
    const results: AccessibilityTestResult[] = [];

    // Test for missing labels
    const unlabeledInputs = container.querySelectorAll('input:not([aria-label]):not([aria-labelledby])');
    unlabeledInputs.forEach((input) => {
      const associatedLabel = container.querySelector(`label[for="${input.id}"]`);
      if (!associatedLabel) {
        results.push({
          passed: false,
          level: 'A',
          criterion: '1.3.1 Info and Relationships',
          message: 'Input element lacks accessible label',
          element: input as HTMLElement,
          severity: 'error'
        });
      }
    });

    // Test for invalid ARIA
    const elementsWithAria = container.querySelectorAll('[aria-expanded], [aria-controls], [role]');
    elementsWithAria.forEach((element) => {
      const ariaExpanded = element.getAttribute('aria-expanded');
      const ariaControls = element.getAttribute('aria-controls');
      
      if (ariaControls && !container.querySelector(`#${ariaControls}`)) {
        results.push({
          passed: false,
          level: 'A',
          criterion: '4.1.2 Name, Role, Value',
          message: 'aria-controls references non-existent element',
          element: element as HTMLElement,
          severity: 'error'
        });
      }
    });

    return results;
  }

  /**
   * Test heading hierarchy
   */
  testHeadingHierarchy(container: HTMLElement): AccessibilityTestResult[] {
    const results: AccessibilityTestResult[] = [];
    const headings = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
    
    let previousLevel = 0;
    headings.forEach((heading) => {
      const level = parseInt(heading.tagName.charAt(1));
      
      if (level > previousLevel + 1) {
        results.push({
          passed: false,
          level: 'A',
          criterion: '1.3.1 Info and Relationships',
          message: `Heading level skipped: jumped from h${previousLevel} to h${level}`,
          element: heading as HTMLElement,
          severity: 'error'
        });
      }
      
      previousLevel = level;
    });

    return results;
  }

  /**
   * Test touch target sizes (mobile accessibility)
   */
  testTouchTargets(container: HTMLElement): AccessibilityTestResult[] {
    const results: AccessibilityTestResult[] = [];
    const interactiveElements = container.querySelectorAll(
      'button, a, input[type="button"], input[type="submit"], [role="button"]'
    );

    interactiveElements.forEach((element) => {
      const htmlElement = element as HTMLElement;
      const rect = htmlElement.getBoundingClientRect();
      const minSize = 44; // WCAG 2.1 AA minimum touch target size

      if (rect.width < minSize || rect.height < minSize) {
        results.push({
          passed: false,
          level: 'AA',
          criterion: '2.5.5 Target Size',
          message: `Touch target too small: ${rect.width}x${rect.height}px (minimum: ${minSize}x${minSize}px)`,
          element: htmlElement,
          severity: 'warning'
        });
      }
    });

    return results;
  }

  /**
   * Run all accessibility tests
   */
  runAllTests(container: HTMLElement = document.body): AccessibilityTestResult[] {
    this.results = [];
    
    this.results.push(...this.testKeyboardAccessibility(container));
    this.results.push(...this.testARIA(container));
    this.results.push(...this.testHeadingHierarchy(container));
    this.results.push(...this.testTouchTargets(container));

    // Test color contrast for all visible text elements
    const textElements = container.querySelectorAll('*');
    textElements.forEach((element) => {
      const htmlElement = element as HTMLElement;
      if (this.hasVisibleText(htmlElement)) {
        this.results.push(...this.testColorContrast(htmlElement));
      }
    });

    return this.results;
  }

  /**
   * Get compliance summary
   */
  getComplianceSummary(): {
    totalTests: number;
    passed: number;
    failed: number;
    errors: number;
    warnings: number;
    complianceLevel: 'None' | 'Partial A' | 'A' | 'Partial AA' | 'AA' | 'Partial AAA' | 'AAA';
  } {
    const totalTests = this.results.length;
    const passed = this.results.filter(r => r.passed).length;
    const failed = totalTests - passed;
    const errors = this.results.filter(r => r.severity === 'error').length;
    const warnings = this.results.filter(r => r.severity === 'warning').length;

    let complianceLevel: 'None' | 'Partial A' | 'A' | 'Partial AA' | 'AA' | 'Partial AAA' | 'AAA' = 'None';
    
    if (errors === 0) {
      if (warnings === 0) {
        complianceLevel = 'AA';
      } else {
        complianceLevel = 'Partial AA';
      }
    } else if (errors < totalTests * 0.5) {
      complianceLevel = 'Partial A';
    }

    return {
      totalTests,
      passed,
      failed,
      errors,
      warnings,
      complianceLevel
    };
  }

  /**
   * Generate accessibility report
   */
  generateReport(): string {
    const summary = this.getComplianceSummary();
    let report = `Accessibility Test Report\n`;
    report += `========================\n\n`;
    report += `Compliance Level: ${summary.complianceLevel}\n`;
    report += `Total Tests: ${summary.totalTests}\n`;
    report += `Passed: ${summary.passed}\n`;
    report += `Failed: ${summary.failed}\n`;
    report += `Errors: ${summary.errors}\n`;
    report += `Warnings: ${summary.warnings}\n\n`;

    if (summary.errors > 0) {
      report += `Critical Issues (Errors):\n`;
      report += `-------------------------\n`;
      this.results
        .filter(r => r.severity === 'error')
        .forEach((result, index) => {
          report += `${index + 1}. ${result.criterion}: ${result.message}\n`;
        });
      report += `\n`;
    }

    if (summary.warnings > 0) {
      report += `Warnings:\n`;
      report += `---------\n`;
      this.results
        .filter(r => r.severity === 'warning')
        .forEach((result, index) => {
          report += `${index + 1}. ${result.criterion}: ${result.message}\n`;
        });
    }

    return report;
  }

  // Helper methods
  private calculateContrastRatio(color1: string, color2: string): number {
    // Parse RGB values from CSS color strings
    const rgb1 = this.parseRgbColor(color1);
    const rgb2 = this.parseRgbColor(color2);
    
    if (!rgb1 || !rgb2) {
      return 1; // Return minimum ratio if colors can't be parsed
    }
    
    // Calculate relative luminance
    const getLuminance = (rgb: { r: number; g: number; b: number }): number => {
      const getRsRGB = (color: number): number => {
        const sRGB = color / 255;
        return sRGB <= 0.03928 ? sRGB / 12.92 : Math.pow((sRGB + 0.055) / 1.055, 2.4);
      };

      const r = getRsRGB(rgb.r);
      const g = getRsRGB(rgb.g);
      const b = getRsRGB(rgb.b);

      return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };
    
    const luminance1 = getLuminance(rgb1);
    const luminance2 = getLuminance(rgb2);
    
    const brightest = Math.max(luminance1, luminance2);
    const darkest = Math.min(luminance1, luminance2);
    
    return (brightest + 0.05) / (darkest + 0.05);
  }
  
  private parseRgbColor(color: string): { r: number; g: number; b: number } | null {
    // Handle hex colors
    if (color.startsWith('#')) {
      const hex = color.slice(1);
      if (hex.length === 3) {
        return {
          r: parseInt(hex[0] + hex[0], 16),
          g: parseInt(hex[1] + hex[1], 16),
          b: parseInt(hex[2] + hex[2], 16)
        };
      } else if (hex.length === 6) {
        return {
          r: parseInt(hex.slice(0, 2), 16),
          g: parseInt(hex.slice(2, 4), 16),
          b: parseInt(hex.slice(4, 6), 16)
        };
      }
    }
    
    // Handle rgb() and rgba() colors
    const rgbMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (rgbMatch) {
      return {
        r: parseInt(rgbMatch[1]),
        g: parseInt(rgbMatch[2]),
        b: parseInt(rgbMatch[3])
      };
    }
    
    // Handle named colors (basic set)
    const namedColors: { [key: string]: { r: number; g: number; b: number } } = {
      'black': { r: 0, g: 0, b: 0 },
      'white': { r: 255, g: 255, b: 255 },
      'red': { r: 255, g: 0, b: 0 },
      'green': { r: 0, g: 128, b: 0 },
      'blue': { r: 0, g: 0, b: 255 },
      'transparent': { r: 255, g: 255, b: 255 } // Treat as white for contrast calculation
    };
    
    return namedColors[color.toLowerCase()] || null;
  }

  private hasVisibleFocusIndicator(element: HTMLElement): boolean {
    const style = window.getComputedStyle(element);
    return style.outline !== 'none' || 
           style.boxShadow !== 'none' || 
           element.classList.contains('focus-visible') ||
           element.classList.contains('focus-outline');
  }

  private hasVisibleText(element: HTMLElement): boolean {
    const style = window.getComputedStyle(element);
    return style.display !== 'none' && 
           style.visibility !== 'hidden' && 
           element.textContent?.trim() !== '';
  }
}

// Export utility functions
export const accessibilityTester = new AccessibilityTester();

export const runAccessibilityTest = (container?: HTMLElement) => {
  return accessibilityTester.runAllTests(container);
};

export const getAccessibilityReport = () => {
  return accessibilityTester.generateReport();
};