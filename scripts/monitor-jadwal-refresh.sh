#!/bin/bash

# Script untuk monitoring refresh jadwal jaga
# Monitoring script untuk testing cache invalidation dan refresh mechanism

echo "🔍 Starting Jadwal Jaga Refresh Monitor..."
echo "=========================================="
echo ""

# Function to test jadwal jaga endpoint
test_jadwal_jaga() {
    local user_id=${1:-13}  # Default to user 13 who has jadwal jaga
    local is_refresh=${2:-false}
    
    echo "⏰ Testing Jadwal Jaga Endpoint..."
    echo "User ID: $user_id"
    echo "Refresh: $is_refresh"
    
    local url="http://127.0.0.1:8000/api/v2/jadwal-jaga/test?user_id=$user_id"
    if [ "$is_refresh" = "true" ]; then
        url="${url}&refresh=$(date +%s)"
    fi
    
    local response=$(curl -s -X GET "$url" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json")
    
    if [ $? -eq 0 ]; then
        echo "✅ Jadwal Jaga Response:"
        echo "$response" | jq '.' 2>/dev/null || echo "$response"
        
        # Extract cache info
        local cache_info=$(echo "$response" | jq -r '.data.cache_info.cached_at // empty' 2>/dev/null)
        local cache_ttl=$(echo "$response" | jq -r '.data.cache_info.cache_ttl // empty' 2>/dev/null)
        local is_refresh_api=$(echo "$response" | jq -r '.data.cache_info.is_refresh // empty' 2>/dev/null)
        
        if [ ! -z "$cache_info" ]; then
            echo ""
            echo "📊 Cache Info:"
            echo "  Cached At: $cache_info"
            echo "  Cache TTL: $cache_ttl seconds"
            echo "  Is Refresh: $is_refresh_api"
        fi
        
        # Count schedules
        local calendar_count=$(echo "$response" | jq '.data.calendar_events | length' 2>/dev/null || echo "0")
        local weekly_count=$(echo "$response" | jq '.data.weekly_schedule | length' 2>/dev/null || echo "0")
        local today_count=$(echo "$response" | jq '.data.today | length' 2>/dev/null || echo "0")
        
        echo ""
        echo "📅 Schedule Counts:"
        echo "  Calendar Events: $calendar_count"
        echo "  Weekly Schedule: $weekly_count"
        echo "  Today Schedule: $today_count"
        
    else
        echo "❌ Failed to get jadwal jaga"
    fi
    echo ""
}

# Function to test cache invalidation
test_cache_invalidation() {
    echo "🗑️ Testing Cache Invalidation..."
    
    # Test normal request
    echo "1. Normal request (should use cache):"
    test_jadwal_jaga 13 false
    
    # Test refresh request
    echo "2. Refresh request (should clear cache):"
    test_jadwal_jaga 13 true
    
    # Test normal request again
    echo "3. Normal request again (should use new cache):"
    test_jadwal_jaga 13 false
}

# Function to monitor real-time
monitor_realtime() {
    echo "🔄 Starting Real-time Monitor..."
    echo "Press Ctrl+C to stop"
    echo ""
    
    local counter=1
    while true; do
        echo "=== Check #$counter ==="
        echo "Time: $(date '+%Y-%m-%d %H:%M:%S')"
        
        test_jadwal_jaga 13 false
        
        echo "Waiting 30 seconds..."
        sleep 30
        counter=$((counter + 1))
        echo ""
    done
}

# Function to test database changes
test_database_changes() {
    echo "🗄️ Testing Database Changes..."
    
    echo "Current jadwal jaga in database:"
    php artisan tinker --execute="echo 'Total JadwalJaga: ' . App\Models\JadwalJaga::count(); echo PHP_EOL; echo 'Today JadwalJaga: ' . App\Models\JadwalJaga::whereDate('tanggal_jaga', now())->count(); echo PHP_EOL;"
    
    echo ""
    echo "Testing cache after database query:"
    test_jadwal_jaga 13 false
}

# Main menu
case "${1:-help}" in
    "test")
        test_jadwal_jaga "${2:-13}" "${3:-false}"
        ;;
    "refresh")
        test_jadwal_jaga "${2:-13}" "true"
        ;;
    "cache")
        test_cache_invalidation
        ;;
    "monitor")
        monitor_realtime
        ;;
    "db")
        test_database_changes
        ;;
    "help"|*)
        echo "Usage: $0 [command] [user_id] [refresh]"
        echo ""
        echo "Commands:"
        echo "  test [user_id] [refresh]  - Test jadwal jaga endpoint"
        echo "  refresh [user_id]         - Test refresh endpoint"
        echo "  cache                     - Test cache invalidation"
        echo "  monitor                   - Real-time monitoring"
        echo "  db                        - Test database changes"
        echo "  help                      - Show this help"
        echo ""
        echo "Examples:"
        echo "  $0 test 13 false          - Test user 13 without refresh"
        echo "  $0 test 13 true           - Test user 13 with refresh"
        echo "  $0 cache                  - Test cache invalidation"
        echo "  $0 monitor                - Start real-time monitoring"
        ;;
esac
