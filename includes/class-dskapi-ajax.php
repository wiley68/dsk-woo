<?php

/**
 * WordPress AJAX helpers (server-side nonce).
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dskapi_Ajax
 */
class Dskapi_Ajax {

	/**
	 * Nonce action for all storefront AJAX requests.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'dskapi_ajax';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_dskapi_get_nonce', array( __CLASS__, 'ajax_get_nonce' ) );
		add_action( 'wp_ajax_nopriv_dskapi_get_nonce', array( __CLASS__, 'ajax_get_nonce' ) );
	}

	/**
	 * AJAX: return a fresh nonce for the current session.
	 *
	 * @return void
	 */
	public static function ajax_get_nonce() {
		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce( self::NONCE_ACTION ),
			)
		);
	}

	/**
	 * Script variables for frontend (nonce is fetched via dskapi_get_nonce).
	 *
	 * @return array<string, string>
	 */
	public static function get_script_vars() {
		return array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
	}

	/**
	 * Verify AJAX nonce from request parameter "nonce".
	 *
	 * @return void
	 */
	public static function verify_nonce() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}
}
