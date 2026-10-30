<?php
/**
 * Defines content types supported by ClientGuard protection.
 *
 * @package Plugiva_ClientGuard
 * @since 1.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determines which registered post types ClientGuard can protect.
 */
final class PCGD_Protected_Content_Types {

	/**
	 * Get all supported content types.
	 *
	 * Pages remain supported by default. Extensions may add
	 * additional eligible post types through the filter.
	 *
	 * @return string[] Supported post type identifiers.
	 */
	public static function get_supported_types() {

		$types = apply_filters(
			'pcgd_protected_content_types',
			array( 'page' )
		);

		if ( ! is_array( $types ) ) {
			$types = array();
		}

		/*
		 * Preserve the Free plugin's baseline support for Pages.
		 */
		$types[] = 'page';

		$supported = array();

		foreach ( $types as $post_type ) {

			if ( ! is_string( $post_type ) ) {
				continue;
			}

			if (
				'' === $post_type
				|| sanitize_key( $post_type ) !== $post_type
			) {
				continue;
			}

			if ( ! self::is_supported_type( $post_type ) ) {
				continue;
			}

			$supported[] = $post_type;
		}

		return array_values( array_unique( $supported ) );
	}

	/**
	 * Determine whether a registered post type is eligible.
	 *
	 * This validates the registration requirements needed by
	 * ClientGuard's intended capability-based enforcement.
	 *
	 * @param string $post_type Post type identifier.
	 * @return bool Whether the type is eligible.
	 */
	public static function is_supported_type( $post_type ) {

		if (
			! is_string( $post_type )
			|| '' === $post_type
			|| sanitize_key( $post_type ) !== $post_type
		) {
			return false;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			return false;
		}

		/*
		 * Internal WordPress types are not eligible.
		 * Posts and Pages are the supported built-in types.
		 */
		if (
			! empty( $post_type_object->_builtin )
			&& ! in_array(
				$post_type,
				array( 'post', 'page' ),
				true
			)
		) {
			return false;
		}

		/*
		 * The type must have an admin interface.
		 */
		if ( empty( $post_type_object->show_ui ) ) {
			return false;
		}

		/*
		 * Require WordPress to map individual-item meta-capabilities.
		 * Without this, our capability-based enforcement strategy
		 * cannot safely assume that individual-item checks are mapped.
		 */
		if ( empty( $post_type_object->map_meta_cap ) ) {
			return false;
		}

		/*
		 * Validate the individual-item capability configuration.
		 */
		if (
			! isset(
				$post_type_object->cap->edit_post,
				$post_type_object->cap->delete_post
			)
		) {
			return false;
		}

		if (
			! is_string( $post_type_object->cap->edit_post )
			|| '' === $post_type_object->cap->edit_post
			|| ! is_string( $post_type_object->cap->delete_post )
			|| '' === $post_type_object->cap->delete_post
		) {
			return false;
		}

		return true;
	}
}