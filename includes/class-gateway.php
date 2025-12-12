<?php

/**
 * DSK API Payment Gateway Class
 *
 * Extends WooCommerce WC_Payment_Gateway to provide DSK Bank
 * credit payment functionality. Handles:
 * - Gateway configuration and settings
 * - Payment availability checks
 * - Payment processing and order creation
 * - Checkout page payment fields
 * - Thank you page and email instructions
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dskapi_Payment_Gateway
 *
 * WooCommerce payment gateway for DSK Bank credit purchases.
 *
 * @since 1.0.0
 * @extends WC_Payment_Gateway
 */
class Dskapi_Payment_Gateway extends WC_Payment_Gateway
{
    /**
     * Gateway domain/text domain.
     *
     * @var string
     */
    public $domain;

    /**
     * Payment instructions text.
     *
     * @var string
     */
    public $instructions;

    /**
     * Order status after payment.
     *
     * @var string
     */
    public $order_status;

    /**
     * Constructor for the gateway.
     *
     * Sets up gateway properties, loads settings, and registers hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->domain = 'dskapipayment';
        $this->id = 'dskapipayment';
        $this->icon = apply_filters('woocommerce_custom_gateway_icon', '');
        $this->has_fields = false;
        $this->method_title = 'Банка ДСК покупки на Кредит';
        $this->method_description = 'Дава възможност на Вашите клиенти да закупуват стока на изплащане с Банка ДСК покупки на Кредит';

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->order_status = $this->get_option('order_status', 'completed');

        // Actions - save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Thank you page hook
        add_action('woocommerce_thankyou_dskapipayment', array($this, 'thankyou_dskapipayment_page'));

        // Customer email instructions
        add_action('woocommerce_email_before_order_table', array($this, 'email_dskapipayment_instructions'), 10, 3);
    }

    /**
     * Initialize gateway settings form fields.
     *
     * Defines the configuration fields shown in WooCommerce > Settings > Payments.
     *
     * @since 1.0.0
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Разреши/Забрани',
                'type'    => 'checkbox',
                'label'   => 'Разреши Банка ДСК покупки на Кредит',
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => 'Заглавие',
                'type'        => 'text',
                'description' => 'Показва това заглавие при избор на метод на плащане Банка ДСК покупки на Кредит.',
                'default'     => 'Банка ДСК',
                'desc_tip'    => true,
            ),
            'order_status' => array(
                'title'       => 'Състояние на поръчката',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => 'Какво да бъде състоянието на поръчката след като платите с този метод.',
                'default'     => 'wc-pending',
                'desc_tip'    => true,
                'options'     => wc_get_order_statuses()
            ),
            'description' => array(
                'title'       => 'Описание',
                'type'        => 'textarea',
                'description' => 'Описание на метода за плащане.',
                'default'     => 'С избора си да финансирате покупката чрез Банка ДСК Вие декларирате, че сте запознат с Информацията относно обработването на лични данни на физически лица от Банка ДСК АД.',
                'desc_tip'    => true,
            ),
            'instructions' => array(
                'title'       => 'Инструкции',
                'type'        => 'textarea',
                'description' => 'Показва тази инструкция при избор на метод на плащане Банка ДСК покупки на Кредит.',
                'default'     => 'Можеш да закупиш избрания продукт на изплащане! Можеш да купуваш стоки от 150.00 лв. до 10000.00 лв. Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Check if the gateway is available for use.
     *
     * Validates multiple conditions:
     * - Gateway is enabled
     * - Order total is within allowed range
     * - Currency is EUR or BGN
     * - Plugin is enabled
     * - DSK API returns valid status
     *
     * @since 1.0.0
     * @return bool True if gateway is available, false otherwise.
     */
    public function is_available()
    {
        $is_available = ('yes' === $this->enabled);

        // Check max amount limit
        if (WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total()) {
            $is_available = false;
        }

        // Check currency - only EUR and BGN supported
        $dskapi_currency_code = get_woocommerce_currency();
        if ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN') {
            $is_available = false;
        }

