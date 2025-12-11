<?php
class Dskapi_Payment_Gateway extends WC_Payment_Gateway {
	public $domain;
	public $instructions;
	public $order_status;
	
	public function __construct() {
		$this->domain = 'dskapipayment';
		$this->id = 'dskapipayment';
		$this->icon = apply_filters('woocommerce_custom_gateway_icon', '');
		$this->has_fields = false;
		$this->method_title = 'DSK Credit';
		$this->method_description = 'Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit';
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		// Define user set variables
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
		$this->order_status = $this->get_option( 'order_status', 'completed' );
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_dskapipayment', array( $this, 'thankyou_dskapipayment_page' ) );
		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_dskapipayment_instructions' ), 10, 3 );
	}
	
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Разреши/Забрани',
				'type'	=> 'checkbox',
				'label'   => 'Разреши DSK Credit покупки на Кредит',
				'default' => 'yes'
			),
			'title' => array(
				'title'	   => 'Заглавие',
				'type'		=> 'text',
				'description' => 'Показва това заглавие при избор на метод на плащане DSK Credit покупки на Кредит.',
				'default'	 => 'Банка ДСК',
				'desc_tip'	=> true,
			),
			'order_status' => array(
				'title'	   => 'Състояние на поръчката',
				'type'		=> 'select',
				'class'	   => 'wc-enhanced-select',
				'description' => 'Какво да бъде състоянието на поръчката след като платите с този метод.',
				'default'	 => 'wc-pending',
				'desc_tip'	=> true,
				'options'	 => wc_get_order_statuses()
			),
			'description' => array(
				'title'	   => 'Описание',
				'type'		=> 'textarea',
				'description' => 'Описание на метода за плащане.',
				'default'	 => 'С избора си да финансирате покупката чрез Банка ДСК Вие декларирате, че сте запознат с Информацията относно обработването на лични данни на физически лица от Банка ДСК АД.',
				'desc_tip'	=> true,
			),
			'instructions' => array(
				'title'	   => 'Инструкции',
				'type'		=> 'textarea',
				'description' => 'Показва тази инструкция при избор на метод на плащане DSK Credit покупки на Кредит.',
				'default'	 => 'Можеш да закупиш избрания продукт на изплащане! Можеш да купуваш стоки от 150.00 лв. до 10000.00 лв. Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.',
				'desc_tip'	=> true,
			),
		);
	}
	
	/**
	* Check if the gateway is available for use.
	*
	* @return bool
	*/
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );
		
		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}
		
		$dskapi_currency_code = get_woocommerce_currency();
		if ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN') {
			$is_available = false;
		}
		
		$dskapi_cid = (string)get_option("dskapi_cid");
		$dskapi_status = (string)get_option("dskapi_status");
		if ($dskapi_status != "on"){
			$is_available = false;
		}
		$dskapi_ch = curl_init();
		curl_setopt($dskapi_ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($dskapi_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($dskapi_ch, CURLOPT_MAXREDIRS, 2);
		curl_setopt($dskapi_ch, CURLOPT_TIMEOUT, 6);
		curl_setopt($dskapi_ch, CURLOPT_URL, DSKAPI_LIVEURL . '/function/getminmax.php?cid='.$dskapi_cid);
		$paramsdskapi = json_decode(curl_exec($dskapi_ch), true);
		curl_close($dskapi_ch);
		
		if (empty($paramsdskapi)){
			return false;
		}
		
		$dskapi_eur = (int)$paramsdskapi['dsk_eur'];
		if (WC()->cart) {
			$dsk_order_total = $this->get_order_total();
			switch ($dskapi_eur) {
				case 0:
					break;
				case 1:
					if ($dskapi_currency_code == "EUR") {
						$dsk_order_total = $dsk_order_total * 1.95583;
					}
					break;
				case 2:
					if ($dskapi_currency_code == "BGN") {
						$dsk_order_total = $dsk_order_total / 1.95583;
					}
					break;
			}
		}
		
		$dskapi_minstojnost = (float)$paramsdskapi['dsk_minstojnost'];
		$dskapi_maxstojnost = (float)$paramsdskapi['dsk_maxstojnost'];
		$dskapi_min_000 = (float)$paramsdskapi['dsk_min_000'];
		$dskapi_status_cp = $paramsdskapi['dsk_status'];
		
		$dskapi_purcent = (float)$paramsdskapi['dsk_purcent'];
		$dskapi_vnoski_default = (int)$paramsdskapi['dsk_vnoski_default'];
		if (($dskapi_purcent == 0) && ($dskapi_vnoski_default <= 6)){
			$dskapi_minstojnost = $dskapi_min_000;
		}
		
		if (WC()->cart){
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
	
	public function thankyou_dskapipayment_page() {
		if ( $this->instructions )
		echo wpautop( wptexturize( $this->instructions ) );
	}
	
	public function email_dskapipayment_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'dskapi' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
	
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}
		
		global $woocommerce;
		$dskapi_price = $woocommerce->cart->total;
		
		?>
		<input type="hidden" name="dskapi_price" id="dskapi_price" value="<?php echo $dskapi_price; ?>" />
		<a target="_blank" href="https://dskbank.bg/docs/default-source/gdpr/%D0%B8%D0%BD%D1%84%D0%BE%D1%80%D0%BC%D0%B0%D1%86%D0%B8%D1%8F-%D0%BE%D1%82%D0%BD%D0%BE%D1%81%D0%BD%D0%BE-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5%D1%82%D0%BE-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8-%D0%BD%D0%B0-%D1%84%D0%B8%D0%B7%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%B8-%D0%BB%D0%B8%D1%86%D0%B0-%D0%BE%D1%82-%D0%B1%D0%B0%D0%BD%D0%BA%D0%B0-%D0%B4%D1%81%D0%BA-%D0%B0%D0%B4-%D0%B8-%D1%81%D1%8A%D0%B3%D0%BB%D0%B0%D1%81%D0%B8%D1%8F-%D0%B7%D0%B0-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8.pdf">Информация относно обработването на лични данни на физически лица от 'Банка ДСК' АД</a>
		<?php
	}
	
	public function validate_fields(){
		return true;
	}
	
	public function process_payment( $order_id ) {
		$order = wc_get_order($order_id);
		$order->payment_complete();
		if (isset($_POST['billing_first_name'])) {
			$dskapi_fname = trim($_POST['billing_first_name'], " ");
		} else {
			$dskapi_fname = $order->get_billing_first_name() ? $order->get_billing_first_name() : '';
		}
		if (isset($_POST['billing_last_name'])) {
			$dskapi_lastname = trim($_POST['billing_last_name'], " ");
		} else {
			$dskapi_lastname = $order->get_billing_last_name() ? $order->get_billing_last_name() : '';
		}
		if (isset($_POST['billing_phone'])) {
			$dskapi_phone = $_POST['billing_phone'];
		} else {
			$dskapi_phone = $order->get_billing_phone() ? $order->get_billing_phone() : '';
		}
		if (isset($_POST['billing_email'])) {
			$dskapi_email = $_POST['billing_email'];
		} else {
			$dskapi_email = $order->get_billing_email() ? $order->get_billing_email() : '';
		}
		if (isset($_POST['dskapi_price'])) {
			$dskapi_price = floatval($_POST['dskapi_price']);
		} else {
			$dskapi_price = 0.00;
		}	
		if (isset($_POST['billing_city'])) {
			$dskapi_billing_city = $_POST['billing_city'];
		} else {
			$dskapi_billing_city = $order->get_billing_city() ? $order->get_billing_city() : '';
		}
		if (isset($_POST['billing_address_1'])) {
			$dskapi_billing_address_1 = $_POST['billing_address_1'];
		} else {
			$dskapi_billing_address_1 = $order->get_billing_address_1() ? $order->get_billing_address_1() : '';
		}
		if (isset($_POST['billing_postcode'])) {
			$dskapi_billing_postcode = $_POST['billing_postcode'];
		} else {
			$dskapi_billing_postcode = $order->get_billing_postcode() ? $order->get_billing_postcode() : '';
		}
		if (isset($_POST['shipping_city'])) {
			$dskapi_shipping_city = $_POST['shipping_city'];
		} else {
			$dskapi_shipping_city = $order->get_shipping_city() ? $order->get_shipping_city() : '';
		}
		if (isset($_POST['shipping_address_1'])) {
			$dskapi_shipping_address_1 = $_POST['shipping_address_1'];
		} else {
			$dskapi_shipping_address_1 = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : '';
		}
		
		global $woocommerce;
		$dskapi_total = (float)$woocommerce->cart->total;
		
		if ($order_id != 0){
			// Proceed to dskapi Process	
			$dskapi_cid = (string)get_option("dskapi_cid");
			
			$dskapi_eur = 0;
			$dskapi_ch_eur = curl_init();
			curl_setopt($dskapi_ch_eur, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($dskapi_ch_eur, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($dskapi_ch_eur, CURLOPT_MAXREDIRS, 3);
			curl_setopt($dskapi_ch_eur, CURLOPT_TIMEOUT, 5);
			curl_setopt($dskapi_ch_eur, CURLOPT_URL, DSKAPI_LIVEURL . '/function/geteur.php?cid=' . $dskapi_cid);
			$paramsdskapieur = json_decode(curl_exec($dskapi_ch_eur), true);

			$dskapi_currency_code = get_woocommerce_currency();
			$dskapi_currency_code_send = 0;
			if ($paramsdskapieur != null) {
				$dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
				switch ($dskapi_eur) {
					case 0:
						$dskapi_currency_code_send = 0;
						break;
					case 1:
						$dskapi_currency_code_send = 0;
						if ($dskapi_currency_code == "EUR") {
							$dskapi_total = number_format($dskapi_total * 1.95583, 2, ".", "");
						}
						break;
					case 2:
						$dskapi_currency_code_send = 1;
						if ($dskapi_currency_code == "BGN") {
							$dskapi_total = number_format($dskapi_total / 1.95583, 2, ".", "");
						}
						break;
					}
			}
			
			$products_id = '';
			$products_name = '';
			$products_q = '';
			$products_p = '';
			$products_c = '';
			$products_m = '';
			$products_i = '';
			foreach($woocommerce->cart->get_cart() as $cart_item) {
				$item = $cart_item['data'];
				if(!empty($item)) {
					$dsk_product = wc_get_product($item->get_id());
					$dskapi_price_cart = (float)wc_get_price_including_tax($dsk_product);
					$products_id .= $cart_item['product_id'];
					$products_id .= '_';
					$products_q .= $cart_item['quantity'];
					$products_q .= '_';
					
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
					
					$products_name .= str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($cart_item['data']->get_title(), ENT_QUOTES)));
					$products_name .= '_';
					$term_list = wp_get_post_terms($cart_item['product_id'],'product_cat',array('fields'=>'ids'));
					$products_c .= $term_list[0];
					$products_c .= '_';
					$dskapi_image = wp_get_attachment_image_src( get_post_thumbnail_id( $cart_item['product_id'] ), 'single-post-thumbnail' );
					$dskapi_imagePath = isset($dskapi_image[0]) ? $dskapi_image[0] : '';
					$dskapi_imagePath_64 = base64_encode($dskapi_imagePath);
					$products_i .= $dskapi_imagePath_64;
					$products_i .= '_';
				}
			}
			$products_id = trim($products_id, "_");
			$products_q = trim($products_q, "_");
			$products_p = trim($products_p, "_");
			$products_c = trim($products_c, "_");
			$products_m = trim($products_m, "_");
			$products_name = trim($products_name, "_");
			$products_i = trim($products_i, "_");
			
			$useragent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
			if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
				$dskapi_type_client = 1;
			}else{
				$dskapi_type_client = 0;
			}
			
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
				'type_client' => $dskapi_type_client,
				'products_p' => $products_p,
				'version' => DSKAPI_VERSION,
				'shoporder_id' => $order_id,
				'products_c' => $products_c,
				'products_m' => $products_m,
				'products_i' => $products_i,
				'currency' => $dskapi_currency_code_send
			];
			$dskapi_plaintext = json_encode($dskapi_post);
			$dskapi_publicKey = openssl_pkey_get_public(file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/pub.pem'));
			$dskapi_a_key = openssl_pkey_get_details($dskapi_publicKey);
			$dskapi_chunkSize = ceil($dskapi_a_key['bits'] / 8) - 11;
			$dskapi_output = '';
			while ($dskapi_plaintext) {
				$dskapi_chunk = substr($dskapi_plaintext, 0, $dskapi_chunkSize);
				$dskapi_plaintext = substr($dskapi_plaintext, $dskapi_chunkSize);
				$dskapi_encrypted = '';
				if (!openssl_public_encrypt($dskapi_chunk, $dskapi_encrypted, $dskapi_publicKey)) {
					die('Failed to encrypt data');
				}
				$dskapi_output .= $dskapi_encrypted;
			}
			if (version_compare(PHP_VERSION, '8.0.0', '<')){
				openssl_free_key($dskapi_publicKey);
			}
			$dskapi_output64 = base64_encode($dskapi_output);
			
			// Create dskapi order i data base
			$dskapi_add_ch = curl_init();
			curl_setopt_array($dskapi_add_ch, array(
				CURLOPT_URL => DSKAPI_LIVEURL . '/function/addorders.php',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 2,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode(array('data' => $dskapi_output64)),
				CURLOPT_HTTPHEADER => array(
					"Content-Type: application/json",
					"cache-control: no-cache"
				),
			));
			$paramsdskapiadd = json_decode(curl_exec($dskapi_add_ch), true);
			curl_close($dskapi_add_ch);
			
			if ((!empty($paramsdskapiadd)) && isset($paramsdskapiadd['order_id']) && ($paramsdskapiadd['order_id'] != 0)){
				// save to dskapiorders file
				$dskapi_tempcontent = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
				if ($dskapi_tempcontent != false){
					$dskapi_orders = json_decode($dskapi_tempcontent);
					// test over 1000
					if (is_array($dskapi_orders) && (count($dskapi_orders) >= 1000)){
						array_shift($dskapi_orders);
					}
					$dskapi_order_current = array(
						"order_id" => $order_id,
						"order_status" => 0
					);
					$key = array_search($order_id, array_map(
						function($o) {
							return $o->order_id;
						}, 
						$dskapi_orders));
					if ($key === false){
						array_push($dskapi_orders, $dskapi_order_current);
					}
					$jsondata = json_encode($dskapi_orders);
					file_put_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json', $jsondata);
				}
			
				// Return thankyou redirect
				WC()->cart->empty_cart();
				if ($dskapi_type_client == 1){
					return array(
						'result'	=> 'success',
						'redirect'  => esc_url_raw( DSKAPI_LIVEURL . '/applicationm_step1.php?oid='.$paramsdskapiadd['order_id'].'&cid='.$dskapi_cid)
					);
				}else{
					return array(
						'result'	=> 'success',
						'redirect'  => esc_url_raw( DSKAPI_LIVEURL . '/application_step1.php?oid='.$paramsdskapiadd['order_id'].'&cid='.$dskapi_cid)
					);
				}
			}else{
				if (empty($paramsdskapiadd)){
					//send mail to bank
					// save to dskapiorders file
					$dskapi_tempcontent = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
					if ($dskapi_tempcontent != false){
						$dskapi_orders = json_decode($dskapi_tempcontent);
						//test over 1000
						if (is_array($dskapi_orders) && (count($dskapi_orders) >= 1000)){
							array_shift($dskapi_orders);
						}
						$dskapi_order_current = array(
							"order_id" => $order_id,
							"order_status" => 0
						);
						$key = array_search($order_id, array_map(
							function($o) {
								return $o->order_id;
							}, 
							$dskapi_orders));
						if ($key === false){
							array_push($dskapi_orders, $dskapi_order_current);
						}
						$jsondata = json_encode($dskapi_orders);
						file_put_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json', $jsondata);
					}
					
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/plain; charset=UTF-8;' . "\r\n";
					wp_mail(DSKAPI_MAIL, 'Проблем комуникация заявка КП DSK Credit', json_encode($dskapi_post, JSON_PRETTY_PRINT), $headers);
					
					wc_add_notice( 'Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.', 'error' );
					return;
				}else{
					wc_add_notice( 'Вече има създадена заявка за кредит в системата на DSK Credit с номер на Вашия ордер: ' . $order_id, 'error' );
					return;
				}
			}
		}
	}
}
