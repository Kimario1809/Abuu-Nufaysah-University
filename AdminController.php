<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;

class AdminController extends Controller {
    
    public function profile() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        $user = $this->currentUser();
        
        $this->view('admin/profile', [
            'title' => 'Admin Profile',
            'user' => $user
        ]);
    }
    
    public function updateProfile() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Handle profile update logic
        $data = $this->post();
        
        // Validate and update profile
        // Add your logic here
        
        $this->setFlash('success', 'Profile updated successfully');
        $this->redirect('/admin/profile');
    }
    
    /**
     * View activity logs
     */
    public function activityLogs() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        $this->view('admin/activity-logs', [
            'title' => 'Activity Logs'
        ]);
    }
    
    /**
     * Export activity logs to CSV
     */
    public function exportActivityLogs() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Get filters from query parameters
        $filters = $_GET ?? [];
        $filterArray = [];
        
        if (!empty($filters['user_id'])) $filterArray['user_id'] = $filters['user_id'];
        if (!empty($filters['action'])) $filterArray['action'] = $filters['action'];
        if (!empty($filters['entity_type'])) $filterArray['entity_type'] = $filters['entity_type'];
        if (!empty($filters['date_from'])) $filterArray['date_from'] = $filters['date_from'];
        if (!empty($filters['date_to'])) $filterArray['date_to'] = $filters['date_to'];
        
        // Get all logs matching filters
        $logs = ActivityLog::getAll($filterArray, 10000);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['ID', 'User', 'Email', 'Role', 'Action', 'Entity Type', 'Entity ID', 'Description', 'IP Address', 'User Agent', 'Created At']);
        
        // Add log data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['full_name'] ?? 'System',
                $log['email'] ?? '',
                $log['role'] ?? '',
                $log['action'],
                $log['entity_type'] ?? '',
                $log['entity_id'] ?? '',
                $log['description'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? '',
                $log['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Clear old activity logs
     */
    public function clearOldActivityLogs() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        $days = (int)($_POST['days'] ?? 90);
        $deletedCount = ActivityLog::deleteOld($days);
        
        $this->jsonSuccess([
            'deleted_count' => $deletedCount
        ], "Deleted {$deletedCount} old logs");
    }
}
