<?php

// Debug script to test WebSocket broadcasting
// Run this with: php scripts/debug-websockets.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Events\OrderPlacedNotification;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\App;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 WebSocket Broadcasting Debug Script\n";
echo "=====================================\n\n";

// Check environment variables
echo "1. Environment Variables:\n";
echo "   BROADCAST_DRIVER: " . env('BROADCAST_DRIVER', 'null') . "\n";
echo "   PUSHER_APP_KEY: " . env('PUSHER_APP_KEY', 'not set') . "\n";
echo "   PUSHER_HOST: " . env('PUSHER_HOST', 'not set') . "\n";
echo "   PUSHER_PORT: " . env('PUSHER_PORT', 'not set') . "\n\n";

// Check if broadcasting is enabled
if (env('BROADCAST_DRIVER') !== 'pusher') {
    echo "❌ BROADCAST_DRIVER is not set to 'pusher'\n";
    echo "   Please set BROADCAST_DRIVER=pusher in your .env file\n\n";
} else {
    echo "✅ Broadcasting driver is set to pusher\n\n";
}

// Test database connection
echo "2. Database Connection:\n";
try {
    $userCount = User::count();
    $orderCount = Order::count();
    $notificationCount = Notification::count();
    
    echo "   ✅ Database connected successfully\n";
    echo "   Users: $userCount, Orders: $orderCount, Notifications: $notificationCount\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Create test notification and order
echo "3. Creating Test Data:\n";
try {
    // Get or create a test user
    $user = User::first();
    if (!$user) {
        echo "   ❌ No users found in database. Please create a user first.\n\n";
        exit(1);
    }
    
    // Create test notification
    $notification = Notification::create([
        'created_by' => $user->id,
        'type' => 'order.placed',
        'title' => 'Test Order Notification',
        'message' => 'This is a test notification from the debug script',
        'data' => [
            'order_id' => 999,
            'order_number' => 'TEST-' . time(),
            'total_amount' => 99.99,
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'user_email' => $user->email,
            'items_count' => 2,
        ],
    ]);
    
    // Create or get test order
    $order = Order::create([
        'user_id' => $user->id,
        'total_amount' => 99.99,
        'status' => 'processing',
        'shipping_address' => [
            'firstName' => 'Test',
            'lastName' => 'User',
            'address' => '123 Test St',
            'city' => 'Test City',
            'postalCode' => '12345',
            'country' => 'Test Country'
        ],
        'payment_method' => 'test',
        'order_number' => 'TEST-' . time(),
    ]);
    
    echo "   ✅ Test notification created (ID: {$notification->id})\n";
    echo "   ✅ Test order created (ID: {$order->id})\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Failed to create test data: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test broadcasting
echo "4. Testing Broadcast:\n";
try {
    echo "   📡 Broadcasting OrderPlacedNotification event...\n";
    
    // Fire the event
    event(new OrderPlacedNotification($notification, $order));
    
    echo "   ✅ Event fired successfully!\n";
    echo "   📺 Check your WebSocket dashboard at: http://127.0.0.1:6001/laravel-websockets\n";
    echo "   🔍 Look for activity on the 'admin-notifications' channel\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Broadcasting failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "5. Next Steps:\n";
echo "   1. Make sure WebSocket server is running: php artisan websockets:serve\n";
echo "   2. Open WebSocket dashboard: http://127.0.0.1:6001/laravel-websockets\n";
echo "   3. Open your admin panel in browser\n";
echo "   4. You should see 1 connection in the dashboard\n";
echo "   5. Run this script again to send another test notification\n\n";

echo "🎉 Debug script completed successfully!\n";
