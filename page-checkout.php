<?php
/**
 * Template Name: Checkout
 * Description: B2B Checkout page with order approval workflows
 */

// Require authentication for checkout (Arkansas compliance)
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

// Get current user and cart
$user_id = get_current_user_id();
$cart = get_user_meta($user_id, '_htb_cart', true);

// Check if cart is empty
if (!is_array($cart) || empty($cart)) {
    ?>
    <div class="htb-checkout-page">
        <div class="wp-block-group alignwide">
            <div class="htb-empty-cart">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h2>Your cart is empty</h2>
                <p>Add some products to your cart before proceeding to checkout.</p>
                <a href="<?php echo get_post_type_archive_link('htb_product'); ?>" class="htb-btn htb-btn-primary">Browse Products</a>
            </div>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Get partner information
global $wpdb;
$partner = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}1_happyturtle_partners WHERE wp_user_id = %d",
    $user_id
), ARRAY_A);

// Calculate cart totals
$cart_items = array();
$subtotal = 0;

foreach ($cart as $product_id => $item) {
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'htb_product') {
        continue;
    }

    $quantity = intval($item['quantity']);
    $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);
    $tiered_pricing = get_post_meta($product_id, '_tiered_pricing', true);
    $sku = get_post_meta($product_id, '_sku', true);
    $unit_type = get_post_meta($product_id, '_unit_type', true);

    // Calculate item price with tiered pricing
    $item_price = floatval($wholesale_price);
    $has_discount = false;

    if ($tiered_pricing && is_array($tiered_pricing)) {
        foreach (array_reverse($tiered_pricing) as $tier) {
            if ($quantity >= intval($tier['min_qty'])) {
                $item_price = floatval($tier['price']);
                $has_discount = true;
                break;
            }
        }
    }

    $line_total = $item_price * $quantity;
    $subtotal += $line_total;

    $cart_items[] = array(
        'product_id' => $product_id,
        'product' => $product,
        'quantity' => $quantity,
        'price' => $item_price,
        'original_price' => floatval($wholesale_price),
        'has_discount' => $has_discount,
        'line_total' => $line_total,
        'sku' => $sku,
        'unit_type' => $unit_type
    );
}

// Calculate transport fee
$transport_fee = 0;
if (class_exists('HappyTurtle_Order_Settings')) {
    $transport_fee = HappyTurtle_Order_Settings::calculate_transport_fee($cart, $subtotal);
}

$total = $subtotal + $transport_fee;

// Get payment terms options
$payment_terms_options = array(
    'net_15' => 'Net-15 (Payment due in 15 days)',
    'net_30' => 'Net-30 (Payment due in 30 days)',
    'net_45' => 'Net-45 (Payment due in 45 days)',
    'net_60' => 'Net-60 (Payment due in 60 days)',
    'net_90' => 'Net-90 (Payment due in 90 days)',
    'cod' => 'COD (Cash on Delivery)',
    'prepaid' => 'Prepaid (Payment before shipment)',
);

$default_payment_terms = get_option('htb_default_payment_terms', 'net_30');

?>

