#!/usr/bin/env node

// GPS Performance Test Suite Runner
// Comprehensive test runner for all GPS performance tests

const GPSPerformanceTest = require('./gps-performance-test');
const GPSBenchmark = require('./gps-benchmark');
const GPSStressTest = require('./gps-stress-test');
const GPSLoadTest = require('./gps-load-test');

class GPSTestSuiteRunner {
  constructor() {
    this.results = {
      performance: null,
      benchmark: null,
      stress: null,
      load: null,
      summary: {}
    };
    
    this.config = {
      runPerformanceTests: true,
      runBenchmarks: true,
      runStressTests: true,
      runLoadTests: true,
      generateReport: true,
      saveResults: true
    };
  }

  // Parse command line arguments
  parseArguments() {
    const args = process.argv.slice(2);
    
    for (const arg of args) {
      switch (arg) {
        case '--performance-only':
          this.config = { ...this.config, runBenchmarks: false, runStressTests: false, runLoadTests: false };
          break;
        case '--benchmark-only':
          this.config = { ...this.config, runPerformanceTests: false, runStressTests: false, runLoadTests: false };
          break;
        case '--stress-only':
          this.config = { ...this.config, runPerformanceTests: false, runBenchmarks: false, runLoadTests: false };
          break;
        case '--load-only':
          this.config = { ...this.config, runPerformanceTests: false, runBenchmarks: false, runStressTests: false };
          break;
        case '--no-report':
          this.config.generateReport = false;
          break;
        case '--no-save':
          this.config.saveResults = false;
          break;
        case '--help':
          this.showHelp();
          process.exit(0);
      }
    }
  }

  // Show help information
  showHelp() {
    console.log(`
🧪 GPS Performance Test Suite Runner

Usage: node run-gps-tests.js [options]

Options:
  --performance-only    Run only performance tests (basic functionality)
  --benchmark-only      Run only benchmarks (detailed performance metrics)  
  --stress-only         Run only stress tests (system limits and edge cases)
  --load-only          Run only load tests (realistic user patterns)
  --no-report          Skip comprehensive report generation
  --no-save            Don't save results to files
  --help               Show this help message

Test Types:
  📊 Performance Tests: Basic functionality and improvement validation
  🏎️ Benchmarks: Detailed performance metrics and comparisons
  🔥 Stress Tests: System limits, edge cases, and failure conditions
  📈 Load Tests: Realistic user load patterns and capacity planning

Example:
  node run-gps-tests.js                    # Run all tests
  node run-gps-tests.js --performance-only # Run only basic performance tests
  node run-gps-tests.js --no-save          # Run all tests but don't save results
`);
  }

  // Setup test environment
  setupEnvironment() {
    // Mock console.time/timeEnd for better test output
    const originalConsoleTime = console.time;
    const originalConsoleTimeEnd = console.timeEnd;
    
    console.time = (label) => {
      console.log(`⏱️ Starting: ${label}`);
      return originalConsoleTime.call(console, label);
    };
    
    console.timeEnd = (label) => {
      const result = originalConsoleTimeEnd.call(console, label);
      console.log(`✅ Completed: ${label}`);
      return result;
    };

    // Set up global test utilities
    global.testStartTime = Date.now();
    
    // Ensure clean state
    if (global.gc) {
      global.gc();
    }
  }

  // Run performance tests
  async runPerformanceTests() {
    if (!this.config.runPerformanceTests) return null;
    
    console.log('🧪 Running GPS Performance Tests...\n');
    console.time('Performance Tests');
    
    try {
      const performanceTest = new GPSPerformanceTest();
      await performanceTest.runAllTests();
      
      console.timeEnd('Performance Tests');
      return performanceTest.generatePerformanceReport();
      
    } catch (error) {
      console.error('❌ Performance tests failed:', error);
      return { error: error.message };
    }
  }

