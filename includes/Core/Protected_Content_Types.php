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
	 * Additional content types requested through the Free–Pro bridge.
	 *
	 * These types are requests only. The authoritative resolver must
	 * validate them before considering them supported.
	 *
	 * @var string[]
	 */
	private static $pro_requested_types = array();

	/**
	 * Whether the Free–Pro content-type integration is active.
	 *
	 * This is runtime-only state. It is not persisted between requests.
	 *
	 * @var bool
	 */
	private static $pro_integration_active = false;

	/**
	 * Initialize the Free–Pro content-type integration.
	 *
	 * Activates the integration for the current request and registers
	 * the requested content types.
	 *
	 * Invalid top-level input leaves the integration inactive.
	 * Individual content types are validated by register_pro_types().
	 *
	 * @param array $post_types Content types requested by Pro.
	 * @return bool Whether the integration was initialized.
	 */
	public static function initialize_pro_integration( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return false;
		}

		self::$pro_integration_active = true;

		self::register_pro_types( $post_types );

		return true;
	}

	/**
	 * Register content types requested by Pro.
	 *
	 * This method only accepts registrations after the integration
	 * has been initialized.
	 *
	 * @param array $post_types Content types requested by Pro.
	 * @return void
	 */
	public static function register_pro_types( $post_types ) {
		if ( ! self::$pro_integration_active ) {
			return;
		}

		if ( ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			if (
				! is_string( $post_type )
				|| '' === $post_type
				|| sanitize_key( $post_type ) !== $post_type
			) {
				continue;
			}

			// Do not automatically accept all registered types.
			if ( 'post' !== $post_type ) {
				continue;
			}

			self::$pro_requested_types[] = $post_type;
		}

		self::$pro_requested_types = array_values(
			array_unique( self::$pro_requested_types )
		);
	}

	/**
	 * Get all supported content types.
	 *
	 * @return string[] Supported post type identifiers.
	 */
	public static function get_supported_types() {

		/*
		* Free supports Pages only.
		*
		* Additional content types must be authorized through the
		* dedicated Free–Pro integration bridge.
		*/
		$types = array( 'page' );

		if ( self::$pro_integration_active ) {
			$types = array_merge(
				$types,
				self::$pro_requested_types
			);
		}

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