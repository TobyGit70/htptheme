<?php
/**
 * Template for displaying B2B product catalog archive
 *
 * @package HappyTurtle_FSE
 */

// Require authentication for B2B product catalog (Arkansas compliance)
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
?>

<div class="wp-block-group alignfull htb-products-archive">

    <!-- Archive Header -->
    <div class="wp-block-group alignwide htb-archive-header">
        <div class="htb-archive-header-content">
            <h1 class="htb-archive-title">
                <?php
                if (is_tax('product_category')) {
                    single_term_title();
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>

            <?php if (is_tax('product_category')) :
                $term_description = term_description();
                if ($term_description) : ?>
                    <div class="htb-archive-description">
                        <?php echo $term_description; ?>
                    </div>
                <?php endif;
            endif; ?>

            <div class="htb-archive-stats">
                <?php
                global $wp_query;
                $total_products = $wp_query->found_posts;
                ?>
                <span class="htb-product-count"><?php echo $total_products; ?> products available</span>
            </div>
        </div>
    </div>

    <!-- Filters and Products Grid -->
    <div class="wp-block-group alignwide htb-catalog-wrapper">

        <!-- Sidebar Filters -->
        <aside class="htb-catalog-sidebar">
            <div class="htb-filter-section">
                <button class="htb-filters-toggle" aria-expanded="false">
                    <span>Filters</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </button>

                <div class="htb-filters-panel">

                    <!-- Search Filter -->
                    <div class="htb-filter-group">
                        <h3 class="htb-filter-title">Search Products</h3>
                        <div class="htb-search-box">
                            <input type="text"
                                   id="htb-product-search"
                                   class="htb-search-input"
                                   placeholder="Search by name or SKU..."
                                   value="<?php echo isset($_GET['product_search']) ? esc_attr($_GET['product_search']) : ''; ?>">
                            <button type="button" class="htb-search-btn" id="htb-search-submit">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="htb-filter-group">
                        <h3 class="htb-filter-title">Categories</h3>
                        <div class="htb-filter-options">
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'product_category',
                                'hide_empty' => false
                            ));

                            $current_category = is_tax('product_category') ? get_queried_object()->slug : '';

                            foreach ($categories as $category) :
                                $is_active = ($current_category === $category->slug);
                                $cat_count = $category->count;
                            ?>
                                <label class="htb-filter-option <?php echo $is_active ? 'active' : ''; ?>">
                                    <input type="checkbox"
                                           name="category[]"
                                           value="<?php echo esc_attr($category->slug); ?>"
                                           <?php checked($is_active); ?>
                                           class="htb-category-filter">
                                    <span class="htb-filter-label"><?php echo esc_html($category->name); ?></span>
                                    <span class="htb-filter-count">(<?php echo $cat_count; ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="htb-filter-group">
                        <h3 class="htb-filter-title">Price Range</h3>
                        <div class="htb-price-range">
                            <div class="htb-price-inputs">
                                <input type="number"
                                       id="htb-price-min"
                                       class="htb-price-input"
                                       placeholder="Min"
                                       value="<?php echo isset($_GET['price_min']) ? esc_attr($_GET['price_min']) : ''; ?>">
                                <span class="htb-price-separator">to</span>
                                <input type="number"
                                       id="htb-price-max"
                                       class="htb-price-input"
                                       placeholder="Max"
                                       value="<?php echo isset($_GET['price_max']) ? esc_attr($_GET['price_max']) : ''; ?>">
                            </div>
                            <button type="button" class="htp-btn htp-btn-secondary htb-apply-price" id="htb-apply-price">
                                Apply Price Filter
                            </button>
                        </div>
                    </div>

                    <!-- Availability Filter -->
                    <div class="htb-filter-group">
                        <h3 class="htb-filter-title">Availability</h3>
                        <div class="htb-filter-options">
                            <label class="htb-filter-option">
                                <input type="checkbox"
                                       name="in_stock"
                                       value="1"
                                       <?php checked(isset($_GET['in_stock'])); ?>
                                       class="htb-availability-filter">
                                <span class="htb-filter-label">In Stock Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="htb-filter-actions">
                        <button type="button" class="htp-btn htp-btn-outline" id="htb-clear-filters">
                            Clear All Filters
                        </button>
                    </div>

                </div>
            </div>
        </aside>

        <!-- Products Grid Area -->
        <main class="htb-catalog-main">

            <!-- Toolbar: Sort and View Options -->
            <div class="htb-catalog-toolbar">
                <div class="htb-toolbar-left">
                    <span class="htb-results-count">
                        Showing <strong><?php echo $wp_query->post_count; ?></strong> of <strong><?php echo $total_products; ?></strong> products
                    </span>
                </div>

                <div class="htb-toolbar-right">
                    <label for="htb-sort-select" class="htb-sort-label">Sort by:</label>
                    <select id="htb-sort-select" class="htb-sort-select">
                        <option value="default" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'default'); ?>>Default</option>
                        <option value="price_asc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'price_asc'); ?>>Price: Low to High</option>
                        <option value="price_desc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'price_desc'); ?>>Price: High to Low</option>
                        <option value="name_asc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'name_asc'); ?>>Name: A to Z</option>
                        <option value="name_desc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'name_desc'); ?>>Name: Z to A</option>
                        <option value="stock_desc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'stock_desc'); ?>>Stock: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="htb-product-grid">
                <?php
                if (have_posts()) :
                    while (have_posts()) : the_post();

                        // Get product meta
                        $sku = get_post_meta(get_the_ID(), '_sku', true);
                        $wholesale_price = get_post_meta(get_the_ID(), '_wholesale_price', true);
                        $stock_quantity = get_post_meta(get_the_ID(), '_stock_quantity', true);
                        $minimum_order = get_post_meta(get_the_ID(), '_minimum_order', true);
                        $unit_type = get_post_meta(get_the_ID(), '_unit_type', true);
                        $case_size = get_post_meta(get_the_ID(), '_case_size', true);
                        $lead_time_days = get_post_meta(get_the_ID(), '_lead_time_days', true);
                        $tiered_pricing = get_post_meta(get_the_ID(), '_tiered_pricing', true);

                        // Get category
                        $categories = get_the_terms(get_the_ID(), 'product_category');
                        $category_name = $categories && !is_wp_error($categories) ? $categories[0]->name : '';

                        // Stock status
                        $in_stock = $stock_quantity && $stock_quantity > 0;
                        $stock_class = $in_stock ? 'in-stock' : 'out-of-stock';
                        $stock_text = $in_stock ? "In Stock ({$stock_quantity} available)" : 'Out of Stock';
                        ?>

                        <article class="htb-product-card htp-card-product elevation-2 elevation-hover" data-product-id="<?php echo get_the_ID(); ?>">

                            <!-- Product Image -->
                            <div class="htb-product-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium', array('class' => 'htb-product-thumbnail')); ?>
                                    <?php else : ?>
                                        <div class="htb-product-placeholder">
                                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <?php if ($category_name) : ?>
                                    <span class="htb-product-category-badge"><?php echo esc_html($category_name); ?></span>
                                <?php endif; ?>

                                <button type="button" class="htb-favorite-btn" data-product-id="<?php echo get_the_ID(); ?>" aria-label="Add to favorites">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Product Info -->
                            <div class="htb-product-info">
                                <h3 class="htb-product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <?php if ($sku) : ?>
                                    <p class="htb-product-sku">SKU: <?php echo esc_html($sku); ?></p>
                                <?php endif; ?>

                                <div class="htb-product-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                                </div>

                                <!-- Stock Status -->
                                <div class="htb-product-stock <?php echo $stock_class; ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <?php if ($in_stock) : ?>
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        <?php else : ?>
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        <?php endif; ?>
                                    </svg>
                                    <span><?php echo $stock_text; ?></span>
                                </div>

                                <!-- Pricing -->
                                <div class="htb-product-pricing">
                                    <div class="htb-product-price">
                                        <span class="htb-price-label">Wholesale:</span>
                                        <span class="htb-price-amount">$<?php echo number_format($wholesale_price, 2); ?></span>
                                        <?php if ($unit_type) : ?>
                                            <span class="htb-price-unit">/ <?php echo esc_html($unit_type); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($tiered_pricing && is_array($tiered_pricing) && !empty($tiered_pricing)) : ?>
                                        <div class="htb-tiered-pricing-indicator">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                <polyline points="17 6 23 6 23 12"></polyline>
                                            </svg>
                                            <span>Volume discounts available</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Meta -->
                                <div class="htb-product-meta">
                                    <?php if ($minimum_order) : ?>
                                        <span class="htb-meta-item">
                                            <strong>Min Order:</strong> <?php echo esc_html($minimum_order); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($lead_time_days) : ?>
                                        <span class="htb-meta-item">
                                            <strong>Lead Time:</strong> <?php echo esc_html($lead_time_days); ?> days
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="htb-product-actions">
                                    <a href="<?php the_permalink(); ?>" class="htp-btn htp-btn-primary htp-btn-block">
                                        View Details
                                    </a>
                                </div>
                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else : ?>

                    <!-- No Products Found -->
                    <div class="htb-no-products">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or search terms.</p>
                        <button type="button" class="htp-btn htp-btn-primary" id="htb-reset-filters">
                            Clear All Filters
                        </button>
                    </div>

                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <nav class="htb-pagination" role="navigation" aria-label="Product pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $wp_query->max_num_pages,
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                        'type' => 'list',
                        'end_size' => 2,
                        'mid_size' => 2
                    ));
                    ?>
                </nav>
            <?php endif; ?>

        </main>

    </div>

</div>

<?php get_footer(); ?>
