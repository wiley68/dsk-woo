<?php

/** load plugin class */
function dskapi_load_class_plugin()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;
    include(DSKAPI_INCLUDES_DIR . '/class-gateway.php');

    $current_dskapi_db_version = get_option('dskapi_db_version');
    if ($current_dskapi_db_version != DSKAPI_DB_VERSION) {
        dskapi_create_tables();
    }
}

/**
 * Create plugin database tables.
 * 
 * @return void
 */
function dskapi_create_tables()
{
    global $wpdb;

    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $table_orders_name      = $wpdb->prefix . 'dskpayment_orders';
    $charset_collate        = $wpdb->get_charset_collate();

    $sql_orders = "CREATE TABLE $table_orders_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_id int(11) NOT NULL,
		order_status tinyint(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `order_status` (`order_status`)
	) $charset_collate;";

    dbDelta($sql_orders);

    update_option('dskapi_db_version', DSKAPI_DB_VERSION);
}

/** add payment gateway */
function add_dskapi_gateway_class($gateways)
{
    $gateways[] = 'Dskapi_Payment_Gateway';
    return $gateways;
}

/** do output buffer */
function dskapi_do_output_buffer()
{
    ob_start();
}

/** load admin menu */
function dskapi_admin_options()
{
    include('dskapi_import_admin.php');
}

/** add origins */
function dskapi_add_allowed_origins($origins)
{
    $origins[] = DSKAPI_LIVEURL;
    return $origins;
}

/**
 * Redirect to checkout with DSK payment gateway after add to cart.
 * Triggered when dskapi_checkout=1 is present in request.
 *
 * @param string $url Default redirect URL.
 * @return string Modified redirect URL.
 */
function dskapi_add_to_cart_redirect($url)
{
    if (isset($_REQUEST['dskapi_checkout']) && $_REQUEST['dskapi_checkout'] == '1') {
        $gateway = isset($_REQUEST['dskapi_gateway']) ? sanitize_text_field($_REQUEST['dskapi_gateway']) : 'dskapipayment';
        return wc_get_checkout_url() . '?payment_method=' . $gateway;
    }
    return $url;
}

/**
 * Preselect DSK payment gateway on checkout page.
 * Sets chosen_payment_method in WooCommerce session when payment_method GET param is present.
 *
 * @return void
 */
function dskapi_template_redirect()
{
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    if (is_checkout() && isset($_GET['payment_method'])) {
        $gateway = wc_clean(wp_unslash($_GET['payment_method']));
        $available = WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : [];

        if (isset($available[$gateway])) {
            WC()->session->set('chosen_payment_method', $gateway);
        }
    }
}

/**
 * Add DSK cart button to WooCommerce cart fragments.
 * This ensures the button is updated when cart is refreshed via AJAX.
 *
 * @param array $fragments Cart fragments to update.
 * @return array Updated fragments.
 */
function dskapi_cart_fragments($fragments)
{
    // Only add fragment on cart page
    if (!is_cart()) {
        return $fragments;
    }

    ob_start();
    dskpayment_cart_button();
    $dskapi_cart_button_html = ob_get_clean();

    // Only add fragment if we have content
    if (!empty(trim($dskapi_cart_button_html))) {
        $fragments['#dskapi-cart-button-container'] = $dskapi_cart_button_html;
    }

    return $fragments;
}

/**
 * AJAX handler to refresh DSK cart button.
 * Called after cart is updated to get fresh button HTML.
 *
 * @return void
 */
