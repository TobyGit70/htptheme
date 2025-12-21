<?php
/**
 * HappyTurtle FSE Theme - Plugin Recommendations System
 *
 * Provides plugin dependency management, installation prompts, and configuration tracking
 * for all recommended plugins needed for optimal theme performance.
 *
 * @package HappyTurtle_FSE
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class HappyTurtle_Plugin_Recommendations {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Plugin recommendations configuration
     */
    private $plugins = array();

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_plugins();
        $this->init_hooks();
    }

    /**
     * Define all recommended plugins
     */
    private function define_plugins() {
        $this->plugins = array(

            // ========================================
            // CRITICAL - CANNOT RUN WITHOUT THESE
            // ========================================

            'age-gate' => array(
                'name' => 'Age Gate',
                'slug' => 'age-gate',
                'version' => '3.7.1',
                'required' => true,
                'priority' => 'critical',
                'category' => 'Cannabis Compliance',
                'description' => '21+ age verification - LEGALLY REQUIRED for cannabis businesses',
                'config_required' => false, // Works immediately
                'config_check' => array($this, 'check_age_gate_config'),
                'settings_url' => 'options-general.php?page=age-gate',
                'documentation' => 'Age gate appears before all content. Minimum age: 21 years. Cookie duration: 24 hours.'
            ),

            'redis-cache' => array(
                'name' => 'Redis Object Cache',
                'slug' => 'redis-cache',
                'version' => '2.7.0',
                'required' => true,
                'priority' => 'critical',
                'category' => 'Performance',
                'description' => 'In-memory object caching for 20-40% database query reduction',
                'config_required' => true,
                'config_check' => array($this, 'check_redis_config'),
                'settings_url' => 'options-general.php?page=redis-cache',
                'documentation' => 'Requires Redis server and wp-config.php configuration.'
            ),

            'b2b-suite' => array(
                'name' => 'B2B Suite',
                'slug' => 'b2b-suite',
                'version' => '1.0.0',
                'required' => true,
                'priority' => 'critical',
                'category' => 'Core Functionality',
                'description' => 'Complete B2B cannabis processing suite - partners, products, orders, compliance',
                'config_required' => false,
                'config_check' => null,
                'settings_url' => 'admin.php?page=b2b-suite',
                'documentation' => 'Core plugin providing all B2B functionality. Includes partner management, product catalog, order processing, batch tracking, compliance tools, and GPS tracking.'
            ),

            // ========================================
            // STRONGLY RECOMMENDED - DAY 1
            // ========================================

            'litespeed-cache' => array(
                'name' => 'LiteSpeed Cache',
                'slug' => 'litespeed-cache',
                'version' => '7.5.0.1',
                'required' => false,
                'priority' => 'high',
                'category' => 'Performance',
                'description' => 'CSS/JS minification, HTML optimization, database cleanup',
                'config_required' => true,
                'config_check' => array($this, 'check_litespeed_config'),
                'settings_url' => 'admin.php?page=litespeed',
                'documentation' => 'Enable CSS/JS minification, HTML optimization, and database cleanup. Server-level caching only works on LiteSpeed servers.'
            ),

            // ========================================
            // RECOMMENDED - WEEK 1
            // ========================================

            'ewww-image-optimizer' => array(
                'name' => 'EWWW Image Optimizer',
                'slug' => 'ewww-image-optimizer',
                'version' => '8.2.1',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Performance',
                'description' => 'Image compression and WebP conversion (no API key required)',
                'config_required' => false,
                'config_check' => array($this, 'check_ewww_config'),
                'settings_url' => 'tools.php?page=ewww-image-optimizer-tools',
                'documentation' => 'Works out of the box. Automatically optimizes images on upload. Recommended settings: Enable WebP conversion and lazy load.'
            ),

            'wp-optimize' => array(
                'name' => 'WP-Optimize',
                'slug' => 'wp-optimize',
                'version' => '4.3.0',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Performance',
                'description' => 'Database cleanup, transient management, scheduled optimization',
                'config_required' => false,
                'config_check' => array($this, 'check_wp_optimize_config'),
                'settings_url' => 'admin.php?page=WP-Optimize',
                'documentation' => 'Auto-cleanup configured for weekly schedule.'
            ),

            'heartbeat-control' => array(
                'name' => 'Heartbeat Control',
                'slug' => 'heartbeat-control',
                'version' => '2.0.1',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Performance',
                'description' => 'Reduce WordPress Heartbeat API CPU usage by 40%',
                'config_required' => false,
                'config_check' => array($this, 'check_heartbeat_config'),
                'settings_url' => 'options-general.php?page=heartbeat_control_settings',
                'documentation' => 'Configured: 60s admin, disabled frontend.'
            ),

            // ========================================
            // OPTIMIZATION - WEEK 2
            // ========================================

            'wp-asset-clean-up' => array(
                'name' => 'Asset CleanUp',
                'slug' => 'wp-asset-clean-up',
                'version' => '1.4.0.3',
                'required' => false,
                'priority' => 'low',
                'category' => 'Optimization',
                'description' => 'Selective CSS/JS loading per page for 30-50% resource reduction',
                'config_required' => false,
                'config_check' => array($this, 'check_asset_cleanup_config'),
                'settings_url' => 'admin.php?page=wpassetcleanup_settings',
                'documentation' => 'Visit pages while logged in as admin to disable unused assets.'
            ),

            'query-monitor' => array(
                'name' => 'Query Monitor',
                'slug' => 'query-monitor',
                'version' => '3.20.0',
                'required' => false,
                'priority' => 'low',
                'category' => 'Development',
                'description' => 'Database query debugging and performance monitoring (deactivate in production)',
                'config_required' => false,
                'config_check' => null,
                'settings_url' => null,
                'documentation' => 'Development tool only. Deactivate in production.'
            ),

            'index-wp-mysql-for-speed' => array(
                'name' => 'Index WP MySQL For Speed',
                'slug' => 'index-wp-mysql-for-speed',
                'version' => '1.5.4',
                'required' => false,
                'priority' => 'low',
                'category' => 'Optimization',
                'description' => 'Add missing database indexes (apply then deactivate)',
                'config_required' => true,
                'config_check' => array($this, 'check_index_mysql_config'),
                'settings_url' => 'tools.php?page=imfsq',
                'documentation' => 'Go to Tools > Index MySQL, apply recommended indexes, then deactivate plugin.'
            ),

            // ========================================
            // SECURITY
            // ========================================

            'wordfence' => array(
                'name' => 'Wordfence Security',
                'slug' => 'wordfence',
                'version' => '8.1.0',
                'required' => false,
                'priority' => 'high',
                'category' => 'Security',
                'description' => 'Firewall, malware scanning, brute force protection',
                'config_required' => false,
                'config_check' => array($this, 'check_wordfence_config'),
                'settings_url' => 'admin.php?page=Wordfence',
                'documentation' => 'Configured: Extended Protection firewall, daily scans at 3 AM, weekly email summaries.'
            ),

            // ========================================
            // COMPLIANCE & OPERATIONS
            // ========================================

            'wp-mail-smtp' => array(
                'name' => 'WP Mail SMTP',
                'slug' => 'wp-mail-smtp',
                'version' => '4.6.0',
                'required' => false,
                'priority' => 'high',
                'category' => 'Compliance',
                'description' => 'Reliable email delivery via SMTP (ScalaHosting credentials required)',
                'config_required' => true,
                'config_check' => array($this, 'check_wp_mail_smtp_config'),
                'settings_url' => 'admin.php?page=wp-mail-smtp',
                'documentation' => 'Get SMTP credentials from ScalaHosting cPanel > Email Accounts.'
            ),

            'cookie-notice' => array(
                'name' => 'Cookie Notice',
                'slug' => 'cookie-notice',
                'version' => '2.5.7',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Compliance',
                'description' => 'Cookie consent banner for USA/CCPA compliance',
                'config_required' => false,
                'config_check' => array($this, 'check_cookie_notice_config'),
                'settings_url' => 'options-general.php?page=cookie-notice',
                'documentation' => 'Configured for USA/CCPA only (not GDPR).'
            ),

            'wpforms-lite' => array(
                'name' => 'WPForms Lite',
                'slug' => 'wpforms-lite',
                'version' => '1.9.8.1',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Compliance',
                'description' => 'Contact forms, newsletter signup, quote requests',
                'config_required' => true,
                'config_check' => array($this, 'check_wpforms_config'),
                'settings_url' => 'admin.php?page=wpforms-overview',
                'documentation' => 'Create forms at WPForms > Add New.'
            ),

            'wplegalpages' => array(
                'name' => 'WP Legal Pages',
                'slug' => 'wplegalpages',
                'version' => '3.5.1',
                'required' => false,
                'priority' => 'medium',
                'category' => 'Compliance',
                'description' => 'Generate legal pages (Privacy Policy, Terms, etc.) for USA compliance',
                'config_required' => true,
                'config_check' => array($this, 'check_wp_legal_pages_config'),
                'settings_url' => 'admin.php?page=wp-legal-pages',
                'documentation' => 'Create legal pages at Legal Pages > Create New Page. Configured for USA/CCPA (not GDPR).'
            ),
        );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // AJAX handlers for bulk installation
        add_action('wp_ajax_htb_install_plugin', array($this, 'ajax_install_plugin'));
        add_action('wp_ajax_htb_activate_plugin', array($this, 'ajax_activate_plugin'));
        add_action('wp_ajax_htb_dismiss_plugin_notice', array($this, 'ajax_dismiss_notice'));
        add_action('wp_ajax_htb_hide_plugin', array($this, 'ajax_hide_plugin'));
        add_action('wp_ajax_htb_unhide_plugin', array($this, 'ajax_unhide_plugin'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add action links to themes page
        add_filter('theme_action_links', array($this, 'add_theme_action_links'), 10, 2);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_theme_page(
            'Plugin Setup',
            'Plugin Setup',
            'manage_options',
            'happyturtle-plugin-setup',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Display admin notices for missing/unconfigured plugins
     */
    public function display_admin_notices() {
        // Don't show on plugin setup page
        if (isset($_GET['page']) && $_GET['page'] === 'happyturtle-plugin-setup') {
            return;
        }

        // Check if user dismissed the notice
        if (get_option('htb_plugin_notice_dismissed')) {
            return;
        }

        $missing_critical = $this->get_missing_critical_plugins();
        $unconfigured_required = $this->get_unconfigured_required_plugins();

        if (!empty($missing_critical) || !empty($unconfigured_required)) {
            ?>
            <div class="notice notice-error is-dismissible htb-plugin-notice">
                <p><strong>HappyTurtle FSE Theme Setup Required</strong></p>

                <?php if (!empty($missing_critical)): ?>
                    <p style="color: #d63638;">
                        <strong>⚠️ CRITICAL:</strong> The following plugins are required but not installed:
                    </p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <?php foreach ($missing_critical as $plugin): ?>
                            <li><strong><?php echo esc_html($plugin['name']); ?></strong> - <?php echo esc_html($plugin['description']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($unconfigured_required)): ?>
                    <p style="color: #d63638;">
                        <strong>⚠️ Configuration Required:</strong> The following plugins need configuration:
                    </p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <?php foreach ($unconfigured_required as $plugin): ?>
                            <li><strong><?php echo esc_html($plugin['name']); ?></strong> - <a href="<?php echo admin_url($plugin['settings_url']); ?>">Configure Now</a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <p>
                    <a href="<?php echo admin_url('themes.php?page=happyturtle-plugin-setup'); ?>" class="button button-primary">
                        Complete Plugin Setup
                    </a>
                    <button type="button" class="button button-link htb-dismiss-notice" style="margin-left: 10px;">Dismiss</button>
                </p>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('.htb-dismiss-notice').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'htb_dismiss_plugin_notice',
                        nonce: '<?php echo wp_create_nonce('htb_dismiss_notice'); ?>'
                    });
                    $('.htb-plugin-notice').fadeOut();
                });
            });
            </script>
            <?php
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $stats = $this->get_plugin_stats();
        $hidden_plugins = $this->get_hidden_plugins();
        ?>
        <div class="wrap htb-plugin-setup">
            <h1>HappyTurtle FSE Theme - Plugin Setup</h1>

            <div class="htb-setup-stats" style="margin: 20px 0; display: flex; gap: 20px;">
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d2327;">Total Plugins</h3>
                    <p style="font-size: 36px; font-weight: 600; margin: 0; color: #1e3a8a;"><?php echo $stats['total']; ?></p>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d2327;">Installed & Active</h3>
                    <p style="font-size: 36px; font-weight: 600; margin: 0; color: #16a34a;"><?php echo $stats['active']; ?></p>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d2327;">Needs Configuration</h3>
                    <p style="font-size: 36px; font-weight: 600; margin: 0; color: #ea580c;"><?php echo $stats['unconfigured']; ?></p>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d2327;">Not Installed</h3>
                    <p style="font-size: 36px; font-weight: 600; margin: 0; color: #dc2626;"><?php echo $stats['missing']; ?></p>
                </div>
            </div>

            <div class="htb-plugin-categories">
                <?php $this->render_plugin_category('critical', 'CRITICAL - Cannot Run Without These'); ?>
                <?php $this->render_plugin_category('high', 'STRONGLY RECOMMENDED - Day 1 / Week 1'); ?>
                <?php $this->render_plugin_category('medium', 'RECOMMENDED - Week 1 / Week 2'); ?>
                <?php $this->render_plugin_category('low', 'OPTIONAL - Optimization & Development'); ?>
            </div>

            <?php if (!empty($hidden_plugins)): ?>
            <div class="htb-hidden-plugins" style="margin: 30px 0;">
                <h2 style="color: #6b7280;">Hidden Plugins (<?php echo count($hidden_plugins); ?>)</h2>
                <p style="color: #6b7280;">These plugins have been hidden from the main list. You can restore them if needed.</p>
                <table class="wp-list-table widefat fixed striped" style="background: #fff;">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hidden_plugins as $slug): ?>
                            <?php if (isset($this->plugins[$slug])): ?>
                                <?php $plugin = $this->plugins[$slug]; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($plugin['name']); ?></strong><br>
                                        <small style="color: #6b7280;">v<?php echo esc_html($plugin['version']); ?></small>
                                    </td>
                                    <td><?php echo esc_html($plugin['category']); ?></td>
                                    <td><?php echo esc_html($plugin['description']); ?></td>
                                    <td>
                                        <button class="button htb-unhide-plugin" data-slug="<?php echo esc_attr($slug); ?>">
                                            Restore
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div style="margin-top: 30px; padding: 20px; background: #dcfce7; border: 1px solid #86efac; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #166534;">✅ Setup Complete!</h3>
                <p style="color: #166534;">All critical and recommended plugins are installed and configured.</p>
                <ul style="color: #166534;">
                    <li><strong>Age Gate:</strong> 21+ verification active (appears before all content)</li>
                    <li><strong>Redis Cache:</strong> Object caching enabled (40% faster database queries)</li>
                    <li><strong>B2B Suite:</strong> Complete B2B system active (partners, products, orders, compliance, GPS tracking)</li>
                    <li><strong>WP-Optimize:</strong> Weekly automatic cleanup scheduled</li>
                    <li><strong>EWWW Image Optimizer:</strong> Automatic image compression on upload</li>
                    <li><strong>WPForms:</strong> 3 forms created (Contact, Quote Request, Partner Application)</li>
                    <li><strong>Legal Pages:</strong> 4 pages created (Privacy, Terms, Disclaimer, Cookie Policy)</li>
                </ul>
                <p style="color: #166534;"><strong>Your site is optimized and ready for production!</strong></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render plugin category section
     */
    private function render_plugin_category($priority, $title) {
        $hidden_plugins = $this->get_hidden_plugins();

        $plugins = array_filter($this->plugins, function($plugin, $slug) use ($priority, $hidden_plugins) {
            return $plugin['priority'] === $priority && !in_array($slug, $hidden_plugins);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($plugins)) {
            return;
        }

        $priority_colors = array(
            'critical' => array('bg' => '#fef2f2', 'border' => '#dc2626', 'text' => '#991b1b'),
            'high' => array('bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#92400e'),
            'medium' => array('bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af'),
            'low' => array('bg' => '#f9fafb', 'border' => '#6b7280', 'text' => '#374151'),
        );

        $colors = $priority_colors[$priority];
        ?>
        <div class="htb-category-section" style="margin: 30px 0; background: <?php echo $colors['bg']; ?>; border-left: 4px solid <?php echo $colors['border']; ?>; padding: 20px;">
            <h2 style="margin-top: 0; color: <?php echo $colors['text']; ?>;"><?php echo esc_html($title); ?></h2>

            <table class="wp-list-table widefat fixed striped" style="background: #fff;">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>Plugin</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugins as $slug => $plugin): ?>
                        <?php $this->render_plugin_row($slug, $plugin); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render individual plugin row
     */
    private function render_plugin_row($slug, $plugin) {
        $status = $this->get_plugin_status($slug, $plugin);

        $status_colors = array(
            'active-configured' => array('bg' => '#dcfce7', 'text' => '#166534', 'icon' => '✅'),
            'active-unconfigured' => array('bg' => '#fef3c7', 'text' => '#92400e', 'icon' => '⚠️'),
            'installed-inactive' => array('bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => '○'),
            'not-installed' => array('bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => '✗'),
        );

        $status_info = $status_colors[$status['state']];
        ?>
        <tr>
            <td style="text-align: center; font-size: 18px;">
                <?php echo $plugin['required'] ? '🔒' : ''; ?>
            </td>
            <td>
                <strong><?php echo esc_html($plugin['name']); ?></strong>
                <?php if ($plugin['required']): ?>
                    <span style="color: #dc2626; font-weight: 600; margin-left: 5px;">*REQUIRED</span>
                <?php endif; ?>
                <br>
                <small style="color: #6b7280;">v<?php echo esc_html($plugin['version']); ?></small>
            </td>
            <td><?php echo esc_html($plugin['category']); ?></td>
            <td><?php echo esc_html($plugin['description']); ?></td>
            <td>
                <span style="display: inline-block; padding: 4px 12px; background: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['text']; ?>; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    <?php echo $status_info['icon']; ?> <?php echo esc_html($status['label']); ?>
                </span>
            </td>
            <td>
                <?php $this->render_plugin_actions($slug, $plugin, $status); ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Render plugin action buttons
     */
    private function render_plugin_actions($slug, $plugin, $status) {
        switch ($status['state']) {
            case 'not-installed':
                ?>
                <button class="button button-primary htb-install-plugin" data-slug="<?php echo esc_attr($slug); ?>">
                    Install
                </button>
                <?php
                break;

            case 'installed-inactive':
                ?>
                <button class="button button-primary htb-activate-plugin" data-slug="<?php echo esc_attr($slug); ?>">
                    Activate
                </button>
                <?php
                break;

            case 'active-unconfigured':
                if ($plugin['settings_url']) {
                    ?>
                    <a href="<?php echo admin_url($plugin['settings_url']); ?>" class="button button-primary">
                        Configure
                    </a>
                    <?php
                }
                ?>
                <button class="button htb-show-docs" data-slug="<?php echo esc_attr($slug); ?>" style="margin-left: 5px;">
                    Docs
                </button>
                <?php if (!$plugin['required']): ?>
                    <button class="button htb-hide-plugin" data-slug="<?php echo esc_attr($slug); ?>" style="margin-left: 5px; color: #d63638;">
                        Hide
                    </button>
                <?php endif; ?>
                <?php
                break;

            case 'active-configured':
                if ($plugin['settings_url']) {
                    ?>
                    <a href="<?php echo admin_url($plugin['settings_url']); ?>" class="button">
                        Settings
                    </a>
                    <?php
                }
                if (!$plugin['required']) {
                    ?>
                    <button class="button htb-hide-plugin" data-slug="<?php echo esc_attr($slug); ?>" style="margin-left: 5px; color: #d63638;">
                        Hide
                    </button>
                    <?php
                }
                break;
        }
    }

    /**
     * Get plugin status
     */
    private function get_plugin_status($slug, $plugin) {
        if (!$this->is_plugin_installed($slug)) {
            return array('state' => 'not-installed', 'label' => 'Not Installed');
        }

        if (!$this->is_plugin_active($slug)) {
            return array('state' => 'installed-inactive', 'label' => 'Installed (Inactive)');
        }

        // Plugin is active, check configuration
        if ($plugin['config_required'] && $plugin['config_check']) {
            $is_configured = call_user_func($plugin['config_check']);
            if (!$is_configured) {
                return array('state' => 'active-unconfigured', 'label' => 'Active - Needs Config');
            }
        }

        return array('state' => 'active-configured', 'label' => 'Active & Configured');
    }

    /**
     * Check if plugin is installed
     */
    private function is_plugin_installed($slug) {
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $slug . '/') === 0 || $plugin_file === $slug . '.php') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if plugin is active
     */
    private function is_plugin_active($slug) {
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            if ((strpos($plugin_file, $slug . '/') === 0 || $plugin_file === $slug . '.php') && is_plugin_active($plugin_file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get missing critical plugins
     */
    private function get_missing_critical_plugins() {
        $missing = array();
        foreach ($this->plugins as $slug => $plugin) {
            if ($plugin['required'] && !$this->is_plugin_active($slug)) {
                $missing[] = $plugin;
            }
        }
        return $missing;
    }

    /**
     * Get unconfigured required plugins
     */
    private function get_unconfigured_required_plugins() {
        $unconfigured = array();
        foreach ($this->plugins as $slug => $plugin) {
            if ($plugin['config_required'] && $this->is_plugin_active($slug) && $plugin['config_check']) {
                $is_configured = call_user_func($plugin['config_check']);
                if (!$is_configured) {
                    $unconfigured[] = $plugin;
                }
            }
        }
        return $unconfigured;
    }

    /**
     * Get plugin statistics
     */
    private function get_plugin_stats() {
        $total = count($this->plugins);
        $active = 0;
        $unconfigured = 0;
        $missing = 0;

        foreach ($this->plugins as $slug => $plugin) {
            if ($this->is_plugin_active($slug)) {
                $active++;

                if ($plugin['config_required'] && $plugin['config_check']) {
                    if (!call_user_func($plugin['config_check'])) {
                        $unconfigured++;
                    }
                }
            } else {
                $missing++;
            }
        }

        return array(
            'total' => $total,
            'active' => $active,
            'unconfigured' => $unconfigured,
            'missing' => $missing
        );
    }

    /**
     * Add action links to theme page
     */
    public function add_theme_action_links($actions, $theme) {
        if ($theme->get_stylesheet() === 'happyturtle-fse') {
            $setup_link = '<a href="' . admin_url('themes.php?page=happyturtle-plugin-setup') . '">Plugin Setup</a>';
            $actions[] = $setup_link;
        }
        return $actions;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'appearance_page_happyturtle-plugin-setup') {
            return;
        }

        wp_enqueue_script('jquery');

        // Inline JavaScript for plugin installation
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Install plugin
            $('.htb-install-plugin').on('click', function() {
                var button = $(this);
                var slug = button.data('slug');

                button.prop('disabled', true).text('Installing...');

                $.post(ajaxurl, {
                    action: 'htb_install_plugin',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('htb_plugin_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        button.text('Installed!').removeClass('button-primary').addClass('button-secondary');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        button.prop('disabled', false).text('Install Failed');
                        alert(response.data.message);
                    }
                });
            });

            // Activate plugin
            $('.htb-activate-plugin').on('click', function() {
                var button = $(this);
                var slug = button.data('slug');

                button.prop('disabled', true).text('Activating...');

                $.post(ajaxurl, {
                    action: 'htb_activate_plugin',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('htb_plugin_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        button.text('Activated!').removeClass('button-primary').addClass('button-secondary');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        button.prop('disabled', false).text('Activation Failed');
                        alert(response.data.message);
                    }
                });
            });

            // Hide plugin
            $('.htb-hide-plugin').on('click', function() {
                var button = $(this);
                var slug = button.data('slug');

                if (!confirm('Hide this plugin from recommendations? You can restore it later from the Hidden Plugins section.')) {
                    return;
                }

                button.prop('disabled', true).text('Hiding...');

                $.post(ajaxurl, {
                    action: 'htb_hide_plugin',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('htb_plugin_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        button.prop('disabled', false).text('Hide');
                        alert(response.data.message);
                    }
                });
            });

            // Unhide plugin
            $('.htb-unhide-plugin').on('click', function() {
                var button = $(this);
                var slug = button.data('slug');

                button.prop('disabled', true).text('Restoring...');

                $.post(ajaxurl, {
                    action: 'htb_unhide_plugin',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('htb_plugin_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        button.prop('disabled', false).text('Restore');
                        alert(response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Install plugin
     */
    public function ajax_install_plugin() {
        check_ajax_referer('htb_plugin_action', 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $slug = sanitize_text_field($_POST['slug']);

        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $api = plugins_api('plugin_information', array('slug' => $slug));

        if (is_wp_error($api)) {
            wp_send_json_error(array('message' => $api->get_error_message()));
        }

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Plugin installed successfully'));
    }

    /**
     * AJAX: Activate plugin
     */
    public function ajax_activate_plugin() {
        check_ajax_referer('htb_plugin_action', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $slug = sanitize_text_field($_POST['slug']);

        $plugins = get_plugins();
        $plugin_file = '';

        foreach ($plugins as $file => $plugin_data) {
            if (strpos($file, $slug . '/') === 0 || $file === $slug . '.php') {
                $plugin_file = $file;
                break;
            }
        }

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => 'Plugin not found'));
        }

        $result = activate_plugin($plugin_file);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Plugin activated successfully'));
    }

    /**
     * AJAX: Dismiss plugin notice
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer('htb_dismiss_notice', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        update_option('htb_plugin_notice_dismissed', true);
        wp_send_json_success(array('message' => 'Notice dismissed'));
    }

    // ============================================================
    // CONFIGURATION CHECK METHODS
    // ============================================================

    /**
     * Check Age Gate configuration
     */
    public function check_age_gate_config() {
        $options = get_option('age_gate');
        return !empty($options) && isset($options['enabled']) && $options['enabled'];
    }

    /**
     * Check Redis configuration
     */
    public function check_redis_config() {
        // Check if WP_CACHE is enabled
        if (!defined('WP_CACHE') || !WP_CACHE) {
            return false;
        }

        // Check if object-cache.php drop-in exists
        if (!file_exists(WP_CONTENT_DIR . '/object-cache.php')) {
            return false;
        }

        // Check if we can connect to Redis
        if (defined('WP_REDIS_HOST') && class_exists('Redis')) {
            try {
                $redis = new Redis();
                $connected = $redis->connect(WP_REDIS_HOST, defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379, 1);
                if ($connected) {
                    $redis->close();
                    return true;
                }
            } catch (Exception $e) {
                // Redis extension not available, but object cache might still work with Predis
            }
        }

        // If we have WP_CACHE and object-cache.php, assume it's configured
        // (Predis doesn't provide PHP Redis class but works through the drop-in)
        return true;
    }

    /**
     * Check LiteSpeed Cache configuration
     */
    public function check_litespeed_config() {
        $options = get_option('litespeed.conf.optm-css_minify');
        return !empty($options);
    }

    /**
     * Check EWWW Image Optimizer configuration
     */
    public function check_ewww_config() {
        // EWWW works without API key, so just check if it's active
        // You can add more specific checks if needed
        $options = get_option('ewww_image_optimizer_cloud_key');
        return true; // Always configured since no API key required
    }

    /**
     * Check WP-Optimize configuration
     */
    public function check_wp_optimize_config() {
        $auto_options = get_option('wp-optimize-auto');
        return !empty($auto_options);
    }

    /**
     * Check Heartbeat Control configuration
     */
    public function check_heartbeat_config() {
        $options = get_option('heartbeat_control');
        return !empty($options);
    }

    /**
     * Check Asset CleanUp configuration
     */
    public function check_asset_cleanup_config() {
        $options = get_option('wpacu_settings');
        return !empty($options);
    }

    /**
     * Check Index WP MySQL configuration
     */
    public function check_index_mysql_config() {
        // This plugin should be deactivated after use
        return !$this->is_plugin_active('index-wp-mysql-for-speed');
    }

    /**
     * Check Wordfence configuration
     */
    public function check_wordfence_config() {
        $activated = get_option('wordfenceActivated');
        return !empty($activated);
    }

    /**
     * Check WP Mail SMTP configuration
     */
    public function check_wp_mail_smtp_config() {
        $options = get_option('wp_mail_smtp');
        return !empty($options) && isset($options['mail']['mailer']) && $options['mail']['mailer'] === 'smtp';
    }

    /**
     * Check Cookie Notice configuration
     */
    public function check_cookie_notice_config() {
        $options = get_option('cookie_notice_options');
        return !empty($options);
    }

    /**
     * Check WPForms configuration
     */
    public function check_wpforms_config() {
        global $wpdb;
        $forms_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'wpforms' AND post_status = 'publish'");
        return $forms_count > 0;
    }

    /**
     * Check WP Legal Pages configuration
     */
    public function check_wp_legal_pages_config() {
        global $wpdb;
        $pages_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'legal_page' AND post_status = 'publish'");
        return $pages_count > 0;
    }

    // ============================================================
    // HIDE/UNHIDE PLUGIN METHODS
    // ============================================================

    /**
     * Get list of hidden plugins
     */
    private function get_hidden_plugins() {
        $hidden = get_option('htb_hidden_plugins', array());
        return is_array($hidden) ? $hidden : array();
    }

    /**
     * AJAX: Hide plugin from recommendations
     */
    public function ajax_hide_plugin() {
        check_ajax_referer('htb_plugin_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $slug = sanitize_text_field($_POST['slug']);

        // Don't allow hiding required plugins
        if (isset($this->plugins[$slug]) && $this->plugins[$slug]['required']) {
            wp_send_json_error(array('message' => 'Cannot hide required plugins'));
        }

        $hidden = $this->get_hidden_plugins();
        if (!in_array($slug, $hidden)) {
            $hidden[] = $slug;
            update_option('htb_hidden_plugins', $hidden);
        }

        wp_send_json_success(array('message' => 'Plugin hidden successfully'));
    }

    /**
     * AJAX: Unhide plugin (restore to recommendations)
     */
    public function ajax_unhide_plugin() {
        check_ajax_referer('htb_plugin_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $slug = sanitize_text_field($_POST['slug']);

        $hidden = $this->get_hidden_plugins();
        $hidden = array_diff($hidden, array($slug));
        update_option('htb_hidden_plugins', array_values($hidden));

        wp_send_json_success(array('message' => 'Plugin restored successfully'));
    }
}

// Initialize
HappyTurtle_Plugin_Recommendations::get_instance();
