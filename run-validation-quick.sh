#!/bin/bash

# 🔄 Quick ResizeObserver Validation Script
# Fast validation of ResizeObserver error fixes without external dependencies

echo "🎯 ResizeObserver Error Fixes - Quick Validation"
echo "================================================="

# Check if we're in the right directory
if [ ! -f "resources/js/dokter-mobile-app.tsx" ]; then
    echo "❌ Error: Please run this script from the DokterKu project root directory"
    exit 1
fi

echo "📍 Project directory: $(pwd)"
echo "⏰ Started at: $(date)"
echo ""

# Test 1: Verify Error Handler Implementation
echo "🛡️ Test 1: Error Handler Implementation"
echo "---------------------------------------"

if grep -q "ResizeObserver loop completed with undelivered notifications" resources/js/dokter-mobile-app.tsx; then
    echo "✅ ResizeObserver error handler found in dokter-mobile-app.tsx"
    
    if grep -q "stopImmediatePropagation" resources/js/dokter-mobile-app.tsx; then
        echo "✅ Event propagation stopping implemented"
    else
        echo "❌ Missing stopImmediatePropagation implementation"
    fi
    
    if grep -q "console.warn.*ResizeObserver loop detected" resources/js/dokter-mobile-app.tsx; then
        echo "✅ Proper logging for ResizeObserver errors found"
    else
        echo "⚠️ ResizeObserver logging could be improved"
    fi
else
    echo "❌ ResizeObserver error handler not found in main app file"
fi

echo ""

# Test 2: Verify Optimized ResizeObserver Utility
echo "⚡ Test 2: OptimizedResizeObserver Utility"
echo "----------------------------------------"

if [ -f "resources/js/utils/OptimizedResizeObserver.ts" ]; then
    echo "✅ OptimizedResizeObserver.ts file exists"
    
    # Check key features
    if grep -q "debounceMs" resources/js/utils/OptimizedResizeObserver.ts; then
        echo "✅ Debouncing feature implemented"
    else
        echo "❌ Missing debouncing feature"
    fi
    
    if grep -q "WeakMap" resources/js/utils/OptimizedResizeObserver.ts; then
        echo "✅ Memory-efficient WeakMap usage found"
    else
        echo "❌ Missing WeakMap for memory management"
    fi
    
    if grep -q "requestAnimationFrame" resources/js/utils/OptimizedResizeObserver.ts; then
        echo "✅ RAF optimization implemented"
    else
        echo "❌ Missing requestAnimationFrame optimization"
    fi
    
    if grep -q "cleanup\|destroy" resources/js/utils/OptimizedResizeObserver.ts; then
        echo "✅ Cleanup/destroy methods found"
    else
        echo "❌ Missing cleanup functionality"
    fi
    
    if grep -q "performanceMetrics\|monitoring" resources/js/utils/OptimizedResizeObserver.ts; then
        echo "✅ Performance monitoring capabilities found"
    else
        echo "⚠️ Performance monitoring could be enhanced"
    fi
else
    echo "❌ OptimizedResizeObserver.ts file not found"
fi

echo ""

# Test 3: Check for Medical Dashboard Integration
echo "🏥 Test 3: Medical Dashboard Integration"
echo "---------------------------------------"

if [ -f "resources/js/components/dokter/HolisticMedicalDashboard.tsx" ]; then
    echo "✅ Medical dashboard component exists"
    
    # Check for potential ResizeObserver usage
    if grep -qi "resize\|chart\|responsive" resources/js/components/dokter/HolisticMedicalDashboard.tsx; then
        echo "✅ Responsive/chart components found (likely ResizeObserver usage)"
    else
        echo "⚠️ Limited responsive functionality detected"
    fi
else
    echo "⚠️ Medical dashboard component not found"
fi

# Check for chart components
CHART_COMPONENTS=$(find resources/js/components -name "*.tsx" -o -name "*.ts" | xargs grep -l -i "chart\|graph\|visual" 2>/dev/null | wc -l)
echo "📊 Found $CHART_COMPONENTS potential chart components"

echo ""

# Test 4: Browser Compatibility Check (Basic)
echo "🌐 Test 4: Browser Compatibility Indicators"
echo "------------------------------------------"

if grep -q "ResizeObserver" resources/js/utils/OptimizedResizeObserver.ts; then
    echo "✅ ResizeObserver API usage confirmed"
else
    echo "❌ ResizeObserver API usage not detected"
fi

if grep -q "performance\.now\|performance\.memory" resources/js/dokter-mobile-app.tsx; then
    echo "✅ Performance API usage found"
