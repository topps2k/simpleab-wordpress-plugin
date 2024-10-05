<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page
function simpleab_add_settings_page() {
    add_options_page('Simple A/B Settings', 'Simple A/B', 'manage_options', 'simpleab', 'simpleab_render_settings_page');
}
add_action('admin_menu', 'simpleab_add_settings_page');

// Render settings page
function simpleab_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('simpleab_options');
            do_settings_sections('simpleab');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function simpleab_register_settings() {
    register_setting('simpleab_options', 'simpleab_options');
    add_settings_section('simpleab_main', 'Main Settings', 'simpleab_section_text', 'simpleab');
    add_settings_field('simpleab_api_key', 'API Key', 'simpleab_api_key_field', 'simpleab', 'simpleab_main');
}
add_action('admin_init', 'simpleab_register_settings');

// Settings section description
function simpleab_section_text() {
    esc_html_e('Enter your Simple A/B API settings below:', 'simpleab');
}

// API Key field
function simpleab_api_key_field() {
    $options = get_option('simpleab_options');
    echo "<input id='simpleab_api_key' name='simpleab_options[api_key]' type='text' value='" . esc_attr($options['api_key']) . "' />";
}

// Enqueue admin scripts and styles
function simpleab_enqueue_admin_scripts($hook) {
    if ('settings_page_simpleab' !== $hook) {
        return;
    }
    wp_enqueue_style('simpleab-admin-css', plugins_url('css/simpleab-admin.css', __FILE__), array(), SIMPLEAB_VERSION);
}
add_action('admin_enqueue_scripts', 'simpleab_enqueue_admin_scripts');