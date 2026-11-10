<?php
/**
 * Manages the integration boundary between ClientGuard Free and Pro.
 *
 * @since 1.8.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides controlled access to Pro integration features.
 */
final class PCGD_Pro_Integration {

	/**
	 * Check whether the Pro coordinator is initialized.
	 *
	 * @return bool
	 */
	public static function is_available() {

		return class_exists( 'PCGD_Pro_Plugin' )
			&& is_callable(
				array(
					'PCGD_Pro_Plugin',
					'is_initialized',
				)
			)
			&& PCGD_Pro_Plugin::is_initialized();
	}

}