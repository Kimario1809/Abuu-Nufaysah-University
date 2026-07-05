<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;

class CourseController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
        $this->requireRole('admin');
    }
    
    public function index() {
        $data = [
            'courses' => Course::getAll(),
            'departments' => Department::getAll()
        ];
        $this->view('courses/index', $data);
    }
    
    public function create() {
        $data = [
            'departments' => Department::getAll(),
            'lecturers' => Lecturer::getAll()
        ];
        $this->view('courses/create', $data);
    }
    
    public function store() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'department_id' => $_POST['department_id'] ?? null,
            'credit_hours' => $_POST['credit_hours'] ?? 0,
            'lecturer_id' => $_POST['lecturer_id'] ?? null,
            'description' => $_POST['description'] ?? '',
            'semester' => $_POST['semester'] ?? 1,
            'year' => $_POST['year'] ?? 1,
            'capacity' => $_POST['capacity'] ?? 50
        ];
        
        $id = Course::create($data);
        
        if ($id) {
            $this->json(['success' => true, 'message' => 'Course created successfully', 'id' => $id]);
        } else {
            $this->json(['error' => 'Failed to create course'], 500);
        }
    }
    
    public function edit($id) {
        $course = Course::find($id);
        if (!$course) {
            $this->redirect('/courses');
            return;
        }
        
        $data = [
            'course' => $course,
            'departments' => Department::getAll(),
            'lecturers' => Lecturer::getAll()
        ];
        $this->view('courses/edit', $data);
    }
    
    public function update($id) {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'department_id' => $_POST['department_id'] ?? null,
            'credit_hours' => $_POST['credit_hours'] ?? 0,
            'lecturer_id' => $_POST['lecturer_id'] ?? null,
            'description' => $_POST['description'] ?? '',
            'semester' => $_POST['semester'] ?? 1,
            'year' => $_POST['year'] ?? 1,
            'capacity' => $_POST['capacity'] ?? 50
        ];
        
        Course::update($id, $data);
        $this->json(['success' => true, 'message' => 'Course updated successfully']);
    }
    
    public function delete($id) {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        Course::delete($id);
        $this->json(['success' => true, 'message' => 'Course deleted successfully']);
    }
    
    public function toggleStatus($id) {
        $course = Course::find($id);
        if (!$course) {
            $this->json(['error' => 'Course not found'], 404);
            return;
        }
        
        $newStatus = $course['is_active'] ? 0 : 1;
        Course::update($id, ['is_active' => $newStatus]);
        
        $this->json([
            'success' => true, 
            'message' => 'Course status updated',
            'status' => $newStatus
        ]);
    }
}