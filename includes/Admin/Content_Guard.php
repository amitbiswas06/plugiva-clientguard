<?php
/**
 * Content protection guard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Content_Guard {

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

        $loader->add_filter( 'map_meta_cap', $this, 'protect_content', 10, 4 );

        // @since 1.7.0
        $loader->add_action( 'pre_post_update', $this, 'sentinel_protected_content_update', 10, 2 );
        $loader->add_filter( 'pre_trash_post', $this, 'block_protected_content_trash', 10, 3 );
        $loader->add_filter( 'pre_delete_post', $this, 'block_protected_content_deletion', 10, 3 );

        // rest fields for clientguard
        $loader->add_action( 'rest_api_init', $this, 'register_rest_fields' );
    }

    /**
     * Helper
     * Check whether content is explicitly protected.
     *
     * Client Mode automatically adds the front page to the protected content list.
     *
     * @since 1.7.0
     * @param int $post_id Post ID.
     * @return bool
     */
    private function is_content_explicitly_protected( $post_id ) {

        $settings = get_option( self::OPTION_NAME );

        $protected = array();

        if ( ! empty( $settings['protected_content'] ) && is_array( $settings['protected_content'] ) ) {
            $protected = $settings['protected_content'];
        }

        // Client Mode automatically protects the front page.
        if ( PCGD_Core_Plugin::is_client_mode() ) {
            $front_page = get_option( 'page_on_front' );

            if ( $front_page && ! in_array( $front_page, $protected, true ) ) {
                $protected[] = (int) $front_page;
            }
        }

        return in_array( $post_id, $protected, true );
    }

    /**
     * Helper
     * Check whether content is protected.
     *
     * @param int $post_id Post ID.
     * @param int $user_id Optional user ID.
     * @return bool
     */
    private function is_content_protected( $post_id, $user_id = 0 ) {

        if ( PCGD_Core_Plugin::should_bypass_protection( $user_id ) ) {
            return false;
        }

        return $this->is_content_explicitly_protected( $post_id );
    }

    /**
     * Protect selected content from editing and deletion.
     *
     * @param string[] $caps    Required capabilities.
     * @param string   $cap     Requested capability.
     * @param int      $user_id User ID.
     * @param array    $args    Additional arguments.
     * @return string[]
     */
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

        /*
        * Allow REST read requests.
        *
        * The Site Editor uses edit_post capability checks while retrieving
        * pages for editing context. Blocking those checks would cause
        * protected content to disappear from the page list.
        */
        if (
            defined( 'REST_REQUEST' )
            && REST_REQUEST
            && isset( $_SERVER['REQUEST_METHOD'] )
            && 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] )
        ) {
            return $caps;
        }

        if ( empty( $args[0] ) || ! is_numeric( $args[0] ) ) {
            return $caps;
        }

        $post_id = (int) $args[0];

        if ( ! $this->is_content_protected( $post_id, $user_id ) ) {
            return $caps;
        }

        return array( 'do_not_allow' );
    }

    /**
     * Records protected content updates for Sentinel.
     *
     * Records legitimate bypasses performed by trusted administrators and
     * unexpected updates to protected content that reach the post update path.
     *
     * @since 1.7.0
     *
     * @param int   $post_id Post ID.
     * @param array $data    Array of post data.
     * @return void
     */
    public function sentinel_protected_content_update( $post_id, $data ) {

        $post_before = get_post( $post_id );

        // If it's a trash request, return immediately
        if (
            $post_before
            && 'trash' !== $post_before->post_status
            && isset( $data['post_status'] )
            && 'trash' === $data['post_status']
        ) {
            return;
        }

        if ( ! $this->is_content_explicitly_protected( $post_id ) ) {
            return;
        }

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {

            // Notify ClientGuard Sentinel that a protected content update was bypassed.
            do_action( 'pcgd_protection_bypassed', 'content_guard', 'update', $post_id );

            return;
        }

        // Notify ClientGuard Sentinel that protected content was updated
        // without the expected bypass.
        do_action( 'pcgd_protection_violation', 'content_guard', 'update', $post_id );
    }

    /**
     * Block protected content from being moved to Trash.
     *
     * @since 1.7.0
     *
     * @param bool|null $trash           Whether to go forward with trashing.
     * @param WP_Post   $post            Post object.
     * @param string    $previous_status Previous post status.
     * @return bool|null
     */
    public function block_protected_content_trash( $trash, $post, $previous_status ) {

        if ( ! $this->is_content_explicitly_protected( $post->ID ) ) {
            return $trash;
        }

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {

            // Notify ClientGuard Sentinel that protected content trashing was bypassed.
            do_action( 'pcgd_protection_bypassed', 'content_guard', 'trash', $post->ID );

            return $trash;
        }

        // Notify ClientGuard Sentinel that protected content trashing was blocked.
        do_action( 'pcgd_protection_blocked', 'content_guard', 'trash', $post->ID );

        return false;
    }

    /**
     * Block protected content from being permanently deleted.
     *
     * @since 1.7.0
     *
     * @param WP_Post|false|null $check        Whether to go forward with deletion.
     * @param WP_Post            $post         Post object.
     * @param bool               $force_delete Whether to bypass Trash.
     * @return WP_Post|false|null
     */
    public function block_protected_content_deletion( $check, $post, $force_delete ) {

        if ( ! $this->is_content_explicitly_protected( $post->ID ) ) {
            return $check;
        }

        if ( PCGD_Core_Plugin::should_bypass_protection() ) {

            // Notify ClientGuard Sentinel that protected content deletion was bypassed.
            // @since 1.7.0
            do_action( 'pcgd_protection_bypassed', 'content_guard', 'delete', $post->ID );

            return $check;
        }

        // Notify ClientGuard Sentinel that protected content deletion was blocked.
        // @since 1.7.0
        do_action( 'pcgd_protection_blocked', 'content_guard', 'delete', $post->ID );

        return false;
    }

    /**
     * Register ClientGuard REST fields.
     *
     * @since 1.7.0
     * @return void
     */
    public function register_rest_fields() {

        register_rest_field(
            'page',
            'pcgd_protected',
            array(
                'get_callback' => array( $this, 'get_rest_protection_status' ),
                'schema'       => array(
                    'description' => __( 'Whether this page is protected by ClientGuard.', 'plugiva-clientguard' ),
                    'type'        => 'boolean',
                    'context'     => array( 'edit' ),
                    'readonly'    => true,
                ),
            )
        );
    }

    /**
     * Get the ClientGuard protection status for a REST page.
     *
     * @since 1.7.0
     * @param array           $object     REST response object.
     * @param string          $field_name REST field name.
     * @param WP_REST_Request $request    REST request.
     * @return bool
     */
    public function get_rest_protection_status( $object, $field_name, $request ) {

        if ( empty( $object['id'] ) ) {
            return false;
        }

        return $this->is_content_protected( (int) $object['id'] );
    }

}
