<?php
require_once __DIR__ . '/../../includes/User.php';
require_once __DIR__ . '/AuditLogger.php';

class UserManager extends User
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

    private function makeKldEmail(string $first, string $last): string
    {
        // Remove unwanted characters
        $first = preg_replace('/[^a-z\s]/i', '', $first);
        $last = preg_replace('/[^a-z]/i', '', $last);
        
        // Extract initials from first name parts
        $nameParts = explode(' ', trim($first));
        $initials = '';
        
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }
        
        return strtolower($initials . $last) . '@kld.edu.ph';
    }

    public function addUser(array $data): bool
    {
        // Basic validation
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['role'])) {
            return false;
        }
        
        $email = $this->makeKldEmail($data['first_name'], $data['last_name']);
        $passwordHash = password_hash($email, PASSWORD_DEFAULT);
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        
        // Insert into users table
        $sql = "INSERT INTO users 
                (full_name, email, password_hash, role, status, is_verified, created_at)
                VALUES (:name, :email, :password, :role, 'pending', 0, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':name' => $fullName,
            ':email' => $email,
            ':password' => $passwordHash,
            ':role' => $data['role']
        ]);
        
        if (!$success) {
            return false;
        }
        
        $userId = $this->db->lastInsertId();
        
        // If student, also insert into students table
        if ($data['role'] === 'student') {
            $stmt = $this->db->prepare(
                "INSERT INTO student (email, first_name, last_name)
                 VALUES (:email, :first_name, :last_name)"
            );
            $stmt->execute([
                ':email' => $email,
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name']
            ]);
        }
        
        // Audit log
        $this->auditLogger->logAction(
            'insert',
            $userId,
            'user',
            [],
            [
                'full_name' => $fullName,
                'email' => $email,
                'role' => $data['role']
            ]
        );
        
        return true;
    }

    public function getUsers(array $filters, int $page = 1, int $perPage = 50): array
{
    $whereConditions = [];
    $parameters = [];
    
    $filterConfig = [
        'name'      => ['column' => 'u.full_name', 'type' => 'like'],
        'email'     => ['column' => 'u.email', 'type' => 'like'],
        'role'      => ['column' => 'u.role', 'type' => 'exact'],
        'status'    => ['column' => 'u.status', 'type' => 'exact'],
        'verified'  => ['column' => 'u.is_verified', 'type' => 'boolean']
    ];
    
    foreach ($filterConfig as $key => $config) {
        $value = $filters[$key] ?? '';
        if ($value === '') {
            continue;
        }
        
        $column = $config['column'];
        
        switch ($config['type']) {
            case 'like':
                $whereConditions[] = "$column LIKE :$key";
                $parameters[":$key"] = "%$value%";
                break;
            case 'boolean':
                $whereConditions[] = "$column = :$key";
                $parameters[":$key"] = (int)$value;
                break;
            case 'exact':
            default:
                $whereConditions[] = "$column = :$key";
                $parameters[":$key"] = $value;
                break;
        }
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Calculate offset for pagination
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.is_verified, u.created_at
            FROM users u
            $whereClause
            ORDER BY u.user_id DESC
            LIMIT :offset, :per_page";
    
    $stmt = $this->db->prepare($sql);
    
    // Bind pagination parameters
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
    
    // Bind filter parameters
    foreach ($parameters as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countUsers(array $filters = []): int
{
    $whereConditions = [];
    $parameters = [];
    
    $filterConfig = [
        'name'      => ['column' => 'u.full_name', 'type' => 'like'],
        'email'     => ['column' => 'u.email', 'type' => 'like'],
        'role'      => ['column' => 'u.role', 'type' => 'exact'],
        'status'    => ['column' => 'u.status', 'type' => 'exact'],
        'verified'  => ['column' => 'u.is_verified', 'type' => 'boolean']
    ];
    
    foreach ($filterConfig as $key => $config) {
        $value = $filters[$key] ?? '';
        if ($value === '') {
            continue;
        }
        
        $column = $config['column'];
        
        switch ($config['type']) {
            case 'like':
                $whereConditions[] = "$column LIKE :$key";
                $parameters[":$key"] = "%$value%";
                break;
            case 'boolean':
                $whereConditions[] = "$column = :$key";
                $parameters[":$key"] = (int)$value;
                break;
            case 'exact':
            default:
                $whereConditions[] = "$column = :$key";
                $parameters[":$key"] = $value;
                break;
        }
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT COUNT(*) as total
            FROM users u
            $whereClause";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($parameters);
    
    return (int) $stmt->fetchColumn();
}
    public function getUserById(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateUser(int $id, array $data): bool
    {
        // Get old data for audit log
        $oldData = $this->getUserById($id);
        if (!$oldData) {
            return false;
        }
        
        $allowedFields = ['full_name', 'email', 'role', 'status', 'is_verified'];
        $fields = [];
        $parameters = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                continue;
            }
            
            $fields[] = "$key = :$key";
            $parameters[":$key"] = ($key === 'is_verified') ? (int)$value : $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($parameters);
        
        if ($ok) {
            $newData = array_intersect_key($data, array_flip($allowedFields));
            $this->auditLogger->logAction('update', $id, 'user', $oldData, $newData);
        }
        
        return $ok;
    }

    public function resetUserPassword(int $id, string $customPassword = ''): bool
    {
        $oldData = $this->getUserById($id);
        if (!$oldData) {
            return false;
        }
        
        $password = $customPassword ?: bin2hex(random_bytes(8));
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password_hash = :password WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            ':password' => $passwordHash,
            ':id' => $id
        ]);
        
        if ($ok) {
            $this->auditLogger->logAction(
                'reset_password', 
                $id, 
                'user', 
                $oldData, 
                ['password_reset' => true]
            );
        }
        
        return $ok;
    }

    public function deleteUser(int $id): bool
    {
        // Get old data for audit log
        $oldData = $this->getUserById($id);
        if (!$oldData) {
            return false;
        }
        
        $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id]);
        
        if ($ok) {
            $this->auditLogger->logAction('delete', $id, 'user', $oldData, []);
        }
        
        return $ok;
    }

    public function bulkUserAction(string $action, array $userIds, array $postData): array
    {
        if (empty($userIds)) {
            return ['success' => false, 'message' => 'No users selected'];
        }

        try {
            switch ($action) {
                case 'delete':
                    $success = $this->bulkDeleteUsers($userIds);
                    $message = $success 
                        ? 'Successfully deleted ' . count($userIds) . ' user(s)' 
                        : 'Failed to delete users';
                    break;

                case 'change_role':
                    if (empty($postData['bulk_role'])) {
                        return ['success' => false, 'message' => 'No role specified'];
                    }
                    $success = $this->bulkChangeRole($userIds, $postData['bulk_role']);
                    $message = $success 
                        ? 'Successfully changed role for ' . count($userIds) . ' user(s)' 
                        : 'Failed to change role';
                    break;

                case 'change_status':
                    if (empty($postData['bulk_status'])) {
                        return ['success' => false, 'message' => 'No status specified'];
                    }
                    $success = $this->bulkChangeStatus($userIds, $postData['bulk_status']);
                    $message = $success 
                        ? 'Successfully changed status for ' . count($userIds) . ' user(s)' 
                        : 'Failed to change status';
                    break;

                case 'export':
                    $data = $this->bulkExportUsers($userIds);
                    if (!empty($data)) {
                        // Generate CSV
                        $filename = 'users_export_' . date('YmdHis') . '.csv';
                        $filepath = __DIR__ . '/../../uploads/exports/' . $filename;
                        
                        if (!is_dir(dirname($filepath))) {
                            mkdir(dirname($filepath), 0755, true);
                        }
                        
                        $fp = fopen($filepath, 'w');
                        if ($fp) {
                            // Write headers
                            fputcsv($fp, ['User ID', 'Full Name', 'Email', 'Role', 'Status', 'Verified', 'Created At']);
                            
                            // Write data
                            foreach ($data as $row) {
                                fputcsv($fp, [
                                    $row['user_id'],
                                    $row['full_name'],
                                    $row['email'],
                                    $row['role'],
                                    $row['status'],
                                    $row['verified'] ? 'Yes' : 'No',
                                    $row['created_at']
                                ]);
                            }
                            fclose($fp);
                            
                            // Trigger download
                            header('Content-Type: text/csv');
                            header('Content-Disposition: attachment; filename="' . $filename . '"');
                            header('Content-Length: ' . filesize($filepath));
                            readfile($filepath);
                            unlink($filepath);
                            exit;
                        }
                    }
                    return ['success' => false, 'message' => 'Export failed'];

                default:
                    return ['success' => false, 'message' => 'Invalid action'];
            }

            return ['success' => $success, 'message' => $message];

        } catch (Exception $e) {
            error_log("Bulk user action error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function bulkDeleteUsers(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($ids);
        
        if ($ok) {
            $this->auditLogger->logAction(
                'bulk_delete', 
                0, 
                'user', 
                ['count' => count($ids), 'user_ids' => $ids], 
                []
            );
        }
        
        return $ok;
    }

    public function bulkChangeRole(array $ids, string $newRole): bool
    {
        if (empty($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE users SET role = ? WHERE user_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute(array_merge([$newRole], $ids));
        
        if ($ok) {
            $this->auditLogger->logAction(
                'bulk_role_change', 
                0, 
                'user', 
                ['count' => count($ids), 'user_ids' => $ids], 
                ['new_role' => $newRole]
            );
        }
        
        return $ok;
    }

    public function bulkChangeStatus(array $ids, string $newStatus): bool
    {
        if (empty($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE users SET status = ? WHERE user_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute(array_merge([$newStatus], $ids));
        
        if ($ok) {
            $this->auditLogger->logAction(
                'bulk_status_change', 
                0, 
                'user', 
                ['count' => count($ids), 'user_ids' => $ids], 
                ['new_status' => $newStatus]
            );
        }
        
        return $ok;
    }

    public function bulkExportUsers(array $ids): array
    {
        try {
            $whereClause = '';
            $parameters = [];
            
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $whereClause = "WHERE user_id IN ($placeholders)";
                $parameters = $ids;
            }
            
            $sql = "SELECT user_id, full_name, email, role, status, is_verified, created_at
                    FROM users $whereClause ORDER BY user_id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($parameters);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                return [
                    'success' => false,
                    'message' => 'No users found to export'
                ];
            }
            
            // Log the export action
            $this->auditLogger->logAction(
                'export',
                0,
                'user',
                ['count' => count($ids), 'user_ids' => $ids],
                []
            );
            
            $this->outputUserCSV($users);
            exit;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ];
        }
    }

    /* ====================  ARCHIVE FUNCTIONS  ==================== */

    public function getArchivedUsers(): array
    {
        $sql = "SELECT * FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function restoreUser(int $userId): bool
    {
        $sql = "UPDATE users SET deleted_at = NULL WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $userId]);
        
        if ($ok) {
            $user = $this->getUserById($userId);
            $this->auditLogger->logAction('restore', $userId, 'user', [], $user ?: []);
        }
        
        return $ok;
    }

    public function permanentlyDeleteUser(int $userId): bool
    {
        try {
            // Get user data before deletion for audit log
            $sql = "SELECT * FROM users WHERE user_id = :id AND deleted_at IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // Only allow permanent deletion of already soft-deleted users
            }
            
            // Permanently delete from database
            $sql = "DELETE FROM users WHERE user_id = :id AND deleted_at IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([':id' => $userId]);
            
            if ($ok) {
                $this->auditLogger->logAction('permanent_delete', $userId, 'user', $user, []);
            }
            
            return $ok;
            
        } catch (PDOException $e) {
            error_log("Error permanently deleting user: " . $e->getMessage());
            return false;
        }
    }

    public function bulkRestoreUsers(array $userIds): bool
    {
        if (empty($userIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "UPDATE users SET deleted_at = NULL WHERE user_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($userIds);
    }

    public function bulkPermanentlyDeleteUsers(array $userIds): bool
    {
        if (empty($userIds)) {
            return false;
        }

        try {
            // Permanently delete from database
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "DELETE FROM users WHERE user_id IN ($placeholders) AND deleted_at IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($userIds);
            
        } catch (PDOException $e) {
            error_log("Error bulk permanently deleting users: " . $e->getMessage());
            return false;
        }
    }

    /* ====================  PRIVATE HELPER METHODS  ==================== */

    private function outputUserCSV(array $users): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        
        $headers = [
            'User ID', 'Full Name', 'Email', 'Role', 'Status', 
            'Is Verified', 'Created At'
        ];
        
        fputcsv($output, $headers);
        
        foreach ($users as $user) {
            $row = [
                $user['user_id'],
                $user['full_name'],
                $user['email'],
                $user['role'],
                $user['status'],
                $user['is_verified'] ? 'Yes' : 'No',
                $user['created_at']
            ];
            
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
}