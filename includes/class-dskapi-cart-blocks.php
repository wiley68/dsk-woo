<?php

/**
 * DSK API Cart Block integration (WooCommerce Blocks cart page).
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the DSK cart credit button into the block-based cart sidebar.
 */
final class Dskapi_Cart_Blocks_Integration implements IntegrationInterface {

	/**
	 * Whether the button markup was already appended during this request.
	 *
	 * @var bool
	 */
	private static $button_appended = false;

	/**
	 * Integration name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dskapi_cart';
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function initialize() {
		add_filter( 'render_block', array( $this, 'append_cart_button_to_block' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_cart_assets' ), 5 );
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', array( $this, 'enqueue_cart_assets' ) );
	}

	/**
	 * Registers cart assets early so Cart block can depend on them.
	 *
	 * @return void
	 */
	public function register_cart_assets() {
		if ( function_exists( 'dskapi_register_cart_assets' ) ) {
			dskapi_register_cart_assets();
		}
	}

	/**
	 * Ensures cart assets load when the Cart block is rendered.
	 *
	 * @return void
	 */
	public function enqueue_cart_assets() {
		if ( function_exists( 'dskapi_register_cart_assets' ) ) {
			dskapi_register_cart_assets();
		}

		wp_enqueue_style( 'dskapi_style_cart' );
		wp_enqueue_script( 'dskapi_js_calc' );
		wp_enqueue_script( 'dskapi_js_cart' );
	}

	/**
	 * Frontend script handles (cart block pages).
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'dskapi_js_cart' );
	}

	/**
	 * Editor script handles.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Data exposed to wcSettings (reserved for future block scripts).
	 *
	 * @return array<string, mixed>
	 */
	public function get_script_data() {
		return array(
			'isBlockCart' => dskapi_is_block_cart(),
		);
	}

	/**
	 * Append DSK cart button HTML after the Proceed to Checkout block.
	 *
	 * Mirrors classic hook woocommerce_after_cart_totals placement in the sidebar.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data.
	 * @return string
	 */
	public function append_cart_button_to_block( $block_content, $block ) {
		if ( self::$button_appended || ! dskapi_is_block_cart() ) {
			return $block_content;
		}

		if ( empty( $block['blockName'] ) || 'woocommerce/proceed-to-checkout-block' !== $block['blockName'] ) {
			return $block_content;
		}

		self::$button_appended = true;

		$html = dskapi_get_cart_button_html();
		if ( '' === $html ) {
			return $block_content;
		}

		return $block_content . $html;
	}
}
