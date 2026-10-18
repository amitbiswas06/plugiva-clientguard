<?php
/**
 * Theme switching Sentinel.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Theme_Switching_Sentinel {

	/**
	 * Theme Guard instance.
	 *
	 * @var PCGD_Admin_Theme_Guard
	 */
	private $guard;

	/**
	 * Constructor.
	 *
	 * @param PCGD_Admin_Theme_Guard $guard Theme Guard instance.
	 */
	public function __construct( $guard ) {
		$this->guard = $guard;
	}

	/**
	 * Register operational theme switching protection.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {

		$loader->add_filter( 'validate_theme_requirements', $this, 'guard_theme_switching', 10, 2 );

		// Protect direct changes to the active theme state.
		$loader->add_filter( 'pre_update_option_template', $this, 'guard_active_theme_option_update', 10, 3 );
		$loader->add_filter( 'pre_update_option_stylesheet', $this, 'guard_active_theme_option_update', 10, 3 );

		// Protect deletion of the active theme state options.
		$loader->add_action( 'delete_option', $this, 'protect_active_theme_option_delete', 10, 1 );

	}

	/**
	 * Block protected theme switching.
	 *
	 * @param bool|WP_Error $met_requirements Theme requirement validation result.
	 * @param string        $stylesheet       Theme stylesheet.
	 * @return bool|WP_Error
	 */
	public function guard_theme_switching( $met_requirements, $stylesheet ) {

		if ( ! $this->guard->is_theme_operations_protected() ) {

			if ( $this->guard->is_theme_operation_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected theme switch was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'theme_guard', 'switch', $stylesheet );
			}

			return $met_requirements;
		}
		
		// Notify ClientGuard Sentinel that a protected theme switch was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'theme_guard', 'switch', $stylesheet );

		return new WP_Error(
			'pcgd_theme_switching_blocked',
			__( 'Theme switching is not allowed.', 'plugiva-clientguard' )
		);
	}

	/**
	 * Guard direct updates to active theme state options.
	 *
	 * @since 1.7.2
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Old option value.
	 * @param string $option    Option name.
	 * @return mixed
	 */
	public function guard_active_theme_option_update( $value, $old_value, $option ) {

		if ( ! in_array( $option, array( 'template', 'stylesheet' ), true ) ) {
			return $value;
		}

		// Do not log or block an update that makes no change.
		if ( $value === $old_value ) {
			return $value;
		}

		if ( ! $this->guard->is_theme_operations_protected() ) {

			if (
				$this->guard->is_theme_operation_protection_enabled()
				&& PCGD_Core_Plugin::should_bypass_protection()
			) {
				do_action( 'pcgd_protection_bypassed', 'theme_guard', 'update', $option );
			}

			return $value;
		}

		do_action( 'pcgd_protection_blocked', 'theme_guard', 'update', $option );

		return $old_value;
	}
	
	/**
	 * Protect active theme state options from deletion.
	 *
	 * @since 1.7.2
	 *
	 * @param string $option Option name.
	 * @return void
	 */
	public function protect_active_theme_option_delete( $option ) {

		$option = sanitize_key( $option );

		if ( ! in_array( $option, array( 'template', 'stylesheet' ), true ) ) {
			return;
		}

		if ( ! $this->guard->is_theme_operations_protected() ) {

			if (
				$this->guard->is_theme_operation_protection_enabled()
				&& PCGD_Core_Plugin::should_bypass_protection()
			) {
				do_action( 'pcgd_protection_bypassed', 'theme_guard', 'delete', $option );
			}

			return;
		}

		do_action( 'pcgd_protection_blocked', 'theme_guard', 'delete', $option );

		PCGD_Core_Plugin::block_protected_option_deletion( $option );
	}

}