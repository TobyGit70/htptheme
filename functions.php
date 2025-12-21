<?php
/**
 * Happy Turtle FSE Theme Functions
 */

// Add theme support
function happyturtle_fse_setup() {
    // Add support for block styles
    add_theme_support('wp-block-styles');

    // Add support for full and wide align images
    add_theme_support('align-wide');

    // Add support for editor styles
    add_theme_support('editor-styles');

    // Add support for responsive embedded content
    add_theme_support('responsive-embeds');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height' => 100,
        'width' => 100,
        'flex-height' => true,
        'flex-width' => true,
    ));

    // Add support for post thumbnails
    add_theme_support('post-thumbnails');

    // Add support for navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'happyturtle-fse'),
        'footer' => __('Footer Menu', 'happyturtle-fse'),
    ));
}
add_action('after_setup_theme', 'happyturtle_fse_setup');

// Enqueue styles and scripts
function happyturtle_fse_styles() {
    // Main theme style
    wp_enqueue_style(
        'happyturtle-fse-style',
        get_template_directory_uri() . '/assets/style.css',
        array(),
        '3.0.0'
    );

    // Enhanced design system (Three Principles)
    wp_enqueue_style(
        'htp-enhanced-design',
        get_template_directory_uri() . '/assets/css/htp-enhanced-design.css',
        array('happyturtle-fse-style'),
        '1.0.0'
    );

    // Splash screen script
    wp_enqueue_script(
        'happyturtle-splash',
        get_template_directory_uri() . '/assets/splash.js',
        array(),
        '2.2.0',
        true
    );

    // Scroll animations script
    wp_enqueue_script(
        'happyturtle-scroll-animations',
        get_template_directory_uri() . '/assets/scroll-animations.js',
        array(),
        '1.0.0',
        true
    );

    // Pass theme URL to JavaScript
    wp_localize_script('happyturtle-splash', 'htbData', array(
        'themeUrl' => get_template_directory_uri()
    ));
}
add_action('wp_enqueue_scripts', 'happyturtle_fse_styles');

// Register pattern categories
function happyturtle_fse_pattern_categories() {
    register_block_pattern_category(
        'happyturtle',
        array(
            'label' => __('Happy Turtle', 'happyturtle-fse'),
            'description' => __('Happy Turtle custom patterns', 'happyturtle-fse'),
        )
    );

    register_block_pattern_category(
        'happyturtle-sections',
        array(
            'label' => __('Happy Turtle Sections', 'happyturtle-fse'),
            'description' => __('Complete section layouts', 'happyturtle-fse'),
        )
    );
}
add_action('init', 'happyturtle_fse_pattern_categories');

// Register block styles
function happyturtle_fse_block_styles() {
    // Register HTP Card style for Group blocks
    register_block_style(
        'core/group',
        array(
            'name'         => 'htp-card',
            'label'        => __('HTP Card', 'happyturtle-fse'),
            'inline_style' => '',
        )
    );

    // Register HTP Card Elevated style for Group blocks
    register_block_style(
        'core/group',
        array(
            'name'         => 'htp-card-elevated',
            'label'        => __('HTP Card (Elevated)', 'happyturtle-fse'),
            'inline_style' => '',
        )
    );
}
add_action('init', 'happyturtle_fse_block_styles');

// Dynamic copyright year shortcode
function happyturtle_copyright_year() {
    $current_year = date('Y');
    if ($current_year > 2023) {
        return '2023-' . $current_year;
    }
    return '2023';
}
add_shortcode('copyright_year', 'happyturtle_copyright_year');

// Allow 3D model file uploads
function happyturtle_allow_3d_uploads($mime_types) {
    $mime_types['glb'] = 'model/gltf-binary';
    $mime_types['gltf'] = 'model/gltf+json';
    $mime_types['usdz'] = 'model/vnd.usdz+zip';
    $mime_types['fbx'] = 'application/octet-stream';
    return $mime_types;
}
add_filter('upload_mimes', 'happyturtle_allow_3d_uploads');

// Increase upload size limit
@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');
@ini_set('max_execution_time', '300');

// Enqueue splash screen on login page
function happyturtle_login_scripts() {
    wp_enqueue_style('happyturtle-splash-style', get_template_directory_uri() . '/assets/style.css', array(), '3.0.0');
    wp_enqueue_script('happyturtle-splash', get_template_directory_uri() . '/assets/splash.js', array(), '2.2.0', true);
    wp_localize_script('happyturtle-splash', 'htbData', array('themeUrl' => get_template_directory_uri()));
}
add_action('login_enqueue_scripts', 'happyturtle_login_scripts');

// Custom Login Page Styling
function happyturtle_login_styles() {
    ?>
    <style type="text/css">
        body.login {
            background: linear-gradient(135deg, #2D6A4F, #52B788, #40916C);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        body.login > * {
            position: static !important;
        }

        #login {
            width: auto;
            margin: 0;
            padding: 0;
        }

        #login h1 a {
            display: none;
        }

        .login form {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        .login form .input,
        .login input[type="text"],
        .login input[type="password"] {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(45, 106, 79, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .login form .input:focus,
        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: #2D6A4F;
            box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.1);
            outline: none;
        }

        .login .button-primary {
            background: linear-gradient(135deg, #1B4332, #2D6A4F, #D4A574);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 700;
            text-shadow: none;
            box-shadow: 0 3px 6px rgba(15, 36, 25, 0.15), 0 2px 4px rgba(212, 165, 116, 0.1);
            transition: all 0.3s ease;
        }

        .login .button-primary:hover {
            background: linear-gradient(135deg, #0F2419, #1B4332, #B8854A);
            box-shadow: 0 10px 20px rgba(15, 36, 25, 0.19), 0 6px 6px rgba(212, 165, 116, 0.12);
            transform: translateY(-2px);
        }

        .login #backtoblog,
        .login #nav {
            background: transparent;
            padding: 0;
            margin-top: 1rem;
            text-align: center;
        }

        .login #backtoblog a,
        .login #nav a {
            color: #FFFFFF !important;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            transition: color 0.3s ease;
        }

        .login #backtoblog a:hover,
        .login #nav a:hover {
            color: #E8C9A0 !important;
        }

        .login .message,
        .login .success {
            border-left: 4px solid #2D6A4F;
            background: rgba(45, 106, 79, 0.1);
            border-radius: 8px;
        }

        /* Ensure splash screen works on login page */
        body.login .htb-splash-screen {
            position: fixed !important;
            z-index: 999999 !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'happyturtle_login_styles');

