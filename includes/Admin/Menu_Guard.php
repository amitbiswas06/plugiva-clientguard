<?php
/**
 * Admin menu protection.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Menu_Guard {

	/**
	 * Settings option name.
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Initialize hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
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

		$hidden = array();

		if ( ! empty( $settings['hide_menus'] ) && is_array( $settings['hide_menus'] ) ) {
			$hidden = $settings['hide_menus'];
		}

		// Client Mode override
		// @since 1.1.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$hidden = array_unique( array_merge( $hidden, array(
				'plugins.php',
				'themes.php',
				'tools.php',
			) ) );
		}

		if ( empty( $hidden ) ) {
			return;
		}

		foreach ( $hidden as $menu_slug ) {
			remove_menu_page( $menu_slug );
		}

	}
}
