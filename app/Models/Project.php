<?php
// app/Models/Project.php

namespace App\Models;

use App\Models\Base\Model;

class Project extends Model
{
    protected string $table = 'tbl_project';
    protected string $primaryKey = 'id_project';
    
    /**
     * Get all published projects with categories and ratings
     */
    public function getAllPublished(array $filters = []): array
    {
        $query = "SELECT p.*, 
                  GROUP_CONCAT(DISTINCT c.nama_kategori ORDER BY c.nama_kategori SEPARATOR ', ') as kategori,
                  GROUP_CONCAT(DISTINCT c.warna_hex ORDER BY c.nama_kategori SEPARATOR ',') as warna,
                  AVG(r.skor) as avg_rating,
                  COUNT(DISTINCT r.id_rating) as total_rating
                  FROM {$this->table} p
                  LEFT JOIN tbl_project_category pc ON p.id_project = pc.id_project
                  LEFT JOIN tbl_category c ON pc.id_kategori = c.id_kategori
                  LEFT JOIN tbl_rating r ON p.id_project = r.id_project
                  WHERE p.status = 'Published'";
        
        $params = [];
        
        // Add search filter
        if (!empty($filters['search'])) {
            $query .= " AND (p.judul LIKE ? OR p.pembuat LIKE ? OR p.deskripsi LIKE ?)";
            $searchParam = "%{$filters['search']}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Add category filter
        if (!empty($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $query .= " AND pc.id_kategori IN ($placeholders)";
            $params = array_merge($params, $filters['categories']);
        }
        
        $query .= " GROUP BY p.id_project";
        
        // Add sorting
        $orderBy = $filters['sort'] ?? 'terbaru';
        switch ($orderBy) {
            case 'judul_asc':
                $query .= " ORDER BY p.judul ASC";
                break;
            case 'judul_desc':
                $query .= " ORDER BY p.judul DESC";
                break;
            case 'terlama':
                $query .= " ORDER BY p.tanggal_selesai ASC";
                break;
            case 'rating':
                $query .= " ORDER BY avg_rating DESC";
                break;
            default:
                $query .= " ORDER BY p.id_project DESC";
        }
        
        // Add limit if specified
        if (!empty($filters['limit'])) {
            $query .= " LIMIT " . (int)$filters['limit'];
        }
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Get project detail with all related data
     */
    public function getDetailById(int $id): ?array
    {
        $query = "SELECT p.*, 
                  GROUP_CONCAT(DISTINCT c.nama_kategori ORDER BY c.nama_kategori SEPARATOR ', ') as kategori,
                  GROUP_CONCAT(DISTINCT c.warna_hex ORDER BY c.nama_kategori SEPARATOR ',') as warna,
                  AVG(r.skor) as avg_rating,
                  COUNT(DISTINCT r.id_rating) as total_rating
                  FROM {$this->table} p
                  LEFT JOIN tbl_project_category pc ON p.id_project = pc.id_project
                  LEFT JOIN tbl_category c ON pc.id_kategori = c.id_kategori
                  LEFT JOIN tbl_rating r ON p.id_project = r.id_rating
                  WHERE p.id_project = ? AND p.status = 'Published'
                  GROUP BY p.id_project";
        
        $result = $this->executeQuery($query, [$id]);
        return $result[0] ?? null;
    }
    
    /**
     * Get project statistics
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total projects
        $stats['total'] = $this->count();
        
        // Total published
        $stats['published'] = $this->count(['status' => 'Published']);
        
        // Total draft
        $stats['draft'] = $this->count(['status' => 'Draft']);
        
        // Top rated project
        $query = "SELECT p.judul, AVG(r.skor) as avg_rating, COUNT(r.id_rating) as total_votes
                  FROM {$this->table} p
                  LEFT JOIN tbl_rating r ON p.id_project = r.id_project
                  WHERE p.status = 'Published'
                  GROUP BY p.id_project
                  HAVING total_votes > 0
                  ORDER BY avg_rating DESC, total_votes DESC
                  LIMIT 1";
        
        $result = $this->executeQuery($query);
        $stats['top_rated'] = $result[0] ?? null;
        
        return $stats;
    }
    
    /**
     * Search projects
     */
    public function search(string $keyword): array
    {
        $query = "SELECT p.*, 
                  GROUP_CONCAT(DISTINCT c.nama_kategori SEPARATOR ', ') as kategori,
                  AVG(r.skor) as avg_rating,
                  COUNT(DISTINCT r.id_rating) as total_rating
                  FROM {$this->table} p
                  LEFT JOIN tbl_project_category pc ON p.id_project = pc.id_project
                  LEFT JOIN tbl_category c ON pc.id_kategori = c.id_kategori
                  LEFT JOIN tbl_rating r ON p.id_project = r.id_project
                  WHERE p.status = 'Published'
                  AND (p.judul LIKE ? OR p.pembuat LIKE ? OR p.deskripsi LIKE ?)
                  GROUP BY p.id_project
                  ORDER BY p.id_project DESC";
        
        $searchParam = "%$keyword%";
        return $this->executeQuery($query, [$searchParam, $searchParam, $searchParam]);
    }
    
    /**
     * Get recent projects
     */
    public function getRecent(int $limit = 10): array
    {
        $query = "SELECT p.*, 
                  GROUP_CONCAT(c.nama_kategori SEPARATOR ', ') as kategori,
                  AVG(r.skor) as avg_rating,
                  COUNT(DISTINCT r.id_rating) as total_rating
                  FROM {$this->table} p
                  LEFT JOIN tbl_project_category pc ON p.id_project = pc.id_project
                  LEFT JOIN tbl_category c ON pc.id_kategori = c.id_kategori
                  LEFT JOIN tbl_rating r ON p.id_project = r.id_project
                  GROUP BY p.id_project
                  ORDER BY p.id_project DESC
                  LIMIT ?";
        
        return $this->executeQuery($query, [$limit]);
    }
    
    /**
     * Update project status
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }
    
    /**
     * Get projects by status
     */
    public function getByStatus(string $status): array
    {
        return $this->findAll(['status' => $status], ['id_project' => 'DESC']);
    }
    
    /**
     * Check if project exists
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }
    
    /**
     * Get project with categories
     */
    public function getWithCategories(int $id): ?array
    {
        $project = $this->find($id);
        if (!$project) {
            return null;
        }
        
        // Get categories
        $query = "SELECT c.* FROM tbl_category c
                  INNER JOIN tbl_project_category pc ON c.id_kategori = pc.id_kategori
                  WHERE pc.id_project = ?";
        
        $project['categories'] = $this->executeQuery($query, [$id]);
        
        return $project;
    }
}