// Change login logo URL
function happyturtle_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'happyturtle_login_logo_url');

// Change login logo title
function happyturtle_login_logo_url_title() {
    return 'Happy Turtle Processing, Inc.';
}
add_filter('login_headertext', 'happyturtle_login_logo_url_title');


// ========================================
// B2B PARTNER SYSTEM
// ========================================

// B2B system now handled by B2B Suite plugin
// Legacy theme files have been moved to plugin
// If you need to revert, restore the .theme-backup files in inc/ folder

// Load WooCommerce integration if WooCommerce is active
if (class_exists('WooCommerce')) {
    $woo_sync_file = get_template_directory() . '/inc/class-woocommerce-sync.php';
    if (file_exists($woo_sync_file)) {
        require_once $woo_sync_file;
    }
}

// Initialize B2B system
function happyturtle_init_b2b_system() {
    // Initialize security logger (must be first)
    $security_logger = HappyTurtle_Security_Logger::get_instance();
    $security_logger->create_tables();

    // Initialize product catalog
    $product_catalog = new HappyTurtle_Product_Catalog();
    $product_catalog->create_tables();
    $product_catalog->insert_default_categories();

    // Initialize partner management
    $partner_manager = new HappyTurtle_Partner_Management();
    $partner_manager->create_tables();

    // Initialize REST API
    $rest_api = new HappyTurtle_REST_API();
    $rest_api->register_routes();
}
add_action('rest_api_init', 'happyturtle_init_b2b_system');

// Create database tables on theme activation
function happyturtle_activate() {
    $security_logger = HappyTurtle_Security_Logger::get_instance();
    $security_logger->create_tables();

    $product_catalog = new HappyTurtle_Product_Catalog();
    $product_catalog->create_tables();
    $product_catalog->insert_default_categories();

    $partner_manager = new HappyTurtle_Partner_Management();
    $partner_manager->create_tables();
}
add_action('after_switch_theme', 'happyturtle_activate');

// Add body class for logged-in status
function happyturtle_body_class($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'user-logged-in';
    } else {
        $classes[] = 'user-not-logged-in';
    }
    return $classes;
}
add_filter('body_class', 'happyturtle_body_class');

// Redirect WooCommerce My Account page to Partner Login
function happyturtle_redirect_my_account() {
    // Only redirect if not logged in and not an admin
    if (!is_user_logged_in() && is_page('my-account') && !current_user_can('administrator')) {
        wp_redirect(home_url('/partner-login/'));
        exit;
    }
}
add_action('template_redirect', 'happyturtle_redirect_my_account');

/**
 * SEO: Custom meta title and description for homepage
 */
function happyturtle_custom_seo_meta() {
    if (is_front_page() || is_home()) {
        ?>
        <meta name="description" content="Happy Turtle Processing | Arkansas Cannabis Processor | Premium concentrates and CBD products for dispensaries statewide. Licensed, compliant, and trusted by Arkansas's medical marijuana industry.">
        <title>Happy Turtle Processing | Arkansas Cannabis Processor | Premium Concentrates & CBD</title>
        <?php
    }
}
add_action('wp_head', 'happyturtle_custom_seo_meta', 1);

/**
 * Filter document title for SEO
 */
function happyturtle_custom_document_title($title) {
    if (is_front_page() || is_home()) {
        return 'Happy Turtle Processing | Arkansas Cannabis Processor | Premium Concentrates & CBD';
    }
    return $title;
}
add_filter('document_title_parts', function($title) {
    if (is_front_page() || is_home()) {
        return ['title' => 'Happy Turtle Processing | Arkansas Cannabis Processor | Premium Concentrates & CBD'];
    }
    return $title;
});


// ============================================================================
// B2B PRODUCT CATALOG FUNCTIONALITY
// ============================================================================

/**
 * Enqueue product catalog scripts and styles
 */
function happyturtle_product_catalog_scripts() {
    // Only load on product archive pages
    if (is_post_type_archive('htb_product') || is_tax('product_category')) {
        // Product catalog JavaScript
        wp_enqueue_script(
            'happyturtle-product-catalog',
            get_template_directory_uri() . '/assets/js/product-catalog.js',
            array(),
            '1.0.0',
            true
        );

        // Localize script for AJAX and user data
        wp_localize_script('happyturtle-product-catalog', 'htbCatalog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htb_catalog_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => site_url('/partner-login')
        ));
    }
}
add_action('wp_enqueue_scripts', 'happyturtle_product_catalog_scripts', 20);

/**
 * Modify product query for filters, search, and sorting
 */
