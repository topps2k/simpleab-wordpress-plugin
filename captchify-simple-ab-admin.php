<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page
function captchify_simpleab_add_settings_page() {
    add_options_page('Captchify Simple A/B Settings', 'Captchify Simple A/B', 'manage_options', 'captchify-simple-ab', 'captchify_simpleab_render_settings_page');
}
add_action('admin_menu', 'captchify_simpleab_add_settings_page');

// Render settings page
function captchify_simpleab_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('captchify-simple-ab-options');
            do_settings_sections('captchify-simple-ab');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function captchify_simpleab_register_settings() {
    register_setting(
        'captchify-simple-ab-options',
        'captchify-simple-ab-options',
        'captchify_simpleab_sanitize_options'
    );
    add_settings_section('captchify_simpleab_main', 'Main Settings', 'captchify_simpleab_section_text', 'captchify-simple-ab');
    add_settings_field('captchify_simpleab_api_key', 'API Key', 'captchify_simpleab_api_key_field', 'captchify-simple-ab', 'captchify_simpleab_main');
}
add_action('admin_init', 'captchify_simpleab_register_settings');

// Sanitize options
function captchify_simpleab_sanitize_options($input) {
    $sanitized_input = array();
    if (isset($input['api_key'])) {
        $sanitized_input['api_key'] = sanitize_text_field($input['api_key']);
    }
    return $sanitized_input;
}

// Settings section description
function captchify_simpleab_section_text() {
    esc_html_e('Enter your Captchify Simple A/B API settings below:', 'captchify-simple-ab');
}

// API Key field
function captchify_simpleab_api_key_field() {
    $options = get_option('captchify-simple-ab-options');
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    echo "<input id='captchify_simpleab_api_key' name='captchify-simple-ab-options[api_key]' type='text' value='" . esc_attr($api_key) . "' />";
}

// Enqueue admin scripts and styles
function captchify_simpleab_enqueue_admin_scripts($hook) {
    if ('settings_page_captchify-simple-ab' !== $hook) {
        return;
    }
    // Note: Make sure this CSS file exists in your plugin directory
    wp_enqueue_style('captchify-simple-ab-admin-css', plugins_url('css/captchify-simple-ab-admin.css', dirname(__FILE__)), array(), CAPTCHIFY_SIMPLEAB_VERSION);
}
add_action('admin_enqueue_scripts', 'captchify_simpleab_enqueue_admin_scripts');