  // Run benchmarks
  async runBenchmarks() {
    if (!this.config.runBenchmarks) return null;
    
    console.log('🏎️ Running GPS Benchmarks...\n');
    console.time('GPS Benchmarks');
    
    try {
      const benchmark = new GPSBenchmark();
      await benchmark.runBenchmarkSuite();
      
      console.timeEnd('GPS Benchmarks');
      return benchmark.generateBenchmarkReport();
      
    } catch (error) {
      console.error('❌ Benchmarks failed:', error);
      return { error: error.message };
    }
  }

  // Run stress tests
  async runStressTests() {
    if (!this.config.runStressTests) return null;
    
    console.log('🔥 Running GPS Stress Tests...\n');
    console.time('GPS Stress Tests');
    
    try {
      const stressTest = new GPSStressTest();
      await stressTest.runStressTestSuite();
      
      console.timeEnd('GPS Stress Tests');
      return stressTest.generateStressTestReport();
      
    } catch (error) {
      console.error('❌ Stress tests failed:', error);
      return { error: error.message };
    }
  }

  // Run load tests
  async runLoadTests() {
    if (!this.config.runLoadTests) return null;
    
    console.log('📈 Running GPS Load Tests...\n');
    console.time('GPS Load Tests');
    
    try {
      const loadTest = new GPSLoadTest();
      await loadTest.runLoadTestSuite();
      
      console.timeEnd('GPS Load Tests');
      return loadTest.generateLoadTestReport();
      
    } catch (error) {
      console.error('❌ Load tests failed:', error);
      return { error: error.message };
    }
  }

  // Run complete test suite
  async runTestSuite() {
    const suiteStartTime = Date.now();
    
    console.log('🚀 GPS Performance Test Suite');
    console.log('=' .repeat(50));
    console.log(`📅 Started: ${new Date().toISOString()}`);
    console.log(`🎯 Configuration: Performance=${this.config.runPerformanceTests}, Benchmark=${this.config.runBenchmarks}, Stress=${this.config.runStressTests}, Load=${this.config.runLoadTests}`);
    console.log('');
    
    try {
      // Run test suites
      this.results.performance = await this.runPerformanceTests();
      console.log('');
      
      this.results.benchmark = await this.runBenchmarks();
      console.log('');
      
      this.results.stress = await this.runStressTests();
      console.log('');
      
      this.results.load = await this.runLoadTests();
      console.log('');
      
      // Generate comprehensive summary
      const totalTime = Date.now() - suiteStartTime;
      this.results.summary = this.generateSummaryReport(totalTime);
      
      if (this.config.generateReport) {
        this.generateComprehensiveReport();
      }
      
      if (this.config.saveResults) {
        await this.saveResults();
      }
      
      return this.results;
      
    } catch (error) {
      console.error('❌ Test suite failed:', error);
      throw error;
    }
  }