function happyturtle_product_query_filters($query) {
    // Only modify main query on product archives
    if (!is_admin() && $query->is_main_query() && (is_post_type_archive('htb_product') || is_tax('product_category'))) {

        // Search filter
        if (isset($_GET['product_search']) && !empty($_GET['product_search'])) {
            $search_term = sanitize_text_field($_GET['product_search']);

            // Search in title and SKU
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_sku',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            );

            $query->set('s', $search_term);
            $query->set('meta_query', $meta_query);
        }

        // Price range filter
        if ((isset($_GET['price_min']) && !empty($_GET['price_min'])) ||
            (isset($_GET['price_max']) && !empty($_GET['price_max']))) {

            $price_meta_query = array(
                'key' => '_wholesale_price',
                'type' => 'NUMERIC'
            );

            if (isset($_GET['price_min']) && !empty($_GET['price_min'])) {
                $price_meta_query['value'] = array();
                $price_meta_query['compare'] = 'BETWEEN';
                $price_meta_query['value'][] = floatval($_GET['price_min']);

                if (isset($_GET['price_max']) && !empty($_GET['price_max'])) {
                    $price_meta_query['value'][] = floatval($_GET['price_max']);
                } else {
                    $price_meta_query['value'][] = 999999; // Max ceiling
                }
            } elseif (isset($_GET['price_max']) && !empty($_GET['price_max'])) {
                $price_meta_query['value'] = floatval($_GET['price_max']);
                $price_meta_query['compare'] = '<=';
            }

            $existing_meta_query = $query->get('meta_query') ?: array();
            $existing_meta_query[] = $price_meta_query;
            $query->set('meta_query', $existing_meta_query);
        }

        // In stock filter
        if (isset($_GET['in_stock']) && $_GET['in_stock'] == '1') {
            $stock_meta_query = array(
                'key' => '_stock_quantity',
                'value' => 0,
                'type' => 'NUMERIC',
                'compare' => '>'
            );

            $existing_meta_query = $query->get('meta_query') ?: array();
            $existing_meta_query[] = $stock_meta_query;
            $query->set('meta_query', $existing_meta_query);
        }

        // Category filter (multiple)
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $categories = explode(',', sanitize_text_field($_GET['category']));
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'product_category',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN'
                )
            ));
        }

        // Sorting
        if (isset($_GET['sort']) && !empty($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'price_asc':
                    $query->set('meta_key', '_wholesale_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'ASC');
                    break;

                case 'price_desc':
                    $query->set('meta_key', '_wholesale_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;

                case 'name_asc':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;

                case 'name_desc':
                    $query->set('orderby', 'title');
                    $query->set('order', 'DESC');
                    break;

                case 'stock_desc':
                    $query->set('meta_key', '_stock_quantity');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;

                default:
                    // Default sorting (date)
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
            }
        }

        // Set posts per page
        $query->set('posts_per_page', 12);
    }
}
add_action('pre_get_posts', 'happyturtle_product_query_filters');

/**
 * AJAX handler for toggling favorites
 */
function happyturtle_toggle_favorite() {
    // Verify nonce
    check_ajax_referer('htb_catalog_nonce', 'nonce');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $user_id = get_current_user_id();

    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product ID'));
        return;
    }

    global $wpdb;
    $favorites_table = $wpdb->prefix . '1_happyturtle_product_favorites';

    // Check if already favorited
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $favorites_table WHERE user_id = %d AND product_id = %d",
        $user_id,
        $product_id
    ));

    if ($existing) {
        // Remove from favorites
        $wpdb->delete(
            $favorites_table,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id
            ),
            array('%d', '%d')
        );

        wp_send_json_success(array(
            'action' => 'removed',
            'message' => 'Removed from favorites'
        ));
    } else {
        // Add to favorites
        $wpdb->insert(
            $favorites_table,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );

        wp_send_json_success(array(
            'action' => 'added',
            'message' => 'Added to favorites'
        ));
    }
}
add_action('wp_ajax_htb_toggle_favorite', 'happyturtle_toggle_favorite');
add_action('wp_ajax_nopriv_htb_toggle_favorite', 'happyturtle_toggle_favorite');

// ============================================================
// PRODUCT DETAIL PAGE FUNCTIONS
// ============================================================

/**
 * Enqueue product detail page scripts
 */
function happyturtle_product_detail_scripts() {
    if (is_singular('htb_product')) {
        wp_enqueue_script(
            'happyturtle-product-detail',
            get_template_directory_uri() . '/assets/js/product-detail.js',
            array(),
            '1.0.0',
            true
        );

        wp_localize_script('happyturtle-product-detail', 'htbProductDetail', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htb_product_detail_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => site_url('/partner-login')
        ));
    }
}
add_action('wp_enqueue_scripts', 'happyturtle_product_detail_scripts', 20);


/**
 * AJAX handler for adding product to cart
 */
function happyturtle_add_to_cart() {
    check_ajax_referer('htb_product_detail_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to add products to cart'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = get_current_user_id();

    if (!$product_id || $quantity < 1) {
        wp_send_json_error(array('message' => 'Invalid product or quantity'));
        return;
    }

    // Verify product exists and is published
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'htb_product' || $product->post_status !== 'publish') {
        wp_send_json_error(array('message' => 'Product not found'));
        return;
    }

    // Check stock availability
    $stock_quantity = get_post_meta($product_id, '_stock_quantity', true);
    if ($stock_quantity !== '' && intval($stock_quantity) < $quantity) {
        wp_send_json_error(array('message' => 'Insufficient stock available'));
        return;
    }

    // Check minimum order quantity
    $minimum_order = get_post_meta($product_id, '_minimum_order', true);
    if ($minimum_order && $quantity < intval($minimum_order)) {
        wp_send_json_error(array('message' => 'Quantity is below minimum order requirement'));
        return;
    }

    // Get or create cart for user (stored in user meta for now, will move to sessions later)
    $cart = get_user_meta($user_id, '_htb_cart', true);
    if (!is_array($cart)) {
        $cart = array();
    }

    // Add or update cart item
    if (isset($cart[$product_id])) {
        $cart[$product_id]['quantity'] += $quantity;
    } else {
        $cart[$product_id] = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'added_at' => current_time('mysql')
        );
    }

    // Save cart
    update_user_meta($user_id, '_htb_cart', $cart);

    // Return success with cart count
    $cart_count = array_sum(array_column($cart, 'quantity'));

    wp_send_json_success(array(
        'message' => 'Product added to cart',
        'cart_count' => $cart_count
    ));
}
add_action('wp_ajax_htb_add_to_cart', 'happyturtle_add_to_cart');


