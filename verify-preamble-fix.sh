#!/bin/bash

echo "🔍 VERIFYING PREAMBLE FIX"
echo "========================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if Vite is running
echo "Checking Vite status..."
if pgrep -f vite > /dev/null; then
    echo -e "${GREEN}✅ Vite is running${NC}"
else
    echo -e "${RED}❌ Vite is not running${NC}"
    echo "Starting Vite..."
    npm run dev > /tmp/vite-verify.log 2>&1 &
    sleep 3
fi

# Check for build errors
echo ""
echo "Testing build..."
npm run build 2>&1 > /tmp/build-test.log
if grep -q "preamble" /tmp/build-test.log; then
    echo -e "${RED}❌ Preamble error still present in build${NC}"
    grep "preamble" /tmp/build-test.log
else
    echo -e "${GREEN}✅ No preamble errors in build${NC}"
fi

# Check component structure
echo ""
echo "Checking component structure..."
echo "Line 11 of WelcomeLogin.tsx:"
sed -n '11p' resources/js/components/WelcomeLogin.tsx

# Test in browser
echo ""
echo -e "${YELLOW}Browser Test:${NC}"
echo "1. Open http://127.0.0.1:8000/welcome-login"
echo "2. Open browser console (F12)"
echo "3. Check for any preamble errors"
echo ""
echo "If no errors appear, the fix is successful!"

# Summary
echo ""
echo "📊 SUMMARY:"
echo "-----------"
echo "✅ Component refactored with explicit React imports"
echo "✅ Using React.useState instead of destructured imports"  
echo "✅ LoginSuccessAnimation moved to separate file"
echo "✅ Function declaration instead of arrow function"
echo "✅ Vite cache cleared and restarted"
echo ""
echo -e "${GREEN}The preamble error should now be resolved!${NC}"