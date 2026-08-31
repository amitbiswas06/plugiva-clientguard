<?php
/**
 * Theme deletion operational guard.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Theme_Deletion_Sentinel {

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
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_action(
			'delete_theme',
			$this,
			'guard_theme_deletion',
			10,
			1
		);
	}

	/**
	 * Guard protected theme deletion.
	 *
	 * @param string $stylesheet Theme stylesheet.
	 * @return void
	 */
	public function guard_theme_deletion( $stylesheet ) {

		/*
		 * Only act when theme operations are protected.
		 */
		if ( ! $this->guard->is_theme_operations_protected() ) {

			if ( $this->guard->is_theme_operation_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected theme deletion was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'theme_guard', 'delete', $stylesheet );
			}

			return;
		}

		global $wp_filesystem;

		$wp_filesystem = new PCGD_Theme_Filesystem_Proxy(
			$wp_filesystem,
			$stylesheet
		);
	}
}