<?php
/**
 * REST API for B2B Partners
 *
 * Provides REST API endpoints for partner integrations
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_REST_API {

    /**
     * API namespace
     */
    private $namespace = 'happyturtle/v1';

    /**
     * Partner management instance
     */
    private $partner_manager;

    /**
     * Product catalog instance
     */
    private $product_catalog;

    /**
     * Constructor
     */
    public function __construct() {
        $this->partner_manager = new HappyTurtle_Partner_Management();
        $this->product_catalog = new HappyTurtle_Product_Catalog();
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Partner registration
        register_rest_route($this->namespace, '/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_partner'),
            'permission_callback' => '__return_true'
        ));

        // Get products
        register_rest_route($this->namespace, '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Get single product
        register_rest_route($this->namespace, '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Get product categories
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Create order
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_order'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Get orders
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Get single order
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));

        // Get current inventory levels
        register_rest_route($this->namespace, '/inventory', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_inventory'),
            'permission_callback' => array($this, 'check_api_credentials')
        ));
    }

    /**
     * Check API credentials
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error Permission status
     */
    public function check_api_credentials($request) {
        $api_key = $request->get_header('X-API-Key');
        $api_secret = $request->get_header('X-API-Secret');

        if (empty($api_key) || empty($api_secret)) {
            return new WP_Error(
                'missing_credentials',
                'API credentials required',
                array('status' => 401)
            );
        }

        $credentials = $this->partner_manager->verify_api_credentials($api_key, $api_secret);

        if (!$credentials) {
            return new WP_Error(
                'invalid_credentials',
                'Invalid API credentials',
                array('status' => 401)
            );
        }

        // Store partner data in request for later use
        $request->set_param('_partner_data', $credentials);

        return true;
    }

    /**
     * Register new partner
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function register_partner($request) {
        $data = array(
            'business_name' => $request->get_param('business_name'),
            'contact_name' => $request->get_param('contact_name'),
            'email' => $request->get_param('email'),
            'phone' => $request->get_param('phone'),
            'address' => $request->get_param('address'),
            'city' => $request->get_param('city'),
            'state' => $request->get_param('state'),
            'zip' => $request->get_param('zip'),
            'license_number' => $request->get_param('license_number'),
            'license_type' => $request->get_param('license_type'),
            'ein' => $request->get_param('ein'),
            'biotrack_license' => $request->get_param('biotrack_license')
        );

        $result = $this->partner_manager->register_partner($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Registration submitted successfully. You will receive an email once your account is approved.',
            'partner_id' => $result
        ), 201);
    }

    /**
     * Get products
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_products($request) {
        $args = array(
            'category' => $request->get_param('category'),
            'status' => 'active'
        );

        $products = $this->product_catalog->get_products($args);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $products,
            'count' => count($products)
        ), 200);
    }

    /**
     * Get single product
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_product($request) {
        $product_id = $request->get_param('id');
        $product = $this->product_catalog->get_product($product_id);

        if (!$product) {
            return new WP_Error(
                'product_not_found',
                'Product not found',
                array('status' => 404)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $product
        ), 200);
    }

    /**
     * Get product categories
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_categories($request) {
        $categories = $this->product_catalog->get_categories();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $categories,
            'count' => count($categories)
        ), 200);
    }

    /**
     * Create order
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function create_order($request) {
        $partner_data = $request->get_param('_partner_data');
        $partner_id = $partner_data['partner']['id'];

        $items = $request->get_param('items');

        if (empty($items) || !is_array($items)) {
            return new WP_Error(
                'invalid_items',
                'Order must contain at least one item',
                array('status' => 400)
            );
        }

        // Validate and enrich items with product data
        $validated_items = array();
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                return new WP_Error(
                    'invalid_item',
                    'Each item must have product_id and quantity',
                    array('status' => 400)
                );
            }

            $product = $this->product_catalog->get_product($item['product_id']);

            if (!$product) {
                return new WP_Error(
                    'product_not_found',
                    'Product not found: ' . $item['product_id'],
                    array('status' => 404)
                );
            }

            // Check stock
            if ($product['stock_quantity'] < $item['quantity']) {
                return new WP_Error(
                    'insufficient_stock',
                    'Insufficient stock for product: ' . $product['product_name'],
                    array('status' => 400)
                );
            }

            // Check minimum order
            if ($item['quantity'] < $product['minimum_order']) {
                return new WP_Error(
                    'below_minimum',
                    sprintf('Minimum order for %s is %d %s', $product['product_name'], $product['minimum_order'], $product['unit_type']),
                    array('status' => 400)
                );
            }

            $validated_items[] = array(
                'product_id' => $product['id'],
                'sku' => $product['sku'],
                'product_name' => $product['product_name'],
                'quantity' => intval($item['quantity']),
                'unit_type' => $product['unit_type'],
                'unit_price' => floatval($product['unit_price']),
                'subtotal' => floatval($product['unit_price']) * intval($item['quantity'])
            );
        }

        $order_id = $this->partner_manager->create_order($partner_id, $validated_items);

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Order created successfully and pending approval',
            'order_id' => $order_id
        ), 201);
    }

    /**
     * Get orders
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_orders($request) {
        $partner_data = $request->get_param('_partner_data');
        $partner_id = $partner_data['partner']['id'];

        $args = array(
            'status' => $request->get_param('status'),
            'limit' => $request->get_param('limit') ?: 50,
            'offset' => $request->get_param('offset') ?: 0
        );

        $orders = $this->partner_manager->get_partner_orders($partner_id, $args);

        // Decode items JSON for each order
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $orders,
            'count' => count($orders)
        ), 200);
    }

    /**
     * Get single order
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_order($request) {
        global $wpdb;

        $partner_data = $request->get_param('_partner_data');
        $partner_id = $partner_data['partner']['id'];
        $order_id = $request->get_param('id');

        $orders_table = $wpdb->prefix . '1_happyturtle_orders';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$orders_table} WHERE id = %d AND partner_id = %d",
            $order_id,
            $partner_id
        ), ARRAY_A);

        if (!$order) {
            return new WP_Error(
                'order_not_found',
                'Order not found',
                array('status' => 404)
            );
        }

        $order['items'] = json_decode($order['items'], true);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $order
        ), 200);
    }

    /**
     * Get inventory levels
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_inventory($request) {
        $category = $request->get_param('category');

        $args = array(
            'category' => $category,
            'status' => 'active'
        );

        $products = $this->product_catalog->get_products($args);

        // Format for inventory view
        $inventory = array();
        foreach ($products as $product) {
            $inventory[] = array(
                'product_id' => $product['id'],
                'sku' => $product['sku'],
                'product_name' => $product['product_name'],
                'category' => $product['category'],
                'stock_quantity' => $product['stock_quantity'],
                'unit_type' => $product['unit_type'],
                'unit_price' => $product['unit_price'],
                'minimum_order' => $product['minimum_order'],
                'available' => $product['stock_quantity'] >= $product['minimum_order']
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $inventory,
            'count' => count($inventory)
        ), 200);
    }
}
