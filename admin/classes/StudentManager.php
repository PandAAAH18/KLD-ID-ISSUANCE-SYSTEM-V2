<?php
require_once __DIR__ . '/../../includes/User.php';
require_once __DIR__ . '/AuditLogger.php';

class StudentManager extends User
{
    private AuditLogger $auditLogger;

    public function __construct($db = null)
    {
        // If no DB connection provided, try to use parent's connection
        if ($db === null) {
            parent::__construct();
            $this->db = $this->getDb(); // Assuming parent has this method
        } else {
            parent::__construct($db);
        }
        
        $this->auditLogger = new AuditLogger($this->db);
    }
/* ====================  STUDENT FUNCTIONS  ==================== */

public function getAllStudents(): array
{
    $sql = "SELECT * FROM student WHERE deleted_at IS NULL ORDER BY id ASC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getStudentById(int $studentId): ?array
{
    $sql = "SELECT * FROM student WHERE id = :id LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $studentId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function addStudent(array $data): bool
{
    $sql = "INSERT INTO student (first_name, last_name, email)
            VALUES (:first_name, :last_name, :email)";
    $stmt = $this->db->prepare($sql);
    $ok = $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name'  => $data['last_name'],
        ':email'      => $data['email']
    ]);

    if ($ok) {
        $id = $this->db->lastInsertId();
        $this->auditLogger->logAction('insert', $id, 'student', [], $data);
    }
    
    return $ok;
}

public function updateStudent(int $studentId, array $data): bool
{
    if (empty($data)) {
        return false;
    }

    $oldData = $this->getStudentById($studentId) ?: [];
    $fields = [];
    $params = [];

    foreach ($data as $key => $value) {
        if ($value !== null && $value !== '') {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }

    if (empty($fields)) {
        return false;
    }

    $params[':id'] = $studentId;
    $sql = "UPDATE student SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $ok = $stmt->execute($params);
    
    if ($ok) {
        $this->auditLogger->logAction('update', $studentId, 'student', $oldData, $data);
    }
    
    return $ok;
}

public function deleteStudent(int $studentId): bool
{
    $oldData = $this->getStudentById($studentId) ?: [];
    $sql = "UPDATE student SET deleted_at = NOW() WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $ok = $stmt->execute([':id' => $studentId]);

    if ($ok) {
        $this->auditLogger->logAction('delete', $studentId, 'student', $oldData, []);
    }
    
    return $ok;
}

public function searchStudents(string $keyword): array
{
    $sql = "SELECT * FROM student
            WHERE (first_name LIKE :kw OR last_name LIKE :kw OR email LIKE :kw)
              AND deleted_at IS NULL
            ORDER BY id ASC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':kw' => '%' . $keyword . '%']);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function filterStudents(array $filters): array
{
    $sql = "SELECT * FROM student WHERE deleted_at IS NULL";
    $params = [];

    if (!empty($filters['course']) && strtolower($filters['course']) !== 'any') {
        $sql .= " AND course = :course";
        $params[':course'] = $filters['course'];
    }

    if (!empty($filters['year_level']) && strtolower($filters['year_level']) !== 'any') {
        $sql .= " AND year_level = :year_level";
        $params[':year_level'] = $filters['year_level'];
    }

    if (isset($filters['profile_completed'])) {
        $sql .= " AND profile_completed = :profile_completed";
        $params[':profile_completed'] = $filters['profile_completed'];
    }

    $sql .= " ORDER BY id ASC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function uploadStudentPhoto(int $studentId, array $file): array
{
    try {
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size: 2MB");
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed: JPEG, PNG, GIF, WebP");
        }

        $uploadDir = '../uploads/student_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9._-]/', '_', $student['email']);
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $this->generateUniqueFilename($uploadDir, $sanitizedEmail, $fileExtension);
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }

