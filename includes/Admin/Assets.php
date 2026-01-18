<?php
/**
 * Admin Assets handler.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Assets {

	/**
	 * Register hooks.
	 *
	 * @param PCG_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_action(
			'admin_enqueue_scripts',
			$this,
			'enqueue_admin_assets'
		);
	}

	/**
	 * Enqueue admin-only assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {

		// Only load on Settings → ClientGuard.
		if ( 'settings_page_plugiva-clientguard' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'pcg-content-protection',
			PCG_PLUGIN_URL . 'assets/admin/content-protection.js',
			array(),
			microtime(),
			true
		);

		wp_localize_script(
			'pcg-content-protection',
			'pcgAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pcg_admin_nonce' ),
			)
		);
	}
}
