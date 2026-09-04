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

		$loader->add_action( 'update_site_option_allowedthemes', $this, 'sentinel_network_theme_change', 10, 3 );
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
	 * Determine whether theme operation protection is enabled.
	 *
	 * This checks the effective protection state without considering
	 * whether the current user is exempt from ClientGuard restrictions.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function is_theme_operation_protection_enabled() {

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

		return $this->is_theme_operation_protection_enabled();
	}

	/**
	 * Observe network-wide theme enable/disable.
	 *
	 * @since 1.7.0
	 *
	 * @param string $option     Option name.
	 * @param array  $value      New allowed themes.
	 * @param array  $old_value  Previous allowed themes.
	 * @return void
	 */
	public function sentinel_network_theme_change( $option, $value, $old_value ) {

		if ( 'allowedthemes' !== $option ) {
			return;
		}

		$enabled = array_diff_key( $value, $old_value );
		$disabled = array_diff_key( $old_value, $value );

		foreach ( $enabled as $stylesheet => $enabled_value ) {

			do_action(
				'pcgd_protection_bypassed',
				'theme_guard',
				'network_enable',
				$stylesheet
			);
		}

		foreach ( $disabled as $stylesheet => $disabled_value ) {

			do_action(
				'pcgd_protection_bypassed',
				'theme_guard',
				'network_disable',
				$stylesheet
			);
		}
	}

}
