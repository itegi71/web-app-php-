<?php
class Database {
    private $host = "localhost";
    private $db_name = "blood_don_system";
    private $username = "root";
    private $password = ""; // Empty by default in XAMPP
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("✅ MySQL Database connected successfully"); // Debug log
        } catch(PDOException $exception) {
            error_log("❌ Database connection failed: " . $exception->getMessage());
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>