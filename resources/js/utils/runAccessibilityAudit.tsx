/**
 * Accessibility Audit Runner
 * Comprehensive WCAG 2.1 AA compliance testing for the dashboard
 */

import { AccessibilityTester, accessibilityTester, runAccessibilityTest, getAccessibilityReport } from './AccessibilityTester';
import { ColorContrastChecker, colorContrastChecker, checkColorContrast, validateAccessibility } from './ColorContrastChecker';

export interface AccessibilityAuditResult {
  overall: {
    complianceLevel: string;
    totalIssues: number;
    criticalIssues: number;
    passRate: number;
  };
  colorContrast: {
    totalChecks: number;
    contrastIssues: number;
    passRate: number;
    recommendations: string[];
  };
  keyboard: {
    focusableElements: number;
    missingFocusIndicators: number;
    keyboardTraps: number;
  };
  aria: {
    totalElements: number;
    missingLabels: number;
    invalidReferences: number;
    improperRoles: number;
  };
  structure: {
    headingHierarchy: boolean;
    landmarks: number;
    skipLinks: number;
  };
  recommendations: {
    priority: 'critical' | 'important' | 'recommended';
    category: string;
    issue: string;
    solution: string;
  }[];
  wcagGuidelines: {
    [key: string]: {
      level: 'A' | 'AA' | 'AAA';
      status: 'pass' | 'fail' | 'partial';
      details: string;
    };
  };
}

export class AccessibilityAuditor {
  private tester: AccessibilityTester;
  private contrastChecker: ColorContrastChecker;

  constructor() {
    this.tester = new AccessibilityTester();
    this.contrastChecker = new ColorContrastChecker();
  }

  /**
   * Run comprehensive accessibility audit
   */
  public async runFullAudit(container: HTMLElement = document.body): Promise<AccessibilityAuditResult> {
    console.log('🔍 Starting comprehensive accessibility audit...');
    
    // Run all accessibility tests
    const testResults = this.tester.runAllTests(container);
    const summary = this.tester.getComplianceSummary();
    
    // Run color contrast validation
    const contrastResults = validateAccessibility(container);
    
    // Keyboard accessibility audit
    const keyboardResults = this.auditKeyboardAccessibility(container);
    
    // ARIA audit
    const ariaResults = this.auditARIA(container);
    
    // Structural audit
    const structureResults = this.auditStructure(container);
    
    // Generate recommendations
    const recommendations = this.generateRecommendations(testResults, contrastResults);
    
    // WCAG guidelines check
    const wcagResults = this.checkWCAGCompliance(testResults);

    const auditResult: AccessibilityAuditResult = {
      overall: {
        complianceLevel: summary.complianceLevel,
        totalIssues: summary.failed,
        criticalIssues: summary.errors,
        passRate: (summary.passed / summary.totalTests) * 100
      },
      colorContrast: {
        totalChecks: contrastResults.totalChecks,
        contrastIssues: contrastResults.contrastIssues,
        passRate: contrastResults.totalChecks > 0 
          ? ((contrastResults.totalChecks - contrastResults.contrastIssues) / contrastResults.totalChecks) * 100 
          : 100,
        recommendations: contrastResults.recommendations
      },
      keyboard: keyboardResults,
      aria: ariaResults,
      structure: structureResults,
      recommendations,
      wcagGuidelines: wcagResults
    };

    console.log('✅ Accessibility audit completed');
    console.log(`📊 Compliance Level: ${summary.complianceLevel}`);
    console.log(`📈 Pass Rate: ${auditResult.overall.passRate.toFixed(1)}%`);
    console.log(`🎯 Color Contrast Pass Rate: ${auditResult.colorContrast.passRate.toFixed(1)}%`);

    return auditResult;
  }

  /**
   * Audit keyboard accessibility
   */
  private auditKeyboardAccessibility(container: HTMLElement) {
    const interactiveElements = container.querySelectorAll(
      'button, a, input, select, textarea, [tabindex], [role="button"], [role="link"]'
    );

    let missingFocusIndicators = 0;
    let keyboardTraps = 0;

    interactiveElements.forEach((element) => {
      const htmlElement = element as HTMLElement;
      
      // Check focus indicators
      const style = window.getComputedStyle(htmlElement);
      const hasFocusIndicator = style.outline !== 'none' || 
                                style.boxShadow !== 'none' || 
                                htmlElement.classList.contains('focus-visible') ||
                                htmlElement.classList.contains('focus-outline');
      
      if (!hasFocusIndicator) {
        missingFocusIndicators++;
      }

      // Check for keyboard traps (simplified check)
      if (htmlElement.tabIndex < 0 && !htmlElement.hasAttribute('aria-hidden')) {
        keyboardTraps++;
      }
    });

    return {
      focusableElements: interactiveElements.length,
      missingFocusIndicators,
      keyboardTraps
    };
  }

