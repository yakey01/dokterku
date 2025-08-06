#!/bin/bash

# Start development servers for Dokterku

echo "🚀 Starting Dokterku Development Environment..."

# Check if Vite is already running on port 5173
if lsof -Pi :5173 -sTCP:LISTEN -t >/dev/null ; then
    echo "⚠️  Vite is already running on port 5173"
    echo "   Run 'npm run dev' manually if you need to restart it"
else
    echo "✅ Starting Vite development server..."
    npm run dev &
    VITE_PID=$!
    echo "   Vite started with PID: $VITE_PID"
fi

# Check if Laravel is already running on port 8000
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "⚠️  Laravel is already running on port 8000"
else
    echo "✅ Starting Laravel development server..."
    php artisan serve &
    LARAVEL_PID=$!
    echo "   Laravel started with PID: $LARAVEL_PID"
fi

echo ""
echo "🎉 Development environment is ready!"
echo "   Laravel: http://127.0.0.1:8000"
echo "   Vite: http://127.0.0.1:5173"
echo ""
echo "📱 Access the Dokter Mobile App at:"
echo "   http://127.0.0.1:8000/dokter/mobile-app"
echo ""
echo "To stop all services, press Ctrl+C"

# Wait for user to press Ctrl+C
wait