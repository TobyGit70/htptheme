<?php
/**
 * Security Alert System
 *
 * Monitors access logs and sends email alerts for suspicious activity
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Security_Alerts {

    private $security_logger;
    private $options;

    public function __construct() {
        $this->security_logger = HappyTurtle_Security_Logger::get_instance();
        $this->options = get_option('htb_security_options', array());

        // Hook into security logger events
        add_action('htb_suspicious_activity_detected', array($this, 'handle_suspicious_activity'), 10, 2);
        add_action('htb_rate_limit_triggered', array($this, 'handle_rate_limit'), 10, 2);
        add_action('htb_ip_whitelist_blocked', array($this, 'handle_ip_whitelist_block'), 10, 2);
        add_action('htb_new_partner_registered', array($this, 'handle_new_partner'), 10, 1);
    }

    /**
     * Handle suspicious activity alert
     */
    public function handle_suspicious_activity($activity_type, $data) {
        if (!$this->is_alerts_enabled()) {
            return;
        }

        // Check if this alert type is enabled
        if ($activity_type === 'failed_attempts' && !$this->get_option('alert_failed_attempts')) {
            return;
        }

        if ($activity_type === 'unusual_location' && !$this->get_option('alert_unusual_location')) {
            return;
        }

        $this->send_alert($activity_type, $data);
    }

    /**
     * Handle rate limit alert
     */
    public function handle_rate_limit($identifier, $data) {
        if (!$this->is_alerts_enabled() || !$this->get_option('alert_rate_limit')) {
            return;
        }

        $this->send_alert('rate_limit', $data);
    }

    /**
     * Handle IP whitelist block
     */
    public function handle_ip_whitelist_block($ip_address, $data) {
        if (!$this->is_alerts_enabled() || !$this->get_option('alert_ip_whitelist_block')) {
            return;
        }

        $this->send_alert('ip_whitelist_block', $data);
    }

    /**
     * Handle new partner registration
     */
    public function handle_new_partner($partner_data) {
        if (!$this->is_alerts_enabled() || !$this->get_option('alert_new_partner')) {
            return;
        }

        $this->send_alert('new_partner', $partner_data);
    }

    /**
     * Send security alert email
     */
    private function send_alert($alert_type, $data) {
        $to = $this->get_option('alert_email') ?: get_option('admin_email');
        $subject = $this->get_alert_subject($alert_type);
        $message = $this->get_alert_message($alert_type, $data);

        // Add footer
        $message .= "\n\n" . str_repeat('-', 50) . "\n";
        $message .= "Happy Turtle Processing, Inc.\n";
        $message .= "Arkansas License #00340\n";
        $message .= "Security Monitoring System\n";
        $message .= "\n";
        $message .= "View security dashboard: " . admin_url('admin.php?page=happyturtle-security') . "\n";
        $message .= "Time: " . current_time('mysql') . "\n";

        // Set headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' Security <' . get_option('admin_email') . '>'
        );

        // Send email
        wp_mail($to, $subject, $message, $headers);

        // Log the alert
        error_log(sprintf('[Happy Turtle Security Alert] %s: %s', $alert_type, json_encode($data)));
    }

    /**
     * Get alert subject
     */
    private function get_alert_subject($alert_type) {
        $subjects = array(
            'failed_attempts' => 'Security Alert: Multiple Failed Login Attempts',
            'rate_limit' => 'Security Alert: Rate Limit Exceeded',
            'unusual_location' => 'Security Alert: Access from Unusual Location',
            'ip_whitelist_block' => 'Security Alert: IP Whitelist Violation',
            'new_partner' => 'Notification: New Partner Registration'
        );

        return '[Happy Turtle B2B] ' . ($subjects[$alert_type] ?? 'Security Alert');
    }

    /**
     * Get alert message
     */
    private function get_alert_message($alert_type, $data) {
        $message = "SECURITY ALERT\n";
        $message .= str_repeat('=', 50) . "\n\n";

        switch ($alert_type) {
            case 'failed_attempts':
                $message .= "Multiple Failed Authentication Attempts Detected\n\n";
                $message .= "IP Address: " . ($data['ip_address'] ?? 'Unknown') . "\n";
                $message .= "Failed Attempts: " . ($data['count'] ?? 0) . "\n";
                $message .= "Time Window: Last 1 hour\n";
                $message .= "Location: " . $this->format_location($data) . "\n";
                $message .= "User Agent: " . ($data['user_agent'] ?? 'Unknown') . "\n\n";
                $message .= "This could indicate a brute force attack attempt.\n";
                $message .= "The IP address has been temporarily locked out.\n";
                break;

            case 'rate_limit':
                $message .= "Rate Limit Exceeded\n\n";
                $message .= "Identifier: " . ($data['identifier'] ?? 'Unknown') . "\n";
                $message .= "Request Count: " . ($data['request_count'] ?? 0) . "\n";
                $message .= "Time Window: 1 minute\n";
                $message .= "Lockout Until: " . ($data['locked_until'] ?? 'Unknown') . "\n\n";
                $message .= "This could indicate:\n";
                $message .= "• Automated bot activity\n";
                $message .= "• Misconfigured integration\n";
                $message .= "• Denial of service attempt\n";
                break;

            case 'unusual_location':
                $message .= "Access from Unusual Location\n\n";
                $message .= "Partner: " . ($data['partner_name'] ?? 'Unknown') . "\n";
                $message .= "Partner ID: " . ($data['partner_id'] ?? 'Unknown') . "\n";
                $message .= "IP Address: " . ($data['ip_address'] ?? 'Unknown') . "\n";
                $message .= "Location: " . $this->format_location($data) . "\n";
                $message .= "Usual Location: Arkansas, US\n\n";
                $message .= "This partner typically accesses from Arkansas.\n";
                $message .= "Access from a foreign country may indicate:\n";
                $message .= "• Compromised credentials\n";
                $message .= "• VPN usage\n";
                $message .= "• Partner traveling\n\n";
                $message .= "Action: Contact partner to verify access.\n";
                break;

            case 'ip_whitelist_block':
                $message .= "IP Whitelist Violation\n\n";
                $message .= "Partner: " . ($data['partner_name'] ?? 'Unknown') . "\n";
                $message .= "Partner ID: " . ($data['partner_id'] ?? 'Unknown') . "\n";
                $message .= "Blocked IP: " . ($data['ip_address'] ?? 'Unknown') . "\n";
                $message .= "Location: " . $this->format_location($data) . "\n";
                $message .= "Whitelisted IPs: " . ($data['whitelisted_ips'] ?? 'None') . "\n\n";
                $message .= "This partner has IP whitelisting enabled.\n";
                $message .= "Access was blocked because the request came from\n";
                $message .= "a non-whitelisted IP address.\n\n";
                $message .= "Action: Add IP to whitelist if legitimate.\n";
                break;

            case 'new_partner':
                $message .= "New Partner Registration\n\n";
                $message .= "Business Name: " . ($data['business_name'] ?? 'Unknown') . "\n";
                $message .= "Contact Name: " . ($data['contact_name'] ?? 'Unknown') . "\n";
                $message .= "Email: " . ($data['email'] ?? 'Unknown') . "\n";
                $message .= "Phone: " . ($data['phone'] ?? 'Unknown') . "\n";
                $message .= "License Number: " . ($data['license_number'] ?? 'Not provided') . "\n";
                $message .= "Registration IP: " . ($data['ip_address'] ?? 'Unknown') . "\n";
                $message .= "Location: " . $this->format_location($data) . "\n\n";
                $message .= "Action: Review and approve in admin dashboard.\n";
                $message .= "Dashboard: " . admin_url('admin.php?page=happyturtle-partners') . "\n";
                break;
        }

        return $message;
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
     * Check if alerts are enabled
     */
    private function is_alerts_enabled() {
        return (bool) $this->get_option('email_alerts_enabled');
    }

    /**
     * Get option with default
     */
    private function get_option($key) {
        $defaults = array(
            'email_alerts_enabled' => 1,
            'alert_email' => '',
            'alert_failed_attempts' => 1,
            'alert_rate_limit' => 1,
            'alert_unusual_location' => 1,
            'alert_new_partner' => 1,
            'alert_ip_whitelist_block' => 1
        );

        return isset($this->options[$key]) ? $this->options[$key] : ($defaults[$key] ?? null);
    }
}

// Initialize alerts system
new HappyTurtle_Security_Alerts();
