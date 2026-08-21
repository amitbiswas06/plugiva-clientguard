<?php
/**
 * Dashboard governance.
 *
 * @package Plugiva_ClientGuard
 * @since 1.5.2
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Dashboard_Guard {

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

		$loader->add_action( 'wp_dashboard_setup', $this, 'manage_dashboard_widgets', 999 );
	}

    /**
     * Manage dashboard widget visibility.
     *
     * @return void
     */
    public function manage_dashboard_widgets() {

        if ( $this->should_hide_activity_widgets() ) {

            remove_meta_box(
                'dashboard_activity',
                'dashboard',
                'normal'
            );

            remove_meta_box(
                'dashboard_right_now',
                'dashboard',
                'normal'
            );
        }

        if ( PCGD_Core_Plugin::is_client_mode() ) {

            remove_meta_box(
                'wpai_status',
                'dashboard',
                'normal'
            );

            remove_meta_box(
                'wpai_capabilities',
                'dashboard',
                'normal'
            );
        }
    }

    /**
     * Determine whether activity-related dashboard widgets
     * should be hidden.
     *
     * @return bool
     */
    private function should_hide_activity_widgets() {

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {
            return false;
        }

        if ( PCGD_Core_Plugin::is_client_mode() ) {
            return true;
        }

        $settings = get_option(
            self::OPTION_NAME,
            array()
        );

        return ! empty( $settings['hide_menus'] );
    }

}