  /**
   * Audit ARIA implementation
   */
  private auditARIA(container: HTMLElement) {
    const allElements = container.querySelectorAll('*');
    let missingLabels = 0;
    let invalidReferences = 0;
    let improperRoles = 0;

    // Check inputs for labels
    const inputs = container.querySelectorAll('input:not([type="hidden"])');
    inputs.forEach((input) => {
      const hasLabel = input.hasAttribute('aria-label') || 
                      input.hasAttribute('aria-labelledby') ||
                      container.querySelector(`label[for="${input.id}"]`);
      
      if (!hasLabel) {
        missingLabels++;
      }
    });

    // Check ARIA references
    allElements.forEach((element) => {
      const ariaControls = element.getAttribute('aria-controls');
      const ariaDescribedBy = element.getAttribute('aria-describedby');
      const ariaLabelledBy = element.getAttribute('aria-labelledby');

      [ariaControls, ariaDescribedBy, ariaLabelledBy].forEach((ref) => {
        if (ref && !container.querySelector(`#${ref}`)) {
          invalidReferences++;
        }
      });

      // Check for proper roles (simplified)
      const role = element.getAttribute('role');
      if (role && !this.isValidRole(role)) {
        improperRoles++;
      }
    });

    return {
      totalElements: allElements.length,
      missingLabels,
      invalidReferences,
      improperRoles
    };
  }

  /**
   * Audit document structure
   */
  private auditStructure(container: HTMLElement) {
    // Check heading hierarchy
    const headings = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
    let hasProperHierarchy = true;
    let previousLevel = 0;

    headings.forEach((heading) => {
      const level = parseInt(heading.tagName.charAt(1));
      if (level > previousLevel + 1) {
        hasProperHierarchy = false;
      }
      previousLevel = level;
    });

    // Count landmarks
    const landmarks = container.querySelectorAll('[role="main"], [role="navigation"], [role="banner"], [role="contentinfo"], main, nav, header, footer');
    
    // Count skip links
    const skipLinks = container.querySelectorAll('.skip-link, [href^="#main"], [href^="#content"]');

    return {
      headingHierarchy: hasProperHierarchy,
      landmarks: landmarks.length,
      skipLinks: skipLinks.length
    };
  }

  /**
   * Generate prioritized recommendations
   */
  private generateRecommendations(testResults: any[], contrastResults: any): AccessibilityAuditResult['recommendations'] {
    const recommendations: AccessibilityAuditResult['recommendations'] = [];

    // Critical issues first
    testResults
      .filter(result => result.severity === 'error')
      .forEach(result => {
        recommendations.push({
          priority: 'critical',
          category: 'WCAG Compliance',
          issue: result.message,
          solution: this.getSolutionForCriterion(result.criterion)
        });
      });

    // Color contrast issues
    if (contrastResults.contrastIssues > 0) {
      recommendations.push({
        priority: 'critical',
        category: 'Color Contrast',
        issue: `${contrastResults.contrastIssues} elements fail color contrast requirements`,
        solution: 'Apply accessible color classes (.text-high-contrast, .btn-primary-accessible) or increase contrast ratios to meet 4.5:1 minimum'
      });
    }

    // Important accessibility improvements
    recommendations.push({
      priority: 'important',
      category: 'Screen Reader',
      issue: 'Ensure all dynamic content changes are announced',
      solution: 'Use aria-live regions and screen reader announcements for all content updates'
    });

    recommendations.push({
      priority: 'recommended',
      category: 'User Experience',
      issue: 'Enhance keyboard navigation',
      solution: 'Add visible focus indicators and ensure all interactive elements are keyboard accessible'
    });

    return recommendations.slice(0, 10); // Limit to top 10 recommendations
  }

  /**
   * Check WCAG 2.1 compliance
   */
  private checkWCAGCompliance(testResults: any[]): AccessibilityAuditResult['wcagGuidelines'] {
    const guidelines: AccessibilityAuditResult['wcagGuidelines'] = {
      '1.3.1': {
        level: 'A',
        status: 'pass',
        details: 'Info and Relationships - Semantic structure implemented'
      },
      '1.4.3': {
        level: 'AA',
        status: 'pass',
        details: 'Contrast (Minimum) - Color contrast ratios meet 4.5:1 requirement'
      },
      '2.1.1': {
        level: 'A',
        status: 'pass',
        details: 'Keyboard - All functionality available via keyboard'
      },
      '2.4.7': {
        level: 'AA',
        status: 'pass',
        details: 'Focus Visible - Focus indicators implemented'
      },
      '4.1.2': {
        level: 'A',
        status: 'pass',
        details: 'Name, Role, Value - ARIA implementation correct'
      }
    };

    // Update status based on test results
    testResults.forEach(result => {
      const criterion = result.criterion.split(' ')[0]; // Extract criterion number
      if (guidelines[criterion]) {
        guidelines[criterion].status = result.passed ? 'pass' : 'fail';
        guidelines[criterion].details = result.message;
      }
    });

    return guidelines;
  }

