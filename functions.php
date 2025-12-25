<?php
/**
 * Happy Turtle FSE Theme Functions
 * Cleaned up version - B2B functionality moved to B2B Suite plugin
 */

// ============================================================================
// HIDE OBJECT CACHE PRO UPSELL NOTICE
// ============================================================================
add_action('admin_init', function() {
    if (get_user_meta(get_current_user_id(), 'roc_dismissed_pro_release_notice', true) != 1) {
        update_user_meta(get_current_user_id(), 'roc_dismissed_pro_release_notice', 1);
    }
});

// ============================================================================
// GITHUB THEME UPDATER
// ============================================================================

class HTP_Theme_Updater {
    private $github_repo = 'TobyGit70/htptheme';
    private $theme_slug = 'happyturtle-fse';
    private $github_api_url;
    private $transient_key = 'htp_theme_update_check';

    public function __construct() {
        $this->github_api_url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';

        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));
        add_filter('themes_api', array($this, 'theme_info'), 20, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_folder_name'), 10, 4);
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();
        $current_version = wp_get_theme($this->theme_slug)->get('Version');

        if ($remote_version && version_compare($remote_version, $current_version, '>')) {
            $transient->response[$this->theme_slug] = array(
                'theme' => $this->theme_slug,
                'new_version' => $remote_version,
                'url' => 'https://github.com/' . $this->github_repo,
                'package' => $this->get_download_url(),
            );
        }

        return $transient;
    }

    private function get_remote_version() {
        $cached = get_transient($this->transient_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_get($this->github_api_url, array(
            'headers' => array('Accept' => 'application/vnd.github.v3+json'),
            'timeout' => 10,
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        $version = isset($release['tag_name']) ? ltrim($release['tag_name'], 'v') : false;

        if ($version) {
            set_transient($this->transient_key, $version, 6 * HOUR_IN_SECONDS);
        }

        return $version;
    }

    private function get_download_url() {
        $response = wp_remote_get($this->github_api_url, array(
            'headers' => array('Accept' => 'application/vnd.github.v3+json'),
            'timeout' => 10,
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        return isset($release['zipball_url']) ? $release['zipball_url'] : false;
    }

    public function theme_info($result, $action, $args) {
        if ($action !== 'theme_information' || !isset($args->slug) || $args->slug !== $this->theme_slug) {
            return $result;
        }

        $remote_version = $this->get_remote_version();
        $theme = wp_get_theme($this->theme_slug);

        return (object) array(
            'name' => $theme->get('Name'),
            'slug' => $this->theme_slug,
            'version' => $remote_version,
            'author' => $theme->get('Author'),
            'homepage' => 'https://github.com/' . $this->github_repo,
            'sections' => array(
                'description' => $theme->get('Description'),
                'changelog' => 'See GitHub releases for changelog.',
            ),
            'download_link' => $this->get_download_url(),
        );
    }

    public function fix_folder_name($source, $remote_source, $upgrader, $hook_extra) {
        if (!isset($hook_extra['theme']) || $hook_extra['theme'] !== $this->theme_slug) {
            return $source;
        }

        $correct_folder = trailingslashit($remote_source) . $this->theme_slug;

        if ($source !== $correct_folder) {
            rename($source, $correct_folder);
            return $correct_folder;
        }

        return $source;
    }
}

new HTP_Theme_Updater();

// ============================================================================
// THEME SETUP
// ============================================================================

// Add theme support
function happyturtle_fse_setup() {
    // Add support for block styles
    add_theme_support('wp-block-styles');

    // Add support for full and wide align images
    add_theme_support('align-wide');

    // Add support for editor styles
    add_theme_support('editor-styles');

    // Add support for responsive embedded content
    add_theme_support('responsive-embeds');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height' => 100,
        'width' => 100,
        'flex-height' => true,
        'flex-width' => true,
    ));

    // Add support for post thumbnails
    add_theme_support('post-thumbnails');

    // Add support for navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'happyturtle-fse'),
        'footer' => __('Footer Menu', 'happyturtle-fse'),
    ));
}
add_action('after_setup_theme', 'happyturtle_fse_setup');

