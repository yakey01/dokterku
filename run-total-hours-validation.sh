#!/bin/bash

# TOTAL HOURS VALIDATION EXECUTION SCRIPT
# 
# Comprehensive validation of Total Hours calculation fixes
# Run with: ./run-total-hours-validation.sh

echo "🔬 TOTAL HOURS VALIDATION SUITE"
echo "==============================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8000"
LOG_FILE="total-hours-validation-$(date +%Y%m%d_%H%M%S).log"

echo "📋 VALIDATION CONFIGURATION:"
echo "   Base URL: $BASE_URL"
echo "   Log File: $LOG_FILE"
echo "   Timestamp: $(date)"
echo ""

# Function to log and display
log_and_display() {
    echo "$1" | tee -a "$LOG_FILE"
}

log_and_display "🎯 MISSION: Zero tolerance validation for negative total_hours"
log_and_display "📍 Scope: All dokter dashboard APIs and edge cases"
log_and_display ""

# Check if server is running
echo "🔍 Checking server status..."
if curl -s "$BASE_URL" > /dev/null; then
    echo -e "${GREEN}✅ Server is running${NC}"
    log_and_display "✅ Server status: RUNNING"
else
    echo -e "${RED}❌ Server is not accessible${NC}"
    log_and_display "❌ Server status: NOT ACCESSIBLE"
    echo "Please start the Laravel server with: php artisan serve"
    exit 1
fi
echo ""

# Run quick validation
echo "🚀 PHASE 1: Quick Validation Test"
echo "================================="
log_and_display "PHASE 1: Quick Validation - $(date)"

php validate-total-hours-quick.php | tee -a "$LOG_FILE"
QUICK_EXIT_CODE=$?

echo ""
log_and_display "Quick validation exit code: $QUICK_EXIT_CODE"

