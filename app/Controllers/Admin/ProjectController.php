public function store(): void {
    try {
        $data = $this->getFormData();
        $this->validateData($data);
        
        $projectId = $this->projectService->createProject($data);
        
        $this->logActivity("Menambahkan karya: {$data['judul']}", $projectId);
        
        SessionHelper::setFlash('success', 'tambah');
        header('Location: /admin/projects');
        exit;
        
    } catch (ValidationException $e) {
        $this->handleValidationError($e);
    } catch (Exception $e) {
        $this->handleGenericError($e);
    }
}

private function getFormData(): array {
    return [
        'judul' => trim($_POST['judul'] ?? ''),
        'pembuat' => trim($_POST['pembuat'] ?? ''),
        'deskripsi' => trim($_POST['deskripsi'] ?? ''),
        'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '',
        'categories' => $_POST['kategori'] ?? [],
        'action' => $_POST['action'] ?? 'draft',
        'files' => $_FILES
    ];
}

private function validateData(array $data): void {
    $required = ['judul', 'pembuat', 'deskripsi', 'tanggal_selesai'];
    $errors = ValidationHelper::required($data, $required);
    
    if (empty($data['categories'])) {
        $errors['categories'] = 'Pilih minimal satu kategori';
    }
    
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }
}