<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Validator;
use App\Helpers\MailHelper;
use App\Models\User;
use App\Models\Department;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\PasswordReset;
use App\Models\Role;

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
    }
    
    public function login() {
        if ($this->auth->isAuthenticated()) {
            $this->redirect($this->auth->getCurrentRole() . '/dashboard');
            return;
        }
        
        $data = [
            'csrf_token' => CSRF::getToken()
        ];
        $this->view('auth/login', $data, false);
    }
    
    public function postLogin() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $identifier = trim($_POST['email'] ?? $_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $validator = new Validator();
        $rules = [
            'password' => 'required|min:6'
        ];
        
        if (!$validator->validate($_POST, $rules)) {
            $this->json(['errors' => $validator->getErrors()], 400);
            return;
        }
        
        $result = $this->auth->login($identifier, $password);
        
        if ($result['success']) {
            $role = $result['role'];
            $this->json([
                'success' => true,
                'redirect' => "/{$role}/dashboard"
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => $result['message']
            ], 401);
        }
    }
    
    public function logout() {
        $this->auth->logout();
        $this->redirect('/login');
    }
    
    public function register() {
        $data = [
            'departments' => Department::getAll()
        ];
        $this->view('auth/register', $data, false);
    }
    
    public function postRegister() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $validator = new Validator();
        $rules = [
            'full_name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'phone' => 'required|min:8',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:student,lecturer'
        ];
        
        if (!$validator->validate($_POST, $rules)) {
            $this->json(['errors' => $validator->getErrors()], 400);
            return;
        }
        
        // Create user
        $userData = [
            'username' => strtolower(str_replace(' ', '.', $_POST['full_name'])),
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
            'role' => $_POST['role'],
            'full_name' => $_POST['full_name']
        ];
        
        $userId = User::create($userData);
        
        if (!$userId) {
            $this->json(['error' => 'Registration failed'], 500);
            return;
        }
        
        $role = Role::findByName($_POST['role']);
        if ($role) {
            Role::assignUserRole($userId, $role['id']);
        }

        // Create role-specific record
        if ($_POST['role'] === 'student') {
            Student::create([
                'user_id' => $userId,
                'student_id' => 'STU' . date('Y') . str_pad($userId, 4, '0', STR_PAD_LEFT),
                'department_id' => $_POST['department_id'] ?? null,
                'enrollment_date' => date('Y-m-d')
            ]);
        } elseif ($_POST['role'] === 'lecturer') {
            Lecturer::create([
                'user_id' => $userId,
                'employee_id' => 'EMP' . date('Y') . str_pad($userId, 4, '0', STR_PAD_LEFT),
                'department_id' => $_POST['department_id'] ?? null
            ]);
        }
        
        $this->json([
            'success' => true,
            'message' => 'Registration successful! Please login.'
        ]);
    }
    
    public function forgotPassword() {
        $this->view('auth/forgot-password', [], false);
    }
    
    public function postForgotPassword() {
        $email = trim($_POST['email'] ?? '');
        
        $user = User::findByEmail($email);
        if (!$user) {
            $this->json(['error' => 'Email not found'], 404);
            return;
        }
        
        PasswordReset::deleteByUserId($user['id']);
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        PasswordReset::createRecord([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);

        $resetUrl = ($_ENV['APP_URL'] ?? 'http://127.0.0.1:8000') . '/reset-password/' . $token;
        $subject = 'Reset your password';
        $message = '<h3>Hello ' . htmlspecialchars($user['full_name']) . '</h3>'
            . '<p>We received a request to reset your password.</p>'
            . '<p><a href="' . $resetUrl . '">Click here to reset your password</a></p>'
            . '<p>If you did not request this, you can ignore this email.</p>';

        $result = MailHelper::send($email, $subject, $message);

        if (!$result['success']) {
            $this->json([
                'success' => false,
                'message' => 'Could not send reset email. ' . $result['message']
            ], 500);
            return;
        }
        
        $this->json([
            'success' => true,
            'message' => 'Password reset link sent to your email. Check Gmail inbox and spam folder.'
        ]);
    }

    public function resetPassword($token) {
        $reset = PasswordReset::findByToken($token);
        if (!$reset) {
            $this->view('auth/reset-password', ['error' => 'This password reset link is invalid or has expired.'], false);
            return;
        }

        $this->view('auth/reset-password', ['token' => $token, 'user_id' => $reset['user_id']], false);
    }

    public function postResetPassword($token) {
        $reset = PasswordReset::findByToken($token);
        if (!$reset) {
            $this->json(['success' => false, 'message' => 'Invalid or expired reset token.'], 400);
            return;
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if (strlen($password) < 8 || $password !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'Password must be at least 8 characters and match confirmation.'], 400);
            return;
        }

        User::changePassword($reset['user_id'], $password);
        PasswordReset::deleteByUserId($reset['user_id']);

        $this->json([
            'success' => true,
            'message' => 'Password updated successfully. You can now log in.'
        ]);
    }
    
    protected function validateCSRF() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return CSRF::validateToken($token);
    }
}