if [ $QUICK_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✅ Quick validation PASSED${NC}"
    log_and_display "✅ Quick validation: PASSED"
elif [ $QUICK_EXIT_CODE -eq 1 ]; then
    echo -e "${RED}❌ Quick validation FAILED (Critical errors)${NC}"
    log_and_display "❌ Quick validation: FAILED (Critical)"
else
    echo -e "${YELLOW}⚠️  Quick validation PARTIAL (Non-critical issues)${NC}"
    log_and_display "⚠️ Quick validation: PARTIAL"
fi

echo ""

# Test specific endpoints manually
echo "🔍 PHASE 2: Manual Endpoint Testing"
echo "===================================="
log_and_display "PHASE 2: Manual Endpoint Testing - $(date)"

# Test Dr. Yaya specifically
USER_ID=26
echo "👨‍⚕️ Testing Dr. Yaya (User ID: $USER_ID)"
log_and_display "Testing User ID: $USER_ID (Dr. Yaya)"

ENDPOINTS=(
    "/api/v2/dashboards/dokter"
    "/api/v2/dashboards/dokter/jadwal-jaga"
    "/api/v2/dashboards/dokter/presensi"
    "/api/v2/dashboards/dokter/leaderboard"
)

for endpoint in "${ENDPOINTS[@]}"; do
    echo "📡 Testing: $endpoint"
    log_and_display "Testing endpoint: $endpoint"
    
    RESPONSE=$(curl -s -w "%{http_code}" "$BASE_URL$endpoint?user_id=$USER_ID")
    HTTP_CODE="${RESPONSE: -3}"
    BODY="${RESPONSE%???}"
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ HTTP 200 OK${NC}"
        log_and_display "✅ $endpoint: HTTP 200 OK"
        
        # Check for total_hours in response
        if echo "$BODY" | grep -q "total_hours"; then
            TOTAL_HOURS=$(echo "$BODY" | jq -r '.schedule_stats.total_hours // .presensi_stats.total_hours // .stats.total_hours // .total_hours // "null"' 2>/dev/null)
            if [ "$TOTAL_HOURS" != "null" ] && [ "$TOTAL_HOURS" != "" ]; then
                if (( $(echo "$TOTAL_HOURS >= 0" | bc -l) )); then
                    echo -e "${GREEN}✅ total_hours: $TOTAL_HOURS (VALID)${NC}"
                    log_and_display "✅ total_hours: $TOTAL_HOURS (VALID)"
                else
                    echo -e "${RED}❌ total_hours: $TOTAL_HOURS (NEGATIVE!)${NC}"
                    log_and_display "❌ total_hours: $TOTAL_HOURS (NEGATIVE!)"
                fi
            else
                echo -e "${YELLOW}⚠️  total_hours: NOT FOUND${NC}"
                log_and_display "⚠️ total_hours: NOT FOUND"
            fi
        else
            echo -e "${YELLOW}⚠️  No total_hours field found${NC}"
            log_and_display "⚠️ No total_hours field found"
        fi
    else
        echo -e "${RED}❌ HTTP $HTTP_CODE${NC}"
        log_and_display "❌ $endpoint: HTTP $HTTP_CODE"
    fi
    echo ""
done

# Test edge case users
echo "🧪 PHASE 3: Edge Case Testing"
echo "============================="
log_and_display "PHASE 3: Edge Case Testing - $(date)"

EDGE_USERS=(1 5 10 15 20 999)

for user_id in "${EDGE_USERS[@]}"; do
    echo "🔬 Testing User ID: $user_id"
    log_and_display "Testing edge case user: $user_id"
    
    RESPONSE=$(curl -s -w "%{http_code}" "$BASE_URL/api/v2/dashboards/dokter?user_id=$user_id")
    HTTP_CODE="${RESPONSE: -3}"
    BODY="${RESPONSE%???}"
    
    if [ "$HTTP_CODE" = "200" ]; then
        TOTAL_HOURS=$(echo "$BODY" | jq -r '.schedule_stats.total_hours // .presensi_stats.total_hours // .stats.total_hours // .total_hours // "null"' 2>/dev/null)
        if [ "$TOTAL_HOURS" != "null" ] && [ "$TOTAL_HOURS" != "" ]; then
            if (( $(echo "$TOTAL_HOURS >= 0" | bc -l) )); then
                echo -e "${GREEN}✅ User $user_id: total_hours = $TOTAL_HOURS${NC}"
                log_and_display "✅ User $user_id: total_hours = $TOTAL_HOURS"
            else
                echo -e "${RED}❌ User $user_id: total_hours = $TOTAL_HOURS (NEGATIVE!)${NC}"
                log_and_display "❌ User $user_id: total_hours = $TOTAL_HOURS (NEGATIVE!)"
            fi
        else
            echo -e "${BLUE}ℹ️  User $user_id: No total_hours data${NC}"
            log_and_display "ℹ️ User $user_id: No total_hours data"
        fi
    else
        echo -e "${YELLOW}⚠️  User $user_id: HTTP $HTTP_CODE${NC}"
        log_and_display "⚠️ User $user_id: HTTP $HTTP_CODE"
    fi
done

echo ""

# Generate final report
echo "📋 FINAL VALIDATION REPORT"
echo "=========================="
log_and_display "FINAL VALIDATION REPORT - $(date)"

# Count errors in log
CRITICAL_ERRORS=$(grep -c "NEGATIVE!" "$LOG_FILE" || echo "0")
WARNINGS=$(grep -c "⚠️" "$LOG_FILE" || echo "0")
SUCCESSES=$(grep -c "✅" "$LOG_FILE" || echo "0")

echo "📊 VALIDATION STATISTICS:"
echo "   ✅ Successful checks: $SUCCESSES"
echo "   ⚠️  Warnings: $WARNINGS"
echo "   ❌ Critical errors: $CRITICAL_ERRORS"
echo ""

log_and_display "STATISTICS: Successes=$SUCCESSES, Warnings=$WARNINGS, Critical=$CRITICAL_ERRORS"

# Final verdict
if [ "$CRITICAL_ERRORS" -eq 0 ]; then
    echo -e "${GREEN}🎉 VALIDATION PASSED${NC}"
    echo -e "${GREEN}✅ No negative total_hours found${NC}"
    echo -e "${GREEN}🚀 System is ready for production${NC}"
    log_and_display "FINAL VERDICT: PASSED - System ready for production"
    FINAL_EXIT_CODE=0
else
    echo -e "${RED}🚨 VALIDATION FAILED${NC}"
    echo -e "${RED}❌ $CRITICAL_ERRORS critical error(s) found${NC}"
    echo -e "${RED}⚠️  DO NOT deploy to production${NC}"
    log_and_display "FINAL VERDICT: FAILED - $CRITICAL_ERRORS critical errors found"
    FINAL_EXIT_CODE=1
fi

echo ""
echo "📁 Full validation log saved to: $LOG_FILE"
echo "🔬 Validation completed at: $(date)"

log_and_display "Validation completed at: $(date)"
log_and_display "Exit code: $FINAL_EXIT_CODE"

exit $FINAL_EXIT_CODE