        // Check plugin status
        $dskapi_cid = (string)get_option("dskapi_cid");
        $dskapi_status = (string)get_option("dskapi_status");
        if ($dskapi_status != "on") {
            $is_available = false;
        }

        // Get min/max limits from API
        $paramsdskapi = Dskapi_Client::get_minmax($dskapi_cid);

        if (empty($paramsdskapi)) {
            return false;
        }

        // Apply currency conversion to order total
        $dskapi_eur = (int)$paramsdskapi['dsk_eur'];
        if (WC()->cart) {
            $dsk_order_total = $this->get_order_total();
            switch ($dskapi_eur) {
                case 0:
                    // No conversion
                    break;
                case 1:
                    // EUR to BGN
                    if ($dskapi_currency_code == "EUR") {
                        $dsk_order_total = $dsk_order_total * 1.95583;
                    }
                    break;
                case 2:
                    // BGN to EUR
                    if ($dskapi_currency_code == "BGN") {
                        $dsk_order_total = $dsk_order_total / 1.95583;
                    }
                    break;
            }
        }

        // Extract limits from API response
        $dskapi_minstojnost = (float)$paramsdskapi['dsk_minstojnost'];
        $dskapi_maxstojnost = (float)$paramsdskapi['dsk_maxstojnost'];
        $dskapi_min_000 = (float)$paramsdskapi['dsk_min_000'];
        $dskapi_status_cp = $paramsdskapi['dsk_status'];

        // Adjust minimum for 0% interest with <= 6 installments
        $dskapi_purcent = (float)$paramsdskapi['dsk_purcent'];
        $dskapi_vnoski_default = (int)$paramsdskapi['dsk_vnoski_default'];
        if (($dskapi_purcent == 0) && ($dskapi_vnoski_default <= 6)) {
            $dskapi_minstojnost = $dskapi_min_000;
        }

        // Validate order total against limits
        if (WC()->cart) {
            if ($dsk_order_total > 0) {
                if (($dskapi_status_cp != 1) ||
                    ($dsk_order_total < $dskapi_minstojnost) ||
                    ($dsk_order_total > $dskapi_maxstojnost)
                ) {
                    $is_available = false;
                }
            }
        }

