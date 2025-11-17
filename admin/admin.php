<?php
require_once '../includes/User.php';
require_once __DIR__.'/../vendor/autoload.php';
use Dompdf\Dompdf;          // ← correct
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Config\QrCodeConfig; // CRITICAL CLASS: MUST LOAD
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
class Admin extends User
{
    public function getDb(): PDO {
    return $this->db;
}

    /* ====================  STUDENT FUNCTIONS  ==================== */
public function getAllStudents()
{
        $sql = "SELECT * FROM student WHERE deleted_at IS NULL ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getStudentById($studentId)
{
    $sql = "SELECT * FROM student WHERE id = :id LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $studentId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function addStudent($data)
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
        $this->logAction('insert', $id, 'student', [], $data);
    }
    return $ok;
}


public function updateStudent(int $studentId, array $data): bool
{
    if (empty($data)) {
        return false;
    }

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
        $this->logAction('update', $studentId, 'student', $old, $data);
    }
    return $ok;
}



public function deleteStudent($studentId)
{
    $sql = "UPDATE student SET deleted_at = NOW() WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $ok = $stmt->execute([':id' => $studentId]);

    if ($ok) {
        $this->logAction('delete', $studentId, 'student', $old, []);
    }
    return $ok;
}



public function searchStudents($keyword)
{
    $sql = "SELECT * FROM student
            WHERE (first_name LIKE :kw OR last_name LIKE :kw OR email LIKE :kw)
              AND deleted_at IS NULL
            ORDER BY id ASC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':kw' => '%' . $keyword . '%']);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function filterStudents(array $filters)
{
    $sql = "SELECT * FROM student WHERE 1=1"; 
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

public function uploadStudentPhoto($studentId, $file)
{
    try {
        // Validate student exists and get email
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        $studentEmail = $student['email'];
        
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }
        
        // Validate file size (e.g., 2MB max)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size: 2MB");
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed: JPEG, PNG, GIF, WebP");
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/student_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Sanitize email for filename
        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9._-]/', '_', $studentEmail);
        
        // Get file extension from original name
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Generate filename: email + extension
        $filename = $sanitizedEmail . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        // Handle filename conflicts
        $counter = 1;
        while (file_exists($filePath)) {
            $filename = $sanitizedEmail . '_' . $counter . '.' . $fileExtension;
            $filePath = $uploadDir . $filename;
            $counter++;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }
        
        // Update student record with photo path (optional)
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

private function updateStudentPhotoPath($studentId, $filename)
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

public function updateStudentPhoto($studentId, $file)
{
    try {
        // Validate student exists
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        $studentEmail = $student['email'];
        
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $this->getUploadError($file['error']));
        }
        
        // Validate file size (2MB max)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size: 2MB");
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($mimeType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Invalid file type. Allowed: JPEG, PNG, GIF, WebP");
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/student_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Delete old photo if exists
        if (!empty($student['photo_path'])) {
            $this->deletePhotoFile($uploadDir . $student['photo_path']);
        }
        
        // Sanitize email for filename
        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9._-]/', '_', $studentEmail);
        
        // Generate filename: email + extension
        $filename = $sanitizedEmail . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        // Handle filename conflicts
        $counter = 1;
        while (file_exists($filePath)) {
            $filename = $sanitizedEmail . '_' . $counter . '.' . $fileExtension;
            $filePath = $uploadDir . $filename;
            $counter++;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }
        
        // Update student record with new photo path
        $updateSuccess = $this->updateStudentPhotoPath($studentId, $filename);
        
        if (!$updateSuccess) {
            // Rollback: delete the uploaded file if database update fails
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

// Helper method to get upload error message
private function getUploadError($errorCode)
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

public function deleteStudentPhoto($studentId)
{
    try {
        // Validate student exists
        $student = $this->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Check if student has a photo
        if (empty($student['photo_path'])) {
            return [
                'success' => true,
                'message' => 'Student has no photo to delete'
            ];
        }
        
        $uploadDir = '../uploads/student_photos/';
        $filePath = $uploadDir . $student['photo_path'];
        
        // Delete the physical file
        $fileDeleted = $this->deletePhotoFile($filePath);
        
        // Update database to remove photo path
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

// Helper method to delete physical file
private function deletePhotoFile($filePath)
{
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}


public function bulkDeleteStudents(array $studentIds): bool
{
    if (empty($studentIds)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

    $sql = "UPDATE student 
            SET deleted_at = NOW() 
            WHERE id IN ($placeholders)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute($studentIds);
}


public function bulkExportStudents($studentIds = [])
{
    try {
        if (empty($studentIds)) {
            $sql = "SELECT * FROM student WHERE deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
        } else {
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $sql = "SELECT * FROM student WHERE id IN ($placeholders) AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($studentIds);
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($students)) {
            return [
                'success' => false,
                'message' => 'No student found to export'
            ];
        }
        
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
        exit;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Export failed: ' . $e->getMessage()
        ];
    }
}


public function countStudentsByFilters($filters)
{
    try {
        $whereConditions = [];
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
            $params[':profile_completed'] = (bool)$filters['profile_completed'] ? 1 : 0;
        }
        
        // Handle deleted_at filter
        if (isset($filters['deleted_at'])) {
            if ($filters['deleted_at'] === 'deleted') {
                $whereConditions[] = "deleted_at IS NOT NULL";
            } elseif ($filters['deleted_at'] === 'active') {
                $whereConditions[] = "deleted_at IS NULL";
            }
        } else {
            $whereConditions[] = "deleted_at IS NULL";
        }
        
        $sql = "SELECT COUNT(*) as count FROM student";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'];
        
    } catch (Exception $e) {
        error_log("Error counting student by filters: " . $e->getMessage());
        return 0;
    }
}

public function importCSV($csv_file) {
        if ($csv_file['error'] !== UPLOAD_ERR_OK) {
            return ['success_count' => 0, 'errors' => ["Error uploading file"]];
        }
        
        $file = fopen($csv_file['tmp_name'], 'r');
        $import_count = 0;
        $errors = [];
        
        // Skip header
        fgetcsv($file);
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if (count($data) >= 4) {
                $email = trim($data[0]);
                $student_id = trim($data[1]);
                $first_name = trim($data[2]);
                $last_name = trim($data[3]);
                
                // Basic validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid email: $email";
                    continue;
                }
                
                if (empty($student_id)) {
                    $errors[] = "Missing Student ID for: $email";
                    continue;
                }
                
                try {
                    $check = $this->db->prepare("SELECT id FROM student WHERE email = ?");
                    $check->execute([$email]);
                    
                    if ($check->rowCount() > 0) {
                        $stmt = $this->db->prepare("UPDATE student SET student_id = ?, first_name = ?, last_name = ? WHERE email = ?");
                        $stmt->execute([$student_id, $first_name, $last_name, $email]);
                    } else {
                        $stmt = $this->db->prepare("INSERT INTO student (email, student_id, first_name, last_name) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$email, $student_id, $first_name, $last_name]);
                    }
                    
                    if ($stmt->rowCount() > 0) {
                        $import_count++;
                        
                        $full_name = $first_name . ' ' . $last_name;
                        $update_user = $this->db->prepare("UPDATE users SET full_name = ? WHERE email = ?");
                        $update_user->execute([$full_name, $email]);
                    }
                    
                } catch (PDOException $e) {
                    $errors[] = "Database error for $email: " . $e->getMessage();
                    continue;
                }
            }
        }
        
        fclose($file);
        
        return [
            'success_count' => $import_count,
            'errors' => $errors
        ];
    }
    
    public function assignStudentID($email, $student_id) {
        try {
            $stmt = $this->db->prepare("UPDATE student SET student_id = ? WHERE email = ?");
            $stmt->execute([$student_id, $email]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

function checkStudentHasAccount($email) {
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
        
        if ($result) {
            if ($result['user_id'] !== null) {
                return [
                    'has_account' => true,
                    'student_data' => [
                        'student_id' => $result['student_id'],
                        'first_name' => $result['first_name'],
                        'last_name' => $result['last_name'],
                        'email' => $result['email'],
                        'year_level' => $result['year_level'],
                        'course' => $result['course']
                    ],
                    'user_data' => [
                        'user_id' => $result['user_id'],
                        'fullname' => $result['user_fullname'],
                        'role' => $result['role'],
                        'is_verified' => $result['is_verified']
                    ]
                ];
            } else {
                return [
                    'has_account' => false,
                    'student_data' => [
                        'student_id' => $result['student_id'],
                        'first_name' => $result['first_name'],
                        'last_name' => $result['last_name'],
                        'email' => $result['email'],
                        'year_level' => $result['year_level'],
                        'course' => $result['course']
                    ],
                    'message' => 'Student exists but no user account found'
                ];
            }
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Database error in checkStudentHasAccount: " . $e->getMessage());
        return false;
    }
}

    /* ====================  ID FUNCTIONS  ==================== */

// public function updateIdStatus($studentId, $status);
// public function markReadyForPrinting($studentId);
// public function markPrinted($studentId);
// public function markReleased($studentId);
// public function requestIdReplacement($studentId);
// public function generateIdTemplate($studentId);   // returns HTML or PDF
// public function printId($studentId);              // triggers print generation
// public function batchPrintIds($studentIds = []);  // for bulk printing


//     /* ====================  USER FUNCTIONS  ==================== */
private function makeKldEmail(string $first, string $last): string
{
    // 1. remove unwanted characters
    $first = preg_replace('/[^a-z\s]/i', '', $first);
    $last  = preg_replace('/[^a-z]/i',  '', $last);

    $initials = '';
    foreach (explode(' ', trim($first)) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return strtolower($initials . $last) . '@kld.edu.ph';
}

public function addUser(array $data): bool
{
    // basic validation
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['role'])) {
        return false;
    }

    $email = $this->makeKldEmail($data['first_name'], $data['last_name']);
    $pwd   = password_hash($email, PASSWORD_DEFAULT);

    // 1. insert into users table
    $sql = "INSERT INTO users
            (full_name, email, password_hash, role, status, is_verified, created_at)
            VALUES (:name, :email, :pwd, :role, 'pending', 0, NOW())";
    $stmt = $this->db->prepare($sql);
    $ok = $stmt->execute([
        ':name'  => trim($data['first_name'] . ' ' . $data['last_name']),
        ':email' => $email,
        ':pwd'   => $pwd,
        ':role'  => $data['role']           // student OR admin
    ]);

    if (!$ok) {
        return false;
    }

    $userId = $this->db->lastInsertId();

    // 2. if student, also insert into students table
    if ($data['role'] === 'student') {
        $stu = $this->db->prepare(
            "INSERT INTO student (email, first_name, last_name)
             VALUES (:email, :fname, :lname)"
        );
        $stu->execute([
            ':email' => $email,
            ':fname' => $data['first_name'],
            ':lname' => $data['last_name']
        ]);
    }

    // 3. audit log
    $this->logAction('insert', $userId, 'user', [], [
        'full_name' => $data['first_name'] . ' ' . $data['last_name'],
        'email'     => $email,
        'role'      => $data['role']
    ]);

    return true;
}

public function getUsers(array $filters): array
{
    $w = []; $p = [];
    foreach ([
    'name'     => 'u.full_name',
    'email'    => 'u.email',
    'role'     => 'u.role',
    'status'   => 'u.status',
    'verified' => 'u.is_verified'
] as $key => $col) {
    if (($val = $filters[$key] ?? '') !== '') {
        if (in_array($key, ['name', 'email'], true)) {
            $w[] = "$col LIKE :$key";
            $p[":$key"] = "%$val%";
        } elseif ($key === 'verified') {
            $w[] = "$col = :$key";
            $p[":$key"] = (int)$val;
        } else {
            // exact match for the rest
            $w[] = "$col = :$key";
            $p[":$key"] = $val;
        }
    }
}
    $where = $w ? 'WHERE '.implode(' AND ', $w) : '';
    $sql = "SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.is_verified, u.created_at
            FROM users u
            $where
            ORDER BY u.user_id DESC
            LIMIT 1000";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($p);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getUserById(int $id): ?array
{
    $sql = "SELECT * FROM users WHERE user_id = :id LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

public function updateUser(int $id, array $data): bool
{
    $fields = [];
    $params = [':id'=>$id];
    $allowed = ['full_name','email','role','status','is_verified'];
    foreach ($data as $k=>$v) if (in_array($k,$allowed)) {
        $fields[] = "$k = :$k";
        $params[":$k"] = $k==='is_verified' ? (int)$v : $v;
    }
    if (!$fields) return false;
    $sql = "UPDATE users SET ".implode(', ',$fields)." WHERE user_id = :id";
    $ok = $this->db->prepare($sql)->execute($params);

    if (!$fields) return false;

    $sql = "UPDATE users SET ".implode(', ',$fields)." WHERE user_id = :id";
    $ok  = $this->db->prepare($sql)->execute($params);

    if ($ok) {
        $this->logAction('update', $id, 'user', $old, $data);
    }
    return $ok;
}

public function resetUserPassword(int $id, string $customPwd = ''): bool
{
    $pwd = $customPwd ? $customPwd : bin2hex(random_bytes(8)); // auto
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = :pwd WHERE user_id = :id";
    $ok = $this->db->prepare($sql)->execute([':pwd'=>$hash, ':id'=>$id]);
     if ($ok) {
        $this->logAction('reset_password', $id, 'user', [], ['password_reset'=>1]);
    }
    return $ok;
}

public function deleteUser(int $id): bool
{
    $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id = :id";
    $ok = $this->db->prepare($sql)->execute([':id'=>$id]);

     if ($ok) {
        $this->logAction('delete', $id, 'user', $old, []);
    }
    return $ok;
}

public function bulkDeleteUsers(array $ids): void
{
    if (!$ids) return;
    $in = str_repeat('?,', count($ids)-1) . '?';
    $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id IN ($in)";
    $this->db->prepare($sql)->execute($ids);
    $this->logAction('bulk_delete', 0, 'user', ['count'=>count($ids)], []);
}

public function bulkChangeRole(array $ids, string $newRole): void
{
    if (!$ids) return;
    $in = str_repeat('?,', count($ids)-1) . '?';
    $sql = "UPDATE users SET role = ? WHERE user_id IN ($in)";
    $this->db->prepare($sql)->execute(array_merge([$newRole], $ids));
    $this->logAction('bulk_role', 0, 'user', ['count'=>count($ids)], ['new_role'=>$newRole]);
}

public function bulkChangeStatus(array $ids, string $newStatus): void
{
    if (!$ids) return;
    $in = str_repeat('?,', count($ids)-1) . '?';
    $sql = "UPDATE users SET status = ? WHERE user_id IN ($in)";
    $this->db->prepare($sql)->execute(array_merge([$newStatus], $ids));
    $this->logAction('bulk_status', 0, 'user', ['count'=>count($ids)], ['new_status'=>$newStatus]);
}

public function bulkExportUsers(array $ids): void
{
    $where = $ids ? 'WHERE user_id IN ('.str_repeat('?,',count($ids)-1).'?)' : '';
    $sql   = "SELECT user_id, full_name, email, role, status, is_verified, created_at
              FROM users $where ORDER BY user_id DESC";
    $stmt  = $this->db->prepare($sql);
    $stmt->execute($ids);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_'.date('Y-m-d_His').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Email','Role','Status','Verified','Created']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['user_id'], $row['full_name'], $row['email'],
            $row['role'], $row['status'], $row['is_verified']?'Yes':'No',
            $row['created_at']
        ]);
    }
    fclose($out);
    exit;
}



//     /* ====================  ADMIN FUNCTIONS  ==================== */

private function logAction(string $action,
                           int    $targetId,
                           string $targetType = 'student',
                           array  $old = [],
                           array  $new = [])
{
    $sql = "INSERT INTO audit_log
              (admin_id, action, target_id, target_type,
               old_values, new_values, ip_address)
            VALUES
              (:admin, :action, :tid, :ttype,
               :old, :new, :ip)";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':admin' => $_SESSION['user_id']           ?? 0,
        ':action'=> $action,
        ':tid'   => $targetId,
        ':ttype' => $targetType,
        ':old'   => $old ? json_encode($old) : null,
        ':new'   => $new ? json_encode($new) : null,
        ':ip'    => $_SERVER['REMOTE_ADDR']        ?? null
    ]);
}

