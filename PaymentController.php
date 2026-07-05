<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Payment;
use App\Models\Student;

class PaymentController extends Controller {
    protected $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
    }
    
    public function index() {
        $this->requireRole('admin');
        $data = [
            'payments' => $this->getAllPayments()
        ];
        $this->view('payments/index', $data);
    }
    
    public function myPayments() {
        $this->requireRole('student');
        $student = Student::getByUserId($this->auth->getCurrentUser()['id']);
        $data = [
            'payments' => Payment::getByStudent($student['id'])
        ];
        $this->view('payments/my-payments', $data);
    }
    
    public function create() {
        $this->requireRole('admin');
        $data = [
            'students' => Student::search('')
        ];
        $this->view('payments/create', $data);
    }
    
    public function store() {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $this->requireRole('admin');
        
        $data = [
            'student_id' => $_POST['student_id'] ?? null,
            'amount' => $_POST['amount'] ?? 0,
            'currency' => strtoupper($_POST['currency'] ?? 'USD'),
            'payment_type' => $_POST['payment_type'] ?? 'tuition',
            'due_date' => $_POST['due_date'] ?? null,
            'notes' => $_POST['notes'] ?? ''
        ];
        
        $data['receipt_no'] = Payment::generateReceiptNo();
        
        $id = Payment::create($data);
        
        if ($id) {
            $this->json(['success' => true, 'message' => 'Payment recorded successfully']);
        } else {
            $this->json(['error' => 'Failed to record payment'], 500);
        }
    }
    
    public function markPaid($id) {
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        $this->requireRole('admin');
        
        $payment = Payment::find($id);
        if (!$payment) {
            $this->json(['error' => 'Payment not found'], 404);
            return;
        }
        
        Payment::markAsPaid($id, $_POST['transaction_id'] ?? null);
        $this->json(['success' => true, 'message' => 'Payment marked as paid']);
    }
    
    public function receipt($id) {
        $payment = Payment::find($id);
        if (!$payment) {
            $this->redirect('/payments');
            return;
        }
        
        $student = Student::find($payment['student_id']);
        $user = \App\Models\User::find($student['user_id']);
        
        $data = [
            'payment' => $payment,
            'student' => $student,
            'user' => $user
        ];
        $this->view('payments/receipt', $data);
    }
    
    private function getAllPayments() {
        $db = \App\Core\Database::getInstance();
        $sql = "SELECT p.*, u.full_name as student_name, s.student_id
                FROM payments p
                INNER JOIN students s ON p.student_id = s.id
                INNER JOIN users u ON s.user_id = u.id
                ORDER BY p.payment_date DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
}