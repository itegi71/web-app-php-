<?php
// test_db.php - Database Connection Test
echo "<h1>Database Connection Test</h1>";

// Test different connection scenarios
$host = "localhost";
$port = "5432";
$username = "postgres";

// Test 1: Try to connect without password (might work if no password set)
echo "<h3>Test 1: Connecting without password</h3>";
try {
    $conn = new PDO("pgsql:host=$host;port=$port", $username);
    echo "✅ Connected to PostgreSQL server<br>";
    
    // List databases
    $stmt = $conn->query("SELECT datname FROM pg_database WHERE datistemplate = false");
    echo "<h4>Available databases:</h4>";
    while ($db = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $db['datname'] . "<br>";
    }
} catch(PDOException $e) {
    echo "❌ Failed: " . $e->getMessage() . "<br>";
}

// Test 2: Try to connect to specific database
echo "<h3>Test 2: Connecting to blood_donation_system database</h3>";
$password = "your_password_here"; // Try your password here
$dbname = "blood_donation_system";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to database successfully!<br>";
    
    // Check tables
    $tables = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tableCount = 0;
    while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
        echo "- Table: " . $table['table_name'] . "<br>";
        $tableCount++;
    }
    
    if ($tableCount === 0) {
        echo "⚠️ No tables found. You need to create the tables.<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Failed: " . $e->getMessage() . "<br>";
    
    // If database doesn't exist, try to create it
    if (strpos($e->getMessage(), 'database "blood_donation_system" does not exist') !== false) {
        echo "<p style='color: orange;'>Database doesn't exist. Let's create it...</p>";
        try {
            $conn = new PDO("pgsql:host=$host;port=$port", $username, $password);
            $conn->exec("CREATE DATABASE blood_donation_system");
            echo "✅ Database 'blood_donation_system' created successfully!<br>";
        } catch(PDOException $e2) {
            echo "❌ Could not create database: " . $e2->getMessage() . "<br>";
        }
    }
}

echo "<hr><h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If connection fails, check your PostgreSQL password</li>";
echo "<li>Make sure PostgreSQL service is running</li>";
echo "<li>Create the database tables if they don't exist</li>";
echo "</ol>";
?>