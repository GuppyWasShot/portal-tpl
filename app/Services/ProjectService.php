public function createProject(array $data): int {
    $this->db->beginTransaction();
    
    try {
        // 1. Create project
        $projectId = $this->saveProject($data);
        
        // 2. Save categories
        $this->saveCategories($projectId, $data['categories']);
        
        // 3. Save links
        $this->saveLinks($projectId, $data);
        
        // 4. Handle files
        if (!empty($data['files']['snapshots']['name'][0])) {
            $this->handleSnapshots($projectId, $data['files']);
        }
        
        if (!empty($data['files']['file_upload']['name'][0])) {
            $this->handleDocuments($projectId, $data['files']);
        }
        
        $this->db->commit();
        return $projectId;
        
    } catch (Exception $e) {
        $this->db->rollback();
        throw $e;
    }
}

private function saveProject(array $data): int {
    $status = $data['action'] === 'publish' ? 'Published' : 'Draft';
    
    return $this->projectModel->create([
        'judul' => $data['judul'],
        'deskripsi' => $data['deskripsi'],
        'pembuat' => $data['pembuat'],
        'tanggal_selesai' => $data['tanggal_selesai'],
        'status' => $status
    ]);
}

private function saveCategories(int $projectId, array $categories): void {
    foreach ($categories as $categoryId) {
        $this->projectCategoryModel->create([
            'id_project' => $projectId,
            'id_kategori' => $categoryId
        ]);
    }
}