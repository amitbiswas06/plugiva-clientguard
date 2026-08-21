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

		// @since 1.7.0
		$theme_switching_sentinel = new PCGD_Admin_Theme_Switching_Sentinel( $this );
		$theme_switching_sentinel->register( $loader );

		$theme_installation_sentinel = new PCGD_Admin_Theme_Installation_Sentinel( $this );
		$theme_installation_sentinel->register( $loader );

		$theme_deletion_sentinel = new PCGD_Admin_Theme_Deletion_Sentinel( $this );
		$theme_deletion_sentinel->register( $loader );
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
		if ( PCGD_Core_Plugin::should_bypass_protection( $user->ID ) ) {
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

	/**
	 * Determine whether theme operations are protected.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function is_theme_operations_protected() {

		if ( PCGD_Core_Plugin::should_bypass_protection() ) {
			return false;
		}

		$settings = get_option( self::OPTION_NAME, array() );

		$lock = ! empty( $settings['lock_theme_switch'] );

		/*
		* Client Mode protects all theme operations.
		*/
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock = true;
		}

		return $lock;
	}
}
