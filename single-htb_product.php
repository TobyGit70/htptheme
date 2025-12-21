<?php
/**
 * Template for single product detail page
 *
 * @package HappyTurtle_FSE
 */

// Require authentication for B2B product pages (Arkansas compliance)
if (!is_user_logged_in()) {
    wp_redirect(home_url('/b2b-login/'));
    exit;
}

// Verify partner role
$user = wp_get_current_user();
if (!in_array('partner', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();

// Get all product meta data
$product_id = get_the_ID();
$sku = get_post_meta($product_id, '_sku', true);
$product_type = get_post_meta($product_id, '_product_type', true);
$unit_type = get_post_meta($product_id, '_unit_type', true);
$case_size = get_post_meta($product_id, '_case_size', true);
$wholesale_price = get_post_meta($product_id, '_wholesale_price', true);
$minimum_order = get_post_meta($product_id, '_minimum_order', true);
$stock_quantity = get_post_meta($product_id, '_stock_quantity', true);
$lead_time_days = get_post_meta($product_id, '_lead_time_days', true);
$tiered_pricing = get_post_meta($product_id, '_tiered_pricing', true);

// Compliance data
$biotrack_uid = get_post_meta($product_id, '_biotrack_uid', true);
$batch_number = get_post_meta($product_id, '_batch_number', true);
$lab_test_date = get_post_meta($product_id, '_lab_test_date', true);
$potency_thc = get_post_meta($product_id, '_potency_thc', true);
$potency_cbd = get_post_meta($product_id, '_potency_cbd', true);

// Specifications
$package_size = get_post_meta($product_id, '_package_size', true);
$shelf_life_days = get_post_meta($product_id, '_shelf_life_days', true);
$storage_requirements = get_post_meta($product_id, '_storage_requirements', true);

// Get category
$categories = get_the_terms($product_id, 'product_category');
$category = $categories && !is_wp_error($categories) ? $categories[0] : null;

// Check if user has favorited this product
$is_favorite = false;
if (is_user_logged_in()) {
    global $wpdb;
    $favorites_table = $wpdb->prefix . '1_happyturtle_product_favorites';
    $user_id = get_current_user_id();
    $is_favorite = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $favorites_table WHERE user_id = %d AND product_id = %d",
        $user_id,
        $product_id
    ));
}

// Calculate stock status
$in_stock = $stock_quantity && $stock_quantity > 0;
$stock_level = '';
if ($stock_quantity > 20) {
    $stock_level = 'high';
} elseif ($stock_quantity > 5) {
    $stock_level = 'medium';
} elseif ($stock_quantity > 0) {
    $stock_level = 'low';
} else {
    $stock_level = 'out';
}

?>

