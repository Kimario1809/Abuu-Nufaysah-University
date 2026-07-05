<?php
namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller {
    
    public function home() {
        $this->view('home', [
            'title' => 'Welcome to Abuu Nufay\'sah University'
        ]);
    }

    public function privacyPolicy() {
        $this->view('legal/privacy-policy', [
            'title' => 'Privacy Policy'
        ], false);
    }

    public function termsOfService() {
        $this->view('legal/terms-of-service', [
            'title' => 'Terms of Service'
        ], false);
    }

    public function cookiePolicy() {
        $this->view('legal/cookie-policy', [
            'title' => 'Cookie Policy'
        ], false);
    }

    public function dataProtectionPolicy() {
        $this->view('legal/data-protection-policy', [
            'title' => 'Data Protection Policy'
        ], false);
    }
}
