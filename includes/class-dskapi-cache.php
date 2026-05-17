<?php

/**
 * DB cache for DSK installment calculation API responses.
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dskapi_Cache
 */
class Dskapi_Cache {

	/**
	 * Cache table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE = 'dskapi_calc_cache';

	/**
	 * Fresh cache lifetime in seconds (15 minutes).
	 *
	 * @var int
	 */
	const TTL_FRESH = 900;

	/**
	 * Maximum age for stale fallback when API is unavailable (6 hours).
	 *
	 * @var int
	 */
	const TTL_STALE_MAX = 21600;

	/**
	 * Get full table name including prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Build deterministic cache key from request parameters.
	 *
	 * @param string $cid          Store calculator ID.
	 * @param int    $product_id   Product ID.
	 * @param string $price        Normalized price string.
	 * @param int    $installments Installment count.
	 * @return string 64-char hash.
	 */
	public static function build_cache_key( $cid, $product_id, $price, $installments ) {
		$payload = implode(
			'|',
			array(
				sanitize_key( (string) $cid ),
				(string) absint( $product_id ),
				(string) $price,
				(string) absint( $installments ),
			)
		);

		return hash( 'sha256', $payload );
	}

	/**
	 * Get fresh (non-expired) cached response.
	 *
	 * @param string $cache_key Cache key hash.
	 * @return array|null Decoded API response or null.
	 */
	public static function get_fresh( $cache_key ) {
		return self::get_row( $cache_key, true );
	}

	/**
	 * Get stale cached response for fallback (expired but within max age).
	 *
	 * @param string $cache_key Cache key hash.
	 * @return array|null Decoded API response or null.
	 */
	public static function get_stale( $cache_key ) {
		return self::get_row( $cache_key, false );
	}

	/**
	 * Store API response in cache.
	 *
	 * @param string $cache_key    Cache key.
	 * @param string $cid          Calculator ID.
	 * @param int    $product_id   Product ID.
	 * @param string $price        Normalized price.
	 * @param int    $installments Installment count.
	 * @param array  $response     API response array.
	 * @return void
	 */
	public static function set( $cache_key, $cid, $product_id, $price, $installments, array $response ) {
		global $wpdb;

		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + self::TTL_FRESH );

		$data = array(
			'cache_key'      => $cache_key,
			'cid'            => sanitize_key( (string) $cid ),
			'product_id'     => absint( $product_id ),
			'price'          => $price,
			'installments'   => absint( $installments ),
			'response_json'  => wp_json_encode( $response ),
			'created_at'     => $now,
			'expires_at'     => $expires,
		);

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE cache_key = %s LIMIT 1",
				$cache_key
			)
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'cache_key' => $cache_key ) );
		} else {
			$wpdb->insert( $table, $data );
		}
	}

	/**
	 * Delete all cache rows for a calculator ID.
	 *
	 * @param string $cid Calculator ID.
	 * @return int Rows deleted.
	 */
	public static function delete_by_cid( $cid ) {
		global $wpdb;

		return (int) $wpdb->delete(
			self::table_name(),
			array( 'cid' => sanitize_key( (string) $cid ) ),
			array( '%s' )
		);
	}

	/**
	 * Purge expired rows older than stale window (housekeeping).
	 *
	 * @return void
	 */
	public static function purge_expired() {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::TTL_STALE_MAX );
		$table  = self::table_name();

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at < %s",
				$cutoff
			)
		);
	}

	/**
	 * Read cache row and decode JSON.
	 *
	 * @param string $cache_key Cache key.
	 * @param bool   $fresh_only Only return if not expired.
	 * @return array|null
	 */
	private static function get_row( $cache_key, $fresh_only ) {
		global $wpdb;

		$table = self::table_name();
		$now   = current_time( 'mysql', true );

		if ( $fresh_only ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT response_json, created_at FROM {$table} WHERE cache_key = %s AND expires_at >= %s LIMIT 1",
					$cache_key,
					$now
				),
				ARRAY_A
			);
		} else {
			$stale_cutoff = gmdate( 'Y-m-d H:i:s', time() - self::TTL_STALE_MAX );
			$row          = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT response_json, created_at FROM {$table} WHERE cache_key = %s AND created_at >= %s LIMIT 1",
					$cache_key,
					$stale_cutoff
				),
				ARRAY_A
			);
		}

		if ( empty( $row['response_json'] ) ) {
			return null;
		}

		$decoded = json_decode( $row['response_json'], true );

		return is_array( $decoded ) ? $decoded : null;
	}
}
