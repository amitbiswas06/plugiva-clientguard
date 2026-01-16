<?php
/**
 * Admin menu protection.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Menu_Guard {

	/**
	 * Settings option name.
	 */
	const OPTION_NAME = 'pcg_settings';

	/**
	 * Initialize hooks.
	 *
	 * @param PCG_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_action( 'admin_menu', $this, 'hide_menus', 999 );
	}

	/**
	 * Hide selected admin menus.
	 */
	public function hide_menus() {

		// Never restrict network Super Admins (multisite only).
        if ( is_multisite() && is_super_admin() ) {
            return;
        }

		$settings = get_option( self::OPTION_NAME );

		if ( empty( $settings['hide_menus'] ) || ! is_array( $settings['hide_menus'] ) ) {
			return;
		}

		foreach ( $settings['hide_menus'] as $menu_slug ) {
			remove_menu_page( $menu_slug );
		}
	}
}
