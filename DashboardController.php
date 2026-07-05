<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Student;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Announcement;
use App\Models\Result;
use App\Models\Notification;
use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\Timetable;

class DashboardController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
    }
    
    public function admin() {
        $this->requireRole('admin');

        $stats = [
            'total_students' => Student::count(),
            'total_courses' => Course::count(),
            'pending_fees' => Payment::getPendingTotal(),
            'new_announcements' => Announcement::getRecent(7),
            'recent_enrollments' => Student::getRecentEnrollments(10),
            'revenue' => Payment::getTotalRevenue()
        ];
        
        $data = [
            'stats' => $stats,
            'announcements' => Announcement::getLatest(5)
        ];
        
        $this->view('dashboard/admin', $data);
    }
    
    public function lecturer() {
        $this->requireRole('lecturer');

        $lecturerId = $this->auth->getCurrentUser()['id'];
        
        $stats = [
            'my_courses' => Course::getByLecturer($lecturerId),
            'total_students' => Course::getTotalStudentsByLecturer($lecturerId),
            'pending_marks' => Result::getPendingByLecturer($lecturerId),
            'announcements' => Announcement::getLatest(5)
        ];
        
        $data = [
            'stats' => $stats,
            'assigned_courses' => Course::getByLecturer($lecturerId, true)
        ];
        
        $this->view('dashboard/lecturer', $data);
    }
    
    public function student() {
        $this->requireRole('student');

        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        
        $stats = [
            'enrolled_courses' => Enrollment::getByStudent($student['id']),
            'current_gpa' => Result::calculateGPA($student['id']),
            'pending_fees' => Payment::getPendingByStudent($student['id']),
            'attendance_rate' => Attendance::getRateByStudent($student['id'])
        ];
        
        $data = [
            'stats' => $stats,
            'student' => $student,
            'announcements' => Announcement::getLatest(5),
            'timetable' => Timetable::getByStudent($student['id'])
        ];
        
        $this->view('dashboard/student', $data);
    }
}