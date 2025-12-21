<?php
/**
 * Admin Page - Partner Management
 *
 * Manage B2B partner registrations, approvals, and orders
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Add admin menu
function happyturtle_add_admin_menus() {
    add_menu_page(
        'B2B Partners',
        'B2B Partners',
        'manage_options',
        'happyturtle-partners',
        'happyturtle_partners_page',
        'dashicons-groups',
        30
    );

    add_submenu_page(
        'happyturtle-partners',
        'Partners',
        'All Partners',
        'manage_options',
        'happyturtle-partners',
        'happyturtle_partners_page'
    );

    add_submenu_page(
        'happyturtle-partners',
        'Orders',
        'Orders',
        'manage_options',
        'happyturtle-orders',
        'happyturtle_orders_page'
    );

    add_submenu_page(
        'happyturtle-partners',
        'Products',
        'Products',
        'manage_options',
        'happyturtle-products',
        'happyturtle_products_page'
    );

    add_submenu_page(
        'happyturtle-partners',
        'BioTrack Settings',
        'BioTrack Settings',
        'manage_options',
        'happyturtle-biotrack',
        'happyturtle_biotrack_settings_page'
    );
}
add_action('admin_menu', 'happyturtle_add_admin_menus');

/**
 * Partners management page
 */
function happyturtle_partners_page() {
    $partner_manager = new HappyTurtle_Partner_Management();

    // Handle bulk import partners
    if (isset($_POST['import_partners']) && isset($_FILES['csv_file'])) {
        check_admin_referer('import_partners');

        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($file); // Skip header row

            $imported = 0;
            $errors = array();

            while (($row = fgetcsv($file)) !== false) {
                $data = array(
                    'business_name' => $row[0],
                    'contact_name' => $row[1],
                    'email' => $row[2],
                    'phone' => $row[3],
                    'address' => $row[4],
                    'city' => $row[5],
                    'state' => isset($row[6]) ? $row[6] : 'AR',
                    'zip' => $row[7],
                    'license_number' => $row[8],
                    'license_type' => $row[9],
                    'ein' => $row[10],
                    'biotrack_license' => isset($row[11]) ? $row[11] : ''
                );

                $result = $partner_manager->register_partner($data);
                if (is_wp_error($result)) {
                    $errors[] = 'Row ' . ($imported + 1) . ': ' . $result->get_error_message();
                } else {
                    $imported++;
                }
            }

            fclose($file);

            if ($imported > 0) {
                echo '<div class="notice notice-success"><p>Successfully imported ' . $imported . ' partners!</p></div>';
            }
            if (!empty($errors)) {
                echo '<div class="notice notice-error"><p>Errors:<br>' . implode('<br>', $errors) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Error uploading file.</p></div>';
        }
    }

    // Handle add new partner
    if (isset($_POST['add_partner'])) {
        check_admin_referer('add_partner');
        $result = $partner_manager->register_partner($_POST);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>Error: ' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Partner added successfully! ID: ' . $result . '</p></div>';
        }
    }

    // Handle partner approval
    if (isset($_POST['approve_partner']) && isset($_POST['partner_id'])) {
        check_admin_referer('approve_partner_' . $_POST['partner_id']);
        $result = $partner_manager->approve_partner($_POST['partner_id'], get_current_user_id());
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>Error: ' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Partner approved successfully!</p></div>';
        }
    }

    // Get partners
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $partners = $partner_manager->get_partners(array('status' => $status));

    ?>
    <div class="wrap">
        <h1>B2B Partners
            <a href="?page=happyturtle-partners&action=add" class="page-title-action">Add New</a>
            <a href="?page=happyturtle-partners&action=import" class="page-title-action">Import CSV</a>
        </h1>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'import'): ?>
            <!-- Import Partners Form -->
            <div class="card" style="max-width:800px;margin-top:2rem;">
                <h2>Import Partners from CSV</h2>
                <p>Upload a CSV file with the following columns (in this exact order):</p>
                <ol>
                    <li>Business Name</li>
                    <li>Contact Name</li>
                    <li>Email</li>
                    <li>Phone</li>
                    <li>Address</li>
                    <li>City</li>
                    <li>State (default: AR)</li>
                    <li>ZIP</li>
                    <li>License Number</li>
                    <li>License Type (dispensary, cultivator, or processor)</li>
                    <li>EIN</li>
                    <li>BioTrack License (optional)</li>
                </ol>
                <p><strong>Example CSV:</strong></p>
                <pre style="background:#f5f5f5;padding:1rem;border-radius:4px;">Business Name,Contact Name,Email,Phone,Address,City,State,ZIP,License Number,License Type,EIN,BioTrack License
