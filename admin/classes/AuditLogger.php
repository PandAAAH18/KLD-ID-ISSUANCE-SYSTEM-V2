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
    
}