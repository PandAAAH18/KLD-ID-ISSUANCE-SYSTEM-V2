<?php
class Database {
    private $host = "localhost";
    private $db_name = "school_id_system";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Fatal Error: Unable to connect to database. Database '" . $this->db_name . "' not found or connection failed. Please check your database configuration in db.php");
        }

        return $this->conn;
    }
}
?>