// Enqueue styles and scripts
function happyturtle_fse_styles() {
    // Google Fonts - Inter (body) and Montserrat (headings)
    wp_enqueue_style(
        'happyturtle-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    // Main theme style
    wp_enqueue_style(
        'happyturtle-fse-style',
        get_template_directory_uri() . '/assets/style.css',
        array(),
        '3.2.0'
    );

    // Enhanced design system (Three Principles)
    wp_enqueue_style(
        'htp-enhanced-design',
        get_template_directory_uri() . '/assets/css/htp-enhanced-design.css',
        array('happyturtle-fse-style'),
        '1.0.0'
    );

    // Pattern styles (hero, cards, etc.)
    wp_enqueue_style(
        'htp-patterns',
        get_template_directory_uri() . '/assets/patterns.css',
        array('happyturtle-fse-style'),
        '1.1.0'
    );

    // Splash screen script
    wp_enqueue_script(
        'happyturtle-splash',
        get_template_directory_uri() . '/assets/splash.js',
        array(),
        '4.1.0',
        true
    );

    // Scroll animations script
    wp_enqueue_script(
        'happyturtle-scroll-animations',
        get_template_directory_uri() . '/assets/scroll-animations.js',
        array(),
        '1.0.0',
        true
    );

    // Pass theme URL to JavaScript
    wp_localize_script('happyturtle-splash', 'htbData', array(
        'themeUrl' => get_template_directory_uri()
    ));
}
add_action('wp_enqueue_scripts', 'happyturtle_fse_styles');

// Register pattern categories
function happyturtle_fse_pattern_categories() {
    register_block_pattern_category(
        'happyturtle',
        array(
            'label' => __('Happy Turtle', 'happyturtle-fse'),
            'description' => __('Happy Turtle custom patterns', 'happyturtle-fse'),
        )
    );

    register_block_pattern_category(
        'happyturtle-sections',
        array(
            'label' => __('Happy Turtle Sections', 'happyturtle-fse'),
            'description' => __('Complete section layouts', 'happyturtle-fse'),
        )
    );
}
add_action('init', 'happyturtle_fse_pattern_categories');

// Register block styles
function happyturtle_fse_block_styles() {
    // Register HTP Card style for Group blocks
    register_block_style(
        'core/group',
        array(
            'name'         => 'htp-card',
            'label'        => __('HTP Card', 'happyturtle-fse'),
            'inline_style' => '',
        )
    );

    // Register HTP Card Elevated style for Group blocks
    register_block_style(
        'core/group',
        array(
            'name'         => 'htp-card-elevated',
            'label'        => __('HTP Card (Elevated)', 'happyturtle-fse'),
            'inline_style' => '',
        )
    );
}
add_action('init', 'happyturtle_fse_block_styles');

// Dynamic copyright year shortcode
function happyturtle_copyright_year() {
    $current_year = date('Y');
    if ($current_year > 2023) {
        return '2023-' . $current_year;
    }
    return '2023';
}
add_shortcode('copyright_year', 'happyturtle_copyright_year');

// Allow 3D model file uploads
function happyturtle_allow_3d_uploads($mime_types) {
    $mime_types['glb'] = 'model/gltf-binary';
    $mime_types['gltf'] = 'model/gltf+json';
    $mime_types['usdz'] = 'model/vnd.usdz+zip';
    $mime_types['fbx'] = 'application/octet-stream';
    return $mime_types;
}
add_filter('upload_mimes', 'happyturtle_allow_3d_uploads');

// Increase upload size limit
@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');
@ini_set('max_execution_time', '300');

// Enqueue splash screen on login page
function happyturtle_login_scripts() {
    wp_enqueue_style('happyturtle-splash-style', get_template_directory_uri() . '/assets/style.css', array(), '3.2.0');
    wp_enqueue_script('happyturtle-splash', get_template_directory_uri() . '/assets/splash.js', array(), '4.1.0', true);
    wp_localize_script('happyturtle-splash', 'htbData', array('themeUrl' => get_template_directory_uri()));
}
add_action('login_enqueue_scripts', 'happyturtle_login_scripts');

// Custom Login Page Styling
function happyturtle_login_styles() {
    ?>
    <style type="text/css">
        body.login {
            background: linear-gradient(135deg, #2D6A4F, #52B788, #40916C);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        body.login > * {
            position: static !important;
        }

        #login {
            width: auto;
            margin: 0;
            padding: 0;
        }

        #login h1 a {
            display: none;
        }

        .login form {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        .login form .input,
        .login input[type="text"],
        .login input[type="password"] {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(45, 106, 79, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .login form .input:focus,
        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: #2D6A4F;
            box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.1);
            outline: none;
        }

        .login .button-primary {
            background: linear-gradient(135deg, #1B4332, #2D6A4F, #D4A574);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 700;
            text-shadow: none;
            box-shadow: 0 3px 6px rgba(15, 36, 25, 0.15), 0 2px 4px rgba(212, 165, 116, 0.1);
            transition: all 0.3s ease;
        }

        .login .button-primary:hover {
            background: linear-gradient(135deg, #0F2419, #1B4332, #B8854A);
            box-shadow: 0 10px 20px rgba(15, 36, 25, 0.19), 0 6px 6px rgba(212, 165, 116, 0.12);
            transform: translateY(-2px);
        }

        .login #backtoblog,
        .login #nav {
            background: transparent;
            padding: 0;
            margin-top: 1rem;
            text-align: center;
        }

        .login #backtoblog a,
        .login #nav a {
            color: #FFFFFF !important;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            transition: color 0.3s ease;
        }

        .login #backtoblog a:hover,
        .login #nav a:hover {
            color: #E8C9A0 !important;
        }

        .login .message,
        .login .success {
            border-left: 4px solid #2D6A4F;
            background: rgba(45, 106, 79, 0.1);
            border-radius: 8px;
        }

        /* Ensure splash screen works on login page */
        body.login .htb-splash-screen {
            position: fixed !important;
            z-index: 999999 !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'happyturtle_login_styles');

// Change login logo URL
function happyturtle_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'happyturtle_login_logo_url');

