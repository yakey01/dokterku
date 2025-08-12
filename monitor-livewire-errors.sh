#!/bin/bash

echo "=== Monitoring Livewire Update Errors ==="
echo "Watching for 500 errors on Livewire update endpoint..."
echo "Press Ctrl+C to stop"
echo ""

# Monitor Laravel logs
echo "Monitoring Laravel logs..."
tail -f storage/logs/laravel.log | grep -E "livewire|update|500|Exception" &

# Create a simple test to verify the dashboard loads
echo ""
echo "Testing dashboard endpoint..."
curl -s -o /dev/null -w "Dashboard HTTP Status: %{http_code}\n" http://127.0.0.1:8000/petugas

echo ""
echo "Instructions:"
echo "1. Open http://127.0.0.1:8000/petugas in your browser"
echo "2. Login if needed"
echo "3. Check browser console for errors (F12)"
echo "4. Look for any 'update' requests in Network tab"
echo "5. Any errors will appear above in real-time"