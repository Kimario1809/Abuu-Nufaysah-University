<?php
/**
 * Test Real-Time Notification
 * Run this file to test WebSocket notification delivery
 */

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Env.php';
require_once __DIR__ . '/app/models/Notification.php';
require_once __DIR__ . '/app/helpers/NotificationHelper.php';

use App\Helpers\NotificationHelper;

// Load environment variables
Env::load(__DIR__ . '/.env');

echo "Testing Real-Time Notification...\n";
echo "================================\n\n";

// Test notification
echo "Sending test notification to admin...\n";
$notificationId = NotificationHelper::notifyAdmin(
    'Test Notification',
    'This is a real-time notification test via WebSocket',
    'info',
    '/admin/dashboard'
);

echo "Notification ID: {$notificationId}\n";
echo "Notification sent successfully!\n\n";

echo "Check your browser for:\n";
echo "1. Toast notification appearing\n";
echo "2. Notification badge updating\n";
echo "3. Notification dropdown showing new notification\n\n";

echo "WebSocket server should be running on port 3001\n";
echo "Connection status indicator should be green (connected)\n";