<main id="main" class="htb-single-product">

    <!-- Breadcrumbs -->
    <div class="htb-breadcrumbs">
        <div class="htb-container">
            <nav aria-label="Breadcrumb">
                <ol class="htb-breadcrumb-list">
                    <li><a href="<?php echo home_url(); ?>">Home</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('htb_product'); ?>">Products</a></li>
                    <?php if ($category): ?>
                        <li><a href="<?php echo get_term_link($category); ?>"><?php echo esc_html($category->name); ?></a></li>
                    <?php endif; ?>
                    <li aria-current="page"><?php the_title(); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="htb-container">

        <?php while (have_posts()) : the_post(); ?>

            <div class="htb-product-detail-wrapper">

                <!-- Product Gallery Section -->
                <div class="htb-product-gallery">

                    <!-- Main Image -->
                    <div class="htb-product-main-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large', array('class' => 'htb-product-image')); ?>
                        <?php else: ?>
                            <div class="htb-product-placeholder-image">
                                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <p>No image available</p>
                            </div>
                        <?php endif; ?>

                        <!-- Category Badge -->
                        <?php if ($category): ?>
                            <span class="htb-product-category-badge">
                                <?php echo esc_html($category->name); ?>
                            </span>
                        <?php endif; ?>

                        <!-- Favorite Button -->
                        <button
                            class="htb-favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>"
                            data-product-id="<?php echo $product_id; ?>"
                            aria-label="Add to favorites"
                            title="<?php echo $is_favorite ? 'Remove from favorites' : 'Add to favorites'; ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Thumbnail Gallery (placeholder for future image gallery) -->
                    <div class="htb-product-thumbnails">
                        <!-- Future: Additional product images -->
                    </div>

                </div>

                <!-- Product Info Section -->
                <div class="htb-product-info">

                    <!-- Product Header -->
                    <header class="htb-product-header">
                        <h1 class="htb-product-title"><?php the_title(); ?></h1>
                        <div class="htb-product-meta-header">
                            <span class="htb-product-sku">SKU: <?php echo esc_html($sku); ?></span>
                            <?php if ($in_stock): ?>
                                <span class="htp-badge <?php echo ($stock_level === 'high') ? 'htp-badge-success' : (($stock_level === 'medium') ? 'htp-badge-info' : 'htp-badge-warning'); ?>">
                                    <?php
                                    if ($stock_level === 'high') echo 'In Stock';
                                    elseif ($stock_level === 'medium') echo 'Limited Stock';
                                    elseif ($stock_level === 'low') echo 'Low Stock';
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="htp-badge htp-badge-error">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <!-- Short Description -->
                    <div class="htb-product-excerpt">
                        <?php the_excerpt(); ?>
                    </div>

                    <!-- Pricing Section -->
                    <div class="htb-product-pricing">
                        <div class="htb-price-main">
                            <span class="htb-price-label">Wholesale Price:</span>
                            <span class="htb-price-amount">$<?php echo number_format(floatval($wholesale_price), 2); ?></span>
                            <?php if ($unit_type): ?>
                                <span class="htb-price-unit">per <?php echo esc_html($unit_type); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($tiered_pricing && is_array($tiered_pricing) && !empty($tiered_pricing)): ?>
                            <div class="htb-tiered-pricing-indicator">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                Volume discounts available
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tiered Pricing Table -->
                    <?php if ($tiered_pricing && is_array($tiered_pricing) && !empty($tiered_pricing)): ?>
                        <div class="htb-tiered-pricing-table">
                            <h3>Volume Pricing</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Quantity</th>
                                        <th>Price per Unit</th>
                                        <th>Savings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1 - <?php echo intval($tiered_pricing[0]['min_qty']) - 1; ?></td>
                                        <td>$<?php echo number_format(floatval($wholesale_price), 2); ?></td>
                                        <td>—</td>
                                    </tr>
                                    <?php foreach ($tiered_pricing as $tier): ?>
                                        <?php
                                        $tier_price = floatval($tier['price']);
                                        $savings = floatval($wholesale_price) - $tier_price;
                                        $savings_percent = ($savings / floatval($wholesale_price)) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo intval($tier['min_qty']); ?>+</td>
                                            <td>$<?php echo number_format($tier_price, 2); ?></td>
                                            <td class="htb-savings">
                                                Save $<?php echo number_format($savings, 2); ?>
                                                (<?php echo number_format($savings_percent, 1); ?>%)
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Order Information -->
                    <div class="htb-order-info-cards">
                        <?php if ($minimum_order): ?>
                            <div class="htb-info-card">
                                <div class="htb-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    </svg>
                                </div>
                                <div class="htb-info-content">
                                    <span class="htb-info-label">Minimum Order</span>
                                    <span class="htb-info-value"><?php echo intval($minimum_order); ?> <?php echo intval($minimum_order) > 1 ? 'units' : 'unit'; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($lead_time_days): ?>
                            <div class="htb-info-card">
                                <div class="htb-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="htb-info-content">
                                    <span class="htb-info-label">Lead Time</span>
                                    <span class="htb-info-value"><?php echo intval($lead_time_days); ?> <?php echo intval($lead_time_days) > 1 ? 'days' : 'day'; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($case_size): ?>
                            <div class="htb-info-card">
                                <div class="htb-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    </svg>
                                </div>
                                <div class="htb-info-content">
                                    <span class="htb-info-label">Case Size</span>
                                    <span class="htb-info-value"><?php echo intval($case_size); ?> units</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($stock_quantity): ?>
                            <div class="htb-info-card">
                                <div class="htb-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                                <div class="htb-info-content">
                                    <span class="htb-info-label">Available Stock</span>
                                    <span class="htb-info-value"><?php echo intval($stock_quantity); ?> in stock</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Order Section -->
                    <div class="htb-add-to-order">
                        <?php if ($in_stock): ?>
                            <div class="htb-quantity-selector">
                                <label for="htb-quantity">Quantity:</label>
                                <div class="htb-quantity-controls">
                                    <button type="button" class="htb-qty-btn htb-qty-minus" aria-label="Decrease quantity">−</button>
                                    <input
                                        type="number"
                                        id="htb-quantity"
                                        class="htb-quantity-input"
                                        value="<?php echo $minimum_order ? intval($minimum_order) : 1; ?>"
                                        min="<?php echo $minimum_order ? intval($minimum_order) : 1; ?>"
                                        max="<?php echo intval($stock_quantity); ?>"
                                        step="1">
                                    <button type="button" class="htb-qty-btn htb-qty-plus" aria-label="Increase quantity">+</button>
                                </div>
                            </div>

                            <button class="htp-btn htp-btn-primary htb-add-to-cart-btn" data-product-id="<?php echo $product_id; ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                Add to Order Request
                            </button>
                        <?php else: ?>
                            <button class="htp-btn htp-btn-secondary" disabled>
                                Out of Stock
                            </button>
                            <button class="htp-btn htp-btn-outline htb-notify-btn" data-product-id="<?php echo $product_id; ?>">
                                Notify When Available
                            </button>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

            <!-- Product Tabs Section -->
            <div class="htb-product-tabs">

                <!-- Tab Navigation -->
                <div class="htb-tabs-nav" role="tablist">
                    <button
                        class="htb-tab-btn active"
                        role="tab"
                        aria-selected="true"
                        aria-controls="tab-details"
                        id="tab-btn-details"
                        data-tab="details">
                        Details
                    </button>

                    <?php if ($biotrack_uid || $batch_number || $lab_test_date || $potency_thc || $potency_cbd): ?>
                        <button
                            class="htb-tab-btn"
                            role="tab"
                            aria-selected="false"
                            aria-controls="tab-compliance"
                            id="tab-btn-compliance"
                            data-tab="compliance">
                            Compliance
                        </button>
                    <?php endif; ?>

                    <button
                        class="htb-tab-btn"
                        role="tab"
                        aria-selected="false"
                        aria-controls="tab-specifications"
                        id="tab-btn-specifications"
                        data-tab="specifications">
                        Specifications
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="htb-tabs-content">

                    <!-- Details Tab -->
                    <div
                        class="htb-tab-panel active"
                        role="tabpanel"
                        id="tab-details"
                        aria-labelledby="tab-btn-details">
                        <div class="htb-tab-content-inner">
                            <?php the_content(); ?>
                        </div>
                    </div>

                    <!-- Compliance Tab -->
                    <?php if ($biotrack_uid || $batch_number || $lab_test_date || $potency_thc || $potency_cbd): ?>
                        <div
                            class="htb-tab-panel"
                            role="tabpanel"
                            id="tab-compliance"
                            aria-labelledby="tab-btn-compliance">
                            <div class="htb-tab-content-inner">
                                <h3>Arkansas Cannabis Compliance Information</h3>

                                <div class="htb-compliance-grid">
                                    <?php if ($biotrack_uid): ?>
                                        <div class="htb-compliance-item">
                                            <span class="htb-compliance-label">BioTrack UID:</span>
                                            <span class="htb-compliance-value"><?php echo esc_html($biotrack_uid); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($batch_number): ?>
                                        <div class="htb-compliance-item">
                                            <span class="htb-compliance-label">Batch Number:</span>
                                            <span class="htb-compliance-value"><?php echo esc_html($batch_number); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($lab_test_date): ?>
                                        <div class="htb-compliance-item">
                                            <span class="htb-compliance-label">Lab Test Date:</span>
                                            <span class="htb-compliance-value"><?php echo date('F j, Y', strtotime($lab_test_date)); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($potency_thc): ?>
                                        <div class="htb-compliance-item">
                                            <span class="htb-compliance-label">THC Potency:</span>
                                            <span class="htb-compliance-value"><?php echo number_format(floatval($potency_thc), 2); ?>%</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($potency_cbd): ?>
                                        <div class="htb-compliance-item">
                                            <span class="htb-compliance-label">CBD Potency:</span>
                                            <span class="htb-compliance-value"><?php echo number_format(floatval($potency_cbd), 2); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="htp-alert htp-alert-info">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <p>All products comply with Arkansas Medical Marijuana Commission regulations. Lab test results available upon request.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Specifications Tab -->
                    <div
                        class="htb-tab-panel"
                        role="tabpanel"
                        id="tab-specifications"
                        aria-labelledby="tab-btn-specifications">
                        <div class="htb-tab-content-inner">
                            <h3>Product Specifications</h3>

                            <table class="htb-specifications-table">
                                <tbody>
                                    <?php if ($sku): ?>
                                        <tr>
                                            <th>SKU</th>
                                            <td><?php echo esc_html($sku); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($product_type): ?>
                                        <tr>
                                            <th>Product Type</th>
                                            <td><?php echo esc_html(ucfirst($product_type)); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($package_size): ?>
                                        <tr>
                                            <th>Package Size</th>
                                            <td><?php echo esc_html($package_size); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($shelf_life_days): ?>
                                        <tr>
                                            <th>Shelf Life</th>
                                            <td>
                                                <?php
                                                $shelf_life = intval($shelf_life_days);
                                                if ($shelf_life >= 9999) {
                                                    echo 'Indefinite';
                                                } elseif ($shelf_life >= 365) {
                                                    echo round($shelf_life / 365, 1) . ' year' . (round($shelf_life / 365, 1) > 1 ? 's' : '');
                                                } else {
                                                    echo $shelf_life . ' days';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($storage_requirements): ?>
                                        <tr>
                                            <th>Storage Requirements</th>
                                            <td><?php echo esc_html($storage_requirements); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($unit_type): ?>
                                        <tr>
                                            <th>Unit Type</th>
                                            <td><?php echo esc_html(ucfirst($unit_type)); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($case_size): ?>
                                        <tr>
                                            <th>Units per Case</th>
                                            <td><?php echo intval($case_size); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Related Products Section -->
            <?php
            // Get related products from same category
            $related_args = array(
                'post_type' => 'htb_product',
                'posts_per_page' => 4,
                'post__not_in' => array($product_id),
                'orderby' => 'rand'
            );

            if ($category) {
                $related_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_category',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    )
                );
            }

            $related_query = new WP_Query($related_args);

            if ($related_query->have_posts()): ?>

                <div class="htb-related-products">
                    <h2>Related Products</h2>

                    <div class="htb-related-grid">
                        <?php while ($related_query->have_posts()): $related_query->the_post();
                            $related_id = get_the_ID();
                            $related_sku = get_post_meta($related_id, '_sku', true);
                            $related_price = get_post_meta($related_id, '_wholesale_price', true);
                            $related_stock = get_post_meta($related_id, '_stock_quantity', true);
                            $related_category = get_the_terms($related_id, 'product_category');
                            $related_category = $related_category && !is_wp_error($related_category) ? $related_category[0] : null;
                        ?>
                            <article class="htb-product-card-mini">
                                <a href="<?php the_permalink(); ?>" class="htb-product-card-link">

                                    <div class="htb-product-image-mini">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php else: ?>
                                            <div class="htb-product-placeholder">
                                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21 15 16 10 5 21"></polyline>
                                                </svg>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($related_category): ?>
                                            <span class="htb-category-badge-mini"><?php echo esc_html($related_category->name); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="htb-product-info-mini">
                                        <h3 class="htb-product-title-mini"><?php the_title(); ?></h3>
                                        <p class="htb-product-sku-mini">SKU: <?php echo esc_html($related_sku); ?></p>

                                        <?php if ($related_price): ?>
                                            <p class="htb-product-price-mini">$<?php echo number_format(floatval($related_price), 2); ?></p>
                                        <?php endif; ?>

                                        <?php if ($related_stock && $related_stock > 0): ?>
                                            <span class="htb-stock-badge-mini htb-stock-in">In Stock</span>
                                        <?php else: ?>
                                            <span class="htb-stock-badge-mini htb-stock-out-mini">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>

                                </a>
                            </article>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                </div>

            <?php endif; ?>

        <?php endwhile; ?>

    </div>

</main>

<?php get_footer(); ?>
