<?php
/**
 * ClientGuard settings event dispatcher.
 *
 * Observes stored ClientGuard settings changes and translates them
 * into internal ClientGuard actions.
 *
 * @package Plugiva_ClientGuard
 * @since 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCGD_Core_Settings_Events {

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Plugin loader.
	 * @return void
	 */
	public function register_hooks( $loader ) {

		$loader->add_action( 'update_option_pcgd_settings', $this, 'handle_settings_update', 10, 3 );
	}

	/**
	 * Handle ClientGuard settings updates.
	 *
	 * @param mixed  $old_value Previous option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option    Option name.
	 * @return void
	 */
	public function handle_settings_update( $old_value, $new_value, $option ) {

        // Client Mode changes are recorded independently because it governs
        // multiple General Settings at once.
        $old_client_mode = ! empty( $old_value['client_mode'] );
        $new_client_mode = ! empty( $new_value['client_mode'] );

        if ( $old_client_mode !== $new_client_mode ) {
            do_action(
                'pcgd_client_mode_changed',
                $new_client_mode,
                $old_client_mode
            );
        }

        // General Settings are only recorded as independent changes when
        // Client Mode is inactive before and after the update. This prevents
        // Client Mode transitions from producing secondary protection events.
        if ( ! $old_client_mode && ! $new_client_mode ) {

            $this->maybe_dispatch_protection_change(
                'theme_guard',
                ! empty( $new_value['lock_theme_switch'] ),
                ! empty( $old_value['lock_theme_switch'] )
            );

            $this->maybe_dispatch_protection_change(
                'appearance_guard',
                ! empty( $new_value['lock_appearance_management'] ),
                ! empty( $old_value['lock_appearance_management'] )
            );

            $this->maybe_dispatch_protection_change(
                'plugin_guard',
                ! empty( $new_value['lock_plugin_install'] ),
                ! empty( $old_value['lock_plugin_install'] )
            );

            // Plugin Activation is a separate policy within Plugin Guard.
            // Record changes to the policy itself rather than effective
            // protection changes caused by enabling or disabling Plugin Guard.
            $old_plugin_activation_allowed = ! empty( $old_value['allow_plugin_toggle'] );
            $new_plugin_activation_allowed = ! empty( $new_value['allow_plugin_toggle'] );

            if ( $old_plugin_activation_allowed !== $new_plugin_activation_allowed ) {
                do_action(
                    'pcgd_plugin_activation_policy_changed',
                    $new_plugin_activation_allowed,
                    $old_plugin_activation_allowed
                );
            }

            $this->maybe_dispatch_protection_change(
                'site_urls',
                ! empty( $new_value['protect_site_urls'] ),
                ! empty( $old_value['protect_site_urls'] )
            );
        }

        // Protected Content is independent from Client Mode. Compare the
        // explicitly configured content IDs and dispatch separate events for
        // additions and removals.
        $old_protected_content = isset( $old_value['protected_content'] ) && is_array( $old_value['protected_content'] )
            ? array_unique( array_map( 'absint', $old_value['protected_content'] ) )
            : array();

        $new_protected_content = isset( $new_value['protected_content'] ) && is_array( $new_value['protected_content'] )
            ? array_unique( array_map( 'absint', $new_value['protected_content'] ) )
            : array();

        $added_content = array_diff( $new_protected_content, $old_protected_content );

        foreach ( $added_content as $post_id ) {
            do_action( 'pcgd_protected_content_added', $post_id );
        }

        $removed_content = array_diff( $old_protected_content, $new_protected_content );

        foreach ( $removed_content as $post_id ) {
            do_action( 'pcgd_protected_content_removed', $post_id );
        }
    }

    /**
     * Dispatch a General Protection state change event when the state changed.
     *
     * @param string $protection Protection identifier.
     * @param bool   $new_state   New protection state.
     * @param bool   $old_state   Previous protection state.
     * @return void
     */
    private function maybe_dispatch_protection_change(
        $protection,
        $new_state,
        $old_state
    ) {

        if ( $old_state === $new_state ) {
            return;
        }

        do_action(
            'pcgd_protection_state_changed',
            $protection,
            $new_state,
            $old_state
        );
    }

}