<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('simpleab_options');

// If you have any custom tables, you can delete them here
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}simpleab_custom_table");

// Clear any cached data that may still exist
wp_cache_flush();