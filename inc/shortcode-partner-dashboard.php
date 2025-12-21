<?php
/**
 * Partner Dashboard Shortcode
 *
 * Displays partner dashboard with product export functionality
 *
 * Usage: [partner_dashboard]
 */

function happyturtle_partner_dashboard_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div style="padding:2rem;text-align:center;background:#f8d7da;border-radius:8px;color:#721c24;">
            <p><strong>Access Denied:</strong> Please log in to access the partner dashboard.</p>
            <p><a href="/wp-login.php">Login</a></p>
        </div>';
    }

    // Get products
    global $wpdb;
    $table = $wpdb->prefix . '1_happyturtle_products';
    $products = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

    ob_start();
    ?>
    <div id="partner-dashboard-app" style="max-width:1400px;margin:2rem auto;padding:0 2rem;">

        <!-- Product Export Section -->
        <div style="background:white;border-radius:16px;padding:3rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);margin-bottom:2rem;">
            <h2 style="color:#1B4332;margin-bottom:0.5rem;">
                <span style="font-size:1.5rem;">📦</span> Product Export
            </h2>
            <p style="margin-bottom:2rem;color:#64748b;">Export products from our catalog to import into your WooCommerce store or other systems.</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem;">
                <!-- Export Format Cards -->
                <div style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border-radius:12px;padding:1.5rem;border:2px solid #cbd5e1;">
                    <h3 style="color:#1B4332;margin-bottom:0.5rem;font-size:1.1rem;">
                        <span style="font-size:1.2rem;">🛍️</span> WooCommerce CSV
                    </h3>
                    <p style="color:#64748b;font-size:0.9rem;margin-bottom:1rem;">Import directly into WooCommerce</p>
                    <button onclick="exportProducts('woocommerce')"
                            style="width:100%;background:linear-gradient(135deg,#1B4332,#2D6A4F);color:white;border:none;border-radius:8px;padding:0.75rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;">
                        Export as WooCommerce CSV
                    </button>
                </div>

                <div style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border-radius:12px;padding:1.5rem;border:2px solid #cbd5e1;">
                    <h3 style="color:#1B4332;margin-bottom:0.5rem;font-size:1.1rem;">
                        <span style="font-size:1.2rem;">📄</span> Standard CSV
                    </h3>
                    <p style="color:#64748b;font-size:0.9rem;margin-bottom:1rem;">Universal spreadsheet format</p>
                    <button onclick="exportProducts('csv')"
                            style="width:100%;background:linear-gradient(135deg,#1B4332,#2D6A4F);color:white;border:none;border-radius:8px;padding:0.75rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;">
                        Export as CSV
                    </button>
                </div>

                <div style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border-radius:12px;padding:1.5rem;border:2px solid #cbd5e1;">
                    <h3 style="color:#1B4332;margin-bottom:0.5rem;font-size:1.1rem;">
                        <span style="font-size:1.2rem;">⚡</span> JSON API
                    </h3>
                    <p style="color:#64748b;font-size:0.9rem;margin-bottom:1rem;">For custom integrations</p>
                    <button onclick="exportProducts('json')"
                            style="width:100%;background:linear-gradient(135deg,#1B4332,#2D6A4F);color:white;border:none;border-radius:8px;padding:0.75rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;">
                        View JSON
                    </button>
                </div>
            </div>

            <!-- Export Progress -->
            <div id="export-progress" style="display:none;background:#e0f2fe;border:1px solid #bae6fd;border-radius:8px;padding:1rem;margin-bottom:2rem;color:#075985;">
                <strong>⏳ Exporting...</strong> Please wait while we prepare your download.
            </div>

            <!-- Export Success -->
            <div id="export-success" style="display:none;background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:1rem;margin-bottom:2rem;color:#155724;">
                <strong>✅ Export Complete!</strong> <span id="export-message"></span>
            </div>

            <!-- Export Error -->
            <div id="export-error" style="display:none;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;padding:1rem;margin-bottom:2rem;color:#721c24;">
                <strong>❌ Export Failed:</strong> <span id="error-message"></span>
            </div>
        </div>

        <!-- Product Selection Table -->
        <div style="background:white;border-radius:16px;padding:3rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
                <h3 style="color:#1B4332;margin:0;">
                    <span style="font-size:1.3rem;">🌿</span> Available Products
                </h3>
                <span style="color:#64748b;font-size:0.9rem;">
                    <?php echo count($products); ?> products available
                </span>
            </div>

            <?php if (empty($products)): ?>
                <p style="text-align:center;color:#64748b;padding:2rem;">No products available for export.</p>
            <?php else: ?>
                <!-- Select All Controls -->
                <div style="margin-bottom:1rem;padding:1rem;background:#f8fafc;border-radius:8px;display:flex;gap:1rem;align-items:center;">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                        <input type="checkbox" id="select-all-products" onclick="toggleSelectAll(this)">
                        <strong>Select All Products</strong>
                    </label>
                    <span style="color:#64748b;margin-left:auto;" id="selected-count">0 selected</span>
                </div>

                <!-- Products Table -->
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb;">
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">Select</th>
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">Product Name</th>
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">Type</th>
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">THC %</th>
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">Price</th>
                                <th style="padding:0.75rem;text-align:left;font-weight:600;color:#1B4332;">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:0.75rem;">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo esc_attr($product['id']); ?>" onchange="updateSelectedCount()">
                                </td>
                                <td style="padding:0.75rem;">
                                    <strong style="color:#1B4332;"><?php echo esc_html($product['name']); ?></strong>
                                    <br>
                                    <small style="color:#64748b;"><?php echo esc_html($product['biotrack_id']); ?></small>
                                </td>
                                <td style="padding:0.75rem;color:#64748b;">
                                    <?php echo esc_html(ucfirst($product['product_type'])); ?>
                                </td>
                                <td style="padding:0.75rem;color:#64748b;">
                                    <?php echo esc_html($product['thc_content']); ?>%
                                </td>
                                <td style="padding:0.75rem;color:#1B4332;">
                                    <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                                </td>
                                <td style="padding:0.75rem;">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span style="background:#d4edda;color:#155724;padding:0.25rem 0.75rem;border-radius:4px;font-size:0.85rem;">
                                            <?php echo esc_html($product['stock_quantity']); ?> in stock
                                        </span>
                                    <?php else: ?>
                                        <span style="background:#f8d7da;color:#721c24;padding:0.25rem 0.75rem;border-radius:4px;font-size:0.85rem;">
                                            Out of stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Export Selected Button -->
                <div style="margin-top:2rem;display:flex;gap:1rem;">
                    <button onclick="exportSelected('woocommerce')"
                            style="flex:1;background:linear-gradient(135deg,#1B4332,#2D6A4F,#D4A574);color:white;border:none;border-radius:8px;padding:1rem;font-weight:700;cursor:pointer;transition:all 0.3s ease;">
                        Export Selected as WooCommerce CSV
                    </button>
                    <button onclick="exportSelected('csv')"
                            style="flex:1;background:linear-gradient(135deg,#2D6A4F,#52B788);color:white;border:none;border-radius:8px;padding:1rem;font-weight:700;cursor:pointer;transition:all 0.3s ease;">
                        Export Selected as CSV
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- How to Import Guide -->
        <div style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border-radius:16px;padding:3rem;margin-top:2rem;border:2px solid #cbd5e1;">
            <h3 style="color:#1B4332;margin-bottom:1rem;">
                <span style="font-size:1.3rem;">💡</span> How to Import Into WooCommerce
            </h3>
            <ol style="color:#1e293b;line-height:1.8;padding-left:1.5rem;">
                <li>Export products using <strong>"Export as WooCommerce CSV"</strong> button above</li>
                <li>In your WordPress admin, go to <strong>Products → Import</strong></li>
                <li>Click <strong>"Choose File"</strong> and select the downloaded CSV</li>
                <li>Click <strong>"Continue"</strong> and map the columns (should auto-map)</li>
                <li>Click <strong>"Run the importer"</strong></li>
                <li>Review imported products in your Products list</li>
            </ol>
            <p style="margin-top:1rem;color:#64748b;font-size:0.9rem;">
                <strong>Note:</strong> Product images are not included in the export. You'll need to add your own product images after importing.
            </p>
        </div>
    </div>

    <script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.product-checkbox:checked');
        const count = checkboxes.length;
        const countEl = document.getElementById('selected-count');
        if (countEl) {
            countEl.textContent = count + ' selected';
        }
    }

    function getSelectedProductIds() {
        const checkboxes = document.querySelectorAll('.product-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    async function exportProducts(format) {
        hideMessages();
        showProgress();

        try {
            let url = '/wp-json/happyturtle/v1/export/products?format=' + format;

            if (format === 'json') {
                // For JSON, fetch and display
                const response = await fetch(url);
                const data = await response.json();

                hideProgress();
                showSuccess('JSON data displayed below. Copy to use in your integration.');

                // Display JSON in a modal or new window
                const jsonWindow = window.open('', '_blank');
                jsonWindow.document.write('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
                jsonWindow.document.close();
            } else {
                // For CSV, trigger download
                window.location.href = url;

                hideProgress();
                showSuccess('Download started! Check your downloads folder.');
            }
        } catch (error) {
            hideProgress();
            showError(error.message);
        }
    }

    async function exportSelected(format) {
        const productIds = getSelectedProductIds();

        if (productIds.length === 0) {
            showError('Please select at least one product to export.');
            return;
        }

        hideMessages();
        showProgress();

        try {
            let url = '/wp-json/happyturtle/v1/export/products?format=' + format + '&ids=' + productIds.join(',');

            // Trigger download
            window.location.href = url;

            hideProgress();
            showSuccess('Download started! Exporting ' + productIds.length + ' products.');
        } catch (error) {
            hideProgress();
            showError(error.message);
        }
    }

    function showProgress() {
        document.getElementById('export-progress').style.display = 'block';
    }

    function hideProgress() {
        document.getElementById('export-progress').style.display = 'none';
    }

    function showSuccess(message) {
        const el = document.getElementById('export-success');
        document.getElementById('export-message').textContent = message;
        el.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            el.style.display = 'none';
        }, 5000);
    }

    function showError(message) {
        const el = document.getElementById('export-error');
        document.getElementById('error-message').textContent = message;
        el.style.display = 'block';

        // Auto-hide after 8 seconds
        setTimeout(() => {
            el.style.display = 'none';
        }, 8000);
    }

    function hideMessages() {
        document.getElementById('export-success').style.display = 'none';
        document.getElementById('export-error').style.display = 'none';
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('partner_dashboard', 'happyturtle_partner_dashboard_shortcode');
