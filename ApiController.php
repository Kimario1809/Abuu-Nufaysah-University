<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Validator;
use App\Core\Session;
use App\Core\CSRF;
use App\Models\Notification;
use App\Models\Result;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Announcement;
use App\Models\Lecturer;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\User;

/**
 * Production-Grade API Controller
 * REST-style API endpoints with proper error handling and security
 */
class ApiController extends Controller {
    protected $auth;
    private $apiVersion = 'v1';
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        
        // Set JSON response headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Validate API request
     * 
     * @return bool
     */
    private function validateRequest() {
        // Check authentication for protected endpoints
        if (!$this->auth->isAuthenticated()) {
            $this->jsonError('Unauthorized', 401);
            return false;
        }
        
        // Validate CSRF for POST/PUT/DELETE requests
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
            if (!CSRF::validateToken($token)) {
                $this->jsonError('Invalid CSRF token', 403);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Return JSON success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    private function jsonSuccess($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
            'version' => $this->apiVersion
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Return JSON error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional errors
     * @return void
     */
    private function jsonError($message, $statusCode = 400, $errors = []) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => time(),
            'version' => $this->apiVersion
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ==================== AUTHENTICATION ENDPOINTS ====================
    
    /**
     * POST /api/login
     * User login endpoint
     */
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = new Validator();
        $rules = [
            'identifier' => 'required',
            'password' => 'required|min:8'
        ];
        
        if (!$validator->validate($data, $rules)) {
            $this->jsonError('Validation failed', 400, $validator->getErrors());
        }
        
        $result = $this->auth->login($data['identifier'], $data['password']);
        
        if ($result['success']) {
            $this->jsonSuccess([
                'user' => $result['user'],
                'role' => $result['role'],
                'csrf_token' => CSRF::getToken()
            ], 'Login successful');
        } else {
            $this->jsonError($result['message'], 401);
        }
    }
    
    /**
     * POST /api/logout
     * User logout endpoint
     */
    public function logout() {
        if (!$this->validateRequest()) return;
        
        $this->auth->logout();
        $this->jsonSuccess(null, 'Logout successful');
    }
    
    /**
     * GET /api/user
     * Get current user information
     */
    public function getUser() {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $this->jsonSuccess($user, 'User retrieved successfully');
    }
    
    // ==================== NOTIFICATION ENDPOINTS ====================
    
    /**
     * GET /api/notifications
     * Get user notifications (filtered by role)
     */
    public function getNotifications() {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $userRole = $this->auth->getCurrentRole();
        $limit = (int)($_GET['limit'] ?? 10);
        $since = $_GET['since'] ?? null;
        
        $notifications = Notification::getByUser($user['id'], $userRole, $limit, $since);
        
        $this->jsonSuccess([
            'notifications' => $notifications,
            'unread_count' => Notification::getUnreadCount($user['id'], $userRole)
        ], 'Notifications retrieved');
    }
    
    /**
     * PUT /api/notifications/{id}/read
     * Mark notification as read
     */
    public function markNotificationRead($id) {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $notification = Notification::find($id);
        
        if (!$notification || $notification['user_id'] != $user['id']) {
            $this->jsonError('Notification not found', 404);
        }
        
        Notification::markAsRead($id);
        $this->jsonSuccess(null, 'Notification marked as read');
    }
    
    /**
     * PUT /api/notifications/{id}/toggle
     * Toggle notification read/unread status
     */
    public function toggleNotificationRead($id) {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $notification = Notification::find($id);
        
        if (!$notification || ($notification['user_id'] != $user['id'] && $notification['target_role'] !== 'all')) {
            $this->jsonError('Notification not found', 404);
            return;
        }
        
        // Toggle the read status
        $newStatus = $notification['is_read'] == 1 ? 0 : 1;
        Notification::update($id, ['is_read' => $newStatus]);
        
        $this->jsonSuccess([
            'is_read' => $newStatus,
            'unread_count' => Notification::getUnreadCount($user['id'], $this->auth->getCurrentRole())
        ], $newStatus == 1 ? 'Notification marked as read' : 'Notification marked as unread');
    }
    
    /**
     * POST /api/test-notification
     * Test real-time notification delivery
     */
    public function testNotification() {
        if (!$this->validateRequest()) return;
        
        $this->requireRole('admin');
        
        $notificationId = \App\Helpers\NotificationHelper::notifyAdmin(
            'Test Notification',
            'This is a real-time notification test via WebSocket',
            'info',
            '/admin/dashboard'
        );
        
        $this->jsonSuccess([
            'notification_id' => $notificationId
        ], 'Test notification sent successfully');
    }
    
    // ==================== DASHBOARD ENDPOINTS ====================
    
    /**
     * GET /api/dashboard/stats
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        if (!$this->validateRequest()) return;
        
        $role = $this->auth->getCurrentRole();
        $stats = [];
        
        switch ($role) {
            case 'admin':
                $stats = [
                    'total_students' => Student::count(),
                    'total_courses' => Course::count(),
                    'total_lecturers' => Lecturer::count(),
                    'pending_payments' => Payment::getPendingCount(),
                    'revenue' => Payment::getTotalRevenue(),
                    'new_announcements' => Announcement::getRecentCount(),
                    'active_sessions' => Session::getActiveCount()
                ];
                break;
            case 'lecturer':
                $lecturer = Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
                $stats = [
                    'my_courses' => Course::countByLecturer($lecturer['id']),
                    'my_students' => Enrollment::countByLecturer($lecturer['id']),
                    'pending_marks' => Result::countPendingByLecturer($lecturer['id'])
                ];
                break;
            case 'student':
                $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
                $stats = [
                    'gpa' => Result::calculateGPA($student['id']),
                    'enrolled' => Enrollment::countByStudent($student['id']),
                    'pending_fees' => Payment::getPendingTotal($student['id']),
                    'attendance' => Attendance::getRateByStudent($student['id'])
                ];
                break;
        }
        
        $this->jsonSuccess($stats, 'Dashboard stats retrieved');
    }
    
    // ==================== PUSH NOTIFICATION ENDPOINTS ====================
    
    /**
     * POST /api/push/subscribe
     * Subscribe to push notifications
     */
    public function subscribePush() {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['endpoint']) || !isset($data['keys'])) {
            $this->jsonError('Invalid subscription data', 400);
            return;
        }
        
        $subscriptionData = [
            'user_id' => $user['id'],
            'endpoint' => $data['endpoint'],
            'p256dh_key' => $data['keys']['p256dh'],
            'auth_key' => $data['keys']['auth']
        ];
        
        $subscriptionId = \App\Models\PushSubscription::createOrUpdate($subscriptionData);
        
        $this->jsonSuccess([
            'subscription_id' => $subscriptionId
        ], 'Push subscription saved');
    }
    
