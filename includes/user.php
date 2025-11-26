<?php
require_once 'db.php';

class User
{
    protected ?\PDO $db;

    public function __construct()
    {
        $db = (new Database())->getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $this->db = $db;
    }

    public function getDb(): PDO {
        return $this->db;
    }

    /* -------------------- register -------------------- */
    public function create(string $email, string $plainPassword, string $role = 'student'): bool
    {
        // 1.  Duplicate check (case-insensitive)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:e) AND deleted_at IS NULL"
        );
        $stmt->execute([':e' => $email]);
        if ($stmt->fetchColumn() > 0) {
            return false;          // caller can show “E-mail already taken”
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql  = "INSERT INTO users (email, password_hash, role, full_name, is_verified, verified)
                 VALUES (:e, :p, :r, :n, 0, 0)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':e' => $email,
            ':p' => $hash,
            ':r' => $role,
            ':n' => $_POST['full_name'] ?? ''
        ]);
    }

    /* -------------------- login -------------------- */
    public function findByEmail(string $email, bool $mustBeVerified = false): ?array
    {
        $sql = "SELECT * FROM users
                WHERE email = :e
                  AND deleted_at IS NULL";
        if ($mustBeVerified) {
            $sql .= " AND is_verified = 1";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findStudentbyEmail(string $email): ?array
    {
        $sql = "SELECT * FROM student
                WHERE email = :e
                  AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* -------------------- verification -------------------- */
    public function markEmailAsVerified(string $email): bool
    {
        $sql = "UPDATE users
                SET is_verified = 1, verified = 1
                WHERE email = :e";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':e' => $email]);
    }
    
}