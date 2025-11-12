<?php
// app/Controllers/Admin/AuthController.php

namespace App\Controllers\Admin;

use App\Services\AuthService;
use App\Helpers\SessionHelper;

class AuthController
{
    private AuthService $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Show login form
     */
    public function showLoginForm(): void
    {
        // If already logged in, redirect to dashboard
        if (SessionHelper::isLoggedIn()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        // Get error messages from session if any
        $error = SessionHelper::getFlash('error');
        $errorType = SessionHelper::getFlash('error_type');
        $attemptsLeft = SessionHelper::getFlash('attempts_left');
        
        // Load view
        require_once __DIR__ . '/../../../views/admin/auth/login.php';
    }
    
    /**
     * Process login
     */
    public function login(): void
    {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('invalid_request');
            return;
        }
        
        // Get input
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $this->redirectWithError('input_kosong');
            return;
        }
        
        // Get user IP
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        // Attempt login
        $result = $this->authService->attemptLogin($username, $password, $ipAddress);
        
        if ($result['success']) {
            // Set session
            SessionHelper::set('admin_logged_in', true);
            SessionHelper::set('admin_id', $result['admin']['id_admin']);
            SessionHelper::set('admin_username', $result['admin']['username']);
            
            // Redirect to dashboard
            header('Location: /admin/dashboard');
            exit;
        } else {
            // Handle different error cases
            switch ($result['error_type']) {
                case 'locked':
                    $this->redirectWithError('terkunci');
                    break;
                    
                case 'invalid_credentials':
                    SessionHelper::setFlash('error', 'gagal');
                    SessionHelper::setFlash('error_type', 'gagal');
                    SessionHelper::setFlash('attempts_left', $result['attempts_left'] ?? 0);
                    header('Location: /admin/login');
                    exit;
                    
                default:
                    $this->redirectWithError('gagal');
            }
        }
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        // Log activity before destroying session
        $adminId = SessionHelper::get('admin_id');
        $username = SessionHelper::get('admin_username');
        
        if ($adminId && $username) {
            $this->authService->logActivity($adminId, $username, 'Logout dari sistem');
        }
        
        // Destroy session
        SessionHelper::destroy();
        
        // Redirect to login with success message
        SessionHelper::setFlash('success', 'logout');
        header('Location: /admin/login');
        exit;
    }
    
    /**
     * Helper method to redirect with error
     */
    private function redirectWithError(string $errorType): void
    {
        header("Location: /admin/login?error=$errorType");
        exit;
    }
}