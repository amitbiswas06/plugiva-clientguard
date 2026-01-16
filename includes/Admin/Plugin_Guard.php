<?php
/**
 * Plugin protection guard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Plugin_Guard {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcg_settings';

	/**
	 * Register hooks.
	 *
	 * @param PCG_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_filter( 'user_has_cap', $this, 'block_plugin_caps', 10, 4 );
	}

	/**
	 * Block plugin install/delete (and optionally activate).
	 *
	 * @param array   $allcaps All user caps.
	 * @param array   $caps    Required caps.
	 * @param array   $args    Arguments.
	 * @param WP_User $user    User object.
	 * @return array
	 */
	public function block_plugin_caps( $allcaps, $caps, $args, $user ) {

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return $allcaps;
		}

		$settings = get_option( self::OPTION_NAME );

		if ( empty( $settings['lock_plugin_install'] ) ) {
			return $allcaps;
		}

		// Always block install & delete.
		$blocked_caps = array(
			'install_plugins',
			'delete_plugins',
		);

		// Optionally block activate/deactivate.
		if ( empty( $settings['allow_plugin_toggle'] ) ) {
			$blocked_caps[] = 'activate_plugins';
		}

		foreach ( $blocked_caps as $cap ) {
			if ( isset( $allcaps[ $cap ] ) ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}
}
