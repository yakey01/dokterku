#!/bin/bash

echo "🚀 VERIFYING BOTTOM NAVIGATION FIX"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check if the new blade file exists
echo "1️⃣ Checking new Blade template..."
if [ -f "resources/views/mobile/dokter/app-fixed.blade.php" ]; then
    echo -e "${GREEN}✅ app-fixed.blade.php exists${NC}"
else
    echo -e "${RED}❌ app-fixed.blade.php not found${NC}"
    exit 1
fi

# Step 2: Verify route is updated
echo ""
echo "2️⃣ Checking route configuration..."
if grep -q "app-fixed" routes/web.php; then
    echo -e "${GREEN}✅ Route updated to use app-fixed template${NC}"
else
    echo -e "${RED}❌ Route not updated${NC}"
    exit 1
fi

# Step 3: Clear all caches
echo ""
echo "3️⃣ Clearing all caches..."
php artisan optimize:clear
echo -e "${GREEN}✅ All caches cleared${NC}"

# Step 4: Test the endpoint
echo ""
echo "4️⃣ Testing the endpoint..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/dokter/mobile-app)
if [ "$RESPONSE" == "302" ] || [ "$RESPONSE" == "200" ]; then
    echo -e "${GREEN}✅ Endpoint responding (HTTP $RESPONSE)${NC}"
else
    echo -e "${RED}❌ Endpoint error (HTTP $RESPONSE)${NC}"
fi

# Step 5: Check for navigation elements in the response
echo ""
echo "5️⃣ Checking for navigation elements..."
CONTENT=$(curl -s http://127.0.0.1:8000/dokter/mobile-app)
if echo "$CONTENT" | grep -q "bottom-navigation"; then
    echo -e "${GREEN}✅ Navigation elements found in HTML${NC}"
else
    echo -e "${YELLOW}⚠️  Navigation not found in initial HTML (may load via React)${NC}"
fi

# Step 6: Instructions for manual testing
echo ""
echo "📱 MANUAL TESTING INSTRUCTIONS:"
echo "================================"
echo ""
echo "1. Open browser: http://127.0.0.1:8000/dokter/mobile-app"
echo "2. Login with dokter credentials"
echo "3. Open DevTools (F12) and toggle device mode (Ctrl+Shift+M)"
echo "4. Select iPhone or any mobile device"
echo "5. Refresh the page (F5)"
echo ""
echo "🔍 WHAT TO LOOK FOR:"
echo "- Bottom navigation should be visible at the bottom of the screen"
echo "- Navigation has purple gradient background"
echo "- 5 buttons: Home, Missions, Guardian, Rewards, Profile"
echo "- Navigation should be responsive to clicks"
echo ""
echo "🧪 TEST PAGE AVAILABLE:"
echo "Open: http://127.0.0.1:8000/test-navigation-fixed.html"
echo ""
echo "✨ If navigation still doesn't appear:"
echo "1. Clear browser cache completely"
echo "2. Use incognito/private mode"
echo "3. Try a different browser"
echo "4. Check console for any errors"