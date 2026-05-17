<?php

/**
 * Control Panel (DSK API) server-to-server endpoints.
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dskapi_Cp_Api
 */
class Dskapi_Cp_Api {

	/**
	 * AJAX action for cache purge requests from CP.
	 *
	 * @var string
	 */
	const ACTION_CLEAR_CACHE = 'dskapi_cp_clear_cache';

	/**
	 * POST field name for store calculator ID (shared secret with CP).
	 *
	 * @var string
	 */
	const PARAM_CID = 'cid';

	/**
	 * Optional header when CP uses server-side HTTP client (no browser Origin).
	 *
	 * @var string
	 */
	const HEADER_CP_ORIGIN = 'HTTP_X_DSKAPI_CP_ORIGIN';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION_CLEAR_CACHE, array( __CLASS__, 'handle_clear_cache' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_CACHE, array( __CLASS__, 'handle_clear_cache' ) );
	}

	/**
	 * Clear calculation cache for the authenticated store (CP only).
	 *
	 * Expected POST body:
	 * - cid: Calculator ID (must match dskapi_cid option).
	 *
	 * CP must send Origin/Referer or X-Dskapi-Cp-Origin matching DSKAPI_LIVEURL host.
	 *
	 * @return void
	 */
	public static function handle_clear_cache() {
		self::enforce_post_method();

		if ( ! self::authorize_cp_request() ) {
			self::send_json(
				array(
					'success' => false,
					'message' => 'Forbidden',
				),
				403
			);
		}

		$stored_cid = Dskapi_Client::get_cid();
		$deleted    = Dskapi_Cache::delete_by_cid( $stored_cid );

		if ( WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'DSKAPI CP cache clear: cid=%s deleted=%d', $stored_cid, $deleted ) );
		}

		self::send_json(
			array(
				'success' => true,
				'deleted' => $deleted,
				'cid'     => $stored_cid,
			),
			200
		);
	}

	/**
	 * Reject non-POST requests.
	 *
	 * @return void
	 */
	private static function enforce_post_method() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		if ( 'POST' !== $method ) {
			self::send_json(
				array(
					'success' => false,
					'message' => 'Method not allowed',
				),
				405
			);
		}
	}

	/**
	 * Authorize request from Control Panel.
	 *
	 * @return bool
	 */
	private static function authorize_cp_request() {
		if ( ! Dskapi_Client::is_enabled() ) {
			return false;
		}

		$stored_cid = Dskapi_Client::get_cid();
		if ( '' === $stored_cid ) {
			return false;
		}

		$request_cid = isset( $_POST[ self::PARAM_CID ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::PARAM_CID ] ) )
			: '';

		if ( '' === $request_cid || ! hash_equals( $stored_cid, $request_cid ) ) {
			return false;
		}

		if ( ! self::is_request_from_control_panel() ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify request originates from DSKAPI_LIVEURL host.
	 *
	 * Accepts standard Origin/Referer or X-Dskapi-Cp-Origin header (for server-side curl).
	 *
	 * @return bool
	 */
	private static function is_request_from_control_panel() {
		$allowed_host = self::get_allowed_cp_host();
		if ( '' === $allowed_host ) {
			return false;
		}

		$request_hosts = self::get_request_source_hosts();
		if ( empty( $request_hosts ) ) {
			return false;
		}

		foreach ( $request_hosts as $host ) {
			if ( hash_equals( $allowed_host, $host ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Allowed CP hostname from DSKAPI_LIVEURL (lowercase, no port).
	 *
	 * @return string
	 */
	private static function get_allowed_cp_host() {
		$host = parse_url( DSKAPI_LIVEURL, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return '';
		}

		return strtolower( $host );
	}

	/**
	 * Collect normalized hostnames from request headers.
	 *
	 * @return string[]
	 */
	private static function get_request_source_hosts() {
		$hosts      = array();
		$header_map = array(
			'HTTP_ORIGIN'           => isset( $_SERVER['HTTP_ORIGIN'] ) ? wp_unslash( $_SERVER['HTTP_ORIGIN'] ) : '',
			'HTTP_REFERER'          => isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '',
			self::HEADER_CP_ORIGIN  => isset( $_SERVER[ self::HEADER_CP_ORIGIN ] ) ? wp_unslash( $_SERVER[ self::HEADER_CP_ORIGIN ] ) : '',
		);

		foreach ( $header_map as $value ) {
			if ( '' === $value ) {
				continue;
			}

			$host = parse_url( $value, PHP_URL_HOST );
			if ( empty( $host ) ) {
				// Header may be host-only without scheme.
				$host = sanitize_text_field( $value );
			}

			if ( '' !== $host ) {
				$hosts[] = strtolower( $host );
			}
		}

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Send JSON response and exit.
	 *
	 * @param array<string, mixed> $payload Response body.
	 * @param int                  $status  HTTP status code.
	 * @return void
	 */
	private static function send_json( array $payload, $status ) {
		status_header( $status );
		wp_send_json( $payload, $status );
	}
}
