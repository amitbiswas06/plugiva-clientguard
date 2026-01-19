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

		add_settings_section(
			'pcg_section_general',
			esc_html__('General Protection', 'plugiva-clientguard'),
			'__return_false',
			'plugiva-clientguard'
		);

		add_settings_section(
			'pcg_section_content',
			esc_html__('Content Protection', 'plugiva-clientguard'),
			'__return_false',
			'plugiva-clientguard'
		);

		add_settings_section(
			'pcg_section_menu',
			esc_html__( 'Menu Visibility', 'plugiva-clientguard' ),
			'__return_false',
			'plugiva-clientguard'
		);

		add_settings_field(
			'lock_theme_switch',
			esc_html__('Lock Theme Switching', 'plugiva-clientguard'),
			array($this, 'render_checkbox'),
			'plugiva-clientguard',
			'pcg_section_general',
			array(
				'key'   => 'lock_theme_switch',
				'label' => esc_html__('Prevent switching or deleting themes', 'plugiva-clientguard'),
			)
		);

		add_settings_field(
			'lock_plugin_install',
			esc_html__('Lock Plugin Installation', 'plugiva-clientguard'),
			array($this, 'render_checkbox'),
			'plugiva-clientguard',
			'pcg_section_general',
			array(
				'key'   => 'lock_plugin_install',
				'label' => esc_html__('Prevent installing or deleting plugins', 'plugiva-clientguard'),
			)
		);

		add_settings_field(
			'allow_plugin_toggle',
			esc_html__('Allow Plugin Activation', 'plugiva-clientguard'),
			array($this, 'render_checkbox'),
			'plugiva-clientguard',
			'pcg_section_general',
			array(
				'key'   => 'allow_plugin_toggle',
				'label' => esc_html__('Allow activating or deactivating plugins', 'plugiva-clientguard'),
			)
		);

		add_settings_field(
			'protected_content',
			esc_html__( 'Protected Content', 'plugiva-clientguard' ),
			array( $this, 'render_protected_content' ),
			'plugiva-clientguard',
			'pcg_section_content'
		);

		add_settings_field(
			'hide_menus',
			esc_html__( 'Hide Admin Menus', 'plugiva-clientguard' ),
			array( $this, 'render_menu_hiding' ),
			'plugiva-clientguard',
			'pcg_section_menu'
		);


	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'hide_menus'            => array(),
			'lock_theme_switch'     => false,
			'lock_plugin_install'   => false,
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

		// Get defaults for reference.
		$defaults = $this->get_default_settings();
		$output   = array();

		// Sanitize checkboxes.
		$output['lock_theme_switch']   = ! empty( $input['lock_theme_switch'] );
		
		$output['lock_plugin_install'] = ! empty( $input['lock_plugin_install'] );

		$output['allow_plugin_toggle'] = ! empty( $input['allow_plugin_toggle'] );

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

		return $output;
	}

	public function render_checkbox( $args ) {

		$settings = get_option( self::OPTION_NAME );
		$key      = $args['key'];
		$label    = $args['label'];

		$value    = ! empty( $settings[ $key ] );
		$disabled = false;
		$note     = '';

		// Dependency: allow_plugin_toggle depends on lock_plugin_install.
		if ( 'allow_plugin_toggle' === $key && empty( $settings['lock_plugin_install'] ) ) {
			$disabled = true;
			$note     = esc_html__(
				'Enable “Lock Plugin Installation” to control plugin activation.',
				'plugiva-clientguard'
			);
		}

		?>
		<label>
			<input type="checkbox"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
				value="1"
				<?php checked( $value ); ?>
				<?php disabled( $disabled ); ?> />
			<?php echo esc_html( $label ); ?>
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
		<div id="pcg-content-protection">

			<div style="margin-bottom:10px;">
				<input type="text"
					id="pcg-page-search"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Search pages by title…', 'plugiva-clientguard' ); ?>" />
				<button type="button"
						class="button button-primary"
						id="pcg-page-search-btn">
					<?php esc_html_e( 'Search', 'plugiva-clientguard' ); ?>
				</button>
			</div>

			<div id="pcg-search-results" style="margin-bottom:15px;"></div>

			<strong><?php esc_html_e( 'Protected Pages', 'plugiva-clientguard' ); ?></strong>

			<ul id="pcg-protected-list">
				<?php if ( empty( $protected ) ) : ?>
					<li class="description">
						<?php esc_html_e( 'No pages are currently protected.', 'plugiva-clientguard' ); ?>
					</li>
				<?php else : ?>
					<?php foreach ( $protected as $post_id ) : ?>
						<li data-id="<?php echo esc_attr( $post_id ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
							<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" class="pcg-view">
								<?php esc_html_e( 'View', 'plugiva-clientguard' ); ?>
							</a>
							<button type="button" class="button-link pcg-remove">
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
				<?php esc_html_e( 'Selected pages will be protected from editing and deletion.', 'plugiva-clientguard' ); ?>
				<br />
				<small><?php esc_html_e( 'Post protection is available in ClientGuard Pro.', 'plugiva-clientguard' ); ?></small>
			</p>

		</div>
		<?php
	}

	public function render_menu_hiding() {

		$settings    = get_option( self::OPTION_NAME );
		$hidden      = ! empty( $settings['hide_menus'] )
			? (array) $settings['hide_menus']
			: array();

		$menus = array(
			'plugins.php'          => __( 'Plugins', 'plugiva-clientguard' ),
			'themes.php'           => __( 'Appearance', 'plugiva-clientguard' ),
			'upload.php'           => __( 'Media', 'plugiva-clientguard' ),
			'users.php'            => __( 'Users', 'plugiva-clientguard' ),
			'tools.php'            => __( 'Tools', 'plugiva-clientguard' ),
			'edit-comments.php'    => __( 'Comments', 'plugiva-clientguard' ),
		);

		echo '<fieldset>';

		foreach ( $menus as $slug => $label ) {
			printf(
				'<label style="display:block;margin-bottom:4px;">
					<input type="checkbox"
						name="%1$s[]"
						value="%2$s"
						%3$s />
					%4$s
				</label>',
				esc_attr( self::OPTION_NAME . '[hide_menus]' ),
				esc_attr( $slug ),
				checked( in_array( $slug, $hidden, true ), true, false ),
				esc_html( $label )
			);
		}

		echo '</fieldset>';

		printf(
			'<p class="description">%s<br><small>%s</small></p>',
			esc_html__(
				'Hidden menus are removed from the sidebar but remain accessible via direct links.',
				'plugiva-clientguard'
			),
			esc_html__(
				'Additional menu controls, including sub-menu items, are available in ClientGuard Pro.',
				'plugiva-clientguard'
			)
		);

	}


	/**
	 * Render settings page (placeholder).
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugiva ClientGuard', 'plugiva-clientguard' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'pcg_settings_group' );
				do_settings_sections( 'plugiva-clientguard' );
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
