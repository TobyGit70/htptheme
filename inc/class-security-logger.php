<?php
/**
 * Unified Security Logging System
 *
 * Tracks ALL access attempts (web login + API) with IP, geolocation, and threat detection
 * Prevents brute force attacks, logs suspicious activity, and provides audit trail
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Security_Logger {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Database table names
     */
    private $access_log_table;
    private $rate_limit_table;
    private $ip_whitelist_table;

    /**
     * Rate limit settings
     */
    const MAX_REQUESTS_PER_MINUTE = 60;
    const MAX_FAILED_ATTEMPTS_PER_HOUR = 10;
    const LOCKOUT_DURATION_MINUTES = 30;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->access_log_table = $wpdb->prefix . '1_happyturtle_access_log';
        $this->rate_limit_table = $wpdb->prefix . '1_happyturtle_rate_limits';
        $this->ip_whitelist_table = $wpdb->prefix . '1_happyturtle_ip_whitelist';

        $this->create_tables();

        // Schedule cleanup cron job
        add_action('htb_security_cleanup_cron', array($this, 'run_cleanup_cron'));

        if (!wp_next_scheduled('htb_security_cleanup_cron')) {
            wp_schedule_event(time(), 'daily', 'htb_security_cleanup_cron');
        }
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Access log table (unified for web + API)
        $sql_access_log = "CREATE TABLE IF NOT EXISTS {$this->access_log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            access_type varchar(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            endpoint varchar(255) DEFAULT NULL,
            method varchar(10) DEFAULT NULL,
            status varchar(20) NOT NULL,
            status_code int(3) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            country_code varchar(2) DEFAULT NULL,
            region varchar(100) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            request_data text,
            response_data text,
            error_message text,
            execution_time float DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY user_id (user_id),
            KEY access_type (access_type),
            KEY event_type (event_type),
            KEY status (status),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Rate limit tracking table
        $sql_rate_limit = "CREATE TABLE IF NOT EXISTS {$this->rate_limit_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            identifier varchar(100) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 1,
            window_start datetime NOT NULL,
            window_end datetime NOT NULL,
            is_locked tinyint(1) DEFAULT 0,
            locked_until datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY identifier (identifier),
            KEY window_end (window_end),
            KEY is_locked (is_locked)
        ) $charset_collate;";

        // IP whitelist table
        $sql_ip_whitelist = "CREATE TABLE IF NOT EXISTS {$this->ip_whitelist_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            ip_range_start varchar(45) DEFAULT NULL,
            ip_range_end varchar(45) DEFAULT NULL,
            description text,
            is_active tinyint(1) DEFAULT 1,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY ip_address (ip_address),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_access_log);
        dbDelta($sql_rate_limit);
        dbDelta($sql_ip_whitelist);
    }

    /**
     * Log access attempt (unified for web and API)
     *
     * @param array $data Log data
     * @return int|false Log ID or false
     */
    public function log_access($data) {
        global $wpdb;

        $defaults = array(
            'partner_id' => null,
            'user_id' => null,
            'access_type' => 'unknown', // 'api' or 'web'
            'event_type' => 'unknown', // 'login', 'api_request', 'product_view', etc.
            'endpoint' => null,
            'method' => null,
            'status' => 'unknown', // 'success', 'failed', 'blocked'
            'status_code' => null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'country_code' => null,
            'region' => null,
            'city' => null,
            'request_data' => null,
            'response_data' => null,
            'error_message' => null,
            'execution_time' => null,
            'created_at' => current_time('mysql')
        );

        $log_data = wp_parse_args($data, $defaults);

        // Get geo-location if not provided
        if (!$log_data['country_code']) {
            $geo = $this->get_geolocation($log_data['ip_address']);
            $log_data['country_code'] = $geo['country_code'];
            $log_data['region'] = $geo['region'];
            $log_data['city'] = $geo['city'];
        }

        // Sanitize sensitive data before logging
        if ($log_data['request_data'] && is_array($log_data['request_data'])) {
            $log_data['request_data'] = $this->sanitize_log_data($log_data['request_data']);
        }

        $result = $wpdb->insert($this->access_log_table, $log_data);

        if ($result === false) {
            error_log('HappyTurtle Security Logger: Failed to insert log entry');
            return false;
        }

        // Check for suspicious activity
        $this->check_suspicious_activity($log_data);

        return $wpdb->insert_id;
    }

    /**
     * Check rate limit
     *
     * @param string $identifier Unique identifier (partner_id, IP, user_id)
     * @param string $type Rate limit type ('api', 'login', 'registration')
     * @return bool|WP_Error True if allowed, WP_Error if rate limited
     */
    public function check_rate_limit($identifier, $type = 'api') {
        global $wpdb;

        $identifier_key = $type . ':' . $identifier;
        $now = current_time('mysql');

        // Check if currently locked out
        $lockout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->rate_limit_table}
             WHERE identifier = %s AND is_locked = 1 AND locked_until > %s",
            $identifier_key,
            $now
        ), ARRAY_A);

        if ($lockout) {
            $time_remaining = strtotime($lockout['locked_until']) - time();
            return new WP_Error(
                'rate_limited',
                sprintf('Too many requests. Try again in %d minutes.', ceil($time_remaining / 60)),
                array('retry_after' => $lockout['locked_until'])
            );
        }

        // Get or create rate limit record
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->rate_limit_table} WHERE identifier = %s",
            $identifier_key
        ), ARRAY_A);

        $max_requests = self::MAX_REQUESTS_PER_MINUTE;
        $window_duration = 60; // seconds

        if ($type === 'login') {
            $max_requests = self::MAX_FAILED_ATTEMPTS_PER_HOUR;
            $window_duration = 3600; // 1 hour
        }

        if (!$record) {
            // First request - create record
            $wpdb->insert($this->rate_limit_table, array(
                'identifier' => $identifier_key,
                'request_count' => 1,
                'window_start' => $now,
                'window_end' => date('Y-m-d H:i:s', time() + $window_duration)
            ));
            return true;
        }

        // Check if window expired
        if (strtotime($record['window_end']) < time()) {
            // Reset window
            $wpdb->update(
                $this->rate_limit_table,
                array(
                    'request_count' => 1,
                    'window_start' => $now,
                    'window_end' => date('Y-m-d H:i:s', time() + $window_duration),
                    'is_locked' => 0,
                    'locked_until' => null
                ),
                array('id' => $record['id'])
            );
            return true;
        }

        // Increment request count
        $new_count = $record['request_count'] + 1;

        if ($new_count > $max_requests) {
            // Lock out
            $lockout_until = date('Y-m-d H:i:s', time() + (self::LOCKOUT_DURATION_MINUTES * 60));
            $wpdb->update(
                $this->rate_limit_table,
                array(
                    'request_count' => $new_count,
                    'is_locked' => 1,
                    'locked_until' => $lockout_until
                ),
                array('id' => $record['id'])
            );

            // Log the lockout
            $this->log_access(array(
                'access_type' => $type,
                'event_type' => 'rate_limit_exceeded',
                'status' => 'blocked',
                'error_message' => "Rate limit exceeded: {$new_count} requests"
            ));

            return new WP_Error(
                'rate_limited',
                sprintf('Too many requests. Locked out for %d minutes.', self::LOCKOUT_DURATION_MINUTES),
                array('retry_after' => $lockout_until)
            );
        }

        // Update count
        $wpdb->update(
            $this->rate_limit_table,
            array('request_count' => $new_count),
            array('id' => $record['id'])
        );

        return true;
    }

    /**
     * Check if IP is whitelisted for partner
     *
     * @param int $partner_id Partner ID
     * @param string $ip_address IP address
     * @return bool True if whitelisted
     */
    public function is_ip_whitelisted($partner_id, $ip_address = null) {
        global $wpdb;

        if (!$ip_address) {
            $ip_address = $this->get_client_ip();
        }

        $whitelisted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->ip_whitelist_table}
             WHERE partner_id = %d
             AND is_active = 1
             AND (ip_address = %s OR (ip_range_start <= %s AND ip_range_end >= %s))",
            $partner_id,
            $ip_address,
            $ip_address,
            $ip_address
        ));

        return $whitelisted > 0;
    }

    /**
     * Add IP to whitelist
     *
     * @param int $partner_id Partner ID
     * @param string $ip_address IP address
     * @param string $description Description
     * @return int|false Whitelist entry ID or false
     */
    public function add_ip_whitelist($partner_id, $ip_address, $description = '') {
        global $wpdb;

        return $wpdb->insert($this->ip_whitelist_table, array(
            'partner_id' => $partner_id,
            'ip_address' => $ip_address,
            'description' => $description,
            'is_active' => 1,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    public function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get geolocation from IP
     *
     * @param string $ip_address IP address
     * @return array Geo data
     */
    private function get_geolocation($ip_address) {
        // Use free IP geolocation API (consider ip-api.com or ipinfo.io)
        // For production, use a paid service like MaxMind GeoIP2

        $geo = array(
            'country_code' => null,
            'region' => null,
            'city' => null
        );

        // Skip for local/private IPs
        if (in_array($ip_address, array('127.0.0.1', '::1', '0.0.0.0')) ||
            strpos($ip_address, '192.168.') === 0 ||
            strpos($ip_address, '10.') === 0) {
            $geo['country_code'] = 'US'; // Assume US for local dev
            $geo['region'] = 'Arkansas';
            $geo['city'] = 'Hot Springs';
            return $geo;
        }

        // Try to get geo data from ip-api.com (free, no key required, 45 req/min)
        $api_url = "http://ip-api.com/json/{$ip_address}?fields=status,countryCode,regionName,city";
        $response = wp_remote_get($api_url, array('timeout' => 3));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data && $data['status'] === 'success') {
                $geo['country_code'] = $data['countryCode'] ?? null;
                $geo['region'] = $data['regionName'] ?? null;
                $geo['city'] = $data['city'] ?? null;
            }
        }

        return $geo;
    }

    /**
     * Sanitize log data (remove sensitive info)
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitize_log_data($data) {
        $sensitive_keys = array('password', 'api_secret', 'secret', 'token', 'credit_card', 'ssn', 'ein');

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), $sensitive_keys)) {
                    $data[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    $data[$key] = $this->sanitize_log_data($value);
                }
            }
        }

        return $data;
    }

    /**
     * Check for suspicious activity
     *
     * @param array $log_data Log data
     */
    private function check_suspicious_activity($log_data) {
        global $wpdb;

        $ip = $log_data['ip_address'];
        $hour_ago = date('Y-m-d H:i:s', time() - 3600);

        // Check for multiple failed attempts from same IP
        $failed_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->access_log_table}
             WHERE ip_address = %s
             AND status = 'failed'
             AND created_at > %s",
            $ip,
            $hour_ago
        ));

        if ($failed_count >= 5) {
            // Alert admin about suspicious activity
            $this->send_security_alert(array(
                'type' => 'multiple_failed_attempts',
                'ip_address' => $ip,
                'count' => $failed_count,
                'details' => $log_data
            ));
        }

        // Check for access from unusual countries
        if ($log_data['partner_id'] && $log_data['country_code']) {
            $usual_countries = $this->get_partner_usual_countries($log_data['partner_id']);
            if (!in_array($log_data['country_code'], $usual_countries)) {
                $this->send_security_alert(array(
                    'type' => 'unusual_location',
                    'partner_id' => $log_data['partner_id'],
                    'country_code' => $log_data['country_code'],
                    'details' => $log_data
                ));
            }
        }
    }

    /**
     * Get partner's usual countries
     *
     * @param int $partner_id Partner ID
     * @return array Country codes
     */
    private function get_partner_usual_countries($partner_id) {
        global $wpdb;

        $countries = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT country_code FROM {$this->access_log_table}
             WHERE partner_id = %d
             AND status = 'success'
             AND country_code IS NOT NULL
             GROUP BY country_code
             HAVING COUNT(*) > 10",
            $partner_id
        ));

        return $countries ?: array('US'); // Default to US if no history
    }

    /**
     * Send security alert to admin
     *
     * @param array $alert Alert data
     */
    private function send_security_alert($alert) {
        $admin_email = get_option('admin_email');
        $subject = 'Happy Turtle B2B Security Alert: ' . $alert['type'];

        $message = "Security Alert\n\n";
        $message .= "Type: " . $alert['type'] . "\n";
        $message .= "Time: " . current_time('mysql') . "\n\n";
        $message .= "Details:\n" . print_r($alert, true);

        wp_mail($admin_email, $subject, $message);

        // Also log to error log
        error_log("HappyTurtle Security Alert: " . json_encode($alert));
    }

    /**
     * Get access logs
     *
     * @param array $args Query arguments
     * @return array Logs
     */
    public function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'partner_id' => null,
            'user_id' => null,
            'access_type' => null,
            'status' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $where_values = array();

        if ($args['partner_id']) {
            $where[] = 'partner_id = %d';
            $where_values[] = $args['partner_id'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ($args['access_type']) {
            $where[] = 'access_type = %s';
            $where_values[] = $args['access_type'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM {$this->access_log_table} WHERE {$where_clause}
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT {$args['limit']} OFFSET {$args['offset']}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Clean up old logs (retention policy)
     *
     * @param int $days Days to retain
     * @return int Number of deleted rows
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', time() - ($days * 24 * 60 * 60));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->access_log_table} WHERE created_at < %s",
            $cutoff_date
        ));
    }

    /**
     * Run cleanup cron job
     */
    public function run_cleanup_cron() {
        $options = get_option('htb_security_options', array());

        // Check if retention policy is enabled
        if (empty($options['retention_enabled'])) {
            return;
        }

        $retention_days = isset($options['retention_days']) ? intval($options['retention_days']) : 90;

        $deleted = $this->cleanup_old_logs($retention_days);

        // Log cleanup action
        error_log(sprintf('[Happy Turtle Security] Automatic cleanup: Deleted %d log entries older than %d days', $deleted, $retention_days));
    }
}
