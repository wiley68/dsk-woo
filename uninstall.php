<?php
// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

// remove plugin options
$options = array(
    'dskapi_status',
    'dskapi_cid',
    'dskapi_reklama',
    'dskapi_gap',
    'dskapi_db_version'
);
foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option);
}

$table_orders_name    = $wpdb->prefix . 'dskpayment_orders';

$wpdb->query("DROP TABLE IF EXISTS $table_orders_name;");

// Clear any cached data that has been removed.
wp_cache_flush();
