<?php
echo "<h1>Blood Donation System - Database Test</h1>";
echo "<h3>Testing PostgreSQL Connection...</h3>";

try {
    // Database configuration - UPDATE THESE CREDENTIALS!
    $host = "localhost";
    $port = "5432";
    $dbname = "blood-php";
    $username = "postgres";
    $password = "unknowndimension/377@localhost"; // Change this to your actual PostgreSQL password
    
    // Create connection
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color: green; font-weight: bold;'>✅ PostgreSQL connection successful!</div>";
    
    // Get PostgreSQL version
    $stmt = $conn->query("SELECT version()");
    $version = $stmt->fetch();
    echo "<p><strong>PostgreSQL Version:</strong> " . $version[0] . "</p>";
    
    // Check if tables exist
    echo "<h4>Database Tables:</h4>";
    $tables = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    
    $tableCount = 0;
    while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $table['table_name'] . "<br>";
        $tableCount++;
    }
    
    if ($tableCount === 0) {
        echo "<div style='color: orange;'>⚠️ No tables found. You need to create the database tables.</div>";
        echo "<p><a href='#' onclick='showSQL()'>Click here to see the SQL to create tables</a></p>";
        echo "<div id='sqlCode' style='display: none; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "<pre>CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    blood_type VARCHAR(3),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE donations (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    donation_date DATE NOT NULL,
    location VARCHAR(255),
    status VARCHAR(50) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
        echo "</div>";
        echo "<script>function showSQL() { document.getElementById('sqlCode').style.display = 'block'; }</script>";
    }
    
} catch(PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Connection failed: " . $e->getMessage() . "</div>";
    
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Check if PostgreSQL service is running</li>";
    echo "<li>Verify the password in this test file</li>";
    echo "<li>Check if database 'blood_donation_system' exists in pgAdmin</li>";
    echo "<li>Make sure the username is correct (usually 'postgres')</li>";
    echo "</ol>";
    
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li><strong>Password issues:</strong> Open pgAdmin → Servers → PostgreSQL → Login/Group Roles → right-click 'postgres' → Properties → change password</li>";
    echo "<li><strong>Database doesn't exist:</strong> Create database 'blood_donation_system' in pgAdmin</li>";
    echo "<li><strong>Service not running:</strong> Start PostgreSQL service in XAMPP or Windows Services</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='/'>Go to Home</a> | <a href='/pages/signup.php'>Sign Up</a> | <a href='/pages/login.php'>Login</a></p>";
?>