namespace App\Services;

use App\Models\Admin;
use App\Models\ActivityLog;
use App\Models\AdminLog;

class AuthService {
    private Admin $adminModel;
    private ActivityLog $activityLog;
    private AdminLog $adminLog;
    
    public function attemptLogin(string $username, string $password, string $ip): array {
        // Check if IP is locked
        if ($this->isIPLocked($ip)) {
            return [
                'success' => false,
                'error_type' => 'locked'
            ];
        }
        
        // Find admin
        $admin = $this->adminModel->findByUsername($username);
        
        if (!$admin) {
            return $this->handleFailedLogin($username, $ip);
        }
        
        // Verify password
        if (!password_verify($password, $admin['password'])) {
            return $this->handleFailedLogin($username, $ip);
        }
        
        // Success!
        $this->logSuccess($username, $ip, $admin);
        
        return [
            'success' => true,
            'admin' => $admin
        ];
    }
    
    private function isIPLocked(string $ip): bool {
        return $this->adminLog->countRecentFailures($ip, 10) >= 5;
    }
    
    private function handleFailedLogin(string $username, string $ip): array {
        $this->adminLog->logAttempt($username, $ip, 'Failed');
        $attemptsLeft = 5 - $this->adminLog->countRecentFailures($ip, 10);
        
        return [
            'success' => false,
            'error_type' => 'invalid_credentials',
            'attempts_left' => max(0, $attemptsLeft)
        ];
    }
    
    private function logSuccess(string $username, string $ip, array $admin): void {
        $this->adminLog->logAttempt($username, $ip, 'Success');
        $this->adminLog->resetFailures($ip);
        $this->activityLog->create([
            'id_admin' => $admin['id_admin'],
            'username' => $username,
            'action' => 'Login ke sistem'
        ]);
    }
}