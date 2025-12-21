<?php
/**
 * One-time script to create legal pages
 * Visit: http://localhost:8000/wp-content/themes/happyturtle-fse/setup-legal-pages.php
 * DELETE THIS FILE AFTER RUNNING
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

$pages_to_create = [
    [
        'title' => 'Disclaimer',
        'slug' => 'disclaimer',
    ],
    [
        'title' => 'Cookie Policy',
        'slug' => 'cookie-policy',
    ],
];

$created = [];
$skipped = [];

foreach ($pages_to_create as $page) {
    // Check if page already exists
    $existing = get_page_by_path($page['slug']);

    if ($existing) {
        $skipped[] = $page['title'];
        continue;
    }

    // Create the page
    $page_id = wp_insert_post([
        'post_title'   => $page['title'],
        'post_name'    => $page['slug'],
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '', // Template will provide content
    ]);

    if ($page_id && !is_wp_error($page_id)) {
        $created[] = $page['title'];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Legal Pages Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        h1 { color: #1B4332; }
        .success { color: #2D6A4F; background: #d4edda; padding: 10px; border-radius: 8px; margin: 10px 0; }
        .info { color: #856404; background: #fff3cd; padding: 10px; border-radius: 8px; margin: 10px 0; }
        .warning { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 8px; margin: 10px 0; }
        a { color: #D4A574; }
    </style>
</head>
<body>
    <h1>Legal Pages Setup Complete</h1>

    <?php if (!empty($created)): ?>
        <div class="success">
            <strong>Created:</strong><br>
            <?php foreach ($created as $title): ?>
                - <?php echo esc_html($title); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($skipped)): ?>
        <div class="info">
            <strong>Already existed (skipped):</strong><br>
            <?php foreach ($skipped as $title): ?>
                - <?php echo esc_html($title); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="warning">
        <strong>IMPORTANT:</strong> Delete this file now for security!<br>
        <code>setup-legal-pages.php</code>
    </div>

    <p><strong>Test your pages:</strong></p>
    <ul>
        <li><a href="/disclaimer/">Disclaimer</a></li>
        <li><a href="/cookie-policy/">Cookie Policy</a></li>
        <li><a href="/privacy-policy/">Privacy Policy</a></li>
        <li><a href="/terms/">Terms of Service</a></li>
    </ul>

    <p><a href="/wp-admin/">← Back to Admin</a></p>
</body>
</html>
