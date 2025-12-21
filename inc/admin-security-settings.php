<?php
/**
 * Security Settings Page
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

class HappyTurtle_Security_Settings_Page {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('htb_security_settings', 'htb_security_options');

        // Email alerts section
        add_settings_section(
            'htb_email_alerts',
            'Email Alerts',
            array($this, 'render_email_alerts_section'),
            'htb_security_settings'
        );

        // SMS alerts section
        add_settings_section(
            'htb_sms_alerts',
            'SMS Alerts',
            array($this, 'render_sms_alerts_section'),
            'htb_security_settings'
        );

        // Retention policy section
        add_settings_section(
            'htb_retention_policy',
            'Data Retention Policy',
            array($this, 'render_retention_section'),
            'htb_security_settings'
        );

        // Rate limiting section
        add_settings_section(
            'htb_rate_limiting',
            'Rate Limiting',
            array($this, 'render_rate_limiting_section'),
            'htb_security_settings'
        );

        // General settings section
        add_settings_section(
            'htb_general_settings',
            'General Settings',
            array($this, 'render_general_section'),
            'htb_security_settings'
        );
    }

    /**
     * Render settings page
     */
    public function render() {
        $options = get_option('htb_security_options', $this->get_default_options());

        // Messages
        $message = '';
        $message_type = 'success';
        if (isset($_GET['settings-updated'])) {
            $message = 'Settings saved successfully.';
        } elseif (isset($_GET['message'])) {
            $messages = array(
                'test_email_sent' => 'Test email sent successfully! Check your inbox.',
                'test_sms_sent' => 'Test SMS sent successfully! Check your phone.',
                'twilio_verified' => 'Twilio credentials verified successfully!',
                'twilio_error' => 'Twilio verification failed. Check your credentials.',
                'sms_error' => 'Failed to send test SMS. Check your Twilio configuration.',
                'cleanup_done' => 'Old logs cleaned up successfully.',
                'error' => 'An error occurred. Please try again.'
            );
            $message = $messages[$_GET['message']] ?? '';

            // Error messages get different styling
            if (in_array($_GET['message'], array('twilio_error', 'sms_error', 'error'))) {
                $message_type = 'error';
            }
        }

        ?>
        <div class="wrap happyturtle-security-settings">
            <h1>
                <span class="dashicons dashicons-admin-settings"></span>
                Security Settings
            </h1>

            <?php if ($message): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('htb_security_settings');
                ?>

                <!-- Email Alerts -->
                <div class="htb-settings-panel">
                    <h2>
                        <span class="dashicons dashicons-email"></span>
                        Email Alerts
                    </h2>
                    <p>Configure email notifications for security events.</p>

                    <table class="form-table">
                        <tr>
                            <th><label for="email_alerts_enabled">Enable Email Alerts</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="htb_security_options[email_alerts_enabled]" id="email_alerts_enabled" value="1"
                                           <?php checked($options['email_alerts_enabled'], 1); ?>>
                                    Send email alerts for suspicious activity
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="alert_email">Alert Email Address</label></th>
                            <td>
                                <input type="email" name="htb_security_options[alert_email]" id="alert_email"
                                       class="regular-text" value="<?php echo esc_attr($options['alert_email']); ?>"
                                       placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                                <p class="description">Leave blank to use site admin email: <?php echo get_option('admin_email'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>Alert Triggers</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="htb_security_options[alert_failed_attempts]" value="1"
                                               <?php checked($options['alert_failed_attempts'], 1); ?>>
                                        Multiple failed login attempts (5+ in 1 hour)
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="htb_security_options[alert_rate_limit]" value="1"
                                               <?php checked($options['alert_rate_limit'], 1); ?>>
                                        Rate limit violations
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="htb_security_options[alert_unusual_location]" value="1"
                                               <?php checked($options['alert_unusual_location'], 1); ?>>
                                        Access from unusual location (non-US)
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="htb_security_options[alert_new_partner]" value="1"
                                               <?php checked($options['alert_new_partner'], 1); ?>>
                                        New partner registration
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="htb_security_options[alert_ip_whitelist_block]" value="1"
                                               <?php checked($options['alert_ip_whitelist_block'], 1); ?>>
                                        IP whitelist violation
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <div class="htb-action-buttons">
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('htb_test_email', 'htb_settings_nonce'); ?>
                            <input type="hidden" name="action" value="test_email">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-email-alt"></span>
                                Send Test Email
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SMS Alerts -->
                <div class="htb-settings-panel">
                    <h2>
                        <span class="dashicons dashicons-smartphone"></span>
                        SMS Alerts (Twilio)
                    </h2>
                    <p>Configure SMS text message notifications for critical security events.</p>

                    <table class="form-table">
                        <tr>
                            <th><label for="sms_alerts_enabled">Enable SMS Alerts</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="htb_security_options[sms_alerts_enabled]" id="sms_alerts_enabled" value="1"
                                           <?php checked($options['sms_alerts_enabled'], 1); ?>>
                                    Send SMS alerts for critical security events
                                </label>
                                <p class="description">SMS alerts are only sent for critical events (failed attempts, rate limits, unusual locations)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="twilio_account_sid">Twilio Account SID</label></th>
                            <td>
                                <input type="text" name="htb_security_options[twilio_account_sid]" id="twilio_account_sid"
                                       class="regular-text" value="<?php echo esc_attr($options['twilio_account_sid']); ?>"
                                       placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                <p class="description">Your Twilio Account SID from <a href="https://console.twilio.com/" target="_blank">console.twilio.com</a></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="twilio_auth_token">Twilio Auth Token</label></th>
                            <td>
                                <input type="password" name="htb_security_options[twilio_auth_token]" id="twilio_auth_token"
                                       class="regular-text" value="<?php echo esc_attr($options['twilio_auth_token']); ?>"
                                       placeholder="Your Auth Token">
                                <p class="description">Your Twilio Auth Token (keep this secret!)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="twilio_phone_number">Twilio Phone Number</label></th>
                            <td>
                                <input type="tel" name="htb_security_options[twilio_phone_number]" id="twilio_phone_number"
                                       class="regular-text" value="<?php echo esc_attr($options['twilio_phone_number']); ?>"
                                       placeholder="+1234567890">
                                <p class="description">Your Twilio phone number in E.164 format (e.g., +15551234567)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sms_phone_numbers">Recipient Phone Numbers</label></th>
                            <td>
                                <textarea name="htb_security_options[sms_phone_numbers]" id="sms_phone_numbers"
                                          class="large-text" rows="3"><?php echo esc_textarea($options['sms_phone_numbers']); ?></textarea>
                                <p class="description">Enter phone numbers to receive alerts, one per line or comma-separated. Format: +1234567890 or 1234567890</p>
                            </td>
                        </tr>
                    </table>

                    <div class="htb-action-buttons">
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('htb_verify_twilio', 'htb_settings_nonce'); ?>
                            <input type="hidden" name="action" value="verify_twilio">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-shield-alt"></span>
                                Verify Credentials
                            </button>
                        </form>

                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('htb_test_sms', 'htb_settings_nonce'); ?>
                            <input type="hidden" name="action" value="test_sms">
                            <input type="tel" name="test_phone" placeholder="+1234567890" style="width: 150px;">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-smartphone"></span>
                                Send Test SMS
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Data Retention -->
                <div class="htb-settings-panel">
                    <h2>
                        <span class="dashicons dashicons-database"></span>
                        Data Retention Policy
                    </h2>
                    <p>Configure how long security logs are stored before automatic deletion.</p>

                    <table class="form-table">
                        <tr>
                            <th><label for="retention_enabled">Auto-Cleanup Enabled</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="htb_security_options[retention_enabled]" id="retention_enabled" value="1"
                                           <?php checked($options['retention_enabled'], 1); ?>>
                                    Automatically delete old logs
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="retention_days">Retention Period</label></th>
                            <td>
                                <input type="number" name="htb_security_options[retention_days]" id="retention_days"
                                       class="small-text" value="<?php echo esc_attr($options['retention_days']); ?>" min="1" max="730">
                                days
                                <p class="description">Logs older than this will be deleted automatically. Default: 90 days.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Current Database Size</th>
                            <td>
                                <?php
                                global $wpdb;
                                $table = $wpdb->prefix . '1_happyturtle_access_log';
                                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                                $size = $wpdb->get_var("SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'");
                                ?>
                                <strong><?php echo number_format($count); ?></strong> log entries
                                (<strong><?php echo $size; ?> MB</strong>)
                            </td>
                        </tr>
                    </table>

                    <div class="htb-action-buttons">
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('htb_cleanup_now', 'htb_settings_nonce'); ?>
                            <input type="hidden" name="action" value="cleanup_now">
                            <button type="submit" class="button button-secondary"
                                    onclick="return confirm('This will delete logs older than <?php echo $options['retention_days']; ?> days. Continue?');">
                                <span class="dashicons dashicons-trash"></span>
                                Run Cleanup Now
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Rate Limiting -->
                <div class="htb-settings-panel">
                    <h2>
                        <span class="dashicons dashicons-shield"></span>
                        Rate Limiting
                    </h2>
                    <p>Control request limits to prevent abuse.</p>

                    <table class="form-table">
                        <tr>
                            <th><label for="api_rate_limit">API Rate Limit</label></th>
                            <td>
                                <input type="number" name="htb_security_options[api_rate_limit]" id="api_rate_limit"
                                       class="small-text" value="<?php echo esc_attr($options['api_rate_limit']); ?>" min="1" max="1000">
                                requests per minute
                                <p class="description">Maximum API requests per partner per minute. Default: 60.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="login_rate_limit">Login Rate Limit</label></th>
                            <td>
                                <input type="number" name="htb_security_options[login_rate_limit]" id="login_rate_limit"
                                       class="small-text" value="<?php echo esc_attr($options['login_rate_limit']); ?>" min="1" max="100">
                                failed attempts per hour
                                <p class="description">Maximum failed login attempts per IP per hour. Default: 10.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="lockout_duration">Lockout Duration</label></th>
                            <td>
                                <input type="number" name="htb_security_options[lockout_duration]" id="lockout_duration"
                                       class="small-text" value="<?php echo esc_attr($options['lockout_duration']); ?>" min="1" max="1440">
                                minutes
                                <p class="description">How long to lock out violators. Default: 30 minutes.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- General Settings -->
                <div class="htb-settings-panel">
                    <h2>
                        <span class="dashicons dashicons-admin-generic"></span>
                        General Settings
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="enable_geolocation">Enable Geolocation</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="htb_security_options[enable_geolocation]" id="enable_geolocation" value="1"
                                           <?php checked($options['enable_geolocation'], 1); ?>>
                                    Track geographic location of access attempts
                                </label>
                                <p class="description">Uses ip-api.com (free service with 45 requests/minute limit)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="log_request_data">Log Request Data</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="htb_security_options[log_request_data]" id="log_request_data" value="1"
                                           <?php checked($options['log_request_data'], 1); ?>>
                                    Store full request and response data (for debugging)
                                </label>
                                <p class="description">Warning: Increases database size. Passwords are always filtered.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whitelist_enforcement">IP Whitelist Enforcement</label></th>
                            <td>
                                <select name="htb_security_options[whitelist_enforcement]" id="whitelist_enforcement">
                                    <option value="disabled" <?php selected($options['whitelist_enforcement'], 'disabled'); ?>>Disabled (whitelist not enforced)</option>
                                    <option value="optional" <?php selected($options['whitelist_enforcement'], 'optional'); ?>>Optional (per-partner basis)</option>
                                    <option value="mandatory" <?php selected($options['whitelist_enforcement'], 'mandatory'); ?>>Mandatory (all partners must be whitelisted)</option>
                                </select>
                                <p class="description">Control IP whitelist enforcement level.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- System Status -->
            <div class="htb-settings-panel">
                <h2>
                    <span class="dashicons dashicons-info"></span>
                    System Status
                </h2>

                <table class="widefat">
                    <tr>
                        <th>Security Logger</th>
                        <td><span class="dashicons dashicons-yes" style="color: #2D6A4F;"></span> Active</td>
                    </tr>
                    <tr>
                        <th>API Middleware</th>
                        <td><span class="dashicons dashicons-yes" style="color: #2D6A4F;"></span> Active</td>
                    </tr>
                    <tr>
                        <th>Database Tables</th>
                        <td>
                            <?php
                            $tables = array(
                                $wpdb->prefix . '1_happyturtle_access_log',
                                $wpdb->prefix . '1_happyturtle_rate_limits',
                                $wpdb->prefix . '1_happyturtle_ip_whitelist'
                            );
                            $all_exist = true;
                            foreach ($tables as $table) {
                                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
                                    $all_exist = false;
                                    break;
                                }
                            }
                            if ($all_exist) {
                                echo '<span class="dashicons dashicons-yes" style="color: #2D6A4F;"></span> All tables exist';
                            } else {
                                echo '<span class="dashicons dashicons-warning" style="color: #D4A574;"></span> Some tables missing';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Cron Jobs</th>
                        <td>
                            <?php
                            $cron_active = wp_next_scheduled('htb_security_cleanup_cron');
                            if ($cron_active) {
                                echo '<span class="dashicons dashicons-yes" style="color: #2D6A4F;"></span> Next cleanup: ' . date('M j, Y H:i', $cron_active);
                            } else {
                                echo '<span class="dashicons dashicons-warning" style="color: #D4A574;"></span> Not scheduled';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Default options
     */
    private function get_default_options() {
        return array(
            'email_alerts_enabled' => 1,
            'alert_email' => '',
            'alert_failed_attempts' => 1,
            'alert_rate_limit' => 1,
            'alert_unusual_location' => 1,
            'alert_new_partner' => 1,
            'alert_ip_whitelist_block' => 1,
            'sms_alerts_enabled' => 0,
            'twilio_account_sid' => '',
            'twilio_auth_token' => '',
            'twilio_phone_number' => '',
            'sms_phone_numbers' => '',
            'retention_enabled' => 1,
            'retention_days' => 90,
            'api_rate_limit' => 60,
            'login_rate_limit' => 10,
            'lockout_duration' => 30,
            'enable_geolocation' => 1,
            'log_request_data' => 1,
            'whitelist_enforcement' => 'optional'
        );
    }

    /**
     * Section callbacks
     */
    public function render_email_alerts_section() {}
    public function render_sms_alerts_section() {}
    public function render_retention_section() {}
    public function render_rate_limiting_section() {}
    public function render_general_section() {}

    /**
     * Handle actions
     */
    public function handle_actions() {
        if (!isset($_POST['action'])) {
            return;
        }

        // Send test email
        if ($_POST['action'] === 'test_email') {
            check_admin_referer('htb_test_email', 'htb_settings_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $options = get_option('htb_security_options', $this->get_default_options());
            $to = $options['alert_email'] ?: get_option('admin_email');

            $subject = 'Happy Turtle Security - Test Alert';
            $message = "This is a test email from the Happy Turtle Security system.\n\n";
            $message .= "If you're receiving this, email alerts are configured correctly.\n\n";
            $message .= "Time: " . current_time('mysql') . "\n";
            $message .= "Site: " . get_bloginfo('name') . "\n";

            wp_mail($to, $subject, $message);

            wp_redirect(add_query_arg('message', 'test_email_sent', wp_get_referer()));
            exit;
        }

        // Cleanup now
        if ($_POST['action'] === 'cleanup_now') {
            check_admin_referer('htb_cleanup_now', 'htb_settings_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $options = get_option('htb_security_options', $this->get_default_options());
            $security_logger = HappyTurtle_Security_Logger::get_instance();
            $deleted = $security_logger->cleanup_old_logs($options['retention_days']);

            wp_redirect(add_query_arg('message', 'cleanup_done', wp_get_referer()));
            exit;
        }

        // Verify Twilio credentials
        if ($_POST['action'] === 'verify_twilio') {
            check_admin_referer('htb_verify_twilio', 'htb_settings_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            // Load SMS alerts class if not already loaded
            if (!class_exists('HappyTurtle_SMS_Alerts')) {
                require_once get_template_directory() . '/inc/class-sms-alerts.php';
            }

            $sms_alerts = new HappyTurtle_SMS_Alerts();
            $result = $sms_alerts->verify_twilio_credentials();

            $message = $result['success'] ? 'twilio_verified' : 'twilio_error';
            wp_redirect(add_query_arg('message', $message, wp_get_referer()));
            exit;
        }

        // Test SMS
        if ($_POST['action'] === 'test_sms') {
            check_admin_referer('htb_test_sms', 'htb_settings_nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $test_phone = sanitize_text_field($_POST['test_phone']);

            if (empty($test_phone)) {
                wp_redirect(add_query_arg('message', 'sms_error', wp_get_referer()));
                exit;
            }

            // Load SMS alerts class if not already loaded
            if (!class_exists('HappyTurtle_SMS_Alerts')) {
                require_once get_template_directory() . '/inc/class-sms-alerts.php';
            }

            $sms_alerts = new HappyTurtle_SMS_Alerts();
            $result = $sms_alerts->send_test_sms($test_phone);

            $message = $result['success'] ? 'test_sms_sent' : 'sms_error';
            wp_redirect(add_query_arg('message', $message, wp_get_referer()));
            exit;
        }
    }
}
