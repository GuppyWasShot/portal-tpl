namespace App\Models\Base;

abstract class Model {
    protected Database $db;
    protected string $table;
    
    public function find(int $id): ?array { }
    public function findAll(): array { }
    public function create(array $data): int { }
    public function update(int $id, array $data): bool { }
    public function delete(int $id): bool { }
}