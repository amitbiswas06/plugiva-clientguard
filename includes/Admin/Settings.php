<?php
/**
 * Admin settings handler.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Settings {

	/**
	 * Option name used to store settings.
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Settings interface renderer.
	 *
	 * @since 1.8.0
	 * @var PCGD_Admin_Settings_Renderer
	 */
	private $renderer;

	/**
	 * Initialize the settings interface renderer.
	 */
	public function __construct() {
		$this->renderer = new PCGD_Admin_Settings_Renderer();
	}

	/**
	 * Register the settings page.
	 */
	public function register_menu() {

		add_options_page(
			esc_html__( 'ClientGuard', 'plugiva-clientguard' ),
			esc_html__( 'ClientGuard', 'plugiva-clientguard' ),
			'manage_options',
			'plugiva-clientguard',
			array( $this->renderer, 'render_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {

		register_setting(
			'pcgd_settings_group',
			self::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		// Ensure defaults exist.
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $this->get_default_settings() );
		}

		// Client Mode section. 
		// @since 1.1.0
		add_settings_section(
			'pcgd_section_client_mode',
			esc_html__( 'Client Mode', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_client_mode_intro' ),
			'plugiva-clientguard'
		);

		add_settings_section(
			'pcgd_section_general',
			esc_html__('General Protection', 'plugiva-clientguard'),
			'__return_false',
			'plugiva-clientguard'
		);

		add_settings_section(
			'pcgd_section_content',
			esc_html__('Content Protection', 'plugiva-clientguard'),
			'__return_false',
			'plugiva-clientguard'
		);

		add_settings_section(
			'pcgd_section_menu',
			esc_html__( 'Menu Visibility', 'plugiva-clientguard' ),
			'__return_false',
			'plugiva-clientguard'
		);

		// Client Mode is a pre-configured set of protections for client sites.
		// @since 1.1.0
		add_settings_field(
			'client_mode',
			esc_html__( 'Enable Client Mode', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_checkbox' ),
			'plugiva-clientguard',
			'pcgd_section_client_mode',
			array(
				'key'   => 'client_mode',
				'label' => esc_html__( 'Simplify the admin with safe, recommended settings', 'plugiva-clientguard' ),
			)
		);

		add_settings_field(
			'lock_theme_switch',
			esc_html__('Lock Theme Switching', 'plugiva-clientguard'),
			array($this->renderer, 'render_checkbox'),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'lock_theme_switch',
				'label' => esc_html__('Keep the active theme in place to maintain site layout', 'plugiva-clientguard'),
			)
		);

		// New appearance management lock field, @since 1.6.0
		add_settings_field(
			'lock_appearance_management',
			esc_html__( 'Lock Appearance Management', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_checkbox' ),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'lock_appearance_management',
				'label' => esc_html__(
					'Prevent changes to menus, widgets, customization, and appearance-related settings.',
					'plugiva-clientguard'
				),
			)
		);

		add_settings_field(
			'lock_plugin_install',
			esc_html__('Lock Plugin Installation', 'plugiva-clientguard'),
			array($this->renderer, 'render_checkbox'),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'lock_plugin_install',
				'label' => esc_html__('Prevent changes to installed plugins to keep the site stable', 'plugiva-clientguard'),
			)
		);

		add_settings_field(
			'allow_plugin_toggle',
			esc_html__('Allow Plugin Activation', 'plugiva-clientguard'),
			array($this->renderer, 'render_checkbox'),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'allow_plugin_toggle',
				'label' => esc_html__('Allow activating or deactivating installed plugins', 'plugiva-clientguard'),
			)
		);

		// protect site URLs to prevent lockout
		// @since 1.2.0
		add_settings_field(
			'protect_site_urls',
			esc_html__( 'Protect Site URLs', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_checkbox' ),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'protect_site_urls',
				'label' => __( 'Keep <em>WordPress Address</em> and <em>Site Address</em> stable to avoid login or site access issues.', 'plugiva-clientguard' ),
			)
		);

		add_settings_field(
			'protected_content',
			esc_html__( 'Protection Rules', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_protected_content' ),
			'plugiva-clientguard',
			'pcgd_section_content'
		);

		add_settings_field(
			'hide_menus',
			esc_html__( 'Hide Admin Menus', 'plugiva-clientguard' ),
			array( $this->renderer, 'render_menu_hiding' ),
			'plugiva-clientguard',
			'pcgd_section_menu'
		);


	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'client_mode' 					=> false, // @since 1.1.0
			'hide_menus'            		=> array(),
			'lock_theme_switch'     		=> false,
			'lock_appearance_management'	=> false, // @since 1.6.0
			'lock_plugin_install'   		=> false,
			'allow_plugin_toggle'   		=> true,
			'protect_site_urls'     		=> false, // @since 1.2.0
			'protected_content'     		=> array(),
			'admin_notice_text'     		=> esc_html__(
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

		// Get defaults for reference.
		$defaults = $this->get_default_settings();
		$output   = array();

		// Sanitize checkboxes.
		$output['client_mode'] = ! empty( $input['client_mode'] ); // @since 1.1.0

		// Hard lock via config - @since 1.3.0
		if ( defined( 'PCGD_LOCK_CLIENT_MODE' ) && PCGD_LOCK_CLIENT_MODE ) {
			$output['client_mode'] = true;
		}

		$output['lock_theme_switch']   			= ! empty( $input['lock_theme_switch'] );
		$output['lock_appearance_management'] 	= ! empty( $input['lock_appearance_management'] ); // @since 1.6.0
		$output['lock_plugin_install'] 			= ! empty( $input['lock_plugin_install'] );
		$output['allow_plugin_toggle'] 			= ! empty( $input['allow_plugin_toggle'] );
		$output['protect_site_urls'] 			= ! empty( $input['protect_site_urls'] ); // @since 1.2.0

		// Sanitize protected content IDs.
		if ( isset( $input['protected_content'] ) && is_array( $input['protected_content'] ) ) {

			$clean = array();

			foreach ( $input['protected_content'] as $post_id ) {
				$post_id = absint( $post_id );

				if ( $post_id && get_post( $post_id ) && ! in_array( $post_id, $clean, true ) ) {
					$clean[] = $post_id;
				}
			}

			$output['protected_content'] = $clean;

		} else {
			// Preserve existing protected content when this form does not submit it.
			// @since 1.8.0
			$existing_settings = get_option( self::OPTION_NAME, array() );

			$output['protected_content'] = isset( $existing_settings['protected_content'] )
				&& is_array( $existing_settings['protected_content'] )
					? $existing_settings['protected_content']
					: array();
		}

		// Sanitize hidden menus.
		if ( isset( $input['hide_menus'] ) && is_array( $input['hide_menus'] ) ) {
			$output['hide_menus'] = array_map( 'sanitize_text_field', $input['hide_menus'] );
		} else {
			$output['hide_menus'] = array();
		}

		// Sanitize admin notice text.
		$output['admin_notice_text'] = isset( $input['admin_notice_text'] )
			? sanitize_text_field( $input['admin_notice_text'] )
			: $defaults['admin_notice_text'];

		// Enforce Client Mode defaults at save level.
		// @since 1.1.0
		$output = $this->enforce_client_mode_settings( $output );

		return $output;
	}

	/**
	 * Apply Client Mode settings enforcement.
	 *
	 * Forces the settings controlled by Client Mode to their required
	 * values and adds the corresponding admin menu restrictions.
	 *
	 * @since 1.7.0
	 *
	 * @param array $settings Settings to update.
	 * @return array Updated settings.
	 */
	public function enforce_client_mode_settings( $settings ) {

		if ( empty( $settings['client_mode'] ) ) {
			return $settings;
		}

		$settings['lock_theme_switch']          = true;
		$settings['lock_appearance_management'] = true;
		$settings['lock_plugin_install']        = true;
		$settings['allow_plugin_toggle']        = false;
		$settings['protect_site_urls']          = true;

		$client_locked = array(
			'plugins.php',
			'themes.php',
			'tools.php',
		);

		if ( function_exists( 'acf' ) ) {
			$client_locked[] = 'acf';
		}

		$current = isset( $settings['hide_menus'] ) && is_array( $settings['hide_menus'] )
			? $settings['hide_menus']
			: array();

		$settings['hide_menus'] = array_unique(
			array_merge( $current, $client_locked )
		);

		return $settings;
	}

	/**
	 * Handle Sentinel actions.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function handle_sentinel_clear_logs() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'plugiva-clientguard' )
			);
		}

		check_admin_referer(
			'pcgd_clear_sentinel_logs',
			'pcgd_sentinel_nonce'
		);

		$sentinel = new PCGD_Core_Sentinel();

		$sentinel->delete_sentinel_events( get_current_blog_id() );

		wp_safe_redirect(
			admin_url( 'options-general.php?page=plugiva-clientguard&tab=sentinel' )
		);
		exit;
	}

	/**
	 * Handle Sentinel human-readable CSV export.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function handle_sentinel_human_export() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'plugiva-clientguard' )
			);
		}

		check_admin_referer(
			'pcgd_export_sentinel_human',
			'pcgd_sentinel_nonce'
		);

		$sentinel = new PCGD_Core_Sentinel();
		$events   = $sentinel->get_sentinel_events( get_current_blog_id() );

		nocache_headers();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=clientguard-sentinel.csv' );

		ob_start();

		// CSV export: raw output intended (non-HTML)
		echo implode(
			',',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV values are escaped by esc_csv().
			array_map(
				array( $this, 'esc_csv' ),
				array(
					'Time',
					'User',
					'Category',
					'Details',
				)
			)
		) . "\n";

		foreach ( $events as $event ) {

			$user = get_user_by( 'id', absint( $event['user_id'] ) );

			$user_name = $user
				? $user->display_name
				: __( 'Unknown', 'plugiva-clientguard' );

			$details = json_decode( $event['details'], true );

			$text = is_array( $details ) && isset( $details['text'] )
				? $details['text']
				: '';

			$timestamp = wp_date(
				'M d, Y \a\t H:i:s \U\T\C',
				strtotime( $event['created_at'] ),
				new DateTimeZone( 'UTC' )
			);

			echo implode(
				',',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV values are escaped by esc_csv().
				array_map(
					array( $this, 'esc_csv' ),
					array(
						$timestamp,
						$user_name,
						$event['category'],
						$text,
					)
				)
			) . "\n";
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ob_get_clean();

		exit;
	}

	/**
	 * Handle Sentinel raw CSV export.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function handle_sentinel_raw_export() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'plugiva-clientguard' )
			);
		}

		check_admin_referer(
			'pcgd_export_sentinel_raw',
			'pcgd_sentinel_nonce'
		);

		$sentinel = new PCGD_Core_Sentinel();
		$events   = $sentinel->get_sentinel_events( get_current_blog_id() );

		nocache_headers();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=clientguard-sentinel-raw.csv' );

		ob_start();

		// CSV export: raw output intended (non-HTML)
		echo implode(
			',',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV values are escaped by esc_csv().
			array_map(
				array( $this, 'esc_csv' ),
				array(
					'ID',
					'Blog ID',
					'User ID',
					'Category',
					'Event',
					'Context',
					'Action',
					'Target',
					'Details',
					'Created At',
				)
			)
		) . "\n";

		foreach ( $events as $event ) {

			echo implode(
				',',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV values are escaped by esc_csv().
				array_map(
					array( $this, 'esc_csv' ),
					array(
						isset( $event['id'] ) ? $event['id'] : '',
						isset( $event['blog_id'] ) ? $event['blog_id'] : '',
						isset( $event['user_id'] ) ? $event['user_id'] : '',
						isset( $event['category'] ) ? $event['category'] : '',
						isset( $event['event'] ) ? $event['event'] : '',
						isset( $event['context'] ) ? $event['context'] : '',
						isset( $event['action'] ) ? $event['action'] : '',
						isset( $event['target'] ) ? $event['target'] : '',
						isset( $event['details'] ) ? $event['details'] : '',
						isset( $event['created_at'] ) ? $event['created_at'] : '',
					)
				)
			) . "\n";
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ob_get_clean();

		exit;
	}

	/**
	 * Escape a value for CSV output.
	 *
	 * @since 1.7.0
	 *
	 * @param mixed $value Value to escape.
	 * @return string Escaped CSV value.
	 */
	private function esc_csv( $value ) {
		$value = (string) $value;
		$value = str_replace( '"', '""', $value );

		return '"' . $value . '"';
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_plugin_settings_link( $links ) {

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=plugiva-clientguard' ) ),
			esc_html__( 'Settings', 'plugiva-clientguard' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

}
