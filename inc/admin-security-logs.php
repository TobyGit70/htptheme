<?php
/**
 * Security Logs Viewer - Advanced Filtering & Export
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Security_Logs_Page {

    private $security_logger;

    public function __construct() {
        $this->security_logger = HappyTurtle_Security_Logger::get_instance();

        // Handle export
        add_action('admin_init', array($this, 'handle_export'));
    }

    /**
     * Render logs page
     */
    public function render() {
        global $wpdb;

        // Get filters
        $filters = $this->get_filters();

        // Get logs with pagination
        $per_page = 50;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        $logs = $this->get_filtered_logs($filters, $per_page, $offset);
        $total = $this->get_filtered_logs_count($filters);
        $total_pages = ceil($total / $per_page);

        ?>
        <div class="wrap happyturtle-security-logs">
            <h1>
                <span class="dashicons dashicons-list-view"></span>
                Access Logs
            </h1>

            <!-- Filters -->
            <div class="htb-filters-panel">
                <form method="get" action="">
                    <input type="hidden" name="page" value="happyturtle-security-logs">

                    <div class="htb-filters-grid">
                        <div class="htb-filter-group">
                            <label>Date Range</label>
                            <select name="date_range">
                                <option value="24h" <?php selected($filters['date_range'], '24h'); ?>>Last 24 Hours</option>
                                <option value="7d" <?php selected($filters['date_range'], '7d'); ?>>Last 7 Days</option>
                                <option value="30d" <?php selected($filters['date_range'], '30d'); ?>>Last 30 Days</option>
                                <option value="custom" <?php selected($filters['date_range'], 'custom'); ?>>Custom Range</option>
                            </select>
                        </div>

                        <div class="htb-filter-group" id="custom-date-range" style="display: <?php echo $filters['date_range'] === 'custom' ? 'block' : 'none'; ?>;">
                            <label>From</label>
                            <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>">
                            <label>To</label>
                            <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>">
                        </div>

                        <div class="htb-filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="success" <?php selected($filters['status'], 'success'); ?>>Success</option>
                                <option value="failed" <?php selected($filters['status'], 'failed'); ?>>Failed</option>
                                <option value="blocked" <?php selected($filters['status'], 'blocked'); ?>>Blocked</option>
                            </select>
                        </div>

                        <div class="htb-filter-group">
                            <label>Access Type</label>
                            <select name="access_type">
                                <option value="">All Types</option>
                                <option value="api" <?php selected($filters['access_type'], 'api'); ?>>API</option>
                                <option value="web" <?php selected($filters['access_type'], 'web'); ?>>Web</option>
                            </select>
                        </div>

                        <div class="htb-filter-group">
                            <label>Partner</label>
                            <select name="partner_id">
                                <option value="">All Partners</option>
                                <?php
                                $partners = $wpdb->get_results(
                                    "SELECT id, business_name FROM {$wpdb->prefix}1_happyturtle_partners ORDER BY business_name",
                                    ARRAY_A
                                );
                                foreach ($partners as $partner) {
                                    echo '<option value="' . esc_attr($partner['id']) . '" ' . selected($filters['partner_id'], $partner['id'], false) . '>' . esc_html($partner['business_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="htb-filter-group">
                            <label>IP Address</label>
                            <input type="text" name="ip_address" value="<?php echo esc_attr($filters['ip_address']); ?>" placeholder="e.g., 192.168.1.1">
                        </div>

                        <div class="htb-filter-group">
                            <label>Event Type</label>
                            <select name="event_type">
                                <option value="">All Events</option>
                                <option value="view_products" <?php selected($filters['event_type'], 'view_products'); ?>>View Products</option>
                                <option value="create_order" <?php selected($filters['event_type'], 'create_order'); ?>>Create Order</option>
                                <option value="view_orders" <?php selected($filters['event_type'], 'view_orders'); ?>>View Orders</option>
                                <option value="partner_registration" <?php selected($filters['event_type'], 'partner_registration'); ?>>Registration</option>
                                <option value="login" <?php selected($filters['event_type'], 'login'); ?>>Login</option>
                            </select>
                        </div>

                        <div class="htb-filter-group">
                            <label>Country</label>
                            <input type="text" name="country_code" value="<?php echo esc_attr($filters['country_code']); ?>" placeholder="e.g., US">
                        </div>
                    </div>

                    <div class="htb-filter-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-filter"></span>
                            Apply Filters
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=happyturtle-security-logs'); ?>" class="button">Reset</a>
                        <button type="submit" name="export" value="csv" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            Export CSV
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="htb-results-summary">
                <strong><?php echo number_format($total); ?></strong> log entries found
                <?php if ($total > 0): ?>
                    | Showing <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $per_page, $total)); ?>
                <?php endif; ?>
            </div>

            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped htb-logs-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Event</th>
                        <th>Partner</th>
                        <th>IP Address</th>
                        <th>Location</th>
                        <th>Endpoint</th>
                        <th>Response Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-info" style="font-size: 48px; color: #999;"></span>
                            <p>No logs found matching your filters.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="htb-log-row htb-log-<?php echo esc_attr($log['status']); ?>">
                            <td>
                                <strong><?php echo date('M j, Y', strtotime($log['created_at'])); ?></strong><br>
                                <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $status_colors = array(
                                    'success' => '#2D6A4F',
                                    'failed' => '#D4A574',
                                    'blocked' => '#dc3545'
                                );
                                $color = $status_colors[$log['status']] ?? '#999';
                                ?>
                                <span class="htb-status-badge" style="background: <?php echo $color; ?>;">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(strtoupper($log['access_type'])); ?></td>
                            <td>
                                <strong><?php echo esc_html(str_replace('_', ' ', ucwords($log['event_type'], '_'))); ?></strong><br>
                                <small><?php echo esc_html($log['method']); ?></small>
                            </td>
                            <td>
                                <?php if ($log['partner_id']): ?>
                                    <?php
                                    $partner = $wpdb->get_row($wpdb->prepare(
                                        "SELECT business_name FROM {$wpdb->prefix}1_happyturtle_partners WHERE id = %d",
                                        $log['partner_id']
                                    ));
                                    ?>
                                    <?php echo $partner ? esc_html($partner->business_name) : 'Unknown'; ?><br>
                                    <small>ID: <?php echo $log['partner_id']; ?></small>
                                <?php else: ?>
                                    <em>—</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($log['ip_address']); ?></code>
                            </td>
                            <td>
                                <?php if ($log['city'] && $log['region']): ?>
                                    <?php echo esc_html($log['city'] . ', ' . $log['region']); ?><br>
                                    <small><?php echo esc_html($log['country_code']); ?></small>
                                <?php else: ?>
                                    <em>Unknown</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($log['endpoint']); ?></code>
                            </td>
                            <td>
                                <?php if ($log['execution_time']): ?>
                                    <?php echo number_format($log['execution_time'] * 1000, 2); ?>ms
                                <?php else: ?>
                                    <em>—</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small htb-view-details" data-log-id="<?php echo $log['id']; ?>">
                                    Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total); ?> items</span>
                    <span class="pagination-links">
                        <?php
                        $base_url = add_query_arg(array_merge(array('page' => 'happyturtle-security-logs'), $filters), admin_url('admin.php'));

                        if ($page > 1) {
                            echo '<a class="button" href="' . esc_url(add_query_arg('paged', $page - 1, $base_url)) . '">‹ Previous</a> ';
                        }

                        echo '<span class="paging-input">Page ' . $page . ' of ' . $total_pages . '</span> ';

                        if ($page < $total_pages) {
                            echo '<a class="button" href="' . esc_url(add_query_arg('paged', $page + 1, $base_url)) . '">Next ›</a>';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Log Details Modal -->
            <div id="htb-log-modal" class="htb-modal" style="display: none;">
                <div class="htb-modal-content">
                    <span class="htb-modal-close">&times;</span>
                    <h2>Access Log Details</h2>
                    <div id="htb-log-modal-body"></div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                // Show/hide custom date range
                $('select[name="date_range"]').on('change', function() {
                    if ($(this).val() === 'custom') {
                        $('#custom-date-range').show();
                    } else {
                        $('#custom-date-range').hide();
                    }
                });

                // View details modal
                $('.htb-view-details').on('click', function() {
                    var logId = $(this).data('log-id');

                    $.post(ajaxurl, {
                        action: 'htb_get_log_details',
                        log_id: logId,
                        nonce: '<?php echo wp_create_nonce('htb_log_details'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#htb-log-modal-body').html(response.data.html);
                            $('#htb-log-modal').fadeIn();
                        }
                    });
                });

                // Close modal
                $('.htb-modal-close, #htb-log-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#htb-log-modal').fadeOut();
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Get filters from query string
     */
    private function get_filters() {
        return array(
            'date_range' => isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '24h',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'access_type' => isset($_GET['access_type']) ? sanitize_text_field($_GET['access_type']) : '',
            'partner_id' => isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0,
            'ip_address' => isset($_GET['ip_address']) ? sanitize_text_field($_GET['ip_address']) : '',
            'event_type' => isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '',
            'country_code' => isset($_GET['country_code']) ? sanitize_text_field($_GET['country_code']) : ''
        );
    }

    /**
     * Get filtered logs
     */
    private function get_filtered_logs($filters, $limit, $offset) {
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';
        $where = array('1=1');
        $values = array();

        // Date range
        if ($filters['date_range'] === 'custom') {
            if ($filters['date_from']) {
                $where[] = 'created_at >= %s';
                $values[] = $filters['date_from'] . ' 00:00:00';
            }
            if ($filters['date_to']) {
                $where[] = 'created_at <= %s';
                $values[] = $filters['date_to'] . ' 23:59:59';
            }
        } else {
            $cutoffs = array(
                '24h' => '-24 hours',
                '7d' => '-7 days',
                '30d' => '-30 days'
            );
            if (isset($cutoffs[$filters['date_range']])) {
                $where[] = 'created_at > %s';
                $values[] = date('Y-m-d H:i:s', strtotime($cutoffs[$filters['date_range']]));
            }
        }

        // Status
        if ($filters['status']) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        // Access type
        if ($filters['access_type']) {
            $where[] = 'access_type = %s';
            $values[] = $filters['access_type'];
        }

        // Partner
        if ($filters['partner_id']) {
            $where[] = 'partner_id = %d';
            $values[] = $filters['partner_id'];
        }

        // IP address
        if ($filters['ip_address']) {
            $where[] = 'ip_address LIKE %s';
            $values[] = '%' . $wpdb->esc_like($filters['ip_address']) . '%';
        }

        // Event type
        if ($filters['event_type']) {
            $where[] = 'event_type = %s';
            $values[] = $filters['event_type'];
        }

        // Country
        if ($filters['country_code']) {
            $where[] = 'country_code = %s';
            $values[] = strtoupper($filters['country_code']);
        }

        $where_clause = implode(' AND ', $where);
        $values[] = $limit;
        $values[] = $offset;

        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get filtered logs count
     */
    private function get_filtered_logs_count($filters) {
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';
        $where = array('1=1');
        $values = array();

        // Same filters as get_filtered_logs but without LIMIT
        // Date range
        if ($filters['date_range'] === 'custom') {
            if ($filters['date_from']) {
                $where[] = 'created_at >= %s';
                $values[] = $filters['date_from'] . ' 00:00:00';
            }
            if ($filters['date_to']) {
                $where[] = 'created_at <= %s';
                $values[] = $filters['date_to'] . ' 23:59:59';
            }
        } else {
            $cutoffs = array(
                '24h' => '-24 hours',
                '7d' => '-7 days',
                '30d' => '-30 days'
            );
            if (isset($cutoffs[$filters['date_range']])) {
                $where[] = 'created_at > %s';
                $values[] = date('Y-m-d H:i:s', strtotime($cutoffs[$filters['date_range']]));
            }
        }

        if ($filters['status']) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        if ($filters['access_type']) {
            $where[] = 'access_type = %s';
            $values[] = $filters['access_type'];
        }

        if ($filters['partner_id']) {
            $where[] = 'partner_id = %d';
            $values[] = $filters['partner_id'];
        }

        if ($filters['ip_address']) {
            $where[] = 'ip_address LIKE %s';
            $values[] = '%' . $wpdb->esc_like($filters['ip_address']) . '%';
        }

        if ($filters['event_type']) {
            $where[] = 'event_type = %s';
            $values[] = $filters['event_type'];
        }

        if ($filters['country_code']) {
            $where[] = 'country_code = %s';
            $values[] = strtoupper($filters['country_code']);
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_var($query);
    }

    /**
     * Handle CSV export
     */
    public function handle_export() {
        if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
            return;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'happyturtle-security-logs') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $filters = $this->get_filters();
        $logs = $this->get_filtered_logs($filters, 10000, 0); // Max 10,000 rows

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=happyturtle-security-logs-' . date('Y-m-d-His') . '.csv');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, array(
            'ID',
            'Date/Time',
            'Partner ID',
            'User ID',
            'Access Type',
            'Event Type',
            'Endpoint',
            'Method',
            'Status',
            'Status Code',
            'IP Address',
            'Country',
            'Region',
            'City',
            'User Agent',
            'Execution Time (ms)',
            'Error Message'
        ));

        // CSV rows
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log['id'],
                $log['created_at'],
                $log['partner_id'],
                $log['user_id'],
                $log['access_type'],
                $log['event_type'],
                $log['endpoint'],
                $log['method'],
                $log['status'],
                $log['status_code'],
                $log['ip_address'],
                $log['country_code'],
                $log['region'],
                $log['city'],
                $log['user_agent'],
                $log['execution_time'] ? round($log['execution_time'] * 1000, 2) : '',
                $log['error_message']
            ));
        }

        fclose($output);
        exit;
    }
}

