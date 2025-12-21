<?php
/**
 * BioTrack THC API Integration Class
 *
 * Handles all communication with Arkansas BioTrack THC system for:
 * - Authentication
 * - Inventory management
 * - Transfer and manifest generation
 * - Quality assurance integration
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_BioTrack_API {

    /**
     * BioTrack API Base URL
     */
    private $api_base_url = 'https://server.biotrackthc.net/api/';

    /**
     * API Credentials (placeholder - configure in wp-admin)
     */
    private $username;
    private $password;
    private $license_number;

    /**
     * Session token for authenticated requests
     */
    private $session_token;

    /**
     * API Version
     */
    private $api_version = '1.0';

    /**
     * Error log
     */
    private $errors = array();

    /**
     * Constructor
     */
    public function __construct() {
        // Load credentials from WordPress options
        $this->username = get_option('biotrack_username', '');
        $this->password = get_option('biotrack_password', '');
        $this->license_number = get_option('biotrack_license_number', '00340');
    }

    /**
     * Authenticate with BioTrack API
     *
     * @return bool Success status
     */
    public function authenticate() {
        if (empty($this->username) || empty($this->password)) {
            $this->log_error('Authentication failed: Missing credentials. Please configure in Settings > BioTrack API');
            return false;
        }

        $auth_data = array(
            'username' => $this->username,
            'password' => $this->password,
            'license_number' => $this->license_number
        );

        $response = $this->make_request('login', $auth_data, 'POST');

        if ($response && isset($response['session_token'])) {
            $this->session_token = $response['session_token'];
            // Store token in transient (expires in 1 hour)
            set_transient('biotrack_session_token', $this->session_token, HOUR_IN_SECONDS);
            return true;
        }

        $this->log_error('Authentication failed: Invalid credentials or server error');
        return false;
    }

    /**
     * Get current inventory from BioTrack
     *
     * @return array|false Inventory data or false on failure
     */
    public function get_inventory() {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $response = $this->make_request('inventory/get', array(), 'GET');

        if ($response && isset($response['inventory'])) {
            return $response['inventory'];
        }

        $this->log_error('Failed to retrieve inventory from BioTrack');
        return false;
    }

    /**
     * Sync inventory to BioTrack
     *
     * @param array $inventory_items Array of inventory items
     * @return bool Success status
     */
    public function sync_inventory($inventory_items) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array(
            'inventory' => $inventory_items,
            'license_number' => $this->license_number,
            'timestamp' => current_time('mysql')
        );

        $response = $this->make_request('inventory/sync', $data, 'POST');

        if ($response && isset($response['success']) && $response['success']) {
            $this->log_success('Inventory synced successfully');
            return true;
        }

        $this->log_error('Failed to sync inventory to BioTrack');
        return false;
    }

    /**
     * Create transfer in BioTrack
     *
     * @param array $transfer_data Transfer details
     * @return string|false Transfer ID or false on failure
     */
    public function create_transfer($transfer_data) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array(
            'transfer_type' => $transfer_data['type'] ?? 'outgoing',
            'destination_license' => $transfer_data['destination_license'],
            'items' => $transfer_data['items'],
            'scheduled_date' => $transfer_data['scheduled_date'],
            'vehicle_id' => $transfer_data['vehicle_id'] ?? null,
            'driver_id' => $transfer_data['driver_id'] ?? null
        );

        $response = $this->make_request('transfer/create', $data, 'POST');

        if ($response && isset($response['transfer_id'])) {
            $this->log_success('Transfer created: ' . $response['transfer_id']);
            return $response['transfer_id'];
        }

        $this->log_error('Failed to create transfer in BioTrack');
        return false;
    }

    /**
     * Generate manifest for transfer
     *
     * @param string $transfer_id BioTrack transfer ID
     * @return array|false Manifest data or false on failure
     */
    public function generate_manifest($transfer_id) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array('transfer_id' => $transfer_id);

        $response = $this->make_request('manifest/generate', $data, 'POST');

        if ($response && isset($response['manifest'])) {
            $this->log_success('Manifest generated for transfer: ' . $transfer_id);
            return $response['manifest'];
        }

        $this->log_error('Failed to generate manifest for transfer: ' . $transfer_id);
        return false;
    }

    /**
     * Update transfer status
     *
     * @param string $transfer_id BioTrack transfer ID
     * @param string $status New status
     * @return bool Success status
     */
    public function update_transfer_status($transfer_id, $status) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array(
            'transfer_id' => $transfer_id,
            'status' => $status,
            'timestamp' => current_time('mysql')
        );

        $response = $this->make_request('transfer/update', $data, 'POST');

        if ($response && isset($response['success']) && $response['success']) {
            $this->log_success('Transfer status updated: ' . $transfer_id . ' -> ' . $status);
            return true;
        }

        $this->log_error('Failed to update transfer status: ' . $transfer_id);
        return false;
    }

    /**
     * Get quality assurance tests
     *
     * @param string $batch_id Batch ID to get tests for
     * @return array|false QA test data or false on failure
     */
    public function get_qa_tests($batch_id) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array('batch_id' => $batch_id);

        $response = $this->make_request('qa/get_tests', $data, 'GET');

        if ($response && isset($response['tests'])) {
            return $response['tests'];
        }

        $this->log_error('Failed to retrieve QA tests for batch: ' . $batch_id);
        return false;
    }

    /**
     * Submit quality assurance test results
     *
     * @param array $test_data QA test results
     * @return bool Success status
     */
    public function submit_qa_test($test_data) {
        if (!$this->ensure_authenticated()) {
            return false;
        }

        $data = array(
            'batch_id' => $test_data['batch_id'],
            'test_type' => $test_data['test_type'],
            'results' => $test_data['results'],
            'test_date' => $test_data['test_date'],
            'lab_name' => $test_data['lab_name'] ?? 'Happy Turtle Processing QA Lab'
        );

        $response = $this->make_request('qa/submit_test', $data, 'POST');

        if ($response && isset($response['success']) && $response['success']) {
            $this->log_success('QA test submitted for batch: ' . $test_data['batch_id']);
            return true;
        }

        $this->log_error('Failed to submit QA test for batch: ' . $test_data['batch_id']);
        return false;
    }

    /**
     * Make API request to BioTrack
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array|false Response data or false on failure
     */
    private function make_request($endpoint, $data = array(), $method = 'GET') {
        $url = $this->api_base_url . $endpoint;

        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            )
        );

        // Add session token if available
        if ($this->session_token) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->session_token;
        }

        // Add data to request
        if ($method === 'POST' || $method === 'PUT') {
            $args['body'] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }

        // DEMO MODE: If credentials not configured, return mock data
        if (empty($this->username) || empty($this->password)) {
            return $this->get_mock_response($endpoint);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('API request failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300) {
            return $decoded;
        }

        $this->log_error('API request failed with status: ' . $status_code);
        return false;
    }

    /**
     * Get mock response for demo mode
     *
     * @param string $endpoint API endpoint
     * @return array Mock response data
     */
    private function get_mock_response($endpoint) {
        $mock_responses = array(
            'login' => array(
                'success' => true,
                'session_token' => 'DEMO_TOKEN_' . time(),
                'message' => 'Demo mode - configure credentials in Settings > BioTrack API'
            ),
            'inventory/get' => array(
                'inventory' => array(
                    array(
                        'id' => 'INV-001',
                        'product_name' => 'Blue Dream Live Resin',
                        'quantity' => 250,
                        'unit' => 'grams',
                        'batch_id' => 'BATCH-2025-001'
                    ),
                    array(
                        'id' => 'INV-002',
                        'product_name' => 'Gorilla Glue #4 Shatter',
                        'quantity' => 180,
                        'unit' => 'grams',
                        'batch_id' => 'BATCH-2025-002'
                    )
                )
            ),
            'transfer/create' => array(
                'success' => true,
                'transfer_id' => 'TRANSFER-' . date('Ymd') . '-' . rand(1000, 9999),
                'message' => 'Demo transfer created'
            ),
            'manifest/generate' => array(
                'manifest' => array(
                    'manifest_id' => 'MANIFEST-' . date('Ymd') . '-' . rand(1000, 9999),
                    'transfer_id' => 'TRANSFER-DEMO',
                    'created_date' => current_time('mysql'),
                    'status' => 'pending'
                )
            )
        );

        // Return mock response or generic success
        return $mock_responses[$endpoint] ?? array('success' => true, 'message' => 'Demo mode response');
    }

    /**
     * Ensure we have valid authentication
     *
     * @return bool Authentication status
     */
    private function ensure_authenticated() {
        // Check if we have a valid session token
        $token = get_transient('biotrack_session_token');

        if ($token) {
            $this->session_token = $token;
            return true;
        }

        // Try to authenticate
        return $this->authenticate();
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     */
    private function log_error($message) {
        $this->errors[] = array(
            'timestamp' => current_time('mysql'),
            'message' => $message
        );

        error_log('[HappyTurtle BioTrack API] ERROR: ' . $message);
    }

    /**
     * Log success message
     *
     * @param string $message Success message
     */
    private function log_success($message) {
        error_log('[HappyTurtle BioTrack API] SUCCESS: ' . $message);
    }

    /**
     * Get recent errors
     *
     * @return array Error log
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Test connection to BioTrack API
     *
     * @return array Connection test results
     */
    public function test_connection() {
        $results = array(
            'connection' => false,
            'authentication' => false,
            'message' => ''
        );

        if (empty($this->username) || empty($this->password)) {
            $results['message'] = 'Demo mode active - configure credentials to connect to BioTrack';
            $results['connection'] = true; // Demo mode is "connected"
            $results['authentication'] = false;
            return $results;
        }

        // Test authentication
        if ($this->authenticate()) {
            $results['connection'] = true;
            $results['authentication'] = true;
            $results['message'] = 'Successfully connected to BioTrack API';
        } else {
            $results['message'] = 'Failed to authenticate with BioTrack - check credentials';
        }

        return $results;
    }
}
