<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Result;
use App\Models\Payment;
use App\Models\Timetable;
use App\Models\Attendance;

class StudentController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
        $this->requireRole('student');
    }
    
    public function profile() {
        $user = $this->auth->getCurrentUser();
        $student = Student::getByUserId($user['id']);
        
        $data = [
            'user' => $user,
            'student' => $student,
            'courses' => Enrollment::getByStudent($student['id']),
            'results' => Result::getByStudent($student['id']),
            'payments' => Payment::getByStudent($student['id'])
        ];
        
        $this->view('students/profile', $data);
    }
    
    public function updateProfile() {
        $user = $this->auth->getCurrentUser();
        $student = Student::getByUserId($user['id']);
        
        $data = [
            'full_name' => $_POST['full_name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        // Update user
        $this->db->update('users', $user['id'], [
            'full_name' => $data['full_name'],
            'phone' => $data['phone']
        ]);
        
        // Update student
        $this->db->update('students', $student['id'], [
            'address' => $data['address']
        ]);
        
        $this->json(['success' => true, 'message' => 'Profile updated successfully']);
    }
    
    public function enrollCourse() {
        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        $courseId = $_POST['course_id'] ?? null;
        
        if (!$courseId) {
            $this->json(['error' => 'Course ID required'], 400);
            return;
        }
        
        // Check if already enrolled
        if (Enrollment::exists($student['id'], $courseId)) {
            $this->json(['error' => 'Already enrolled'], 409);
            return;
        }
        
        // Check capacity
        if (!Course::hasCapacity($courseId)) {
            $this->json(['error' => 'Course is full'], 409);
            return;
        }
        
        $enrollment = Enrollment::create([
            'student_id' => $student['id'],
            'course_id' => $courseId,
            'status' => 'enrolled'
        ]);
        
        if ($enrollment) {
            $this->json(['success' => true, 'message' => 'Successfully enrolled']);
        } else {
            $this->json(['error' => 'Enrollment failed'], 500);
        }
    }
    
    public function viewTimetable() {
        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        $timetable = Timetable::getByStudent($student['id']);
        
        $this->json(['timetable' => $timetable]);
    }
    
    public function viewResults() {
        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        $results = Result::getByStudent($student['id']);
        
        $this->json(['results' => $results]);
    }
    
    public function viewPayments() {
        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        $payments = Payment::getByStudent($student['id']);
        
        $this->json(['payments' => $payments]);
    }
}