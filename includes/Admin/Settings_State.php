<?php
/**
 * Settings State Resolver.
 *
 * Determines effective UI state for settings fields.
 *
 * @package Plugiva_ClientGuard
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Settings_State {

    /**
     * Get effective state for a setting key.
     *
     * @param string $key      Setting key.
     * @param array  $settings Stored settings.
     * @return array {
     *     @type bool   $value
     *     @type bool   $disabled
     *     @type string $note
     * }
     */
    public static function get( $key, $settings ) {

        $value    = ! empty( $settings[ $key ] );
        $disabled = false;
        $note     = '';

        // Config lock (highest priority)
        if ( defined( 'PCGD_LOCK_CLIENT_MODE' ) && PCGD_LOCK_CLIENT_MODE ) {

            if ( 'client_mode' === $key ) {
                return array(
                    'value'    => true,
                    'disabled' => true,
                    'note'     => esc_html__( 'locked via configuration.', 'plugiva-clientguard' ),
                );
            }

            if ( in_array( $key, array(
                'lock_theme_switch',
                'lock_plugin_install',
                'allow_plugin_toggle',
                'protect_site_urls',
            ), true ) ) {

                return array(
                    'value'    => ( 'allow_plugin_toggle' === $key ) ? false : true,
                    'disabled' => true,
                    'note'     => esc_html__( 'controlled by Client Mode (locked via configuration).', 'plugiva-clientguard' ),
                );
            }
        }

        // Client Mode override
        if ( PCGD_Core_Plugin::is_client_mode() ) {

            if ( in_array( $key, array(
                'lock_theme_switch',
                'lock_plugin_install',
                'allow_plugin_toggle',
                'protect_site_urls',
            ), true ) ) {

                return array(
                    'value'    => ( 'allow_plugin_toggle' === $key ) ? false : true,
                    'disabled' => true,
                    'note'     => esc_html__( 'controlled by Client Mode.', 'plugiva-clientguard' ),
                );
            }
        }

        // Dependency rule
        if ( 'allow_plugin_toggle' === $key && empty( $settings['lock_plugin_install'] ) ) {
            return array(
                'value'    => $value,
                'disabled' => true,
                'note'     => esc_html__(
                    'Enable "Lock Plugin Installation" to control plugin activation.',
                    'plugiva-clientguard'
                ),
            );
        }

        return array(
            'value'    => $value,
            'disabled' => $disabled,
            'note'     => $note,
        );
    }


    /**
     * Get effective state for menu visibility item.
     *
     * @param string $slug Menu slug.
     * @param array  $settings Settings array.
     * @return array
     */
    public static function get_menu_state( $slug, $settings ) {

        $hidden = ! empty( $settings['hide_menus'] )
            ? (array) $settings['hide_menus']
            : array();

        $is_checked = in_array( $slug, $hidden, true );

        $is_client_mode   = PCGD_Core_Plugin::is_client_mode();
        $is_config_locked = defined( 'PCGD_LOCK_CLIENT_MODE' ) && PCGD_LOCK_CLIENT_MODE;

        $client_locked = array( 'plugins.php', 'themes.php', 'tools.php' );

        // Add ACF dynamically (if used elsewhere, optional)
        if ( function_exists( 'acf' ) ) {
            $client_locked[] = 'acf';
        }

        // Config lock (highest priority)
        if ( $is_config_locked && in_array( $slug, $client_locked, true ) ) {
            return array(
                'checked'  => true,
                'disabled' => true,
                'note'     => esc_html__( 'controlled by Client Mode (locked via configuration).', 'plugiva-clientguard' ),
            );
        }

        // Client Mode
        if ( $is_client_mode && in_array( $slug, $client_locked, true ) ) {
            return array(
                'checked'  => true,
                'disabled' => true,
                'note'     => esc_html__( 'controlled by Client Mode.', 'plugiva-clientguard' ),
            );
        }

        return array(
            'checked'  => $is_checked,
            'disabled' => false,
            'note'     => '',
        );
    }

}