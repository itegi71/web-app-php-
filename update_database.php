<?php
// update_database.php - Add new tables and columns
echo "<h1>🩸 Updating Database Schema</h1>";

try {
    $host = "localhost";
    $dbname = "blood_don_system";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add new columns to users table
    $alter_users = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100)",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_donation_date DATE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_available BOOLEAN DEFAULT TRUE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type ENUM('donor', 'admin') DEFAULT 'donor'"
    ];
    
    foreach ($alter_users as $sql) {
        try {
            $conn->exec($sql);
            echo "✅ Updated users table<br>";
        } catch (Exception $e) {
            echo "⚠️ Users table already updated: " . $e->getMessage() . "<br>";
        }
    }
    
    // Create new tables
    $new_tables = [
        "blood_requests" => "CREATE TABLE IF NOT EXISTS blood_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_name VARCHAR(255) NOT NULL,
            blood_type VARCHAR(3) NOT NULL,
            units_required INT NOT NULL,
            hospital_name VARCHAR(255),
            urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            contact_number VARCHAR(20),
            address TEXT,
            required_date DATE,
            status ENUM('open', 'fulfilled', 'cancelled') DEFAULT 'open',
            requested_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (requested_by) REFERENCES users(id)
        )",
        
        "appointments" => "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            appointment_date DATETIME NOT NULL,
            blood_bank_name VARCHAR(255),
            location TEXT,
            status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "blood_inventory" => "CREATE TABLE IF NOT EXISTS blood_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blood_type VARCHAR(3) NOT NULL,
            units_available INT DEFAULT 0,
            blood_bank_name VARCHAR(255),
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($new_tables as $table_name => $sql) {
        try {
            $conn->exec($sql);
            echo "✅ Created table: $table_name<br>";
        } catch (Exception $e) {
            echo "⚠️ Table $table_name already exists: " . $e->getMessage() . "<br>";
        }
    }
    
    // Insert sample inventory data
    $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    foreach ($blood_types as $type) {
        $stmt = $conn->prepare("INSERT IGNORE INTO blood_inventory (blood_type, units_available, blood_bank_name) VALUES (?, ?, ?)");
        $stmt->execute([$type, rand(5, 20), 'City Blood Bank']);
    }
    
    echo "<h2 style='color: green;'>🎉 Database Update Completed!</h2>";
    echo "<p><a href='/pages/home.php'>Go to Updated Home Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'><h3>❌ Update Failed</h3>";
    echo "Error: " . $e->getMessage() . "</div>";
}
?>