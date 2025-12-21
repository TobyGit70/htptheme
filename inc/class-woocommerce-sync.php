<?php
/**
 * WooCommerce Product Sync
 *
 * Syncs B2B products to WooCommerce for retail sales
 * Maintains separate inventories and pricing
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_WooCommerce_Sync {

    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Hook into B2B product saves
        add_action('happyturtle_product_created', array($this, 'sync_product_to_woocommerce'), 10, 2);
        add_action('happyturtle_product_updated', array($this, 'sync_product_to_woocommerce'), 10, 2);

        // Hook into WooCommerce order completion
        add_action('woocommerce_order_status_completed', array($this, 'update_b2b_inventory'), 10, 1);
    }

    /**
     * Sync B2B product to WooCommerce
     *
     * @param int $b2b_product_id B2B product ID
     * @param array $product_data Product data
     */
    public function sync_product_to_woocommerce($b2b_product_id, $product_data) {
        global $wpdb;

        // Get linked WooCommerce product ID
        $table_name = $wpdb->prefix . '1_happyturtle_products';
        $wc_product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_b2b_product_id' AND meta_value = %d",
            $b2b_product_id
        ));

        // Calculate retail pricing (wholesale + markup)
        $retail_markup = 1.50; // 50% markup for retail
        $retail_price = floatval($product_data['unit_price']) * $retail_markup;

        // Allocate separate inventory for retail (30% of total stock)
        $retail_allocation = 0.30;
        $retail_stock = floor($product_data['stock_quantity'] * $retail_allocation);

        if ($wc_product_id) {
            // Update existing WooCommerce product
            $product = wc_get_product($wc_product_id);

            if ($product) {
                $product->set_name($product_data['product_name']);
                $product->set_description($product_data['description']);
                $product->set_regular_price($retail_price);
                $product->set_stock_quantity($retail_stock);
                $product->set_manage_stock(true);

                // Add custom meta for B2B linking
                $product->update_meta_data('_b2b_product_id', $b2b_product_id);
                $product->update_meta_data('_thc_percentage', $product_data['thc_percentage']);
                $product->update_meta_data('_cbd_percentage', $product_data['cbd_percentage']);
                $product->update_meta_data('_terpene_profile', $product_data['terpene_profile']);
                $product->update_meta_data('_batch_number', $product_data['batch_number']);
                $product->update_meta_data('_lab_results', $product_data['lab_results']);

                $product->save();
            }
        } else {
            // Create new WooCommerce product
            $product = new WC_Product_Simple();

            $product->set_name($product_data['product_name']);
            $product->set_sku($product_data['sku'] . '-RETAIL');
            $product->set_description($product_data['description']);
            $product->set_regular_price($retail_price);
            $product->set_stock_quantity($retail_stock);
            $product->set_manage_stock(true);
            $product->set_status('publish');

            // Set category
            $category_id = $this->get_or_create_category($product_data['category']);
            if ($category_id) {
                $product->set_category_ids(array($category_id));
            }

            // Add custom meta
            $product->update_meta_data('_b2b_product_id', $b2b_product_id);
            $product->update_meta_data('_thc_percentage', $product_data['thc_percentage']);
            $product->update_meta_data('_cbd_percentage', $product_data['cbd_percentage']);
            $product->update_meta_data('_terpene_profile', $product_data['terpene_profile']);
            $product->update_meta_data('_batch_number', $product_data['batch_number']);
            $product->update_meta_data('_lab_results', $product_data['lab_results']);

            $new_id = $product->save();

            // Store mapping in B2B product table
            $wpdb->update(
                $table_name,
                array('wc_product_id' => $new_id),
                array('id' => $b2b_product_id)
            );
        }
    }

    /**
     * Get or create WooCommerce product category
     *
     * @param string $category_name Category name
     * @return int|false Category term ID
     */
    private function get_or_create_category($category_name) {
        $term = get_term_by('name', $category_name, 'product_cat');

        if ($term) {
            return $term->term_id;
        }

        $result = wp_insert_term($category_name, 'product_cat');

        if (is_wp_error($result)) {
            return false;
        }

        return $result['term_id'];
    }

    /**
     * Update B2B inventory when WooCommerce order completes
     *
     * Deducts retail sales from B2B inventory
     *
     * @param int $order_id WooCommerce order ID
     */
    public function update_b2b_inventory($order_id) {
        global $wpdb;

        $order = wc_get_order($order_id);
        $table_name = $wpdb->prefix . '1_happyturtle_products';

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $b2b_product_id = $product->get_meta('_b2b_product_id');

            if ($b2b_product_id) {
                // Deduct from B2B inventory
                $quantity = $item->get_quantity();

                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name}
                     SET stock_quantity = stock_quantity - %d,
                         updated_at = %s
                     WHERE id = %d",
                    $quantity,
                    current_time('mysql'),
                    $b2b_product_id
                ));

                // Log the inventory change
                $this->log_inventory_change($b2b_product_id, -$quantity, 'woocommerce', $order_id);
            }
        }
    }

    /**
     * Log inventory changes
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity change (negative for decrease)
     * @param string $source Source of change
     * @param int $reference_id Reference order ID
     */
    private function log_inventory_change($product_id, $quantity, $source, $reference_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . '1_happyturtle_inventory_log';

        // Create table if doesn't exist
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            quantity_change int(11) NOT NULL,
            source varchar(50) NOT NULL,
            reference_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY source (source)
        )");

        $wpdb->insert($table_name, array(
            'product_id' => $product_id,
            'quantity_change' => $quantity,
            'source' => $source,
            'reference_id' => $reference_id,
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Trigger sync for existing products
     * Run this once after WooCommerce installation
     */
    public static function bulk_sync_all_products() {
        global $wpdb;

        $table_name = $wpdb->prefix . '1_happyturtle_products';
        $products = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 'active'", ARRAY_A);

        $sync = new self();
        $count = 0;

        foreach ($products as $product) {
            $sync->sync_product_to_woocommerce($product['id'], $product);
            $count++;
        }

        return $count;
    }
}

// Initialize if WooCommerce is active
if (class_exists('WooCommerce')) {
    new HappyTurtle_WooCommerce_Sync();
}