    /**
     * POST /api/push/unsubscribe
     * Unsubscribe from push notifications
     */
    public function unsubscribePush() {
        if (!$this->validateRequest()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['endpoint'])) {
            $this->jsonError('Endpoint required', 400);
            return;
        }
        
        \App\Models\PushSubscription::deleteByEndpoint($data['endpoint']);
        
        $this->jsonSuccess(null, 'Push subscription removed');
    }
    
    /**
     * POST /api/push/verify
     * Verify push subscription
     */
    public function verifyPush() {
        if (!$this->validateRequest()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['endpoint'])) {
            $this->jsonError('Endpoint required', 400);
            return;
        }
        
        $subscription = \App\Models\PushSubscription::getByEndpoint($data['endpoint']);
        
        if ($subscription) {
            $this->jsonSuccess([
                'valid' => true,
                'subscription' => $subscription
            ], 'Subscription valid');
        } else {
            $this->jsonError('Subscription not found', 404);
        }
    }
    
    // ==================== COURSE ENDPOINTS ====================
    
    /**
     * GET /api/courses
     * Get courses (filtered by role)
     */
    public function getCourses() {
        if (!$this->validateRequest()) return;
        
        $role = $this->auth->getCurrentRole();
        $courses = [];
        
        switch ($role) {
            case 'admin':
                $courses = Course::all();
                break;
            case 'lecturer':
                $lecturer = Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
                $courses = Course::getByLecturer($lecturer['id']);
                break;
            case 'student':
                $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
                $courses = Course::getByStudent($student['id']);
                break;
        }
        
        $this->jsonSuccess($courses, 'Courses retrieved');
    }
    
    /**
     * GET /api/courses/{id}
     * Get single course details
     */
    public function getCourse($id) {
        if (!$this->validateRequest()) return;
        
        $course = Course::find($id);
        
        if (!$course) {
            $this->jsonError('Course not found', 404);
        }
        
        $this->jsonSuccess($course, 'Course retrieved');
    }
    
    // ==================== STUDENT ENDPOINTS ====================
    
    /**
     * GET /api/students
     * Get students (admin only)
     */
    public function getStudents() {
        if (!$this->validateRequest()) return;
        
        if ($this->auth->getCurrentRole() !== 'admin') {
            $this->jsonError('Access denied', 403);
        }
        
        $search = $_GET['search'] ?? null;
        $department = $_GET['department'] ?? null;
        
        $students = Student::search($search, $department);
        $this->jsonSuccess($students, 'Students retrieved');
    }
    
    /**
     * GET /api/students/{id}
     * Get single student details
     */
    public function getStudent($id) {
        if (!$this->validateRequest()) return;
        
        $role = $this->auth->getCurrentRole();
        $student = Student::find($id);
        
        if (!$student) {
            $this->jsonError('Student not found', 404);
        }
        
        // Students can only view their own data
        if ($role === 'student') {
            $currentUser = Student::getByUserId($this->auth->getCurrentUser()['id']);
            if ($currentUser['id'] != $id) {
                $this->jsonError('Access denied', 403);
            }
        }
        
        $this->jsonSuccess($student, 'Student retrieved');
    }
    
    // ==================== RESULTS ENDPOINTS ====================
    
    /**
     * GET /api/results
     * Get results
     */
    public function getResults() {
        if (!$this->validateRequest()) return;
        
        $user = $this->auth->getCurrentUser();
        $role = $user['role'];
        
        if ($role === 'student') {
            $student = Student::getByUserId($user['id']);
            $results = Result::getByStudent($student['id']);
        } elseif ($role === 'lecturer') {
            $lecturer = Lecturer::getByUserId($user['id']);
            $results = Result::getByLecturer($lecturer['id']);
        } else {
            $results = Result::all();
        }
        
        $this->jsonSuccess($results, 'Results retrieved');
    }
    
    /**
     * POST /api/results
     * Create/update result (lecturer/admin only)
     */
    public function postResult() {
        if (!$this->validateRequest()) return;
        
        $role = $this->auth->getCurrentRole();
        if (!in_array($role, ['admin', 'lecturer'])) {
            $this->jsonError('Access denied', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = new Validator();
        $rules = [
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'marks' => 'required|numeric|min:0|max:100',
            'semester_id' => 'required|exists:semesters,id'
        ];
        
        if (!$validator->validate($data, $rules)) {
            $this->jsonError('Validation failed', 400, $validator->getErrors());
        }
        
        $result = Result::create($data);
        
        if ($result) {
            $this->jsonSuccess($result, 'Result saved successfully', 201);
        } else {
            $this->jsonError('Failed to save result', 500);
        }
    }
    
    // ==================== PAYMENT ENDPOINTS ====================
    
    /**
     * POST /api/payments
     * Process payment
     */
    public function postPayment() {
        if (!$this->validateRequest()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = new Validator();
        $rules = [
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|in:USD,TSH',
            'payment_type' => 'required|in:tuition,registration,library,other',
            'student_id' => 'required|exists:students,id'
        ];
        
        if (!$validator->validate($data, $rules)) {
            $this->jsonError('Validation failed', 400, $validator->getErrors());
        }
        
        $payment = Payment::create([
            'student_id' => $data['student_id'],
            'receipt_no' => 'REC-' . date('Ymd') . '-' . rand(1000, 9999),
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'payment_type' => $data['payment_type'],
            'status' => 'paid',
            'payment_method' => $data['payment_method'] ?? 'online'
        ]);
        
        if ($payment) {
            Notification::create([
                'user_id' => $this->auth->getCurrentUser()['id'],
                'type' => 'payment',
                'title' => 'Payment Confirmed',
                'message' => 'Your payment of ' . Payment::formatAmount($data['amount'], $data['currency'] ?? 'USD') . ' has been confirmed',
                'link' => '/payments'
            ]);
            
            $this->jsonSuccess($payment, 'Payment recorded successfully', 201);
        } else {
            $this->jsonError('Payment processing failed', 500);
        }
    }
    
    /**
     * GET /api/payments
     * Get payments
     */
    public function getPayments() {
        if (!$this->validateRequest()) return;
        
        $role = $this->auth->getCurrentRole();
        
        if ($role === 'student') {
            $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
            $payments = Payment::getByStudent($student['id']);
        } else {
            $payments = Payment::all();
        }
        
        $this->jsonSuccess($payments, 'Payments retrieved');
    }
    
    // ==================== ANNOUNCEMENT ENDPOINTS ====================
    
    /**
     * GET /api/announcements
     * Get announcements
     */
    public function getAnnouncements() {
        if (!$this->validateRequest()) return;
        
        $limit = (int)($_GET['limit'] ?? 10);
        $announcements = Announcement::getRecent($limit);
        
        $this->jsonSuccess($announcements, 'Announcements retrieved');
    }
    
    /**
     * POST /api/announcements
     * Create announcement (admin only)
     */
    public function postAnnouncement() {
        if (!$this->validateRequest()) return;
        
        if ($this->auth->getCurrentRole() !== 'admin') {
            $this->jsonError('Access denied', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = new Validator();
        $rules = [
            'title' => 'required|max:255',
            'content' => 'required',
            'target_audience' => 'required|in:all,students,lecturers,admin'
        ];
        
        if (!$validator->validate($data, $rules)) {
            $this->jsonError('Validation failed', 400, $validator->getErrors());
        }
        
        $data['created_by'] = $this->auth->getCurrentUser()['id'];
        $announcement = Announcement::create($data);
        
        if ($announcement) {
            $this->jsonSuccess($announcement, 'Announcement created successfully', 201);
        } else {
            $this->jsonError('Failed to create announcement', 500);
        }
    }
}