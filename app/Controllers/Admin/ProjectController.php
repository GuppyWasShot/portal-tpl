<?php
// app/Controllers/Admin/ProjectController.php

namespace App\Controllers\Admin;

use App\Services\ProjectService;
use App\Services\FileUploadService;
use App\Models\Project;
use App\Models\Category;
use App\Helpers\SessionHelper;

class ProjectController
{
    private ProjectService $projectService;
    private FileUploadService $fileService;
    private Project $projectModel;
    private Category $categoryModel;
    
    public function __construct()
    {
        $this->projectService = new ProjectService();
        $this->fileService = new FileUploadService();
        $this->projectModel = new Project();
        $this->categoryModel = new Category();
    }
    
    /**
     * List all projects
     */
    public function index(): void
    {
        // Get all projects with filters
        $searchQuery = $_GET['search'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $filters = [];
        if ($searchQuery) {
            $filters['search'] = $searchQuery;
        }
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }
        
        $projects = $this->projectModel->getRecent(100); // Get all for admin
        
        // Get success message if any
        $success = SessionHelper::getFlash('success');
        
        // Load view
        require_once __DIR__ . '/../../../views/admin/projects/index.php';
    }
    
    /**
     * Show create form
     */
    public function create(): void
    {
        // Get all categories
        $categories = $this->categoryModel->findAll();
        
        // Get error if any
        $error = SessionHelper::getFlash('error');
        
        // Load view
        require_once __DIR__ . '/../../../views/admin/projects/create.php';
    }
    
