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
		$loader->add_filter( 'dashboard_recent_drafts_query_args', $this, 'exclude_protected_recent_drafts' );
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
     * Exclude protected Posts from the dashboard Recent Drafts query.
     *
     * WordPress may receive a null edit link for protected drafts because
     * Content Guard denies the edit_post capability. Excluding those drafts
     * prevents the core widget from passing that null value to esc_url().
     *
     * @since 1.8.0
     *
     * @param array $query_args Recent Drafts query arguments.
     * @return array
     */
    public function exclude_protected_recent_drafts( $query_args ) {

        if (
            ! is_array( $query_args )
            || PCGD_Core_Plugin::should_bypass_protection()
            || PCGD_Core_Plugin::is_protection_relaxed( 'pcgd_content' )
            || ! in_array(
                'post',
                PCGD_Protected_Content_Types::get_supported_types(),
                true
            )
        ) {
            return $query_args;
        }

        $settings = get_option(
            self::OPTION_NAME,
            array()
        );

        if (
            ! is_array( $settings )
            || empty( $settings['protected_content'] )
            || ! is_array( $settings['protected_content'] )
        ) {
            $protected_ids = array();
        } else {
            $protected_ids = array_filter(
                array_map( 'absint', $settings['protected_content'] )
            );
        }

        if ( empty( $protected_ids ) ) {
            return $query_args;
        }

        $excluded_ids = array();

        if ( ! empty( $query_args['post__not_in'] ) ) {
            $existing_excluded_ids = (array) $query_args['post__not_in'];
            $excluded_ids = array_filter(
                array_map( 'absint', $existing_excluded_ids )
            );
        }

        $query_args['post__not_in'] = array_values(
            array_unique(
                array_merge( $excluded_ids, $protected_ids )
            )
        );

        return $query_args;
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