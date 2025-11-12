// ✅ Singleton pattern dengan OOP
namespace App\Config;

class Database {
    private static ?Database $instance = null;
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}