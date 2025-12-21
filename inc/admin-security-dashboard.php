<?php
/**
 * Security Dashboard - Main Overview Page
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Security_Dashboard {

    private $security_logger;

    public function __construct() {
        $this->security_logger = HappyTurtle_Security_Logger::get_instance();

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue admin styles/scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main security menu
        add_menu_page(
            'Security Dashboard',
            'Security',
            'manage_options',
            'happyturtle-security',
            array($this, 'render_dashboard'),
            'dashicons-shield',
            30
        );

        // Submenu pages
        add_submenu_page(
            'happyturtle-security',
            'Security Dashboard',
            'Dashboard',
            'manage_options',
            'happyturtle-security',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'happyturtle-security',
            'Access Logs',
            'Access Logs',
            'manage_options',
            'happyturtle-security-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'happyturtle-security',
            'IP Whitelist',
            'IP Whitelist',
            'manage_options',
            'happyturtle-security-whitelist',
            array($this, 'render_whitelist_page')
        );

        add_submenu_page(
            'happyturtle-security',
            'Settings',
            'Settings',
            'manage_options',
            'happyturtle-security-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'happyturtle-security') === false) {
            return;
        }

        wp_enqueue_style('happyturtle-security-admin', get_template_directory_uri() . '/assets/css/security-admin.css', array(), '1.0.0');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_script('happyturtle-security-admin', get_template_directory_uri() . '/assets/js/security-admin.js', array('jquery', 'chart-js'), '1.0.0', true);
    }

    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        global $wpdb;

        // Get statistics
        $stats = $this->get_dashboard_stats();
        $recent_logs = $this->get_recent_logs(10);
        $top_partners = $this->get_top_partners(5);
        $hourly_data = $this->get_hourly_activity();

        ?>
        <div class="wrap happyturtle-security-dashboard">
            <h1>
                <span class="dashicons dashicons-shield"></span>
                Security Dashboard
            </h1>

            <div class="htb-dashboard-header">
                <div class="htb-time-range">
                    <select id="htb-time-range">
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="htb-stats-grid">
                <div class="htb-stat-card htb-stat-total">
                    <div class="htb-stat-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                    <div class="htb-stat-content">
                        <div class="htb-stat-value"><?php echo number_format($stats['total_requests']); ?></div>
                        <div class="htb-stat-label">Total Requests</div>
                    </div>
                </div>

                <div class="htb-stat-card htb-stat-success">
                    <div class="htb-stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="htb-stat-content">
                        <div class="htb-stat-value"><?php echo number_format($stats['successful_requests']); ?></div>
                        <div class="htb-stat-label">Successful</div>
                        <div class="htb-stat-percentage"><?php echo $stats['success_rate']; ?>%</div>
                    </div>
                </div>

                <div class="htb-stat-card htb-stat-failed">
                    <div class="htb-stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                    <div class="htb-stat-content">
                        <div class="htb-stat-value"><?php echo number_format($stats['failed_requests']); ?></div>
                        <div class="htb-stat-label">Failed Auth</div>
                        <div class="htb-stat-percentage"><?php echo $stats['failure_rate']; ?>%</div>
                    </div>
                </div>

                <div class="htb-stat-card htb-stat-blocked">
                    <div class="htb-stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="htb-stat-content">
                        <div class="htb-stat-value"><?php echo number_format($stats['blocked_requests']); ?></div>
                        <div class="htb-stat-label">Rate Limited</div>
                    </div>
                </div>
            </div>

            <div class="htb-dashboard-grid">
                <!-- Activity Chart -->
                <div class="htb-dashboard-panel htb-panel-chart">
                    <h2>Activity Timeline</h2>
                    <canvas id="htb-activity-chart" width="400" height="200"></canvas>
                </div>

                <!-- Top Partners -->
                <div class="htb-dashboard-panel htb-panel-partners">
                    <h2>Top Partners (24h)</h2>
                    <table class="htb-mini-table">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Requests</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_partners as $partner): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($partner['business_name'] ?: 'Unknown'); ?></strong>
                                    <?php if ($partner['partner_id']): ?>
                                    <br><small>ID: <?php echo $partner['partner_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($partner['request_count']); ?></td>
                                <td>
                                    <span class="htb-success-badge" style="background: <?php echo $partner['success_rate'] > 90 ? '#2D6A4F' : '#D4A574'; ?>">
                                        <?php echo $partner['success_rate']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Access Logs -->
                <div class="htb-dashboard-panel htb-panel-logs">
                    <h2>Recent Activity</h2>
                    <div class="htb-log-list">
                        <?php foreach ($recent_logs as $log): ?>
                        <div class="htb-log-item htb-log-<?php echo esc_attr($log['status']); ?>">
                            <div class="htb-log-icon">
                                <?php
                                if ($log['status'] === 'success') {
                                    echo '<span class="dashicons dashicons-yes" style="color: #2D6A4F;"></span>';
                                } elseif ($log['status'] === 'blocked') {
                                    echo '<span class="dashicons dashicons-no" style="color: #dc3545;"></span>';
                                } else {
                                    echo '<span class="dashicons dashicons-warning" style="color: #D4A574;"></span>';
                                }
                                ?>
                            </div>
                            <div class="htb-log-content">
                                <div class="htb-log-title">
                                    <strong><?php echo esc_html($log['event_type']); ?></strong>
                                    <span class="htb-log-status"><?php echo esc_html($log['status']); ?></span>
                                </div>
                                <div class="htb-log-meta">
                                    <?php echo esc_html($log['endpoint']); ?> •
                                    IP: <?php echo esc_html($log['ip_address']); ?>
                                    <?php if ($log['city']): ?>
                                        • <?php echo esc_html($log['city'] . ', ' . $log['region']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="htb-log-time"><?php echo human_time_diff(strtotime($log['created_at']), current_time('timestamp')); ?> ago</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=happyturtle-security-logs'); ?>" class="button button-primary">View All Logs</a>
                </div>

                <!-- Alerts Panel -->
                <div class="htb-dashboard-panel htb-panel-alerts">
                    <h2>Active Alerts</h2>
                    <?php $alerts = $this->get_active_alerts(); ?>
                    <?php if (empty($alerts)): ?>
                        <div class="htb-no-alerts">
                            <span class="dashicons dashicons-yes-alt" style="color: #2D6A4F; font-size: 48px;"></span>
                            <p>No active security alerts</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                        <div class="htb-alert-item htb-alert-<?php echo esc_attr($alert['severity']); ?>">
                            <div class="htb-alert-icon">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <div class="htb-alert-content">
                                <strong><?php echo esc_html($alert['title']); ?></strong>
                                <p><?php echo esc_html($alert['message']); ?></p>
                                <small><?php echo esc_html($alert['time']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            // Chart data
            var hourlyData = <?php echo json_encode($hourly_data); ?>;
            </script>
        </div>
        <?php
    }

    /**
     * Render logs page (placeholder - full implementation in separate file)
     */
    public function render_logs_page() {
        require_once get_template_directory() . '/inc/admin-security-logs.php';
        $logs_page = new HappyTurtle_Security_Logs_Page();
        $logs_page->render();
    }

    /**
     * Render whitelist page (placeholder)
     */
    public function render_whitelist_page() {
        require_once get_template_directory() . '/inc/admin-ip-whitelist.php';
        $whitelist_page = new HappyTurtle_IP_Whitelist_Page();
        $whitelist_page->render();
    }

    /**
     * Render settings page (placeholder)
     */
    public function render_settings_page() {
        require_once get_template_directory() . '/inc/admin-security-settings.php';
        $settings_page = new HappyTurtle_Security_Settings_Page();
        $settings_page->render();
    }

    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at > %s",
            $cutoff
        ));

        $successful = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND status = 'success'",
            $cutoff
        ));

        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND status = 'failed'",
            $cutoff
        ));

        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND status = 'blocked'",
            $cutoff
        ));

        $success_rate = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        $failure_rate = $total > 0 ? round(($failed / $total) * 100, 1) : 0;

        return array(
            'total_requests' => $total,
            'successful_requests' => $successful,
            'failed_requests' => $failed,
            'blocked_requests' => $blocked,
            'success_rate' => $success_rate,
            'failure_rate' => $failure_rate
        );
    }

    /**
     * Get recent logs
     */
    private function get_recent_logs($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
            $limit
        ), ARRAY_A);
    }

    /**
     * Get top partners
     */
    private function get_top_partners($limit = 5) {
        global $wpdb;

        $log_table = $wpdb->prefix . '1_happyturtle_access_log';
        $partner_table = $wpdb->prefix . '1_happyturtle_partners';
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                l.partner_id,
                p.business_name,
                COUNT(*) as request_count,
                SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) as success_count
            FROM {$log_table} l
            LEFT JOIN {$partner_table} p ON l.partner_id = p.id
            WHERE l.created_at > %s AND l.partner_id IS NOT NULL
            GROUP BY l.partner_id
            ORDER BY request_count DESC
            LIMIT %d",
            $cutoff,
            $limit
        ), ARRAY_A);

        foreach ($results as &$result) {
            $result['success_rate'] = $result['request_count'] > 0
                ? round(($result['success_count'] / $result['request_count']) * 100, 1)
                : 0;
        }

        return $results;
    }

    /**
     * Get hourly activity for chart
     */
    private function get_hourly_activity() {
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, '%%Y-%%m-%%d %%H:00:00') as hour,
                status,
                COUNT(*) as count
            FROM {$table}
            WHERE created_at > %s
            GROUP BY hour, status
            ORDER BY hour ASC",
            $cutoff
        ), ARRAY_A);

        // Format for Chart.js
        $hours = array();
        $success_data = array();
        $failed_data = array();
        $blocked_data = array();

        $grouped = array();
        foreach ($results as $row) {
            $grouped[$row['hour']][$row['status']] = $row['count'];
        }

        foreach ($grouped as $hour => $statuses) {
            $hours[] = date('H:i', strtotime($hour));
            $success_data[] = $statuses['success'] ?? 0;
            $failed_data[] = $statuses['failed'] ?? 0;
            $blocked_data[] = $statuses['blocked'] ?? 0;
        }

        return array(
            'labels' => $hours,
            'datasets' => array(
                array(
                    'label' => 'Success',
                    'data' => $success_data,
                    'backgroundColor' => 'rgba(45, 106, 79, 0.5)',
                    'borderColor' => '#2D6A4F',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'Failed',
                    'data' => $failed_data,
                    'backgroundColor' => 'rgba(212, 165, 116, 0.5)',
                    'borderColor' => '#D4A574',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'Blocked',
                    'data' => $blocked_data,
                    'backgroundColor' => 'rgba(220, 53, 69, 0.5)',
                    'borderColor' => '#dc3545',
                    'borderWidth' => 2
                )
            )
        );
    }

    /**
     * Get active alerts
     */
    private function get_active_alerts() {
        // Check for suspicious activity in last hour
        global $wpdb;

        $table = $wpdb->prefix . '1_happyturtle_access_log';
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $alerts = array();

        // Check for multiple failed attempts
        $failed_ips = $wpdb->get_results($wpdb->prepare(
            "SELECT ip_address, COUNT(*) as count
            FROM {$table}
            WHERE created_at > %s AND status = 'failed'
            GROUP BY ip_address
            HAVING count > 5
            ORDER BY count DESC",
            $cutoff
        ), ARRAY_A);

        foreach ($failed_ips as $ip) {
            $alerts[] = array(
                'severity' => 'high',
                'title' => 'Multiple Failed Attempts',
                'message' => sprintf('%d failed authentication attempts from IP %s in the last hour', $ip['count'], $ip['ip_address']),
                'time' => 'Last hour'
            );
        }

        // Check for rate limit violations
        $rate_table = $wpdb->prefix . '1_happyturtle_rate_limits';
        $locked = $wpdb->get_results(
            "SELECT * FROM {$rate_table} WHERE is_locked = 1 AND locked_until > NOW()",
            ARRAY_A
        );

        foreach ($locked as $lock) {
            $alerts[] = array(
                'severity' => 'medium',
                'title' => 'Rate Limit Active',
                'message' => sprintf('Identifier %s is locked due to rate limit violation until %s', $lock['identifier'], date('H:i', strtotime($lock['locked_until']))),
                'time' => 'Active now'
            );
        }

        return $alerts;
    }
}

// Initialize dashboard
new HappyTurtle_Security_Dashboard();
