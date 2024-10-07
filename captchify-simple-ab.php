<?php
/**
 * Plugin Name: Captchify Simple A/B
 * Plugin URI: https://www.captchify.com
 * Description: A WordPress plugin that uses the SimpleAB PHP SDK for A/B/n testing.
 * Version: 1.1.0
 * Author: captchify
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: captchify-simple-ab
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CAPTCHIFY_SIMPLEAB_VERSION', '1.1.0');
define('CAPTCHIFY_SIMPLEAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAPTCHIFY_SIMPLEAB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include SimpleAB PHP SDK
require_once CAPTCHIFY_SIMPLEAB_PLUGIN_DIR . 'SimpleABSDK.php';

// Include admin functions
require_once CAPTCHIFY_SIMPLEAB_PLUGIN_DIR . 'captchify-simple-ab-admin.php';

// Debug logging function
function simpleab_debug_log($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

// Initialize SimpleAB SDK
function simpleab_init_sdk() {
    $simpleab_options = get_option('captchify-simple-ab-options');
    $api_key = isset($simpleab_options['api_key']) ? sanitize_text_field($simpleab_options['api_key']) : '';
    $api_url = 'https://api.captchify.com'; // Add option later for multi-region support

    global $simpleab_sdk;
    try {
        $simpleab_sdk = new \SimpleAB\SDK\SimpleABSDK($api_url, $api_key);
        simpleab_debug_log("SimpleAB SDK initialized successfully");
    } catch (Exception $e) {
        simpleab_debug_log("Error initializing SimpleAB SDK: " . $e->getMessage());
    }
}
add_action('init', 'simpleab_init_sdk');

// Enqueue styles
function simpleab_enqueue_styles($hook) {
    // Only enqueue on the SimpleAB settings page
    if ('settings_page_simpleab' === $hook) {
        wp_enqueue_style('simpleab-css', CAPTCHIFY_SIMPLEAB_PLUGIN_URL . 'css/simpleab-admin.css', array(), CAPTCHIFY_SIMPLEAB_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'simpleab_enqueue_styles');

// Shortcode for A/B/n test with dynamic allocation key and metric tracking
function simpleab_ab_test_shortcode($atts, $content = null) {
    // Disable wpautop to avoid unwanted <p> tags
    remove_filter('the_content', 'wpautop');

    global $simpleab_sdk;

    // Merge shortcode attributes with default values
    $a = shortcode_atts(array(
        'experiment_id' => '',
        'stage' => 'Prod',
        'dimension' => '',
        'allocation_key' => is_user_logged_in() ? (string)get_current_user_id() : (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''),
    ), $atts);

    // Register and enqueue the script
    wp_register_script('simpleab-metric-script', CAPTCHIFY_SIMPLEAB_PLUGIN_URL . 'js/simpleab-metric.js', array(), CAPTCHIFY_SIMPLEAB_VERSION, true);

    // Get treatment using PHP SDK
    try {
        $treatment = $simpleab_sdk->getTreatment(
            $a['experiment_id'],
            $a['stage'],
            $a['dimension'],
            $a['allocation_key']
        );
        simpleab_debug_log("Treatment returned: " . $treatment);
    } catch (Exception $e) {
        simpleab_debug_log("Error getting treatment: " . $e->getMessage());
        return "<!-- SimpleAB Error: " . esc_html($e->getMessage()) . " -->";
    }

    // Start building the output
    $output = "<div class='simpleab-experiment' 
                    data-experiment-id='" . esc_attr($a['experiment_id']) . "'
                    data-stage='" . esc_attr($a['stage']) . "'
                    data-dimension='" . esc_attr($a['dimension']) . "'
                    data-treatment='" . esc_attr($treatment) . "'>";

    if (!is_null($content)) {
        $variants = explode('||', $content);

        // Generate HTML for each variant
        foreach ($variants as $index => $variant) {
            $variant_treatment = $index == 0 ? 'C' : 'T' . $index;
            $style = ($variant_treatment === $treatment) ? '' : 'style="display:none;"';
            $output .= "<div class='simpleab-variant-" . esc_attr($variant_treatment) . "' $style>" . do_shortcode(trim($variant)) . "</div>";
        }
    }

    $output .= "</div>";

    // Localize script with necessary data
    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('simpleab_track_metric')
    );

    wp_localize_script('simpleab-metric-script', 'simpleab_data', $script_data);

    // Enqueue the script
    wp_enqueue_script('simpleab-metric-script');

    // Restore wpautop after processing the shortcode
    add_filter('the_content', 'wpautop');

    simpleab_debug_log("Shortcode output generated successfully");

    $filtered = str_replace(array("\r\n", "\r", "\n"), '', $output);
    return $filtered;
}
add_shortcode('simpleab_test_custom', 'simpleab_ab_test_shortcode');

function simpleab_get_user_ip() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    } else {
        $ip = '';
    }
    return $ip;
}


// Shortcode for A/B/n test with dynamic allocation key and metric tracking
function simpleab_segment_shortcode($atts, $content = null) {
    // Disable wpautop to avoid unwanted <p> tags
    remove_filter('the_content', 'wpautop');

    global $simpleab_sdk;

    // Merge shortcode attributes with default values
    $a = shortcode_atts(array(
        'experiment_id' => '',
        'stage' => 'Prod',
        'allocation_key' => is_user_logged_in() ? (string)get_current_user_id() : (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''),
    ), $atts);

    // Register and enqueue the script
    wp_register_script('simpleab-segment-metric-script', CAPTCHIFY_SIMPLEAB_PLUGIN_URL . 'js/simpleab-segment-metric.js', array(), CAPTCHIFY_SIMPLEAB_VERSION, true);

    // Get segment using PHP SDK
    try {
        $segment = $simpleab_sdk->getSegment([
            'ip' => simpleab_get_user_ip(),
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : ''
        ]);

    } catch (Exception $e) {
        simpleab_debug_log("Error getting segment: " . $e->getMessage());
        return "<!-- SimpleAB Error: " . esc_html($e->getMessage()) . " -->";
    }

    // Get treatment using PHP SDK
    try {
        $treatment = $simpleab_sdk->getTreatmentWithSegment(
            $a['experiment_id'],
            $a['stage'],
            $segment,
            $a['allocation_key']
        );
        simpleab_debug_log("Treatment returned: " . $treatment);
    } catch (Exception $e) {
        simpleab_debug_log("Error getting treatment: " . $e->getMessage());
        return "<!-- SimpleAB Error: " . esc_html($e->getMessage()) . " -->";
    }

    // Start building the output
    $output = "<div class='simpleab-segment-experiment' 
                    data-experiment-id='" . esc_attr($a['experiment_id']) . "'
                    data-stage='" . esc_attr($a['stage']) . "'
                    data-segment='" . esc_attr(wp_json_encode($segment)) . "'
                    data-treatment='" . esc_attr($treatment) . "'>";

    if (!is_null($content)) {
        $variants = explode('||', $content);

        // Generate HTML for each variant
        foreach ($variants as $index => $variant) {
            $variant_treatment = $index == 0 ? 'C' : 'T' . $index;
            $style = ($variant_treatment === $treatment) ? '' : 'style="display:none;"';
            $output .= "<div class='simpleab-variant-" . esc_attr($variant_treatment) . "' $style>" . do_shortcode(trim($variant)) . "</div>";
        }
    }

    $output .= "</div>";

    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('simpleab_segment_track_metric')
    );
    wp_localize_script('simpleab-segment-metric-script', 'simpleab_segment_data', $script_data);

    // Enqueue the script
    wp_enqueue_script('simpleab-segment-metric-script');

    // Restore wpautop after processing the shortcode
    add_filter('the_content', 'wpautop');

    simpleab_debug_log("Shortcode output generated successfully");

    $filtered = str_replace(array("\r\n", "\r", "\n"), '', $output);

    return $filtered;
}
add_shortcode('simpleab_test', 'simpleab_segment_shortcode');

// AJAX handler for tracking metrics
function simpleab_track_metric_ajax() {
    check_ajax_referer('simpleab_track_metric');

    global $simpleab_sdk;

    try {
        $simpleab_sdk->trackMetric([
            'experimentID' => isset($_POST['experiment_id']) ? sanitize_text_field(wp_unslash($_POST['experiment_id'])) : '',
            'stage' => isset($_POST['stage']) ? sanitize_text_field(wp_unslash($_POST['stage'])) : '',
            'dimension' => isset($_POST['dimension']) ? sanitize_text_field(wp_unslash($_POST['dimension'])) : '',
            'treatment' => isset($_POST['treatment']) ? sanitize_text_field(wp_unslash($_POST['treatment'])) : '',
            'metricName' => isset($_POST['metric_name']) ? sanitize_text_field(wp_unslash($_POST['metric_name'])) : '',
            'metricValue' => isset($_POST['metric_value']) ? floatval(wp_unslash($_POST['metric_value'])) : 0,
            'aggregationType' => isset($_POST['aggregation_type']) ? sanitize_text_field(wp_unslash($_POST['aggregation_type'])) : ''
        ]);
        $simpleab_sdk->flush();
        simpleab_debug_log("Metric tracked successfully via AJAX");
    } catch (Exception $e) {
        simpleab_debug_log("Error tracking metric via AJAX: " . $e->getMessage());
    }

    wp_die();
}
add_action('wp_ajax_simpleab_track_metric', 'simpleab_track_metric_ajax');
add_action('wp_ajax_nopriv_simpleab_track_metric', 'simpleab_track_metric_ajax');

// AJAX handler for tracking metrics
function simpleab_segment_track_metric_ajax() {
    check_ajax_referer('simpleab_segment_track_metric');

    global $simpleab_sdk;

    if (isset($_POST['segment'])) {
        // Decode the JSON-encoded segment data
        $segment_json = sanitize_text_field(wp_unslash($_POST['segment']));
        $segment = json_decode($segment_json);
    
        // Debug to check if the segment was decoded properly
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON parsing error
            simpleab_debug_log('Error decoding segment: ' . json_last_error_msg());
        }

    }

    try {
        $simpleab_sdk->trackMetricWithSegment([
            'experimentID' => isset($_POST['experiment_id']) ? sanitize_text_field(wp_unslash($_POST['experiment_id'])) : '',
            'stage' => isset($_POST['stage']) ? sanitize_text_field(wp_unslash($_POST['stage'])) : '',
            'segment' => $segment,
            'treatment' => isset($_POST['treatment']) ? sanitize_text_field(wp_unslash($_POST['treatment'])) : '',
            'metricName' => isset($_POST['metric_name']) ? sanitize_text_field(wp_unslash($_POST['metric_name'])) : '',
            'metricValue' => isset($_POST['metric_value']) ? floatval(wp_unslash($_POST['metric_value'])) : 0,
            'aggregationType' => isset($_POST['aggregation_type']) ? sanitize_text_field(wp_unslash($_POST['aggregation_type'])) : ''
        ]);
        $simpleab_sdk->flush();
        simpleab_debug_log("Metric tracked successfully via AJAX");
    } catch (Exception $e) {
        simpleab_debug_log("Error tracking metric via AJAX: " . $e->getMessage());
    }

    wp_die();
}
add_action('wp_ajax_simpleab_segment_track_metric', 'simpleab_segment_track_metric_ajax');
add_action('wp_ajax_nopriv_simpleab_segment_track_metric', 'simpleab_segment_track_metric_ajax');