function dskapi_refresh_cart_button()
{
    ob_start();
    dskpayment_cart_button();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

/** add order column dskapi status */
function dskapi_add_order_column_status($columns)
{
    $dskapi_status_columns = (is_array($columns)) ? $columns : array();
    unset($dskapi_status_columns['order_actions']);
    $dskapi_status_columns['dskapi_status_columnt'] = 'Банка ДСК Статус';
    $dskapi_status_columns['order_actions'] = $columns['order_actions'];
    return $dskapi_status_columns;
}

/** add order column dskapi status hpos */
function dskapi_add_order_column_status_hpos($columns)
{
    $dskapi_reordered_columns = array();
    foreach ($columns as $key => $column) {
        $dskapi_reordered_columns[$key] = $column;
        if ($key ===  'order_status') {
            $dskapi_reordered_columns['dskapi_status_columnt'] = 'Банка ДСК Статус';
        }
    }
    return $dskapi_reordered_columns;
}

/** add order column dskapi status values */
/**
 * Display DSK API status in orders table column (legacy post-based).
 *
 * @param string $column Column name.
 * @return void
 */
function dskapi_add_order_column_status_values($column)
{
    global $post;

    if ($column !== 'dskapi_status_columnt') {
        return;
    }

    echo esc_html(Dskapi_Orders::get_status_label($post->ID));
}

/** add order column dskapi status values hpos */
/**
 * Display DSK API status in orders table column (HPOS compatible).
 *
 * @param string   $column Column name.
 * @param WC_Order $order  WooCommerce order object.
 * @return void
 */
function dskapi_add_order_column_status_values_hpos($column, $order)
{
    if ($column !== 'dskapi_status_columnt') {
        return;
    }

    echo esc_html(Dskapi_Orders::get_status_label($order->get_id()));
}

function dskapi_wordpress_get_params($param = null, $null_return = null)
{
    if ($param) {
        $value = (!empty($_POST[$param]) ? trim(esc_sql($_POST[$param])) : (!empty($_GET[$param]) ? trim(esc_sql($_GET[$param])) : $null_return));
        return $value;
    } else {
        $params = array();
        foreach ($_POST as $key => $param) {
            $params[trim(esc_sql($key))] = (!empty($_POST[$key]) ? trim(esc_sql($_POST[$key])) :  $null_return);
        }
        foreach ($_GET as $key => $param) {
            $key = trim(esc_sql($key));
            if (!isset($params[$key])) { // if there is no key or it's a null value
                $params[trim(esc_sql($key))] = (!empty($_GET[$key]) ? trim(esc_sql($_GET[$key])) : $null_return);
            }
        }
        return $params;
    }
}

/**
 * Enqueue frontend styles and scripts.
 *
 * @return void
 */
function dskapi_add_meta()
{
    if (is_front_page()) {
        $css_file = DSKAPI_PLUGIN_DIR . '/css/dskapi_rek.css';
        $js_file = DSKAPI_PLUGIN_DIR . '/js/dskapi_rek.js';

        wp_enqueue_style('dskapi_style_rek', DSKAPI_CSS_URI . '/dskapi_rek.css', [], filemtime($css_file), 'all');
        wp_enqueue_script('dskapi_js_rek', DSKAPI_JS_URI . '/dskapi_rek.js', [], filemtime($js_file), true);
    }

    if (is_product()) {
        $css_file = DSKAPI_PLUGIN_DIR . '/css/dskapi.css';
        $js_file = DSKAPI_PLUGIN_DIR . '/js/dskapi_product.js';

        wp_enqueue_style('dskapi_style_product', DSKAPI_CSS_URI . '/dskapi.css', [], filemtime($css_file), 'all');
        wp_enqueue_script('dskapi_js_product', DSKAPI_JS_URI . '/dskapi_product.js', [], filemtime($js_file), true);
    }

    if (is_cart()) {
        $css_file = DSKAPI_PLUGIN_DIR . '/css/dskapi.css';
        $js_file = DSKAPI_PLUGIN_DIR . '/js/dskapi_cart.js';

        wp_enqueue_style('dskapi_style_cart', DSKAPI_CSS_URI . '/dskapi.css', [], filemtime($css_file), 'all');
        wp_enqueue_script('dskapi_js_cart', DSKAPI_JS_URI . '/dskapi_cart.js', ['jquery'], filemtime($js_file), true);

        // Pass AJAX URL to JavaScript
        wp_localize_script('dskapi_js_cart', 'dskapi_cart_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dskapi_cart_nonce')
        ]);
    }
}

