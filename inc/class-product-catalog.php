<?php
/**
 * Product Catalog Management
 *
 * Manages Happy Turtle Processing product catalog with demo Arkansas cannabis products
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Product_Catalog {

    /**
     * Database table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . '1_happyturtle_products';
    }

    /**
     * Create products database table
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sku varchar(50) NOT NULL,
            product_name varchar(255) NOT NULL,
            category varchar(100) NOT NULL,
            subcategory varchar(100) DEFAULT NULL,
            description text,
            thc_percentage decimal(5,2) DEFAULT NULL,
            cbd_percentage decimal(5,2) DEFAULT NULL,
            terpene_profile text,
            unit_type varchar(50) NOT NULL,
            unit_price decimal(10,2) NOT NULL,
            minimum_order int(11) DEFAULT 1,
            stock_quantity int(11) NOT NULL DEFAULT 0,
            biotrack_id varchar(100) DEFAULT NULL,
            batch_number varchar(100) DEFAULT NULL,
            test_date date DEFAULT NULL,
            lab_results text,
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            KEY category (category),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert demo products if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        if ($count == 0) {
            $this->insert_demo_products();
        }
    }

    /**
     * Insert demo products into catalog
     */
    private function insert_demo_products() {
        global $wpdb;

        $demo_products = array(
            // CONCENTRATES - Live Resin
            array(
                'sku' => 'HTP-LR-001',
                'product_name' => 'Blue Dream Live Resin',
                'category' => 'Concentrates',
                'subcategory' => 'Live Resin',
                'description' => 'Premium live resin extracted from fresh-frozen Blue Dream flower. Smooth, flavorful, and potent with strong blueberry and sweet berry notes.',
                'thc_percentage' => 78.5,
                'cbd_percentage' => 0.8,
                'terpene_profile' => 'Myrcene, Pinene, Caryophyllene',
                'unit_type' => 'gram',
                'unit_price' => 35.00,
                'minimum_order' => 5,
                'stock_quantity' => 250,
                'batch_number' => 'BATCH-2025-001',
                'test_date' => '2025-10-01',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),
            array(
                'sku' => 'HTP-LR-002',
                'product_name' => 'Gorilla Glue #4 Live Resin',
                'category' => 'Concentrates',
                'subcategory' => 'Live Resin',
                'description' => 'Heavy-hitting live resin from GG#4. Earthy, pungent, and incredibly sticky with strong sedative effects.',
                'thc_percentage' => 82.3,
                'cbd_percentage' => 0.5,
                'terpene_profile' => 'Caryophyllene, Limonene, Myrcene',
                'unit_type' => 'gram',
                'unit_price' => 38.00,
                'minimum_order' => 5,
                'stock_quantity' => 180,
                'batch_number' => 'BATCH-2025-002',
                'test_date' => '2025-10-02',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),
            array(
                'sku' => 'HTP-LR-003',
                'product_name' => 'Wedding Cake Live Resin',
                'category' => 'Concentrates',
                'subcategory' => 'Live Resin',
                'description' => 'Dessert-like live resin with vanilla and cake batter notes. Balanced hybrid effects.',
                'thc_percentage' => 80.1,
                'cbd_percentage' => 0.6,
                'terpene_profile' => 'Limonene, Caryophyllene, Humulene',
                'unit_type' => 'gram',
                'unit_price' => 36.00,
                'minimum_order' => 5,
                'stock_quantity' => 200,
                'batch_number' => 'BATCH-2025-003',
                'test_date' => '2025-10-03',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),

            // CONCENTRATES - Shatter
            array(
                'sku' => 'HTP-SH-001',
                'product_name' => 'Pineapple Express Shatter',
                'category' => 'Concentrates',
                'subcategory' => 'Shatter',
                'description' => 'Crystal-clear shatter with tropical pineapple and citrus flavors. Energizing sativa effects.',
                'thc_percentage' => 85.7,
                'cbd_percentage' => 0.3,
                'terpene_profile' => 'Limonene, Pinene, Myrcene',
                'unit_type' => 'gram',
                'unit_price' => 32.00,
                'minimum_order' => 10,
                'stock_quantity' => 300,
                'batch_number' => 'BATCH-2025-004',
                'test_date' => '2025-10-04',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),
            array(
                'sku' => 'HTP-SH-002',
                'product_name' => 'GSC (Girl Scout Cookies) Shatter',
                'category' => 'Concentrates',
                'subcategory' => 'Shatter',
                'description' => 'Sweet and earthy shatter from classic GSC genetics. Euphoric and relaxing.',
                'thc_percentage' => 83.2,
                'cbd_percentage' => 0.4,
                'terpene_profile' => 'Caryophyllene, Limonene, Linalool',
                'unit_type' => 'gram',
                'unit_price' => 33.00,
                'minimum_order' => 10,
                'stock_quantity' => 275,
                'batch_number' => 'BATCH-2025-005',
                'test_date' => '2025-10-05',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),

            // CONCENTRATES - Wax/Crumble
            array(
                'sku' => 'HTP-WX-001',
                'product_name' => 'OG Kush Crumble',
                'category' => 'Concentrates',
                'subcategory' => 'Wax/Crumble',
                'description' => 'Classic OG Kush in easy-to-handle crumble form. Pine and lemon with relaxing effects.',
                'thc_percentage' => 79.8,
                'cbd_percentage' => 0.7,
                'terpene_profile' => 'Myrcene, Limonene, Caryophyllene',
                'unit_type' => 'gram',
                'unit_price' => 30.00,
                'minimum_order' => 10,
                'stock_quantity' => 320,
                'batch_number' => 'BATCH-2025-006',
                'test_date' => '2025-10-06',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            ),

            // VAPE CARTRIDGES
            array(
                'sku' => 'HTP-VC-001',
                'product_name' => 'Sour Diesel Vape Cartridge - 1g',
                'category' => 'Vape Cartridges',
                'subcategory' => 'Distillate + Terpenes',
                'description' => 'Premium distillate cartridge with cannabis-derived terpenes. Energizing and uplifting.',
                'thc_percentage' => 88.5,
                'cbd_percentage' => 0.2,
                'terpene_profile' => 'Limonene, Pinene, Myrcene',
                'unit_type' => 'cartridge',
                'unit_price' => 45.00,
                'minimum_order' => 25,
                'stock_quantity' => 500,
                'batch_number' => 'BATCH-2025-007',
                'test_date' => '2025-10-07',
                'lab_results' => 'Passed all Arkansas MMJ safety tests. Heavy metal testing: PASS',
                'status' => 'active'
            ),
            array(
                'sku' => 'HTP-VC-002',
                'product_name' => 'Granddaddy Purple Vape Cartridge - 1g',
                'category' => 'Vape Cartridges',
                'subcategory' => 'Distillate + Terpenes',
                'description' => 'Grape and berry flavored indica cartridge. Perfect for evening relaxation.',
                'thc_percentage' => 87.2,
                'cbd_percentage' => 0.3,
                'terpene_profile' => 'Myrcene, Pinene, Caryophyllene',
                'unit_type' => 'cartridge',
                'unit_price' => 45.00,
                'minimum_order' => 25,
                'stock_quantity' => 450,
                'batch_number' => 'BATCH-2025-008',
                'test_date' => '2025-10-08',
                'lab_results' => 'Passed all Arkansas MMJ safety tests. Heavy metal testing: PASS',
                'status' => 'active'
            ),

            // EDIBLES - Gummies
            array(
                'sku' => 'HTP-ED-GUM-001',
                'product_name' => 'Mixed Berry Gummies - 10mg THC (10pk)',
                'category' => 'Edibles',
                'subcategory' => 'Gummies',
                'description' => 'Delicious mixed berry gummies. 10mg THC per piece, 100mg total per package. Made with distillate.',
                'thc_percentage' => null,
                'cbd_percentage' => null,
                'terpene_profile' => null,
                'unit_type' => 'package',
                'unit_price' => 25.00,
                'minimum_order' => 50,
                'stock_quantity' => 800,
                'batch_number' => 'BATCH-2025-009',
                'test_date' => '2025-09-30',
                'lab_results' => 'Passed all Arkansas MMJ safety tests. Homogeneity testing: PASS',
                'status' => 'active'
            ),
            array(
                'sku' => 'HTP-ED-GUM-002',
                'product_name' => 'Watermelon Gummies - 5mg THC (20pk)',
                'category' => 'Edibles',
                'subcategory' => 'Gummies',
                'description' => 'Refreshing watermelon gummies. 5mg THC per piece, 100mg total. Perfect for micro-dosing.',
                'thc_percentage' => null,
                'cbd_percentage' => null,
                'terpene_profile' => null,
                'unit_type' => 'package',
                'unit_price' => 25.00,
                'minimum_order' => 50,
                'stock_quantity' => 750,
                'batch_number' => 'BATCH-2025-010',
                'test_date' => '2025-09-30',
                'lab_results' => 'Passed all Arkansas MMJ safety tests. Homogeneity testing: PASS',
                'status' => 'active'
            ),

            // PRE-ROLLS
            array(
                'sku' => 'HTP-PR-001',
                'product_name' => 'Blue Dream Infused Pre-Roll - 1g',
                'category' => 'Pre-Rolls',
                'subcategory' => 'Infused',
                'description' => 'Premium Blue Dream flower infused with live resin and rolled to perfection.',
                'thc_percentage' => 42.5,
                'cbd_percentage' => 0.8,
                'terpene_profile' => 'Myrcene, Pinene, Caryophyllene',
                'unit_type' => 'pre-roll',
                'unit_price' => 15.00,
                'minimum_order' => 100,
                'stock_quantity' => 1000,
                'batch_number' => 'BATCH-2025-011',
                'test_date' => '2025-10-05',
                'lab_results' => 'Passed all Arkansas MMJ safety tests',
                'status' => 'active'
            )
        );

        $current_time = current_time('mysql');

        foreach ($demo_products as $product) {
            $product['created_at'] = $current_time;
            $product['updated_at'] = $current_time;

            $wpdb->insert($this->table_name, $product);
        }
    }

    /**
     * Get all products
     *
     * @param array $args Query arguments
     * @return array Products
     */
    public function get_products($args = array()) {
        global $wpdb;

        $defaults = array(
            'category' => '',
            'status' => 'active',
            'orderby' => 'product_name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where = array("status = %s");
        $where_values = array($args['status']);

        if (!empty($args['category'])) {
            $where[] = "category = %s";
            $where_values[] = $args['category'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} {$limit_clause}",
            $where_values
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get product by ID
     *
     * @param int $product_id Product ID
     * @return array|null Product data
     */
    public function get_product($product_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $product_id),
            ARRAY_A
        );
    }

    /**
     * Get product by SKU
     *
     * @param string $sku Product SKU
     * @return array|null Product data
     */
    public function get_product_by_sku($sku) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE sku = %s", $sku),
            ARRAY_A
        );
    }

    /**
     * Get all product categories
     *
     * @return array Categories
     */
    public function get_categories() {
        global $wpdb;

        return $wpdb->get_col("SELECT DISTINCT category FROM {$this->table_name} WHERE status = 'active' ORDER BY category ASC");
    }

    /**
     * Update product stock
     *
     * @param int $product_id Product ID
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function update_stock($product_id, $quantity) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'stock_quantity' => $quantity,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $product_id),
            array('%d', '%s'),
            array('%d')
        ) !== false;
    }
}