    /**
     * Store new project
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/projects/create');
            exit;
        }
        
        try {
            // Get form data
            $data = [
                'judul' => trim($_POST['judul'] ?? ''),
                'pembuat' => trim($_POST['pembuat'] ?? ''),
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '',
                'link_utama' => trim($_POST['link_utama'] ?? ''),
                'categories' => $_POST['kategori'] ?? [],
                'action' => $_POST['action'] ?? 'draft',
                'additional_links' => [
                    'labels' => $_POST['link_label'] ?? [],
                    'urls' => $_POST['link_url'] ?? []
                ],
                'file_labels' => $_POST['file_label'] ?? []
            ];
            
            // Validate required fields
            if (empty($data['judul']) || empty($data['pembuat']) || 
                empty($data['deskripsi']) || empty($data['tanggal_selesai']) ||
                empty($data['link_utama'])) {
                SessionHelper::setFlash('error', 'empty_field');
                header('Location: /admin/projects/create');
                exit;
            }
            
            if (empty($data['categories'])) {
                SessionHelper::setFlash('error', 'no_category');
                header('Location: /admin/projects/create');
                exit;
            }
            
            // Handle file uploads
            $uploadedFiles = [];
            if (!empty($_FILES['snapshots']['name'][0])) {
                $uploadedFiles['snapshots'] = $this->fileService->handleMultipleSnapshots(
                    $_FILES['snapshots']
                );
            }
            
            if (!empty($_FILES['file_upload']['name'][0])) {
                $uploadedFiles['documents'] = $this->fileService->handleMultipleDocuments(
                    $_FILES['file_upload'],
                    $data['file_labels']
                );
            }
            
            $data['files'] = $uploadedFiles;
            
            // Create project
            $projectId = $this->projectService->createProject($data);
            
            // Log activity
            $adminId = SessionHelper::get('admin_id');
            $username = SessionHelper::get('admin_username');
            $status = $data['action'] === 'publish' ? 'Published' : 'Draft';
            
            $this->projectService->logActivity(
                $adminId,
                $username,
                "Menambahkan karya: {$data['judul']} (Status: $status)",
                $projectId
            );
            
            SessionHelper::setFlash('success', 'tambah');
            header('Location: /admin/projects');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error creating project: " . $e->getMessage());
            SessionHelper::setFlash('error', 'database_error');
            SessionHelper::setFlash('error_message', $e->getMessage());
            header('Location: /admin/projects/create');
            exit;
        }
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): void
    {
        // Get project with categories
        $project = $this->projectModel->getWithCategories($id);
        
        if (!$project) {
            header('Location: /admin/projects?error=not_found');
            exit;
        }
        
        // Get all categories
        $categories = $this->categoryModel->findAll();
        
        // Get project links
        $links = $this->projectService->getProjectLinks($id);
        
        // Get project files
        $files = $this->projectService->getProjectFiles($id);
        
        // Get error if any
        $error = SessionHelper::getFlash('error');
        
        // Load view
        require_once __DIR__ . '/../../../views/admin/projects/edit.php';
    }
    
    /**
     * Update project
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/projects/$id/edit");
            exit;
        }
        
        try {
            // Get form data
            $data = [
                'id' => $id,
                'judul' => trim($_POST['judul'] ?? ''),
                'pembuat' => trim($_POST['pembuat'] ?? ''),
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '',
                'categories' => $_POST['kategori'] ?? [],
                'action' => $_POST['action'] ?? 'draft',
                'additional_links' => [
                    'labels' => $_POST['link_label'] ?? [],
                    'urls' => $_POST['link_url'] ?? []
                ],
                'file_labels' => $_POST['file_label'] ?? []
            ];
            
            // Validate required fields
            if (empty($data['judul']) || empty($data['pembuat']) || 
                empty($data['deskripsi']) || empty($data['tanggal_selesai'])) {
                SessionHelper::setFlash('error', 'empty_field');
                header("Location: /admin/projects/$id/edit");
                exit;
            }
            
            if (empty($data['categories'])) {
                SessionHelper::setFlash('error', 'no_category');
                header("Location: /admin/projects/$id/edit");
                exit;
            }
            
            // Handle new file uploads
            $uploadedFiles = [];
            if (!empty($_FILES['snapshots']['name'][0])) {
                $uploadedFiles['snapshots'] = $this->fileService->handleMultipleSnapshots(
                    $_FILES['snapshots']
                );
            }
            
            if (!empty($_FILES['file_upload']['name'][0])) {
                $uploadedFiles['documents'] = $this->fileService->handleMultipleDocuments(
                    $_FILES['file_upload'],
                    $data['file_labels']
                );
            }
            
            $data['files'] = $uploadedFiles;
            
            // Update project
            $this->projectService->updateProject($data);
            
            // Log activity
            $adminId = SessionHelper::get('admin_id');
            $username = SessionHelper::get('admin_username');
            $status = $data['action'] === 'publish' ? 'Published' : 'Draft';
            
            $this->projectService->logActivity(
                $adminId,
                $username,
                "Mengupdate karya: {$data['judul']} (Status: $status)",
                $id
            );
            
            SessionHelper::setFlash('success', 'edit');
            header('Location: /admin/projects');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error updating project: " . $e->getMessage());
            SessionHelper::setFlash('error', 'database_error');
            SessionHelper::setFlash('error_message', $e->getMessage());
            header("Location: /admin/projects/$id/edit");
            exit;
        }
    }
    
    /**
     * Delete project
     */
    public function delete(int $id): void
    {
        try {
            // Get project info first
            $project = $this->projectModel->find($id);
            
            if (!$project) {
                header('Location: /admin/projects?error=not_found');
                exit;
            }
            
            // Delete project (including files)
            $this->projectService->deleteProject($id);
            
            // Log activity
            $adminId = SessionHelper::get('admin_id');
            $username = SessionHelper::get('admin_username');
            
            $this->projectService->logActivity(
                $adminId,
                $username,
                "Menghapus karya: {$project['judul']}"
            );
            
            SessionHelper::setFlash('success', 'hapus');
            header('Location: /admin/projects');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error deleting project: " . $e->getMessage());
            header('Location: /admin/projects?error=delete_failed');
            exit;
        }
    }
    
    /**
     * Show change status form
     */
    public function changeStatusForm(int $id): void
    {
        $project = $this->projectModel->find($id);
        
        if (!$project) {
            header('Location: /admin/projects?error=not_found');
            exit;
        }
        
        // Load view
        require_once __DIR__ . '/../../../views/admin/projects/change-status.php';
    }
    
    /**
     * Update project status
     */
    public function changeStatus(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/projects/$id/status");
            exit;
        }
        
        $newStatus = $_POST['status'] ?? '';
        
        if (!in_array($newStatus, ['Draft', 'Published', 'Hidden'])) {
            header("Location: /admin/projects/$id/status?error=invalid_status");
            exit;
        }
        
        try {
            // Get project info
            $project = $this->projectModel->find($id);
            
            // Update status
            $this->projectModel->updateStatus($id, $newStatus);
            
            // Log activity
            $adminId = SessionHelper::get('admin_id');
            $username = SessionHelper::get('admin_username');
            
            $this->projectService->logActivity(
                $adminId,
                $username,
                "Mengubah status karya '{$project['judul']}' menjadi $newStatus",
                $id
            );
            
            SessionHelper::setFlash('success', 'status');
            header('Location: /admin/projects');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error changing status: " . $e->getMessage());
            header("Location: /admin/projects/$id/status?error=update_failed");
            exit;
        }
    }
}