/**
 * AJAX handler for notify when available
 */
function happyturtle_notify_when_available() {
    check_ajax_referer('htb_product_detail_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $user_id = get_current_user_id();

    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . '1_happyturtle_product_notifications';

    // Check if notification already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND product_id = %d AND notified = 0",
        $user_id,
        $product_id
    ));

    if ($existing) {
        wp_send_json_error(array('message' => 'You are already subscribed to notifications for this product'));
        return;
    }

    // Insert notification request
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'notified' => 0,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s')
    );

    if ($result) {
        wp_send_json_success(array('message' => 'You will be notified when this product is back in stock'));
    } else {
        wp_send_json_error(array('message' => 'Failed to save notification preference'));
    }
}
add_action('wp_ajax_htb_notify_when_available', 'happyturtle_notify_when_available');


/**
 * Create product notifications table
 */
function happyturtle_create_product_notifications_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . '1_happyturtle_product_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        notified tinyint(1) DEFAULT 0,
        created_at datetime NOT NULL,
        notified_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id),
        KEY notified (notified)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// Run on theme activation (add to functions.php main activation hook if needed)


// ============================================================
// SHOPPING CART FUNCTIONALITY
// ============================================================

/**
 * Enqueue cart scripts and styles
 */
function happyturtle_cart_scripts() {
    // Only load on cart page
    if (is_page_template('page-cart.php')) {
        wp_enqueue_script(
            'happyturtle-cart',
            get_template_directory_uri() . '/assets/js/cart.js',
            array(),
            '1.0.0',
            true
        );

        wp_localize_script('happyturtle-cart', 'htbCart', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htb_cart_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => site_url('/partner-login')
        ));
    }
}
add_action('wp_enqueue_scripts', 'happyturtle_cart_scripts', 20);


/**
 * AJAX: Update cart item quantity
 */
function happyturtle_update_cart_quantity() {
    check_ajax_referer('htb_cart_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = get_current_user_id();

    if (!$product_id || $quantity < 1) {
        wp_send_json_error(array('message' => 'Invalid product or quantity'));
        return;
    }

    // Check product exists
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'htb_product') {
        wp_send_json_error(array('message' => 'Product not found'));
        return;
    }

    // Check stock
    $stock_quantity = get_post_meta($product_id, '_stock_quantity', true);
    if ($stock_quantity !== '' && intval($stock_quantity) < $quantity) {
        wp_send_json_error(array('message' => 'Insufficient stock available'));
        return;
    }

    // Check minimum order
    $minimum_order = get_post_meta($product_id, '_minimum_order', true);
    if ($minimum_order && $quantity < intval($minimum_order)) {
        wp_send_json_error(array('message' => 'Quantity is below minimum order requirement'));
        return;
    }

    // Get cart
    $cart = get_user_meta($user_id, '_htb_cart', true);
    if (!is_array($cart)) {
        $cart = array();
    }

    // Update quantity
    if (isset($cart[$product_id])) {
        $cart[$product_id]['quantity'] = $quantity;
        update_user_meta($user_id, '_htb_cart', $cart);

        // Calculate new totals
        $totals = happyturtle_calculate_cart_totals($cart);

        // Calculate line total for this item
        $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);
        $tiered_pricing = get_post_meta($product_id, '_tiered_pricing', true);

        $item_price = floatval($wholesale_price);
        if ($tiered_pricing && is_array($tiered_pricing)) {
            foreach (array_reverse($tiered_pricing) as $tier) {
                if ($quantity >= intval($tier['min_qty'])) {
                    $item_price = floatval($tier['price']);
                    break;
                }
            }
        }

        $line_total = $item_price * $quantity;

        wp_send_json_success(array(
            'message' => 'Cart updated',
            'line_total' => $line_total,
            'subtotal' => $totals['subtotal'],
            'transport_fee' => $totals['transport_fee'],
            'total' => $totals['total'],
            'cart_count' => array_sum(array_column($cart, 'quantity'))
        ));
    } else {
        wp_send_json_error(array('message' => 'Product not in cart'));
    }
}
add_action('wp_ajax_htb_update_cart_quantity', 'happyturtle_update_cart_quantity');


/**
 * AJAX: Remove item from cart
 */
function happyturtle_remove_from_cart() {
    check_ajax_referer('htb_cart_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $user_id = get_current_user_id();

    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product'));
        return;
    }

    // Get cart
    $cart = get_user_meta($user_id, '_htb_cart', true);
    if (!is_array($cart)) {
        $cart = array();
    }

    // Remove item
    if (isset($cart[$product_id])) {
        unset($cart[$product_id]);
        update_user_meta($user_id, '_htb_cart', $cart);

        // Calculate new totals
        $totals = happyturtle_calculate_cart_totals($cart);

        wp_send_json_success(array(
            'message' => 'Item removed from cart',
            'subtotal' => $totals['subtotal'],
            'transport_fee' => $totals['transport_fee'],
            'total' => $totals['total'],
            'cart_count' => array_sum(array_column($cart, 'quantity'))
        ));
    } else {
        wp_send_json_error(array('message' => 'Product not in cart'));
    }
}
add_action('wp_ajax_htb_remove_from_cart', 'happyturtle_remove_from_cart');


/**
 * AJAX: Clear entire cart
 */
