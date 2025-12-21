<?php
// Additional methods to add to HappyTurtle_Plugin_Recommendations class

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
