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
        $loader->add_filter( 'pre_trash_post', $this, 'block_protected_content_trash', 10, 3 );
        $loader->add_filter( 'pre_delete_post', $this, 'block_protected_content_deletion', 10, 3 );
    }

    /**
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

        if ( ! $this->is_content_protected( $post->ID ) ) {
            return $trash;
        }

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

        if ( ! $this->is_content_protected( $post->ID ) ) {
            return $check;
        }

        return false;
    }

}
