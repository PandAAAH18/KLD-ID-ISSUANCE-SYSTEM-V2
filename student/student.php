<?php
require_once '../includes/User.php';

class Student extends User
{
    /**
     * Get database connection
     */
    public function getDb(): PDO {
        return $this->db;
    }

    /**
     * Validate student data before update
     * Returns array of errors if validation fails, empty array if valid
     */
    public function validateStudentData(array $data): array {
        $errors = [];

        if (isset($data['first_name'])) {
            $fn = trim($data['first_name']);
            if (empty($fn)) {
                $errors['first_name'] = 'First name is required';
            } elseif (strlen($fn) < 2) {
                $errors['first_name'] = 'First name must be at least 2 characters';
            } elseif (strlen($fn) > 50) {
                $errors['first_name'] = 'First name must not exceed 50 characters';
            }
        }

        if (isset($data['last_name'])) {
            $ln = trim($data['last_name']);
            if (empty($ln)) {
                $errors['last_name'] = 'Last name is required';
            } elseif (strlen($ln) < 2) {
                $errors['last_name'] = 'Last name must be at least 2 characters';
            } elseif (strlen($ln) > 50) {
                $errors['last_name'] = 'Last name must not exceed 50 characters';
            }
        }

        if (isset($data['contact_number'])) {
            $cn = trim($data['contact_number']);
            if (empty($cn)) {
                $errors['contact_number'] = 'Contact number is required';
            } elseif (!preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $cn)) {
                $errors['contact_number'] = 'Contact number must be valid (at least 10 digits)';
            }
        }

        if (isset($data['course'])) {
            if (empty(trim($data['course']))) {
                $errors['course'] = 'Course is required';
            }
        }

        if (isset($data['year_level'])) {
            if (empty(trim($data['year_level']))) {
                $errors['year_level'] = 'Year level is required';
            }
        }

        if (isset($data['gender'])) {
            $gender = trim($data['gender']);
            if (!empty($gender) && !in_array($gender, ['Male', 'Female'], true)) {
                $errors['gender'] = 'Invalid gender selection';
            }
        }

        if (isset($data['blood_type'])) {
            $bt = trim($data['blood_type']);
            if (!empty($bt)) {
                $valid = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                if (!in_array($bt, $valid, true)) {
                    $errors['blood_type'] = 'Invalid blood type';
                }
            }
        }

        if (isset($data['dob'])) {
            $dob = trim($data['dob']);
            if (!empty($dob)) {
                $dobTime = strtotime($dob);
                if ($dobTime === false) {
                    $errors['dob'] = 'Invalid date format';
                } elseif ($dobTime > time()) {
                    $errors['dob'] = 'Date of birth cannot be in the future';
                }
            }
        }

        if (isset($data['address'])) {
            $addr = trim($data['address']);
            if (!empty($addr) && strlen($addr) > 500) {
                $errors['address'] = 'Address must not exceed 500 characters';
            }
        }

        if (isset($data['emergency_contact'])) {
            $ec = trim($data['emergency_contact']);
            if (!empty($ec) && !preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $ec)) {
                $errors['emergency_contact'] = 'Emergency contact must be a valid phone number';
            }
        }

        return $errors;
    }

    /**
     * Update a student's profile row.
     * Returns array with success status and message
     */

    // ---------------------------------------- COMPLETE PROFILE UPDATE WITH OPTIONAL PHOTO UPLOAD ----------------------------------------