        $this->updateStudentPhotoPath($studentId, $filename);

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filePath,
            'message' => 'Photo uploaded successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

public function updateStudentPhoto(int $studentId, array $file): array
{
    try {
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $this->getUploadError($file['error']));
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size: 2MB");
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($mimeType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Invalid file type. Allowed: JPEG, PNG, GIF, WebP");
        }

        $uploadDir = '../uploads/student_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!empty($student['photo_path'])) {
            $this->deletePhotoFile($uploadDir . $student['photo_path']);
        }

        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9._-]/', '_', $student['email']);
        $filename = $this->generateUniqueFilename($uploadDir, $sanitizedEmail, $fileExtension);
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }

        $updateSuccess = $this->updateStudentPhotoPath($studentId, $filename);
        
        if (!$updateSuccess) {
            $this->deletePhotoFile($filePath);
            throw new Exception("Failed to update student record");
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filePath,
            'message' => 'Student photo updated successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

public function deleteStudentPhoto(int $studentId): array
{
    try {
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }

        if (empty($student['photo_path'])) {
            return [
                'success' => true,
                'message' => 'Student has no photo to delete'
            ];
        }

        $uploadDir = '../uploads/student_photos/';
        $filePath = $uploadDir . $student['photo_path'];
        $fileDeleted = $this->deletePhotoFile($filePath);
        $dbUpdated = $this->updateStudentPhotoPath($studentId, null);

        if (!$dbUpdated) {
            throw new Exception("Failed to update student record");
        }

        return [
            'success' => true,
            'message' => 'Student photo deleted successfully',
            'file_deleted' => $fileDeleted
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

public function bulkDeleteStudents(array $studentIds): bool
{
    if (empty($studentIds)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $sql = "UPDATE student SET deleted_at = NOW() WHERE id IN ($placeholders)";
    $stmt = $this->db->prepare($sql);

    return $stmt->execute($studentIds);
}

public function bulkExportStudents(array $studentIds = []): array
{
    try {
        if (empty($studentIds)) {
            $sql = "SELECT * FROM student WHERE deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $sql = "SELECT * FROM student WHERE id IN ($placeholders) AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($studentIds);
        }
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($students)) {
            return [
                'success' => false,
                'message' => 'No students found to export'
            ];
        }

        $this->outputCSV($students);
        exit;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Export failed: ' . $e->getMessage()
        ];
    }
}

public function countStudentsByFilters(array $filters): int
{
    try {
        $whereConditions = ["deleted_at IS NULL"];
        $params = [];
        
        if (isset($filters['course']) && $filters['course'] !== '') {
            $whereConditions[] = "course = :course";
            $params[':course'] = $filters['course'];
        }
        
        if (isset($filters['year_level']) && $filters['year_level'] !== '') {
            $whereConditions[] = "year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        
        if (isset($filters['profile_completed'])) {
            $whereConditions[] = "profile_completed = :profile_completed";
            $params[':profile_completed'] = (bool)$filters['profile_completed'];
        }

        $sql = "SELECT COUNT(*) as count FROM student WHERE " . implode(" AND ", $whereConditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'];
        
    } catch (Exception $e) {
        error_log("Error counting students by filters: " . $e->getMessage());
        return 0;
    }
}

public function importCSV(array $csvFile): array
{
    if ($csvFile['error'] !== UPLOAD_ERR_OK) {
        return ['success_count' => 0, 'errors' => ["Error uploading file"]];
    }
    
    $file = fopen($csvFile['tmp_name'], 'r');
    $importCount = 0;
    $errors = [];
    
    fgetcsv($file); // Skip header
    
    while (($data = fgetcsv($file)) !== false) {
        if (count($data) >= 4) {
            $result = $this->processCSVRow($data);
            if ($result['success']) {
                $importCount++;
            } else {
                $errors[] = $result['error'];
            }
        }
    }
    
    fclose($file);
    
    return [
        'success_count' => $importCount,
        'errors' => $errors
    ];
}

public function assignStudentID(string $email, string $studentId): bool
{
    try {
        $stmt = $this->db->prepare("UPDATE student SET student_id = ? WHERE email = ?");
        $stmt->execute([$studentId, $email]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

public function checkStudentHasAccount(string $email): array|bool
{
    try {
        $sql = "
            SELECT 
                s.*,
                u.user_id,
                u.full_name as user_fullname,
                u.role,
                u.is_verified
            FROM student s
            LEFT JOIN users u ON s.email = u.email
            WHERE s.email = :email
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }

        $response = [
            'has_account' => $result['user_id'] !== null,
            'student_data' => [
                'student_id' => $result['student_id'],
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'email' => $result['email'],
                'year_level' => $result['year_level'],
                'course' => $result['course']
            ]
        ];

        if ($response['has_account']) {
            $response['user_data'] = [
                'user_id' => $result['user_id'],
                'fullname' => $result['user_fullname'],
                'role' => $result['role'],
                'is_verified' => $result['is_verified']
            ];
        } else {
            $response['message'] = 'Student exists but no user account found';
        }

        return $response;
        
    } catch (PDOException $e) {
        error_log("Database error in checkStudentHasAccount: " . $e->getMessage());
        return false;
    }
}

/* ====================  PRIVATE HELPER METHODS  ==================== */

private function updateStudentPhotoPath(int $studentId, ?string $filename): bool
{
    try {
        $sql = "UPDATE student SET photo_path = :photoPath WHERE id = :studentId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':photoPath', $filename, PDO::PARAM_STR);
        $stmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error updating student photo path: " . $e->getMessage());
        return false;
    }
}

private function getUploadError(int $errorCode): string
{
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
    ];
    
    return $uploadErrors[$errorCode] ?? 'Unknown upload error';
}

private function deletePhotoFile(string $filePath): bool
{
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

private function generateUniqueFilename(string $directory, string $baseName, string $extension): string
{
    $filename = $baseName . '.' . $extension;
    $filePath = $directory . $filename;
    $counter = 1;

    while (file_exists($filePath)) {
        $filename = $baseName . '_' . $counter . '.' . $extension;
        $filePath = $directory . $filename;
        $counter++;
    }

    return $filename;
}

private function outputCSV(array $students): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    
    $headers = [
        'ID', 'Student ID', 'Email', 'First Name', 'Last Name', 
        'Year Level', 'Course', 'Contact Number', 'Address',
        'Emergency Contact', 'Blood Type', 'Profile Completed',
        'Created At', 'Digital ID Generated At'
    ];
    
    fputcsv($output, $headers);
    
    foreach ($students as $student) {
        $row = [
            $student['id'],
            $student['student_id'],
            $student['email'],
            $student['first_name'],
            $student['last_name'],
            $student['year_level'],
            $student['course'],
            $student['contact_number'],
            $student['address'],
            $student['emergency_contact'],
            $student['blood_type'],
            $student['profile_completed'] ? 'Yes' : 'No',
            $student['created_at'],
            $student['digital_id_generated_at']
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
}

private function processCSVRow(array $data): array
{
    $email = trim($data[0]);
    $studentId = trim($data[1]);
    $firstName = trim($data[2]);
    $lastName = trim($data[3]);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => "Invalid email: $email"];
    }
    
    if (empty($studentId)) {
        return ['success' => false, 'error' => "Missing Student ID for: $email"];
    }
    
    try {
        $check = $this->db->prepare("SELECT id FROM student WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $stmt = $this->db->prepare("UPDATE student SET student_id = ?, first_name = ?, last_name = ? WHERE email = ?");
            $stmt->execute([$studentId, $firstName, $lastName, $email]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO student (email, student_id, first_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $studentId, $firstName, $lastName]);
        }
        
        if ($stmt->rowCount() > 0) {
            $fullName = $firstName . ' ' . $lastName;
            $updateUser = $this->db->prepare("UPDATE users SET full_name = ? WHERE email = ?");
            $updateUser->execute([$fullName, $email]);
            
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => "No changes made for: $email"];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => "Database error for $email: " . $e->getMessage()];
    }
}
}