/**
 * Display advertisement banner on front page.
 *
 * @return void
 */
function dskapi_reklama()
{
    if (!is_front_page()) {
        return;
    }

    $dskapi_reklama = get_option('dskapi_reklama');

    if ($dskapi_reklama !== 'on' || !Dskapi_Client::is_enabled()) {
        return;
    }

    $paramsdskapi = Dskapi_Client::get_reklama();

    if (empty($paramsdskapi)) {
        return;
    }

    $o = '';

    if (($paramsdskapi['dsk_status'] ?? 0) != 1 || ($paramsdskapi['dsk_container_status'] ?? 0) != 1) {
        return;
    }

    $is_mobile = Dskapi_Client::is_mobile();

    if ($is_mobile) {
        $o .= '<div class="dskapi_float" onclick="window.open(\'' . esc_url(DSKAPI_LIVEURL . '/procedure.php') . '\', \'_blank\');">';
    } else {
        $o .= '<div class="dskapi_float" onclick="DskapiChangeContainer();">';
    }

    $o .= '<img src="' . esc_url(DSKAPI_LIVEURL . '/dist/img/dsk_logo.png') . '" class="dskapi-my-float">';
    $o .= '</div>';
    $o .= '<div class="dskapi-label-container">';
    $o .= '<div class="dskapi-label-text">';
    $o .= '<div class="dskapi-label-text-mask">';
    $o .= '<img src="' . esc_url($paramsdskapi['dsk_picture'] ?? '') . '" class="dskapi_header">';
    $o .= '<p class="dskapi_txt1">' . esc_html($paramsdskapi['dsk_container_txt1'] ?? '') . '</p>';
    $o .= '<p class="dskapi_txt2">' . esc_html($paramsdskapi['dsk_container_txt2'] ?? '') . '</p>';
    $o .= '<p class="dskapi-label-text-a"><a href="' . esc_url($paramsdskapi['dsk_logo_url'] ?? '#') . '" target="_blank">За повече информация</a></p>';
    $o .= '</div>';
    $o .= '</div>';
    $o .= '</div>';

    echo $o;
}


