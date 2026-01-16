<?php
/**
 * Content protection guard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Content_Guard {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcg_settings';

	/**
	 * Protected post meta key.
	 */
	const META_KEY = '_pcg_protected';

	/**
	 * Register hooks.
	 *
	 * @param PCG_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_filter( 'map_meta_cap', $this, 'protect_content', 10, 4 );
	}

	/**
	 * Prevent editing or deleting protected content.
	 *
	 * @param array  $caps    Primitive caps.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments (post ID usually at index 0).
	 * @return array
	 */
	/* public function protect_content( $caps, $cap, $user_id, $args ) {

		// Only care about post capabilities.
		if ( empty( $args[0] ) ) {
			return $caps;
		}

		$post_id = absint( $args[0] );

		if ( ! $post_id ) {
			return $caps;
		}

		// Never restrict network super admins.
		if ( is_multisite() && is_super_admin( $user_id ) ) {
			return $caps;
		}

		$settings = get_option( self::OPTION_NAME );

		if ( empty( $settings['protected_content'] ) || ! is_array( $settings['protected_content'] ) ) {
			return $caps;
		}

		if ( ! in_array( $post_id, $settings['protected_content'], true ) ) {
			return $caps;
		}

		// Block delete & edit actions.
		$blocked_caps = array(
			'delete_post',
			'delete_page',
			'edit_post',
			'edit_page',
		);

		if ( in_array( $cap, $blocked_caps, true ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	} */

    public function protect_content( $caps, $cap, $user_id, $args ) {

        $relevant_caps = array(
            'edit_post',
            'edit_page',
            'delete_post',
            'delete_page',
        );

        if ( ! in_array( $cap, $relevant_caps, true ) ) {
            return $caps;
        }

        if ( empty( $args[0] ) || ! is_numeric( $args[0] ) ) {
            return $caps;
        }

        $post_id = (int) $args[0];

        // Never restrict network super admins.
        if ( is_multisite() && is_super_admin( $user_id ) ) {
            return $caps;
        }

        $settings = get_option( self::OPTION_NAME );

        if ( empty( $settings['protected_content'] ) || ! is_array( $settings['protected_content'] ) ) {
            return $caps;
        }

        if ( ! in_array( $post_id, $settings['protected_content'], true ) ) {
            return $caps;
        }

        // Block edit & delete.
        return array( 'do_not_allow' );
    }

}
