<?php

/**
 * DSK POS Loans Uninstall
 *
 * Handles cleanup when the plugin is uninstalled.
 * Removes all plugin options and database tables.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

/**
 * Plugin options to be removed on uninstall.
 *
 * @var array $options List of option names to delete.
 */
$options = array(
    'dskapi_status',
    'dskapi_cid',
    'dskapi_reklama',
    'dskapi_gap',
    'dskapi_db_version'
);

/**
 * Remove all plugin options from the database.
 * Handles both single-site and multisite installations.
 */
foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option);
}

/**
 * Custom database table name for DSK payment orders.
 *
 * @var string $table_orders_name Full table name with prefix.
 */
$table_orders_name = $wpdb->prefix . 'dskpayment_orders';

/**
 * Drop the custom orders table.
 * Uses IF EXISTS to prevent errors if table doesn't exist.
 */
$wpdb->query("DROP TABLE IF EXISTS $table_orders_name;");

/**
 * Clear any cached data related to the removed options and tables.
 */
wp_cache_flush();
