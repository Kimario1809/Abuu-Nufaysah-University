<?php
namespace Database\Seeds;

use App\Core\Database;

class DatabaseSeeder {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        $this->seedDepartments();
        $this->seedUsers();
        $this->seedCourses();
        $this->seedEnrollments();
        $this->seedAnnouncements();
    }
    
    private function seedDepartments() {
        $departments = [
            ['code' => 'CS', 'name' => 'Computer Science'],
            ['code' => 'ENG', 'name' => 'Engineering'],
            ['code' => 'BUS', 'name' => 'Business Administration'],
            ['code' => 'MED', 'name' => 'Medicine'],
            ['code' => 'LAW', 'name' => 'Law'],
            ['code' => 'EDU', 'name' => 'Education'],
        ];
        
        foreach ($departments as $dept) {
            $this->db->query(
                "INSERT INTO departments (code, name) VALUES (?, ?)",
                [$dept['code'], $dept['name']]
            );
        }
        
        echo "Departments seeded successfully.\n";
    }
    
    private function seedUsers() {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@abuu.edu',
                'phone' => '+255712345678',
                'password_hash' => password_hash('Admin@123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'full_name' => 'System Administrator'
            ],
            [
                'username' => 'john.lecturer',
                'email' => 'john@abuu.edu',
                'phone' => '+255765432109',
                'password_hash' => password_hash('Lecturer@123', PASSWORD_BCRYPT),
                'role' => 'lecturer',
                'full_name' => 'Dr. John Smith'
            ],
            [
                'username' => 'jane.student',
                'email' => 'jane@abuu.edu',
                'phone' => '+255698765432',
                'password_hash' => password_hash('Student@123', PASSWORD_BCRYPT),
                'role' => 'student',
                'full_name' => 'Jane Doe'
            ]
        ];
        
        foreach ($users as $user) {
            $this->db->query(
                "INSERT INTO users (username, email, phone, password_hash, role, full_name) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$user['username'], $user['email'], $user['phone'] ?? null, $user['password_hash'], 
                 $user['role'], $user['full_name']]
            );
        }
        
        echo "Users seeded successfully.\n";
    }
    
    private function seedCourses() {
        $courses = [
            ['code' => 'CS101', 'name' => 'Introduction to Programming', 'department_id' => 1, 'credit_hours' => 3],
            ['code' => 'CS201', 'name' => 'Data Structures', 'department_id' => 1, 'credit_hours' => 4],
            ['code' => 'CS301', 'name' => 'Database Systems', 'department_id' => 1, 'credit_hours' => 3],
            ['code' => 'ENG101', 'name' => 'Mechanics', 'department_id' => 2, 'credit_hours' => 4],
            ['code' => 'BUS101', 'name' => 'Principles of Management', 'department_id' => 3, 'credit_hours' => 3],
        ];
        
        foreach ($courses as $course) {
            $this->db->query(
                "INSERT INTO courses (code, name, department_id, credit_hours, lecturer_id) 
                 VALUES (?, ?, ?, ?, ?)",
                [$course['code'], $course['name'], $course['department_id'], 
                 $course['credit_hours'], null]
            );
        }
        
        echo "Courses seeded successfully.\n";
    }
    
    private function seedEnrollments() {
        // Assuming student_id = 1, course_ids 1-3
        $enrollments = [
            ['student_id' => 1, 'course_id' => 1],
            ['student_id' => 1, 'course_id' => 2],
            ['student_id' => 1, 'course_id' => 3],
        ];
        
        foreach ($enrollments as $enrollment) {
            $this->db->query(
                "INSERT INTO enrollments (student_id, course_id, status) 
                 VALUES (?, ?, 'enrolled')",
                [$enrollment['student_id'], $enrollment['course_id']]
            );
        }
        
        echo "Enrollments seeded successfully.\n";
    }
    
    private function seedAnnouncements() {
        $announcements = [
            [
                'title' => 'Welcome to Abuu Nufay\'sah University',
                'content' => 'Welcome to the new academic year. We wish you all the best.',
                'author_id' => 1,
                'author_role' => 'admin',
                'priority' => 'high',
                'target_audience' => 'all'
            ],
            [
                'title' => 'Mid-Semester Break',
                'content' => 'Please note that the university will be closed for mid-semester break from August 15-20.',
                'author_id' => 1,
                'author_role' => 'admin',
                'priority' => 'medium',
                'target_audience' => 'all'
            ]
        ];
        
        foreach ($announcements as $announcement) {
            $this->db->query(
                "INSERT INTO announcements (title, content, author_id, author_role, priority, target_audience) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$announcement['title'], $announcement['content'], 
                 $announcement['author_id'], $announcement['author_role'],
                 $announcement['priority'], $announcement['target_audience']]
            );
        }
        
        echo "Announcements seeded successfully.\n";
    }
}