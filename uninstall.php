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
    'dskapi_reklama'
);
foreach ($options as $option) {
    delete_option( $option );
    delete_site_option( $option );
}
// Clear any cached data that has been removed.
wp_cache_flush();
