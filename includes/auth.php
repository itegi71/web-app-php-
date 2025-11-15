<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    public $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        
        // Check if connection is valid
        if ($this->conn === null) {
            throw new Exception("Database connection failed. Please check your database configuration.");
        }
    }

    public function register($email, $password, $full_name, $blood_type, $phone) {
        try {
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $this->conn->prepare($check_query);
            
            if ($check_stmt === false) {
                throw new Exception("Database preparation failed. Check connection.");
            }
            
            $check_stmt->bindParam(":email", $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                return "Email already exists!";
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $query = "INSERT INTO users (email, password, full_name, blood_type, phone) 
                     VALUES (:email, :password, :full_name, :blood_type, :phone)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":full_name", $full_name);
            $stmt->bindParam(":blood_type", $blood_type);
            $stmt->bindParam(":phone", $phone);

            if ($stmt->execute()) {
                return true;
            }
            return "Registration failed!";
        } catch(PDOException $exception) {
            return "Database error: " . $exception->getMessage();
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    public function login($email, $password) {
        try {
            if ($this->conn === null) {
                return "Database connection not available";
            }
            
            $query = "SELECT id, email, password, full_name, blood_type FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['blood_type'] = $row['blood_type'];
                    return true;
                }
            }
            return false;
        } catch(PDOException $exception) {
            return "Database error: " . $exception->getMessage();
        }
    }
}
?>