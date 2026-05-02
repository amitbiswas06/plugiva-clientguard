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
        // Hooks will be added in next steps.

        $loader->add_filter( 'pre_update_option_siteurl', $this, 'block_siteurl_update', 10, 2 );

        $loader->add_filter( 'pre_update_option_home', $this, 'block_home_update', 10, 2 );

        $loader->add_action( 'load-options-permalink.php', $this, 'block_permalink_page' );

        $loader->add_action( 'admin_head', $this, 'hide_site_url_fields_css' ); // @since 1.4.0
    }

    /**
     * Block access to permalink settings in Client Mode.
     *
     * @return void
     */
    public function block_permalink_page() {

        if ( ! PCGD_Core_Plugin::is_client_mode() ) {
            return;
        }

        // Redirect to dashboard
        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Check if site URL protection is active.
     *
     * @return bool
     */
    public function is_site_url_protected() {

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

}