function happyturtle_clear_cart() {
    check_ajax_referer('htb_cart_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }

    $user_id = get_current_user_id();

    // Clear cart
    delete_user_meta($user_id, '_htb_cart');

    wp_send_json_success(array(
        'message' => 'Cart cleared',
        'cart_count' => 0
    ));
}
add_action('wp_ajax_htb_clear_cart', 'happyturtle_clear_cart');


/**
 * Calculate cart totals
 */
function happyturtle_calculate_cart_totals($cart) {
    if (!is_array($cart) || empty($cart)) {
        return array(
            'subtotal' => 0,
            'transport_fee' => 0,
            'total' => 0
        );
    }

    $subtotal = 0;
    $cart_items = array();

    foreach ($cart as $product_id => $item) {
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'htb_product') {
            continue;
        }

        $quantity = intval($item['quantity']);
        $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);
        $tiered_pricing = get_post_meta($product_id, '_tiered_pricing', true);

        // Calculate item price with tiered pricing
        $item_price = floatval($wholesale_price);
        if ($tiered_pricing && is_array($tiered_pricing)) {
            foreach (array_reverse($tiered_pricing) as $tier) {
                if ($quantity >= intval($tier['min_qty'])) {
                    $item_price = floatval($tier['price']);
                    break;
                }
            }
        }

        $line_total = $item_price * $quantity;
        $subtotal += $line_total;

        $cart_items[$product_id] = array(
            'quantity' => $quantity,
            'price' => $item_price,
            'total' => $line_total
        );
    }

    // Calculate transport fee
    $transport_fee = 0;
    if (class_exists('HappyTurtle_Order_Settings')) {
        $transport_fee = HappyTurtle_Order_Settings::calculate_transport_fee($cart_items, $subtotal);
    }

    $total = $subtotal + $transport_fee;

    return array(
        'subtotal' => $subtotal,
        'transport_fee' => $transport_fee,
        'total' => $total
    );
}
/**
 * Checkout Functions
 * AJAX handlers and order processing for checkout page
 */

// ============================================================
// ENQUEUE CHECKOUT SCRIPTS
// ============================================================

/**
 * Enqueue checkout page scripts
 */
function happyturtle_checkout_scripts() {
    // Only load on checkout page
    if (!is_page_template('page-checkout.php')) {
        return;
    }

    // Enqueue checkout JavaScript
    wp_enqueue_script(
        'htb-checkout',
        get_template_directory_uri() . '/assets/js/checkout.js',
        array(),
        '1.0.0',
        true
    );

    // Localize script with AJAX data
    wp_localize_script('htb-checkout', 'htbCheckout', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('htb_checkout_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'happyturtle_checkout_scripts');


// ============================================================
// ORDER SUBMISSION AJAX HANDLER
// ============================================================

/**
 * Handle order submission from checkout page
 */
function happyturtle_submit_order() {
    // Verify nonce
    if (!isset($_POST['htb_checkout_nonce']) || !wp_verify_nonce($_POST['htb_checkout_nonce'], 'htb_checkout')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to submit an order.'));
    }

    $user_id = get_current_user_id();

    // Get partner information
    global $wpdb;
    $partner = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}1_happyturtle_partners WHERE wp_user_id = %d",
        $user_id
    ), ARRAY_A);

    if (!$partner) {
        wp_send_json_error(array('message' => 'Partner information not found.'));
    }

    $partner_id = intval($partner['id']);

    // Get cart from user meta
    $cart = get_user_meta($user_id, '_htb_cart', true);

    if (!is_array($cart) || empty($cart)) {
        wp_send_json_error(array('message' => 'Your cart is empty.'));
    }

    // Validate delivery information
    $delivery_address = isset($_POST['delivery_address']) ? sanitize_textarea_field($_POST['delivery_address']) : '';
    $delivery_city = isset($_POST['delivery_city']) ? sanitize_text_field($_POST['delivery_city']) : '';
    $delivery_state = isset($_POST['delivery_state']) ? sanitize_text_field($_POST['delivery_state']) : '';
    $delivery_zip = isset($_POST['delivery_zip']) ? sanitize_text_field($_POST['delivery_zip']) : '';
    $delivery_phone = isset($_POST['delivery_phone']) ? sanitize_text_field($_POST['delivery_phone']) : '';
    $delivery_instructions = isset($_POST['delivery_instructions']) ? sanitize_textarea_field($_POST['delivery_instructions']) : '';

    if (empty($delivery_address) || empty($delivery_city) || empty($delivery_state) || empty($delivery_zip) || empty($delivery_phone)) {
        wp_send_json_error(array('message' => 'Please fill in all required delivery information.'));
    }

    // Validate payment information
    $payment_terms = isset($_POST['payment_terms']) ? sanitize_text_field($_POST['payment_terms']) : '';
    $po_number = isset($_POST['po_number']) ? sanitize_text_field($_POST['po_number']) : '';
    $order_notes = isset($_POST['order_notes']) ? sanitize_textarea_field($_POST['order_notes']) : '';

    if (empty($payment_terms)) {
        wp_send_json_error(array('message' => 'Please select payment terms.'));
    }

    // Calculate order totals
    $subtotal = 0;
    $cart_items = array();

    foreach ($cart as $product_id => $item) {
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'htb_product') {
            continue;
        }

        $quantity = intval($item['quantity']);
        $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);
        $tiered_pricing = get_post_meta($product_id, '_tiered_pricing', true);
        $sku = get_post_meta($product_id, '_sku', true);

        // Apply tiered pricing
        $item_price = floatval($wholesale_price);
        $discount_applied = false;

        if ($tiered_pricing && is_array($tiered_pricing)) {
            foreach (array_reverse($tiered_pricing) as $tier) {
                if ($quantity >= intval($tier['min_qty'])) {
                    $item_price = floatval($tier['price']);
                    $discount_applied = true;
                    break;
                }
            }
        }

        $line_total = $item_price * $quantity;
        $subtotal += $line_total;

        $cart_items[] = array(
            'product_id' => $product_id,
            'product_name' => $product->post_title,
            'sku' => $sku,
            'quantity' => $quantity,
            'price' => $item_price,
            'line_total' => $line_total
        );
    }

    // Calculate transport fee
    $transport_fee = 0;
    if (class_exists('HappyTurtle_Order_Settings')) {
        $transport_fee = HappyTurtle_Order_Settings::calculate_transport_fee($cart, $subtotal);
    }

    $total = $subtotal + $transport_fee;

    // Determine order approval status based on workflow
    $approval_workflow = get_option('htb_order_approval_workflow', 'manual');
    $order_status = 'pending'; // Default to pending approval

    if (class_exists('HappyTurtle_Order_Settings')) {
        $should_auto_approve = HappyTurtle_Order_Settings::should_auto_approve($partner_id, $total);

        if ($should_auto_approve) {
            $order_status = 'approved';
        }
    }

    // Insert order into database
    $order_data = array(
        'partner_id' => $partner_id,
        'order_number' => happyturtle_generate_order_number(),
        'order_status' => $order_status,
        'subtotal' => $subtotal,
        'transport_fee' => $transport_fee,
        'total' => $total,
        'payment_terms' => $payment_terms,
        'po_number' => $po_number,
        'delivery_address' => $delivery_address,
        'delivery_city' => $delivery_city,
        'delivery_state' => $delivery_state,
        'delivery_zip' => $delivery_zip,
        'delivery_phone' => $delivery_phone,
        'delivery_instructions' => $delivery_instructions,
        'order_notes' => $order_notes,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );

    $inserted = $wpdb->insert(
        $wpdb->prefix . '1_happyturtle_orders',
        $order_data,
        array('%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        wp_send_json_error(array('message' => 'Failed to create order. Please try again.'));
    }

    $order_id = $wpdb->insert_id;

    // Insert order items
    foreach ($cart_items as $cart_item) {
        $item_data = array(
            'order_id' => $order_id,
            'product_id' => $cart_item['product_id'],
            'product_name' => $cart_item['product_name'],
            'sku' => $cart_item['sku'],
            'quantity' => $cart_item['quantity'],
            'price' => $cart_item['price'],
            'line_total' => $cart_item['line_total'],
            'created_at' => current_time('mysql')
        );

        $wpdb->insert(
            $wpdb->prefix . '1_happyturtle_order_items',
            $item_data,
            array('%d', '%d', '%s', '%s', '%d', '%f', '%f', '%s')
        );
    }

    // Clear cart
    delete_user_meta($user_id, '_htb_cart');

    // Log order creation in access log
    if (function_exists('happyturtle_log_access')) {
        happyturtle_log_access(array(
            'partner_id' => $partner_id,
            'endpoint' => '/checkout/submit-order',
            'request_method' => 'POST',
            'response_status' => 200,
            'request_body' => json_encode(array(
                'order_id' => $order_id,
                'order_number' => $order_data['order_number'],
                'total' => $total,
                'status' => $order_status
            ))
        ));
    }

    // Send confirmation email
    happyturtle_send_order_confirmation_email($order_id, $partner, $order_data, $cart_items);

    // Send notification to admin if order requires approval
    if ($order_status === 'pending') {
        happyturtle_send_order_notification_to_admin($order_id, $partner, $order_data);
    }

    // Success response
    $message = ($order_status === 'approved')
        ? 'Order submitted and approved successfully!'
        : 'Order submitted successfully! You will receive a confirmation email once approved.';

    wp_send_json_success(array(
        'message' => $message,
        'order_id' => $order_id,
        'order_number' => $order_data['order_number'],
        'order_status' => $order_status,
        'redirect_url' => site_url('/order-confirmation/?order=' . $order_id)
    ));
}
add_action('wp_ajax_htb_submit_order', 'happyturtle_submit_order');


// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Generate unique order number
 */
function happyturtle_generate_order_number() {
    // Format: HT-YYYYMMDD-XXXXX
    $date_prefix = 'HT-' . date('Ymd');

    global $wpdb;
    $today_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}1_happyturtle_orders WHERE order_number LIKE %s",
        $date_prefix . '-%'
    ));

    $order_sequence = str_pad($today_count + 1, 5, '0', STR_PAD_LEFT);

    return $date_prefix . '-' . $order_sequence;
}


/**
 * Send order confirmation email to partner
 */
function happyturtle_send_order_confirmation_email($order_id, $partner, $order_data, $items) {
    $to = $partner['email'];
    $subject = 'Order Confirmation - ' . $order_data['order_number'];

    $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';

    // Header
    $message .= '<div style="background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 100%); color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">';
    $message .= '<h1 style="margin: 0; font-size: 28px;">Order Confirmation</h1>';
    $message .= '</div>';

    // Content
    $message .= '<div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;">';

    $message .= '<p>Dear ' . esc_html($partner['contact_name']) . ',</p>';

    if ($order_data['order_status'] === 'approved') {
        $message .= '<p style="background: #d1f2eb; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;">Your order has been <strong>automatically approved</strong> and is being processed!</p>';
    } else {
        $message .= '<p style="background: #fef3cd; border-left: 4px solid #fbbf24; padding: 15px; margin: 20px 0;">Your order has been received and is <strong>pending approval</strong>. You will receive a confirmation email once your order is reviewed.</p>';
    }

    $message .= '<h2 style="color: #1B4332; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">Order Details</h2>';

    $message .= '<table style="width: 100%; margin: 20px 0;">';
    $message .= '<tr><td style="padding: 8px 0; font-weight: 600;">Order Number:</td><td>' . esc_html($order_data['order_number']) . '</td></tr>';
    $message .= '<tr><td style="padding: 8px 0; font-weight: 600;">Order Date:</td><td>' . date('F j, Y', strtotime($order_data['created_at'])) . '</td></tr>';
    $message .= '<tr><td style="padding: 8px 0; font-weight: 600;">Payment Terms:</td><td>' . esc_html(strtoupper(str_replace('_', '-', $order_data['payment_terms']))) . '</td></tr>';
    if (!empty($order_data['po_number'])) {
        $message .= '<tr><td style="padding: 8px 0; font-weight: 600;">PO Number:</td><td>' . esc_html($order_data['po_number']) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= '<h2 style="color: #1B4332; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-top: 30px;">Order Items</h2>';

    $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
    $message .= '<thead><tr style="background: #e5e7eb;">';
    $message .= '<th style="padding: 12px; text-align: left;">Product</th>';
    $message .= '<th style="padding: 12px; text-align: center;">Qty</th>';
    $message .= '<th style="padding: 12px; text-align: right;">Price</th>';
    $message .= '<th style="padding: 12px; text-align: right;">Total</th>';
    $message .= '</tr></thead>';
    $message .= '<tbody>';

    foreach ($items as $item) {
        $message .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
        $message .= '<td style="padding: 12px;">' . esc_html($item['product_name']) . '<br><small style="color: #6b7280;">SKU: ' . esc_html($item['sku']) . '</small></td>';
        $message .= '<td style="padding: 12px; text-align: center;">' . intval($item['quantity']) . '</td>';
        $message .= '<td style="padding: 12px; text-align: right;">$' . number_format($item['price'], 2) . '</td>';
        $message .= '<td style="padding: 12px; text-align: right; font-weight: 600;">$' . number_format($item['line_total'], 2) . '</td>';
        $message .= '</tr>';
    }

    $message .= '</tbody>';
    $message .= '<tfoot>';
    $message .= '<tr><td colspan="3" style="padding: 12px; text-align: right; font-weight: 600;">Subtotal:</td><td style="padding: 12px; text-align: right;">$' . number_format($order_data['subtotal'], 2) . '</td></tr>';
    $message .= '<tr><td colspan="3" style="padding: 12px; text-align: right; font-weight: 600;">Transport Fee:</td><td style="padding: 12px; text-align: right;">$' . number_format($order_data['transport_fee'], 2) . '</td></tr>';
    $message .= '<tr style="background: #f0f4f2;"><td colspan="3" style="padding: 12px; text-align: right; font-weight: 700; font-size: 18px; color: #1B4332;">Total:</td><td style="padding: 12px; text-align: right; font-weight: 700; font-size: 18px; color: #1B4332;">$' . number_format($order_data['total'], 2) . '</td></tr>';
    $message .= '</tfoot>';
    $message .= '</table>';

    $message .= '<h2 style="color: #1B4332; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-top: 30px;">Delivery Information</h2>';

    $message .= '<p>';
    $message .= esc_html($order_data['delivery_address']) . '<br>';
    $message .= esc_html($order_data['delivery_city']) . ', ' . esc_html($order_data['delivery_state']) . ' ' . esc_html($order_data['delivery_zip']) . '<br>';
    $message .= 'Phone: ' . esc_html($order_data['delivery_phone']);
    $message .= '</p>';

    if (!empty($order_data['delivery_instructions'])) {
        $message .= '<p><strong>Delivery Instructions:</strong><br>' . nl2br(esc_html($order_data['delivery_instructions'])) . '</p>';
    }

    $message .= '<div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;">';
    $message .= '<p style="margin: 0;">If you have any questions about your order, please contact us:</p>';
    $message .= '<p style="margin: 10px 0 0 0;">Email: <a href="mailto:orders@happyturtleprocessing.com">orders@happyturtleprocessing.com</a><br>';
    $message .= 'Phone: (501) 555-0100</p>';
    $message .= '</div>';

    $message .= '</div>'; // End content div
    $message .= '</div>'; // End container div
    $message .= '</body></html>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}


/**
 * Send order notification to admin
 */
function happyturtle_send_order_notification_to_admin($order_id, $partner, $order_data) {
    $admin_email = get_option('admin_email');
    $subject = 'New Order Requires Approval - ' . $order_data['order_number'];

    $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';

    $message .= '<h1 style="color: #1B4332;">New Order Requires Approval</h1>';

    $message .= '<p>A new order has been submitted and requires your approval.</p>';

    $message .= '<table style="width: 100%; margin: 20px 0; border: 1px solid #e5e7eb;">';
    $message .= '<tr><td style="padding: 10px; font-weight: 600; background: #f9f9f9;">Order Number:</td><td style="padding: 10px;">' . esc_html($order_data['order_number']) . '</td></tr>';
    $message .= '<tr><td style="padding: 10px; font-weight: 600; background: #f9f9f9;">Partner:</td><td style="padding: 10px;">' . esc_html($partner['business_name']) . '</td></tr>';
    $message .= '<tr><td style="padding: 10px; font-weight: 600; background: #f9f9f9;">Total:</td><td style="padding: 10px; font-size: 18px; font-weight: 700; color: #1B4332;">$' . number_format($order_data['total'], 2) . '</td></tr>';
    $message .= '<tr><td style="padding: 10px; font-weight: 600; background: #f9f9f9;">Payment Terms:</td><td style="padding: 10px;">' . esc_html(strtoupper(str_replace('_', '-', $order_data['payment_terms']))) . '</td></tr>';
    $message .= '</table>';

    $message .= '<p style="margin-top: 30px;"><a href="' . admin_url('admin.php?page=b2b-orders&order_id=' . $order_id) . '" style="display: inline-block; padding: 12px 24px; background: #1B4332; color: #fff; text-decoration: none; border-radius: 6px;">Review Order in Admin</a></p>';

    $message .= '</div>';
    $message .= '</body></html>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($admin_email, $subject, $message, $headers);
}


// ============================================================================
// PLUGIN RECOMMENDATIONS & SETUP WIZARD
// ============================================================================

/**
 * Load plugin recommendation system
 * Provides setup wizard, installation prompts, and configuration tracking
 */
require_once get_template_directory() . '/inc/class-plugin-recommendations.php';


// ============================================================================
// PARTNER LICENSE LOOKUP & AUTO-POPULATE FOR FORMS
// ============================================================================

/**
 * Enqueue license lookup scripts on pages with WPForms
 */
function happyturtle_license_lookup_scripts() {
    // Load on any page that might have WPForms or check URL for partner-related pages
    $load_script = false;

    // Check by page slug
    if (is_page(array('contact', 'partner-application', 'quote', 'support', 'compliance-inquiry'))) {
        $load_script = true;
    }

    // Also check URL path for partner-related pages (handles cases where is_page fails)
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($request_uri, 'partner') !== false ||
        strpos($request_uri, 'contact') !== false ||
        strpos($request_uri, 'quote') !== false ||
        strpos($request_uri, 'support') !== false ||
        strpos($request_uri, 'compliance') !== false) {
        $load_script = true;
    }

    // Also load if URL has license parameter (redirect from contact page)
    if (isset($_GET['license']) || isset($_GET['name']) || isset($_GET['email'])) {
        $load_script = true;
    }

    if ($load_script) {
        wp_enqueue_script(
            'happyturtle-license-lookup',
            get_template_directory_uri() . '/assets/js/license-lookup.js',
            array('jquery'),
            '1.0.9', // Version bump to force cache refresh
            true
        );

        wp_localize_script('happyturtle-license-lookup', 'htbLicenseLookup', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htb_license_lookup_nonce'),
            'isLoggedIn' => is_user_logged_in()
        ));
    }
}
add_action('wp_enqueue_scripts', 'happyturtle_license_lookup_scripts', 25);


