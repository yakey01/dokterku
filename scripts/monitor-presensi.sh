#!/bin/bash

# Script untuk memantau console presensi dokter
# Monitoring script untuk endpoint server-time dan status presensi

echo "🔍 Starting Presensi Console Monitor..."
echo "======================================"
echo ""

# Function to test server-time endpoint
test_server_time() {
    echo "⏰ Testing Server Time Endpoint..."
    response=$(curl -s -X GET "http://127.0.0.1:8000/api/v2/server-time" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json")
    
    if [ $? -eq 0 ]; then
        echo "✅ Server Time Response:"
        echo "$response" | jq '.' 2>/dev/null || echo "$response"
    else
        echo "❌ Failed to get server time"
    fi
    echo ""
}

# Function to test work location status
test_work_location() {
    echo "📍 Testing Work Location Status..."
    response=$(curl -s -X GET "http://127.0.0.1:8000/api/v2/dashboards/dokter/work-location/status" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer YOUR_TOKEN_HERE")
    
    if [ $? -eq 0 ]; then
        echo "✅ Work Location Response:"
        echo "$response" | jq '.' 2>/dev/null || echo "$response"
    else
        echo "❌ Failed to get work location status"
    fi
    echo ""
}

# Function to monitor continuously
monitor_continuously() {
    echo "🔄 Starting Continuous Monitoring..."
    echo "Press Ctrl+C to stop"
    echo ""
    
    while true; do
        echo "=== $(date '+%Y-%m-%d %H:%M:%S') ==="
        test_server_time
        sleep 5
    done
}

# Main menu
case "${1:-}" in
    "server-time")
        test_server_time
        ;;
    "work-location")
        test_work_location
        ;;
    "monitor")
        monitor_continuously
        ;;
    *)
        echo "Usage: $0 {server-time|work-location|monitor}"
        echo ""
        echo "Commands:"
        echo "  server-time   - Test server-time endpoint"
        echo "  work-location - Test work location status"
        echo "  monitor       - Start continuous monitoring"
        echo ""
        echo "Example:"
        echo "  $0 server-time"
        echo "  $0 monitor"
        ;;
esac
