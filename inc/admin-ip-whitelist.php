<?php
/**
 * IP Whitelist Management
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_IP_Whitelist_Page {

    private $security_logger;

    public function __construct() {
        $this->security_logger = HappyTurtle_Security_Logger::get_instance();

        // Handle actions
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Render whitelist page
     */
    public function render() {
        global $wpdb;

        // Get all whitelist entries
        $table = $wpdb->prefix . '1_happyturtle_ip_whitelist';
        $entries = $wpdb->get_results(
            "SELECT w.*, p.business_name
            FROM {$table} w
            LEFT JOIN {$wpdb->prefix}1_happyturtle_partners p ON w.partner_id = p.id
            ORDER BY w.created_at DESC",
            ARRAY_A
        );

        // Get all partners for dropdown
        $partners = $wpdb->get_results(
            "SELECT id, business_name FROM {$wpdb->prefix}1_happyturtle_partners ORDER BY business_name",
            ARRAY_A
        );

        // Messages
        $message = '';
        if (isset($_GET['message'])) {
            $messages = array(
                'added' => 'IP address added to whitelist successfully.',
                'deleted' => 'IP address removed from whitelist.',
                'error' => 'An error occurred. Please try again.'
            );
            $message = $messages[$_GET['message']] ?? '';
        }

        ?>
        <div class="wrap happyturtle-ip-whitelist">
            <h1>
                <span class="dashicons dashicons-lock"></span>
                IP Whitelist Management
            </h1>

            <p>Restrict partner access to specific IP addresses for enhanced security. When IP whitelisting is enabled for a partner, they can only access the API from approved IP addresses.</p>

            <?php if ($message): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Add New IP -->
            <div class="htb-panel htb-add-ip-panel">
                <h2>Add IP to Whitelist</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('htb_add_ip_whitelist', 'htb_whitelist_nonce'); ?>
                    <input type="hidden" name="action" value="add_ip">

                    <table class="form-table">
                        <tr>
                            <th><label for="partner_id">Partner *</label></th>
                            <td>
                                <select name="partner_id" id="partner_id" required class="regular-text">
                                    <option value="">Select Partner</option>
                                    <?php foreach ($partners as $partner): ?>
                                    <option value="<?php echo esc_attr($partner['id']); ?>">
                                        <?php echo esc_html($partner['business_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ip_address">IP Address *</label></th>
                            <td>
                                <input type="text" name="ip_address" id="ip_address" required
                                       class="regular-text" placeholder="e.g., 192.168.1.100"
                                       pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                                <p class="description">Enter a single IPv4 address (e.g., 192.168.1.100)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="description">Description</label></th>
                            <td>
                                <input type="text" name="description" id="description"
                                       class="regular-text" placeholder="e.g., Main Office Router">
                                <p class="description">Optional note to identify this IP address</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Add to Whitelist
                        </button>
                    </p>
                </form>
            </div>

            <!-- Current Whitelist -->
            <div class="htb-panel">
                <h2>Current Whitelist (<?php echo count($entries); ?> entries)</h2>

                <?php if (empty($entries)): ?>
                <div class="htb-empty-state">
                    <span class="dashicons dashicons-info" style="font-size: 48px; color: #999;"></span>
                    <p>No IP addresses in whitelist yet.</p>
                    <p>Add IP addresses above to restrict partner access to specific locations.</p>
                </div>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>IP Address</th>
                            <th>Description</th>
                            <th>Added</th>
                            <th>Last Seen</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($entry['business_name'] ?: 'Unknown Partner'); ?></strong><br>
                                <small>ID: <?php echo $entry['partner_id']; ?></small>
                            </td>
                            <td>
                                <code style="font-size: 14px; background: #f0f0f1; padding: 4px 8px; border-radius: 3px;">
                                    <?php echo esc_html($entry['ip_address']); ?>
                                </code>
                            </td>
                            <td><?php echo $entry['description'] ? esc_html($entry['description']) : '<em>—</em>'; ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($entry['created_at'])); ?><br>
                                <small><?php echo date('H:i', strtotime($entry['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($entry['last_seen']): ?>
                                    <?php echo human_time_diff(strtotime($entry['last_seen']), current_time('timestamp')); ?> ago
                                <?php else: ?>
                                    <em>Never</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="" style="display: inline;">
                                    <?php wp_nonce_field('htb_delete_ip_whitelist', 'htb_whitelist_nonce'); ?>
                                    <input type="hidden" name="action" value="delete_ip">
                                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                    <button type="submit" class="button button-small button-link-delete"
                                            onclick="return confirm('Are you sure you want to remove this IP from the whitelist?');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Bulk Import -->
            <div class="htb-panel">
                <h2>Bulk Import</h2>
                <p>Import multiple IP addresses at once. One IP per line, optionally with description separated by comma.</p>

                <form method="post" action="">
                    <?php wp_nonce_field('htb_bulk_import_ip', 'htb_whitelist_nonce'); ?>
                    <input type="hidden" name="action" value="bulk_import">

                    <table class="form-table">
                        <tr>
                            <th><label for="bulk_partner_id">Partner *</label></th>
                            <td>
                                <select name="bulk_partner_id" id="bulk_partner_id" required class="regular-text">
                                    <option value="">Select Partner</option>
                                    <?php foreach ($partners as $partner): ?>
                                    <option value="<?php echo esc_attr($partner['id']); ?>">
                                        <?php echo esc_html($partner['business_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="bulk_ips">IP Addresses *</label></th>
                            <td>
                                <textarea name="bulk_ips" id="bulk_ips" rows="10" class="large-text code" required
                                          placeholder="192.168.1.100, Main Office&#10;192.168.1.101, Backup Router&#10;10.0.0.50"></textarea>
                                <p class="description">Format: <code>IP_ADDRESS, Description (optional)</code> - One per line</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-upload"></span>
                            Import IP Addresses
                        </button>
                    </p>
                </form>
            </div>

            <!-- Usage Statistics -->
            <div class="htb-panel">
                <h2>IP Whitelist Statistics</h2>
                <?php
                $stats = $wpdb->get_row(
                    "SELECT
                        COUNT(DISTINCT partner_id) as total_partners,
                        COUNT(*) as total_ips,
                        SUM(CASE WHEN last_seen IS NOT NULL THEN 1 ELSE 0 END) as active_ips
                    FROM {$table}",
                    ARRAY_A
                );
                ?>
                <div class="htb-stats-grid">
                    <div class="htb-stat-card">
                        <div class="htb-stat-value"><?php echo $stats['total_partners']; ?></div>
                        <div class="htb-stat-label">Partners with IP Restrictions</div>
                    </div>
                    <div class="htb-stat-card">
                        <div class="htb-stat-value"><?php echo $stats['total_ips']; ?></div>
                        <div class="htb-stat-label">Total Whitelisted IPs</div>
                    </div>
                    <div class="htb-stat-card">
                        <div class="htb-stat-value"><?php echo $stats['active_ips']; ?></div>
                        <div class="htb-stat-label">Recently Used IPs</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle whitelist actions
     */
    public function handle_actions() {
        if (!isset($_POST['action'])) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . '1_happyturtle_ip_whitelist';

        // Add IP
        if ($_POST['action'] === 'add_ip') {
            check_admin_referer('htb_add_ip_whitelist', 'htb_whitelist_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $partner_id = intval($_POST['partner_id']);
            $ip_address = sanitize_text_field($_POST['ip_address']);
            $description = sanitize_text_field($_POST['description']);

            // Validate IP
            if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
                exit;
            }

            // Check if already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE partner_id = %d AND ip_address = %s",
                $partner_id,
                $ip_address
            ));

            if ($exists) {
                wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
                exit;
            }

            $wpdb->insert($table, array(
                'partner_id' => $partner_id,
                'ip_address' => $ip_address,
                'description' => $description,
                'created_at' => current_time('mysql')
            ));

            wp_redirect(add_query_arg('message', 'added', wp_get_referer()));
            exit;
        }

        // Delete IP
        if ($_POST['action'] === 'delete_ip') {
            check_admin_referer('htb_delete_ip_whitelist', 'htb_whitelist_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $entry_id = intval($_POST['entry_id']);
            $wpdb->delete($table, array('id' => $entry_id));

            wp_redirect(add_query_arg('message', 'deleted', wp_get_referer()));
            exit;
        }

        // Bulk import
        if ($_POST['action'] === 'bulk_import') {
            check_admin_referer('htb_bulk_import_ip', 'htb_whitelist_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $partner_id = intval($_POST['bulk_partner_id']);
            $bulk_ips = sanitize_textarea_field($_POST['bulk_ips']);

            $lines = explode("\n", $bulk_ips);
            $imported = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Parse line
                $parts = array_map('trim', explode(',', $line));
                $ip_address = $parts[0];
                $description = $parts[1] ?? '';

                // Validate IP
                if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    continue;
                }

                // Check if already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE partner_id = %d AND ip_address = %s",
                    $partner_id,
                    $ip_address
                ));

                if ($exists) {
                    continue;
                }

                $wpdb->insert($table, array(
                    'partner_id' => $partner_id,
                    'ip_address' => $ip_address,
                    'description' => $description,
                    'created_at' => current_time('mysql')
                ));

                $imported++;
            }

            wp_redirect(add_query_arg('message', 'added', wp_get_referer()));
            exit;
        }
    }
}
