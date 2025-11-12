class FileUploadService {
    private const SNAPSHOT_DIR = 'uploads/snapshots/';
    private const MAX_SIZE = 2 * 1024 * 1024; // 2MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    
    public function handleMultipleSnapshots(array $files): array {
        $uploaded = [];
        
        foreach ($files['name'] as $idx => $originalName) {
            if ($files['error'][$idx] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $this->validateFile($files, $idx);
            $uploaded[] = $this->uploadFile($files, $idx, self::SNAPSHOT_DIR);
        }
        
        return $uploaded;
    }
    
    private function validateFile(array $files, int $idx): void {
        if ($files['size'][$idx] > self::MAX_SIZE) {
            throw new FileUploadException('File too large');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $files['tmp_name'][$idx]);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            throw new FileUploadException('Invalid file type');
        }
    }
    
    private function uploadFile(array $files, int $idx, string $dir): array {
        $fileName = $this->generateFileName($files['name'][$idx]);
        $filePath = $dir . $fileName;
        
        if (!move_uploaded_file($files['tmp_name'][$idx], '../' . $filePath)) {
            throw new FileUploadException('Upload failed');
        }
        
        return [
            'name' => $files['name'][$idx],
            'path' => $filePath,
            'size' => $files['size'][$idx],
            'type' => $files['type'][$idx]
        ];
    }
    
    private function generateFileName(string $original): string {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        return 'snapshot_' . time() . '_' . uniqid() . '.' . $ext;
    }
}