/** vizualize credit button */
function dskpayment_button()
{
    $dskapi_status = (string)get_option("dskapi_status");

    if ($dskapi_status == "on") {
        $dskapi_cid = (string)get_option("dskapi_cid");
        $dskapi_gap = (int)get_option("dskapi_gap", 0);
        global $product;
        global $woocommerce;
        if (version_compare($woocommerce->version, '2.6', ">=")) {
            $dskapi_product_id = $product->get_id();
            $dskapi_product_name = $product->get_name();
        } else {
            $dskapi_product_id = $product->id;
            $dskapi_product_name = $product->name;
        }
        $dskapi_price = wc_get_price_including_tax($product);
        if ($dskapi_price == 0) {
            $dskapi_is_empty = true;
        } else {
            $dskapi_is_empty = false;
        }

        $dskapi_currency_code = get_woocommerce_currency();
        if ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN') {
            return NULL;
        }

        $paramsdskapieur = Dskapi_Client::get_eur($dskapi_cid);

        if (empty($paramsdskapieur)) {
            return null;
        }

        $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
        $dskapi_sign = 'лв.';
        switch ($dskapi_eur) {
            case 0:
                break;
            case 1:
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
                }
                $dskapi_sign = 'лв.';
                break;
            case 2:
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
                }
                $dskapi_sign = 'евро';
                break;
        }

        $paramsdskapi = Dskapi_Client::get_product($dskapi_price, $dskapi_product_id, $dskapi_cid);

        if (empty($paramsdskapi)) {
            return NULL;
        }

        $dskapi_zaglavie = $paramsdskapi['dsk_zaglavie'];
        $dskapi_custom_button_status = intval($paramsdskapi['dsk_custom_button_status']);
        $dskapi_options = boolval($paramsdskapi['dsk_options']);
        $dskapi_is_visible = boolval($paramsdskapi['dsk_is_visible']);
        $dskapi_button_normal = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk.png';
        $dskapi_button_normal_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '.png';
        $dskapi_button_hover = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk-hover.png';
        $dskapi_button_hover_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '_hover.png';
        $dskapi_isvnoska = intval($paramsdskapi['dsk_isvnoska']);
        $dskapi_vnoski = intval($paramsdskapi['dsk_vnoski_default']);
        $dskapi_vnoska = floatval($paramsdskapi['dsk_vnoska']);
        $dskapi_button_status = intval($paramsdskapi['dsk_button_status']);
        $dskapi_maxstojnost = number_format(floatval($paramsdskapi['dsk_maxstojnost']), 2, ".", "");
        $dskapi_vnoski_visible = intval($paramsdskapi['dsk_vnoski_visible']);
        $dskapi_gpr = floatval($paramsdskapi['dsk_gpr']);

        // Parse installment visibility bitmask (bit N = month N+3)
        $dskapi_vnoski_visible_arr = Dskapi_Client::parse_installment_visibility($dskapi_vnoski_visible, $dskapi_vnoski);

        $useragent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $dskapi_is_mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
        if ($dskapi_is_mobile) {
            $dskapi_PopUp_Detailed_v1 = "dskapim_PopUp_Detailed_v1";
            $dskapi_Mask = "dskapim_Mask";
            $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dskm' . $paramsdskapi['dsk_reklama'] . '.png';
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
            $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dsk' . $paramsdskapi['dsk_reklama'] . '.png';
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

        if ((!$dskapi_is_empty) && ($dskapi_options) && $dskapi_is_visible && ($paramsdskapi['dsk_status'] == 1) && ($dskapi_button_status != 0)) {
?>
            <div id="dskapi-product-button-container" style="margin-top:<?php echo $dskapi_gap; ?>px;">
                <table class="dskapi_table">
                    <tr>
                        <td class="dskapi_button_table">
                            <div class="dskapi_button_div_txt">
                                <?php echo $dskapi_zaglavie; ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <table class="dskapi_table_img">
                    <tr>
                        <td class="dskapi_button_table">
                            <?php if ($dskapi_custom_button_status == 1) { ?>
                                <img id="btn_dskapi" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal_custom; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover_custom; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal_custom; ?>'" />
                            <?php } else { ?>
                                <img id="btn_dskapi" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal; ?>'" />
                            <?php } ?>
                        </td>
                    </tr>
                    <?php if ($dskapi_isvnoska == 1) { ?>
                        <tr>
                            <td class="dskapi_button_table">
                                <p><span id="dskapi_vnoski_txt"><?php echo $dskapi_vnoski; ?></span> x <span id="dskapi_vnoska_txt"><?php echo number_format($dskapi_vnoska, 2, '.', ''); ?></span> <span id="dskapi_sign_txt"><?php echo $dskapi_sign; ?></span></p>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <input type="hidden" id="dskapi_price" value="<?php echo wc_get_price_including_tax($product); ?>" />
            <input type="hidden" id="dskapi_cid" value="<?php echo $dskapi_cid; ?>" />
            <input type="hidden" id="dskapi_product_id" value="<?php echo $dskapi_product_id; ?>" />
            <input type="hidden" id="DSKAPI_LIVEURL" value="<?php echo DSKAPI_LIVEURL; ?>" />
            <input type="hidden" id="dskapi_button_status" value="<?php echo $dskapi_button_status; ?>" />
            <input type="hidden" id="dskapi_maxstojnost" value="<?php echo $dskapi_maxstojnost; ?>" />
            <input type="hidden" id="dskapi_eur" value="<?php echo $dskapi_eur; ?>" />
            <input type="hidden" id="dskapi_currency_code" value="<?php echo $dskapi_currency_code; ?>" />
            <input type="hidden" id="dskapi_checkout_url" value="<?php echo esc_url(wc_get_checkout_url()); ?>" />
            <input type="hidden" id="dskapi_payment_method" value="dskapipayment" />
            <div id="dskapi-product-popup-container" class="modalpayment_dskapi">
                <div class="modalpayment-content_dskapi">
                    <div id="dskapi_body">
                        <div class="<?php echo $dskapi_PopUp_Detailed_v1; ?>">
                            <div class="<?php echo $dskapi_Mask; ?>">
                                <img src="<?php echo $dskapi_picture; ?>" class="dskapi_header">
                                <p class="<?php echo $dskapi_product_name; ?>">Купи на изплащане със стоков кредит от Банка ДСК</p>
                                <div class="<?php echo $dskapi_body_panel_txt3; ?>">
                                    <div class="<?php echo $dskapi_body_panel_txt3_left; ?>">
                                        <p>
                                            • Улеснена процедура за електронно подписване<br />
                                            • Атрактивни условия по кредита<br />
                                            • Параметри изцяло по Ваш избор<br />
                                            • Одобрение до няколко минути изцяло онлайн
                                        </p>
                                    </div>
                                    <div class="<?php echo $dskapi_body_panel_txt3_right; ?>">
                                        <select id="dskapi_pogasitelni_vnoski_input" class="dskapi_txt_right" onchange="dskapi_pogasitelni_vnoski_input_change();" onfocus="dskapi_pogasitelni_vnoski_input_focus(this.value);">
                                            <?php for ($i = 3; $i <= 48; $i++) { ?>
                                                <?php if ($dskapi_vnoski_visible_arr[$i]) { ?>}
                                                <option value="<?php echo $i; ?>" <?php if ($dskapi_vnoski == $i) {
                                                                                        echo "selected";
                                                                                    } ?>><?php echo $i; ?> месеца</option>
                                            <?php } ?>
                                        <?php } ?>
                                        </select>
                                        <div class="<?php echo $dskapi_sumi_panel; ?>">
                                            <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                <div class="dskapi_sumi_txt">Размер на кредита /<?php echo $dskapi_sign; ?>/</div>
                                                <div>
                                                    <input class="dskapi_mesecna_price" type="text" id="dskapi_price_txt" readonly="readonly" value="<?php echo number_format($dskapi_price, 2, ".", ""); ?>" />
                                                </div>
                                            </div>
                                            <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                <div class="dskapi_sumi_txt">Месечна вноска /<?php echo $dskapi_sign; ?>/</div>
                                                <div>
                                                    <input class="dskapi_mesecna_price" type="text" id="dskapi_vnoska" readonly="readonly" value="<?php echo number_format($dskapi_vnoska, 2, ".", ""); ?>" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="<?php echo $dskapi_sumi_panel; ?>">
                                            <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                <div class="dskapi_sumi_txt">Обща дължима сума /<?php echo $dskapi_sign; ?>/</div>
                                                <div>
                                                    <input class="dskapi_mesecna_price" type="text" id="dskapi_obshtozaplashtane" readonly="readonly" value="<?php echo number_format($dskapi_vnoska * $dskapi_vnoski, 2, ".", ""); ?>" />
                                                </div>
                                            </div>
                                            <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                <div class="dskapi_sumi_txt">ГПР /%/</div>
                                                <div>
                                                    <input class="dskapi_mesecna_price" type="text" id="dskapi_gpr" readonly="readonly" value="<?php echo number_format($dskapi_gpr, 2, ".", ""); ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="<?php echo $dskapi_body_panel_txt4; ?>">
                                    Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.
                                </div>
                                <div class="<?php echo $dskapi_body_panel_footer; ?>">
                                    <div class="dskapi_btn" id="dskapi_buy_credit">Купи на изплащане</div>
                                    <div class="dskapi_btn_cancel" id="dskapi_back_credit">Откажи</div>
                                    <div class="<?php echo $dskapi_body_panel_left; ?>">
                                        <div class="dskapi_txt_footer">Ver. <?php echo DSKAPI_VERSION; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
    }
}

