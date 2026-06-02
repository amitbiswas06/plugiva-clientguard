<?php
/**
 * Appearance protection guard.
 *
 * @package Plugiva_ClientGuard
 * @since 1.6.0 - New guard class created for future appearance-related protections.
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Appearance_Guard {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 * @return void
	 */
	public function register( $loader ) {

        $loader->add_filter( 'user_has_cap', $this, 'block_appearance_caps', 10, 4 );

	}

    /**
	 * Block appearance management capabilities.
	 *
	 * @param array   $allcaps All user capabilities.
	 * @param array   $caps    Required capabilities.
	 * @param array   $args    Capability arguments.
	 * @param WP_User $user    User object.
	 * @return array
	 */
	public function block_appearance_caps( $allcaps, $caps, $args, $user ) {

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return $allcaps;
		}

		$settings = get_option( self::OPTION_NAME );

		$lock = ! empty( $settings['lock_appearance_management'] );

		// Client Mode override.
		// @since 1.6.0
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock = true;
		}

		if ( ! $lock ) {
			return $allcaps;
		}

		if ( isset( $allcaps['edit_theme_options'] ) ) {
			$allcaps['edit_theme_options'] = false;
		}

		return $allcaps;
	}

}