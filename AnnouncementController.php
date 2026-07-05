<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Course;

class AnnouncementController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
    }
    
    public function index() {
        $data = [
            'announcements' => Announcement::getLatest(50),
            'departments' => Department::getAll(),
            'courses' => Course::getAll()
        ];
        $this->view('announcements/index', $data);
    }
    
    public function create() {
        $this->requireRole(['admin', 'lecturer']);
        $data = [
            'departments' => Department::getAll(),
            'courses' => Course::getAll()
        ];
        $this->view('announcements/create', $data);
    }
    
    public function store() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $this->requireRole(['admin', 'lecturer']);
        
        $user = $this->auth->getCurrentUser();
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'author_id' => $user['id'],
            'author_role' => $user['role'],
            'priority' => $_POST['priority'] ?? 'medium',
            'target_audience' => $_POST['target_audience'] ?? 'all',
            'department_id' => $_POST['department_id'] ?? null,
            'course_id' => $_POST['course_id'] ?? null,
            'expires_at' => $_POST['expires_at'] ?? null
        ];
        
        $id = Announcement::createAnnouncement($data);
        
        if ($id) {
            // Notify target users
            $this->notifyTargetAudience($data);
            
            $this->json(['success' => true, 'message' => 'Announcement created successfully']);
        } else {
            $this->json(['error' => 'Failed to create announcement'], 500);
        }
    }
    
    public function edit($id) {
        $this->requireRole(['admin', 'lecturer']);
        
        $announcement = Announcement::find($id);
        if (!$announcement) {
            $this->redirect('/announcements');
            return;
        }
        
        $data = [
            'announcement' => $announcement,
            'departments' => Department::getAll(),
            'courses' => Course::getAll()
        ];
        $this->view('announcements/edit', $data);
    }
    
    public function update($id) {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $this->requireRole(['admin', 'lecturer']);
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'priority' => $_POST['priority'] ?? 'medium',
            'target_audience' => $_POST['target_audience'] ?? 'all',
            'department_id' => $_POST['department_id'] ?? null,
            'course_id' => $_POST['course_id'] ?? null,
            'expires_at' => $_POST['expires_at'] ?? null
        ];
        
        Announcement::update($id, $data);
        $this->json(['success' => true, 'message' => 'Announcement updated successfully']);
    }
    
    public function delete($id) {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $this->requireRole(['admin', 'lecturer']);
        
        Announcement::delete($id);
        $this->json(['success' => true, 'message' => 'Announcement deleted successfully']);
    }
    
    public function publish($id) {
        $this->requireRole(['admin', 'lecturer']);
        Announcement::publish($id);
        $this->json(['success' => true, 'message' => 'Announcement published']);
    }
    
    public function unpublish($id) {
        $this->requireRole(['admin', 'lecturer']);
        Announcement::unpublish($id);
        $this->json(['success' => true, 'message' => 'Announcement unpublished']);
    }
    
    private function notifyTargetAudience($data) {
        // Implementation for sending notifications to target audience
        // This could be email, SMS, or in-app notifications
        // For now, we'll just log it
        error_log("Announcement created: " . $data['title']);
    }
}