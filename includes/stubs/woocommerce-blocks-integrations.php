<?php

/**
 * Intelephense stubs — WooCommerce Blocks Integrations (not loaded at runtime).
 *
 * @package DSK_POS_Loans
 */

namespace Automattic\WooCommerce\Blocks\Integrations;

interface IntegrationInterface
{
	public function get_name();
	public function initialize();
	/**
	 * @return string[]
	 */
	public function get_script_handles();
	/**
	 * @return string[]
	 */
	public function get_editor_script_handles();
	/**
	 * @return array<string, mixed>
	 */
	public function get_script_data();
}

class IntegrationRegistry
{
	public function initialize( $registry_identifier = '' ) {
		return true;
	}

	public function register( IntegrationInterface $integration ) {
		return true;
	}

	public function is_registered( $name ) {
		return false;
	}

	/**
	 * @param string|IntegrationInterface $name
	 * @return bool|IntegrationInterface|null
	 */
	public function unregister( $name ) {
		return false;
	}

	/**
	 * @return IntegrationInterface|null
	 */
	public function get_registered( $name ) {
		return null;
	}

	/**
	 * @return IntegrationInterface[]
	 */
	public function get_all_registered() {
		return array();
	}
}
