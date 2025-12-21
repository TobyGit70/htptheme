<?php
/**
 * Template Name: Order Confirmation
 * Description: Order confirmation and status page
 */

// Require authentication for order confirmation (Arkansas compliance)
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

$user_id = get_current_user_id();

// Get order ID from URL
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

if (!$order_id) {
    ?>
    <div class="htb-empty-state" style="text-align: center; padding: 100px 20px;">
        <h2>Order Not Found</h2>
        <p>The order you're looking for doesn't exist.</p>
        <a href="<?php echo get_post_type_archive_link('htb_product'); ?>" class="htb-btn htb-btn-primary">Continue Shopping</a>
    </div>
    <?php
    get_footer();
    return;
}

// Get order from database
global $wpdb;
$order = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}1_happyturtle_orders WHERE id = %d",
    $order_id
), ARRAY_A);

if (!$order) {
    ?>
    <div class="htb-empty-state" style="text-align: center; padding: 100px 20px;">
        <h2>Order Not Found</h2>
        <p>The order you're looking for doesn't exist.</p>
        <a href="<?php echo get_post_type_archive_link('htb_product'); ?>" class="htb-btn htb-btn-primary">Continue Shopping</a>
    </div>
    <?php
    get_footer();
    return;
}

// Get partner information
$partner = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}1_happyturtle_partners WHERE wp_user_id = %d",
    $user_id
), ARRAY_A);

// Verify this order belongs to the logged-in partner
if (!$partner || intval($order['partner_id']) !== intval($partner['id'])) {
    ?>
    <div class="htb-empty-state" style="text-align: center; padding: 100px 20px;">
        <h2>Access Denied</h2>
        <p>You don't have permission to view this order.</p>
        <a href="<?php echo get_post_type_archive_link('htb_product'); ?>" class="htb-btn htb-btn-primary">Continue Shopping</a>
    </div>
    <?php
    get_footer();
    return;
}

// Get order items
$order_items = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}1_happyturtle_order_items WHERE order_id = %d ORDER BY id ASC",
    $order_id
), ARRAY_A);

?>

