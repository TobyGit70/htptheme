<?php
/**
 * API Security Middleware
 *
 * Intercepts all REST API requests to add logging and rate limiting
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_API_Security_Middleware {

    /**
     * Security logger instance
     */
    private $security_logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->security_logger = HappyTurtle_Security_Logger::get_instance();

        // Hook into REST API request lifecycle
        add_filter('rest_pre_dispatch', array($this, 'log_api_request_start'), 10, 3);
        add_filter('rest_post_dispatch', array($this, 'log_api_request_end'), 10, 3);
        add_filter('rest_request_before_callbacks', array($this, 'check_rate_limit'), 10, 3);
    }

    /**
     * Log API request start
     *
     * @param mixed $result Response
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request object
     * @return mixed Response
     */
    public function log_api_request_start($result, $server, $request) {
        // Only log Happy Turtle API requests
        if (strpos($request->get_route(), '/happyturtle/v1/') === false) {
            return $result;
        }

        // Store request start time for performance tracking
        $request->set_param('_request_start_time', microtime(true));

        return $result;
    }

    /**
     * Log API request completion
     *
     * @param WP_REST_Response $response Response object
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function log_api_request_end($response, $server, $request) {
        // Only log Happy Turtle API requests
        if (strpos($request->get_route(), '/happyturtle/v1/') === false) {
            return $response;
        }

        $start_time = $request->get_param('_request_start_time');
        $execution_time = $start_time ? (microtime(true) - $start_time) : null;

        // Get partner data if authenticated
        $partner_data = $request->get_param('_partner_data');
        $partner_id = $partner_data ? $partner_data['partner']['id'] : null;

        // Determine status
        $status_code = $response->get_status();
        $status = $status_code >= 200 && $status_code < 300 ? 'success' : 'failed';

        if ($status_code == 401 || $status_code == 403) {
            $status = 'failed';
        } elseif ($status_code == 429) {
            $status = 'blocked';
        }

        // Get response data (sanitized)
        $response_data = $response->get_data();
        if (isset($response_data['api_key']) || isset($response_data['api_secret'])) {
            $response_data = '[CREDENTIALS REDACTED]';
        }

        // Log the request
        $this->security_logger->log_access(array(
            'partner_id' => $partner_id,
            'access_type' => 'api',
            'event_type' => $this->get_event_type($request->get_route(), $request->get_method()),
            'endpoint' => $request->get_route(),
            'method' => $request->get_method(),
            'status' => $status,
            'status_code' => $status_code,
            'request_data' => $request->get_params(),
            'response_data' => is_string($response_data) ? $response_data : json_encode($response_data),
            'execution_time' => $execution_time
        ));

        return $response;
    }

    /**
     * Check rate limit before executing callbacks
     *
     * @param WP_REST_Response|null $response Response object
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|null Response
     */
    public function check_rate_limit($response, $server, $request) {
        // Only check Happy Turtle API requests
        if (strpos($request->get_route(), '/happyturtle/v1/') === false) {
            return $response;
        }

        // Skip rate limit for registration endpoint (public)
        if ($request->get_route() === '/happyturtle/v1/register') {
            // Use IP-based rate limit for registration
            $rate_check = $this->security_logger->check_rate_limit(
                $this->security_logger->get_client_ip(),
                'registration'
            );
        } else {
            // Get API key for rate limiting
            $api_key = $request->get_header('X-API-Key');
            if ($api_key) {
                $rate_check = $this->security_logger->check_rate_limit($api_key, 'api');
            } else {
                // No API key - use IP-based rate limit
                $rate_check = $this->security_logger->check_rate_limit(
                    $this->security_logger->get_client_ip(),
                    'api'
                );
            }
        }

        if (is_wp_error($rate_check)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $rate_check->get_error_code(),
                'message' => $rate_check->get_error_message(),
                'data' => $rate_check->get_error_data()
            ), 429);
        }

        return $response;
    }

    /**
     * Determine event type from route and method
     *
     * @param string $route Route
     * @param string $method HTTP method
     * @return string Event type
     */
    private function get_event_type($route, $method) {
        if ($route === '/happyturtle/v1/register') {
            return 'partner_registration';
        } elseif (strpos($route, '/products') !== false) {
            return $method === 'GET' ? 'view_products' : 'manage_products';
        } elseif (strpos($route, '/orders') !== false) {
            return $method === 'POST' ? 'create_order' : 'view_orders';
        } elseif (strpos($route, '/categories') !== false) {
            return 'view_categories';
        } elseif (strpos($route, '/inventory') !== false) {
            return 'view_inventory';
        }

        return 'api_request';
    }
}

// Initialize middleware
new HappyTurtle_API_Security_Middleware();
