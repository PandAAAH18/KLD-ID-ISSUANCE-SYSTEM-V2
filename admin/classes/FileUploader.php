<?php
/**
 * FileUploader Class
 * Handles secure file uploads with validation, type checking, and antivirus support
 */
class FileUploader
{
    private array $allowedMimeTypes = [];
    private int $maxFileSize;
    private string $uploadDir;
    private string $uploadDirName;
    private bool $enableVirusScan;

    public function __construct(string $uploadDirName = 'uploads', bool $enableVirusScan = false)
    {
        $this->uploadDirName = $uploadDirName;
        $this->uploadDir = __DIR__ . '/../../' . $uploadDirName;
        $this->maxFileSize = MAX_FILE_SIZE;
        $this->enableVirusScan = $enableVirusScan && UPLOAD_VIRUS_SCAN;
        $this->allowedMimeTypes = ALLOWED_IMAGE_TYPES + ALLOWED_DOCUMENT_TYPES;

        // Ensure directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Validate uploaded file
     * @param array $file $_FILES element
     * @param array|null $allowedTypes Override default allowed types
     * @return array|bool Array with errors if invalid, true if valid
     */
    public function validate(array $file, ?array $allowedTypes = null): array|bool
    {
        $errors = [];

        // Check if file exists
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['No file uploaded'];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = "File size exceeds maximum of " . round($this->maxFileSize / 1048576, 2) . "MB";
        }

        // Check MIME type
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedTypes = $allowedTypes ?? $this->allowedMimeTypes;

        if (!in_array($mimeType, $allowedTypes, true)) {
            $errors[] = "Invalid file type: {$mimeType}. Allowed: " . implode(', ', $allowedTypes);
        }

        // Check for suspicious file extensions
        $fileName = $file['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $dangerousExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'sh', 'bat'];

        if (in_array($ext, $dangerousExts, true)) {
            $errors[] = "Dangerous file extension not allowed: .$ext";
        }

        // Optional: Virus scan
        if ($this->enableVirusScan) {
            $virusResult = $this->scanForVirus($file['tmp_name']);
            if ($virusResult !== true) {
                $errors[] = "Virus scan failed: $virusResult";
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Upload file securely
     * @param array $file $_FILES element
     * @param string $subdir Subdirectory within uploadDir
     * @param array|null $allowedTypes Override allowed types
     * @return array|false Array ['filename' => string, 'path' => string] or false on failure
     */
    public function upload(array $file, string $subdir = '', ?array $allowedTypes = null): array|false
    {
        $validation = $this->validate($file, $allowedTypes);

        if ($validation !== true) {
            error_log("File upload validation failed: " . implode('; ', $validation));
            return false;
        }

        // Generate safe filename
        $fileName = $this->generateSafeFileName($file['name']);
        $uploadPath = $this->uploadDir . ($subdir ? '/' . trim($subdir, '/') : '');

        // Ensure subdirectory exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . '/' . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Set proper permissions
            chmod($fullPath, 0644);

            return [
                'filename' => $fileName,
                'path' => ($subdir ? $subdir . '/' : '') . $fileName,
                'full_path' => $fullPath
            ];
        }

        error_log("Failed to move uploaded file: {$file['tmp_name']} to {$fullPath}");
        return false;
    }

    /**
     * Delete a file
     * @param string $fileName Filename or relative path
     * @param string $subdir Subdirectory
     * @return bool
     */
    public function delete(string $fileName, string $subdir = ''): bool
    {
        $uploadPath = $this->uploadDir . ($subdir ? '/' . trim($subdir, '/') : '');
        $fullPath = $uploadPath . '/' . basename($fileName);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Generate safe filename with timestamp
     * @param string $originalName Original filename
     * @return string Safe filename
     */
    private function generateSafeFileName(string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        // Remove special characters
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        $name = substr($name, 0, 50); // Limit length

        // Add timestamp prefix
        return uniqid($name . '_', true) . '.' . $ext;
    }

    /**
     * Scan file for viruses using ClamAV (if available)
     * @param string $filePath
     * @return bool|string True if safe, error message if infected
     */
    private function scanForVirus(string $filePath): bool|string
    {
        // Check if ClamAV is available
        if (!command_exists('clamscan')) {
            error_log("ClamAV not available for virus scanning");
            return true; // Skip scanning if not available
        }

        $output = [];
        $returnVar = 0;
        exec("clamscan --quiet '{$filePath}'", $output, $returnVar);

        if ($returnVar === 0) {
            return true; // File is clean
        } else if ($returnVar === 1) {
            return "File infected"; // Virus detected
        } else {
            return "Virus scan error (code: $returnVar)";
        }
    }

    /**
     * Check if command exists on system
     */
    private function command_exists(string $cmd): bool
    {
        $result = shell_exec("which $cmd 2>/dev/null || where $cmd 2>nul");
        return !empty($result);
    }

    /**
     * Get upload directory path
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Set allowed MIME types
     */
    public function setAllowedTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;
        return $this;
    }

    /**
     * Set max file size
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
}
?>
