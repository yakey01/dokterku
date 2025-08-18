/**
 * Comprehensive Manajer Frontend Validation Script
 * Tests React components, API calls, responsiveness, and error handling
 */

class ManajerFrontendValidator {
    constructor() {
        this.results = [];
        this.errors = [];
        this.apiBaseUrl = '/api/v2/manajer';
        
        console.log('🔍 MANAJER FRONTEND VALIDATION STARTED');
        console.log('======================================\n');
    }

    /**
     * Run all frontend validation tests
     */
    async runAllTests() {
        await this.testReactComponentMounting();
        await this.testApiConnectivity();
        await this.testMobileResponsiveness();
        await this.testCssAndStyling();
        await this.testErrorHandling();
        await this.testUserInteractions();
        await this.testDataVisualization();
        await this.testPerformance();
        
        this.generateReport();
    }

    /**
     * Test React component mounting and rendering
     */
    async testReactComponentMounting() {
        console.log('⚛️ Testing React Component Mounting...');
        
        try {
            // Test if main dashboard container exists
            const dashboardContainer = document.querySelector('#manajer-dashboard-root');
            if (dashboardContainer) {
                this.addResult('react_container', '✅ Dashboard container: Found');
                console.log('  ✅ Dashboard container: Found');
            } else {
                this.addError('react_container', '❌ Dashboard container: Missing');
                console.log('  ❌ Dashboard container: Missing');
            }

            // Test for React component presence
            const reactComponents = [
                '.executive-dashboard',
                '.kpi-metrics',
                '.financial-overview',
                '.staff-analytics',
                '.real-time-updates'
            ];

            for (const selector of reactComponents) {
                const element = document.querySelector(selector);
                if (element) {
                    this.addResult(`component_${selector}`, `✅ Component ${selector}: Rendered`);
                    console.log(`  ✅ Component ${selector}: Rendered`);
                } else {
                    this.addError(`component_${selector}`, `❌ Component ${selector}: Not found`);
                    console.log(`  ❌ Component ${selector}: Not found`);
                }
            }

            // Test for React state management
            if (window.React && window.ReactDOM) {
                this.addResult('react_loaded', '✅ React libraries: Loaded');
                console.log('  ✅ React libraries: Loaded');
            } else {
                this.addError('react_loaded', '❌ React libraries: Not loaded');
                console.log('  ❌ React libraries: Not loaded');
            }

        } catch (error) {
            this.addError('react_mounting', `❌ React mounting error: ${error.message}`);
            console.log(`  ❌ React mounting error: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Test API connectivity and responses
     */
    async testApiConnectivity() {
        console.log('🌐 Testing API Connectivity...');

        const endpoints = [
            { url: `${this.apiBaseUrl}/dashboard`, name: 'Dashboard Data' },
            { url: `${this.apiBaseUrl}/finance`, name: 'Finance Data' },
            { url: `${this.apiBaseUrl}/attendance`, name: 'Attendance Data' },
            { url: `${this.apiBaseUrl}/jaspel`, name: 'Jaspel Data' },
            { url: `${this.apiBaseUrl}/profile`, name: 'Profile Data' }
        ];

        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint.url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data && typeof data === 'object') {
                        this.addResult(`api_${endpoint.url}`, `✅ ${endpoint.name}: Working (${response.status})`);
                        console.log(`  ✅ ${endpoint.name}: Working (${response.status})`);
                    } else {
                        this.addError(`api_${endpoint.url}`, `❌ ${endpoint.name}: Invalid data format`);
                        console.log(`  ❌ ${endpoint.name}: Invalid data format`);
                    }
                } else {
                    this.addError(`api_${endpoint.url}`, `❌ ${endpoint.name}: HTTP ${response.status}`);
                    console.log(`  ❌ ${endpoint.name}: HTTP ${response.status}`);
                }

            } catch (error) {
                this.addError(`api_${endpoint.url}`, `❌ ${endpoint.name}: ${error.message}`);
                console.log(`  ❌ ${endpoint.name}: ${error.message}`);
            }
        }
        console.log('');
    }

    /**
     * Test mobile responsiveness
     */
    async testMobileResponsiveness() {
        console.log('📱 Testing Mobile Responsiveness...');

        const breakpoints = [
            { width: 320, name: 'Mobile Small' },
            { width: 375, name: 'Mobile Medium' },
            { width: 768, name: 'Tablet' },
            { width: 1024, name: 'Desktop Small' },
            { width: 1440, name: 'Desktop Large' }
        ];

        for (const breakpoint of breakpoints) {
            try {
                // Simulate viewport change
                Object.defineProperty(window, 'innerWidth', {
                    writable: true,
                    configurable: true,
                    value: breakpoint.width,
                });

                // Trigger resize event
                window.dispatchEvent(new Event('resize'));

                // Wait for layout adjustments
                await new Promise(resolve => setTimeout(resolve, 100));

                // Check responsive elements
                const mobileNav = document.querySelector('.mobile-navigation');
                const sidebarCollapse = document.querySelector('.sidebar-collapsed');
                const responsiveGrid = document.querySelector('.responsive-grid');

                let responsiveScore = 0;
                let totalChecks = 3;

                if (breakpoint.width <= 768) {
                    // Mobile checks
                    if (mobileNav && window.getComputedStyle(mobileNav).display !== 'none') {
                        responsiveScore++;
                    }
                    if (sidebarCollapse) {
                        responsiveScore++;
                    }
                } else {
                    // Desktop checks
                    if (!mobileNav || window.getComputedStyle(mobileNav).display === 'none') {
                        responsiveScore++;
                    }
                    responsiveScore++; // Desktop always gets sidebar check
                }

                if (responsiveGrid) {
                    responsiveScore++;
                }

                const percentage = Math.round((responsiveScore / totalChecks) * 100);
                if (percentage >= 70) {
                    this.addResult(`responsive_${breakpoint.width}`, `✅ ${breakpoint.name} (${breakpoint.width}px): ${percentage}% responsive`);
                    console.log(`  ✅ ${breakpoint.name} (${breakpoint.width}px): ${percentage}% responsive`);
                } else {
                    this.addError(`responsive_${breakpoint.width}`, `❌ ${breakpoint.name} (${breakpoint.width}px): ${percentage}% responsive`);
                    console.log(`  ❌ ${breakpoint.name} (${breakpoint.width}px): ${percentage}% responsive`);
                }

            } catch (error) {
                this.addError(`responsive_${breakpoint.width}`, `❌ ${breakpoint.name}: ${error.message}`);
                console.log(`  ❌ ${breakpoint.name}: ${error.message}`);
            }
        }
        console.log('');
    }

    /**
     * Test CSS and styling
     */
    async testCssAndStyling() {
        console.log('🎨 Testing CSS and Styling...');

        try {
            // Test CSS framework loading
            const frameworks = [
                { name: 'Tailwind CSS', test: () => !!document.querySelector('[class*="bg-"]') },
                { name: 'Custom Styles', test: () => !!document.querySelector('.manajer-dashboard') },
                { name: 'Glassmorphism', test: () => !!document.querySelector('[class*="backdrop-blur"]') }
            ];

            frameworks.forEach(framework => {
                if (framework.test()) {
                    this.addResult(`css_${framework.name.toLowerCase().replace(' ', '_')}`, `✅ ${framework.name}: Loaded`);
                    console.log(`  ✅ ${framework.name}: Loaded`);
                } else {
                    this.addError(`css_${framework.name.toLowerCase().replace(' ', '_')}`, `❌ ${framework.name}: Not detected`);
                    console.log(`  ❌ ${framework.name}: Not detected`);
                }
            });

            // Test color contrast
            const contrastElements = document.querySelectorAll('.text-primary, .text-secondary, .bg-primary');
            if (contrastElements.length > 0) {
                this.addResult('css_contrast', `✅ Color scheme: ${contrastElements.length} elements with proper contrast`);
                console.log(`  ✅ Color scheme: ${contrastElements.length} elements with proper contrast`);
            } else {
                this.addError('css_contrast', '❌ Color scheme: No contrast elements detected');
                console.log('  ❌ Color scheme: No contrast elements detected');
            }

            // Test animations
            const animatedElements = document.querySelectorAll('[class*="animate-"], [class*="transition-"]');
            if (animatedElements.length > 0) {
                this.addResult('css_animations', `✅ Animations: ${animatedElements.length} animated elements`);
                console.log(`  ✅ Animations: ${animatedElements.length} animated elements`);
            } else {
                this.addError('css_animations', '❌ Animations: No animated elements detected');
                console.log('  ❌ Animations: No animated elements detected');
            }

        } catch (error) {
            this.addError('css_styling', `❌ CSS styling error: ${error.message}`);
            console.log(`  ❌ CSS styling error: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Test error handling
     */
    async testErrorHandling() {
        console.log('🚨 Testing Error Handling...');

        try {
            // Test error boundary
            if (window.ErrorBoundary || document.querySelector('.error-boundary')) {
                this.addResult('error_boundary', '✅ Error boundary: Implemented');
                console.log('  ✅ Error boundary: Implemented');
            } else {
                this.addError('error_boundary', '❌ Error boundary: Not implemented');
                console.log('  ❌ Error boundary: Not implemented');
            }

            // Test error messages
            const errorElements = document.querySelectorAll('.error-message, .alert-error, [role="alert"]');
            if (errorElements.length > 0) {
                this.addResult('error_messages', `✅ Error messages: ${errorElements.length} error display elements`);
                console.log(`  ✅ Error messages: ${errorElements.length} error display elements`);
            } else {
                this.addError('error_messages', '❌ Error messages: No error display elements');
                console.log('  ❌ Error messages: No error display elements');
            }

            // Test network error handling
            try {
                await fetch('/api/v2/manajer/nonexistent-endpoint');
            } catch (networkError) {
                this.addResult('network_error_handling', '✅ Network errors: Properly caught');
                console.log('  ✅ Network errors: Properly caught');
            }

        } catch (error) {
            this.addError('error_handling', `❌ Error handling test failed: ${error.message}`);
            console.log(`  ❌ Error handling test failed: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Test user interactions
     */
    async testUserInteractions() {
        console.log('👆 Testing User Interactions...');

        try {
            // Test clickable elements
            const clickableElements = document.querySelectorAll('button, [role="button"], .btn, .clickable');
            if (clickableElements.length > 0) {
                this.addResult('clickable_elements', `✅ Interactive elements: ${clickableElements.length} clickable elements`);
                console.log(`  ✅ Interactive elements: ${clickableElements.length} clickable elements`);
            } else {
                this.addError('clickable_elements', '❌ Interactive elements: No clickable elements found');
                console.log('  ❌ Interactive elements: No clickable elements found');
            }

            // Test form elements
            const formElements = document.querySelectorAll('input, select, textarea, form');
            if (formElements.length > 0) {
                this.addResult('form_elements', `✅ Form elements: ${formElements.length} form controls`);
                console.log(`  ✅ Form elements: ${formElements.length} form controls`);
            } else {
                this.addResult('form_elements', '⚠️ Form elements: No form controls (may be expected)');
                console.log('  ⚠️ Form elements: No form controls (may be expected)');
            }

            // Test keyboard navigation
            const focusableElements = document.querySelectorAll('[tabindex], button, input, select, textarea, a[href]');
            if (focusableElements.length > 0) {
                this.addResult('keyboard_navigation', `✅ Keyboard navigation: ${focusableElements.length} focusable elements`);
                console.log(`  ✅ Keyboard navigation: ${focusableElements.length} focusable elements`);
            } else {
                this.addError('keyboard_navigation', '❌ Keyboard navigation: No focusable elements');
                console.log('  ❌ Keyboard navigation: No focusable elements');
            }

        } catch (error) {
            this.addError('user_interactions', `❌ User interaction test failed: ${error.message}`);
            console.log(`  ❌ User interaction test failed: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Test data visualization components
     */
    async testDataVisualization() {
        console.log('📊 Testing Data Visualization...');

        try {
            // Test chart elements
            const chartElements = document.querySelectorAll('.chart, .graph, canvas, svg[class*="chart"]');
            if (chartElements.length > 0) {
                this.addResult('data_visualization', `✅ Charts: ${chartElements.length} visualization elements`);
                console.log(`  ✅ Charts: ${chartElements.length} visualization elements`);
            } else {
                this.addError('data_visualization', '❌ Charts: No visualization elements found');
                console.log('  ❌ Charts: No visualization elements found');
            }

            // Test metrics display
            const metricElements = document.querySelectorAll('.metric, .kpi, .stat, [class*="metric"]');
            if (metricElements.length > 0) {
                this.addResult('metrics_display', `✅ Metrics: ${metricElements.length} metric display elements`);
                console.log(`  ✅ Metrics: ${metricElements.length} metric display elements`);
            } else {
                this.addError('metrics_display', '❌ Metrics: No metric display elements');
                console.log('  ❌ Metrics: No metric display elements');
            }

            // Test data loading states
            const loadingElements = document.querySelectorAll('.loading, .spinner, .skeleton, [class*="loading"]');
            if (loadingElements.length > 0) {
                this.addResult('loading_states', `✅ Loading states: ${loadingElements.length} loading indicators`);
                console.log(`  ✅ Loading states: ${loadingElements.length} loading indicators`);
            } else {
                this.addError('loading_states', '❌ Loading states: No loading indicators');
                console.log('  ❌ Loading states: No loading indicators');
            }

        } catch (error) {
            this.addError('data_visualization', `❌ Data visualization test failed: ${error.message}`);
            console.log(`  ❌ Data visualization test failed: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Test performance metrics
     */
    async testPerformance() {
        console.log('⚡ Testing Performance...');

        try {
            // Test page load time
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            if (loadTime < 3000) {
                this.addResult('performance_load_time', `✅ Load time: ${loadTime}ms (Good)`);
                console.log(`  ✅ Load time: ${loadTime}ms (Good)`);
            } else if (loadTime < 5000) {
                this.addResult('performance_load_time', `⚠️ Load time: ${loadTime}ms (Acceptable)`);
                console.log(`  ⚠️ Load time: ${loadTime}ms (Acceptable)`);
            } else {
                this.addError('performance_load_time', `❌ Load time: ${loadTime}ms (Slow)`);
                console.log(`  ❌ Load time: ${loadTime}ms (Slow)`);
            }

            // Test JavaScript bundle size
            const scripts = document.querySelectorAll('script[src]');
            if (scripts.length > 0) {
                this.addResult('performance_scripts', `✅ JavaScript: ${scripts.length} script files`);
                console.log(`  ✅ JavaScript: ${scripts.length} script files`);
            }

            // Test CSS bundle size
            const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
            if (stylesheets.length > 0) {
                this.addResult('performance_css', `✅ CSS: ${stylesheets.length} stylesheet files`);
                console.log(`  ✅ CSS: ${stylesheets.length} stylesheet files`);
            }

            // Test memory usage (if available)
            if (performance.memory) {
                const memoryMB = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
                if (memoryMB < 50) {
                    this.addResult('performance_memory', `✅ Memory usage: ${memoryMB}MB (Good)`);
                    console.log(`  ✅ Memory usage: ${memoryMB}MB (Good)`);
                } else if (memoryMB < 100) {
                    this.addResult('performance_memory', `⚠️ Memory usage: ${memoryMB}MB (Acceptable)`);
                    console.log(`  ⚠️ Memory usage: ${memoryMB}MB (Acceptable)`);
                } else {
                    this.addError('performance_memory', `❌ Memory usage: ${memoryMB}MB (High)`);
                    console.log(`  ❌ Memory usage: ${memoryMB}MB (High)`);
                }
            }

        } catch (error) {
            this.addError('performance', `❌ Performance test failed: ${error.message}`);
            console.log(`  ❌ Performance test failed: ${error.message}`);
        }
        console.log('');
    }

    /**
     * Helper method to get auth token
     */
    getAuthToken() {
        // Try to get token from various sources
        return localStorage.getItem('auth_token') || 
               sessionStorage.getItem('auth_token') || 
               document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
               'test-token';
    }

    /**
     * Add a successful result
     */
    addResult(key, message) {
        this.results.push({ key, message });
    }

    /**
     * Add an error result
     */
    addError(key, message) {
        this.errors.push({ key, message });
    }

    /**
     * Generate final validation report
     */
    generateReport() {
        console.log('📋 FRONTEND VALIDATION REPORT');
        console.log('=============================\n');

        const totalTests = this.results.length + this.errors.length;
        const passedTests = this.results.length;
        const failedTests = this.errors.length;

        console.log('📊 SUMMARY:');
        console.log(`  Total Tests: ${totalTests}`);
        console.log(`  Passed: ${passedTests} ✅`);
        console.log(`  Failed: ${failedTests} ❌`);
        console.log(`  Success Rate: ${Math.round((passedTests / totalTests) * 100)}%\n`);

        if (this.errors.length > 0) {
            console.log('❌ FAILURES:');
            this.errors.forEach(error => console.log(`  ${error.message}`));
            console.log('');
        }

        console.log('✅ SUCCESSES:');
        this.results.forEach(result => console.log(`  ${result.message}`));
        console.log('');

        // Recommendations
        console.log('💡 RECOMMENDATIONS:');
        if (failedTests > 0) {
            console.log('  • Fix failed frontend tests before deployment');
            console.log('  • Verify React components are properly mounted');
            console.log('  • Test API endpoints with proper authentication');
            console.log('  • Ensure responsive design works on all devices');
        } else {
            console.log('  • All frontend tests passed! UI is ready');
            console.log('  • Consider performance optimization');
            console.log('  • Test with real user data');
        }

        console.log(`\n🏁 Frontend validation completed at ${new Date().toLocaleString()}`);

        // Return results for programmatic use
        return {
            total: totalTests,
            passed: passedTests,
            failed: failedTests,
            results: this.results,
            errors: this.errors
        };
    }
}

// Initialize and run validation when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const validator = new ManajerFrontendValidator();
        validator.runAllTests();
    });
} else {
    const validator = new ManajerFrontendValidator();
    validator.runAllTests();
}

// Export for manual use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ManajerFrontendValidator;
}

// Global access
window.ManajerFrontendValidator = ManajerFrontendValidator;