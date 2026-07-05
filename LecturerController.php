<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Result;
use App\Models\Attendance;
use App\Models\Timetable;
use App\Models\Lecturer;

class LecturerController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
        $this->requireRole('lecturer');
    }
    
    public function dashboard() {
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $data = [
            'courses' => Course::getByLecturer($lecturer['id']),
            'total_students' => Enrollment::countByLecturer($lecturer['id']),
            'pending_marks' => Result::getPendingByLecturer($lecturer['id']),
            'timetable' => Timetable::getByLecturer($lecturer['id'])
        ];
        $this->view('dashboard/lecturer', $data);
    }
    
    public function uploadResults() {
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $data = [
            'courses' => Course::getByLecturer($lecturer['id'])
        ];
        $this->view('results/upload', $data);
    }
    
    public function postUploadResults() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $courseId = $_POST['course_id'] ?? null;
        $results = $_POST['results'] ?? [];
        
        if (!$courseId || empty($results)) {
            $this->json(['error' => 'Invalid data'], 400);
            return;
        }
        
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $course = Course::find($courseId);
        
        if (!$course || $course['lecturer_id'] != $lecturer['id']) {
            $this->json(['error' => 'You are not authorized to upload results for this course'], 403);
            return;
        }
        
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        
        try {
            foreach ($results as $data) {
                $enrollmentId = $data['enrollment_id'] ?? null;
                $score = $data['score'] ?? null;
                
                if (!$enrollmentId || !is_numeric($score)) continue;
                
                $gradeInfo = Result::getGrade($score);
                
                // Update or create result
                $existing = $db->query(
                    "SELECT id FROM results WHERE enrollment_id = ?",
                    [$enrollmentId]
                )->fetch();
                
                if ($existing) {
                    $db->query(
                        "UPDATE results SET score = ?, grade = ?, grade_point = ?, updated_at = NOW() WHERE id = ?",
                        [$score, $gradeInfo['grade'], $gradeInfo['point'], $existing['id']]
                    );
                } else {
                    $db->query(
                        "INSERT INTO results (enrollment_id, score, grade, grade_point, semester, year, uploaded_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$enrollmentId, $score, $gradeInfo['grade'], $gradeInfo['point'], 
                         $course['semester'], $course['year'], $this->auth->getCurrentUser()['id']]
                    );
                }
            }
            
            $db->commit();
            $this->json(['success' => true, 'message' => 'Results uploaded successfully']);
            
        } catch (\Exception $e) {
            $db->rollBack();
            $this->json(['error' => 'Failed to upload results: ' . $e->getMessage()], 500);
        }
    }
    
    public function publishResults() {
        $courseId = $_POST['course_id'] ?? null;
        
        if (!$courseId) {
            $this->json(['error' => 'Course ID required'], 400);
            return;
        }
        
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $course = Course::find($courseId);
        
        if (!$course || $course['lecturer_id'] != $lecturer['id']) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }
        
        $db = \App\Core\Database::getInstance();
        $db->query(
            "UPDATE results r
             INNER JOIN enrollments e ON r.enrollment_id = e.id
             INNER JOIN courses c ON e.course_id = c.id
             SET r.is_published = 1
             WHERE c.id = ? AND c.lecturer_id = ?",
            [$courseId, $lecturer['id']]
        );
        
        $this->json(['success' => true, 'message' => 'Results published successfully']);
    }
    
    public function markAttendance() {
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $data = [
            'courses' => Course::getByLecturer($lecturer['id'])
        ];
        $this->view('attendance/mark', $data);
    }
    
    public function postMarkAttendance() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $courseId = $_POST['course_id'] ?? null;
        $date = $_POST['date'] ?? date('Y-m-d');
        $attendance = $_POST['attendance'] ?? [];
        
        if (!$courseId || empty($attendance)) {
            $this->json(['error' => 'Invalid data'], 400);
            return;
        }
        
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $course = Course::find($courseId);
        
        if (!$course || $course['lecturer_id'] != $lecturer['id']) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }
        
        $enrollments = Enrollment::getByCourse($courseId);
        
        foreach ($enrollments as $enrollment) {
            $status = $attendance[$enrollment['id']] ?? 'absent';
            Attendance::markAttendance($enrollment['id'], $date, $status);
        }
        
        $this->json(['success' => true, 'message' => 'Attendance marked successfully']);
    }
    
    public function getCourseStudents() {
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            $this->json(['error' => 'Course ID required'], 400);
            return;
        }
        
        $lecturer = \App\Models\Lecturer::getByUserId($this->auth->getCurrentUser()['id']);
        $course = Course::find($courseId);
        
        if (!$course || $course['lecturer_id'] != $lecturer['id']) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }
        
        $students = Enrollment::getByCourse($courseId);
        $this->json(['students' => $students]);
    }
    
    public function profile() {
        $user = $this->auth->getCurrentUser();
        $lecturer = \App\Models\Lecturer::getByUserId($user['id']);
        
        $data = [
            'user' => $user,
            'lecturer' => $lecturer
        ];
        $this->view('profile/lecturer', $data);
    }
}