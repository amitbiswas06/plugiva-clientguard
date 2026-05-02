<?php
/**
 * Gentle admin notices.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Notices {

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
		$loader->add_action( 'admin_notices', $this, 'show_notices' );

		// Client Mode indicator in admin bar, @since 1.1.0
		$loader->add_action( 'admin_bar_menu', $this, 'add_client_mode_indicator', 100 );

		$loader->add_action( 'admin_notices', $this, 'show_client_mode_suggestion' );
		$loader->add_action( 'admin_init', $this, 'handle_client_mode_actions' );
	}

	/**
	 * Show relevant notices.
	 */
	public function show_notices() {

		// Never show to network super admins.
		if ( is_multisite() && is_super_admin() ) {
			return;
		}

		$settings = get_option( self::OPTION_NAME );
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$this->maybe_render_notice( 'theme', $screen, $settings );
		$this->maybe_render_notice( 'plugin', $screen, $settings );

		if (
			'post' === $screen->base &&
			! empty( $screen->post_type ) &&
			! empty( $screen->post_id )
		) {
			$this->maybe_render_notice(
				'content',
				$screen,
				$settings,
				(int) $screen->post_id
			);
		}

		// @since 1.2.0 - new notice for site URL protection.
		// $this->maybe_render_notice( 'settings_siteurl', $screen, $settings );
		// $this->maybe_render_notice( 'permalink', $screen, $settings );
	}

	/**
	 * Conditionally render a notice.
	 *
	 * @param string   $type     Notice type.
	 * @param WP_Screen $screen  Current screen.
	 * @param array    $settings Plugin settings.
	 * @param int      $post_id  Optional post ID.
	 */
	private function maybe_render_notice( $type, $screen, $settings, $post_id = 0 ) {

		$notices = $this->get_notice_definitions();

		if ( empty( $notices[ $type ] ) ) {
			return;
		}

		$notice = $notices[ $type ];

		if ( ! in_array( $screen->id, $notice['screens'], true ) ) {
			return;
		}

		// Content guard requires explicit post match.
		if ( 'content' === $type ) {

			if (
				! $post_id ||
				empty( $settings['protected_content'] ) ||
				! in_array( $post_id, $settings['protected_content'], true )
			) {
				return;
			}

		} else {

			// check client mode for non-content notices
			// @since 1.1.0 - Client Mode overrides all notices.
			$is_client_mode = PCGD_Core_Plugin::is_client_mode();

			if ( empty( $settings[ $notice['setting'] ] ) && ! $is_client_mode ) {
				return;
			}
		}

		$message = apply_filters(
			'pcgd_notice_message',
			$notice['message'],
			array(
				'type'   => $type,
				'screen' => $screen->id,
				'post_id'=> $post_id,
			)
		);

		$this->render_notice( $message );
	}

	/**
	 * Notice definitions.
	 *
	 * @return array
	 */
	private function get_notice_definitions() {
		return array(

			/* 'theme' => array(
				'screens' => array( 'themes' ),
				'setting' => 'lock_theme_switch',
				'message' => esc_html__(
					'Theme switching is disabled to keep the site layout stable.',
					'plugiva-clientguard'
				),
			),
			'plugin' => array(
				'screens' => array( 'plugins' ),
				'setting' => 'lock_plugin_install',
				'message' => esc_html__(
					'Installing or removing plugins is disabled to keep the site running smoothly.',
					'plugiva-clientguard'
				),
			),
			'content' => array(
				'screens' => array( 'post' ),
				'setting' => 'protected_content',
				'message' => esc_html__(
					'This content is protected to prevent accidental changes.',
					'plugiva-clientguard'
				),
			),
			// @since 1.2.0 - new notice for site URL protection.
			'settings_siteurl' => array(
				'screens' => array( 'options-general' ),
				'setting' => 'protect_site_urls',
				'message' => esc_html__(
					'Site URLs are protected to prevent accidental changes that could break login or site access.',
					'plugiva-clientguard'
				),
			),
			'permalink' => array(
				'screens' => array( 'dashboard' ),
				'setting' => 'client_mode',
				'message' => esc_html__(
					'Permalink settings are restricted in Client Mode to prevent site routing issues.',
					'plugiva-clientguard'
				),
			), */
		);
	}

	/**
	 * Render notice markup.
	 *
	 * @param string $message Message text.
	 */
	private function render_notice( $message ) {
		?>
		<div class="notice notice-info">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add Client Mode indicator to admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @since 1.1.0
	 */
	public function add_client_mode_indicator( $wp_admin_bar ) {

		// Only for admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only when Client Mode is active
		if ( ! PCGD_Core_Plugin::is_client_mode() ) {
			return;
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'pcgd-client-mode',
			'title' => '<span class="ab-icon dashicons-shield"></span> ' . esc_html__( 'Client Mode Active', 'plugiva-clientguard' ),
			'href'  => admin_url( 'options-general.php?page=plugiva-clientguard' ),
			'meta' 	=> array(
				'title' => esc_attr__( 'Some admin actions are restricted to prevent accidental changes.', 'plugiva-clientguard' ),
			),
		) );
	}

	/**
	 * Show suggestion notice to enable Client Mode.
	 *
	 * @since 1.4.0
	 */
	public function show_client_mode_suggestion() {

		// Only admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip if already enabled
		if ( PCGD_Core_Plugin::is_client_mode() ) {
			return;
		}

		// update_user_meta( get_current_user_id(), 'pcgd_client_mode_notice_dismissed', 0 );

		// Skip if dismissed
		$dismissed = get_user_meta( get_current_user_id(), 'pcgd_client_mode_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		// Limit to specific screens
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed_screens = array(
			'dashboard',
			'plugins',
			'settings_page_plugiva-clientguard',
		);

		if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
			return;
		}

		$enable_url  = add_query_arg( 'pcgd_enable_client_mode', '1' );
		$dismiss_url = add_query_arg( 'pcgd_dismiss_client_mode_notice', '1' );
		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Enable Client Mode to simplify the admin and prevent unintended changes.', 'plugiva-clientguard' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $enable_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Enable Client Mode', 'plugiva-clientguard' ); ?>
				</a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
					<?php esc_html_e( 'Dismiss', 'plugiva-clientguard' ); ?>
				</a>
			</p>
		</div>
		<?php
	}


	/**
	 * Handle actions from the Client Mode suggestion notice.
	 *
	 * @since 1.4.0
	 */
	public function handle_client_mode_actions() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Enable Client Mode
		$enable = isset( $_GET['pcgd_enable_client_mode'] )
			? sanitize_text_field( wp_unslash( $_GET['pcgd_enable_client_mode'] ) )
			: '';

		if ( '1' === $enable ) {

			$settings = get_option( 'pcgd_settings', array() );
			$settings['client_mode'] = true;

			update_option( 'pcgd_settings', $settings );

			// Redirect to settings page (important UX decision)
			wp_safe_redirect( admin_url( 'admin.php?page=plugiva-clientguard' ) );
			exit;
		}

		// Dismiss notice
		$dismiss = isset( $_GET['pcgd_dismiss_client_mode_notice'] )
			? sanitize_text_field( wp_unslash( $_GET['pcgd_dismiss_client_mode_notice'] ) )
			: '';

		if ( '1' === $dismiss ) {

			update_user_meta( get_current_user_id(), 'pcgd_client_mode_notice_dismissed', 1 );

			// Stay on same page
			wp_safe_redirect( remove_query_arg( 'pcgd_dismiss_client_mode_notice' ) );
			exit;
		}
	}

}