        return $is_available;
    }

    /**
     * Output the thank you page content.
     *
     * Displays instructions after successful order placement.
     *
     * @since 1.0.0
     * @return void
     */
    public function thankyou_dskapipayment_page()
    {
        if ($this->instructions)
            echo wpautop(wptexturize($this->instructions));
    }

    /**
     * Add payment instructions to customer emails.
     *
     * @since 1.0.0
     * @param WC_Order $order      Order object.
     * @param bool     $sent_to_admin Whether email is sent to admin.
     * @param bool     $plain_text    Whether email is plain text.
     * @return void
     */
    public function email_dskapipayment_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if ($this->instructions && !$sent_to_admin && 'dskapi' === $order->payment_method && $order->has_status('on-hold')) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }

    /**
     * Output the payment fields on checkout page.
     *
     * Displays:
     * - Gateway description
     * - GDPR information link
     * - Interest rates popup with installment calculator
     *
     * @since 1.0.0
     * @return void
     */
    public function payment_fields()
    {
        // Output description
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }

        global $woocommerce;
        $dskapi_price_original = $woocommerce->cart->total;
        $dskapi_price = $dskapi_price_original;
        $dskapi_product_id = (int)Dskapi_Client::get_cart_product_id();
        $dskapi_cid = (string)get_option("dskapi_cid");

        // Get currency and EUR settings
        $dskapi_currency_code = get_woocommerce_currency();
        $paramsdskapieur = Dskapi_Client::get_eur($dskapi_cid);
        $dskapi_sign = 'лв.';

        // Apply currency conversion
        if (!empty($paramsdskapieur)) {
            $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
            switch ($dskapi_eur) {
                case 0:
                    // No conversion
                    if ($dskapi_currency_code == 'EUR') {
                        $dskapi_sign = 'евро';
                    }
                    break;
                case 1:
                    // EUR to BGN
                    if ($dskapi_currency_code == "EUR") {
                        $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
                    }
                    $dskapi_sign = 'лв.';
                    break;
                case 2:
                    // BGN to EUR
                    if ($dskapi_currency_code == "BGN") {
                        $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
                    }
                    $dskapi_sign = 'евро';
                    break;
            }
        }

        // Get product data from API
        $paramsdskapi = Dskapi_Client::get_product($dskapi_price, $dskapi_product_id, $dskapi_cid);

        // Parse installment visibility
        $dskapi_vnoski_visible_arr = [];
        $dskapi_vnoski = 12;
        $dskapi_vnoska = 0;
        $dskapi_gpr = 0;

        if (!empty($paramsdskapi)) {
            $dskapi_vnoski = (int)$paramsdskapi['dsk_vnoski_default'];
            $dskapi_vnoska = (float)$paramsdskapi['dsk_vnoska'];
            $dskapi_gpr = (float)$paramsdskapi['dsk_gpr'];
            $dskapi_vnoski_visible = (int)$paramsdskapi['dsk_vnoski_visible'];
            $dskapi_vnoski_visible_arr = Dskapi_Client::parse_installment_visibility($dskapi_vnoski_visible, $dskapi_vnoski);
        }

        // Determine mobile/desktop CSS classes
        $dskapi_is_mobile = Dskapi_Client::is_mobile();
        if ($dskapi_is_mobile) {
            $dskapi_PopUp_Detailed_v1 = "dskapim_PopUp_Detailed_v1";
            $dskapi_Mask = "dskapim_Mask";
            $dskapi_picture = !empty($paramsdskapi) ? DSKAPI_LIVEURL . '/calculators/assets/img/dskm' . $paramsdskapi['dsk_reklama'] . '.png' : '';
            $dskapi_product_name = "dskapim_product_name";
            $dskapi_body_panel_txt3 = "dskapim_body_panel_txt3";
            $dskapi_body_panel_txt4 = "dskapim_body_panel_txt4";
            $dskapi_body_panel_txt3_left = "dskapim_body_panel_txt3_left";
            $dskapi_body_panel_txt3_right = "dskapim_body_panel_txt3_right";
            $dskapi_sumi_panel = "dskapim_sumi_panel";
            $dskapi_kredit_panel = "dskapim_kredit_panel";
            $dskapi_body_panel_footer = "dskapim_body_panel_footer";
            $dskapi_body_panel_left = "dskapim_body_panel_left";
        } else {
            $dskapi_PopUp_Detailed_v1 = "dskapi_PopUp_Detailed_v1";
            $dskapi_Mask = "dskapi_Mask";
            $dskapi_picture = !empty($paramsdskapi) ? DSKAPI_LIVEURL . '/calculators/assets/img/dsk' . $paramsdskapi['dsk_reklama'] . '.png' : '';
            $dskapi_product_name = "dskapi_product_name";
            $dskapi_body_panel_txt3 = "dskapi_body_panel_txt3";
            $dskapi_body_panel_txt4 = "dskapi_body_panel_txt4";
            $dskapi_body_panel_txt3_left = "dskapi_body_panel_txt3_left";
            $dskapi_body_panel_txt3_right = "dskapi_body_panel_txt3_right";
            $dskapi_sumi_panel = "dskapi_sumi_panel";
            $dskapi_kredit_panel = "dskapi_kredit_panel";
            $dskapi_body_panel_footer = "dskapi_body_panel_footer";
            $dskapi_body_panel_left = "dskapi_body_panel_left";
        }