// ----------------------------- ID FUNCTIONS ==================== */
/* ---------- 1.  all pending requests with student names ---------- */
    public function getAllIdRequests(): array
    {
        $db = $this->getDb();
        $sql = "SELECT r.*, s.first_name, s.last_name, s.student_id
                FROM id_requests r
                JOIN student s ON s.id = r.student_id
                ORDER BY r.created_at DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- 2.  all issued ids with names ---------- */
    public function getAllIssuedIds(): array
    {
        $db = $this->getDb();
        $sql = "SELECT i.*, s.first_name, s.last_name
                FROM issued_ids i
                JOIN student s ON s.id = i.user_id
                ORDER BY i.issue_date DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- 3.  generate unique id & move request to issued ---------- */
    public function generateStudentId(int $requestId): void
    {
        $db = $this->getDb();

        /* lock request row */
        $req = $db->prepare("SELECT * FROM id_requests WHERE id = ? FOR UPDATE");
        $req->execute([$requestId]);
        $row = $req->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'pending') return;

        /* build next id number (simple incremental) */
        $last = $db->query("SELECT id_number FROM issued_ids ORDER BY id DESC LIMIT 1")->fetchColumn();
        $next = $last ? (intval(substr($last, -6)) + 1) : 100000;   // 6-digit suffix
        $idNumber = date('Y').str_pad($next, 6, '0', STR_PAD_LEFT); // e.g. 2025001001

        $expiry = date('Y-m-d', strtotime('+4 years'));

        /* create issued row */
        $ins = $db->prepare("INSERT INTO issued_ids
                            (user_id, id_number, issue_date, expiry_date, status)
                            VALUES (?, ?, NOW(), ?, 'pending')");
        $ins->execute([$row['student_id'], $idNumber, $expiry]);

        /* mark request approved */
        $up = $db->prepare("UPDATE id_requests
                            SET status = 'approved', updated_at = NOW()
                            WHERE id = ?");
        $up->execute([$requestId]);
    }

    /* ---------- 4.  status helpers ---------- */
    public function markIdPrinted(int $issuedId): void
    {
        $this->getDb()->prepare("UPDATE issued_ids SET status='printed' WHERE id=?")
                      ->execute([$issuedId]);
    }
    public function markIdDelivered(int $issuedId): void
    {
        $this->getDb()->prepare("UPDATE issued_ids SET status='delivered' WHERE id=?")
                      ->execute([$issuedId]);
    }

    /* filter requests */
public function getRequestsByStatus(string $status): array
{
    $db = $this->getDb();
    $sql = "SELECT r.*, s.first_name, s.last_name
            FROM id_requests r
            JOIN student s ON s.id = r.student_id
            WHERE r.status = ?
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* filter issued cards */
public function getIssuedByStatus(string $filter): array
{
    $db = $this->getDb();
    $map = ['generated'=>'generated','completed'=>'delivered'];
    $sql = "SELECT i.*, s.first_name, s.last_name
            FROM issued_ids i
            JOIN student s ON s.id = i.user_id
            WHERE i.status = ?
            ORDER BY i.issue_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$map[$filter]]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* approve / reject */
public function setRequestStatus(int $requestId, string $newStatus): void
{
    $db = $this->getDb();
    $sql = "UPDATE id_requests
            SET status = ?, updated_at = NOW()
            WHERE id = ? AND status = 'pending'";
    $db->prepare($sql)->execute([$newStatus, $requestId]);
}

public function generateId(int $requestId): void
{
    $db = $this->getDb();

    /* 1.  pull request + student */
    $stmt = $db->prepare("SELECT r.student_id, s.email, s.first_name, s.last_name,
                                 s.course, s.year_level, s.photo, s.signature,
                                 s.emergency_contact, s.blood_type
                          FROM   id_requests r
                          JOIN   student      s ON s.id = r.student_id
                          WHERE  r.id = ? AND r.status = 'approved'");
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return;

    $studentId = $row['student_id'];

    /* 2.  next id number */
    $last = $db->query("SELECT id_number FROM issued_ids ORDER BY id_number DESC LIMIT 1")->fetchColumn();
    $next = $last ? (intval(substr($last, -6)) + 1) : 100000;
    $idNumber = date('Y').str_pad($next, 6, '0', STR_PAD_LEFT);
    $expiry   = date('Y-m-d', strtotime('+4 years'));

    /* 3. QR code ------------------------------------------------------------- */
$verifyUrl = APP_URL.'/verify_id.php?n='.$idNumber;

$qrName = $idNumber.'.png';          // file name we will store & reference
$qrPath = __DIR__.'/../uploads/qr/'.$qrName;

/* -------  Endroid QrCode  ------- */
$writer = new \Endroid\QrCode\Writer\PngWriter();

$qrCode = new \Endroid\QrCode\QrCode(
    data: $verifyUrl,                                        // verification link
    encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
    size: 300,
    margin: 10,
    foregroundColor: new \Endroid\QrCode\Color\Color(0, 0, 0),
    backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255)
);

// optional logo – comment out if you don’t want it
$logo = new \Endroid\QrCode\Logo\Logo(
    path: __DIR__.'/../assets/images/kldlogo.png',
    resizeToWidth: 50,
    punchoutBackground: true
);

$result = $writer->write($qrCode, $logo);   // label omitted – add if desired
$result->saveToFile($qrPath);               // physical file for local storage
/* -------------------------------- */


    /* 4.  PDF (front + back with QR) */
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $front = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:0 auto;text-align:center;">
        <h3>SCHOOL NAME</h3>
        <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;"><br>
        <b>'.$row['first_name'].' '.$row['last_name'].'</b><br>
        '.$row['course'].' - '.$row['year_level'].'<br>
        ID: '.$idNumber.'<br>
        Emergency: '.$row['emergency_contact'].'<br>
        Blood: '.$row['blood_type'].'
    </div>';

    $back = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:30px auto;text-align:center;">
        <p style="margin-top:10px;">If found please return to school registrar.</p>
        <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:80px;"><br>
        <small>Signature</small><br>
        <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:120px;">
    </div>';

    $dompdf->loadHtml($front.$back);
    $dompdf->setPaper('CR80', 'landscape');
    $dompdf->render();

    /* 5.  save PDF */
    $fileName = $row['email'].'_'.date('YmdHis').'.pdf';
    $filePath = __DIR__.'/../uploads/digital_id/'.$fileName;
    if (!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);
    file_put_contents($filePath, $dompdf->output());

    /* 6.  insert row */
    $ins = $db->prepare("INSERT INTO issued_ids
                        (user_id, id_number, issue_date, expiry_date, status, digital_id_file)
                        VALUES (?, ?, NOW(), ?, 'generated', ?)");
    $ins->execute([$studentId, $idNumber, $expiry, $fileName]);

    /* 7.  close request */
    $db->prepare("UPDATE id_requests SET status='generated', updated_at=NOW() WHERE id=?")
       ->execute([$requestId]);
}

public function regenerateId(int $issuedId): void
{
    $db = $this->getDb();

    /* 1.  pull row (correct PK) */
    $stmt = $db->prepare("SELECT i.id_number, i.digital_id_file,
                                 s.email, s.first_name, s.last_name,
                                 s.course, s.year_level, s.photo, s.signature,
                                 s.emergency_contact, s.blood_type
                          FROM issued_ids i
                          JOIN student s ON s.id = i.user_id
                          WHERE i.id_number = ? AND i.status = 'generated'");
    $stmt->execute([$issuedId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return;

    $idNumber = $row['id_number'];

    /* 2.  delete old files */
    if ($row['digital_id_file'] && file_exists(__DIR__.'/../uploads/digital_id/'.$row['digital_id_file'])) {
        unlink(__DIR__.'/../uploads/digital_id/'.$row['digital_id_file']);
    }
    if (file_exists(__DIR__.'/../uploads/qr/qr_'.$idNumber.'.png')) {
        unlink(__DIR__.'/../uploads/qr/qr_'.$idNumber.'.png');
    }

  /* 3. QR code */
$verifyUrl = APP_URL.'/verify_id.php?n='.$idNumber;

// 1. Define the Configuration
$config = QrCodeConfig::create()
    ->size(80)        
    ->margin(0)       
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
    ->build();

// 2. GENERATE the QR Code Image Data
$result = Builder::create() 
    ->config($config) 
    ->writer(new PngWriter())
    ->data($verifyUrl)
    ->build(); 

// 3. FILE SAVING LOGIC
$qrName = 'qr_'.$idNumber.'.png';
$qrPath = __DIR__.'/../uploads/qr/'.$qrName;

// Create the directory if it doesn't exist
if (!is_dir(dirname($qrPath))) {
    mkdir(dirname($qrPath), 0755, true);
}

// Save the file.
$result->saveToFile($qrPath);


    /* 4.  rebuild PDF (identical HTML) */
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $front = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:0 auto;text-align:center;">
        <h3>SCHOOL NAME</h3>
        <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;"><br>
        <b>'.$row['first_name'].' '.$row['last_name'].'</b><br>
        '.$row['course'].' - '.$row['year_level'].'<br>
        ID: '.$idNumber.'<br>
        Emergency: '.$row['emergency_contact'].'<br>
        Blood: '.$row['blood_type'].'
    </div>';

    $back = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:30px auto;text-align:center;">
        <p style="margin-top:10px;">If found please return to school registrar.</p>
        <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:80px;"><br>
        <small>Signature</small><br>
        <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:120px;">
    </div>';

    $dompdf->loadHtml($front.$back);
    $dompdf->setPaper('CR80', 'landscape');
    $dompdf->render();

    /* 5.  save new PDF */
    $fileName = $row['email'].'_'.date('YmdHis').'.pdf';
    $filePath = __DIR__.'/../uploads/digital_id/'.$fileName;
    file_put_contents($filePath, $dompdf->output());

    /* 6.  update row */
    $db->prepare("UPDATE issued_ids
                  SET digital_id_file = ?, issue_date = NOW()
                  WHERE id_number = ?")
       ->execute([$fileName, $issuedId]);
}

}