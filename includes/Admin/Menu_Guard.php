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

		// Handle ACF admin visibility based on settings and Client Mode.
		// @since 1.1.0
		$loader->add_filter( 'acf/settings/show_admin', $this, 'handle_acf_admin_visibility' );
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

		// Remove submenu (run independently)
		// @since 1.4.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			remove_submenu_page( 'options-general.php', 'options-permalink.php' );
		}

		if ( empty( $hidden ) ) {
			return;
		}

		foreach ( $hidden as $menu_slug ) {
			remove_menu_page( $menu_slug );
		}

	}

	/**
	 * Handle ACF admin visibility based on settings and Client Mode.
	 *
	 * @param bool $show Current visibility state of ACF admin.
	 * @return bool Modified visibility state.
	 * @since 1.1.0
	 */
	public function handle_acf_admin_visibility( $show ) {

		$settings = get_option( 'pcgd_settings', array() );

		// Hide if selected in menu hiding
		if ( ! empty( $settings['hide_menus'] ) && in_array( 'acf', $settings['hide_menus'], true ) ) {
			return false;
		}

		// Hide in Client Mode
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			return false;
		}

		return $show;
	}

}