  // Generate summary report
  generateSummaryReport(totalTime) {
    console.log('📋 GPS Test Suite Summary');
    console.log('=' .repeat(50));
    
    const summary = {
      totalTime,
      testsRun: 0,
      testsPassed: 0,
      overallGrade: 'F',
      keyFindings: [],
      recommendations: []
    };
    
    // Analyze performance tests
    if (this.results.performance && !this.results.performance.error) {
      summary.testsRun++;
      if (this.results.performance.passRate === '100.0') {
        summary.testsPassed++;
        summary.keyFindings.push('✅ All core GPS functionality working correctly');
      } else {
        summary.keyFindings.push(`⚠️ ${this.results.performance.passedTests}/${this.results.performance.totalTests} performance tests passed`);
      }
    }
    
    // Analyze benchmarks
    if (this.results.benchmark && !this.results.benchmark.error) {
      summary.testsRun++;
      const grade = this.results.benchmark.overallGrade;
      if (grade === 'A' || grade === 'B') {
        summary.testsPassed++;
        summary.keyFindings.push(`🏆 Benchmark grade: ${grade} - Excellent performance`);
      } else {
        summary.keyFindings.push(`📊 Benchmark grade: ${grade} - Performance needs improvement`);
      }
    }
    
    // Analyze stress tests
    if (this.results.stress && !this.results.stress.error) {
      summary.testsRun++;
      const grade = this.results.stress.stabilityGrade;
      if (grade === 'A' || grade === 'B') {
        summary.testsPassed++;
        summary.keyFindings.push(`🛡️ Stress test grade: ${grade} - System handles stress well`);
      } else {
        summary.keyFindings.push(`🔥 Stress test grade: ${grade} - System struggles under stress`);
        summary.recommendations.push('Implement better error handling and resource management');
      }
    }
    
    // Analyze load tests
    if (this.results.load && !this.results.load.error) {
      summary.testsRun++;
      const grade = this.results.load.overallGrade;
      if (grade === 'A' || grade === 'B') {
        summary.testsPassed++;
        summary.keyFindings.push(`📈 Load test grade: ${grade} - Handles realistic loads effectively`);
      } else {
        summary.keyFindings.push(`📉 Load test grade: ${grade} - Performance issues under realistic loads`);
        summary.recommendations.push('Optimize for concurrent user patterns');
      }
    }
    
    // Calculate overall grade
    const passRate = summary.testsRun > 0 ? summary.testsPassed / summary.testsRun : 0;
    summary.overallGrade = passRate >= 0.9 ? 'A' : 
                          passRate >= 0.75 ? 'B' : 
                          passRate >= 0.5 ? 'C' : 'D';
    
    // Display summary
    console.log(`🎯 Tests Run: ${summary.testsRun}`);
    console.log(`✅ Tests Passed: ${summary.testsPassed}`);
    console.log(`🎖️ Overall Grade: ${summary.overallGrade}`);
    console.log(`⏱️ Total Time: ${(totalTime / 60000).toFixed(1)} minutes`);
    console.log('');
    
    console.log('🔍 Key Findings:');
    summary.keyFindings.forEach(finding => console.log(`  ${finding}`));
    
    if (summary.recommendations.length > 0) {
      console.log('');
      console.log('💡 Recommendations:');
      summary.recommendations.forEach(rec => console.log(`  ${rec}`));
    }
    
    console.log('');
    
    // Performance improvement summary
    if (this.results.performance && !this.results.performance.error) {
      const freshGPSTest = this.results.performance.results.find(r => r.testName === 'Fresh GPS Acquisition');
      if (freshGPSTest && freshGPSTest.improvement) {
        console.log(`🚀 GPS Speed Improvement: ${freshGPSTest.improvement}% faster than baseline`);
        console.log(`   Before: ~15s → After: ${freshGPSTest.acquisitionTime}ms`);
      }
      
      const cachedTest = this.results.performance.results.find(r => r.testName === 'Cached GPS Access');
      if (cachedTest && cachedTest.acquisitionTime) {
        console.log(`⚡ Cache Performance: ${cachedTest.acquisitionTime}ms for cached requests`);
      }
    }
    
    return summary;
  }

  // Generate comprehensive report
  generateComprehensiveReport() {
    const reportContent = this.generateMarkdownReport();
    
    // Display key metrics
    console.log('\n📊 Performance Metrics Summary:');
    console.log('-'.repeat(40));
    
    if (this.results.performance) {
      console.log('Performance Tests:');
      const results = this.results.performance.results || [];
      results.forEach(test => {
        if (test.acquisitionTime) {
          console.log(`  ${test.testName}: ${test.acquisitionTime}ms`);
        }
      });
    }
    
    if (this.results.benchmark) {
      console.log('\nBenchmark Results:');
      const results = this.results.benchmark.results || [];
      results.forEach(test => {
        console.log(`  ${test.testName}: Grade ${this.results.benchmark.overallGrade}`);
      });
    }
    
    return reportContent;
  }