// AJAX handler for log details
add_action('wp_ajax_htb_get_log_details', function() {
    check_ajax_referer('htb_log_details', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $log_id = intval($_POST['log_id']);

    global $wpdb;
    $log = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}1_happyturtle_access_log WHERE id = %d",
        $log_id
    ), ARRAY_A);

    if (!$log) {
        wp_send_json_error('Log not found');
    }

    ob_start();
    ?>
    <table class="htb-details-table">
        <tr>
            <th>Timestamp</th>
            <td><?php echo esc_html($log['created_at']); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><strong><?php echo esc_html(ucfirst($log['status'])); ?></strong> (Code: <?php echo $log['status_code']; ?>)</td>
        </tr>
        <tr>
            <th>Access Type</th>
            <td><?php echo esc_html($log['access_type']); ?></td>
        </tr>
        <tr>
            <th>Event Type</th>
            <td><?php echo esc_html($log['event_type']); ?></td>
        </tr>
        <tr>
            <th>Endpoint</th>
            <td><code><?php echo esc_html($log['endpoint']); ?></code></td>
        </tr>
        <tr>
            <th>Method</th>
            <td><?php echo esc_html($log['method']); ?></td>
        </tr>
        <tr>
            <th>IP Address</th>
            <td><code><?php echo esc_html($log['ip_address']); ?></code></td>
        </tr>
        <tr>
            <th>Location</th>
            <td>
                <?php if ($log['city']): ?>
                    <?php echo esc_html($log['city'] . ', ' . $log['region'] . ', ' . $log['country_code']); ?>
                <?php else: ?>
                    <em>Unknown</em>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>User Agent</th>
            <td><code><?php echo esc_html($log['user_agent']); ?></code></td>
        </tr>
        <tr>
            <th>Execution Time</th>
            <td><?php echo $log['execution_time'] ? number_format($log['execution_time'] * 1000, 2) . ' ms' : 'N/A'; ?></td>
        </tr>
        <?php if ($log['request_data']): ?>
        <tr>
            <th>Request Data</th>
            <td><pre><?php echo esc_html($log['request_data']); ?></pre></td>
        </tr>
        <?php endif; ?>
        <?php if ($log['response_data']): ?>
        <tr>
            <th>Response Data</th>
            <td><pre><?php echo esc_html($log['response_data']); ?></pre></td>
        </tr>
        <?php endif; ?>
        <?php if ($log['error_message']): ?>
        <tr>
            <th>Error Message</th>
            <td><code style="color: #dc3545;"><?php echo esc_html($log['error_message']); ?></code></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
});
