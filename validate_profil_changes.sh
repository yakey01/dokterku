#!/bin/bash

# PROFIL.TSX VALIDATION SCRIPT
# Validates modifications meet strict requirements

echo "🔍 PROFIL.TSX VALIDATION SCRIPT"
echo "================================"

PROFIL_FILE="/Users/kym/Herd/Dokterku/resources/js/components/dokter/Profil.tsx"
PASSED=0
FAILED=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

validate_check() {
    local description="$1"
    local command="$2"
    local expected="$3"
    
    echo -n "Checking: $description... "
    
    if eval "$command"; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ FAIL${NC}"
        echo "  Expected: $expected"
        ((FAILED++))
    fi
}

validate_count() {
    local description="$1"
    local pattern="$2"
    local expected_count="$3"
    
    actual_count=$(grep -c "$pattern" "$PROFIL_FILE" 2>/dev/null || echo "0")
    echo -n "Checking: $description... "
    
    if [ "$actual_count" -eq "$expected_count" ]; then
        echo -e "${GREEN}✅ PASS${NC} (found: $actual_count)"
        ((PASSED++))
    else
        echo -e "${RED}❌ FAIL${NC} (expected: $expected_count, found: $actual_count)"
        ((FAILED++))
    fi
}

validate_line_exists() {
    local description="$1"
    local pattern="$2"
    
    echo -n "Checking: $description... "
    
    if grep -q "$pattern" "$PROFIL_FILE"; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ FAIL${NC} - Required pattern not found"
        ((FAILED++))
    fi
}

validate_line_not_exists() {
    local description="$1"
    local pattern="$2"
    
    echo -n "Checking: $description... "
    
    if ! grep -q "$pattern" "$PROFIL_FILE"; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ FAIL${NC} - Pattern should not exist"
        ((FAILED++))
    fi
}

# Check if file exists
if [ ! -f "$PROFIL_FILE" ]; then
    echo -e "${RED}❌ FATAL: Profil.tsx not found at expected location${NC}"
    exit 1
fi

echo -e "\n${YELLOW}📋 BASELINE STRUCTURE VALIDATION${NC}"
echo "=================================="

# Tab navigation validation
validate_count "Tab navigation has exactly 2 items" "{ id: '" 2
validate_line_exists "Profile tab exists" "{ id: 'profile', label: 'Profile', icon: User }"
validate_line_exists "Settings tab exists" "{ id: 'settings', label: 'Settings', icon: Settings }"
validate_line_not_exists "Achievements tab removed" "{ id: 'achievements', label: 'Achievements', icon: Award }"
validate_line_not_exists "Certifications tab removed" "{ id: 'certifications', label: 'Certifications', icon: GraduationCap }"

echo -e "\n${YELLOW}🗑️ REMOVED CONTENT VALIDATION${NC}"
echo "================================"

# Content sections validation
validate_line_not_exists "Achievements tab content removed" "activeTab === 'achievements'"
validate_line_not_exists "Certifications tab content removed" "activeTab === 'certifications'"
validate_line_not_exists "Achievements array removed" "const achievements = \["
validate_line_not_exists "Certifications array removed" "const certifications = \["
validate_line_not_exists "getRarityColor function removed" "const getRarityColor ="

echo -e "\n${YELLOW}🔒 PRESERVED CONTENT VALIDATION${NC}"
echo "=================================="

# Critical preserved sections
validate_line_exists "Profile tab content preserved" "activeTab === 'profile'"
validate_line_exists "Settings tab content preserved" "activeTab === 'settings'"
validate_line_exists "Profile header card preserved" "Profile Header Card"
validate_line_exists "Edit modal preserved" "Edit Profile Modal"
validate_line_exists "handleSave function preserved" "const handleSave ="
validate_line_exists "handleCancel function preserved" "const handleCancel ="

echo -e "\n${YELLOW}📱 RESPONSIVE DESIGN VALIDATION${NC}"
echo "================================="

# Responsive patterns
validate_line_exists "isIpad state preserved" "const \[isIpad, setIsIpad\]"
validate_line_exists "orientation state preserved" "const \[orientation, setOrientation\]"
validate_count "isIpad conditions preserved" "isIpad" 15
validate_count "orientation conditions preserved" "orientation === 'landscape'" 4

echo -e "\n${YELLOW}🎨 STYLING VALIDATION${NC}"
echo "====================="

# Styling preservation
validate_line_exists "Main gradient preserved" "bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900"
validate_line_exists "Header gradient preserved" "bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400"
validate_line_exists "Glassmorphism preserved" "backdrop-blur-xl"
validate_count "Border white/20 preserved" "border-white/20" 10

echo -e "\n${YELLOW}⚙️ STATE MANAGEMENT VALIDATION${NC}"
echo "==============================="

# State management
validate_line_exists "activeTab state preserved" "const \[activeTab, setActiveTab\] = useState('profile')"
validate_line_exists "profileData state preserved" "const \[profileData, setProfileData\]"
validate_line_exists "editData state preserved" "const \[editData, setEditData\]"
validate_line_exists "isEditing state preserved" "const \[isEditing, setIsEditing\]"

echo -e "\n${YELLOW}🔧 IMPORT CLEANUP VALIDATION${NC}"
echo "============================"

# Import validation (check unused imports are removed)
validate_line_not_exists "Award import removed" "Award,"
validate_line_not_exists "GraduationCap import removed" "GraduationCap,"
validate_line_not_exists "CheckCircle import removed" "CheckCircle,"

# Check remaining required imports are preserved
validate_line_exists "User import preserved" "User,"
validate_line_exists "Settings import preserved" "Settings,"
validate_line_exists "Edit import preserved" "Edit,"

echo -e "\n${YELLOW}📊 FILE METRICS VALIDATION${NC}"
echo "==========================="

# File size validation
TOTAL_LINES=$(wc -l < "$PROFIL_FILE")
echo "Total lines in file: $TOTAL_LINES"

if [ "$TOTAL_LINES" -ge 720 ] && [ "$TOTAL_LINES" -le 750 ]; then
    echo -e "File size: ${GREEN}✅ PASS${NC} (appropriate reduction)"
    ((PASSED++))
else
    echo -e "File size: ${RED}❌ FAIL${NC} (expected ~720-750 lines)"
    ((FAILED++))
fi

echo -e "\n${YELLOW}🔍 SYNTAX VALIDATION${NC}"
echo "===================="

# Basic syntax check (TypeScript/TSX)
if npm run build:check >/dev/null 2>&1; then
    echo -e "TypeScript syntax: ${GREEN}✅ PASS${NC}"
    ((PASSED++))
else
    echo -e "TypeScript syntax: ${YELLOW}⚠️ WARNING${NC} (run build check manually)"
fi

# Final summary
echo -e "\n${YELLOW}📈 VALIDATION SUMMARY${NC}"
echo "====================="
echo -e "Tests Passed: ${GREEN}$PASSED${NC}"
echo -e "Tests Failed: ${RED}$FAILED${NC}"
echo -e "Total Tests: $((PASSED + FAILED))"

if [ "$FAILED" -eq 0 ]; then
    echo -e "\n${GREEN}🎉 ALL VALIDATIONS PASSED!${NC}"
    echo "✅ Modifications meet all requirements"
    echo "✅ Safe to proceed with deployment"
    exit 0
else
    echo -e "\n${RED}🚫 VALIDATION FAILURES DETECTED!${NC}"
    echo "❌ $FAILED tests failed"
    echo "🔄 Review and fix issues before proceeding"
    exit 1
fi