?>
        <!-- Hidden price field for checkout processing -->
        <input type="hidden" name="dskapi_price" id="dskapi_price" value="<?php echo esc_attr($dskapi_price_original); ?>" />

        <!-- GDPR Information Link -->
        <a target="_blank" href="https://dskbank.bg/docs/default-source/gdpr/%D0%B8%D0%BD%D1%84%D0%BE%D1%80%D0%BC%D0%B0%D1%86%D0%B8%D1%8F-%D0%BE%D1%82%D0%BD%D0%BE%D1%81%D0%BD%D0%BE-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5%D1%82%D0%BE-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8-%D0%BD%D0%B0-%D1%84%D0%B8%D0%B7%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%B8-%D0%BB%D0%B8%D1%86%D0%B0-%D0%BE%D1%82-%D0%B1%D0%B0%D0%BD%D0%BA%D0%B0-%D0%B4%D1%81%D0%BA-%D0%B0%D0%B4-%D0%B8-%D1%81%D1%8A%D0%B3%D0%BB%D0%B0%D1%81%D0%B8%D1%8F-%D0%B7%D0%B0-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8.pdf">Информация относно обработването на лични данни на физически лица от 'Банка ДСК' АД</a>
        <br style="clear:both;" />
        <br style="clear:both;" />

        <!-- Interest Rates Link -->
        <a href="#" id="dskapi_checkout_interest_rates_link" style="cursor: pointer;">
            Лихвени схеми
        </a>
        <br style="clear:both;" />

        <!-- Hidden fields for JavaScript -->
        <input type="hidden" id="dskapi_checkout_price_txt_hidden" value="<?php echo esc_attr(number_format((float)$dskapi_price, 2, '.', '')); ?>" />
        <input type="hidden" id="dskapi_checkout_cid" value="<?php echo esc_attr($dskapi_cid); ?>" />
        <input type="hidden" id="dskapi_checkout_product_id" value="<?php echo esc_attr($dskapi_product_id); ?>" />
        <input type="hidden" id="DSKAPI_CHECKOUT_LIVEURL" value="<?php echo esc_attr(DSKAPI_LIVEURL); ?>" />

        <!-- Interest Rates Popup Modal -->
        <div id="dskapi-checkout-popup-container" class="modalpayment_dskapi" style="display:none;">
            <div class="modalpayment-content_dskapi">
                <div id="dskapi_body">
                    <div class="<?php echo esc_attr($dskapi_PopUp_Detailed_v1); ?>">
                        <div class="<?php echo esc_attr($dskapi_Mask); ?>">
                            <?php if (!empty($dskapi_picture)) : ?>
                                <img src="<?php echo esc_url($dskapi_picture); ?>" class="dskapi_header">
                            <?php endif; ?>
                            <p class="<?php echo esc_attr($dskapi_product_name); ?>">Купи на изплащане със стоков кредит от Банка ДСК</p>

                            <div class="<?php echo esc_attr($dskapi_body_panel_txt3); ?>">
                                <!-- Left Panel - Benefits -->
                                <div class="<?php echo esc_attr($dskapi_body_panel_txt3_left); ?>">
                                    <p>
                                        • Улеснена процедура за електронно подписване<br />
                                        • Атрактивни условия по кредита<br />
                                        • Параметри изцяло по Ваш избор<br />
                                        • Одобрение до няколко минути изцяло онлайн
                                    </p>
                                </div>

                                <!-- Right Panel - Calculator -->
                                <div class="<?php echo esc_attr($dskapi_body_panel_txt3_right); ?>">
                                    <!-- Installment Select -->
                                    <select id="dskapi_checkout_vnoski_input" class="dskapi_txt_right" onchange="dskapi_checkout_vnoski_input_change();" onfocus="dskapi_checkout_vnoski_input_focus(this.value);">
                                        <?php for ($i = 3; $i <= 48; $i++) : ?>
                                            <?php if (!empty($dskapi_vnoski_visible_arr[$i])) : ?>
                                                <option value="<?php echo $i; ?>" <?php if ($dskapi_vnoski == $i) echo 'selected'; ?>><?php echo $i; ?> месеца</option>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </select>

                                    <!-- Credit Amount and Monthly Payment -->
                                    <div class="<?php echo esc_attr($dskapi_sumi_panel); ?>">
                                        <div class="<?php echo esc_attr($dskapi_kredit_panel); ?>">
                                            <div class="dskapi_sumi_txt">Размер на кредита /<?php echo esc_html($dskapi_sign); ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_checkout_price_txt" readonly="readonly" value="<?php echo esc_attr(number_format((float)$dskapi_price, 2, ".", "")); ?>" />
                                            </div>
                                        </div>
                                        <div class="<?php echo esc_attr($dskapi_kredit_panel); ?>">
                                            <div class="dskapi_sumi_txt">Месечна вноска /<?php echo esc_html($dskapi_sign); ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_checkout_vnoska" readonly="readonly" value="<?php echo esc_attr(number_format($dskapi_vnoska, 2, ".", "")); ?>" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Amount and APR -->
                                    <div class="<?php echo esc_attr($dskapi_sumi_panel); ?>">
                                        <div class="<?php echo esc_attr($dskapi_kredit_panel); ?>">
                                            <div class="dskapi_sumi_txt">Обща дължима сума /<?php echo esc_html($dskapi_sign); ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_checkout_obshtozaplashtane" readonly="readonly" value="<?php echo esc_attr(number_format($dskapi_vnoska * $dskapi_vnoski, 2, ".", "")); ?>" />
                                            </div>
                                        </div>
                                        <div class="<?php echo esc_attr($dskapi_kredit_panel); ?>">
                                            <div class="dskapi_sumi_txt">ГПР /%/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_checkout_gpr" readonly="readonly" value="<?php echo esc_attr(number_format($dskapi_gpr, 2, ".", "")); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Disclaimer -->
                            <div class="<?php echo esc_attr($dskapi_body_panel_txt4); ?>">
                                Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.
                            </div>

                            <!-- Footer with Close Button -->
                            <div class="<?php echo esc_attr($dskapi_body_panel_footer); ?>">
                                <div class="dskapi_btn_cancel" id="dskapi_checkout_close_popup">Затвори</div>
                                <div class="<?php echo esc_attr($dskapi_body_panel_left); ?>">
                                    <div class="dskapi_txt_footer">Ver. <?php echo esc_html(DSKAPI_VERSION); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Validate payment fields.
     *
     * @since 1.0.0
     * @return bool Always returns true as no validation required.
     */
    public function validate_fields()
    {
        return true;
    }

    /**
     * Process the payment and return the result.
     *
     * Handles:
     * - Extracting billing/shipping data from order
     * - Preparing cart products data
     * - Encrypting data with RSA public key
     * - Sending order to DSK Bank API
     * - Creating local order record
     * - Redirecting to bank application page
     *
     * @since 1.0.0
     * @param int $order_id WooCommerce order ID.
     * @return array|void Array with result and redirect URL, or void on error.
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->payment_complete();

        // Get billing first name
        if (isset($_POST['billing_first_name'])) {
            $dskapi_fname = trim($_POST['billing_first_name'], " ");
        } else {
            $dskapi_fname = $order->get_billing_first_name() ? $order->get_billing_first_name() : '';
        }

        // Get billing last name
        if (isset($_POST['billing_last_name'])) {
            $dskapi_lastname = trim($_POST['billing_last_name'], " ");
        } else {
            $dskapi_lastname = $order->get_billing_last_name() ? $order->get_billing_last_name() : '';
        }

        // Get billing phone
        if (isset($_POST['billing_phone'])) {
            $dskapi_phone = $_POST['billing_phone'];
        } else {
            $dskapi_phone = $order->get_billing_phone() ? $order->get_billing_phone() : '';
        }

        // Get billing email
        if (isset($_POST['billing_email'])) {
            $dskapi_email = $_POST['billing_email'];
        } else {
            $dskapi_email = $order->get_billing_email() ? $order->get_billing_email() : '';
        }

        // Get price from form
        if (isset($_POST['dskapi_price'])) {
            $dskapi_price = floatval($_POST['dskapi_price']);
        } else {
            $dskapi_price = 0.00;
        }

        // Get billing city
        if (isset($_POST['billing_city'])) {
            $dskapi_billing_city = $_POST['billing_city'];
        } else {
            $dskapi_billing_city = $order->get_billing_city() ? $order->get_billing_city() : '';
        }

        // Get billing address
        if (isset($_POST['billing_address_1'])) {
            $dskapi_billing_address_1 = $_POST['billing_address_1'];
        } else {
            $dskapi_billing_address_1 = $order->get_billing_address_1() ? $order->get_billing_address_1() : '';
        }

        // Get billing postcode
        if (isset($_POST['billing_postcode'])) {
            $dskapi_billing_postcode = $_POST['billing_postcode'];
        } else {
            $dskapi_billing_postcode = $order->get_billing_postcode() ? $order->get_billing_postcode() : '';
        }

        // Get shipping city
        if (isset($_POST['shipping_city'])) {
            $dskapi_shipping_city = $_POST['shipping_city'];
        } else {
            $dskapi_shipping_city = $order->get_shipping_city() ? $order->get_shipping_city() : '';
        }

        // Get shipping address
        if (isset($_POST['shipping_address_1'])) {
            $dskapi_shipping_address_1 = $_POST['shipping_address_1'];
        } else {
            $dskapi_shipping_address_1 = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : '';
        }

        global $woocommerce;
        $dskapi_total = (float)$woocommerce->cart->total;

        if ($order_id != 0) {
            // Proceed to DSK API process
            $dskapi_cid = (string)get_option("dskapi_cid");

            // Get EUR conversion settings
            $dskapi_eur = 0;
            $paramsdskapieur = Dskapi_Client::get_eur($dskapi_cid);

            $dskapi_currency_code = get_woocommerce_currency();
            $dskapi_currency_code_send = 0;

            // Apply currency conversion
            if ($paramsdskapieur != null) {
                $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
                switch ($dskapi_eur) {
                    case 0:
                        $dskapi_currency_code_send = 0;
                        break;
                    case 1:
                        // EUR to BGN
                        $dskapi_currency_code_send = 0;
                        if ($dskapi_currency_code == "EUR") {
                            $dskapi_total = number_format($dskapi_total * 1.95583, 2, ".", "");
                        }
                        break;
                    case 2:
                        // BGN to EUR
                        $dskapi_currency_code_send = 1;
                        if ($dskapi_currency_code == "BGN") {
                            $dskapi_total = number_format($dskapi_total / 1.95583, 2, ".", "");
                        }
                        break;
                }
            }

            // Build product data strings
            $products_id = '';
            $products_name = '';
            $products_q = '';
            $products_p = '';
            $products_c = '';
            $products_m = '';
            $products_i = '';

            // Loop through cart items
            foreach ($woocommerce->cart->get_cart() as $cart_item) {
                $item = $cart_item['data'];
                if (!empty($item)) {
                    $dsk_product = wc_get_product($item->get_id());
                    $dskapi_price_cart = (float)wc_get_price_including_tax($dsk_product);

                    // Append product ID
                    $products_id .= $cart_item['product_id'];
                    $products_id .= '_';

                    // Append quantity
                    $products_q .= $cart_item['quantity'];
                    $products_q .= '_';

                    // Calculate and append price with currency conversion
                    $products_p_temp = $dskapi_price_cart;
                    switch ($dskapi_eur) {
                        case 0:
                            break;
                        case 1:
                            if ($dskapi_currency_code == "EUR") {
                                $products_p_temp = number_format($products_p_temp * 1.95583, 2, ".", "");
                            }
                            break;
                        case 2:
                            if ($dskapi_currency_code == "BGN") {
                                $products_p_temp = number_format($products_p_temp / 1.95583, 2, ".", "");
                            }
                            break;
                    }
                    $products_p .= $products_p_temp;
                    $products_p .= '_';

                    // Append product name (sanitized)
                    $products_name .= str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($cart_item['data']->get_title(), ENT_QUOTES)));
                    $products_name .= '_';

                    // Append category ID
                    $term_list = wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields' => 'ids'));
                    $products_c .= $term_list[0];
                    $products_c .= '_';

                    // Append base64 encoded image URL
                    $dskapi_image = wp_get_attachment_image_src(get_post_thumbnail_id($cart_item['product_id']), 'single-post-thumbnail');
                    $dskapi_imagePath = isset($dskapi_image[0]) ? $dskapi_image[0] : '';
                    $dskapi_imagePath_64 = base64_encode($dskapi_imagePath);
                    $products_i .= $dskapi_imagePath_64;
                    $products_i .= '_';
                }
            }

            // Clean trailing underscores
            $products_id = trim($products_id, "_");
            $products_q = trim($products_q, "_");
            $products_p = trim($products_p, "_");
            $products_c = trim($products_c, "_");
            $products_m = trim($products_m, "_");
            $products_name = trim($products_name, "_");
            $products_i = trim($products_i, "_");

            // Build order data array
            $dskapi_post = [
                'unicid' => $dskapi_cid,
                'first_name' => $dskapi_fname,
                'last_name' => $dskapi_lastname,
                'phone' => $dskapi_phone,
                'email' => $dskapi_email,
                'address2' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_billing_address_1, ENT_QUOTES))),
                'address2city' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_billing_city, ENT_QUOTES))),
                'postcode' => $dskapi_billing_postcode,
                'price' => $dskapi_total,
                'address' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_shipping_address_1, ENT_QUOTES))),
                'addresscity' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_shipping_city, ENT_QUOTES))),
                'products_id' => $products_id,
                'products_name' => $products_name,
                'products_q' => $products_q,
                'type_client' => Dskapi_Client::is_mobile() ? 1 : 0,
                'products_p' => $products_p,
                'version' => DSKAPI_VERSION,
                'shoporder_id' => $order_id,
                'products_c' => $products_c,
                'products_m' => $products_m,
                'products_i' => $products_i,
                'currency' => $dskapi_currency_code_send
            ];

            // Encrypt data with RSA public key
            $dskapi_plaintext = json_encode($dskapi_post);
            $dskapi_publicKey = openssl_pkey_get_public(file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/pub.pem'));
            $dskapi_a_key = openssl_pkey_get_details($dskapi_publicKey);
            $dskapi_chunkSize = ceil($dskapi_a_key['bits'] / 8) - 11;
            $dskapi_output = '';

            // Encrypt in chunks
            while ($dskapi_plaintext) {
                $dskapi_chunk = substr($dskapi_plaintext, 0, $dskapi_chunkSize);
                $dskapi_plaintext = substr($dskapi_plaintext, $dskapi_chunkSize);
                $dskapi_encrypted = '';
                if (!openssl_public_encrypt($dskapi_chunk, $dskapi_encrypted, $dskapi_publicKey)) {
                    die('Failed to encrypt data');
                }
                $dskapi_output .= $dskapi_encrypted;
            }

            // Free key resource (PHP < 8.0)
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                openssl_free_key($dskapi_publicKey);
            }

            // Base64 encode encrypted data
            $dskapi_output64 = base64_encode($dskapi_output);

            // Send order to DSK Bank API
            $paramsdskapiadd = Dskapi_Client::add_order(['data' => $dskapi_output64]);

            // Handle API response
            if ((!empty($paramsdskapiadd)) && isset($paramsdskapiadd['order_id']) && ($paramsdskapiadd['order_id'] != 0)) {
                // Success - save order to database
                Dskapi_Orders::create($order_id, 0);

                // Empty cart and redirect to bank application
                WC()->cart->empty_cart();

                if (Dskapi_Client::is_mobile()) {
                    return array(
                        'result'    => 'success',
                        'redirect'  => esc_url_raw(DSKAPI_LIVEURL . '/applicationm_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid)
                    );
                } else {
                    return array(
                        'result'    => 'success',
                        'redirect'  => esc_url_raw(DSKAPI_LIVEURL . '/application_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid)
                    );
                }
            } else {
                // Handle API failure
                if (empty($paramsdskapiadd)) {
                    // Communication error - send email to bank
                    Dskapi_Orders::create($order_id, 0);

                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/plain; charset=UTF-8;' . "\r\n";
                    wp_mail(DSKAPI_MAIL, 'Проблем комуникация заявка КП DSK Credit', json_encode($dskapi_post, JSON_PRETTY_PRINT), $headers);

                    wc_add_notice('Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.', 'error');
                    return;
                } else {
                    // Duplicate order error
                    wc_add_notice('Вече има създадена заявка за кредит в системата на DSK Credit с номер на Вашия ордер: ' . $order_id, 'error');
                    return;
                }
            }
        }
    }
}