// Change login logo title
function happyturtle_login_logo_url_title() {
    return 'Happy Turtle Processing, Inc.';
}
add_filter('login_headertext', 'happyturtle_login_logo_url_title');

// Add "Authorized Personnel Only" notice to WP admin login page
function happyturtle_login_message($message) {
    $notice = '<div style="background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
        <span style="margin-right: 8px;">&#9888;</span> AUTHORIZED PERSONNEL ONLY
    </div>';
    return $notice . $message;
}
add_filter('login_message', 'happyturtle_login_message');

// Add "Authorized Partners Only" notice to WooCommerce My Account login
function happyturtle_partner_login_notice() {
    if (!is_user_logged_in()) {
        echo '<div style="background: linear-gradient(135deg, #1B4332, #2D6A4F); color: #fff; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); width: 100%; max-width: 400px;">
            <span style="margin-right: 8px;">&#128274;</span> AUTHORIZED PARTNERS ONLY
        </div>';
    }
}
add_action('woocommerce_before_customer_login_form', 'happyturtle_partner_login_notice');

// ============================================================================
// AR LICENSE NUMBER LOGIN
// ============================================================================
// Allow partners to log in with their AR license number (stored in user meta)

function happyturtle_allow_license_login($user, $username, $password) {
    // If already authenticated or empty username, skip
    if ($user instanceof WP_User || empty($username)) {
        return $user;
    }

    // Check if username looks like an AR license number (just digits)
    if (preg_match('/^\d{1,6}$/', $username)) {
        // Try to find user by ar_disp_XXX username format
        $found_user = get_user_by('login', 'ar_disp_' . $username);

        if ($found_user) {
            // Verify password
            if (wp_check_password($password, $found_user->user_pass, $found_user->ID)) {
                return $found_user;
            } else {
                return new WP_Error('incorrect_password', __('The password you entered is incorrect.'));
            }
        }

        // Also check user meta ar_license_number
        $users = get_users([
            'meta_key' => 'ar_license_number',
            'meta_value' => $username,
            'number' => 1,
        ]);

        if (!empty($users)) {
            $found_user = $users[0];
            if (wp_check_password($password, $found_user->user_pass, $found_user->ID)) {
                return $found_user;
            } else {
                return new WP_Error('incorrect_password', __('The password you entered is incorrect.'));
            }
        }

        // Also check partner table if B2B Suite is active
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}b2b_partners'");
        if ($table_exists) {
            $partner = $wpdb->get_row($wpdb->prepare(
                "SELECT wp_user_id FROM {$wpdb->prefix}b2b_partners WHERE license_number = %s OR license_number = %s",
                $username,
                'D' . str_pad($username, 5, '0', STR_PAD_LEFT)
            ));

            if ($partner && $partner->wp_user_id) {
                $found_user = get_user_by('id', $partner->wp_user_id);
                if ($found_user && wp_check_password($password, $found_user->user_pass, $found_user->ID)) {
                    return $found_user;
                }
            }
        }
    }

    return $user;
}
add_filter('authenticate', 'happyturtle_allow_license_login', 20, 3);

