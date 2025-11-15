<?php
// enhance_database.php - Add tables for notifications and admin features
echo "<h1>🩸 Enhancing Database for Advanced Features</h1>";

try {
    $host = "localhost";
    $dbname = "blood_don_system";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create notifications table
    $notifications_sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->exec($notifications_sql);
    echo "✅ Created notifications table<br>";
    
    // Create system_settings table
    $settings_sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($settings_sql);
    echo "✅ Created system_settings table<br>";
    
    // Create SMS logs table
    $sms_logs_sql = "CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_phone VARCHAR(20),
        message TEXT,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sms_logs_sql);
    echo "✅ Created sms_logs table<br>";
    
    // Create email logs table
    $email_logs_sql = "CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(255),
        subject VARCHAR(255),
        message TEXT,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($email_logs_sql);
    echo "✅ Created email_logs table<br>";
    
    // Insert default system settings
    $default_settings = [
        ['sms_enabled', '0', 'Enable SMS notifications'],
        ['email_enabled', '1', 'Enable email notifications'],
        ['low_stock_threshold', '5', 'Low stock alert threshold'],
        ['critical_blood_types', 'O-,AB-', 'Critical blood types that need monitoring'],
        ['admin_email', 'admin@bloodbank.org', 'Admin notification email'],
        ['sms_provider', 'africastalking', 'SMS service provider']
    ];
    
    foreach ($default_settings as $setting) {
        list($key, $value, $description) = $setting;
        $stmt = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute([$key, $value, $description]);
    }
    echo "✅ Added default system settings<br>";
    
    // Make first user an admin
    $conn->exec("UPDATE users SET user_type = 'admin' WHERE id = 1");
    echo "✅ Set first user as admin<br>";
    
    echo "<h2 style='color: green;'>🎉 Database Enhancement Completed!</h2>";
    echo "<p><a href='/pages/admin/dashboard.php'>Go to Admin Panel</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'><h3>❌ Enhancement Failed</h3>";
    echo "Error: " . $e->getMessage() . "</div>";
}
?>