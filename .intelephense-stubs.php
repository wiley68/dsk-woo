<?php

/**
 * Intelephense stubs за WooCommerce и WordPress класове
 * Този файл НЕ се изпълнява - служи само за IDE автодовършване
 * 
 * @package DSK Credit API
 */

namespace {

// ============================================================================
// WordPress Core Functions
// ============================================================================

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option($option, $default = false)
    {
        return $default;
    }

    /**
     * @param string $option
     * @param mixed $value
     * @param string $autoload
     * @return bool
     */
    function update_option($option, $value, $autoload = null)
    {
        return false;
    }

    /**
     * @param string $option
     * @return bool
     */
    function delete_option($option)
    {
        return false;
    }

    /**
     * Removes a site option from the database.
     * 
     * @param string $option Name of the option to delete.
     * @return bool True if the option was deleted, false otherwise.
     */
    function delete_site_option($option)
    {
        return false;
    }

    /**
     * Clears the object cache of all data.
     * 
     * @return bool True on success, false on failure.
     */
    function wp_cache_flush()
    {
        return true;
    }

    /**
     * Adds a submenu page to the Settings main menu.
     * 
     * @param string $page_title The text to be displayed in the title tags.
     * @param string $menu_title The text to be used for the menu.
     * @param string $capability The capability required for access.
     * @param string $menu_slug The slug name to refer to this menu.
     * @param callable $callback The function to be called to output the page.
     * @param int $position The position in the menu order.
     * @return string|false The hook suffix, or false if user lacks capability.
     */
    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        return '';
    }

    /**
     * Modifies the database based on specified SQL statements.
     * 
     * @param string|array $queries SQL queries to run.
     * @param bool $execute Whether to execute the query. Default true.
     * @return array Strings containing the results of the queries.
     */
    function dbDelta($queries = '', $execute = true)
    {
        return [];
    }

    /**
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     * @param int $accepted_args
     * @return true
     */
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }

    /**
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     * @param int $accepted_args
     * @return true
     */
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }

    /**
     * @param string $tag
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function apply_filters($tag, $value, ...$args)
    {
        return $value;
    }

    /**
     * @param string $tag
     * @param mixed ...$args
     * @return void
     */
    function do_action($tag, ...$args) {}

    /**
     * @param int $post_id
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    function get_post_meta($post_id, $key = '', $single = false)
    {
        return $single ? '' : [];
    }

    /**
     * @param int $post_id
     * @param string $key
     * @param mixed $value
     * @param mixed $prev_value
     * @return int|bool
     */
    function update_post_meta($post_id, $key, $value, $prev_value = '')
    {
        return false;
    }

    /**
     * @param string $plugin
     * @return bool
     */
    function is_plugin_active_for_network($plugin)
    {
        return false;
    }

    /**
     * @param string $text
     * @return string
     */
    function esc_sql($text)
    {
        return $text;
    }

    /**
     * @param string $url
     * @return string
     */
    function esc_url($url)
    {
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    function esc_url_raw($url)
    {
        return $url;
    }

    /**
     * @param string $text
     * @return string
     */
    function esc_html($text)
    {
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    function esc_attr($text)
    {
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    function sanitize_text_field($text)
    {
        return $text;
    }

    /**
     * Converts a value to a non-negative integer.
     * 
     * @param mixed $maybeint Data to convert to a non-negative integer.
     * @return int A non-negative integer.
     */
    function absint($maybeint)
    {
        return abs((int) $maybeint);
    }

    /**
     * Retrieves the URL to the admin area.
     * 
     * @param string $path Optional path relative to the admin URL.
     * @param string $scheme The scheme to use. Default 'admin'.
     * @return string Admin URL.
     */
    function admin_url($path = '', $scheme = 'admin')
    {
        return '/wp-admin/' . ltrim($path, '/');
    }

    /**
     * Creates a cryptographic token tied to a specific action.
     * 
     * @param string|int $action The action name.
     * @return string The nonce token.
     */
    function wp_create_nonce($action = -1)
    {
        return '';
    }

    /**
     * Verifies that correct nonce was used.
     * 
     * @param string $nonce Nonce to verify.
     * @param string|int $action Action name.
     * @return false|int False if invalid, 1 if valid and generated 0-12 hours ago, 2 if generated 12-24 hours ago.
     */
    function wp_verify_nonce($nonce, $action = -1)
    {
        return 1;
    }

    /**
     * Send a JSON response with success status.
     * 
     * @param mixed $data Data to encode as JSON.
     * @param int $status_code HTTP status code. Default 200.
     * @return void
     */
    function wp_send_json_success($data = null, $status_code = 200) {}

    /**
     * Send a JSON response with error status.
     * 
     * @param mixed $data Data to encode as JSON.
     * @param int $status_code HTTP status code. Default 200.
     * @return void
     */
    function wp_send_json_error($data = null, $status_code = 200) {}

    /**
     * Gets file modification time.
     * 
     * @param string $filename Path to the file.
     * @return int|false Unix timestamp or false on failure.
     */
    function filemtime($filename)
    {
        return time();
    }

    /**
     * @param string $text
     * @return string
     */
    function wp_kses_post($text)
    {
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    function wpautop($text)
    {
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    function wptexturize($text)
    {
        return $text;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string|array $headers
     * @param string|array $attachments
     * @return bool
     */
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array())
    {
        return false;
    }

    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool|null $ver
     * @param bool $in_footer
     * @return bool
     */
    function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false)
    {
        return true;
    }

    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool|null $ver
     * @param string $media
     * @return bool
     */
    function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all')
    {
        return true;
    }

    /**
     * @param string $handle
     * @return void
     */
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {}

    /**
     * @param string $handle
     * @return void
     */
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {}

    /**
     * Localize a script.
     * 
     * @param string $handle Script handle.
     * @param string $object_name Name for the JavaScript object.
     * @param array $l10n Data to pass to the script.
     * @return bool True if data was added, false if not.
     */
    function wp_localize_script($handle, $object_name, $l10n)
    {
        return true;
    }

    /**
     * @param string $handle
     * @param string $domain
     * @param string $path
     * @return bool
     */
    function wp_set_script_translations($handle, $domain = 'default', $path = '')
    {
        return true;
    }

    /**
     * @param int $post_id
     * @param string $size
     * @return int
     */
    function get_post_thumbnail_id($post_id = null, $size = 'post-thumbnail')
    {
        return 0;
    }

    /**
     * @param int $attachment_id
     * @param string|array $size
     * @param bool $icon
     * @return array|false
     */
    function wp_get_attachment_image_src($attachment_id, $size = 'thumbnail', $icon = false)
    {
        return false;
    }

    /**
     * @param int $post_id
     * @param string $taxonomy
     * @param array $args
     * @return array|WP_Error
     */
    function wp_get_post_terms($post_id, $taxonomy, $args = array())
    {
        return [];
    }

    /**
     * @return bool
     */
    function is_front_page()
    {
        return false;
    }

    /**
     * @return bool
     */
    function is_product()
    {
        return false;
    }

    /**
     * @return bool
     */
    function is_checkout()
    {
        return false;
    }

    /**
     * @return bool
     */
    function is_cart()
    {
        return false;
    }

    /**
     * @return bool
     */
    function is_admin()
    {
        return false;
    }

    /**
     * @param string $file
     * @return string
     */
    function plugin_dir_path($file)
    {
        return '';
    }

    /**
     * @param string $file
     * @return string
     */
    function plugin_dir_url($file)
    {
        return '';
    }

    /**
     * @param string $path
     * @return string
     */
    function untrailingslashit($path)
    {
        return $path;
    }

    /**
     * @param string $path
     * @return string
     */
    function trailingslashit($path)
    {
        return $path . '/';
    }

    // ============================================================================
    // WordPress Constants
    // ============================================================================

    define('ABSPATH', '/');
    /** @var bool WordPress debug mode - value varies by environment */
    const WP_DEBUG = true;
    define('WP_CONTENT_URL', '');
    define('WP_CONTENT_DIR', '');
    define('WP_PLUGIN_DIR', '');
    define('WP_PLUGIN_URL', '');

    // ============================================================================
    // WordPress Classes
    // ============================================================================

    class WP_Error
    {
        public function __construct($code = '', $message = '', $data = '') {}
        public function get_error_code()
        {
            return '';
        }
        public function get_error_message($code = '')
        {
            return '';
        }
        public function get_error_data($code = '')
        {
            return null;
        }
        public function has_errors()
        {
            return false;
        }
    }

// ============================================================================
// WooCommerce Core
// ============================================================================

    /**
     * @return WooCommerce
     */
    function WC()
    {
        return new WooCommerce();
    }

    /**
     * @param int $order_id
     * @return WC_Order|false
     */
    function wc_get_order($order_id)
    {
        return false;
    }

    /**
     * @param int $product_id
     * @return WC_Product|null|false
     */
    function wc_get_product($product_id)
    {
        return false;
    }

    /**
     * @param WC_Product $product
     * @param array $args
     * @return float|string
     */
    function wc_get_price_including_tax($product, $args = array())
    {
        return 0.0;
    }

    /**
     * @param WC_Product $product
     * @param array $args
     * @return float|string
     */
    function wc_get_price_excluding_tax($product, $args = array())
    {
        return 0.0;
    }

    /**
     * @return string
     */
    function get_woocommerce_currency()
    {
        return 'BGN';
    }

    /**
     * @return string
     */
    function get_woocommerce_currency_symbol($currency = '')
    {
        return 'лв.';
    }

    /**
     * @return array
     */
    function wc_get_order_statuses()
    {
        return [];
    }

    /**
     * @param string $message
     * @param string $notice_type
     * @param array $data
     * @return void
     */
    function wc_add_notice($message, $notice_type = 'success', $data = array()) {}

    /**
     * @param float $price
     * @param array $args
     * @return string
     */
    function wc_price($price, $args = array())
    {
        return '';
    }

    /**
     * Returns the checkout URL.
     *
     * @return string Checkout page URL.
     */
    function wc_get_checkout_url()
    {
        return '/checkout/';
    }

    /**
     * Returns the cart URL.
     *
     * @return string Cart page URL.
     */
    function wc_get_cart_url()
    {
        return '/cart/';
    }

    /**
     * Clean variables using sanitize_text_field.
     * Arrays are cleaned recursively.
     *
     * @param string|array $var Data to sanitize.
     * @return string|array Sanitized data.
     */
    function wc_clean($var)
    {
        return is_array($var) ? array_map('wc_clean', $var) : sanitize_text_field($var);
    }

    /**
     * Remove slashes from a string or array of strings.
     *
     * @param string|array $value String or array of strings to unslash.
     * @return string|array Unslashed string or array.
     */
    function wp_unslash($value)
    {
        return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value);
    }

    /**
     * @param string $string
     * @param int $flags
     * @return string
     */
    function htmlspecialchars_decode($string, $flags = ENT_COMPAT)
    {
        return $string;
    }

    // ============================================================================
    // WooCommerce Main Class
    // ============================================================================

    class WooCommerce
    {
        /** @var string */
        public $version = '';
        /** @var WC_Cart */
        public $cart;
        /** @var WC_Customer */
        public $customer;
        /** @var WC_Session */
        public $session;
        /** @var WC_Query */
        public $query;
        /** @var WC_Checkout */
        public $checkout;
        /** @var WC_Countries */
        public $countries;
        /** @var WC_Mailer */
        public $mailer;
        /** @var WC_Payment_Gateways */
        public $payment_gateways;
        /** @var WC_Shipping */
        public $shipping;
    }

    class WC_Session
    {
        /**
         * Get a session variable.
         *
         * @param string $key Session key.
         * @param mixed $default Default value.
         * @return mixed Session value or default.
         */
        public function get($key, $default = null)
        {
            return $default;
        }

        /**
         * Set a session variable.
         *
         * @param string $key Session key.
         * @param mixed $value Session value.
         * @return void
         */
        public function set($key, $value) {}
    }

    class WC_Query {}
    class WC_Checkout {}
    class WC_Countries {}
    class WC_Mailer {}

    class WC_Payment_Gateways
    {
        /**
         * Get available payment gateways.
         *
         * @return array Array of WC_Payment_Gateway objects.
         */
        public function get_available_payment_gateways()
        {
            return [];
        }
    }
    class WC_Shipping {}

    // ============================================================================
    // WC_Payment_Gateway
    // ============================================================================

    class WC_Payment_Gateway extends WC_Settings_API
    {
        /** @var string */
        public $id = '';
        /** @var string */
        public $icon = '';
        /** @var bool */
        public $has_fields = false;
        /** @var string */
        public $method_title = '';
        /** @var string */
        public $method_description = '';
        /** @var string */
        public $title = '';
        /** @var string */
        public $description = '';
        /** @var string */
        public $enabled = 'no';
        /** @var array */
        public $supports = [];
        /** @var float */
        public $max_amount = 0;
        /** @var array */
        public $form_fields = [];
        /** @var array */
        public $settings = [];
        /** @var int */
        public $order_button_text = '';
        /** @var string */
        public $chosen = '';
        /** @var bool */
        public $availability = '';
        /** @var array */
        public $countries = [];

        public function __construct() {}
        public function init_form_fields() {}
        public function init_settings() {}
        public function get_option($key, $empty_value = null)
        {
            return '';
        }
        public function get_title()
        {
            return $this->title;
        }
        public function get_description()
        {
            return $this->description;
        }
        public function get_icon()
        {
            return $this->icon;
        }
        public function get_order_total()
        {
            return 0.0;
        }
        public function is_available()
        {
            return false;
        }
        public function needs_setup()
        {
            return false;
        }
        public function payment_fields() {}
        public function validate_fields()
        {
            return true;
        }
        public function process_payment($order_id)
        {
            return [];
        }
        public function process_admin_options()
        {
            return true;
        }
        public function supports($feature)
        {
            return false;
        }
        public function get_return_url($order = null)
        {
            return '';
        }
        public function process_refund($order_id, $amount = null, $reason = '')
        {
            return false;
        }
    }

    // ============================================================================
    // WC_Settings_API
    // ============================================================================

    class WC_Settings_API
    {
        /** @var string */
        public $plugin_id = '';
        /** @var string */
        public $id = '';
        /** @var array */
        public $form_fields = [];
        /** @var array */
        public $settings = [];

        public function init_form_fields() {}
        public function init_settings() {}
        public function get_option($key, $empty_value = null)
        {
            return '';
        }
        public function update_option($key, $value = '')
        {
            return true;
        }
        public function process_admin_options()
        {
            return true;
        }
        public function generate_settings_html($form_fields = array(), $echo = true)
        {
            return '';
        }
        public function get_field_key($key)
        {
            return '';
        }
    }

    // ============================================================================
    // WC_Order
    // ============================================================================

    class WC_Order extends WC_Abstract_Order
    {
        /** @var string */
        public $payment_method = '';

        public function __construct($order = 0) {}
        public function get_id()
        {
            return 0;
        }
        public function get_status()
        {
            return '';
        }
        public function update_status($new_status, $note = '', $manual_update = false)
        {
            return true;
        }
        public function get_billing_first_name()
        {
            return '';
        }
        public function get_billing_last_name()
        {
            return '';
        }
        public function get_billing_email()
        {
            return '';
        }
        public function get_billing_phone()
        {
            return '';
        }
        public function get_billing_address_1()
        {
            return '';
        }
        public function get_billing_address_2()
        {
            return '';
        }
        public function get_billing_city()
        {
            return '';
        }
        public function get_billing_state()
        {
            return '';
        }
        public function get_billing_postcode()
        {
            return '';
        }
        public function get_billing_country()
        {
            return '';
        }
        public function get_shipping_first_name()
        {
            return '';
        }
        public function get_shipping_last_name()
        {
            return '';
        }
        public function get_shipping_address_1()
        {
            return '';
        }
        public function get_shipping_address_2()
        {
            return '';
        }
        public function get_shipping_city()
        {
            return '';
        }
        public function get_shipping_state()
        {
            return '';
        }
        public function get_shipping_postcode()
        {
            return '';
        }
        public function get_shipping_country()
        {
            return '';
        }
        public function get_total()
        {
            return 0.0;
        }
        public function get_subtotal()
        {
            return 0.0;
        }
        public function get_currency()
        {
            return '';
        }
        public function get_payment_method()
        {
            return '';
        }
        public function get_payment_method_title()
        {
            return '';
        }
        public function get_items()
        {
            return [];
        }
        public function payment_complete($payment_transaction_id = '') {}
        public function add_order_note($note, $is_customer_note = 0, $added_by_user = false)
        {
            return 0;
        }
        public function has_status($status)
        {
            return false;
        }
        public function is_paid()
        {
            return false;
        }
        public function get_meta($key = '', $single = true, $context = 'view')
        {
            return null;
        }
        public function update_meta_data($key, $value, $id = 0) {}
        public function delete_meta_data($key) {}
        public function save()
        {
            return 0;
        }
    }

    // ============================================================================
    // WC_Abstract_Order
    // ============================================================================

    abstract class WC_Abstract_Order extends WC_Data {}

    // ============================================================================
    // WC_Data
    // ============================================================================

    abstract class WC_Data
    {
        public function get_id()
        {
            return 0;
        }
        public function get_meta($key = '', $single = true, $context = 'view')
        {
            return null;
        }
        public function update_meta_data($key, $value, $id = 0) {}
        public function add_meta_data($key, $value, $unique = false) {}
        public function delete_meta_data($key) {}
        public function save()
        {
            return 0;
        }
    }

    // ============================================================================
    // WC_Product
    // ============================================================================

    class WC_Product extends WC_Data
    {
        /** @var int */
        public $id = 0;
        /** @var string */
        public $name = '';

        public function __construct($product = 0) {}
        public function get_id()
        {
            return 0;
        }
        public function get_name()
        {
            return '';
        }
        public function get_title()
        {
            return '';
        }
        public function get_slug()
        {
            return '';
        }
        public function get_type()
        {
            return '';
        }
        public function get_status()
        {
            return '';
        }
        public function get_description()
        {
            return '';
        }
        public function get_short_description()
        {
            return '';
        }
        public function get_sku()
        {
            return '';
        }
        public function get_price($context = 'view')
        {
            return '';
        }
        public function get_regular_price($context = 'view')
        {
            return '';
        }
        public function get_sale_price($context = 'view')
        {
            return '';
        }
        public function is_on_sale()
        {
            return false;
        }
        public function is_in_stock()
        {
            return false;
        }
        public function is_virtual()
        {
            return false;
        }
        public function is_downloadable()
        {
            return false;
        }
        public function is_visible()
        {
            return false;
        }
        public function is_purchasable()
        {
            return false;
        }
        public function get_stock_quantity()
        {
            return 0;
        }
        public function get_stock_status()
        {
            return '';
        }
        public function get_weight()
        {
            return 0.0;
        }
        public function get_length()
        {
            return 0.0;
        }
        public function get_width()
        {
            return 0.0;
        }
        public function get_height()
        {
            return 0.0;
        }
        public function get_image_id()
        {
            return 0;
        }
        public function get_gallery_image_ids()
        {
            return [];
        }
        public function get_category_ids()
        {
            return [];
        }
        public function get_tag_ids()
        {
            return [];
        }
        public function get_permalink()
        {
            return '';
        }
        public function get_image($size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true)
        {
            return '';
        }
    }

    // ============================================================================
    // WC_Cart
    // ============================================================================

    class WC_Cart
    {
        /** @var float */
        public $total = 0.0;
        /** @var float */
        public $subtotal = 0.0;
        /** @var float */
        public $subtotal_ex_tax = 0.0;
        /** @var float */
        public $tax_total = 0.0;
        /** @var float */
        public $discount_cart = 0.0;
        /** @var float */
        public $discount_cart_tax = 0.0;
        /** @var float */
        public $shipping_total = 0.0;
        /** @var float */
        public $shipping_tax_total = 0.0;
        /** @var float */
        public $fee_total = 0.0;

        public function get_cart()
        {
            return [];
        }
        public function get_cart_contents()
        {
            return [];
        }
        public function get_cart_contents_count()
        {
            return 0;
        }
        public function get_cart_contents_total()
        {
            return 0.0;
        }
        public function get_total($context = 'view')
        {
            return 0.0;
        }
        public function get_subtotal()
        {
            return 0.0;
        }
        public function get_subtotal_tax()
        {
            return 0.0;
        }
        public function get_discount_total()
        {
            return 0.0;
        }
        public function get_discount_tax()
        {
            return 0.0;
        }
        public function get_shipping_total()
        {
            return 0.0;
        }
        public function get_shipping_tax()
        {
            return 0.0;
        }
        public function is_empty()
        {
            return true;
        }
        public function empty_cart($clear_persistent_cart = true) {}
        public function add_to_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array())
        {
            return '';
        }
        public function set_quantity($cart_item_key, $quantity = 1, $refresh_totals = true)
        {
            return true;
        }
        public function remove_cart_item($cart_item_key)
        {
            return true;
        }
        public function calculate_totals() {}
        public function apply_coupon($coupon_code)
        {
            return true;
        }
        public function remove_coupon($coupon_code)
        {
            return true;
        }
        public function get_applied_coupons()
        {
            return [];
        }
        public function needs_shipping()
        {
            return false;
        }
        public function needs_payment()
        {
            return false;
        }
    }

    // ============================================================================
    // WC_Customer
    // ============================================================================

    class WC_Customer extends WC_Data
    {
        public function get_id()
        {
            return 0;
        }
        public function get_email()
        {
            return '';
        }
        public function get_first_name()
        {
            return '';
        }
        public function get_last_name()
        {
            return '';
        }
        public function get_billing_first_name()
        {
            return '';
        }
        public function get_billing_last_name()
        {
            return '';
        }
        public function get_billing_email()
        {
            return '';
        }
        public function get_billing_phone()
        {
            return '';
        }
        public function get_billing_address_1()
        {
            return '';
        }
        public function get_billing_address_2()
        {
            return '';
        }
        public function get_billing_city()
        {
            return '';
        }
        public function get_billing_state()
        {
            return '';
        }
        public function get_billing_postcode()
        {
            return '';
        }
        public function get_billing_country()
        {
            return '';
        }
        public function get_shipping_first_name()
        {
            return '';
        }
        public function get_shipping_last_name()
        {
            return '';
        }
        public function get_shipping_address_1()
        {
            return '';
        }
        public function get_shipping_address_2()
        {
            return '';
        }
        public function get_shipping_city()
        {
            return '';
        }
        public function get_shipping_state()
        {
            return '';
        }
        public function get_shipping_postcode()
        {
            return '';
        }
        public function get_shipping_country()
        {
            return '';
        }
    }
}

// ============================================================================
// WooCommerce Blocks - Namespaced Classes
// ============================================================================

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {
    abstract class AbstractPaymentMethodType
    {
        /** @var string */
        protected $name = '';
        /** @var array */
        protected $settings = [];

        abstract public function initialize();
        abstract public function is_active();
        abstract public function get_payment_method_script_handles();
        abstract public function get_payment_method_data();

        public function get_name()
        {
            return $this->name;
        }
        protected function get_setting($name, $default = '')
        {
            return $default;
        }
    }
}

namespace Automattic\WooCommerce\Blocks\Payments {
    class PaymentMethodRegistry
    {
        public function register(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType $payment_method) {}
        public function get_registered($name)
        {
            return null;
        }
        public function get_all_registered()
        {
            return [];
        }
        public function is_registered($name)
        {
            return false;
        }
    }
}

namespace Automattic\WooCommerce\Utilities {
    class FeaturesUtil
    {
        public static function declare_compatibility($feature_id, $plugin_file, $positive_compatibility = true) {}
        public static function feature_is_enabled($feature_id)
        {
            return false;
        }
        public static function is_compatible($feature_id, $plugin_file)
        {
            return false;
        }
    }
}
