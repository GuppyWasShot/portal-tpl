<?php
// ===================================================================
// app/Helpers/SessionHelper.php
// ===================================================================

namespace App\Helpers;

class SessionHelper
{
    /**
     * Set session value
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Set flash message (one-time message)
     */
    public static function setFlash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get and remove flash message
     */
    public static function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::get('admin_logged_in') === true;
    }
    
    /**
     * Get current admin ID
     */
    public static function getAdminId(): ?int
    {
        return self::get('admin_id');
    }
    
    /**
     * Get current admin username
     */
    public static function getAdminUsername(): ?string
    {
        return self::get('admin_username');
    }
    
    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        session_unset();
        session_destroy();
        
        // Start new session for flash messages
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Regenerate session ID (for security)
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}


// ===================================================================
// app/Middleware/AuthMiddleware.php
// ===================================================================

namespace App\Middleware;

use App\Helpers\SessionHelper;

class AuthMiddleware
{
    /**
     * Check if user is authenticated
     */
    public static function check(): void
    {
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('error', 'belum_login');
            header('Location: /admin/login');
            exit;
        }
    }
    
    /**
     * Check if user is guest (not authenticated)
     */
    public static function guest(): void
    {
        if (SessionHelper::isLoggedIn()) {
            header('Location: /admin/dashboard');
            exit;
        }
    }
}


// ===================================================================
// app/Helpers/ValidationHelper.php
// ===================================================================

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Validate required fields
     */
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Field $field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL format
     */
    public static function url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate file upload
     */
    public static function file(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error";
            return $errors;
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = "File too large. Max size: " . ($maxSize / 1024 / 1024) . "MB";
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowedTypes);
        }
        
        return $errors;
    }
}


// ===================================================================
// app/Helpers/SecurityHelper.php
// ===================================================================

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!SessionHelper::has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            SessionHelper::set('csrf_token', $token);
        }
        
        return SessionHelper::get('csrf_token');
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = SessionHelper::get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}