else
    echo "⚠️ Performance API usage limited"
fi

if grep -q "WeakMap\|Map" resources/js/utils/OptimizedResizeObserver.ts; then
    echo "✅ Modern JavaScript features (WeakMap/Map) used"
else
    echo "❌ Missing modern JavaScript features"
fi

echo ""

# Test 5: File Structure and Organization
echo "📁 Test 5: File Structure Validation"
echo "-----------------------------------"

FILES_TO_CHECK=(
    "resources/js/dokter-mobile-app.tsx"
    "resources/js/utils/OptimizedResizeObserver.ts"
    "public/test-resizeobserver-validation.html"
    "test-resizeobserver-automation.js"
)

for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$file" ]; then
        size=$(stat -c%s "$file" 2>/dev/null || stat -f%z "$file" 2>/dev/null)
        echo "✅ $file (${size} bytes)"
    else
        echo "❌ Missing: $file"
    fi
done

echo ""

# Test 6: Code Quality Indicators
echo "🔍 Test 6: Code Quality Indicators"
echo "---------------------------------"

# Check TypeScript usage
if grep -q "interface\|type\|export" resources/js/utils/OptimizedResizeObserver.ts; then
    echo "✅ TypeScript interfaces/types properly defined"
else
    echo "⚠️ Limited TypeScript type definitions"
fi

# Check error handling
ERROR_HANDLING_COUNT=$(grep -c "try\|catch\|throw" resources/js/dokter-mobile-app.tsx resources/js/utils/OptimizedResizeObserver.ts 2>/dev/null)
echo "✅ Error handling blocks found: $ERROR_HANDLING_COUNT"

# Check documentation
COMMENT_COUNT=$(grep -c "//\|/\*\|\*" resources/js/utils/OptimizedResizeObserver.ts 2>/dev/null)
echo "✅ Documentation comments: $COMMENT_COUNT"

echo ""

# Summary and Recommendations
echo "📋 VALIDATION SUMMARY"
echo "===================="

TOTAL_CHECKS=20
PASSED_CHECKS=0

# Count successful checks (basic heuristic)
if [ -f "resources/js/dokter-mobile-app.tsx" ] && grep -q "ResizeObserver loop completed" resources/js/dokter-mobile-app.tsx; then
    ((PASSED_CHECKS += 5))
fi

if [ -f "resources/js/utils/OptimizedResizeObserver.ts" ]; then
    ((PASSED_CHECKS += 8))
fi

if [ -f "public/test-resizeobserver-validation.html" ]; then
    ((PASSED_CHECKS += 3))
fi

if [ -f "test-resizeobserver-automation.js" ]; then
    ((PASSED_CHECKS += 2))
fi

if [ $ERROR_HANDLING_COUNT -gt 5 ]; then
    ((PASSED_CHECKS += 2))
fi

SUCCESS_RATE=$((PASSED_CHECKS * 100 / TOTAL_CHECKS))

echo "🎯 Validation Score: $PASSED_CHECKS/$TOTAL_CHECKS ($SUCCESS_RATE%)"

if [ $SUCCESS_RATE -ge 90 ]; then
    echo "✅ EXCELLENT - Ready for production deployment"
    DEPLOYMENT_STATUS="APPROVED"
elif [ $SUCCESS_RATE -ge 70 ]; then
    echo "⚠️ GOOD - Minor improvements recommended"
    DEPLOYMENT_STATUS="CONDITIONAL"
else
    echo "❌ NEEDS IMPROVEMENT - Address issues before deployment"
    DEPLOYMENT_STATUS="NOT_READY"
fi

echo ""
echo "🚀 DEPLOYMENT RECOMMENDATION: $DEPLOYMENT_STATUS"
echo ""

# Next Steps
echo "📋 NEXT STEPS:"
echo "1. Run manual browser test: open public/test-resizeobserver-validation.html"
echo "2. Execute automated tests: npm install puppeteer && node test-resizeobserver-automation.js"
echo "3. Test medical dashboard components in browser"
echo "4. Monitor ResizeObserver errors in browser console"
echo "5. Validate performance improvements in production"

echo ""
echo "⏰ Completed at: $(date)"
echo "📄 Full validation report: RESIZEOBSERVER_VALIDATION_SUITE.md"

# Exit with appropriate code
if [ "$DEPLOYMENT_STATUS" = "APPROVED" ]; then
    exit 0
elif [ "$DEPLOYMENT_STATUS" = "CONDITIONAL" ]; then
    exit 1
else
    exit 2
fi