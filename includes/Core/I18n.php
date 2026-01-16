<?php
/**
 * Define the internationalization functionality.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Core_I18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'plugiva-clientguard',
			false,
			dirname( plugin_basename( PCG_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
