<?php
/**
 * Theme protection guard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Theme_Guard {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_filter( 'user_has_cap', $this, 'block_theme_caps', 10, 4 );
	}

	/**
	 * Block theme switching and deletion.
	 *
	 * @param array   $allcaps All user caps.
	 * @param array   $caps    Required caps.
	 * @param array   $args    Arguments.
	 * @param WP_User $user    User object.
	 * @return array
	 */
	public function block_theme_caps( $allcaps, $caps, $args, $user ) {

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return $allcaps;
		}

		$settings = get_option( self::OPTION_NAME );

		$lock = ! empty( $settings['lock_theme_switch'] );

		// Client Mode override
		// @since 1.1.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock = true;
		}

		if ( ! $lock ) {
			return $allcaps;
		}

		// Always block switch & delete.
		// @since 1.1.0 - also block theme installation and editing for safety.
		$blocked_caps = array(
			'switch_themes',
			'delete_themes',
			'install_themes',
			'edit_themes',
		);

		foreach ( $blocked_caps as $cap ) {
			if ( isset( $allcaps[ $cap ] ) ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}
}
