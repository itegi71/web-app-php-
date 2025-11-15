<?php
class EmailService {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database->getConnection();
    }
    
    public function sendEmail($to, $subject, $message, $isHTML = true) {
        try {
            // For development - log email instead of sending
            $this->logEmail($to, $subject, $message, 'sent');
            
            // In production, you would use PHPMailer or similar
            if ($this->isEmailEnabled()) {
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Blood Donation System <noreply@bloodbank.org>" . "\r\n";
                
                // Uncomment to actually send emails in production:
                // mail($to, $subject, $message, $headers);
                
                error_log("📧 Email prepared for: $to - Subject: $subject");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logEmail($to, $subject, $message, 'failed', $e->getMessage());
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendDonationReminder($user_email, $user_name, $appointment_date, $location) {
        $subject = "🩸 Blood Donation Appointment Reminder";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #c33; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f5f5f5; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Blood Donation Reminder</h1>
            </div>
            <div class='content'>
                <h2>Hello $user_name,</h2>
                <p>This is a friendly reminder about your upcoming blood donation appointment:</p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <strong>Date:</strong> $appointment_date<br>
                    <strong>Location:</strong> $location
                </div>
                <p><strong>Preparation Tips:</strong></p>
                <ul>
                    <li>Drink plenty of water before donation</li>
                    <li>Eat a healthy meal</li>
                    <li>Bring a valid ID</li>
                    <li>Wear comfortable clothing</li>
                </ul>
                <p>Thank you for saving lives!</p>
            </div>
            <div class='footer'>
                <p>Blood Donation Management System<br>
                <small>This is an automated message. Please do not reply.</small></p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user_email, $subject, $message);
    }
    
    public function sendBloodRequestNotification($blood_type, $units_required, $hospital, $urgency) {
        // Get all available donors with matching blood type
        $stmt = $this->conn->prepare("
            SELECT email, full_name FROM users 
            WHERE blood_type = ? AND is_available = 1 AND user_type = 'donor'
        ");
        $stmt->execute([$blood_type]);
        $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sent_count = 0;
        foreach ($donors as $donor) {
            $subject = "🆘 URGENT: Blood Donation Needed - $blood_type";
            $message = "
            <html>
            <body>
                <h2>Urgent Blood Donation Request</h2>
                <p>Hello {$donor['full_name']},</p>
                <p>There is an <strong>$urgency</strong> need for <strong>$blood_type</strong> blood at <strong>$hospital</strong>.</p>
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <strong>Required:</strong> $units_required units of $blood_type<br>
                    <strong>Urgency:</strong> " . ucfirst($urgency) . "
                </div>
                <p>If you're able to help, please visit our platform to schedule a donation or contact the blood bank directly.</p>
                <p><strong>Your donation can save lives!</strong></p>
                <br>
                <p>Thank you for your generosity,<br>
                Blood Donation Management System</p>
            </body>
            </html>
            ";
            
            if ($this->sendEmail($donor['email'], $subject, $message)) {
                $sent_count++;
            }
        }
        
        return $sent_count;
    }
    
    public function sendLowStockAlert($blood_type, $current_units) {
        $admin_email = $this->getSystemSetting('admin_email');
        $subject = "⚠️ Low Stock Alert: $blood_type";
        $message = "
        <html>
        <body>
            <h2>Low Blood Stock Alert</h2>
            <p><strong>Blood Type:</strong> $blood_type</p>
            <p><strong>Current Stock:</strong> $current_units units</p>
            <p><strong>Alert Level:</strong> Below threshold</p>
            <br>
            <p>Please take necessary actions to replenish stock.</p>
        </body>
        </html>
        ";
        
        return $this->sendEmail($admin_email, $subject, $message);
    }
    
    private function logEmail($to, $subject, $message, $status, $error = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO email_logs (recipient_email, subject, message, status, error_message) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$to, $subject, $message, $status, $error]);
    }
    
    private function isEmailEnabled() {
        $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'email_enabled'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['setting_value'] == '1';
    }
    
    private function getSystemSetting($key) {
        $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : null;
    }
}
?>