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

		$loader->add_filter(
			'validate_theme_requirements',
			$this,
			'guard_theme_switching',
			10,
			2
		);
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
			return $met_requirements;
		}

		return new WP_Error(
			'pcgd_theme_switching_blocked',
			__( 'Theme switching is not allowed.', 'plugiva-clientguard' )
		);
	}
}