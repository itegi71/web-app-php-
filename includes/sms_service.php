<?php
class SMSService {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database->getConnection();
    }
    
    public function sendSMS($phone_number, $message) {
        try {
            // For development - log SMS instead of sending
            $this->logSMS($phone_number, $message, 'sent');
            
            // In production, integrate with services like:
            // - Africa's Talking
            // - Twilio
            // - Nexmo
            // - Local SMS gateway
            
            if ($this->isSMSEnabled()) {
                error_log("📱 SMS prepared for: $phone_number - Message: $message");
                
                // Simulate SMS sending (replace with actual API call)
                // return $this->sendViaAfricaTalking($phone_number, $message);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logSMS($phone_number, $message, 'failed', $e->getMessage());
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendAppointmentReminder($phone_number, $user_name, $appointment_date, $location) {
        $message = "Hello $user_name! Reminder: Your blood donation is scheduled for $appointment_date at $location. Please drink water and eat well before donation. Thank you!";
        return $this->sendSMS($phone_number, $message);
    }
    
    public function sendUrgentRequestAlert($phone_number, $blood_type, $hospital) {
        $message = "URGENT: $blood_type blood needed at $hospital. If available, please donate. Your help can save lives! Contact the blood bank for details.";
        return $this->sendSMS($phone_number, $message);
    }
    
    public function sendDonationThankYou($phone_number, $user_name) {
        $message = "Thank you $user_name! Your blood donation is greatly appreciated. You've helped save lives today! You can donate again in 8 weeks.";
        return $this->sendSMS($phone_number, $message);
    }
    
    // Africa's Talking Integration (Example)
    private function sendViaAfricaTalking($phone_number, $message) {
        // You'll need to install Africa's Talking PHP SDK
        // composer require africastalking/africastalking
        
        /*
        $username = 'your_username';
        $apiKey = 'your_api_key';
        
        $AT = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
        $sms = $AT->sms();
        
        try {
            $result = $sms->send([
                'to'      => $phone_number,
                'message' => $message,
                'from'    => 'BLOODBANK'
            ]);
            
            return $result['status'] === 'success';
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        */
        
        // For now, return true to simulate success
        return true;
    }
    
    private function logSMS($phone_number, $message, $status, $error = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO sms_logs (recipient_phone, message, status, error_message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$phone_number, $message, $status, $error]);
    }
    
    private function isSMSEnabled() {
        $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_enabled'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['setting_value'] == '1';
    }
}
?>