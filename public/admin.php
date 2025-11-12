<?php
// public/admin.php - Admin Front Controller

require_once __DIR__ . '/../bootstrap/autoload.php';

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProjectController;
use App\Middleware\AuthMiddleware;

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/admin', '', $path);

// Simple router
try {
    // Auth routes (no middleware)
    if ($path === '/login' || $path === '/') {
        $controller = new AuthController();
        
        if ($requestMethod === 'GET') {
            $controller->showLoginForm();
        } elseif ($requestMethod === 'POST') {
            $controller->login();
        }
        exit;
    }
    
    if ($path === '/logout') {
        $controller = new AuthController();
        $controller->logout();
        exit;
    }
    
    // Protected routes (requires auth middleware)
    AuthMiddleware::check();
    
    // Dashboard
    if ($path === '/dashboard') {
        $controller = new DashboardController();
        $controller->index();
        exit;
    }
    
    // Projects routes
    if (preg_match('#^/projects/?$#', $path)) {
        $controller = new ProjectController();
        $controller->index();
        exit;
    }
    
    if (preg_match('#^/projects/create/?$#', $path)) {
        $controller = new ProjectController();
        
        if ($requestMethod === 'GET') {
            $controller->create();
        } elseif ($requestMethod === 'POST') {
            $controller->store();
        }
        exit;
    }
    
    if (preg_match('#^/projects/(\d+)/edit/?$#', $path, $matches)) {
        $controller = new ProjectController();
        $id = (int) $matches[1];
        
        if ($requestMethod === 'GET') {
            $controller->edit($id);
        } elseif ($requestMethod === 'POST') {
            $controller->update($id);
        }
        exit;
    }
    
    if (preg_match('#^/projects/(\d+)/delete/?$#', $path, $matches)) {
        $controller = new ProjectController();
        $id = (int) $matches[1];
        $controller->delete($id);
        exit;
    }
    
    if (preg_match('#^/projects/(\d+)/status/?$#', $path, $matches)) {
        $controller = new ProjectController();
        $id = (int) $matches[1];
        
        if ($requestMethod === 'GET') {
            $controller->changeStatusForm($id);
        } elseif ($requestMethod === 'POST') {
            $controller->changeStatus($id);
        }
        exit;
    }
    
    // 404 - Route not found
    http_response_code(404);
    echo "404 - Page Not Found";
    
} catch (\Exception $e) {
    // Error handling
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo "500 - Internal Server Error";
}