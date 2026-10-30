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
		$loader->add_action( 'wp_ajax_pcgd_add_protected_content', $this, 'add_protected_content' ); // @since 1.8.0
		$loader->add_action( 'wp_ajax_pcgd_remove_protected_content', $this, 'remove_protected_content' ); // @since 1.8.0
		$loader->add_action( 'wp_ajax_pcgd_remove_all_protected_content', $this, 'remove_all_protected_content' ); // @since 1.8.0
	}

	/**
	 * Search supported content by title.
	 *
	 * @since 1.8.0
	 */
	public function search_pages() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'pcgd_admin_nonce', 'nonce' );

		$term = isset( $_POST['term'] )
			? sanitize_text_field( wp_unslash( $_POST['term'] ) )
			: '';

		if ( mb_strlen( $term ) < 3 ) {
			wp_send_json_success( array() );
		}

		$post_type = isset( $_POST['post_type'] )
			? sanitize_key( wp_unslash( $_POST['post_type'] ) )
			: 'page';

		$supported_types = PCGD_Protected_Content_Types::get_supported_types();

		if ( ! in_array( $post_type, $supported_types, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'This content type is not supported.', 'plugiva-clientguard' ),
				),
				400
			);
		}

		$items = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				's'              => $term,
				'search_columns' => array( 'post_title' ),
				'posts_per_page' => 10,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$results = array();

		foreach ( $items as $item ) {

			$post_type_object = get_post_type_object( $item->post_type );

			$type_label = (
				$post_type_object
				&& isset( $post_type_object->labels->singular_name )
			)
				? $post_type_object->labels->singular_name
				: $item->post_type;

			$results[] = array(
				'id'         => (int) $item->ID,
				'title'      => esc_html( get_the_title( $item->ID ) ),
				'link'       => esc_url( get_permalink( $item->ID ) ),
				'post_type'  => $item->post_type,
				'type_label' => esc_html( $type_label ),
				'status'     => $item->post_status,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * Add selected content to the protected-content list.
	 *
	 * Validates each submitted content ID independently, preserves existing
	 * protected IDs and unrelated settings, and reports partial success.
	 *
	 * @since 1.8.0
	 */
	public function add_protected_content() {

		/*
		* Only administrators who can manage plugin settings may modify
		* the protected-content list.
		*/
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to change protected content.', 'plugiva-clientguard' ),
				),
				403
			);
		}

		// Verify that the request originates from an authorized admin action.
		check_ajax_referer( 'pcgd_admin_nonce', 'nonce' );

		/*
		* The request must contain an array of selected content IDs.
		* Unslash the array before validating its individual values.
		*/
		if (
			! isset( $_POST['post_ids'] )
			|| ! is_array( $_POST['post_ids'] )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'No content was selected.', 'plugiva-clientguard' ),
				),
				400
			);
		}

		$post_ids = wp_unslash( $_POST['post_ids'] );

		/*
		* Retrieve the existing settings without rebuilding the entire
		* option. This allows unrelated settings to remain unchanged.
		*/
		$settings = get_option( PCGD_Admin_Settings::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$existing_ids = isset( $settings['protected_content'] )
			&& is_array( $settings['protected_content'] )
				? $settings['protected_content']
				: array();

		$protected_ids = array();
		$protected_lookup = array();

		/*
		* Preserve existing positive IDs, including IDs whose content
		* types may not currently be supported or registered.
		*
		* The lookup array makes duplicate checks efficient when processing
		* multiple selections.
		*/
		foreach ( $existing_ids as $existing_id ) {

			$existing_id = absint( $existing_id );

			if ( ! $existing_id || isset( $protected_lookup[ $existing_id ] ) ) {
				continue;
			}

			$protected_ids[] = $existing_id;
			$protected_lookup[ $existing_id ] = true;
		}

		$added_ids     	= array();
		$rejected_ids  	= array();
		$added_items 	= array();
		$rejected_count = 0;

		$supported_types = PCGD_Protected_Content_Types::get_supported_types();

		/*
		* Validate each submitted ID independently so that an invalid
		* selection does not prevent other valid selections from succeeding.
		*/
		foreach ( $post_ids as $post_id ) {

			/*
			* Accept positive integer IDs or strings containing only
			* positive integer digits. Reject malformed values instead
			* of allowing absint() to turn negative values into valid IDs.
			*/
			if (
				! is_int( $post_id )
				&& ! (
					is_string( $post_id )
					&& preg_match( '/^[1-9][0-9]*$/D', $post_id )
				)
			) {
				$rejected_count++;
				continue;
			}

			if ( (int) $post_id <= 0 ) {
				$rejected_count++;
				continue;
			}

			$post_id = (int) $post_id;

			// Ignore duplicate selections and content already protected.
			if ( isset( $protected_lookup[ $post_id ] ) ) {
				continue;
			}

			$post = get_post( $post_id );

			/*
			* New additions must exist, belong to an eligible content type,
			* and be editable by the current user.
			*/
			if (
				! $post
				|| ! in_array( $post->post_type, $supported_types, true )
				|| ! current_user_can( 'edit_post', $post_id )
			) {
				$rejected_ids[] = $post_id;
				$rejected_count++;
				continue;
			}

			// Add the validated ID to both the ordered list and lookup.
			$protected_ids[] = $post_id;
			$protected_lookup[ $post_id ] = true;
			$added_ids[] = $post_id;

			/*
			* Collect display information for the newly protected item.
			*/
			$post_type_object = get_post_type_object( $post->post_type );

			$type_label = (
				$post_type_object
				&& isset( $post_type_object->labels->name )
			)
				? $post_type_object->labels->name
				: $post->post_type;

			$added_items[] = array(
				'id'         => $post_id,
				'title'      => get_the_title( $post_id ),
				'permalink'  => get_permalink( $post_id ),
				'post_type'  => $post->post_type,
				'type_label' => $type_label,
			);
		}

		/*
		* Persist only when new content has been added. All other settings
		* remain as retrieved from the existing option.
		*/
		if ( ! empty( $added_ids ) ) {

			$settings['protected_content'] = $protected_ids;

			$updated = update_option(
				PCGD_Admin_Settings::OPTION_NAME,
				$settings
			);

			/*
			* update_option() returns false both when an update fails
			* and when the value is unchanged. Verify the stored state
			* before reporting a persistence failure.
			*/
			if ( false === $updated || true === $updated ) {

				$saved_settings = get_option(
					PCGD_Admin_Settings::OPTION_NAME,
					array()
				);

				$saved_ids = (
					is_array( $saved_settings )
					&& isset( $saved_settings['protected_content'] )
					&& is_array( $saved_settings['protected_content'] )
				)
					? array_map( 'absint', $saved_settings['protected_content'] )
					: array();

				$missing_ids = array_diff( $added_ids, $saved_ids );

				if ( ! empty( $missing_ids ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'The protected-content list could not be saved. Please try again.', 'plugiva-clientguard' ),
						),
						500
					);
				}
			}
		}

		/*
		* Report the outcome consistently. The rejected count includes
		* malformed submissions; rejected_ids contains identifiable IDs
		* that failed content or permission validation.
		*/
		if ( $rejected_count > 0 ) {

			wp_send_json_success(
				array(
					'added_ids'     	=> $added_ids,
					'added_items' 		=> $added_items,
					'rejected_ids'  	=> $rejected_ids,
					'rejected_count' 	=> $rejected_count,
					'message'       	=> sprintf(
						/* translators: 1: Number of items added, 2: Number of items rejected. */
						__( '%1$d item(s) protected; %2$d item(s) could not be protected.', 'plugiva-clientguard' ),
						count( $added_ids ),
						$rejected_count
					),
				)
			);
		}

		if ( empty( $added_ids ) ) {
			wp_send_json_success(
				array(
					'added_ids'      => array(),
					'added_items' 	 => array(),
					'rejected_ids'   => array(),
					'rejected_count' => 0,
					'message'        => __( 'All selected content is already protected.', 'plugiva-clientguard' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'added_ids'      => $added_ids,
				'added_items' 	 => $added_items,
				'rejected_ids'   => array(),
				'rejected_count' => 0,
				'message'        => sprintf(
					/* translators: %d: Number of items added. */
					_n(
						'%d item protected successfully.',
						'%d items protected successfully.',
						count( $added_ids ),
						'plugiva-clientguard'
					),
					count( $added_ids )
				),
			)
		);
	}

	/**
	 * Remove protection from a content item.
	 *
	 * @since 1.8.0
	 */
	public function remove_protected_content() {

		/*
		* Verify the current user's permission.
		*/
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to manage protected content.', 'plugiva-clientguard' ),
				),
				403
			);
		}

		/*
		* Verify the AJAX nonce.
		*/
		check_ajax_referer( 'pcgd_admin_nonce', 'nonce' );

		/*
		* Validate the requested content ID.
		*/
		$post_id = isset( $_POST['post_id'] ) ? wp_unslash( $_POST['post_id'] ) : null;

		if (
			! is_scalar( $post_id )
			|| ! preg_match( '/^[1-9][0-9]*$/D', (string) $post_id )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid content ID.', 'plugiva-clientguard' ),
				),
				400
			);
		}

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid content ID.', 'plugiva-clientguard' ),
				),
				400
			);
		}

		/*
		* Load the existing settings without replacing unrelated values.
		*/
		$settings = get_option( PCGD_Admin_Settings::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to load ClientGuard settings.', 'plugiva-clientguard' ),
				),
				500
			);
		}

		if (
			! isset( $settings['protected_content'] )
			|| ! is_array( $settings['protected_content'] )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'This content is not currently protected.', 'plugiva-clientguard' ),
				),
				404
			);
		}

		/*
		* Remove only the requested ID.
		*
		* Keep other valid IDs, including content types that may not
		* currently be available for protection.
		*/
		$protected_ids = array();
		$found         = false;

		foreach ( $settings['protected_content'] as $existing_id ) {

			$existing_id = absint( $existing_id );

			if ( ! $existing_id ) {
				continue;
			}

			if ( $existing_id === $post_id ) {
				$found = true;
				continue;
			}

			if ( ! in_array( $existing_id, $protected_ids, true ) ) {
				$protected_ids[] = $existing_id;
			}
		}

		if ( ! $found ) {
			wp_send_json_error(
				array(
					'message' => __( 'This content is not currently protected.', 'plugiva-clientguard' ),
				),
				404
			);
		}

		/*
		* Update only the protected-content setting.
		*/
		$settings['protected_content'] = $protected_ids;

		update_option( PCGD_Admin_Settings::OPTION_NAME, $settings );

		/*
		* Verify the saved state rather than relying only on the
		* return value of update_option().
		*/
		$saved_settings = get_option( PCGD_Admin_Settings::OPTION_NAME, array() );

		if (
			! is_array( $saved_settings )
			|| ! isset( $saved_settings['protected_content'] )
			|| ! is_array( $saved_settings['protected_content'] )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to save the protection changes.', 'plugiva-clientguard' ),
				),
				500
			);
		}

		$saved_ids = array_map( 'absint', $saved_settings['protected_content'] );

		if ( in_array( $post_id, $saved_ids, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to remove protection from this content.', 'plugiva-clientguard' ),
				),
				500
			);
		}

		/*
		* Report success.
		*/
		$post  = get_post( $post_id );
		$title = $post ? get_the_title( $post_id ) : __( 'selected content', 'plugiva-clientguard' );

		wp_send_json_success(
			array(
				'removed_id'     => $post_id,
				'message'        => sprintf(
					/* translators: %s: Content title. */
					__( 'Protection removed from "%s".', 'plugiva-clientguard' ),
					$title
				),
				'remaining_count' => count( array_filter( $saved_ids ) ),
			)
		);
	}

	/**
	 * Remove protection from all content items.
	 *
	 * @since 1.8.0
	 */
	public function remove_all_protected_content() {

		/*
		* Verify the current user's permission.
		*/
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to manage protected content.', 'plugiva-clientguard' ),
				),
				403
			);
		}

		/*
		* Verify the AJAX nonce.
		*/
		check_ajax_referer( 'pcgd_admin_nonce', 'nonce' );

		/*
		* Load the existing settings.
		*/
		$settings = get_option( PCGD_Admin_Settings::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to load ClientGuard settings.', 'plugiva-clientguard' ),
				),
				500
			);
		}

		/*
		* Check whether there is any protected content to remove.
		*/
		if (
			! isset( $settings['protected_content'] )
			|| ! is_array( $settings['protected_content'] )
			|| empty( $settings['protected_content'] )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'There is no protected content to remove.', 'plugiva-clientguard' ),
				),
				400
			);
		}

		/*
		* Clear only the protected-content setting.
		* Preserve all other ClientGuard settings.
		*/
		$settings['protected_content'] = array();

		update_option( PCGD_Admin_Settings::OPTION_NAME, $settings );

		/*
		* Verify the saved state.
		*/
		$saved_settings = get_option( PCGD_Admin_Settings::OPTION_NAME, array() );

		if (
			! is_array( $saved_settings )
			|| ! isset( $saved_settings['protected_content'] )
			|| ! is_array( $saved_settings['protected_content'] )
			|| ! empty( $saved_settings['protected_content'] )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to remove protection from all content.', 'plugiva-clientguard' ),
				),
				500
			);
		}

		/*
		* Report success.
		*/
		wp_send_json_success(
			array(
				'message' => __( 'Protection removed from all content.', 'plugiva-clientguard' ),
			)
		);
	}

}
