<?php

/**
 * DSK API Payment Gateway Blocks Integration
 *
 * Provides WooCommerce Blocks support for the DSK Payment Gateway.
 * Enables the payment method to work with the new block-based checkout.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class Dskapi_Payment_Gateway_Blocks
 *
 * Extends AbstractPaymentMethodType to integrate DSK payment gateway
 * with WooCommerce Blocks checkout.
 *
 * @since 1.0.0
 */
final class Dskapi_Payment_Gateway_Blocks extends AbstractPaymentMethodType
{
    /**
     * The payment gateway instance.
     *
     * @var Dskapi_Payment_Gateway
     */
    private $gateway;

    /**
     * The payment method name/identifier.
     *
     * @var string
     */
    protected $name = 'dskapipayment';

    /**
     * Initialize the payment method type.
     *
     * Loads gateway settings and creates a new gateway instance.
     *
     * @return void
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_dskapipayment_settings', []);
        $this->gateway = new Dskapi_Payment_Gateway();
    }

    /**
     * Check if the payment method is active and available.
     *
     * @return bool True if the gateway is available, false otherwise.
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Get the script handles for the payment method.
     *
     * Registers the JavaScript file required for the blocks integration
     * and sets up script translations.
     *
     * @return array Array of script handles.
     */
    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'dskapipayment-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('dskapipayment-blocks-integration');
        }

        return ['dskapipayment-blocks-integration'];
    }

    /**
     * Get payment method data to pass to the frontend.
     *
     * Returns an array of data that will be available to the
     * JavaScript payment method component.
     *
     * @return array Payment method data including title, description, and supports.
     */
    public function get_payment_method_data()
    {
        return [
            'title'             => $this->gateway->title,
            'descriptiondskapi' => $this->dskapi_get_payment_fields_html(),
            'supports'          => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }

    /**
     * Get the payment fields HTML.
     *
     * Captures the output of the gateway's payment_fields method
     * to include in the blocks checkout.
     *
     * @return string The payment fields HTML.
     */
    public function dskapi_get_payment_fields_html()
    {
        ob_start();
        $this->gateway->payment_fields(true);
        return ob_get_clean();
    }
}
