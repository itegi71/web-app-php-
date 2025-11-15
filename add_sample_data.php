<?php
// add_sample_data.php - Add sample data for testing
echo "<h1>Adding Sample Data</h1>";

try {
    $host = "localhost";
    $dbname = "blood_don_system";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add sample donors
    $sample_donors = [
        ['John Doe', 'john@email.com', 'A+', 'Nairobi', 1],
        ['Jane Smith', 'jane@email.com', 'O+', 'Mombasa', 1],
        ['Mike Johnson', 'mike@email.com', 'B-', 'Nairobi', 1],
        ['Sarah Williams', 'sarah@email.com', 'AB+', 'Kisumu', 0],
        ['David Brown', 'david@email.com', 'A-', 'Nairobi', 1]
    ];
    
    $donor_count = 0;
    foreach ($sample_donors as $donor) {
        list($name, $email, $blood_type, $city, $available) = $donor;
        
        // Check if email exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->bindParam(":email", $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, blood_type, phone, city, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, password_hash('password123', PASSWORD_DEFAULT), $blood_type, '0712345678', $city, $available]);
            $donor_count++;
        }
    }
    echo "✅ Added $donor_count sample donors<br>";
    
    // Add sample blood requests
    $sample_requests = [
        ['Mary Kamau', 'O+', 2, 'Nairobi Hospital', 'critical', '0723456789', '123 Hospital Road, Nairobi', date('Y-m-d', strtotime('+2 days'))],
        ['Peter Ochieng', 'A+', 1, 'Kenyatta Hospital', 'high', '0734567890', '456 Medical Avenue, Nairobi', date('Y-m-d', strtotime('+3 days'))],
        ['Grace Wanjiku', 'B-', 3, 'Mombasa Hospital', 'medium', '0745678901', '789 Coast Road, Mombasa', date('Y-m-d', strtotime('+5 days'))]
    ];
    
    $request_count = 0;
    foreach ($sample_requests as $request) {
        list($patient, $blood_type, $units, $hospital, $urgency, $contact, $address, $req_date) = $request;
        
        $stmt = $conn->prepare("INSERT INTO blood_requests (patient_name, blood_type, units_required, hospital_name, urgency_level, contact_number, address, required_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')");
        $stmt->execute([$patient, $blood_type, $units, $hospital, $urgency, $contact, $address, $req_date]);
        $request_count++;
    }
    echo "✅ Added $request_count sample blood requests<br>";
    
    // Add sample appointments
    $sample_appointments = [
        [1, date('Y-m-d 10:00:00', strtotime('+1 week')), 'City Blood Bank', '123 Donation Center, Nairobi', 'scheduled'],
        [2, date('Y-m-d 14:00:00', strtotime('+2 weeks')), 'Regional Hospital', '456 Health Street, Mombasa', 'scheduled']
    ];
    
    $appointment_count = 0;
    foreach ($sample_appointments as $appointment) {
        list($user_id, $app_date, $bank_name, $location, $status) = $appointment;
        
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, blood_bank_name, location, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $app_date, $bank_name, $location, $status]);
        $appointment_count++;
    }
    echo "✅ Added $appointment_count sample appointments<br>";
    
    echo "<h2 style='color: green;'>🎉 Sample Data Added Successfully!</h2>";
    echo "<p><a href='/pages/find_donors.php'>Test Find Donors</a> | <a href='/pages/blood_requests.php'>Test Blood Requests</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'><h3>❌ Error Adding Sample Data</h3>";
    echo "Error: " . $e->getMessage() . "</div>";
}
?>