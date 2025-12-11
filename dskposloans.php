<?php
/**
 * Plugin Name: DSK Credit API
 * Plugin URI: 
 * Description: Кредитен калкулатор DSK Credit API
 * Version: 1.1.1
 * Author: Avalon Ltd
 * Author URI: http://avalonbg.com
 * Text Domain: dskposloans
 * Domain Path: /languages
 * Network: 
 * License: 
 */
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 **/
// Makes sure the plugin is defined before trying to use it
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}
 
if ( (is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    /** definitions */
    define( 'DSKAPI_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
    define( 'DSKAPI_INCLUDES_DIR', DSKAPI_PLUGIN_DIR . '/includes' );
    define( 'DSKAPI_IMAGES_URI', WP_CONTENT_URL . '/plugins/dskposloans/images' );
    define( 'DSKAPI_CSS_URI', WP_CONTENT_URL . '/plugins/dskposloans/css' );
    define( 'DSKAPI_JS_URI', WP_CONTENT_URL . '/plugins/dskposloans/js' );
    define( 'DSKAPI_LIVEURL', 'https://dsk.avalon-bg.eu' );
    define( 'DSKAPI_MAIL', 'home@avalonbg.com' );
    define( 'DSKAPI_VERSION', '1.1.1' );
    
    /** includes */
    require_once DSKAPI_INCLUDES_DIR . '/functions.php';
    require_once DSKAPI_INCLUDES_DIR . '/admin.php';
    
    /** add origins */
    add_filter( 'allowed_http_origins', 'dskapi_add_allowed_origins' );
    
    /** add order column dskapi status */
    add_filter( 'manage_edit-shop_order_columns', 'dskapi_add_order_column_status' );
    add_filter( 'manage_woocommerce_page_wc-orders_columns', 'dskapi_add_order_column_status_hpos' );
    /** add order column dskapi status values */
    add_action( 'manage_shop_order_posts_custom_column', 'dskapi_add_order_column_status_values', 2 );
    add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'dskapi_add_order_column_status_values_hpos', 10, 2 );
    
    /** add admin menu options page ###includes/admin.php### */
    add_action('admin_menu', 'dskapi_admin_actions');
    /** output buffer ###includes/functions.php### */
    add_action('init', 'dskapi_do_output_buffer');
    /** vizualize credit button ###includes/functions.php### */
    add_action('woocommerce_after_add_to_cart_button', 'dskpayment_button');
    
    /** reklama ###includes/functions.php### */
    add_action('wp_enqueue_scripts', 'dskapi_add_meta');
    add_action( 'loop_start', 'dskapi_reklama' );
    
    /** load plugin class */
    add_action('plugins_loaded', 'dskapi_load_class_plugin', 0);
    add_filter( 'woocommerce_payment_gateways', 'add_dskapi_gateway_class' );
    
    /** declare compatibility with cart_checkout_blocks feature */
    function dskapi_declare_cart_checkout_blocks_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }
    add_action('before_woocommerce_init', 'dskapi_declare_cart_checkout_blocks_compatibility');

    // hook the function to the 'woocommerce_blocks_loaded' action
    add_action( 'woocommerce_blocks_loaded', 'dskapi_register_order_approval_payment_method_type' );
    function dskapi_register_order_approval_payment_method_type() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'class-block.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new Dskapi_Payment_Gateway_Blocks );
            }
        );
    }
    
    /** update order api */
    add_action( 'wp_ajax_dskapi_updateorder', 'dskapi_updateorder' );
    add_action( 'wp_ajax_nopriv_dskapi_updateorder', 'dskapi_updateorder' );
    
}
