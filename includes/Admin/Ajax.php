<?php
/**
 * AJAX handlers.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Ajax {

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_action( 'wp_ajax_pcgd_search_pages', $this, 'search_pages' );
	}

	/**
	 * Search pages by title.
	 */
	public function search_pages() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'pcgd_admin_nonce', 'nonce' );

		$term = isset( $_POST['term'] )
			? sanitize_text_field( wp_unslash( $_POST['term'] ) )
			: '';

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			's'              => $term,
			'posts_per_page' => 10,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$results = array();

		foreach ( $pages as $page ) {
			$results[] = array(
                'id'    => (int) $page->ID,
                'title' => esc_html( $page->post_title ),
                'link'  => esc_url( get_permalink( $page->ID ) ),
            );
		}

		wp_send_json_success( $results );
	}
}
