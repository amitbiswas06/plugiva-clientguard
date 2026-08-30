<?php
/**
 * Plugin Installation Sentinel.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Plugin_Installation_Sentinel {

	/**
	 * Plugin Guard instance.
	 *
	 * @var PCGD_Admin_Plugin_Guard
	 */
	private $guard;

	/**
	 * Constructor.
	 *
	 * @param PCGD_Admin_Plugin_Guard $guard Plugin Guard instance.
	 */
	public function __construct( $guard ) {
		$this->guard = $guard;
	}

	/**
	 * Register operational installation protection.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {

		$loader->add_filter(
			'upgrader_source_selection',
			$this,
			'guard_plugin_installation',
			10,
			4
		);
	}

	/**
	 * Block protected plugin installations.
	 *
	 * @param string    $source        Selected source directory.
	 * @param string    $remote_source Temporary working directory.
	 * @param WP_Upgrader $upgrader    Upgrader instance.
	 * @param array     $hook_extra    Upgrade context.
	 * @return string|WP_Error
	 */
	public function guard_plugin_installation(
		$source,
		$remote_source,
		$upgrader,
		$hook_extra
	) {

		/*
		 * Only act when plugin operations are protected.
		 */
		if ( ! $this->guard->is_plugin_operations_protected() ) {
			return $source;
		}

		/*
		 * Only target fresh plugin installations.
		 *
		 * Plugin updates do not provide this installation context.
		 */
		if (
			! isset( $hook_extra['type'], $hook_extra['action'] )
			||
			'plugin' !== $hook_extra['type']
			||
			'install' !== $hook_extra['action']
		) {
			return $source;
		}

		/*
		* Create the installation-blocked error.
		*/
		$error = new WP_Error(
			'pcgd_plugin_installation_blocked',
			__( 'Plugin installation is not allowed.', 'plugiva-clientguard' )
		);

		/*
		* Remove the unpacked temporary installation package.
		*
		* This is the working directory WordPress would normally
		* clean after installation.
		*/
		global $wp_filesystem;

		$deleted = $wp_filesystem->delete(
			$remote_source,
			true
		);

		// Notify ClientGuard Sentinel that a protected plugin installation was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'plugin_guard', 'install', null );

		/*
		* Installation must never proceed, regardless of whether
		* temporary-directory cleanup succeeded.
		*/
		$upgrader->result = $error;

		return $error;
	}
}