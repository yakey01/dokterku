<?php
/**
 * Create API test token for Dr. Yaya Mulyana
 */

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find the user
$user = App\Models\User::find(13); // Dr. Yaya Mulyana

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

// Create token
$token = $user->createToken('checkin-validation-test');

echo "✅ Token created for: {$user->name}\n";
echo "🔑 Token: {$token->plainTextToken}\n";
echo "👤 User ID: {$user->id}\n";
echo "📧 Email: {$user->email}\n";
echo "🏢 Work Location ID: {$user->work_location_id}\n";

if ($user->workLocation) {
    echo "📍 Work Location: {$user->workLocation->name}\n";
    echo "🗺️  Coordinates: {$user->workLocation->latitude}, {$user->workLocation->longitude}\n";
    echo "📏 Radius: {$user->workLocation->radius_meters}m\n";
}