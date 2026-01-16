<?php
/**
 * Admin settings handler.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Settings {

	/**
	 * Option name used to store settings.
	 */
	const OPTION_NAME = 'pcg_settings';

	/**
	 * Register the settings page.
	 */
	public function register_menu() {

		add_options_page(
			esc_html__( 'ClientGuard', 'plugiva-clientguard' ),
			esc_html__( 'ClientGuard', 'plugiva-clientguard' ),
			'manage_options',
			'plugiva-clientguard',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {

		register_setting(
			'pcg_settings_group',
			self::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		// Ensure defaults exist.
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $this->get_default_settings() );
		}
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'hide_menus'            => array(),
			'lock_theme_switch'     => true,
			'lock_plugin_install'   => true,
			'allow_plugin_toggle'   => true,
			'protected_content'     => array(),
			'admin_notice_text'     => esc_html__(
				'Some site settings are managed to keep things running smoothly.',
				'plugiva-clientguard'
			),
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {

		$defaults = $this->get_default_settings();
		$output   = array();

		$output['hide_menus'] = isset( $input['hide_menus'] ) && is_array( $input['hide_menus'] )
			? array_map( 'sanitize_text_field', $input['hide_menus'] )
			: array();

		$output['lock_theme_switch'] = isset( $input['lock_theme_switch'] )
			? (bool) $input['lock_theme_switch']
			: $defaults['lock_theme_switch'];

		$output['lock_plugin_install'] = isset( $input['lock_plugin_install'] )
			? (bool) $input['lock_plugin_install']
			: $defaults['lock_plugin_install'];

		$output['allow_plugin_toggle'] = isset( $input['allow_plugin_toggle'] )
			? (bool) $input['allow_plugin_toggle']
			: $defaults['allow_plugin_toggle'];

		$output['protected_content'] = isset( $input['protected_content'] ) && is_array( $input['protected_content'] )
			? array_map( 'absint', $input['protected_content'] )
			: array();

		$output['admin_notice_text'] = isset( $input['admin_notice_text'] )
			? sanitize_text_field( $input['admin_notice_text'] )
			: $defaults['admin_notice_text'];

		return $output;
	}

	/**
	 * Render settings page (placeholder).
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugiva ClientGuard', 'plugiva-clientguard' ); ?></h1>
			<p>
				<?php echo esc_html__(
					'ClientGuard is active. Settings UI will be available here.',
					'plugiva-clientguard'
				); ?>
			</p>
		</div>
		<?php
	}
}
