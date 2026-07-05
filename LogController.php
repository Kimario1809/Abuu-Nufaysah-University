<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Logger;

class LogController extends Controller {
    protected $auth;
    private $logger;
    
    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->logger = Logger::getInstance();
        $this->requireAuth();
        $this->requireRole('admin');
    }
    
    public function index() {
        $this->view('logs/index');
    }
    
    public function view($file) {
        $content = $this->logger->readLog($file, 500);
        $this->json(['content' => implode('', $content)]);
    }
    
    public function delete($file) {
        $result = $this->logger->deleteLog($file);
        $this->json(['success' => $result]);
    }
    
    public function clear($file) {
        $result = $this->logger->clearLog($file);
        $this->json(['success' => $result]);
    }
    
    public function stats() {
        $stats = $this->logger->getStats();
        $this->json($stats);
    }
}