function dskapi_updateorder()
{
    $json = array();
    $json['success'] = 'unsuccess';

    $dskapi_cid = (string)get_option("dskapi_cid");

    if (isset($_REQUEST['order_id'])) {
        $dskapi_order_id = $_REQUEST['order_id'];
    } else {
        $dskapi_order_id = '';
    }

    if (isset($_REQUEST['status'])) {
        $dskapi_status = $_REQUEST['status'];
    } else {
        $dskapi_status = 0;
    }

    if (isset($_REQUEST['calculator_id'])) {
        $dskapi_calculator_id = $_REQUEST['calculator_id'];
    } else {
        $dskapi_calculator_id = '';
    }

    if (($dskapi_calculator_id != '') && ($dskapi_cid == $dskapi_calculator_id)) {
        if (file_exists(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json')) {
            $orderdata = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
            $dskapi_orderdata_all = json_decode($orderdata, true);
            foreach ($dskapi_orderdata_all as $key => $value) {
                if ($dskapi_orderdata_all[$key]['order_id'] == $dskapi_order_id) {
                    $dskapi_orderdata_all[$key]['order_status'] = $dskapi_status;
                }
            }
            $jsondata = json_encode($dskapi_orderdata_all);
            file_put_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json', $jsondata);
            $json['success'] = 'success';
        }
    }

    $json['dskapi_order_id'] = $dskapi_order_id;
    $json['dskapi_status'] = $dskapi_status;
    $json['dskapi_calculator_id'] = $dskapi_calculator_id;

    echo (json_encode($json));
    die();
}

/**
 * Display DSK credit button on cart page.
 * Shows after cart totals with functionality to proceed to checkout with DSK payment.
 * Wrapper div is always output for WooCommerce fragments compatibility.
 *
 * @return void
 */
function dskpayment_cart_button()
{
    // Always output wrapper for WooCommerce fragments
    echo '<div id="dskapi-cart-button-container">';

    $dskapi_status = (string)get_option("dskapi_status");
    if ($dskapi_status != "on") {
        echo '</div><!-- #dskapi-cart-button-container -->';
        return;
    }

    $dskapi_cid = (string)get_option("dskapi_cid");
    $dskapi_gap = (int)get_option("dskapi_gap", 0);

    // Get cart total and product ID
    $dskapi_price = Dskapi_Client::get_cart_total();
    $dskapi_product_id = Dskapi_Client::get_cart_product_id();

    if ($dskapi_price <= 0) {
        echo '</div><!-- #dskapi-cart-button-container -->';
        return;
    }

    // Determine currency settings
    $dskapi_currency_code = get_woocommerce_currency();
    if ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN') {
        echo '</div><!-- #dskapi-cart-button-container -->';
        return;
    }

    // Get EUR settings from API
    $paramsdskapieur = Dskapi_Client::get_eur($dskapi_cid);
    if (empty($paramsdskapieur)) {
        echo '</div><!-- #dskapi-cart-button-container -->';
        return;
    }

    $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
    $dskapi_sign = 'лв.';
    switch ($dskapi_eur) {
        case 0:
            // No conversion - use current currency
            if ($dskapi_currency_code == 'EUR') {
                $dskapi_sign = 'евро';
            }
            break;
        case 1:
            // Convert EUR to BGN
            if ($dskapi_currency_code == "EUR") {
                $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
            }
            $dskapi_sign = 'лв.';
            break;
        case 2:
            // Convert BGN to EUR
            if ($dskapi_currency_code == "BGN") {
                $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
            }
            $dskapi_sign = 'евро';
            break;
    }

    // Get product data from API
    $paramsdskapi = Dskapi_Client::get_product($dskapi_price, $dskapi_product_id, $dskapi_cid);

    if (empty($paramsdskapi)) {
        return;
    }

    $dskapi_zaglavie = $paramsdskapi['dsk_zaglavie'];
    $dskapi_custom_button_status = intval($paramsdskapi['dsk_custom_button_status']);
    $dskapi_options = boolval($paramsdskapi['dsk_options']);
    $dskapi_is_visible = boolval($paramsdskapi['dsk_is_visible']);
    $dskapi_button_normal = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk.png';
    $dskapi_button_normal_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '.png';
    $dskapi_button_hover = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk-hover.png';
    $dskapi_button_hover_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '_hover.png';
    $dskapi_isvnoska = intval($paramsdskapi['dsk_isvnoska']);
    $dskapi_vnoski = intval($paramsdskapi['dsk_vnoski_default']);
    $dskapi_vnoska = floatval($paramsdskapi['dsk_vnoska']);
    $dskapi_button_status = intval($paramsdskapi['dsk_button_status']);
    $dskapi_minstojnost = number_format(floatval($paramsdskapi['dsk_minstojnost']), 2, ".", "");
    $dskapi_maxstojnost = number_format(floatval($paramsdskapi['dsk_maxstojnost']), 2, ".", "");
    $dskapi_vnoski_visible = intval($paramsdskapi['dsk_vnoski_visible']);
    $dskapi_gpr = floatval($paramsdskapi['dsk_gpr']);

    // Parse installment visibility bitmask
    $dskapi_vnoski_visible_arr = Dskapi_Client::parse_installment_visibility($dskapi_vnoski_visible, $dskapi_vnoski);

    // Determine mobile/desktop styles
    $dskapi_is_mobile = Dskapi_Client::is_mobile();
    if ($dskapi_is_mobile) {
        $dskapi_PopUp_Detailed_v1 = "dskapim_PopUp_Detailed_v1";
        $dskapi_Mask = "dskapim_Mask";
        $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dskm' . $paramsdskapi['dsk_reklama'] . '.png';
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
        $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dsk' . $paramsdskapi['dsk_reklama'] . '.png';
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

    if (($dskapi_options) && $dskapi_is_visible && ($paramsdskapi['dsk_status'] == 1) && ($dskapi_button_status != 0)) {
        ?>
        <div class="dskapi-cart-button-inner" style="margin-top:<?php echo $dskapi_gap; ?>px;">
            <table class="dskapi_table">
                <tr>
                    <td class="dskapi_button_table">
                        <div class="dskapi_button_div_txt">
                            <?php echo $dskapi_zaglavie; ?>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="dskapi_table_img">
                <tr>
                    <td class="dskapi_button_table">
                        <?php if ($dskapi_custom_button_status == 1) { ?>
                            <img id="btn_dskapi_cart" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal_custom; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover_custom; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal_custom; ?>'" />
                        <?php } else { ?>
                            <img id="btn_dskapi_cart" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal; ?>'" />
                        <?php } ?>
                    </td>
                </tr>
                <?php if ($dskapi_isvnoska == 1) { ?>
                    <tr>
                        <td class="dskapi_button_table">
                            <p><span id="dskapi_cart_vnoski_txt"><?php echo $dskapi_vnoski; ?></span> x <span id="dskapi_cart_vnoska_txt"><?php echo number_format($dskapi_vnoska, 2, '.', ''); ?></span> <span id="dskapi_cart_sign_txt"><?php echo $dskapi_sign; ?></span></p>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div><!-- .dskapi-cart-button-inner -->
        <input type="hidden" id="dskapi_cart_price" value="<?php echo number_format($dskapi_price, 2, '.', ''); ?>" />
        <input type="hidden" id="dskapi_cart_cid" value="<?php echo $dskapi_cid; ?>" />
        <input type="hidden" id="dskapi_cart_product_id" value="<?php echo $dskapi_product_id; ?>" />
        <input type="hidden" id="DSKAPI_CART_LIVEURL" value="<?php echo DSKAPI_LIVEURL; ?>" />
        <input type="hidden" id="dskapi_cart_button_status" value="<?php echo $dskapi_button_status; ?>" />
        <input type="hidden" id="dskapi_cart_maxstojnost" value="<?php echo $dskapi_maxstojnost; ?>" />
        <input type="hidden" id="dskapi_cart_checkout_url" value="<?php echo esc_url(wc_get_checkout_url()); ?>" />
        <input type="hidden" id="dskapi_cart_payment_method" value="dskapipayment" />
        <div id="dskapi-cart-popup-container" class="modalpayment_dskapi">
            <div class="modalpayment-content_dskapi">
                <div id="dskapi_body">
                    <div class="<?php echo $dskapi_PopUp_Detailed_v1; ?>">
                        <div class="<?php echo $dskapi_Mask; ?>">
                            <img src="<?php echo $dskapi_picture; ?>" class="dskapi_header">
                            <p class="<?php echo $dskapi_product_name; ?>">Купи на изплащане със стоков кредит от Банка ДСК</p>
                            <div class="<?php echo $dskapi_body_panel_txt3; ?>">
                                <div class="<?php echo $dskapi_body_panel_txt3_left; ?>">
                                    <p>
                                        • Улеснена процедура за електронно подписване<br />
                                        • Атрактивни условия по кредита<br />
                                        • Параметри изцяло по Ваш избор<br />
                                        • Одобрение до няколко минути изцяло онлайн
                                    </p>
                                </div>
                                <div class="<?php echo $dskapi_body_panel_txt3_right; ?>">
                                    <select id="dskapi_cart_pogasitelni_vnoski_input" class="dskapi_txt_right" onchange="dskapi_cart_pogasitelni_vnoski_input_change();" onfocus="dskapi_cart_pogasitelni_vnoski_input_focus(this.value);">
                                        <?php for ($i = 3; $i <= 48; $i++) { ?>
                                            <?php if ($dskapi_vnoski_visible_arr[$i]) { ?>
                                                <option value="<?php echo $i; ?>" <?php if ($dskapi_vnoski == $i) {
                                                                                        echo 'selected';
                                                                                    } ?>><?php echo $i; ?> месеца</option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                    <div class="<?php echo $dskapi_sumi_panel; ?>">
                                        <div class="<?php echo $dskapi_kredit_panel; ?>">
                                            <div class="dskapi_sumi_txt">Размер на кредита /<?php echo $dskapi_sign; ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_cart_price_txt" readonly="readonly" value="<?php echo number_format($dskapi_price, 2, ".", ""); ?>" />
                                            </div>
                                        </div>
                                        <div class="<?php echo $dskapi_kredit_panel; ?>">
                                            <div class="dskapi_sumi_txt">Месечна вноска /<?php echo $dskapi_sign; ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_cart_vnoska" readonly="readonly" value="<?php echo number_format($dskapi_vnoska, 2, ".", ""); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?php echo $dskapi_sumi_panel; ?>">
                                        <div class="<?php echo $dskapi_kredit_panel; ?>">
                                            <div class="dskapi_sumi_txt">Обща дължима сума /<?php echo $dskapi_sign; ?>/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_cart_obshtozaplashtane" readonly="readonly" value="<?php echo number_format($dskapi_vnoska * $dskapi_vnoski, 2, ".", ""); ?>" />
                                            </div>
                                        </div>
                                        <div class="<?php echo $dskapi_kredit_panel; ?>">
                                            <div class="dskapi_sumi_txt">ГПР /%/</div>
                                            <div>
                                                <input class="dskapi_mesecna_price" type="text" id="dskapi_cart_gpr" readonly="readonly" value="<?php echo number_format($dskapi_gpr, 2, ".", ""); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="<?php echo $dskapi_body_panel_txt4; ?>">
                                Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.
                            </div>
                            <div class="<?php echo $dskapi_body_panel_footer; ?>">
                                <div class="dskapi_btn" id="dskapi_cart_buy_credit">Купи на изплащане</div>
                                <div class="dskapi_btn_cancel" id="dskapi_cart_back_credit">Откажи</div>
                                <div class="<?php echo $dskapi_body_panel_left; ?>">
                                    <div class="dskapi_txt_footer">Ver. <?php echo DSKAPI_VERSION; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
    // Close the wrapper div for WooCommerce fragments
    echo '</div><!-- #dskapi-cart-button-container -->';
}
