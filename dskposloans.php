<?php

/**
 * Plugin Name: Банка ДСК покупки на Кредит
 * Plugin URI: https://avalonbg.com
 * Description: Дава възможност на Вашите клиенти да закупуват стока на изплащане с Банка ДСК
 * Version: 1.2.0
 * Author: Avalon Ltd
 * Author URI: https://avalonbg.com
 * Owner: Банка ДСК
 * Text Domain: dskposloans
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 10.4.0
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package DSK_POS_Loans
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active.
 * 
 * Checks both active plugins and network-activated plugins.
 *
 * @since 1.0.0
 * @return bool True if WooCommerce is active, false otherwise.
 */
function dskapi_is_woocommerce_active()
{
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
        || is_plugin_active_for_network('woocommerce/woocommerce.php');
}

if (! dskapi_is_woocommerce_active()) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>Банка ДСК покупки на Кредит:</strong> WooCommerce не е активиран! Моля, активирайте го.</p></div>';
    });

    return;
}

/** Plugin constants */
define('DSKAPI_VERSION', '1.2.0');
define('DSKAPI_DB_VERSION', '1.0.1');
define('DSKAPI_PLUGIN_FILE', __FILE__);
define('DSKAPI_PLUGIN_DIR', untrailingslashit(dirname(__FILE__)));
define('DSKAPI_PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('DSKAPI_INCLUDES_DIR', DSKAPI_PLUGIN_DIR . '/includes');
define('DSKAPI_IMAGES_URI', DSKAPI_PLUGIN_URL . '/images');
define('DSKAPI_CSS_URI', DSKAPI_PLUGIN_URL . '/css');
define('DSKAPI_JS_URI', DSKAPI_PLUGIN_URL . '/js');
define('DSKAPI_LIVEURL', 'https://dsk.avalon-bg.eu');
define('DSKAPI_MAIL', 'home@avalonbg.com');

/** includes */
$dskapi_files = [
    '/functions.php',
    '/admin.php'
];
foreach ($dskapi_files as $file) {
    require_once DSKAPI_INCLUDES_DIR . $file;
}

// Declare WooCommerce compatibility (must be before plugins_loaded)
add_action('before_woocommerce_init', 'dskapi_declare_woocommerce_compatibility');

// Bootstrap plugin
add_action('plugins_loaded', 'dskapi_plugin_bootstrap', 0);

/**
 * Initialize the plugin after all plugins are loaded.
 * 
 * Registers all hooks, filters and actions required for the plugin to work.
 *
 * @since 1.0.0
 * @return void
 */
function dskapi_plugin_bootstrap()
{
    /** load plugin class */
    dskapi_load_class_plugin();
    add_filter('woocommerce_payment_gateways', 'add_dskapi_gateway_class');

    /** add origins */
    add_filter('allowed_http_origins', 'dskapi_add_allowed_origins');

    /** add order column dskapi status */
    add_filter('manage_edit-shop_order_columns', 'dskapi_add_order_column_status');
    add_filter('manage_woocommerce_page_wc-orders_columns', 'dskapi_add_order_column_status_hpos');
    /** add order column dskapi status values */
    add_action('manage_shop_order_posts_custom_column', 'dskapi_add_order_column_status_values', 2);
    add_action('manage_woocommerce_page_wc-orders_custom_column', 'dskapi_add_order_column_status_values_hpos', 10, 2);

    /** add admin menu options page ###includes/admin.php### */
    add_action('admin_menu', 'dskapi_admin_actions');
    /** output buffer ###includes/functions.php### */
    add_action('init', 'dskapi_do_output_buffer');
    /** vizualize credit button ###includes/functions.php### */
    add_action('woocommerce_after_add_to_cart_button', 'dskpayment_button');

    /** reklama ###includes/functions.php### */
    add_action('wp_enqueue_scripts', 'dskapi_add_meta');
    add_action('loop_start', 'dskapi_reklama');

    // Registers a custom payment method type for WooCommerce Blocks
    add_action('woocommerce_blocks_loaded', 'dskapi_register_order_approval_payment_method_type');

    /** update order api */
    add_action('wp_ajax_dskapi_updateorder', 'dskapi_updateorder');
    add_action('wp_ajax_nopriv_dskapi_updateorder', 'dskapi_updateorder');
}

/**
 * Declare compatibility with WooCommerce features.
 * 
 * Notifies WooCommerce that the plugin supports:
 * - Cart and Checkout Blocks
 * - High-Performance Order Storage (HPOS)
 *
 * @since 1.2.0
 * @return void
 */
function dskapi_declare_woocommerce_compatibility()
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Cart and Checkout Blocks compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', DSKAPI_PLUGIN_FILE, true);
        // High-Performance Order Storage (HPOS) compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', DSKAPI_PLUGIN_FILE, true);
    }
}

/**
 * Register the payment method for WooCommerce Blocks.
 * 
 * Loads the block integration class and registers it in the
 * WooCommerce Blocks payment method registry.
 *
 * @since 1.1.0
 * @return void
 */
function dskapi_register_order_approval_payment_method_type()
{
    if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }
    require_once plugin_dir_path(__FILE__) . 'class-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Dskapi_Payment_Gateway_Blocks);
        }
    );
}
