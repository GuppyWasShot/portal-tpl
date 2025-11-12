namespace App\Controllers\Admin;

use App\Services\AuthService;
use App\Helpers\SessionHelper;

class AuthController {
    private AuthService $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('invalid_request');
            return;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->redirectWithError('input_kosong');
            return;
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $result = $this->authService->attemptLogin($username, $password, $ipAddress);
        
        if ($result['success']) {
            SessionHelper::set('admin_logged_in', true);
            SessionHelper::set('admin_id', $result['admin']['id_admin']);
            SessionHelper::set('admin_username', $result['admin']['username']);
            
            header('Location: /admin/dashboard');
            exit;
        } else {
            $this->handleLoginError($result);
        }
    }
    
    private function handleLoginError(array $result): void {
        switch ($result['error_type']) {
            case 'locked':
                $this->redirectWithError('terkunci');
                break;
            case 'invalid_credentials':
                SessionHelper::setFlash('attempts_left', $result['attempts_left']);
                $this->redirectWithError('gagal');
                break;
            default:
                $this->redirectWithError('gagal');
        }
    }
    
    private function redirectWithError(string $errorType): void {
        header("Location: /admin/login?error=$errorType");
        exit;
    }
}