  // Generate markdown report
  generateMarkdownReport() {
    const timestamp = new Date().toISOString();
    
    return `
# GPS Performance Test Report

Generated: ${timestamp}

## Executive Summary

- **Overall Grade**: ${this.results.summary?.overallGrade || 'N/A'}
- **Tests Completed**: ${this.results.summary?.testsRun || 0}
- **Tests Passed**: ${this.results.summary?.testsPassed || 0}
- **Total Duration**: ${this.results.summary?.totalTime ? (this.results.summary.totalTime / 60000).toFixed(1) + ' minutes' : 'N/A'}

## Key Improvements Validated

### GPS Acquisition Speed
${this.results.performance?.results?.find(r => r.testName === 'Fresh GPS Acquisition')?.improvement ? 
  `- **${this.results.performance.results.find(r => r.testName === 'Fresh GPS Acquisition').improvement}% improvement** over baseline (15s → ${this.results.performance.results.find(r => r.testName === 'Fresh GPS Acquisition').acquisitionTime}ms)` : 
  '- Performance data not available'
}

### Smart Caching
${this.results.performance?.results?.find(r => r.testName === 'Cached GPS Access') ? 
  `- Cache access time: **${this.results.performance.results.find(r => r.testName === 'Cached GPS Access').acquisitionTime}ms**` : 
  '- Cache performance data not available'
}

### Progressive Timeout Strategy
${this.results.performance?.results?.find(r => r.testName === 'Progressive Timeout Strategy') ? 
  `- Progressive timeout working: **${this.results.performance.results.find(r => r.testName === 'Progressive Timeout Strategy').success ? 'Yes' : 'No'}**` : 
  '- Progressive timeout data not available'
}

### Battery Optimization
${this.results.performance?.results?.find(r => r.testName === 'Battery Optimization') ? 
  `- Battery optimization active: **${this.results.performance.results.find(r => r.testName === 'Battery Optimization').optimizationWorking ? 'Yes' : 'No'}**` : 
  '- Battery optimization data not available'
}

## Performance Grades

${this.results.performance ? `- **Performance Tests**: ${this.results.performance.passRate}% pass rate` : ''}
${this.results.benchmark ? `- **Benchmarks**: Grade ${this.results.benchmark.overallGrade}` : ''}
${this.results.stress ? `- **Stress Tests**: Grade ${this.results.stress.stabilityGrade}` : ''}
${this.results.load ? `- **Load Tests**: Grade ${this.results.load.overallGrade}` : ''}

## Recommendations

${this.results.summary?.recommendations?.map(rec => `- ${rec}`).join('\n') || '- No specific recommendations at this time'}

## Detailed Results

### Performance Test Results
${this.results.performance ? JSON.stringify(this.results.performance, null, 2) : 'Not available'}

---
*Report generated by GPS Performance Test Suite*
`;
  }

  // Save results to files
  async saveResults() {
    const fs = require('fs').promises;
    const path = require('path');
    
    try {
      const resultsDir = path.join(__dirname, 'results');
      await fs.mkdir(resultsDir, { recursive: true });
      
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
      
      // Save JSON results
      const jsonFile = path.join(resultsDir, `gps-test-results-${timestamp}.json`);
      await fs.writeFile(jsonFile, JSON.stringify(this.results, null, 2));
      
      // Save markdown report
      const reportContent = this.generateMarkdownReport();
      const mdFile = path.join(resultsDir, `gps-test-report-${timestamp}.md`);
      await fs.writeFile(mdFile, reportContent);
      
      console.log(`💾 Results saved:`);
      console.log(`   JSON: ${jsonFile}`);
      console.log(`   Report: ${mdFile}`);
      
    } catch (error) {
      console.error('❌ Failed to save results:', error.message);
    }
  }

  // Main execution
  async main() {
    try {
      this.parseArguments();
      this.setupEnvironment();
      
      const results = await this.runTestSuite();
      
      console.log('\n🎉 GPS Performance Test Suite completed successfully!');
      console.log(`📊 Overall Grade: ${results.summary?.overallGrade || 'N/A'}`);
      
      // Exit with appropriate code
      const grade = results.summary?.overallGrade;
      const exitCode = grade === 'A' || grade === 'B' ? 0 : 1;
      process.exit(exitCode);
      
    } catch (error) {
      console.error('\n❌ GPS Performance Test Suite failed:', error);
      process.exit(1);
    }
  }
}

// Run if called directly
if (require.main === module) {
  const runner = new GPSTestSuiteRunner();
  runner.main();
}

module.exports = GPSTestSuiteRunner;