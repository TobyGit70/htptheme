<?php
/**
 * Product Export System
 *
 * Allows partners to export products in multiple formats for their own systems
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Product_Export {

    private $product_catalog;

    public function __construct() {
        $this->product_catalog = new HappyTurtle_Product_Catalog();

        // Register REST API endpoints for exports
        add_action('rest_api_init', array($this, 'register_export_routes'));

        // Add AJAX handlers for admin export
        add_action('wp_ajax_htb_export_products', array($this, 'handle_ajax_export'));
    }

    /**
     * Register REST API routes for exports
     */
    public function register_export_routes() {
        // Export products endpoint (requires authentication)
        register_rest_route('happyturtle/v1', '/export/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_products_api'),
            'permission_callback' => array($this, 'check_partner_permission'),
            'args' => array(
                'format' => array(
                    'required' => false,
                    'default' => 'woocommerce',
                    'enum' => array('woocommerce', 'csv', 'json')
                ),
                'ids' => array(
                    'required' => false,
                    'description' => 'Comma-separated product IDs to export'
                )
            )
        ));
    }

    /**
     * Check if partner has permission to export
     */
    public function check_partner_permission($request) {
        // Check if user is logged in or has valid API credentials
        if (is_user_logged_in()) {
            return current_user_can('read');
        }

        // Check for API authentication
        $api_key = $request->get_header('X-API-Key');
        $api_secret = $request->get_header('X-API-Secret');

        if (empty($api_key) || empty($api_secret)) {
            return false;
        }

        // Verify API credentials
        $partner_manager = new HappyTurtle_Partner_Management();
        $partner = $partner_manager->get_partner_by_api_key($api_key);

        if (!$partner || !password_verify($api_secret, $partner['api_secret_hash'])) {
            return false;
        }

        return true;
    }

    /**
     * API endpoint to export products
     */
    public function export_products_api($request) {
        $format = $request->get_param('format');
        $ids = $request->get_param('ids');

        // Get product IDs to export
        $product_ids = array();
        if (!empty($ids)) {
            $product_ids = array_map('intval', explode(',', $ids));
        }

        // Get products
        $products = $this->get_products_for_export($product_ids);

        if (empty($products)) {
            return new WP_Error('no_products', 'No products found to export', array('status' => 404));
        }

        // Format based on requested format
        switch ($format) {
            case 'woocommerce':
                $data = $this->format_woocommerce($products);
                break;
            case 'csv':
                return $this->export_csv($products);
            case 'json':
            default:
                $data = $this->format_json($products);
                break;
        }

        return rest_ensure_response($data);
    }

    /**
     * Get products for export
     *
     * @param array $product_ids Optional specific product IDs
     * @return array Products data
     */
    private function get_products_for_export($product_ids = array()) {
        global $wpdb;
        $table = $wpdb->prefix . '1_happyturtle_products';

        if (empty($product_ids)) {
            // Get all products
            $products = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        } else {
            // Get specific products
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $products = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id IN ($placeholders) AND status = 'active' ORDER BY name ASC", $product_ids),
                ARRAY_A
            );
        }

        return $products;
    }

    /**
     * Format products for WooCommerce import
     *
     * WooCommerce CSV Import format
     * https://woocommerce.com/document/product-csv-importer-exporter/
     */
    public function format_woocommerce($products) {
        $formatted = array();

        foreach ($products as $product) {
            $formatted[] = array(
                'ID' => $product['biotrack_id'], // Use BioTrack ID as SKU
                'Type' => 'simple',
                'SKU' => $product['biotrack_id'],
                'Name' => $product['name'],
                'Published' => 1,
                'Is featured?' => 0,
                'Visibility in catalog' => 'visible',
                'Short description' => $this->truncate($product['description'], 140),
                'Description' => $product['description'],
                'Date sale price starts' => '',
                'Date sale price ends' => '',
                'Tax status' => 'taxable',
                'Tax class' => '',
                'In stock?' => ($product['stock_quantity'] > 0) ? 1 : 0,
                'Stock' => $product['stock_quantity'],
                'Backorders allowed?' => 0,
                'Sold individually?' => 0,
                'Weight (lbs)' => $this->convert_grams_to_pounds($product['weight_grams']),
                'Length (in)' => '',
                'Width (in)' => '',
                'Height (in)' => '',
                'Allow customer reviews?' => 1,
                'Purchase note' => '',
                'Sale price' => '',
                'Regular price' => number_format($product['price'], 2, '.', ''),
                'Categories' => $this->format_categories($product),
                'Tags' => $this->format_tags($product),
                'Images' => '', // Will be empty - partners can add their own images
                'Download limit' => '',
                'Download expiry days' => '',
                'Parent' => '',
                'Grouped products' => '',
                'Upsells' => '',
                'Cross-sells' => '',
                'External URL' => '',
                'Button text' => '',
                'Position' => 0,
                'Meta: biotrack_id' => $product['biotrack_id'],
                'Meta: thc_content' => $product['thc_content'],
                'Meta: cbd_content' => $product['cbd_content'],
                'Meta: product_type' => $product['product_type'],
                'Meta: strain' => $product['strain'],
                'Meta: supplier' => $product['supplier']
            );
        }

        return $formatted;
    }

    /**
     * Format products as JSON
     */
    public function format_json($products) {
        $formatted = array();

        foreach ($products as $product) {
            $formatted[] = array(
                'id' => intval($product['id']),
                'biotrack_id' => $product['biotrack_id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'product_type' => $product['product_type'],
                'strain' => $product['strain'],
                'thc_content' => floatval($product['thc_content']),
                'cbd_content' => floatval($product['cbd_content']),
                'price' => floatval($product['price']),
                'stock_quantity' => intval($product['stock_quantity']),
                'weight_grams' => floatval($product['weight_grams']),
                'supplier' => $product['supplier'],
                'batch_number' => $product['batch_number'],
                'harvest_date' => $product['harvest_date'],
                'status' => $product['status'],
                'created_at' => $product['created_at'],
                'updated_at' => $product['updated_at']
            );
        }

        return array(
            'success' => true,
            'count' => count($formatted),
            'products' => $formatted
        );
    }

    /**
     * Export products as CSV
     */
    public function export_csv($products) {
        $filename = 'happyturtle-products-' . date('Y-m-d-His') . '.csv';

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // CSV headers
        $headers = array(
            'BioTrack ID',
            'Product Name',
            'Description',
            'Product Type',
            'Strain',
            'THC %',
            'CBD %',
            'Price',
            'Stock Quantity',
            'Weight (grams)',
            'Supplier',
            'Batch Number',
            'Harvest Date',
            'Status'
        );

        fputcsv($output, $headers);

        // Add product rows
        foreach ($products as $product) {
            $row = array(
                $product['biotrack_id'],
                $product['name'],
                $product['description'],
                $product['product_type'],
                $product['strain'],
                $product['thc_content'],
                $product['cbd_content'],
                number_format($product['price'], 2, '.', ''),
                $product['stock_quantity'],
                $product['weight_grams'],
                $product['supplier'],
                $product['batch_number'],
                $product['harvest_date'],
                $product['status']
            );

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Export as WooCommerce CSV format
     */
    public function export_woocommerce_csv($products) {
        $filename = 'woocommerce-products-' . date('Y-m-d-His') . '.csv';

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Get WooCommerce formatted data
        $formatted = $this->format_woocommerce($products);

        if (!empty($formatted)) {
            // Write headers (keys from first product)
            fputcsv($output, array_keys($formatted[0]));

            // Write data rows
            foreach ($formatted as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Handle AJAX export request
     */
    public function handle_ajax_export() {
        check_ajax_referer('htb_export_products', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error('Unauthorized');
        }

        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();

        $products = $this->get_products_for_export($product_ids);

        if (empty($products)) {
            wp_send_json_error('No products found');
        }

        // Handle different formats
        if ($format === 'csv') {
            $this->export_csv($products);
        } elseif ($format === 'woocommerce') {
            $this->export_woocommerce_csv($products);
        } else {
            $data = $this->format_json($products);
            wp_send_json_success($data);
        }
    }

    /**
     * Helper: Convert grams to pounds
     */
    private function convert_grams_to_pounds($grams) {
        return number_format($grams * 0.00220462, 4, '.', '');
    }

    /**
     * Helper: Format categories
     */
    private function format_categories($product) {
        $categories = array();

        // Add product type as category
        if (!empty($product['product_type'])) {
            $categories[] = ucfirst($product['product_type']);
        }

        // Add strain type if available
        if (!empty($product['strain'])) {
            $categories[] = ucfirst($product['strain']);
        }

        return implode(', ', $categories);
    }

    /**
     * Helper: Format tags
     */
    private function format_tags($product) {
        $tags = array();

        // Add THC/CBD tags
        if ($product['thc_content'] > 20) {
            $tags[] = 'High THC';
        }

        if ($product['cbd_content'] > 10) {
            $tags[] = 'High CBD';
        }

        // Add supplier as tag
        if (!empty($product['supplier'])) {
            $tags[] = $product['supplier'];
        }

        return implode(', ', $tags);
    }

    /**
     * Helper: Truncate text
     */
    private function truncate($text, $length = 140) {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}

// Initialize product export
new HappyTurtle_Product_Export();