Green Leaf Dispensary,John Smith,john@greenleaf.com,501-555-0100,123 Main St,Little Rock,AR,72201,DIS-001,dispensary,12-3456789,BTL-001</pre>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('import_partners'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="csv_file">CSV File</label></th>
                            <td>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <p class="description">Select a CSV file to import partners</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="import_partners" class="button button-primary">Import Partners</button>
                        <a href="?page=happyturtle-partners" class="button">Cancel</a>
                    </p>
                </form>
            </div>
        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
            <!-- Add New Partner Form -->
            <div class="card" style="max-width:800px;margin-top:2rem;">
                <h2>Add New Partner</h2>
                <form method="post">
                    <?php wp_nonce_field('add_partner'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="business_name">Business Name *</label></th>
                            <td><input type="text" name="business_name" id="business_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="contact_name">Contact Name *</label></th>
                            <td><input type="text" name="contact_name" id="contact_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="email">Email *</label></th>
                            <td><input type="email" name="email" id="email" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="phone">Phone *</label></th>
                            <td><input type="tel" name="phone" id="phone" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="address">Address *</label></th>
                            <td><input type="text" name="address" id="address" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="city">City *</label></th>
                            <td><input type="text" name="city" id="city" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="state">State *</label></th>
                            <td><input type="text" name="state" id="state" value="AR" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="zip">ZIP *</label></th>
                            <td><input type="text" name="zip" id="zip" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="license_number">License Number *</label></th>
                            <td><input type="text" name="license_number" id="license_number" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="license_type">License Type *</label></th>
                            <td>
                                <select name="license_type" id="license_type" required>
                                    <option value="">Select...</option>
                                    <option value="dispensary">Dispensary</option>
                                    <option value="cultivator">Cultivator</option>
                                    <option value="processor">Processor</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ein">EIN *</label></th>
                            <td><input type="text" name="ein" id="ein" class="regular-text" placeholder="XX-XXXXXXX" required></td>
                        </tr>
                        <tr>
                            <th><label for="biotrack_license">BioTrack License</label></th>
                            <td><input type="text" name="biotrack_license" id="biotrack_license" class="regular-text"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="add_partner" class="button button-primary">Add Partner</button>
                        <a href="?page=happyturtle-partners" class="button">Cancel</a>
                    </p>
                </form>
            </div>
        <?php else: ?>

        <ul class="subsubsub">
            <li><a href="?page=happyturtle-partners" <?php echo empty($status) ? 'class="current"' : ''; ?>>All</a> | </li>
            <li><a href="?page=happyturtle-partners&status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>Pending Approval</a> | </li>
            <li><a href="?page=happyturtle-partners&status=active" <?php echo $status === 'active' ? 'class="current"' : ''; ?>>Active</a></li>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>License Number</th>
                    <th>License Type</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="8">No partners found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><strong><?php echo esc_html($partner['business_name']); ?></strong></td>
                            <td><?php echo esc_html($partner['contact_name']); ?></td>
                            <td><?php echo esc_html($partner['email']); ?></td>
                            <td><?php echo esc_html($partner['license_number']); ?></td>
                            <td><?php echo esc_html($partner['license_type']); ?></td>
                            <td>
                                <?php if ($partner['status'] === 'pending'): ?>
                                    <span style="color: orange;">⏳ Pending</span>
                                <?php elseif ($partner['status'] === 'active'): ?>
                                    <span style="color: green;">✓ Active</span>
                                <?php else: ?>
                                    <?php echo esc_html($partner['status']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($partner['created_at'])); ?></td>
                            <td>
                                <?php if ($partner['status'] === 'pending'): ?>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('approve_partner_' . $partner['id']); ?>
                                        <input type="hidden" name="partner_id" value="<?php echo $partner['id']; ?>">
                                        <button type="submit" name="approve_partner" class="button button-primary" onclick="return confirm('Approve this partner? An email with API credentials will be sent.')">Approve</button>
                                    </form>
                                <?php endif; ?>
                                <a href="?page=happyturtle-partner-details&id=<?php echo $partner['id']; ?>" class="button">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <style>
        .subsubsub { margin: 1em 0 2em 0; }
        .wp-list-table th { background: #2D6A4F; color: white; padding: 12px; }
        .wp-list-table td { padding: 12px; }
    </style>

    <?php endif; // End if action=add ?>
    </div>
    <?php
}

/**
 * Orders management page
 */
function happyturtle_orders_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . '1_happyturtle_orders';
    $partners_table = $wpdb->prefix . '1_happyturtle_partners';

    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

    $where_clause = '';
    if (!empty($status)) {
        $where_clause = $wpdb->prepare("WHERE o.status = %s", $status);
    }

    $orders = $wpdb->get_results("
        SELECT o.*, p.business_name
        FROM {$orders_table} o
        LEFT JOIN {$partners_table} p ON o.partner_id = p.id
        {$where_clause}
        ORDER BY o.order_date DESC
        LIMIT 100
    ", ARRAY_A);

    ?>
    <div class="wrap">
        <h1>B2B Orders</h1>

        <ul class="subsubsub">
            <li><a href="?page=happyturtle-orders" <?php echo empty($status) ? 'class="current"' : ''; ?>>All</a> | </li>
            <li><a href="?page=happyturtle-orders&status=pending_approval" <?php echo $status === 'pending_approval' ? 'class="current"' : ''; ?>>Pending Approval</a> | </li>
            <li><a href="?page=happyturtle-orders&status=approved" <?php echo $status === 'approved' ? 'class="current"' : ''; ?>>Approved</a> | </li>
            <li><a href="?page=happyturtle-orders&status=completed" <?php echo $status === 'completed' ? 'class="current"' : ''; ?>>Completed</a></li>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Partner</th>
                    <th>Order Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>BioTrack</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                            <td><?php echo esc_html($order['business_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total'], 2); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $order['status']))); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $order['payment_status']))); ?></td>
                            <td>
                                <?php if ($order['biotrack_transfer_id']): ?>
                                    Transfer: <?php echo esc_html($order['biotrack_transfer_id']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=happyturtle-order-details&id=<?php echo $order['id']; ?>" class="button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <style>
        .subsubsub { margin: 1em 0 2em 0; }
        .wp-list-table th { background: #2D6A4F; color: white; padding: 12px; }
        .wp-list-table td { padding: 12px; }
    </style>
    <?php
}

/**
 * Products management page
 */
function happyturtle_products_page() {
    global $wpdb;
    $product_catalog = new HappyTurtle_Product_Catalog();
    $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

    // Handle bulk import products
    if (isset($_POST['import_products']) && isset($_FILES['csv_file'])) {
        check_admin_referer('import_products');

        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($file); // Skip header row

            $imported = 0;
            $errors = array();
            $table_name = $wpdb->prefix . '1_happyturtle_products';

            while (($row = fgetcsv($file)) !== false) {
                $result = $wpdb->insert($table_name, array(
                    'sku' => sanitize_text_field($row[0]),
                    'product_name' => sanitize_text_field($row[1]),
                    'category' => sanitize_text_field($row[2]),
                    'subcategory' => sanitize_text_field($row[3]),
                    'description' => sanitize_textarea_field($row[4]),
                    'thc_percentage' => !empty($row[5]) ? floatval($row[5]) : null,
                    'cbd_percentage' => !empty($row[6]) ? floatval($row[6]) : null,
                    'terpene_profile' => sanitize_text_field($row[7]),
                    'unit_type' => sanitize_text_field($row[8]),
                    'unit_price' => floatval($row[9]),
                    'minimum_order' => intval($row[10]),
                    'stock_quantity' => intval($row[11]),
                    'batch_number' => sanitize_text_field($row[12]),
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ));

                if ($result) {
                    $imported++;
                } else {
                    $errors[] = 'Row ' . ($imported + 1) . ': Failed to import';
                }
            }

            fclose($file);

            if ($imported > 0) {
                echo '<div class="notice notice-success"><p>Successfully imported ' . $imported . ' products!</p></div>';
            }
            if (!empty($errors)) {
                echo '<div class="notice notice-error"><p>Errors:<br>' . implode('<br>', $errors) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Error uploading file.</p></div>';
        }
    }

    // Handle add new product
    if (isset($_POST['add_product'])) {
        check_admin_referer('add_product');

        $table_name = $wpdb->prefix . '1_happyturtle_products';
        $result = $wpdb->insert($table_name, array(
            'sku' => sanitize_text_field($_POST['sku']),
            'product_name' => sanitize_text_field($_POST['product_name']),
            'category' => sanitize_text_field($_POST['category']),
            'subcategory' => sanitize_text_field($_POST['subcategory']),
            'description' => sanitize_textarea_field($_POST['description']),
            'thc_percentage' => !empty($_POST['thc_percentage']) ? floatval($_POST['thc_percentage']) : null,
            'cbd_percentage' => !empty($_POST['cbd_percentage']) ? floatval($_POST['cbd_percentage']) : null,
            'terpene_profile' => sanitize_text_field($_POST['terpene_profile']),
            'unit_type' => sanitize_text_field($_POST['unit_type']),
            'unit_price' => floatval($_POST['unit_price']),
            'minimum_order' => intval($_POST['minimum_order']),
            'stock_quantity' => intval($_POST['stock_quantity']),
            'batch_number' => sanitize_text_field($_POST['batch_number']),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));

        if ($result) {
            echo '<div class="notice notice-success"><p>Product added successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding product.</p></div>';
        }
    }

    $products = $product_catalog->get_products(array('category' => $category));
    $categories = $product_catalog->get_categories();

    ?>
    <div class="wrap">
        <h1>Product Catalog
            <a href="?page=happyturtle-products&action=add" class="page-title-action">Add New</a>
        </h1>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
            <!-- Add New Product Form -->
            <div class="card" style="max-width:800px;margin-top:2rem;">
                <h2>Add New Product</h2>
                <form method="post">
                    <?php wp_nonce_field('add_product'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="sku">SKU *</label></th>
                            <td><input type="text" name="sku" id="sku" class="regular-text" required placeholder="HTP-XX-000"></td>
                        </tr>
                        <tr>
                            <th><label for="product_name">Product Name *</label></th>
                            <td><input type="text" name="product_name" id="product_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="category">Category *</label></th>
                            <td>
                                <select name="category" id="category" required>
                                    <option value="">Select...</option>
                                    <option value="Concentrates">Concentrates</option>
                                    <option value="Vape Cartridges">Vape Cartridges</option>
                                    <option value="Edibles">Edibles</option>
                                    <option value="Pre-Rolls">Pre-Rolls</option>
                                    <option value="Flower">Flower</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="subcategory">Subcategory</label></th>
                            <td><input type="text" name="subcategory" id="subcategory" class="regular-text" placeholder="e.g. Live Resin, Shatter"></td>
                        </tr>
                        <tr>
                            <th><label for="description">Description</label></th>
                            <td><textarea name="description" id="description" class="large-text" rows="4"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="thc_percentage">THC %</label></th>
                            <td><input type="number" name="thc_percentage" id="thc_percentage" step="0.01" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="cbd_percentage">CBD %</label></th>
                            <td><input type="number" name="cbd_percentage" id="cbd_percentage" step="0.01" min="0" max="100" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="terpene_profile">Terpene Profile</label></th>
                            <td><input type="text" name="terpene_profile" id="terpene_profile" class="regular-text" placeholder="Myrcene, Limonene, Pinene"></td>
                        </tr>
                        <tr>
                            <th><label for="unit_type">Unit Type *</label></th>
                            <td>
                                <select name="unit_type" id="unit_type" required>
                                    <option value="">Select...</option>
                                    <option value="gram">Gram</option>
                                    <option value="cartridge">Cartridge</option>
                                    <option value="package">Package</option>
                                    <option value="pre-roll">Pre-Roll</option>
                                    <option value="ounce">Ounce</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="unit_price">Unit Price ($) *</label></th>
                            <td><input type="number" name="unit_price" id="unit_price" step="0.01" min="0" class="small-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="minimum_order">Minimum Order *</label></th>
                            <td><input type="number" name="minimum_order" id="minimum_order" min="1" class="small-text" value="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="stock_quantity">Stock Quantity *</label></th>
                            <td><input type="number" name="stock_quantity" id="stock_quantity" min="0" class="small-text" value="0" required></td>
                        </tr>
                        <tr>
                            <th><label for="batch_number">Batch Number</label></th>
                            <td><input type="text" name="batch_number" id="batch_number" class="regular-text" placeholder="BATCH-2025-001"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="add_product" class="button button-primary">Add Product</button>
                        <a href="?page=happyturtle-products" class="button">Cancel</a>
                    </p>
                </form>
            </div>
        <?php else: ?>

        <h1>Product Catalog</h1>

        <ul class="subsubsub">
            <li><a href="?page=happyturtle-products" <?php echo empty($category) ? 'class="current"' : ''; ?>>All Products</a></li>
            <?php foreach ($categories as $cat): ?>
                <li> | <a href="?page=happyturtle-products&category=<?php echo urlencode($cat); ?>" <?php echo $category === $cat ? 'class="current"' : ''; ?>><?php echo esc_html($cat); ?></a></li>
            <?php endforeach; ?>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>THC %</th>
                    <th>CBD %</th>
                    <th>Stock</th>
                    <th>Unit Price</th>
                    <th>Min Order</th>
                    <th>Batch</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><code><?php echo esc_html($product['sku']); ?></code></td>
                        <td><strong><?php echo esc_html($product['product_name']); ?></strong></td>
                        <td><?php echo esc_html($product['category']); ?></td>
                        <td><?php echo $product['thc_percentage'] ? number_format($product['thc_percentage'], 1) . '%' : '-'; ?></td>
                        <td><?php echo $product['cbd_percentage'] ? number_format($product['cbd_percentage'], 1) . '%' : '-'; ?></td>
                        <td>
                            <?php if ($product['stock_quantity'] < $product['minimum_order']): ?>
                                <span style="color: red;"><?php echo $product['stock_quantity']; ?> <?php echo esc_html($product['unit_type']); ?></span>
                            <?php else: ?>
                                <?php echo $product['stock_quantity']; ?> <?php echo esc_html($product['unit_type']); ?>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                        <td><?php echo $product['minimum_order']; ?></td>
                        <td><?php echo esc_html($product['batch_number']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <style>
        .subsubsub { margin: 1em 0 2em 0; }
        .wp-list-table th { background: #2D6A4F; color: white; padding: 12px; }
        .wp-list-table td { padding: 12px; }
    </style>

    <?php endif; // End if action=add ?>
    </div>
    <?php
}

/**
 * BioTrack settings page
 */
function happyturtle_biotrack_settings_page() {
    // Save settings
    if (isset($_POST['save_biotrack_settings'])) {
        check_admin_referer('biotrack_settings');

        update_option('biotrack_username', sanitize_text_field($_POST['biotrack_username']));
        update_option('biotrack_password', sanitize_text_field($_POST['biotrack_password']));
        update_option('biotrack_license_number', sanitize_text_field($_POST['biotrack_license_number']));

        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Test connection
    if (isset($_POST['test_connection'])) {
        check_admin_referer('biotrack_test');

        $biotrack = new HappyTurtle_BioTrack_API();
        $test_result = $biotrack->test_connection();

        if ($test_result['authentication']) {
            echo '<div class="notice notice-success"><p>✓ ' . $test_result['message'] . '</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>⚠ ' . $test_result['message'] . '</p></div>';
        }
    }

    $username = get_option('biotrack_username', '');
    $password = get_option('biotrack_password', '');
    $license = get_option('biotrack_license_number', '00340');

    ?>
    <div class="wrap">
        <h1>BioTrack THC API Settings</h1>

        <div class="card" style="max-width: 600px;">
            <h2>Arkansas BioTrack Integration</h2>
            <p>Configure your BioTrack THC credentials to enable inventory sync and transfer management.</p>

            <?php if (empty($username) || empty($password)): ?>
                <div class="notice notice-info inline">
                    <p><strong>Demo Mode Active:</strong> The system is currently operating in demo mode with mock data. Enter your BioTrack credentials below to connect to the live Arkansas system.</p>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('biotrack_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="biotrack_username">BioTrack Username</label></th>
                        <td>
                            <input type="text" name="biotrack_username" id="biotrack_username" value="<?php echo esc_attr($username); ?>" class="regular-text" placeholder="Enter BioTrack username">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="biotrack_password">BioTrack Password</label></th>
                        <td>
                            <input type="password" name="biotrack_password" id="biotrack_password" value="<?php echo esc_attr($password); ?>" class="regular-text" placeholder="Enter BioTrack password">
                            <p class="description">Your BioTrack password is stored securely.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="biotrack_license_number">License Number</label></th>
                        <td>
                            <input type="text" name="biotrack_license_number" id="biotrack_license_number" value="<?php echo esc_attr($license); ?>" class="regular-text" placeholder="00340">
                            <p class="description">Your Arkansas medical marijuana processor license number.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="save_biotrack_settings" class="button button-primary">Save Settings</button>
                </p>
            </form>

            <hr>

            <form method="post">
                <?php wp_nonce_field('biotrack_test'); ?>
                <p>
                    <button type="submit" name="test_connection" class="button">Test Connection</button>
                    <span class="description">Verify connection to BioTrack API</span>
                </p>
            </form>
        </div>

        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>API Endpoints</h2>
            <p>Base URL: <code>https://server.biotrackthc.net/api/</code></p>

            <h3>Documentation</h3>
            <p><a href="https://server.biotrackthc.net/API_documentation/Arkansas/?xml#getting-started" target="_blank">Arkansas BioTrack API Documentation →</a></p>
        </div>
    </div>
    <?php
}
