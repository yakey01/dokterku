/**
 * Runtime Debugging Injection System
 * Can be injected into the browser console or added as a script to debug object rendering
 */

(function() {
  'use strict';
  
  class ReactObjectDebugger {
    constructor() {
      this.originalMethods = {};
      this.detectionLog = [];
      this.isActive = false;
      this.objectPatterns = [];
      
      this.setupDetectionPatterns();
      this.injectDebugger();
    }
    
    setupDetectionPatterns() {
      this.objectPatterns = [
        {
          name: 'jadwal_object',
          test: (obj) => obj && typeof obj === 'object' && 
                       (obj.hasOwnProperty('jadwal') || 
                        obj.hasOwnProperty('tanggal_jaga') ||
                        obj.hasOwnProperty('shift_template')),
          severity: 'HIGH'
        },
        {
          name: 'plain_object',
          test: (obj) => obj && 
                        typeof obj === 'object' && 
                        obj.constructor === Object &&
                        !React.isValidElement(obj),
          severity: 'MEDIUM'
        },
        {
          name: 'array_with_objects',
          test: (obj) => Array.isArray(obj) && 
                        obj.some(item => item && typeof item === 'object' && 
                                       !React.isValidElement(item)),
          severity: 'MEDIUM'
        }
      ];
    }
    
    injectDebugger() {
      console.log('🚀 React Object Debugger injected!');
      console.log('Commands available:');
      console.log('- window.reactDebugger.start() - Start monitoring');
      console.log('- window.reactDebugger.stop() - Stop monitoring');
      console.log('- window.reactDebugger.getLog() - View detection log');
      console.log('- window.reactDebugger.analyzeDOM() - Analyze current DOM');
      console.log('- window.reactDebugger.findJadwalObjects() - Find jadwal objects in memory');
      
      // Make available globally
      window.reactDebugger = this;
      
      // Start automatically
      this.start();
    }
    
    start() {
      if (this.isActive) {
        console.log('⚠️ Debugger already active');
        return;
      }
      
      console.log('🔍 Starting React object detection...');
      this.isActive = true;
      
      this.interceptReactMethods();
      this.interceptConsoleErrors();
      this.monitorDOMChanges();
      this.setupGlobalErrorHandler();
      
      console.log('✅ React object debugger active');
    }
    
    stop() {
      if (!this.isActive) {
        console.log('⚠️ Debugger not active');
        return;
      }
      
      console.log('🛑 Stopping React object detection...');
      this.isActive = false;
      
      this.restoreOriginalMethods();
      
      console.log('✅ React object debugger stopped');
    }
    
    interceptReactMethods() {
      // Intercept React.createElement
      if (window.React && window.React.createElement) {
        this.originalMethods.createElement = window.React.createElement;
        
        window.React.createElement = (...args) => {
          const [type, props, ...children] = args;
          
          // Check props for objects
          if (props) {
            this.checkObjectInProps(props, type);
          }
          
          // Check children for objects
          children.forEach((child, index) => {
            this.checkObjectChild(child, type, `child[${index}]`);
          });
          
          // Check props.children
          if (props && props.children) {
            if (Array.isArray(props.children)) {
              props.children.forEach((child, index) => {
                this.checkObjectChild(child, type, `props.children[${index}]`);
              });
            } else {
              this.checkObjectChild(props.children, type, 'props.children');
            }
          }
          
          return this.originalMethods.createElement(...args);
        };
      }
      
      // Intercept ReactDOM.render if available
      if (window.ReactDOM && window.ReactDOM.render) {
        this.originalMethods.render = window.ReactDOM.render;
        
        window.ReactDOM.render = (element, container, callback) => {
          this.checkObjectChild(element, 'ReactDOM.render', 'root');
          return this.originalMethods.render(element, container, callback);
        };
      }
    }
    
    interceptConsoleErrors() {
      this.originalMethods.error = console.error;
      
      console.error = (...args) => {
        const message = args.join(' ');
        
        if (message.includes('Objects are not valid as React child') ||
            message.includes('[object Object]')) {
          
          this.logDetection({
            type: 'CONSOLE_ERROR',
            message,
            stackTrace: new Error().stack,
            timestamp: Date.now(),
            args: args.map(arg => this.safeStringify(arg))
          });
          
          // Enhanced error logging
          console.group('🚨 OBJECT RENDERING ERROR DETECTED');
          console.log('Original error:', ...args);
          console.log('Stack trace:', new Error().stack);
          console.log('Detection log:', this.detectionLog.slice(-5));
          console.groupEnd();
        }
        
        return this.originalMethods.error(...args);
      };
    }
    
    checkObjectInProps(props, componentType) {
      Object.entries(props).forEach(([key, value]) => {
        if (key === 'children') return; // Handle separately
        
        const pattern = this.findMatchingPattern(value);
        if (pattern) {
          this.logDetection({
            type: 'PROP_OBJECT',
            component: this.getComponentName(componentType),
            prop: key,
            value: this.safeStringify(value),
            pattern: pattern.name,
            severity: pattern.severity,
            stackTrace: new Error().stack,
            timestamp: Date.now()
          });
        }
      });
    }
    
    checkObjectChild(child, componentType, location) {
      const pattern = this.findMatchingPattern(child);
      if (pattern) {
        this.logDetection({
          type: 'CHILD_OBJECT',
          component: this.getComponentName(componentType),
          location,
          value: this.safeStringify(child),
          pattern: pattern.name,
          severity: pattern.severity,
          stackTrace: new Error().stack,
          timestamp: Date.now()
        });
        
        // Immediate warning for HIGH severity
        if (pattern.severity === 'HIGH') {
          console.warn(`🚨 HIGH SEVERITY: Object detected in ${location} of ${this.getComponentName(componentType)}:`, child);
        }
      }
    }
    
    findMatchingPattern(value) {
      return this.objectPatterns.find(pattern => pattern.test(value));
    }
    
    getComponentName(type) {
      if (typeof type === 'string') return type;
      if (type && type.name) return type.name;
      if (type && type.displayName) return type.displayName;
      return 'Anonymous';
    }
    
    safeStringify(obj) {
      try {
        if (obj === null || obj === undefined) return String(obj);
        if (typeof obj === 'string' || typeof obj === 'number' || typeof obj === 'boolean') {
          return String(obj);
        }
        
        // Handle React elements
        if (obj && obj.$$typeof) {
          return `ReactElement(${obj.type})`;
        }
        
        // Handle circular references
        const cache = new Set();
        return JSON.stringify(obj, (key, value) => {
          if (typeof value === 'object' && value !== null) {
            if (cache.has(value)) return '[Circular]';
            cache.add(value);
          }
          return value;
        }, 2);
      } catch (e) {
        return `[Unstringifiable: ${e.message}]`;
      }
    }
    
    logDetection(entry) {
      this.detectionLog.push(entry);
      
      // Keep log size manageable
      if (this.detectionLog.length > 1000) {
        this.detectionLog = this.detectionLog.slice(-500);
      }
      
      // Auto-alert for HIGH severity
      if (entry.severity === 'HIGH') {
        console.error('🚨 HIGH SEVERITY OBJECT DETECTION:', entry);
      }
    }
    
    monitorDOMChanges() {
      if (!window.MutationObserver) return;
      
      this.observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
          if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(node => {
              if (node.nodeType === Node.TEXT_NODE) {
                const text = node.textContent || '';
                if (text.includes('[object Object]')) {
                  this.logDetection({
                    type: 'DOM_OBJECT_TEXT',
                    text,
                    parentElement: node.parentElement?.tagName || 'unknown',
                    timestamp: Date.now()
                  });
                  
                  console.warn('🚨 Object text detected in DOM:', {
                    text,
                    element: node.parentElement,
                    node
                  });
                }
              }
            });
          }
        });
      });
      
      this.observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true
      });
    }
    
    setupGlobalErrorHandler() {
      this.originalMethods.onerror = window.onerror;
      
      window.onerror = (message, source, lineno, colno, error) => {
        if (message.includes('Objects are not valid as React child')) {
          this.logDetection({
            type: 'GLOBAL_ERROR',
            message,
            source,
            lineno,
            colno,
            error: error ? error.stack : null,
            timestamp: Date.now()
          });
          
          console.error('🚨 Global React object error:', {
            message, source, lineno, colno, error
          });
        }
        
        if (this.originalMethods.onerror) {
          return this.originalMethods.onerror(message, source, lineno, colno, error);
        }
      };
    }
    
    restoreOriginalMethods() {
      Object.entries(this.originalMethods).forEach(([method, original]) => {
        if (method === 'createElement' && window.React) {
          window.React.createElement = original;
        } else if (method === 'render' && window.ReactDOM) {
          window.ReactDOM.render = original;
        } else if (method === 'error') {
          console.error = original;
        } else if (method === 'onerror') {
          window.onerror = original;
        }
      });
      
      if (this.observer) {
        this.observer.disconnect();
      }
    }
    
    getLog() {
      return this.detectionLog;
    }
    
    getRecentLog(count = 10) {
      return this.detectionLog.slice(-count);
    }
    
    getLogByType(type) {
      return this.detectionLog.filter(entry => entry.type === type);
    }
    
    getLogBySeverity(severity) {
      return this.detectionLog.filter(entry => entry.severity === severity);
    }
    
    analyzeDOM() {
      console.log('🔍 Analyzing DOM for object text...');
      
      const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );
      
      const objectTexts = [];
      let node;
      
      while (node = walker.nextNode()) {
        const text = node.textContent || '';
        if (text.includes('[object Object]')) {
          objectTexts.push({
            text,
            element: node.parentElement,
            html: node.parentElement?.innerHTML || ''
          });
        }
      }
      
      console.log(`Found ${objectTexts.length} object texts in DOM:`, objectTexts);
      return objectTexts;
    }
    
    findJadwalObjects() {
      console.log('🔍 Searching for jadwal objects in memory...');
      
      const results = [];
      
      // Check React Fiber tree if available
      if (window.React && window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED) {
        try {
          const internals = window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;
          console.log('React internals available:', internals);
        } catch (e) {
          console.log('React internals not accessible');
        }
      }
      
      // Check global variables
      for (let key in window) {
        try {
          const value = window[key];
          if (value && typeof value === 'object') {
            if (this.containsJadwal(value)) {
              results.push({
                location: `window.${key}`,
                value: this.safeStringify(value)
              });
            }
          }
        } catch (e) {
          // Skip inaccessible properties
        }
      }
      
      console.log(`Found ${results.length} jadwal objects:`, results);
      return results;
    }
    
    containsJadwal(obj, depth = 0) {
      if (depth > 3) return false; // Prevent infinite recursion
      
      try {
        if (obj && typeof obj === 'object') {
          // Direct jadwal properties
          if (obj.hasOwnProperty('jadwal') || 
              obj.hasOwnProperty('tanggal_jaga') ||
              obj.hasOwnProperty('shift_template')) {
            return true;
          }
          
          // Check nested objects
          for (let key in obj) {
            if (key.toLowerCase().includes('jadwal')) {
              return true;
            }
            if (typeof obj[key] === 'object' && this.containsJadwal(obj[key], depth + 1)) {
              return true;
            }
          }
        }
      } catch (e) {
        // Handle circular references or other issues
      }
      
      return false;
    }
    
    generateReport() {
      const report = {
        timestamp: new Date().toISOString(),
        summary: {
          totalDetections: this.detectionLog.length,
          highSeverity: this.getLogBySeverity('HIGH').length,
          mediumSeverity: this.getLogBySeverity('MEDIUM').length,
          consoleErrors: this.getLogByType('CONSOLE_ERROR').length,
          childObjects: this.getLogByType('CHILD_OBJECT').length,
          propObjects: this.getLogByType('PROP_OBJECT').length
        },
        recentDetections: this.getRecentLog(20),
        domAnalysis: this.analyzeDOM(),
        jadwalObjects: this.findJadwalObjects()
      };
      
      console.log('📊 DEBUG REPORT:', report);
      return report;
    }
  }
  
  // Auto-initialize when script loads
  new ReactObjectDebugger();
  
})();

// Additional utility functions for manual debugging
window.debugReactRender = function(component, props = {}) {
  console.log('🧪 Testing component render:', component.name || component);
  try {
    const element = React.createElement(component, props);
    console.log('✅ Component created successfully:', element);
    return element;
  } catch (error) {
    console.error('❌ Component creation failed:', error);
    return null;
  }
};

window.inspectReactProps = function(element) {
  if (!element || !element.props) {
    console.log('❌ Invalid React element');
    return;
  }
  
  console.group('🔍 React Element Props');
  console.log('Type:', element.type);
  console.log('Props:', element.props);
  
  Object.entries(element.props).forEach(([key, value]) => {
    if (value && typeof value === 'object' && !React.isValidElement(value)) {
      console.warn(`⚠️ Object prop detected: ${key}`, value);
    }
  });
  
  console.groupEnd();
};

console.log('🚀 React Object Debugging utilities loaded!');
console.log('Additional functions available:');
console.log('- window.debugReactRender(Component, props) - Test component creation');
console.log('- window.inspectReactProps(element) - Inspect element props');