// Block subscribers from logging in - must be verified partner first
function happyturtle_block_subscriber_login($user, $username, $password) {
    if ($user instanceof WP_User) {
        // Check if user is only a subscriber (not verified)
        if (in_array('subscriber', $user->roles) && !in_array('partner', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error(
                'account_pending',
                __('Your account is pending verification. You will receive an email with login credentials once approved.')
            );
        }
    }
    return $user;
}
add_filter('authenticate', 'happyturtle_block_subscriber_login', 30, 3);

// Update login form with help text
function happyturtle_login_label_change() {
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update username label
            var label = document.querySelector('label[for="username"]');
            if (label) {
                label.innerHTML = 'AR Dispensary License # <span class="required" aria-hidden="true">*</span>';
            }

            // Update placeholder
            var input = document.querySelector('#username');
            if (input) {
                input.placeholder = 'Enter your license number (e.g., 309)';
            }

            // Add help text below the form
            var form = document.querySelector('.woocommerce-form-login');
            if (form && !document.querySelector('.htp-login-help')) {
                var helpDiv = document.createElement('div');
                helpDiv.className = 'htp-login-help';
                helpDiv.style.cssText = 'margin-top: 20px; padding: 15px; background: #f0f9f4; border-radius: 8px; font-size: 13px; color: #1B4332; border: 1px solid #d4edda;';
                helpDiv.innerHTML = '<strong>Login Help:</strong><br>Enter your Arkansas Dispensary License Number (the number only, e.g., <strong>309</strong>) and the password sent to you by Happy Turtle Processing.<br><br><em>Not yet a partner? <a href="/partner-application" style="color:#2D6A4F;font-weight:600;">Apply here</a></em>';
                form.appendChild(helpDiv);
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'happyturtle_login_label_change');

// Style the WooCommerce My Account login form and button
function happyturtle_myaccount_login_styles() {
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        ?>
        <style>
            /* Center entire login section on page */
            .woocommerce {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 60vh;
                max-width: 400px !important;
                margin: 0 auto !important;
                padding: 40px 20px;
            }

            /* Hide the Login title */
            .woocommerce-form-login > h2,
            .woocommerce > h2,
            .u-column1 > h2 {
                display: none !important;
            }

            /* Style error/notice messages */
            .woocommerce-error,
            .woocommerce-message,
            .woocommerce-info {
                width: 100%;
                max-width: 400px;
                margin-bottom: 20px !important;
                border-radius: 8px;
            }

            /* My Account Login Page Styling */
            .woocommerce-form-login {
                max-width: 400px;
                width: 100%;
                padding: 30px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }

            .woocommerce-form-login .form-row input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.3s ease;
            }

            .woocommerce-form-login .form-row input:focus {
                border-color: #2D6A4F;
                outline: none;
                box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.1);
            }

            .woocommerce-form-login .woocommerce-form-login__submit,
            .woocommerce-form-login button[type="submit"] {
                width: 100%;
                padding: 16px 24px;
                background: linear-gradient(135deg, #1B4332, #2D6A4F) !important;
                color: #fff !important;
                border: none !important;
                border-radius: 8px;
                font-size: 18px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(27, 67, 50, 0.3);
                margin-top: 16px;
            }

            .woocommerce-form-login .woocommerce-form-login__submit:hover,
            .woocommerce-form-login button[type="submit"]:hover {
                background: linear-gradient(135deg, #0F2419, #1B4332) !important;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(27, 67, 50, 0.4);
            }

            .woocommerce-LostPassword {
                text-align: center;
                margin-top: 16px;
            }

            .woocommerce-LostPassword a {
                color: #2D6A4F;
                text-decoration: none;
            }

            .woocommerce-LostPassword a:hover {
                text-decoration: underline;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'happyturtle_myaccount_login_styles');

// ============================================================================
// CUSTOM FAVICON (SVG ATOM)
// ============================================================================

function happyturtle_custom_favicon() {
    // Remove default site icon
    remove_action('wp_head', 'wp_site_icon', 99);

    $favicon_url = get_template_directory_uri() . '/assets/images/favicon.svg';
    ?>
    <link rel="icon" href="<?php echo esc_url($favicon_url); ?>" type="image/svg+xml">
    <link rel="icon" href="<?php echo esc_url($favicon_url); ?>" sizes="any" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo esc_url($favicon_url); ?>">
    <?php
}
add_action('wp_head', 'happyturtle_custom_favicon', 1);

// Also add to login page
function happyturtle_login_favicon() {
    $favicon_url = get_template_directory_uri() . '/assets/images/favicon.svg';
    ?>
    <link rel="icon" href="<?php echo esc_url($favicon_url); ?>" type="image/svg+xml">
    <?php
}
add_action('login_head', 'happyturtle_login_favicon', 1);




// ============================================================================
// HIDE CART/ACCOUNT ICONS UNTIL LOGGED IN
// ============================================================================
// Partners log in via the atom icon in the footer

function happyturtle_hide_cart_account_icons() {
    // Hide cart/account for non-logged-in users
    if (!is_user_logged_in()) {
        echo '<style>
            .wp-block-woocommerce-customer-account,
            .wc-block-mini-cart,
            .wp-block-woocommerce-mini-cart,
            .logged-in-only {
                display: none !important;
            }
        </style>';
    }
}
add_action('wp_head', 'happyturtle_hide_cart_account_icons');

// ============================================================================
// ACCOUNT ICON HOVER DROPDOWN
// ============================================================================

function happyturtle_account_dropdown() {
    if (!is_user_logged_in()) return;

    $account_url = wc_get_page_permalink('myaccount');
    ?>
    <style>
        .wp-block-woocommerce-customer-account {
            position: relative !important;
            z-index: 999999 !important;
        }
        .htp-account-dropdown {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            background: #fff !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25) !important;
            min-width: 180px !important;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            z-index: 9999999 !important;
            padding: 8px 0 !important;
            margin-top: 8px !important;
            overflow: visible !important;
        }
        .wp-block-woocommerce-customer-account:hover .htp-account-dropdown,
        .htp-account-dropdown:hover {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        /* Fix parent containers that might clip */
        .site-header,
        .site-header .wp-block-group,
        .wp-block-navigation,
        header,
        .wp-block-group.site-header {
            overflow: visible !important;
        }
        .htp-account-dropdown a {
            display: block !important;
            padding: 12px 18px !important;
            color: #1a1a1a !important;
            text-decoration: none !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            transition: background 0.2s !important;
            background: #fff !important;
        }
        .htp-account-dropdown a:hover {
            background: #f5f5f5 !important;
            color: #2D6A4F !important;
        }
        .htp-account-dropdown a.logout-link {
            border-top: 1px solid #eee !important;
            margin-top: 4px !important;
            padding-top: 14px !important;
            color: #dc2626 !important;
        }
        .htp-account-dropdown a.logout-link:hover {
            background: #fef2f2 !important;
            color: #b91c1c !important;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var accountIcon = document.querySelector('.wp-block-woocommerce-customer-account');
        if (!accountIcon || accountIcon.querySelector('.htp-account-dropdown')) return;

        var dropdown = document.createElement('div');
        dropdown.className = 'htp-account-dropdown';
        dropdown.innerHTML = '<a href="<?php echo esc_url($account_url . 'orders/'); ?>">Orders</a>' +
                            '<a href="<?php echo esc_url($account_url . 'edit-account/'); ?>">Account details</a>' +
                            '<a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link">Log out</a>';
        accountIcon.appendChild(dropdown);
    });
    </script>
    <?php
}
add_action('wp_footer', 'happyturtle_account_dropdown');

// ============================================================================
// HIDE PAGE TITLES GLOBALLY
// ============================================================================

function happyturtle_hide_page_titles() {
    echo '<style>
        /* Hide page titles globally - but NOT on single product pages */
        .entry-title,
        .page-title,
        .wp-block-post-title,
        h1.entry-title,
        h1.page-title,
        .woocommerce-products-header__title,
        article.page .entry-header,
        .page .wp-block-post-title,
        .archive-title,
        .woocommerce-products-header h1,
        .woocommerce-page h1.page-title,
        .woocommerce h1.page-title,
        header.woocommerce-products-header,
        .term-description,
        body.post-type-archive-product h1,
        .wp-block-query-title {
            display: none !important;
        }

        /* Show product title on single product pages */
        .single-product h1.product_title,
        .single-product .product_title,
        body.single-product h1.product_title {
            display: block !important;
            visibility: visible !important;
        }
    </style>';
}
add_action('wp_head', 'happyturtle_hide_page_titles');

// ============================================================================
// WOOCOMMERCE SHOP STYLES - Now handled by B2B Suite plugin
// ============================================================================
// Shop and product page styles are in:
// plugins/b2b-suite/includes/class-wc-shop-filters.php

// ============================================================================
// WOOCOMMERCE ATOM PLACEHOLDER IMAGE
// ============================================================================

// Override the entire placeholder HTML to use inline SVG
function happyturtle_wc_placeholder_img($html, $size, $dimensions) {
    // Determine size based on context
    $width = is_product() ? 400 : 150;
    $height = is_product() ? 400 : 150;

    // Atom SVG with green background
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="' . $width . '" height="' . $height . '" style="background: linear-gradient(135deg, #1B4332, #2D6A4F); padding: 30px; border-radius: 8px; display: block; margin: 0 auto;">
      <defs>
        <radialGradient id="nucleusGlow" cx="50%" cy="50%" r="50%">
          <stop offset="0%" style="stop-color:#FFFFFF"/>
          <stop offset="40%" style="stop-color:#FFE4B5"/>
          <stop offset="70%" style="stop-color:#FFA500"/>
          <stop offset="100%" style="stop-color:#FF8C00"/>
        </radialGradient>
        <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
          <feGaussianBlur stdDeviation="1" result="blur"/>
          <feMerge>
            <feMergeNode in="blur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      </defs>
      <g fill="none" stroke-width="1.5" filter="url(#glow)">
        <ellipse cx="16" cy="16" rx="14" ry="5" stroke="#FF8C00" opacity="0.9" transform="rotate(-35 16 16)"/>
        <ellipse cx="16" cy="16" rx="14" ry="5" stroke="#FFA500" opacity="0.85" transform="rotate(35 16 16)"/>
        <ellipse cx="16" cy="16" rx="5" ry="14" stroke="#FFD700" opacity="0.8"/>
      </g>
      <circle cx="16" cy="16" r="5" fill="url(#nucleusGlow)" filter="url(#glow)"/>
      <circle cx="16" cy="16" r="2.5" fill="#FFFFFF" opacity="0.95"/>
      <circle cx="14.5" cy="14.5" r="1" fill="white" opacity="0.6"/>
    </svg>';

    return $svg;
}
add_filter('woocommerce_placeholder_img', 'happyturtle_wc_placeholder_img', 10, 3);


// ============================================================================
// WOOCOMMERCE ACCESS CONTROL - PARTNER AND ADMIN ONLY
// ============================================================================
// ABC Rule 19.1(a) Compliance: Store access restricted to verified partners only.
// - Subscribers: Can log in but cannot access store (pending verification)
// - Partners: Full store access (verified)
// - Administrators: Full store access

function happyturtle_restrict_woocommerce_access() {
    // Skip admin and AJAX requests
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    // Skip if WooCommerce isn't active
    if (!function_exists('is_woocommerce')) {
        return;
    }

    // Check if on any WooCommerce page that should be restricted
    $is_store_page = false;

    if (function_exists('is_shop') && is_shop()) $is_store_page = true;
    if (function_exists('is_product_category') && is_product_category()) $is_store_page = true;
    if (function_exists('is_product_tag') && is_product_tag()) $is_store_page = true;
    if (function_exists('is_product') && is_product()) $is_store_page = true;
    if (function_exists('is_cart') && is_cart()) $is_store_page = true;
    if (function_exists('is_checkout') && is_checkout()) $is_store_page = true;
    if (function_exists('is_woocommerce') && is_woocommerce() && !is_account_page()) $is_store_page = true;

    // Allow my-account page for login
    if (function_exists('is_account_page') && is_account_page()) {
        return;
    }

    // If not a store page, allow access
    if (!$is_store_page) {
        return;
    }

    // Not logged in - redirect to login
    if (!is_user_logged_in()) {
        wp_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    // Logged in - check if user has partner or admin role
    $user = wp_get_current_user();
    $allowed_roles = ['partner', 'administrator'];

    if (!array_intersect($allowed_roles, $user->roles)) {
        // User is logged in but not a verified partner (e.g., subscriber)
        wp_redirect(home_url('/?access=pending'));
        exit;
    }
}
add_action('template_redirect', 'happyturtle_restrict_woocommerce_access');

// ============================================================================
// B2B FUNCTIONALITY
// ============================================================================
//
// All B2B functionality has been moved to the B2B Suite plugin:
// - Partner Management
// - Product Catalog
// - Order Processing
// - Security & Logging
// - REST API
// - WooCommerce Integration
//
// Make sure B2B Suite plugin is active for full functionality.


// ============================================================================
// PROTECT PARTNER ROLE FROM WOOCOMMERCE OVERRIDE
// ============================================================================
// Prevents WooCommerce from changing "partner" role to "customer"

function happyturtle_protect_partner_role($customer_id, $new_customer_data, $password_generated) {
    $user = get_user_by('id', $customer_id);
    if (!$user) return;

    // If user is a partner, don't let WooCommerce change it
    if (in_array('partner', $user->roles)) {
        return;
    }
}
add_action('woocommerce_created_customer', 'happyturtle_protect_partner_role', 5, 3);

// Prevent role change on order completion
function happyturtle_prevent_role_change_on_order($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_customer_id();
    if (!$user_id) return;

    $user = get_user_by('id', $user_id);
    if (!$user) return;

    // If user is a partner, remove "customer" role if WooCommerce added it
    if (in_array('partner', $user->roles) && in_array('customer', $user->roles)) {
        $user->remove_role('customer');
    }
}
add_action('woocommerce_order_status_completed', 'happyturtle_prevent_role_change_on_order', 20);
add_action('woocommerce_order_status_processing', 'happyturtle_prevent_role_change_on_order', 20);

// ============================================================================
// HIDE LOGIN LINKS WHEN B2B PLUGIN IS INACTIVE
// ============================================================================

function happyturtle_hide_login_without_b2b() {
    // Only hide login links when B2B plugin is deactivated
    if (!class_exists('B2B_Suite') && !defined('B2B_SUITE_VERSION')) {
        echo '<style>
            /* Hide login/account links when B2B is inactive */
            a[href*="/my-account"],
            a[href*="wp-login.php"],
            .login-link,
            .partner-login,
            .atom-login-link,
            .footer-atom-link,
            .wp-block-woocommerce-customer-account {
                display: none !important;
            }
        </style>';
    }
}
add_action('wp_head', 'happyturtle_hide_login_without_b2b');

// ============================================================================
// ABC RULE 19.4 - REQUIRED WARNINGS ON PRODUCT/SHOP PAGES
// ============================================================================

function htp_compliance_warning_notice() {
    $warning = '<div class="htp-compliance-warning" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-left: 4px solid #d97706; padding: 15px 20px; margin: 20px 0; border-radius: 8px; font-size: 0.9em; color: #92400e;">
        <strong>⚠️ WARNING:</strong> This product is for use only by licensed Arkansas medical marijuana dispensaries. Cannabis products are intended for adult use only (21+). Keep out of reach of children. These statements have not been evaluated by the FDA. This product is not intended to diagnose, treat, cure, or prevent any disease.
    </div>';
    echo $warning;
}

// Add warning to shop/archive pages (above products)
add_action('woocommerce_before_shop_loop', 'htp_compliance_warning_notice', 5);

// Add warning to single product pages (above product)
add_action('woocommerce_before_single_product', 'htp_compliance_warning_notice', 5);

// Add warning to cart page
add_action('woocommerce_before_cart', 'htp_compliance_warning_notice', 5);

// Add warning to checkout page
add_action('woocommerce_before_checkout_form', 'htp_compliance_warning_notice', 5);

// ABC COMPLIANCE WARNING - JavaScript injection for block themes
function htp_compliance_warning_js() {
    if (!function_exists('is_woocommerce')) return;
    if (!is_woocommerce() && !is_cart() && !is_checkout()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('.htp-compliance-warning')) return;
        var warning = document.createElement('div');
        warning.className = 'htp-compliance-warning';
        warning.innerHTML = '<strong>⚠️ WARNING:</strong> This product is for use only by licensed Arkansas medical marijuana dispensaries. Cannabis products are intended for adult use only (21+). Keep out of reach of children. These statements have not been evaluated by the FDA. This product is not intended to diagnose, treat, cure, or prevent any disease.';
        warning.style.cssText = 'background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-left: 4px solid #d97706; padding: 15px 20px; margin: 20px auto; border-radius: 8px; font-size: 0.9em; color: #92400e; max-width: 1200px;';
        var main = document.querySelector('.woocommerce-products-header, .wp-block-woocommerce-product-collection, .woocommerce, main .entry-content, main');
        if (main) main.insertBefore(warning, main.firstChild);
    });
    </script>
    <?php
}
add_action('wp_footer', 'htp_compliance_warning_js');
