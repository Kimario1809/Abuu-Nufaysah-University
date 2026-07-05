<?php
namespace App\Helpers;

use App\Core\Auth;
use App\Models\Permission;

class RbacHelper {
    public static function enforceOwnData($userId, $resourceOwnerId) {
        $auth = Auth::getInstance();
        if ($auth->getCurrentRole() === 'admin') {
            return true;
        }
        if ($auth->getCurrentRole() === 'student') {
            return (int)$userId === (int)$resourceOwnerId;
        }
        return true;
    }

    public static function filterStudentsQuery($baseQuery, $userId, $tableAlias = 's') {
        $auth = Auth::getInstance();
        if ($auth->getCurrentRole() === 'admin') {
            return $baseQuery;
        }
        if ($auth->getCurrentRole() === 'student') {
            return $baseQuery . " AND {$tableAlias}.user_id = {$userId}";
        }
        return $baseQuery;
    }

    public static function filterCoursesQuery($baseQuery, $userId, $tableAlias = 'c') {
        $auth = Auth::getInstance();
        if ($auth->getCurrentRole() === 'admin') {
            return $baseQuery;
        }
        if ($auth->getCurrentRole() === 'lecturer') {
            return $baseQuery . " AND {$tableAlias}.lecturer_id = {$userId}";
        }
        if ($auth->getCurrentRole() === 'student') {
            return $baseQuery . " AND {$tableAlias}.id IN (SELECT course_id FROM enrollments WHERE student_id = {$userId})";
        }
        return $baseQuery;
    }

    public static function getAllowedModules() {
        $auth = Auth::getInstance();
        $role = $auth->getCurrentRole();
        $modules = [
            'admin' => ['dashboard', 'students', 'lecturers', 'courses', 'grades', 'attendance', 'assignments', 'chat', 'reports', 'settings', 'ai'],
            'lecturer' => ['dashboard', 'courses', 'grades', 'attendance', 'assignments', 'chat', 'announcements'],
            'student' => ['dashboard', 'announcements', 'assignments', 'grades', 'attendance', 'chat']
        ];
        return isset($modules[$role]) ? $modules[$role] : [];
    }
}