public function updateStudentProfile(
    int    $studentId,
    string $firstName,
    string $lastName,
    string $yearLevel,
    string $course,
    string $contactNumber,
    string $address,
    ?array $file = null   // $_FILES['photo']  (optional)
): bool {
    try {
        $this->db->beginTransaction();

        // basic data
        $sql = 'UPDATE student
                SET first_name    = :fn,
                    last_name     = :ln,
                    year_level    = :yl,
                    course        = :co,
                    contact_number= :cn,
                    address       = :ad,
                    updated_at    = NOW()
                WHERE id = :id
                  AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':yl' => $yearLevel,
            ':co' => $course,
            ':cn' => $contactNumber,
            ':ad' => $address,
            ':id' => $studentId
        ]);

        // optional photo upload
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/gif'];
            if (!in_array($file['type'], $allowed, true)) {
                throw new RuntimeException('Only JPG/PNG/GIF images allowed.');
            }

            $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $name  = "student_{$studentId}." . $ext;
            $dest  = __DIR__ . '/../uploads/student/' . $name;

            if (!is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt = $this->db->prepare(
                    'UPDATE student SET photo = :p WHERE id = :id'
                );
                $stmt->execute([':p' => $name, ':id' => $studentId]);
            }
        }

        $this->db->commit();
        return true;
    } catch (Throwable $e) {
        $this->db->rollBack();
        error_log(__METHOD__ . ' : ' . $e->getMessage());
        return false;
    }
}

// ---------------------------------------- MY ID FUNCTIONS ----------------------------------------

