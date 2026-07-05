<?php
namespace App\Controllers;

use App\Core\Controller;

class ResultController extends Controller {
    
    public function upload() {
        $this->requireAuth();
        $this->requireRole('lecturer');
        
        $this->view('results/upload', [
            'title' => 'Upload Results'
        ]);
    }
    
    public function postUploadResults() {
        $this->requireAuth();
        $this->requireRole('lecturer');
        
        // Handle result upload logic
        $data = $this->post();
        
        // Validate and process results
        // Add your logic here
        
        $this->setFlash('success', 'Results uploaded successfully');
        $this->redirect('/results');
    }
    
    public function publishResults() {
        $this->requireAuth();
        $this->requireRole('lecturer');
        
        // Handle result publishing logic
        $data = $this->post();
        
        // Add your logic here
        
        $this->setFlash('success', 'Results published successfully');
        $this->redirect('/results');
    }
    
    public function index() {
        $this->requireAuth();
        
        $this->view('results/index', [
            'title' => 'Results'
        ]);
    }
}
