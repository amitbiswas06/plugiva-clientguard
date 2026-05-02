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
			array( $this, 'render_client_mode_intro' ),
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
			array( $this, 'render_checkbox' ),
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
			array($this, 'render_checkbox'),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'lock_theme_switch',
				'label' => esc_html__('Keep the active theme in place to maintain site layout', 'plugiva-clientguard'),
			)
		);

		add_settings_field(
			'lock_plugin_install',
			esc_html__('Lock Plugin Installation', 'plugiva-clientguard'),
			array($this, 'render_checkbox'),
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
			array($this, 'render_checkbox'),
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
			array( $this, 'render_checkbox' ),
			'plugiva-clientguard',
			'pcgd_section_general',
			array(
				'key'   => 'protect_site_urls',
				'label' => __( 'Keep <em>WordPress Address</em> and <em>Site Address</em> stable to avoid login or site access issues.', 'plugiva-clientguard' ),
			)
		);

		add_settings_field(
			'protected_content',
			esc_html__( 'Protected Content', 'plugiva-clientguard' ),
			array( $this, 'render_protected_content' ),
			'plugiva-clientguard',
			'pcgd_section_content'
		);

		add_settings_field(
			'hide_menus',
			esc_html__( 'Hide Admin Menus', 'plugiva-clientguard' ),
			array( $this, 'render_menu_hiding' ),
			'plugiva-clientguard',
			'pcgd_section_menu'
		);


	}

	/**
	 * Client mode intro function
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function render_client_mode_intro() {
		echo '<p>' . esc_html__(
			'Client Mode applies safe defaults to simplify the admin and help prevent unintended changes.',
			'plugiva-clientguard'
		) . '</p>';
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'client_mode' 			=> false, // @since 1.1.0
			'hide_menus'            => array(),
			'lock_theme_switch'     => false,
			'lock_plugin_install'   => false,
			'allow_plugin_toggle'   => true,
			'protect_site_urls'     => false, // @since 1.2.0
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

		// Get defaults for reference.
		$defaults = $this->get_default_settings();
		$output   = array();

		// Sanitize checkboxes.
		$output['client_mode'] = ! empty( $input['client_mode'] ); // @since 1.1.0

		// Hard lock via config - @since 1.3.0
		if ( defined( 'PCGD_LOCK_CLIENT_MODE' ) && PCGD_LOCK_CLIENT_MODE ) {
			$output['client_mode'] = true;
		}

		$output['lock_theme_switch']   	= ! empty( $input['lock_theme_switch'] );
		$output['lock_plugin_install'] 	= ! empty( $input['lock_plugin_install'] );
		$output['allow_plugin_toggle'] 	= ! empty( $input['allow_plugin_toggle'] );
		$output['protect_site_urls'] 	= ! empty( $input['protect_site_urls'] ); // @since 1.2.0

		// Sanitize protected content IDs.
		if ( isset( $input['protected_content'] ) && is_array( $input['protected_content'] ) ) {

			$clean = array();

			foreach ( $input['protected_content'] as $post_id ) {
				$post_id = absint( $post_id );

				if ( $post_id && get_post( $post_id ) ) {
					$clean[] = $post_id;
				}
			}

			$output['protected_content'] = $clean;

		} else {
			$output['protected_content'] = array();
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

		// Enforce Client Mode defaults at save level
		// @since 1.1.0
		if ( ! empty( $output['client_mode'] ) ) {

			$output['lock_theme_switch']   	= true;
			$output['lock_plugin_install'] 	= true;
			$output['allow_plugin_toggle'] 	= false;
			$output['protect_site_urls'] 	= true; // @since 1.2.0

			// For menu hiding
			$client_locked = array(
				'plugins.php',
				'themes.php',
				'tools.php',
			);

			// Add ACF only if active
			if ( function_exists( 'acf' ) ) {
				$client_locked[] = 'acf';
			}

			$current = isset( $output['hide_menus'] ) && is_array( $output['hide_menus'] )
				? $output['hide_menus']
				: array();

			$output['hide_menus'] = array_unique( array_merge( $current, $client_locked ) );
		}

		return $output;
	}

	public function render_checkbox( $args ) {

		$settings = get_option( self::OPTION_NAME, array() );
		$key      = $args['key'];
		$label    = $args['label'];

		// Get effective state considering Client Mode and dependencies.
		// @since 1.3.0 - moved logic to separate class for better organization and future extensibility.
		$state 		= PCGD_Admin_Settings_State::get( $key, $settings );
		$value    	= $state['value'];
		$disabled 	= $state['disabled'];
		$note     	= $state['note'];

		?>
		<label>
			<input type="checkbox"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
				value="1"
				<?php checked( $value ); ?>
				<?php disabled( $disabled ); ?> />
			<?php echo wp_kses( $label, array( 'strong' => array(), 'em' => array() ) ); ?>
		</label>

		<?php if ( $note ) : ?>
			<p class="description"><small><?php echo esc_html( $note ); ?></small></p>
		<?php endif; ?>
		<?php
	}

	public function render_protected_content() {

		$settings  = get_option( self::OPTION_NAME );
		$protected = ! empty( $settings['protected_content'] )
			? array_map( 'absint', (array) $settings['protected_content'] )
			: array();

			// Remove stale / deleted posts.
			$protected = array_filter(
				$protected,
				function ( $post_id ) {
					return get_post( $post_id );
				}
			);
		?>
		<div id="pcgd-content-protection">

			<div style="margin-bottom:10px;">
				<input type="text"
					id="pcgd-page-search"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Search pages by title…', 'plugiva-clientguard' ); ?>" />
				<button type="button"
						class="button button-primary"
						id="pcgd-page-search-btn">
					<?php esc_html_e( 'Search', 'plugiva-clientguard' ); ?>
				</button>
			</div>

			<div id="pcgd-search-results" style="margin-bottom:15px;"></div>

			<strong><?php esc_html_e( 'Protected Pages', 'plugiva-clientguard' ); ?></strong>

			<ul id="pcgd-protected-list">
				<?php if ( empty( $protected ) ) : ?>
					<li class="description">
						<?php esc_html_e( 'No pages are currently protected.', 'plugiva-clientguard' ); ?>
					</li>
				<?php else : ?>
					<?php foreach ( $protected as $post_id ) : ?>
						<li data-id="<?php echo esc_attr( $post_id ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
							<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" class="pcgd-view">
								<?php esc_html_e( 'View', 'plugiva-clientguard' ); ?>
							</a>
							<button type="button" class="button-link pcgd-remove">
								<?php esc_html_e( 'Remove', 'plugiva-clientguard' ); ?>
							</button>
							<input type="hidden"
								name="<?php echo esc_attr( self::OPTION_NAME . '[protected_content][]' ); ?>"
								value="<?php echo esc_attr( $post_id ); ?>" />
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>

			<p class="description">
				<?php esc_html_e( 'Selected pages are kept safe from editing or deletion.', 'plugiva-clientguard' ); ?>
				<br />
				<small><?php esc_html_e( 'Post safety is available in Plugiva ClientGuard Pro.', 'plugiva-clientguard' ); ?></small>
			</p>

		</div>
		<?php
	}

	public function render_menu_hiding() {

		$acf_active = function_exists( 'acf' ); // Check if ACF is active for potential future integration notes.

		$settings 	= get_option( self::OPTION_NAME, array() );

		$menus = array(
			'plugins.php'          => __( 'Plugins', 'plugiva-clientguard' ),
			'themes.php'           => __( 'Appearance', 'plugiva-clientguard' ),
			'upload.php'           => __( 'Media', 'plugiva-clientguard' ),
			'users.php'            => __( 'Users', 'plugiva-clientguard' ),
			'tools.php'            => __( 'Tools', 'plugiva-clientguard' ),
			'edit-comments.php'    => __( 'Comments', 'plugiva-clientguard' ),
		);

		// Add ACF only if active, @since 1.1.0
		if ( $acf_active ) {
			$menus['acf'] = __( 'ACF (Custom Fields)', 'plugiva-clientguard' );
		}

		echo '<fieldset>';

		foreach ( $menus as $slug => $label ) {

			// Get effective state considering Client Mode and dependencies.
			// @since 1.3.0 - moved logic to separate class for better organization and future extensibility.
			$state 			= PCGD_Admin_Settings_State::get_menu_state( $slug, $settings );
			$checked_attr 	= checked( $state['checked'], true, false );
			$disabled_attr 	= disabled( $state['disabled'], true, false );

			$display_label 	= $label;

			if ( ! empty( $state['note'] ) ) {
				$display_label .= ' <small style="color:#666;">[' . esc_html( $state['note'] ) . ']</small>';
			}

			printf(
				'<label style="display:block;margin-bottom:4px;">
					<input type="checkbox"
						name="%1$s[]"
						value="%2$s"
						%3$s
						%4$s />
					%5$s
				</label>',
				esc_attr( self::OPTION_NAME . '[hide_menus]' ),
				esc_attr( $slug ),
				$checked_attr,
				$disabled_attr,
				wp_kses( $display_label, array(
					'small' => array(
						'style' => array(),
					),
				) )
			);
		}

		echo '</fieldset>';

		printf(
			'<p class="description">%s<br><small>%s</small></p>',
			esc_html__(
				'Hidden menus are removed to keep the admin simple and focused.',
				'plugiva-clientguard'
			),
			esc_html__(
				'More advanced menu controls are available in Plugiva ClientGuard Pro.',
				'plugiva-clientguard'
			)
		);

	}


	/**
	 * Render settings page.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugiva ClientGuard', 'plugiva-clientguard' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'pcgd_settings_group' );

				// Client Mode (highlighted)
				echo '<div class="pcgd-client-mode-box">';
				PCGD_Core_Admin_Renderer::render_section( 'plugiva-clientguard', 'pcgd_section_client_mode' );
				echo '</div>';

				// Other sections
				echo '<div class="pcgd-general-box">';
				PCGD_Core_Admin_Renderer::render_section( 'plugiva-clientguard', 'pcgd_section_general' );
				PCGD_Core_Admin_Renderer::render_section( 'plugiva-clientguard', 'pcgd_section_content' );
				PCGD_Core_Admin_Renderer::render_section( 'plugiva-clientguard', 'pcgd_section_menu' );
				echo '</div>';

				submit_button();
				?>
			</form>
		</div>
		<?php
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