/* -------- 1. fetch student row by id ------- */
public function findById(int $student_id): ?array {
    $db = $this->getDb();          // inherited from User
    $stmt = $db->prepare("SELECT * FROM student WHERE id = ? LIMIT 1");
    $stmt->execute([$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/* -------- 2. insert id request ------------- */
public function insertIdRequest(int $student_id, string $type, string $reason): void {
    $db = $this->getDb();
    $sql = "INSERT INTO id_requests 
            (student_id, request_type, reason, status, created_at) 
            VALUES 
            (:sid, :type, :reason, 'pending', NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':sid'   => $student_id,
        ':type'  => $type,
        ':reason'=> $reason
    ]);
}

/* -------- 2a. get latest id request status ------------- */
public function getLatestIdRequest(int $student_id): ?array {
    $db = $this->getDb();
    $sql = "SELECT * FROM id_requests 
            WHERE student_id = :sid 
            ORDER BY created_at DESC 
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/* -------- 2b. get all id request history ------------- */
public function getIdRequestHistory(int $student_id): array {
    $db = $this->getDb();
    $sql = "SELECT * FROM id_requests 
            WHERE student_id = :sid 
            ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* -------- 2c. get issued ID with digital file ------------- */
public function getIssuedId(int $student_id): ?array {
    $db = $this->getDb();
    $sql = "SELECT * FROM issued_ids 
            WHERE user_id = :sid 
            ORDER BY issue_date DESC 
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/* single-call file saver */
public function saveUploadedFile(array $file, string $subFolder): string
{
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allow, true)) throw new RuntimeException('Invalid file type.');

    $dir  = __DIR__."/../uploads/$subFolder/";
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new RuntimeException('Failed to create upload directory: ' . $dir);
        }
    }
    
    // Check if directory is writable
    if (!is_writable($dir)) {
        throw new RuntimeException('Upload directory is not writable: ' . $dir);
    }

    $name = uniqid().'_'.time().'.'.$ext;
    $path = $dir.$name;
    
    // Debug logging
    error_log("Attempting to move file from: {$file['tmp_name']} to: $path");
    error_log("File exists in tmp: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
    error_log("Directory writable: " . (is_writable($dir) ? 'YES' : 'NO'));

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        $error = error_get_last();
        throw new RuntimeException('Failed to move file. Error: ' . ($error['message'] ?? 'Unknown error'));
    }
    
    // Verify file was actually created
    if (!file_exists($path)) {
        throw new RuntimeException('File upload completed but file not found at destination.');
    }

    return $name;   // store only the filename
}

    /* generic update – accepts associative array of columns */
    public function updateStudent(int $studentId, array $data): array
    {
        if (!$data) {
            return [
                'success' => false,
                'message' => 'No data provided for update',
                'errors' => []
            ];
        }

        // Validate data
        $validationErrors = $this->validateStudentData($data);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ];
        }

        try {
            $db = $this->getDb();
            $db->beginTransaction();

            // List of real, allowed columns in your `student` table
            $allowed = [
                'first_name',
                'last_name',
                'year_level',
                'course',
                'contact_number',
                'address',
                'photo',
                'dob',
                'gender',
                'blood_type',
                'emergency_contact_name',
                'emergency_contact',
                'cor',
                'signature'
            ];

            // Filter invalid columns and trim values
            $clean = [];
            foreach ($data as $col => $val) {
                if (in_array($col, $allowed, true)) {
                    // Trim string values but keep nulls and special types
                    if (is_string($val)) {
                        $val = trim($val);
                        // Don't store empty strings, convert to null instead
                        if ($val === '') {
                            $val = null;
                        }
                    }
                    $clean[$col] = $val;
                }
            }

            if (!$clean) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No valid data fields to update',
                    'errors' => []
                ];
            }

            // Build SET clause
            $set = [];
            foreach ($clean as $col => $val) {
                $set[] = "$col = :$col";
            }

            $sql = "UPDATE student SET " . implode(', ', $set) . " WHERE id = :id AND deleted_at IS NULL";

            $stmt = $db->prepare($sql);

            foreach ($clean as $col => $val) {
                $stmt->bindValue(":$col", $val);
            }

            $stmt->bindValue(":id", $studentId, PDO::PARAM_INT);
            $stmt->execute();

            $db->commit();

            return [
                'success' => true,
                'message' => 'Student profile updated successfully',
                'errors' => []
            ];
        } catch (Throwable $e) {
            $db->rollBack();
            error_log(__METHOD__ . ' : ' . $e->getMessage());
            error_log(__METHOD__ . ' Stack: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Failed to update student profile: ' . $e->getMessage(),
                'errors' => ['database' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get student by ID with optional select fields
     */
    public function findByIdWith(int $student_id, array $fields = []): ?array {
        $db = $this->getDb();
        $selectFields = empty($fields) ? '*' : implode(', ', $fields);
        $stmt = $db->prepare("SELECT $selectFields FROM student WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$student_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get multiple students with filtering and pagination
     */
    public function getStudents(int $limit = 50, int $offset = 0, array $filters = []): array {
        $db = $this->getDb();
        $where = ['deleted_at IS NULL'];

        if (isset($filters['course']) && !empty($filters['course'])) {
            $where[] = 'course = :course';
        }
        if (isset($filters['year_level']) && !empty($filters['year_level'])) {
            $where[] = 'year_level = :year_level';
        }
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = "(CONCAT(first_name, ' ', last_name) LIKE :search OR email LIKE :search)";
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM student WHERE $whereClause LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if (isset($filters['course'])) {
            $stmt->bindValue(':course', $filters['course']);
        }
        if (isset($filters['year_level'])) {
            $stmt->bindValue(':year_level', $filters['year_level']);
        }
        if (isset($filters['search'])) {
            $stmt->bindValue(':search', '%' . $filters['search'] . '%');
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Count total students with optional filters
     */
    public function countStudents(array $filters = []): int {
        $db = $this->getDb();
        $where = ['deleted_at IS NULL'];

        if (isset($filters['course']) && !empty($filters['course'])) {
            $where[] = 'course = :course';
        }
        if (isset($filters['year_level']) && !empty($filters['year_level'])) {
            $where[] = 'year_level = :year_level';
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as count FROM student WHERE $whereClause";

        $stmt = $db->prepare($sql);

        if (isset($filters['course'])) {
            $stmt->bindValue(':course', $filters['course']);
        }
        if (isset($filters['year_level'])) {
            $stmt->bindValue(':year_level', $filters['year_level']);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Validate file before upload
     */
    public function validateFile(array $file, array $options = []): array {
        $errors = [];
        $maxSize = $options['max_size'] ?? 5242880; // 5MB default
        $allowedMimes = $options['mime_types'] ?? ['image/jpeg', 'image/png'];
        $allowedExts = $options['extensions'] ?? ['jpg', 'jpeg', 'png'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file selected',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
            ];
            $errors['upload'] = $uploadErrors[$file['error']] ?? 'Unknown upload error';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($file['size'] > $maxSize) {
            $errors['size'] = 'File size exceeds maximum allowed (' . ($maxSize / 1048576) . 'MB)';
        }

        if (!in_array($file['type'], $allowedMimes, true)) {
            $errors['mime'] = 'File type not allowed';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $errors['extension'] = 'File extension not allowed';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}