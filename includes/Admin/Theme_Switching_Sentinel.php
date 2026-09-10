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

		// Network Admin site settings can change the active theme by updating
		// the site's `template` and `stylesheet` options directly without calling switch_theme method
		$loader->add_filter( 'pre_update_option_template', $this, 'guard_network_theme_option_update', 10, 3 );

		$loader->add_filter( 'pre_update_option_stylesheet', $this, 'guard_network_theme_option_update', 10, 3 );

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
	 * Guard active theme option updates from Network Admin site settings.
	 *
	 * WordPress Network Admin updates the `template` and `stylesheet`
	 * options directly from the site settings screen instead of calling
	 * switch_theme(). Therefore, those updates do not pass through the
	 * normal theme switching validation hook.
	 *
	 * This callback observes the `stylesheet` update as the operational
	 * theme-switch event, while the `template` update is ignored to avoid
	 * recording the same theme switch twice.
	 *
	 * @since 1.7.0
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Old option value.
	 * @param string $option    Option name.
	 * @return mixed
	 */
	public function guard_network_theme_option_update( $value, $old_value, $option ) {

		if ( ! is_network_admin() ) {
			return $value;
		}

		if ( 'stylesheet' !== $option ) {
			return $value;
		}

		$action = isset( $_GET['action'] )
			? sanitize_key( wp_unslash( $_GET['action'] ) )
			: '';

		if ( 'update-site' !== $action ) {
			return $value;
		}

		if ( PCGD_Core_Plugin::should_bypass_protection() ) {

			// Just observe multisite superadmin bypass
			do_action(
				'pcgd_protection_bypassed',
				'theme_guard',
				'switch',
				sanitize_text_field( wp_unslash( $value ) )
			);
		}

		return $value;
	}

}