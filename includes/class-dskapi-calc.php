<?php

/**
 * AJAX handler for cached installment calculations.
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dskapi_Calc
 */
class Dskapi_Calc {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_dskapi_get_product_custom', array( __CLASS__, 'ajax_get_product_custom' ) );
		add_action( 'wp_ajax_nopriv_dskapi_get_product_custom', array( __CLASS__, 'ajax_get_product_custom' ) );
	}

	/**
	 * AJAX: return installment calculation (same JSON shape as DSK API).
	 *
	 * @return void
	 */
	public static function ajax_get_product_custom() {
		Dskapi_Ajax::verify_nonce();

		if ( ! Dskapi_Client::is_enabled() ) {
			self::send_error_response();
		}

		$cid = isset( $_POST['cid'] ) ? sanitize_text_field( wp_unslash( $_POST['cid'] ) ) : '';

		if ( $cid === '' || $cid !== Dskapi_Client::get_cid() ) {
			self::send_error_response();
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$installments = isset( $_POST['dskapi_vnoski'] ) ? absint( $_POST['dskapi_vnoski'] ) : 0;
		$price_raw    = isset( $_POST['price'] ) ? wp_unslash( $_POST['price'] ) : '';

		if ( $product_id <= 0 || $installments < 3 || $installments > 48 ) {
			self::send_error_response();
		}

		$price = number_format( (float) $price_raw, 2, '.', '' );
		if ( (float) $price <= 0 ) {
			self::send_error_response();
		}

		$result = Dskapi_Client::get_product_custom( $price, $product_id, $installments, $cid );

		if ( null === $result ) {
			self::send_error_response();
		}

		wp_send_json( $result );
	}

	/**
	 * Send JSON body matching API validation-failure shape for JS handlers.
	 *
	 * @return void
	 */
	private static function send_error_response() {
		wp_send_json(
			array(
				'dsk_is_visible' => false,
				'dsk_options'    => false,
			)
		);
	}
}
