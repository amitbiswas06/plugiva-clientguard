<?php
/**
 * Admin settings renderer helper.
 *
 * @package Plugiva_ClientGuard
 * @since 1.1.0
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Core_Admin_Renderer {

	/**
	 * Render a specific settings section.
	 *
	 * @param string $page       Settings page slug.
	 * @param string $section_id Section ID.
	 */
	public static function render_section( $page, $section_id ) {

		global $wp_settings_sections, $wp_settings_fields;

		if ( empty( $wp_settings_sections[ $page ][ $section_id ] ) ) {
			return;
		}

		$section = $wp_settings_sections[ $page ][ $section_id ];

		// Section title.
		if ( ! empty( $section['title'] ) ) {
			echo '<h2>' . esc_html( $section['title'] ) . '</h2>';
		}

		// Section description callback.
		if ( ! empty( $section['callback'] ) ) {
			call_user_func( $section['callback'] );
		}

		// Fields.
		if ( ! empty( $wp_settings_fields[ $page ][ $section_id ] ) ) {
			echo '<table class="form-table">';
			do_settings_fields( $page, $section_id );
			echo '</table>';
		}
	}
}