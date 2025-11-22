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
     * Update a student's profile row.
     * Returns true on success, false on failure.
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

/* single-call file saver */
public function saveUploadedFile(array $file, string $subFolder): string
{
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allow, true)) throw new RuntimeException('Invalid file type.');

    $dir  = __DIR__."/../uploads/$subFolder/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = uniqid().'_'.time().'.'.$ext;
    $path = $dir.$name;

    if (!move_uploaded_file($file['tmp_name'], $path))
        throw new RuntimeException('Failed to move file.');

    return $name;   // store only the filename
}

    /* generic update – accepts associative array of columns */
    public function updateStudent(int $studentId, array $data): void
    {
        if (!$data) return;

        $db = $this->getDb();

        // List of real, allowed columns in your `student` table
        $allowed = [
            'first_name',
            'last_name',
            'middle_name',
            'year_level',
            'course',
            'contact_number',
            'address',
            'photo',
            'updated_at'
        ];

        // Filter invalid columns
        $clean = [];
        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $clean[$col] = $val;
            }
        }

        if (!$clean) return; // nothing valid to update

        // Build SET clause
        $set = [];
        foreach ($clean as $col => $val) {
            $set[] = "$col = :$col";
        }

        $sql = "UPDATE student SET " . implode(', ', $set) . " WHERE id = :id";

        $stmt = $db->prepare($sql);

        foreach ($clean as $col => $val) {
            $stmt->bindValue(":$col", $val);
        }

        $stmt->bindValue(":id", $studentId, PDO::PARAM_INT);
        $stmt->execute();
    }
}