/**
 * AJAX: Lookup partner by license number
 * Note: This is a read-only lookup of public business license data
 */
function happyturtle_lookup_partner_by_license() {
    $license = isset($_POST['license_number']) ? sanitize_text_field($_POST['license_number']) : '';

    if (empty($license)) {
        wp_send_json_error(array('message' => 'Please enter a license number'));
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . '1_happyturtle_partners';

    // Search by license_number or mmcc_license_number
    $partner = $wpdb->get_row($wpdb->prepare(
        "SELECT id, business_name, contact_name, email, phone, address, city, state, zip,
                license_number, license_type, biotrack_license, license_status, status
         FROM $table
         WHERE license_number = %s OR mmcc_license_number = %s
         LIMIT 1",
        $license,
        $license
    ), ARRAY_A);

    if (!$partner) {
        wp_send_json_error(array(
            'message' => 'License not found in our system. Please enter your information manually.',
            'not_found' => true
        ));
        return;
    }

    // Return partner data for form population
    wp_send_json_success(array(
        'partner' => array(
            'id' => $partner['id'],
            'business_name' => $partner['business_name'],
            'contact_name' => $partner['contact_name'],
            'email' => $partner['email'],
            'phone' => $partner['phone'],
            'address' => $partner['address'],
            'city' => $partner['city'],
            'state' => $partner['state'],
            'zip' => $partner['zip'],
            'license_number' => $partner['license_number'],
            'license_type' => $partner['license_type'],
            'biotrack_license' => $partner['biotrack_license'],
            'license_status' => $partner['license_status'],
            'status' => $partner['status']
        ),
        'message' => 'Partner found! Information auto-filled.'
    ));
}
add_action('wp_ajax_htb_lookup_partner_by_license', 'happyturtle_lookup_partner_by_license');
add_action('wp_ajax_nopriv_htb_lookup_partner_by_license', 'happyturtle_lookup_partner_by_license');


/**
 * AJAX: Update partner information from form
 */
function happyturtle_update_partner_from_form() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htb_license_lookup_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
        return;
    }

    $partner_id = isset($_POST['partner_id']) ? intval($_POST['partner_id']) : 0;
    $license_number = isset($_POST['license_number']) ? sanitize_text_field($_POST['license_number']) : '';

    if (!$partner_id && !$license_number) {
        wp_send_json_error(array('message' => 'Partner identification required'));
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . '1_happyturtle_partners';

    // Build update data from submitted fields
    $update_data = array();
    $update_format = array();

    $fields_to_update = array(
        'business_name' => 'sanitize_text_field',
        'contact_name' => 'sanitize_text_field',
        'email' => 'sanitize_email',
        'phone' => 'sanitize_text_field',
        'address' => 'sanitize_textarea_field',
        'city' => 'sanitize_text_field',
        'state' => 'sanitize_text_field',
        'zip' => 'sanitize_text_field',
        'biotrack_license' => 'sanitize_text_field',
        'license_type' => 'sanitize_text_field'
    );

    foreach ($fields_to_update as $field => $sanitizer) {
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            $update_data[$field] = call_user_func($sanitizer, $_POST[$field]);
            $update_format[] = '%s';
        }
    }

    if (empty($update_data)) {
        wp_send_json_error(array('message' => 'No data to update'));
        return;
    }

    // Add updated_at timestamp
    $update_data['updated_at'] = current_time('mysql');
    $update_format[] = '%s';

    // Determine where clause
    if ($partner_id) {
        $where = array('id' => $partner_id);
        $where_format = array('%d');
    } else {
        $where = array('license_number' => $license_number);
        $where_format = array('%s');
    }

    $result = $wpdb->update($table, $update_data, $where, $update_format, $where_format);

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Partner information updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update partner information'));
    }
}
add_action('wp_ajax_htb_update_partner_from_form', 'happyturtle_update_partner_from_form');
add_action('wp_ajax_nopriv_htb_update_partner_from_form', 'happyturtle_update_partner_from_form');
