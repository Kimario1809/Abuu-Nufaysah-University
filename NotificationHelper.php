<?php
namespace App\Helpers;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Core\Auth;
use App\Core\Env;
use App\Core\Curl;

/**
 * Notification Helper Class
 * Provides convenient methods for creating role-based notifications
 */
class NotificationHelper {
    
    /**
     * Create notification for admin users
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return int Notification ID
     */
    public static function notifyAdmin($title, $message, $type = 'info', $link = null) {
        $notificationId = Notification::createForRole('admin', [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notification to admin subscribers
        self::sendPushNotification('admin', $title, $message, $type, $link);
        
        // Broadcast via WebSocket
        $notification = Notification::find($notificationId);
        if ($notification) {
            self::broadcastWebSocket($notification, 'role', 'admin');
        }
        
        return $notificationId;
    }
    
    /**
     * Create notification for lecturers
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return int Notification ID
     */
    public static function notifyLecturers($title, $message, $type = 'info', $link = null) {
        $notificationId = Notification::createForRole('lecturer', [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notification to lecturer subscribers
        self::sendPushNotification('lecturer', $title, $message, $type, $link);
        
        // Broadcast via WebSocket
        $notification = Notification::find($notificationId);
        if ($notification) {
            self::broadcastWebSocket($notification, 'role', 'lecturer');
        }
        
        return $notificationId;
    }
    
    /**
     * Create notification for students
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return int Notification ID
     */
    public static function notifyStudents($title, $message, $type = 'info', $link = null) {
        $notificationId = Notification::createForRole('student', [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notification to student subscribers
        self::sendPushNotification('student', $title, $message, $type, $link);
        
        // Broadcast via WebSocket
        $notification = Notification::find($notificationId);
        if ($notification) {
            self::broadcastWebSocket($notification, 'role', 'student');
        }
        
        return $notificationId;
    }
    
    /**
     * Create notification for all users
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return int Notification ID
     */
    public static function notifyAll($title, $message, $type = 'announcement', $link = null) {
        $notificationId = Notification::create([
            'user_id' => null,
            'target_role' => 'all',
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notification to all subscribers
        self::sendPushNotification('all', $title, $message, $type, $link);
        
        // Broadcast via WebSocket
        $notification = Notification::find($notificationId);
        if ($notification) {
            self::broadcastWebSocket($notification, 'all');
        }
        
        return $notificationId;
    }
    
    /**
     * Create notification for specific user
     * 
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return int Notification ID
     */
    public static function notifyUser($userId, $title, $message, $type = 'info', $link = null) {
        $notificationId = Notification::create([
            'user_id' => $userId,
            'target_role' => 'all',
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notification to specific user
        self::sendPushToUser($userId, $title, $message, $type, $link);
        
        // Broadcast via WebSocket
        $notification = Notification::find($notificationId);
        if ($notification) {
            self::broadcastWebSocket($notification, 'user', $userId);
        }
        
        return $notificationId;
    }
    
    /**
     * Create notification for multiple users
     * 
     * @param array $userIds Array of user IDs
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     * @return array Array of notification IDs
     */
    public static function notifyUsers($userIds, $title, $message, $type = 'info', $link = null) {
        $ids = Notification::createForUsers($userIds, [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
        
        // Send push notifications to all users
        foreach ($userIds as $userId) {
            self::sendPushToUser($userId, $title, $message, $type, $link);
        }
        
        return $ids;
    }
    
    /**
     * Send push notification to role subscribers
     * 
     * @param string $role Target role
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     */
    private static function sendPushNotification($role, $title, $message, $type, $link) {
        try {
            $subscriptions = PushSubscription::getByRole($role);
            
            foreach ($subscriptions as $subscription) {
                self::sendWebPush($subscription, $title, $message, $type, $link);
            }
        } catch (\Exception $e) {
            error_log("Push notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Send push notification to specific user
     * 
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     */
    private static function sendPushToUser($userId, $title, $message, $type, $link) {
        try {
            $subscription = PushSubscription::getByUserId($userId);
            
            if ($subscription) {
                self::sendWebPush($subscription, $title, $message, $type, $link);
            }
        } catch (\Exception $e) {
            error_log("Push notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Send web push notification
     * 
     * @param array $subscription Push subscription data
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $link Optional link
     */
    private static function sendWebPush($subscription, $title, $message, $type, $link) {
        try {
            // Check if web-push-php library is available
            if (!class_exists('Minishlink\WebPush\WebPush')) {
                // Log that library is not installed
                error_log("Web Push: web-push-php library not installed. Install with: composer require minishlink/web-push");
                error_log("Web Push: {$title} - {$message} to endpoint: {$subscription['endpoint']}");
                return false;
            }

            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject' => Env::get('VAPID_SUBJECT', 'mailto:admin@abuu.edu'),
                    'publicKey' => Env::get('VAPID_PUBLIC_KEY'),
                    'privateKey' => Env::get('VAPID_PRIVATE_KEY')
                ]
            ]);

            $pushSubscription = new \Minishlink\WebPush\Subscription(
                $subscription['endpoint'],
                $subscription['p256dh_key'],
                $subscription['auth_key']
            );

            $payload = json_encode([
                'title' => $title,
                'body' => $message,
                'icon' => '/assets/images/logo-icon.png',
                'badge' => '/assets/images/badge-icon.png',
                'data' => ['url' => $link ?: '/notifications'],
                'tag' => 'abuu-notification-' . time(),
                'requireInteraction' => true,
                'renotify' => true
            ]);

            $report = $webPush->sendOne($pushSubscription, $payload);

            if ($report->isSuccess()) {
                error_log("Web Push sent successfully: {$title}");
                return true;
            } else {
                error_log("Web Push failed: " . $report->getReason());
                
                // If subscription is invalid, remove it
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::deactivate($subscription['id']);
                }
                
                return false;
            }
        } catch (\Exception $e) {
            error_log("Push notification error: " . $e->getMessage());
            
            // Fallback: log the notification for manual implementation
            error_log("Web Push (fallback): {$title} - {$message} to endpoint: {$subscription['endpoint']}");
            return false;
        }
    }
    
    /**
     * Broadcast notification via WebSocket
     * 
     * @param array $notification Notification data
     * @param string $target Target (all, role, user)
     * @param string|null $targetValue Target value (role name or user ID)
     */
    private static function broadcastWebSocket($notification, $target = 'all', $targetValue = null) {
        try {
            $wsServer = Env::get('WEBSOCKET_SERVER', 'localhost');
            $wsPort = Env::get('WEBSOCKET_PORT', '3001');
            $wsUseSSL = Env::get('WEBSOCKET_HTTPS', 'false');
            
            $protocol = $wsUseSSL === 'true' ? 'https' : 'http';
            $url = "{$protocol}://{$wsServer}:{$wsPort}/broadcast";
            
            $payload = [
                'notification' => [
                    'id' => $notification['id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'link' => $notification['link'],
                    'is_read' => $notification['is_read'],
                    'created_at' => $notification['created_at']
                ],
                'target' => $target
            ];
            
            if ($target === 'role' && $targetValue) {
                $payload['notification']['target_role'] = $targetValue;
            } elseif ($target === 'user' && $targetValue) {
                $payload['notification']['user_id'] = $targetValue;
            }
            
            // Use cURL to send broadcast
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2 second timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                error_log("WebSocket broadcast successful: {$notification['title']}");
                return true;
            } else {
                error_log("WebSocket broadcast failed: HTTP {$httpCode}");
                return false;
            }
        } catch (\Exception $e) {
            error_log("WebSocket broadcast error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== PRESET NOTIFICATIONS ====================
    
    /**
     * Payment received notification (for admin)
     */
    public static function paymentReceived($amount, $studentName, $paymentId) {
        return self::notifyAdmin(
            'Payment Received',
            "Payment of {$amount} received from {$studentName}",
            'payment',
            "/payments/view/{$paymentId}"
        );
    }
    
    /**
     * Payment confirmed notification (for student)
     */
    public static function paymentConfirmed($amount, $paymentId) {
        return self::notifyUser(
            Auth::getInstance()->getCurrentUser()['id'],
            'Payment Confirmed',
            "Your payment of {$amount} has been confirmed",
            'success',
            "/payments/view/{$paymentId}"
        );
    }
    
    /**
     * New assignment notification (for students)
     */
    public static function newAssignment($courseName, $assignmentTitle, $assignmentId) {
        return self::notifyStudents(
            'New Assignment',
            "New assignment '{$assignmentTitle}' posted for {$courseName}",
            'assignment',
            "/assignments/view/{$assignmentId}"
        );
    }
    
    /**
     * Assignment due reminder (for students)
     */
    public static function assignmentDueSoon($assignmentTitle, $dueDate, $assignmentId) {
        return self::notifyStudents(
            'Assignment Due Soon',
            "Assignment '{$assignmentTitle}' is due on {$dueDate}",
            'warning',
            "/assignments/view/{$assignmentId}"
        );
    }
    
    /**
     * Grade posted notification (for students)
     */
    public static function gradePosted($courseName, $grade, $gradeId) {
        return self::notifyUser(
            Auth::getInstance()->getCurrentUser()['id'],
            'Grade Posted',
            "Your grade for {$courseName} has been posted: {$grade}",
            'grade',
            "/grades/view/{$gradeId}"
        );
    }
    
    /**
     * New announcement notification (for all)
     */
    public static function newAnnouncement($title, $announcementId) {
        return self::notifyAll(
            'New Announcement',
            $title,
            'announcement',
            "/announcements/view/{$announcementId}"
        );
    }
    
    /**
     * System maintenance notification (for all)
     */
    public static function systemMaintenance($startTime, $duration) {
        return self::notifyAll(
            'Scheduled Maintenance',
            "System maintenance scheduled for {$startTime} (duration: {$duration})",
            'system',
            null
        );
    }
    
    /**
     * New student enrollment notification (for admin)
     */
    public static function newStudentEnrollment($studentName, $courseName) {
        return self::notifyAdmin(
            'New Enrollment',
            "Student {$studentName} enrolled in {$courseName}",
            'info',
            "/enrollments"
        );
    }
    
    /**
     * Assignment submission notification (for lecturer)
     */
    public static function assignmentSubmitted($studentName, $assignmentTitle, $submissionId) {
        return self::notifyLecturers(
            'Assignment Submitted',
            "{$studentName} submitted '{$assignmentTitle}'",
            'assignment',
            "/submissions/view/{$submissionId}"
        );
    }
    
    /**
     * Low attendance alert (for lecturer)
     */
    public static function lowAttendanceAlert($courseName, $attendanceRate) {
        return self::notifyLecturers(
            'Low Attendance Alert',
            "Low attendance in {$courseName}: {$attendanceRate}% attendance rate",
            'warning',
            "/attendance/view/{$courseName}"
        );
    }
    
    /**
     * Chat message notification
     */
    public static function newChatMessage($senderName, $message, $chatId) {
        return self::notifyUser(
            Auth::getInstance()->getCurrentUser()['id'],
            'New Message',
            "{$senderName}: {$message}",
            'chat',
            "/chat/view/{$chatId}"
        );
    }
    
    /**
     * Account security alert (for user)
     */
    public static function securityAlert($message, $userId = null) {
        if ($userId) {
            return self::notifyUser(
                $userId,
                'Security Alert',
                $message,
                'error',
                "/security"
            );
        }
        return self::notifyUser(
            Auth::getInstance()->getCurrentUser()['id'],
            'Security Alert',
            $message,
            'error',
            "/security"
        );
    }
    
    /**
     * Course registration opened (for students)
     */
    public static function courseRegistrationOpened($semesterName) {
        return self::notifyStudents(
            'Course Registration Opened',
            "Course registration for {$semesterName} is now open",
            'info',
            "/courses/register"
        );
    }
    
    /**
     * Exam schedule published (for students)
     */
    public static function examSchedulePublished($semesterName) {
        return self::notifyStudents(
            'Exam Schedule Published',
            "Exam schedule for {$semesterName} has been published",
            'info',
            "/exams/schedule"
        );
    }
    
    /**
     * Result published (for students)
     */
    public static function resultPublished($semesterName) {
        return self::notifyStudents(
            'Results Published',
            "Results for {$semesterName} have been published",
            'success',
            "/results/view"
        );
    }
}
