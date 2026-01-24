<?php
/**
 * Admin Assets handler.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Assets {

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
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
			'pcgd-content-protection',
			PCGD_PLUGIN_URL . 'assets/admin/content-protection.js',
			array(),
			PCGD_VERSION,
			true
		);

		wp_localize_script(
			'pcgd-content-protection',
			'pcgdAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pcgd_admin_nonce' ),
			)
		);

        wp_enqueue_style(
            'pcgd-admin',
            PCGD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PCGD_VERSION
        );

	}
}
