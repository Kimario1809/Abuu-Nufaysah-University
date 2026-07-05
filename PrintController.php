<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

class PrintController extends Controller {
    protected $auth;

    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
    }

    public function report() {
        $user = $this->auth->getCurrentUser();
        $data = [
            'user' => $user,
            'activePage' => 'print'
        ];
        $this->view('print/report', $data);
    }
}
