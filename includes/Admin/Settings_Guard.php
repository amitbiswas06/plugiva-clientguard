<?php
/**
 * Settings Guard.
 *
 * @package Plugiva_ClientGuard
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Settings_Guard {

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

        // Redirect direct access to the permalink page when Client Mode is ON
        $loader->add_action( 'load-options-permalink.php', $this, 'block_permalink_page' );

        foreach ( $this->get_protected_permalink_options() as $option ) {
            $loader->add_filter( 'pre_update_option_' . $option, $this, 'block_permalink_structure_update', 10, 2 );
        }

        $loader->add_action( 'delete_option', $this, 'protect_permalink_option_delete', 10, 1 );

        // block access to AI Connectors page in Client Mode.
        $loader->add_action( 'load-options-connectors.php', $this, 'block_connectors_page' ); // @since 1.5.0

        // block access to AI Settings page ("options-general.php?page=ai-wp-admin") in Client Mode.
        $loader->add_action( 'load-settings_page_ai-wp-admin', $this, 'block_ai_settings_page' ); // @since 1.5.0

        // Suspend WordPress AI runtime features in Client Mode.
        $loader->add_filter( 'wpai_features_enabled', $this, 'filter_ai_runtime_enabled' ); // @since 1.5.0

        $loader->add_filter( 'pre_update_option_siteurl', $this, 'block_siteurl_update', 10, 2 );

        $loader->add_filter( 'pre_update_option_home', $this, 'block_home_update', 10, 2 );

        $loader->add_action( 'admin_head', $this, 'hide_site_url_fields_css' ); // @since 1.4.0

        $loader->add_action( 'delete_option', $this, 'protect_site_url_option_delete', 10, 1 );
    }

    /**
     * Helper
     * Determine whether Client Mode protections should apply.
     *
     * @since 1.7.0
     * @return bool
     */
    private function is_client_mode_protection_active() {

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {
            return false;
        }

        return PCGD_Core_Plugin::is_client_mode();
    }

    /**
     * Helper
     * Get permalink options protected in Client Mode.
     *
     * @return string[] Protected option names.
     * @since 1.7.0
     */
    private function get_protected_permalink_options() {

        $protected_options = apply_filters(
            'pcgd_protected_permalink_options',
            array(
                'permalink_structure',
                'category_base',
                'tag_base',
            )
        );

        if ( ! is_array( $protected_options ) ) {
            return array();
        }

        $protected_options = array_filter(
            array_map( 'sanitize_key', $protected_options )
        );

        return array_values( array_unique( $protected_options ) );
    }

    /**
     * Block access to permalink settings in Client Mode.
     *
     * @return void
     */
    public function block_permalink_page() {

        if ( ! $this->is_client_mode_protection_active() ) {
            return;
        }

        // Redirect to dashboard
        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Block protected permalink option updates in Client Mode.
     *
     * @param mixed $new_value New value.
     * @param mixed $old_value Old value.
     * @return mixed
     */
    public function block_permalink_structure_update( $new_value, $old_value ) {

        if ( ! $this->is_client_mode_protection_active() ) {
            return $new_value;
        }

        // Only block if an actual change is attempted.
        if ( $new_value !== $old_value ) {
            return $old_value;
        }

        return $new_value;
    }

    /**
     * Prevent protected permalink options from being deleted in Client Mode.
     *
     * @param string $option Name of the option being deleted.
     * @return void
     * @since 1.7.0
     */
    public function protect_permalink_option_delete( $option ) {

        $option = sanitize_key( $option );

        if ( '' === $option ) {
            return;
        }

        if ( ! $this->is_client_mode_protection_active() ) {
            return;
        }

        if ( ! in_array( $option, $this->get_protected_permalink_options(), true ) ) {
            return;
        }

        PCGD_Core_Plugin::block_protected_option_deletion( $option );
    }

    /**
     * Block access to AI Connectors page in Client Mode.
     *
     * @return void
     * @since 1.5.0
     */
    public function block_connectors_page() {

        if ( ! $this->is_client_mode_protection_active() ) {
            return;
        }

        // Redirect to dashboard.
        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Block WordPress AI settings page in Client Mode.
     *
     * @return void
     * @since 1.5.0
     */
    public function block_ai_settings_page() {

        if ( ! $this->is_client_mode_protection_active() ) {
            return;
        }

        // Redirect to dashboard.
        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Filter whether WordPress AI runtime features should initialize.
     *
     * Client Mode represents a protected operational environment where
     * advanced automation and AI-powered mutation systems are suspended.
     *
     * This integration uses the official WordPress AI runtime filter
     * and does not modify stored plugin settings or user preferences.
     *
     * @param bool $enabled Whether AI runtime features are enabled.
     *
     * @return bool
     * @since 1.5.0
     */
    public function filter_ai_runtime_enabled( $enabled ) {

        // Suspend AI runtime features in Client Mode.
        if ( $this->is_client_mode_protection_active() ) {
            return false;
        }

        return $enabled;
    }

    /********************************************************************************
     * SITE URLS
    ********************************************************************************/

    /**
     * Helper
     * Check if site URL protection is active.
     *
     * @since 1.7.0
     * @return bool
     */
    public function is_site_url_protected() {

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {
            return false;
        }

        $settings = get_option( self::OPTION_NAME );

        $enabled = ! empty( $settings['protect_site_urls'] );

        // Client Mode override
        if ( PCGD_Core_Plugin::is_client_mode() ) {
            $enabled = true;
        }

        return $enabled;
    }

    /**
     * Block siteurl update when protected.
     *
     * @param string $new_value New value.
     * @param string $old_value Old value.
     * @return string
     */
    public function block_siteurl_update( $new_value, $old_value ) {

        if ( ! $this->is_site_url_protected() ) {
            return $new_value;
        }

        // Only block if actual change attempted
        if ( $new_value !== $old_value ) {
            return $old_value;
        }

        return $new_value;
    }

    /**
     * Block home URL update when protected.
     *
     * @param string $new_value New value.
     * @param string $old_value Old value.
     * @return string
     */
    public function block_home_update( $new_value, $old_value ) {

        if ( ! $this->is_site_url_protected() ) {
            return $new_value;
        }

        if ( $new_value !== $old_value ) {
            return $old_value;
        }

        return $new_value;
    }

    /**
     * Hide site URL fields via CSS when protected.
     *
     * @return void
     * @since 1.4.0
     */
    public function hide_site_url_fields_css() {

        // Only when protection is active
        if ( ! $this->is_site_url_protected() ) {
            return;
        }

        // Strict screen targeting
        $screen = get_current_screen();
        if ( ! $screen || 'options-general' !== $screen->id ) {
            return;
        }

        echo '<style>
            /* Hide Site URL fields (ClientGuard) */
            tr:has(#siteurl),
            tr:has(#home) {
                display: none !important;
            }
        </style>';
    }

    /**
     * Prevent protected Site URL options from being deleted.
     *
     * WordPress does not provide a cancellable filter before an option is
     * deleted. The `delete_option` action fires before the database row is
     * removed, allowing ClientGuard to stop execution before the deletion
     * occurs.
     *
     * @param string $option Name of the option being deleted.
     * @return void
     * @since 1.7.0
     */
    public function protect_site_url_option_delete( $option ) {

        $option = sanitize_key( $option );

        if ( '' === $option ) {
            return;
        }

        if ( ! in_array( $option, array( 'siteurl', 'home' ), true ) ) {
            return;
        }

        if ( ! $this->is_site_url_protected() ) {
            return;
        }

        PCGD_Core_Plugin::block_protected_option_deletion( $option );
    }

}