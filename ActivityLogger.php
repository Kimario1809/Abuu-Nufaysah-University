<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use App\Core\Auth;

/**
 * Activity Logger Helper Class
 * Provides convenient methods for logging user activities
 */
class ActivityLogger {
    
    /**
     * Log user login
     * 
     * @param int $userId User ID
     * @return int Log ID
     */
    public static function login($userId) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'login',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'description' => 'User logged in'
        ]);
    }
    
    /**
     * Log user logout
     * 
     * @param int $userId User ID
     * @return int Log ID
     */
    public static function logout($userId) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'logout',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'description' => 'User logged out'
        ]);
    }
    
    /**
     * Log failed login attempt
     * 
     * @param string $identifier Email or phone
     * @param string $reason Failure reason
     * @return int Log ID
     */
    public static function failedLogin($identifier, $reason = 'Invalid credentials') {
        return ActivityLog::log([
            'user_id' => null,
            'action' => 'failed_login',
            'entity_type' => 'user',
            'entity_id' => null,
            'description' => "Failed login attempt for {$identifier}: {$reason}",
            'metadata' => [
                'identifier' => $identifier,
                'reason' => $reason
            ]
        ]);
    }
    
    /**
     * Log password change
     * 
     * @param int $userId User ID
     * @return int Log ID
     */
    public static function passwordChanged($userId) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'password_changed',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'description' => 'User changed password'
        ]);
    }
    
    /**
     * Log password reset request
     * 
     * @param int $userId User ID
     * @return int Log ID
     */
    public static function passwordResetRequested($userId) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'password_reset_requested',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'description' => 'User requested password reset'
        ]);
    }
    
    /**
     * Log profile update
     * 
     * @param int $userId User ID
     * @param array $changes Changed fields
     * @return int Log ID
     */
    public static function profileUpdated($userId, $changes = []) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'profile_updated',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'description' => 'User updated profile',
            'metadata' => ['changes' => $changes]
        ]);
    }
    
    /**
     * Log CRUD create operation
     * 
     * @param string $entityType Entity type (user, course, etc.)
     * @param int $entityId Entity ID
     * @param string $description Description
     * @return int Log ID
     */
    public static function created($entityType, $entityId, $description = null) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'created',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?: "Created {$entityType} #{$entityId}"
        ]);
    }
    
    /**
     * Log CRUD update operation
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string $description Description
     * @param array $changes Changed fields
     * @return int Log ID
     */
    public static function updated($entityType, $entityId, $description = null, $changes = []) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'updated',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?: "Updated {$entityType} #{$entityId}",
            'metadata' => ['changes' => $changes]
        ]);
    }
    
    /**
     * Log CRUD delete operation
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string $description Description
     * @return int Log ID
     */
    public static function deleted($entityType, $entityId, $description = null) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'deleted',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?: "Deleted {$entityType} #{$entityId}"
        ]);
    }
    
    /**
     * Log view/access operation
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string $description Description
     * @return int Log ID
     */
    public static function viewed($entityType, $entityId, $description = null) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'viewed',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?: "Viewed {$entityType} #{$entityId}"
        ]);
    }
    
    /**
     * Log download operation
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string $fileName File name
     * @return int Log ID
     */
    public static function downloaded($entityType, $entityId, $fileName) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'downloaded',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => "Downloaded {$fileName}",
            'metadata' => ['file_name' => $fileName]
        ]);
    }
    
    /**
     * Log export operation
     * 
     * @param string $entityType Entity type
     * @param string $format Export format
     * @param int $recordCount Number of records
     * @return int Log ID
     */
    public static function exported($entityType, $format, $recordCount) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'exported',
            'entity_type' => $entityType,
            'entity_id' => null,
            'description' => "Exported {$recordCount} {$entityType} records as {$format}",
            'metadata' => [
                'format' => $format,
                'record_count' => $recordCount
            ]
        ]);
    }
    
    /**
     * Log import operation
     * 
     * @param string $entityType Entity type
     * @param int $successCount Number of successful imports
     * @param int $failureCount Number of failed imports
     * @return int Log ID
     */
    public static function imported($entityType, $successCount, $failureCount) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'imported',
            'entity_type' => $entityType,
            'entity_id' => null,
            'description' => "Imported {$successCount} {$entityType} records ({$failureCount} failed)",
            'metadata' => [
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]
        ]);
    }
    
    /**
     * Log payment operation
     * 
     * @param int $userId User ID
     * @param int $paymentId Payment ID
     * @param string $action Action (created, confirmed, failed)
     * @param float $amount Amount
     * @return int Log ID
     */
    public static function payment($userId, $paymentId, $action, $amount) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => "payment_{$action}",
            'entity_type' => 'payment',
            'entity_id' => $paymentId,
            'description' => "Payment {$action}: {$amount}",
            'metadata' => ['amount' => $amount]
        ]);
    }
    
    /**
     * Log enrollment operation
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param string $action Action (enrolled, dropped)
     * @return int Log ID
     */
    public static function enrollment($userId, $courseId, $action) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => "enrollment_{$action}",
            'entity_type' => 'enrollment',
            'entity_id' => null,
            'description' => "Student {$action} in course #{$courseId}",
            'metadata' => ['course_id' => $courseId]
        ]);
    }
    
    /**
     * Log grade operation
     * 
     * @param int $studentId Student ID
     * @param int $gradeId Grade ID
     * @param string $action Action (posted, updated)
     * @return int Log ID
     */
    public static function grade($studentId, $gradeId, $action) {
        return ActivityLog::log([
            'user_id' => $studentId,
            'action' => "grade_{$action}",
            'entity_type' => 'grade',
            'entity_id' => $gradeId,
            'description' => "Grade {$action} for student #{$studentId}"
        ]);
    }
    
    /**
     * Log assignment operation
     * 
     * @param int $userId User ID
     * @param int $assignmentId Assignment ID
     * @param string $action Action (created, submitted, graded)
     * @return int Log ID
     */
    public static function assignment($userId, $assignmentId, $action) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => "assignment_{$action}",
            'entity_type' => 'assignment',
            'entity_id' => $assignmentId,
            'description' => "Assignment {$action} #{$assignmentId}"
        ]);
    }
    
    /**
     * Log announcement operation
     * 
     * @param int $userId User ID
     * @param int $announcementId Announcement ID
     * @param string $action Action (created, updated, deleted)
     * @return int Log ID
     */
    public static function announcement($userId, $announcementId, $action) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => "announcement_{$action}",
            'entity_type' => 'announcement',
            'entity_id' => $announcementId,
            'description' => "Announcement {$action} #{$announcementId}"
        ]);
    }
    
    /**
     * Log system event
     * 
     * @param string $action Action
     * @param string $description Description
     * @param array $metadata Additional metadata
     * @return int Log ID
     */
    public static function system($action, $description, $metadata = []) {
        return ActivityLog::log([
            'user_id' => null,
            'action' => "system_{$action}",
            'entity_type' => 'system',
            'entity_id' => null,
            'description' => $description,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Log security event
     * 
     * @param int|null $userId User ID (null if unknown)
     * @param string $event Security event
     * @param string $description Description
     * @return int Log ID
     */
    public static function security($userId, $event, $description) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => "security_{$event}",
            'entity_type' => 'security',
            'entity_id' => null,
            'description' => $description,
            'metadata' => ['security_event' => $event]
        ]);
    }
    
    /**
     * Log API request
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param int|null $userId User ID
     * @param int $statusCode Response status code
     * @return int Log ID
     */
    public static function apiRequest($endpoint, $method, $userId = null, $statusCode = 200) {
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => 'api_request',
            'entity_type' => 'api',
            'entity_id' => null,
            'description' => "API {$method} request to {$endpoint}",
            'metadata' => [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode
            ]
        ]);
    }
    
    /**
     * Log custom activity
     * 
     * @param string $action Action
     * @param string|null $entityType Entity type
     * @param int|null $entityId Entity ID
     * @param string|null $description Description
     * @param array|null $metadata Additional metadata
     * @return int Log ID
     */
    public static function custom($action, $entityType = null, $entityId = null, $description = null, $metadata = null) {
        $userId = Auth::getInstance()->getCurrentUser()['id'] ?? null;
        
        return ActivityLog::log([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata' => $metadata
        ]);
    }
}