<div class="htb-checkout-page">
    <div class="wp-block-group alignwide">

        <!-- Checkout Header -->
        <div class="htb-checkout-header">
            <h1>Checkout</h1>
            <div class="htb-checkout-steps">
                <div class="htb-step htb-step-active">
                    <div class="htb-step-number">1</div>
                    <div class="htb-step-label">Review Order</div>
                </div>
                <div class="htb-step-connector"></div>
                <div class="htb-step">
                    <div class="htb-step-number">2</div>
                    <div class="htb-step-label">Submit for Approval</div>
                </div>
                <div class="htb-step-connector"></div>
                <div class="htb-step">
                    <div class="htb-step-number">3</div>
                    <div class="htb-step-label">Confirmation</div>
                </div>
            </div>
        </div>

        <form id="htb-checkout-form" class="htb-checkout-form" method="post" action="">
            <?php wp_nonce_field('htb_checkout_nonce', 'htb_checkout_nonce'); ?>

            <div class="htb-checkout-content">

                <!-- Main Checkout Area -->
                <div class="htb-checkout-main">

                    <!-- Partner Information -->
                    <div class="htb-checkout-section">
                        <h2 class="htb-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Partner Information
                        </h2>
                        <div class="htb-info-card">
                            <div class="htb-info-row">
                                <span class="htb-info-label">Business Name:</span>
                                <span class="htb-info-value"><?php echo esc_html($partner['business_name']); ?></span>
                            </div>
                            <div class="htb-info-row">
                                <span class="htb-info-label">License Number:</span>
                                <span class="htb-info-value"><?php echo esc_html($partner['license_number']); ?></span>
                            </div>
                            <div class="htb-info-row">
                                <span class="htb-info-label">Contact:</span>
                                <span class="htb-info-value"><?php echo esc_html($partner['contact_name']); ?></span>
                            </div>
                            <div class="htb-info-row">
                                <span class="htb-info-label">Email:</span>
                                <span class="htb-info-value"><?php echo esc_html($partner['email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="htb-checkout-section">
                        <h2 class="htb-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="3" width="15" height="13"></rect>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                            Delivery Information
                        </h2>

                        <div class="htb-form-row">
                            <div class="htb-form-group">
                                <label for="delivery_address">Delivery Address *</label>
                                <textarea
                                    id="delivery_address"
                                    name="delivery_address"
                                    rows="3"
                                    required
                                    placeholder="Enter full delivery address"
                                ><?php echo esc_textarea($partner['address']); ?></textarea>
                            </div>
                        </div>

                        <div class="htb-form-row htb-form-row-2col">
                            <div class="htb-form-group">
                                <label for="delivery_city">City *</label>
                                <input
                                    type="text"
                                    id="delivery_city"
                                    name="delivery_city"
                                    value="<?php echo esc_attr($partner['city']); ?>"
                                    required>
                            </div>
                            <div class="htb-form-group">
                                <label for="delivery_state">State *</label>
                                <input
                                    type="text"
                                    id="delivery_state"
                                    name="delivery_state"
                                    value="<?php echo esc_attr($partner['state']); ?>"
                                    required
                                    maxlength="2"
                                    placeholder="AR">
                            </div>
                        </div>

                        <div class="htb-form-row htb-form-row-2col">
                            <div class="htb-form-group">
                                <label for="delivery_zip">ZIP Code *</label>
                                <input
                                    type="text"
                                    id="delivery_zip"
                                    name="delivery_zip"
                                    value="<?php echo esc_attr($partner['zip']); ?>"
                                    required
                                    maxlength="10"
                                    placeholder="72201">
                            </div>
                            <div class="htb-form-group">
                                <label for="delivery_phone">Delivery Contact Phone *</label>
                                <input
                                    type="tel"
                                    id="delivery_phone"
                                    name="delivery_phone"
                                    value="<?php echo esc_attr($partner['phone']); ?>"
                                    required
                                    placeholder="(501) 555-1234">
                            </div>
                        </div>

                        <div class="htb-form-row">
                            <div class="htb-form-group">
                                <label for="delivery_instructions">Delivery Instructions (Optional)</label>
                                <textarea
                                    id="delivery_instructions"
                                    name="delivery_instructions"
                                    rows="2"
                                    placeholder="Any special delivery instructions or requirements"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items Review -->
                    <div class="htb-checkout-section">
                        <h2 class="htb-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            Order Review (<?php echo count($cart_items); ?> items)
                        </h2>

                        <div class="htb-checkout-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="htb-checkout-item">
                                    <div class="htb-checkout-item-image">
                                        <?php if (has_post_thumbnail($item['product_id'])): ?>
                                            <?php echo get_the_post_thumbnail($item['product_id'], 'thumbnail'); ?>
                                        <?php else: ?>
                                            <div class="htb-cart-placeholder">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21 15 16 10 5 21"></polyline>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="htb-checkout-item-details">
                                        <h4><?php echo esc_html($item['product']->post_title); ?></h4>
                                        <div class="htb-checkout-item-meta">
                                            <span class="htb-checkout-sku">SKU: <?php echo esc_html($item['sku']); ?></span>
                                            <span class="htb-checkout-qty">Qty: <?php echo intval($item['quantity']); ?></span>
                                        </div>
                                    </div>

                                    <div class="htb-checkout-item-price">
                                        <?php if ($item['has_discount']): ?>
                                            <span class="htb-price-original">$<?php echo number_format($item['original_price'], 2); ?></span>
                                            <span class="htb-price-discounted">$<?php echo number_format($item['price'], 2); ?></span>
                                            <span class="htb-price-unit">/<?php echo esc_html($item['unit_type']); ?></span>
                                        <?php else: ?>
                                            <span class="htb-price-regular">$<?php echo number_format($item['price'], 2); ?></span>
                                            <span class="htb-price-unit">/<?php echo esc_html($item['unit_type']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="htb-checkout-item-total">
                                        <span class="htb-total-label">Total:</span>
                                        <span class="htb-total-amount">$<?php echo number_format($item['line_total'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="<?php echo site_url('/cart/'); ?>" class="htb-btn htb-btn-secondary htb-edit-cart-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Cart
                        </a>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="htb-checkout-section">
                        <h2 class="htb-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Payment Method
                        </h2>

                        <?php
                        // Get available payment methods
                        $payment_gateways = HappyTurtle_Payment_Gateways::get_instance();
                        $payment_methods = $payment_gateways->get_payment_methods(true);
                        $cannabis_warnings = $payment_gateways->get_cannabis_warnings();
                        ?>

                        <!-- Cannabis Business Warning -->
                        <div class="htb-cannabis-warning" style="margin-bottom: 20px; padding: 15px; background: #fff8e1; border-left: 4px solid #ffc107; border-radius: 4px;">
                            <div style="display: flex; align-items: start; gap: 10px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <div>
                                    <strong style="display: block; margin-bottom: 5px; color: #e65100;">Cannabis Business Notice</strong>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #666;">
                                        <?php echo esc_html($cannabis_warnings['partner_warning']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Options -->
                        <div class="htb-payment-methods">
                            <?php foreach ($payment_methods as $method_id => $method): ?>
                                <label class="htb-payment-method-option">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="<?php echo esc_attr($method_id); ?>"
                                        <?php checked($method_id, 'credit_terms'); ?>
                                        required>
                                    <div class="htb-payment-method-card">
                                        <div class="htb-payment-method-header">
                                            <strong><?php echo esc_html($method['label']); ?></strong>
                                            <span class="htb-payment-badge"><?php echo esc_html($method['fees']); ?></span>
                                        </div>
                                        <div class="htb-payment-method-description">
                                            <?php echo esc_html($method['description']); ?>
                                        </div>
                                        <div class="htb-payment-method-meta">
                                            <span class="htb-processing-time">⏱ <?php echo esc_html($method['processing_time']); ?></span>
                                        </div>
                                        <?php if (isset($method['note'])): ?>
                                            <div class="htb-payment-note">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                </svg>
                                                <span><?php echo esc_html($method['note']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Credit Terms Selection (shown when credit_terms is selected) -->
                        <div id="credit-terms-options" class="htb-payment-details" style="display: block; margin-top: 15px;">
                            <div class="htb-form-row">
                                <div class="htb-form-group">
                                    <label for="payment_terms">Select Payment Terms *</label>
                                    <select id="payment_terms" name="payment_terms">
                                        <?php foreach ($payment_terms_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($default_payment_terms, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Stripe Payment Details -->
                        <div id="stripe-payment-details" class="htb-payment-details" style="display: none; margin-top: 15px;">
                            <div class="htb-payment-processor-warning" style="padding: 12px; background: #e3f2fd; border-radius: 4px; margin-bottom: 15px;">
                                <strong>Processor Notice:</strong> <?php echo esc_html($cannabis_warnings['processor_warning']); ?>
                            </div>
                            <div class="htb-form-row">
                                <div class="htb-form-group">
                                    <label>Credit/Debit Card</label>
                                    <p style="margin: 5px 0; font-size: 13px; color: #666;">
                                        You will be redirected to Stripe's secure payment page after submitting your order.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- PayPal Payment Details -->
                        <div id="paypal-payment-details" class="htb-payment-details" style="display: none; margin-top: 15px;">
                            <div class="htb-payment-processor-warning" style="padding: 12px; background: #e3f2fd; border-radius: 4px; margin-bottom: 15px;">
                                <strong>Processor Notice:</strong> <?php echo esc_html($cannabis_warnings['processor_warning']); ?>
                            </div>
                            <div class="htb-form-row">
                                <div class="htb-form-group">
                                    <label>PayPal Account</label>
                                    <p style="margin: 5px 0; font-size: 13px; color: #666;">
                                        You will be redirected to PayPal to complete your payment after submitting your order.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Authorize.net Payment Details -->
                        <div id="authorize_net-payment-details" class="htb-payment-details" style="display: none; margin-top: 15px;">
                            <div class="htb-payment-processor-warning" style="padding: 12px; background: #e3f2fd; border-radius: 4px; margin-bottom: 15px;">
                                <strong>Processor Notice:</strong> <?php echo esc_html($cannabis_warnings['processor_warning']); ?>
                            </div>
                            <div class="htb-form-row">
                                <div class="htb-form-group">
                                    <label>Credit Card Payment</label>
                                    <p style="margin: 5px 0; font-size: 13px; color: #666;">
                                        You will be directed to a secure payment page after submitting your order.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ACH Payment Details -->
                        <div id="ach-payment-details" class="htb-payment-details" style="display: none; margin-top: 15px;">
                            <div class="htb-ach-warning" style="padding: 12px; background: #fff3e0; border-left: 3px solid #ff9800; border-radius: 4px; margin-bottom: 15px;">
                                <strong>⚠️ ACH/Bank Transfer Notice:</strong> <?php echo esc_html($cannabis_warnings['ach_warning']); ?>
                            </div>
                            <div class="htb-form-row">
                                <div class="htb-form-group">
                                    <label>Bank Transfer Instructions</label>
                                    <p style="margin: 5px 0; font-size: 13px; color: #666;">
                                        Bank account details will be provided after order submission. Your order will be processed once payment is received and cleared (3-5 business days).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Order & Requisition Information -->
                    <div class="htb-checkout-section">
                        <h2 class="htb-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            Purchase Order & Requisition (Optional)
                        </h2>

                        <div class="htb-form-row">
                            <div class="htb-form-group">
                                <label for="po_number">Purchase Order (PO) Number</label>
                                <input
                                    type="text"
                                    id="po_number"
                                    name="po_number"
                                    placeholder="Enter your PO number if applicable"
                                    maxlength="100">
                                <p class="htb-field-help">If your organization requires a PO number for this purchase</p>
                            </div>
                        </div>

                        <div class="htb-form-row htb-form-row-2col">
                            <div class="htb-form-group">
                                <label for="requisition_number">Requisition Number</label>
                                <input
                                    type="text"
                                    id="requisition_number"
                                    name="requisition_number"
                                    placeholder="REQ-12345"
                                    maxlength="100">
                            </div>
                            <div class="htb-form-group">
                                <label for="requisition_department">Department</label>
                                <input
                                    type="text"
                                    id="requisition_department"
                                    name="requisition_department"
                                    placeholder="e.g., Purchasing, Operations"
                                    maxlength="100">
                            </div>
                        </div>

                        <div class="htb-form-row">
                            <div class="htb-form-group">
                                <label for="requisition_approver">Approving Manager/Supervisor</label>
                                <input
                                    type="text"
                                    id="requisition_approver"
                                    name="requisition_approver"
                                    placeholder="Name of approving authority"
                                    maxlength="100">
                                <p class="htb-field-help">Name of the person who approved this requisition</p>
                            </div>
                        </div>

                        <div class="htb-form-row">
                            <div class="htb-form-group">
                                <label for="order_notes">Order Notes / Special Instructions</label>
                                <textarea
                                    id="order_notes"
                                    name="order_notes"
                                    rows="3"
                                    placeholder="Any special requests or notes for this order"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Order Summary Sidebar -->
                <div class="htb-checkout-sidebar">

                    <div class="htb-order-summary">
                        <h3>Order Summary</h3>

                        <div class="htb-summary-row">
                            <span>Subtotal:</span>
                            <span class="htb-summary-subtotal">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="htb-summary-row">
                            <span>Transport Fee:</span>
                            <span class="htb-summary-transport">$<?php echo number_format($transport_fee, 2); ?></span>
                        </div>

                        <div class="htb-summary-divider"></div>

                        <div class="htb-summary-row htb-summary-total-row">
                            <span>Order Total:</span>
                            <span class="htb-summary-total">$<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="submit" class="htb-btn htb-btn-primary htb-btn-block htb-submit-order-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Submit Order for Approval
                        </button>

                        <div class="htb-approval-info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <p>Your order will be reviewed and you will receive a confirmation email once approved.</p>
                        </div>
                    </div>

                    <!-- Trust Badges -->
                    <div class="htb-checkout-trust-badges">
                        <div class="htb-trust-badge">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span>Secure Ordering</span>
                        </div>
                        <div class="htb-trust-badge">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>Licensed Business</span>
                        </div>
                        <div class="htb-trust-badge">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span>Support Available</span>
                        </div>
                    </div>

                </div>

            </div>

        </form>

    </div>
</div>

<style>
/* Payment Method Styles */
.htb-payment-methods {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.htb-payment-method-option {
    cursor: pointer;
    display: block;
}

.htb-payment-method-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.htb-payment-method-card {
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    transition: all 0.2s ease;
}

.htb-payment-method-option input[type="radio"]:checked + .htb-payment-method-card {
    border-color: #2271b1;
    background: #f0f6fc;
}

.htb-payment-method-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.htb-payment-method-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.htb-payment-method-header strong {
    font-size: 15px;
    color: #1e1e1e;
}

.htb-payment-badge {
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976d2;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
}

.htb-payment-method-description {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
}

.htb-payment-method-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #888;
}

.htb-processing-time {
    display: flex;
    align-items: center;
    gap: 4px;
}

.htb-payment-note {
    display: flex;
    align-items: start;
    gap: 6px;
    margin-top: 10px;
    padding: 8px;
    background: #fff3e0;
    border-radius: 4px;
    font-size: 12px;
    color: #e65100;
}

.htb-payment-note svg {
    flex-shrink: 0;
    margin-top: 1px;
    stroke: #ff9800;
}

.htb-field-help {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #666;
}

.htb-payment-details {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const paymentDetails = {
        'credit_terms': document.getElementById('credit-terms-options'),
        'stripe': document.getElementById('stripe-payment-details'),
        'paypal': document.getElementById('paypal-payment-details'),
        'authorize_net': document.getElementById('authorize_net-payment-details'),
        'ach': document.getElementById('ach-payment-details')
    };

    // Function to show/hide payment details
    function updatePaymentDetails() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) return;

        const selectedValue = selectedMethod.value;

        // Hide all payment details
        Object.values(paymentDetails).forEach(detail => {
            if (detail) detail.style.display = 'none';
        });

        // Show selected payment details
        if (paymentDetails[selectedValue]) {
            paymentDetails[selectedValue].style.display = 'block';
        }

        // Update payment terms requirement based on method
        const paymentTermsSelect = document.getElementById('payment_terms');
        if (paymentTermsSelect) {
            paymentTermsSelect.required = (selectedValue === 'credit_terms');
        }
    }

    // Add event listeners to all radio buttons
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', updatePaymentDetails);
    });

    // Initialize on page load
    updatePaymentDetails();
});
</script>

<?php get_footer(); ?>