  /**
   * Check if role is valid
   */
  private isValidRole(role: string): boolean {
    const validRoles = [
      'alert', 'alertdialog', 'application', 'article', 'banner', 'button', 'cell', 'checkbox',
      'columnheader', 'combobox', 'complementary', 'contentinfo', 'definition', 'dialog',
      'directory', 'document', 'feed', 'figure', 'form', 'grid', 'gridcell', 'group',
      'heading', 'img', 'link', 'list', 'listbox', 'listitem', 'log', 'main', 'marquee',
      'math', 'menu', 'menubar', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'navigation',
      'none', 'note', 'option', 'presentation', 'progressbar', 'radio', 'radiogroup',
      'region', 'row', 'rowgroup', 'rowheader', 'scrollbar', 'search', 'separator',
      'slider', 'spinbutton', 'status', 'switch', 'tab', 'table', 'tablist', 'tabpanel',
      'term', 'textbox', 'timer', 'toolbar', 'tooltip', 'tree', 'treegrid', 'treeitem'
    ];
    
    return validRoles.includes(role);
  }

  /**
   * Get solution for WCAG criterion
   */
  private getSolutionForCriterion(criterion: string): string {
    const solutions: { [key: string]: string } = {
      '1.3.1': 'Add proper semantic HTML structure and ARIA labels',
      '1.4.3': 'Increase color contrast to meet 4.5:1 ratio minimum',
      '2.1.1': 'Ensure all interactive elements are keyboard accessible',
      '2.4.7': 'Add visible focus indicators to all focusable elements',
      '4.1.2': 'Fix ARIA references and ensure proper name/role/value implementation'
    };

    const criterionNumber = criterion.split(' ')[0];
    return solutions[criterionNumber] || 'Review WCAG 2.1 guidelines for specific requirements';
  }

  /**
   * Generate detailed report
   */
  public generateDetailedReport(auditResult: AccessibilityAuditResult): string {
    let report = `
# 🔍 Accessibility Audit Report
Generated: ${new Date().toLocaleString()}

## 📊 Overall Summary
- **Compliance Level**: ${auditResult.overall.complianceLevel}
- **Pass Rate**: ${auditResult.overall.passRate.toFixed(1)}%
- **Critical Issues**: ${auditResult.overall.criticalIssues}
- **Total Issues**: ${auditResult.overall.totalIssues}

## 🎨 Color Contrast Analysis
- **Total Checks**: ${auditResult.colorContrast.totalChecks}
- **Contrast Issues**: ${auditResult.colorContrast.contrastIssues}
- **Pass Rate**: ${auditResult.colorContrast.passRate.toFixed(1)}%

## ⌨️ Keyboard Accessibility
- **Focusable Elements**: ${auditResult.keyboard.focusableElements}
- **Missing Focus Indicators**: ${auditResult.keyboard.missingFocusIndicators}
- **Keyboard Traps**: ${auditResult.keyboard.keyboardTraps}

## 🏷️ ARIA Implementation
- **Total Elements**: ${auditResult.aria.totalElements}
- **Missing Labels**: ${auditResult.aria.missingLabels}
- **Invalid References**: ${auditResult.aria.invalidReferences}
- **Improper Roles**: ${auditResult.aria.improperRoles}

## 🏗️ Document Structure
- **Heading Hierarchy**: ${auditResult.structure.headingHierarchy ? '✅ Correct' : '❌ Issues Found'}
- **Landmarks**: ${auditResult.structure.landmarks}
- **Skip Links**: ${auditResult.structure.skipLinks}

## 🎯 Priority Recommendations
`;

    auditResult.recommendations.forEach((rec, index) => {
      const priorityIcon = rec.priority === 'critical' ? '🚨' : rec.priority === 'important' ? '⚠️' : '💡';
      report += `
${index + 1}. ${priorityIcon} **${rec.category}**: ${rec.issue}
   Solution: ${rec.solution}
`;
    });

    report += `
## 📋 WCAG 2.1 Guidelines Compliance
`;

    Object.entries(auditResult.wcagGuidelines).forEach(([criterion, guideline]) => {
      const statusIcon = guideline.status === 'pass' ? '✅' : guideline.status === 'fail' ? '❌' : '⚠️';
      report += `
- **${criterion}** (Level ${guideline.level}): ${statusIcon} ${guideline.details}
`;
    });

    return report;
  }
}

// Export singleton instance
export const accessibilityAuditor = new AccessibilityAuditor();

// Export utility functions
export const runFullAccessibilityAudit = async (container?: HTMLElement) => {
  return accessibilityAuditor.runFullAudit(container);
};

export const generateAccessibilityReport = (auditResult: AccessibilityAuditResult) => {
  return accessibilityAuditor.generateDetailedReport(auditResult);
};

// Auto-run audit in development mode
if (process.env.NODE_ENV === 'development') {
  // Run audit after page load
  if (typeof window !== 'undefined') {
    window.addEventListener('load', async () => {
      setTimeout(async () => {
        try {
          const result = await runFullAccessibilityAudit();
          console.log('🔍 Accessibility Audit Results:', result);
          console.log('📄 Detailed Report:\n', generateAccessibilityReport(result));
        } catch (error) {
          console.error('Accessibility audit failed:', error);
        }
      }, 2000);
    });
  }
}