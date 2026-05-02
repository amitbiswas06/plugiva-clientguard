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
		// Client Mode indicator in admin bar, @since 1.1.0
		$loader->add_action( 'admin_bar_menu', $this, 'add_client_mode_indicator', 100 );

		// Notices related to Client Mode, @since 1.4.0
		$loader->add_action( 'admin_notices', $this, 'show_client_mode_status' );
		$loader->add_action( 'admin_notices', $this, 'show_client_mode_suggestion' );
		$loader->add_action( 'admin_init', $this, 'handle_client_mode_actions' );
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
	 * Show notice on settings page when Client Mode is active.
	 *
	 * @since 1.4.0
	 */
	public function show_client_mode_status() {

		// Only admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only when Client Mode is active
		if ( ! PCGD_Core_Plugin::is_client_mode() ) {
			return;
		}

		// Restrict to specific screens
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed_screens = array(
			'dashboard',
			'settings_page_plugiva-clientguard',
		);

		if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
			return;
		}

		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Client Mode is active - admin is simplified to help prevent unintended changes.', 'plugiva-clientguard' ); ?>
			</p>
		</div>
		<?php
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
