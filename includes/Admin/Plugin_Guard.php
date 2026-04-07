<?php
/**
 * Plugin Guard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Plugin_Guard {

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
		$loader->add_filter( 'user_has_cap', $this, 'filter_caps', 10, 4 );
		$loader->add_filter( 'map_meta_cap', $this, 'block_plugin_actions', 10, 4 );
	}

	/**
	 * Filter primitive plugin capabilities.
	 *
	 * @param array  $allcaps All user caps.
	 * @param array  $caps    Required caps.
	 * @param array  $args    Arguments.
	 * @param object $user    User object.
	 * @return array
	 */
	public function filter_caps( $allcaps, $caps, $args, $user ) {

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return $allcaps;
		}

		$settings = get_option( self::OPTION_NAME );

		$lock_install = ! empty( $settings['lock_plugin_install'] );

		// Client Mode override
		// @since 1.1.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock_install = true;
		}

		if ( ! $lock_install ) {
			return $allcaps;
		}

		// Always block install & delete.
		$allcaps['install_plugins'] = false;
		$allcaps['delete_plugins'] 	= false;

		// Block plugin file editing for safety.
		// @since 1.1.0
		$allcaps['edit_plugins'] 	= false;

		/**
		 * IMPORTANT:
		 * Do NOT remove 'activate_plugins'.
		 * Removing it hides the Plugins menu entirely.
		 */

		return $allcaps;
	}

	/**
	 * Block plugin activation/deactivation actions.
	 *
	 * @param array  $caps    Required caps.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments.
	 * @return array
	 */
	public function block_plugin_actions( $caps, $cap, $user_id, $args ) {

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user_id ) ) {
			return $caps;
		}

		$settings = get_option( self::OPTION_NAME );

		$lock_install = ! empty( $settings['lock_plugin_install'] );
		$allow_toggle = ! empty( $settings['allow_plugin_toggle'] );

		// Client Mode override
		// @since 1.1.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock_install = true;
			$allow_toggle = false;
		}

		if ( ! $lock_install || $allow_toggle ) {
			return $caps;
		}

		// Block activation & deactivation explicitly.
		if ( in_array( $cap, array( 'activate_plugin', 'deactivate_plugin' ), true ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}
}
