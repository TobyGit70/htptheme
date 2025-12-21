<?php
/**
 * SMS Alert System using Twilio
 *
 * Sends SMS alerts for critical security events
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_SMS_Alerts {

    private $twilio_sid;
    private $twilio_token;
    private $twilio_phone;
    private $options;

    public function __construct() {
        $this->options = get_option('htb_security_options', array());

        // Get Twilio credentials from settings
        $this->twilio_sid = $this->get_option('twilio_account_sid');
        $this->twilio_token = $this->get_option('twilio_auth_token');
        $this->twilio_phone = $this->get_option('twilio_phone_number');

        // Hook into security alert events
        add_action('htb_suspicious_activity_detected', array($this, 'handle_sms_alert'), 10, 2);
        add_action('htb_rate_limit_triggered', array($this, 'handle_sms_alert'), 10, 2);
        add_action('htb_ip_whitelist_blocked', array($this, 'handle_sms_alert'), 10, 2);
    }

    /**
     * Handle SMS alert
     */
    public function handle_sms_alert($alert_type, $data) {
        // Check if SMS alerts are enabled
        if (!$this->is_sms_enabled()) {
            return;
        }

        // Check if this alert type should trigger SMS
        if (!$this->should_send_sms($alert_type)) {
            return;
        }

        // Get recipient phone numbers
        $phone_numbers = $this->get_recipient_phones();

        if (empty($phone_numbers)) {
            return;
        }

        // Format SMS message
        $message = $this->format_sms_message($alert_type, $data);

        // Send to all recipients
        foreach ($phone_numbers as $phone) {
            $this->send_sms($phone, $message);
        }
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $to Recipient phone number
     * @param string $message Message text
     * @return bool Success
     */
    public function send_sms($to, $message) {
        // Validate credentials
        if (empty($this->twilio_sid) || empty($this->twilio_token) || empty($this->twilio_phone)) {
            error_log('[Happy Turtle SMS] Twilio credentials not configured');
            return false;
        }

        // Validate phone number format (E.164)
        $to = $this->format_phone_number($to);
        if (!$to) {
            error_log('[Happy Turtle SMS] Invalid phone number format: ' . $to);
            return false;
        }

        // Twilio API endpoint
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilio_sid}/Messages.json";

        // Prepare request data
        $data = array(
            'From' => $this->twilio_phone,
            'To' => $to,
            'Body' => $message
        );

        // Send request using WordPress HTTP API
        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->twilio_sid . ':' . $this->twilio_token),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $data
        ));

        // Check for errors
        if (is_wp_error($response)) {
            error_log('[Happy Turtle SMS] Error sending SMS: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 201) {
            error_log('[Happy Turtle SMS] SMS sent successfully to ' . $to . ' (SID: ' . $body['sid'] . ')');
            return true;
        } else {
            error_log('[Happy Turtle SMS] Failed to send SMS: ' . ($body['message'] ?? 'Unknown error'));
            return false;
        }
    }

    /**
     * Format SMS message for alert
     *
     * @param string $alert_type Alert type
     * @param array $data Alert data
     * @return string SMS message
     */
    private function format_sms_message($alert_type, $data) {
        $messages = array(
            'failed_attempts' => sprintf(
                "🚨 Happy Turtle Security Alert\n\nMultiple failed login attempts detected!\n\nIP: %s\nAttempts: %d\nLocation: %s\n\nView: %s",
                $data['ip_address'] ?? 'Unknown',
                $data['count'] ?? 0,
                $this->format_location($data),
                admin_url('admin.php?page=happyturtle-security')
            ),

            'rate_limit' => sprintf(
                "⚠️ Happy Turtle Security Alert\n\nRate limit exceeded!\n\nIdentifier: %s\nRequests: %d/min\nLocked until: %s\n\nView: %s",
                $data['identifier'] ?? 'Unknown',
                $data['request_count'] ?? 0,
                isset($data['locked_until']) ? date('H:i', strtotime($data['locked_until'])) : 'N/A',
                admin_url('admin.php?page=happyturtle-security')
            ),

            'unusual_location' => sprintf(
                "🌍 Happy Turtle Security Alert\n\nAccess from unusual location!\n\nPartner: %s\nIP: %s\nLocation: %s\n\nAction: Contact partner to verify.\n\nView: %s",
                $data['partner_name'] ?? 'Unknown',
                $data['ip_address'] ?? 'Unknown',
                $this->format_location($data),
                admin_url('admin.php?page=happyturtle-security')
            ),

            'ip_whitelist_block' => sprintf(
                "🔒 Happy Turtle Security Alert\n\nIP whitelist violation!\n\nPartner: %s\nBlocked IP: %s\nLocation: %s\n\nAction: Add IP to whitelist if legitimate.\n\nView: %s",
                $data['partner_name'] ?? 'Unknown',
                $data['ip_address'] ?? 'Unknown',
                $this->format_location($data),
                admin_url('admin.php?page=happyturtle-security-whitelist')
            )
        );

        return $messages[$alert_type] ?? sprintf(
            "🚨 Happy Turtle Security Alert\n\nType: %s\nTime: %s\n\nView: %s",
            $alert_type,
            current_time('mysql'),
            admin_url('admin.php?page=happyturtle-security')
        );
    }

    /**
     * Format location string
     */
    private function format_location($data) {
        $parts = array();

        if (!empty($data['city'])) {
            $parts[] = $data['city'];
        }
        if (!empty($data['region'])) {
            $parts[] = $data['region'];
        }
        if (!empty($data['country_code'])) {
            $parts[] = $data['country_code'];
        }

        return !empty($parts) ? implode(', ', $parts) : 'Unknown';
    }

    /**
     * Format phone number to E.164 format
     *
     * @param string $phone Phone number
     * @return string|false Formatted phone or false
     */
    private function format_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 1, assume US/Canada number
        if (substr($phone, 0, 1) === '1' && strlen($phone) === 11) {
            return '+' . $phone;
        }

        // If 10 digits, assume US/Canada without country code
        if (strlen($phone) === 10) {
            return '+1' . $phone;
        }

        // If already has + prefix, validate length
        if (strlen($phone) >= 10 && strlen($phone) <= 15) {
            return '+' . $phone;
        }

        return false;
    }

    /**
     * Get recipient phone numbers
     *
     * @return array Phone numbers
     */
    private function get_recipient_phones() {
        $phones_string = $this->get_option('sms_phone_numbers');

        if (empty($phones_string)) {
            return array();
        }

        // Split by comma and clean
        $phones = array_map('trim', explode(',', $phones_string));
        $phones = array_filter($phones); // Remove empty values

        return $phones;
    }

    /**
     * Check if SMS alerts are enabled
     */
    private function is_sms_enabled() {
        return (bool) $this->get_option('sms_alerts_enabled');
    }

    /**
     * Check if alert type should send SMS
     */
    private function should_send_sms($alert_type) {
        // SMS is for critical alerts only
        $critical_alerts = array(
            'failed_attempts',
            'rate_limit',
            'unusual_location',
            'ip_whitelist_block'
        );

        return in_array($alert_type, $critical_alerts);
    }

    /**
     * Send test SMS
     *
     * @param string $phone Phone number to test
     * @return array Result with success/error
     */
    public function send_test_sms($phone) {
        $message = "🧪 Happy Turtle Security Test\n\nThis is a test SMS from your Happy Turtle B2B security system.\n\nIf you're receiving this, SMS alerts are configured correctly!\n\nTime: " . current_time('mysql');

        $result = $this->send_sms($phone, $message);

        if ($result) {
            return array(
                'success' => true,
                'message' => 'Test SMS sent successfully to ' . $phone
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to send test SMS. Check your Twilio credentials and phone number format.'
            );
        }
    }

    /**
     * Get option with default
     */
    private function get_option($key) {
        return isset($this->options[$key]) ? $this->options[$key] : '';
    }

    /**
     * Verify Twilio credentials
     *
     * @return array Result with success/error
     */
    public function verify_twilio_credentials() {
        if (empty($this->twilio_sid) || empty($this->twilio_token)) {
            return array(
                'success' => false,
                'message' => 'Twilio credentials not configured'
            );
        }

        // Test by fetching account info
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilio_sid}.json";

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->twilio_sid . ':' . $this->twilio_token)
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return array(
                'success' => true,
                'message' => 'Twilio credentials verified successfully!',
                'account_name' => $body['friendly_name'] ?? 'Unknown'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Invalid Twilio credentials (Status: ' . $status_code . ')'
            );
        }
    }
}

// Initialize SMS alerts
new HappyTurtle_SMS_Alerts();