<div class="htb-order-confirmation-page">
    <div class="wp-block-group alignfull" style="max-width: 1200px; margin: 0 auto; padding: 3rem 2rem;">

        <!-- Success Header -->
        <div class="htb-confirmation-header">
            <?php if ($order['order_status'] === 'approved'): ?>
                <div class="htb-success-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h1>Order Approved!</h1>
                <p class="htb-confirmation-message">Your order has been approved and is being processed.</p>
            <?php else: ?>
                <div class="htb-pending-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h1>Order Submitted Successfully!</h1>
                <p class="htb-confirmation-message">Your order is pending approval. You'll receive a confirmation email once it's reviewed.</p>
            <?php endif; ?>

            <div class="htb-order-number-display">
                <span class="htb-order-label">Order Number:</span>
                <span class="htb-order-number"><?php echo esc_html($order['order_number']); ?></span>
            </div>
        </div>

        <!-- Order Status Badge -->
        <div class="htb-status-badge-container">
            <?php
            $status_class = '';
            $status_text = '';
            $status_icon = '';

            switch ($order['order_status']) {
                case 'approved':
                    $status_class = 'htb-status-approved';
                    $status_text = 'Approved';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    break;
                case 'pending':
                    $status_class = 'htb-status-pending';
                    $status_text = 'Pending Approval';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                    break;
                case 'processing':
                    $status_class = 'htb-status-processing';
                    $status_text = 'Processing';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>';
                    break;
                case 'shipped':
                    $status_class = 'htb-status-shipped';
                    $status_text = 'Shipped';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"></path><path d="M16 8h4l3 3v5h-7V8z"></path><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
                    break;
                case 'delivered':
                    $status_class = 'htb-status-delivered';
                    $status_text = 'Delivered';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
                    break;
                case 'cancelled':
                    $status_class = 'htb-status-cancelled';
                    $status_text = 'Cancelled';
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                    break;
                default:
                    $status_class = 'htb-status-pending';
                    $status_text = ucfirst($order['order_status']);
                    $status_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
            }
            ?>

            <div class="htb-status-badge <?php echo $status_class; ?>">
                <?php echo $status_icon; ?>
                <span><?php echo esc_html($status_text); ?></span>
            </div>
        </div>

        <!-- Order Details Grid -->
        <div class="htb-confirmation-content">

            <!-- Order Information -->
            <div class="htb-confirmation-section">
                <h2 class="htb-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Order Information
                </h2>

                <div class="htb-info-grid">
                    <div class="htb-info-row">
                        <span class="htb-info-label">Order Date:</span>
                        <span class="htb-info-value"><?php echo date('F j, Y @ g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="htb-info-row">
                        <span class="htb-info-label">Payment Terms:</span>
                        <span class="htb-info-value"><?php echo esc_html(strtoupper(str_replace('_', '-', $order['payment_terms']))); ?></span>
                    </div>
                    <?php if (!empty($order['po_number'])): ?>
                    <div class="htb-info-row">
                        <span class="htb-info-label">PO Number:</span>
                        <span class="htb-info-value"><?php echo esc_html($order['po_number']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="htb-confirmation-section">
                <h2 class="htb-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    Order Items
                </h2>

                <div class="htb-order-items-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="htb-text-center">Quantity</th>
                                <th class="htb-text-right">Price</th>
                                <th class="htb-text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="htb-item-name"><?php echo esc_html($item['product_name']); ?></div>
                                    <div class="htb-item-sku">SKU: <?php echo esc_html($item['sku']); ?></div>
                                </td>
                                <td class="htb-text-center"><?php echo intval($item['quantity']); ?></td>
                                <td class="htb-text-right">$<?php echo number_format(floatval($item['price']), 2); ?></td>
                                <td class="htb-text-right htb-item-total">$<?php echo number_format(floatval($item['line_total']), 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="htb-subtotal-row">
                                <td colspan="3" class="htb-text-right htb-label">Subtotal:</td>
                                <td class="htb-text-right">$<?php echo number_format(floatval($order['subtotal']), 2); ?></td>
                            </tr>
                            <tr class="htb-transport-row">
                                <td colspan="3" class="htb-text-right htb-label">Transport Fee:</td>
                                <td class="htb-text-right">$<?php echo number_format(floatval($order['transport_fee']), 2); ?></td>
                            </tr>
                            <tr class="htb-total-row">
                                <td colspan="3" class="htb-text-right htb-label">Order Total:</td>
                                <td class="htb-text-right htb-total">$<?php echo number_format(floatval($order['total']), 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="htb-confirmation-section">
                <h2 class="htb-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 3h15v13H1z"></path>
                        <path d="M16 8h4l3 3v5h-7V8z"></path>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    Delivery Information
                </h2>

                <div class="htb-delivery-address">
                    <p>
                        <?php echo nl2br(esc_html($order['delivery_address'])); ?><br>
                        <?php echo esc_html($order['delivery_city']); ?>, <?php echo esc_html($order['delivery_state']); ?> <?php echo esc_html($order['delivery_zip']); ?><br>
                        Phone: <?php echo esc_html($order['delivery_phone']); ?>
                    </p>

                    <?php if (!empty($order['delivery_instructions'])): ?>
                    <div class="htb-delivery-instructions">
                        <strong>Delivery Instructions:</strong>
                        <p><?php echo nl2br(esc_html($order['delivery_instructions'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($order['order_notes'])): ?>
            <!-- Order Notes -->
            <div class="htb-confirmation-section">
                <h2 class="htb-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Order Notes
                </h2>
                <p><?php echo nl2br(esc_html($order['order_notes'])); ?></p>
            </div>
            <?php endif; ?>

        </div>

        <!-- Action Buttons -->
        <div class="htb-confirmation-actions">
            <a href="<?php echo get_post_type_archive_link('htb_product'); ?>" class="htb-btn htb-btn-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Continue Shopping
            </a>
            <a href="<?php echo site_url('/partner-dashboard'); ?>" class="htb-btn htb-btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                View Dashboard
            </a>
        </div>

        <!-- Support Info -->
        <div class="htb-support-info">
            <h3>Need Help?</h3>
            <p>If you have any questions about your order, please contact us:</p>
            <p>
                <strong>Email:</strong> <a href="mailto:orders@happyturtleprocessing.com">orders@happyturtleprocessing.com</a><br>
                <strong>Phone:</strong> (501) 555-0100
            </p>
        </div>

    </div>
</div>

<?php get_footer(); ?>
