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

}