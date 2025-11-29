<?php
class AuditLogger
{
    private $db;
    
    public function __construct($db)
    {
        if (!$db) {
            throw new Exception("Database connection required for AuditLogger");
        }
        $this->db = $db;
    }
    
    public function logAction(string $action, int $recordId, string $tableName, array $oldData = [], array $newData = [], ?int $userId = null, string $description = ''): bool
    {
        try {
            // Use session user ID if not provided
            if ($userId === null) {
                $userId = $_SESSION['user_id'] ?? null;
            }

            
            $sql = "INSERT INTO audit_logs 
                    (action, record_id, table_name, old_data, new_data, user_id, description, ip_address, user_agent, created_at) 
                    VALUES (:action, :record_id, :table_name, :old_data, :new_data, :user_id, :description, :ip_address, :user_agent, NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':action' => $action,
                ':record_id' => $recordId,
                ':table_name' => $tableName,
                ':old_data' => !empty($oldData) ? json_encode($oldData) : null,
                ':new_data' => !empty($newData) ? json_encode($newData) : null,
                ':user_id' => $userId,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
            ]);
            
            if (!$result) {
                error_log("Failed to insert audit log: " . json_encode($stmt->errorInfo()));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("PDO Audit log error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyToken(string $token): ?array
{
    try {
        $sql = "SELECT * FROM email_verification 
                WHERE token = :token 
                AND expires_at > NOW() 
                AND is_verified = 0 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($record) {
            // Start transaction to ensure both updates succeed
            $this->db->beginTransaction();
            
            try {
                // 1. Mark token as verified in email_verification table
                $updateSql = "UPDATE email_verification 
                             SET is_verified = 1, verified_at = NOW() 
                             WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([':id' => $record['id']]);

                // 2. Update user record - mark as verified in users table
                $userSql = "UPDATE users 
                           SET is_verified = 1, verified_at = NOW() 
                           WHERE user_id = :user_id";
                $userStmt = $this->db->prepare($userSql);
                $userStmt->execute([':user_id' => $record['user_id']]);

                // Commit both changes
                $this->db->commit();
                
                return $record;
            } catch (\Exception $e) {
                // Rollback if any update fails
                $this->db->rollBack();
                throw $e;
            }
        }

        return null;
    } catch (\Exception $e) {
        error_log("Error verifying token: " . $e->getMessage());
        return null;
    }
}
    
}