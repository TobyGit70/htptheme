<?php
/**
 * Partner Management System
 *
 * Manages B2B partner registration, approval, and API key generation
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Partner_Management {

    /**
     * Database table names
     */
    private $partners_table;
    private $api_keys_table;
    private $orders_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->partners_table = $wpdb->prefix . '1_happyturtle_partners';
        $this->api_keys_table = $wpdb->prefix . '1_happyturtle_api_keys';
        $this->orders_table = $wpdb->prefix . '1_happyturtle_orders';
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Partners table
        $sql_partners = "CREATE TABLE IF NOT EXISTS {$this->partners_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_name varchar(255) NOT NULL,
            contact_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            state varchar(2) NOT NULL DEFAULT 'AR',
            zip varchar(10) NOT NULL,
            license_number varchar(100) NOT NULL,
            license_type varchar(50) NOT NULL,
            ein varchar(20) NOT NULL,
            license_verified tinyint(1) DEFAULT 0,
            license_verified_date datetime DEFAULT NULL,
            verified_by bigint(20) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            biotrack_license varchar(100) DEFAULT NULL,
            notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY license_number (license_number),
            KEY status (status)
        ) $charset_collate;";

        // API Keys table
        $sql_api_keys = "CREATE TABLE IF NOT EXISTS {$this->api_keys_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) NOT NULL,
            api_key varchar(64) NOT NULL,
            api_secret varchar(64) NOT NULL,
            status varchar(20) DEFAULT 'active',
            permissions text,
            last_used datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY partner_id (partner_id),
            KEY status (status)
        ) $charset_collate;";

        // Orders table
        $sql_orders = "CREATE TABLE IF NOT EXISTS {$this->orders_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            partner_id bigint(20) NOT NULL,
            order_date datetime NOT NULL,
            status varchar(50) DEFAULT 'pending',
            items text NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            tax decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL,
            payment_status varchar(50) DEFAULT 'pending',
            biotrack_transfer_id varchar(100) DEFAULT NULL,
            biotrack_manifest_id varchar(100) DEFAULT NULL,
            scheduled_pickup datetime DEFAULT NULL,
            actual_pickup datetime DEFAULT NULL,
            delivery_status varchar(50) DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY partner_id (partner_id),
            KEY status (status),
            KEY order_date (order_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_partners);
        dbDelta($sql_api_keys);
        dbDelta($sql_orders);
    }

    /**
     * Register new partner
     *
     * @param array $data Partner registration data
     * @return int|WP_Error Partner ID or error
     */
    public function register_partner($data) {
        global $wpdb;

        // Validate required fields
        $required_fields = array('business_name', 'contact_name', 'email', 'phone', 'address', 'city', 'zip', 'license_number', 'license_type', 'ein');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', 'Required field missing: ' . $field);
            }
        }

        // Validate email
        if (!is_email($data['email'])) {
            return new WP_Error('invalid_email', 'Invalid email address');
        }

        // Check for duplicate email or license
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->partners_table} WHERE email = %s OR license_number = %s",
            $data['email'],
            $data['license_number']
        ));

        if ($existing) {
            return new WP_Error('duplicate_partner', 'A partner with this email or license number already exists');
        }

        // Prepare partner data
        $partner_data = array(
            'business_name' => sanitize_text_field($data['business_name']),
            'contact_name' => sanitize_text_field($data['contact_name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'address' => sanitize_textarea_field($data['address']),
            'city' => sanitize_text_field($data['city']),
            'state' => isset($data['state']) ? sanitize_text_field($data['state']) : 'AR',
            'zip' => sanitize_text_field($data['zip']),
            'license_number' => sanitize_text_field($data['license_number']),
            'license_type' => sanitize_text_field($data['license_type']),
            'ein' => sanitize_text_field($data['ein']),
            'biotrack_license' => isset($data['biotrack_license']) ? sanitize_text_field($data['biotrack_license']) : null,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->partners_table, $partner_data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to register partner');
        }

        $partner_id = $wpdb->insert_id;

        // Send notification email to admin
        $this->send_registration_notification($partner_id);

        return $partner_id;
    }

    /**
     * Approve partner and generate API key
     *
     * @param int $partner_id Partner ID
     * @param int $admin_user_id Admin user ID who approved
     * @return bool|WP_Error Success status or error
     */
    public function approve_partner($partner_id, $admin_user_id) {
        global $wpdb;

        $partner = $this->get_partner($partner_id);
        if (!$partner) {
            return new WP_Error('invalid_partner', 'Partner not found');
        }

        // Update partner status
        $updated = $wpdb->update(
            $this->partners_table,
            array(
                'status' => 'active',
                'license_verified' => 1,
                'license_verified_date' => current_time('mysql'),
                'verified_by' => $admin_user_id,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $partner_id),
            array('%s', '%d', '%s', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to update partner status');
        }

        // Generate API key
        $api_key_result = $this->generate_api_key($partner_id);

        if (is_wp_error($api_key_result)) {
            return $api_key_result;
        }

        // Send approval email with API credentials
        $this->send_approval_email($partner_id, $api_key_result);

        return true;
    }

    /**
     * Generate API key for partner
     *
     * @param int $partner_id Partner ID
     * @return array|WP_Error API credentials or error
     */
    public function generate_api_key($partner_id) {
        global $wpdb;

        $partner = $this->get_partner($partner_id);
        if (!$partner) {
            return new WP_Error('invalid_partner', 'Partner not found');
        }

        // Generate unique API key and secret
        $api_key = 'htb_' . bin2hex(random_bytes(24));
        $api_secret = bin2hex(random_bytes(32));

        // Default permissions
        $permissions = array(
            'view_products' => true,
            'create_orders' => true,
            'view_orders' => true,
            'view_inventory' => true
        );

        $api_data = array(
            'partner_id' => $partner_id,
            'api_key' => $api_key,
            'api_secret' => password_hash($api_secret, PASSWORD_DEFAULT),
            'status' => 'active',
            'permissions' => json_encode($permissions),
            'expires_at' => null, // No expiration by default
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->api_keys_table, $api_data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to generate API key');
        }

        return array(
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'partner_id' => $partner_id
        );
    }

    /**
     * Verify API credentials
     *
     * @param string $api_key API key
     * @param string $api_secret API secret
     * @return array|false Partner data or false
     */
    public function verify_api_credentials($api_key, $api_secret) {
        global $wpdb;

        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->api_keys_table} WHERE api_key = %s AND status = 'active'",
            $api_key
        ), ARRAY_A);

        if (!$key_data) {
            return false;
        }

        // Check expiration
        if ($key_data['expires_at'] && strtotime($key_data['expires_at']) < time()) {
            return false;
        }

        // Verify secret
        if (!password_verify($api_secret, $key_data['api_secret'])) {
            return false;
        }

        // Update last used timestamp
        $wpdb->update(
            $this->api_keys_table,
            array('last_used' => current_time('mysql')),
            array('id' => $key_data['id']),
            array('%s'),
            array('%d')
        );

        // Get partner data
        $partner = $this->get_partner($key_data['partner_id']);

        if (!$partner || $partner['status'] !== 'active') {
            return false;
        }

        return array(
            'partner' => $partner,
            'permissions' => json_decode($key_data['permissions'], true)
        );
    }

    /**
     * Get partner by ID
     *
     * @param int $partner_id Partner ID
     * @return array|null Partner data
     */
    public function get_partner($partner_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->partners_table} WHERE id = %d", $partner_id),
            ARRAY_A
        );
    }

    /**
     * Get all partners
     *
     * @param array $args Query arguments
     * @return array Partners
     */
    public function get_partners($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where = array();
        $where_values = array();

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->partners_table} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} {$limit_clause}",
                $where_values
            );
        } else {
            $query = "SELECT * FROM {$this->partners_table} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} {$limit_clause}";
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Send registration notification to admin
     *
     * @param int $partner_id Partner ID
     */
    private function send_registration_notification($partner_id) {
        $partner = $this->get_partner($partner_id);

        $admin_email = get_option('admin_email');
        $subject = 'New Partner Registration - Happy Turtle Processing';

        $message = sprintf(
            "New partner registration requires review:\n\n" .
            "Business Name: %s\n" .
            "Contact: %s\n" .
            "Email: %s\n" .
            "Phone: %s\n" .
            "License Number: %s\n" .
            "License Type: %s\n\n" .
            "Review and approve in wp-admin: %s",
            $partner['business_name'],
            $partner['contact_name'],
            $partner['email'],
            $partner['phone'],
            $partner['license_number'],
            $partner['license_type'],
            admin_url('admin.php?page=happyturtle-partners')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send approval email to partner
     *
     * @param int $partner_id Partner ID
     * @param array $api_credentials API key and secret
     */
    private function send_approval_email($partner_id, $api_credentials) {
        $partner = $this->get_partner($partner_id);

        $subject = 'Welcome to Happy Turtle Processing - API Access Approved';

        $message = sprintf(
            "Hello %s,\n\n" .
            "Congratulations! Your Happy Turtle Processing partner account has been approved.\n\n" .
            "Your API Credentials:\n" .
            "API Key: %s\n" .
            "API Secret: %s\n\n" .
            "IMPORTANT: Store these credentials securely. The API secret will not be shown again.\n\n" .
            "API Documentation: %s\n" .
            "Partner Portal: %s\n\n" .
            "You can now begin placing orders through our API or partner portal.\n\n" .
            "For support, contact: compliance@happyturtleprocessing.com\n\n" .
            "Thank you for partnering with Happy Turtle Processing!",
            $partner['contact_name'],
            $api_credentials['api_key'],
            $api_credentials['api_secret'],
            home_url('/api-docs/'),
            home_url('/partner-portal/')
        );

        wp_mail($partner['email'], $subject, $message);
    }

    /**
     * Create order for partner
     *
     * @param int $partner_id Partner ID
     * @param array $items Order items
     * @return int|WP_Error Order ID or error
     */
    public function create_order($partner_id, $items) {
        global $wpdb;

        $partner = $this->get_partner($partner_id);
        if (!$partner || $partner['status'] !== 'active') {
            return new WP_Error('invalid_partner', 'Partner not found or not active');
        }

        // Generate order number
        $order_number = 'HTP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $tax = 0; // Arkansas sales tax - calculate based on jurisdiction
        $total = $subtotal + $tax;

        $order_data = array(
            'order_number' => $order_number,
            'partner_id' => $partner_id,
            'order_date' => current_time('mysql'),
            'status' => 'pending_approval',
            'items' => json_encode($items),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->orders_table, $order_data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create order');
        }

        return $wpdb->insert_id;
    }

    /**
     * Get orders for partner
     *
     * @param int $partner_id Partner ID
     * @param array $args Query arguments
     * @return array Orders
     */
    public function get_partner_orders($partner_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'orderby' => 'order_date',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where = array("partner_id = %d");
        $where_values = array($partner_id);

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->orders_table} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} {$limit_clause}",
            $where_values
        );

        return $wpdb->get_results($query, ARRAY_A);
    }
}
