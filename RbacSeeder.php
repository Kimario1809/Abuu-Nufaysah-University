<?php
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/models/Role.php';
require_once __DIR__ . '/../../app/models/Permission.php';

use App\Core\Database;
use App\Models\Role;
use App\Models\Permission;

class RbacSeeder {
    public static function run() {
        $db = Database::getInstance();

        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Full system access'],
            ['name' => 'lecturer', 'display_name' => 'Lecturer', 'description' => 'Course and student management'],
            ['name' => 'student', 'display_name' => 'Student', 'description' => 'Personal academic access']
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        $permissions = [
            ['name' => '*', 'module' => 'all', 'description' => 'All permissions'],
            ['name' => 'manage_courses', 'module' => 'courses', 'description' => 'Manage courses'],
            ['name' => 'upload_marks', 'module' => 'grades', 'description' => 'Upload marks'],
            ['name' => 'view_students', 'module' => 'students', 'description' => 'View students'],
            ['name' => 'view_results', 'module' => 'grades', 'description' => 'View results'],
            ['name' => 'view_timetable', 'module' => 'timetable', 'description' => 'View timetable'],
            ['name' => 'manage_announcements', 'module' => 'announcements', 'description' => 'Manage announcements']
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $adminRole = Role::findByName('admin');
        $lecturerRole = Role::findByName('lecturer');
        $studentRole = Role::findByName('student');

        $allPermissions = Permission::all();
        foreach ($allPermissions as $perm) {
            if ($adminRole) {
                Role::assignPermission($adminRole['id'], $perm['id']);
            }
        }

        $lecturerPermissions = ['manage_courses', 'upload_marks', 'view_students', 'view_results', 'manage_announcements'];
        foreach ($lecturerPermissions as $name) {
            $perm = Permission::findByName($name);
            if ($lecturerRole && $perm) {
                Role::assignPermission($lecturerRole['id'], $perm['id']);
            }
        }

        $studentPermissions = ['view_results', 'view_timetable'];
        foreach ($studentPermissions as $name) {
            $perm = Permission::findByName($name);
            if ($studentRole && $perm) {
                Role::assignPermission($studentRole['id'], $perm['id']);
            }
        }
    }
}

RbacSeeder::run();
