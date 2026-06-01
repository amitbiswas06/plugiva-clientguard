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

		// @since 1.5.1 - remove admin bar nodes for hidden menus and protected content.
		$loader->add_action( 'admin_bar_menu', $this, 'remove_hidden_admin_bar_nodes', 999 );

	}

	/**
	 * Remove admin bar items for hidden menus.
	 *
	 * Mirrors ClientGuard menu visibility rules and removes
	 * edit shortcuts for protected content.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 * @since 1.5.1
	 */
	public function remove_hidden_admin_bar_nodes( $wp_admin_bar ) {

		$settings = get_option( 'pcgd_settings', array() );

		// Mirror hidden admin menus in the admin bar.
		$hidden = ! empty( $settings['hide_menus'] )
			? (array) $settings['hide_menus']
			: array();

		if ( PCGD_Core_Plugin::is_client_mode() ) {

			$hidden = array_unique(
				array_merge(
					$hidden,
					array(
						'plugins.php',
						'themes.php',
						'tools.php',
					)
				)
			);

			if ( function_exists( 'acf' ) ) {
				$hidden[] = 'acf';
			}
		}

		$map = array(
			'plugins.php'       => array(
				'plugins',
			),
			'themes.php'        => array(
				'appearance',
				'widgets',
				'menus',
				'customize',
			),
			'upload.php'        => array(
				'new-media',
			),
			'edit-comments.php' => array(
				'comments',
			),
			'users.php'         => array(
				'new-user',
			),
		);

		foreach ( $hidden as $menu_slug ) {

			if ( empty( $map[ $menu_slug ] ) ) {
				continue;
			}

			foreach ( $map[ $menu_slug ] as $node_id ) {
				$wp_admin_bar->remove_node( $node_id );
			}
		}

		// Remove Edit link for protected content.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return;
		}

		$protected = ! empty( $settings['protected_content'] )
			? array_map( 'absint', (array) $settings['protected_content'] )
			: array();

		if ( PCGD_Core_Plugin::is_client_mode() ) {

			// Client Mode automatically protects the homepage.
			$front_page = get_option( 'page_on_front' );

			if ( $front_page && ! in_array( $front_page, $protected, true ) ) {
				$protected[] = (int) $front_page;
			}
		}

		if ( in_array( $post_id, $protected, true ) ) {
			$wp_admin_bar->remove_node( 'edit' );
		}
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
				'title' => esc_attr__( 'Admin is simplified to help prevent unintended changes.', 'plugiva-clientguard' ),
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

		// Enable URL: points to current page with nonce to trigger enabling Client Mode in handler.
		$enable_url = wp_nonce_url(
			add_query_arg( 'pcgd_enable_client_mode', '1' ),
			'pcgd_enable_client_mode'
		);

		// Dismiss URL: just a nonce link that triggers dismissal in handler, no special page needed.
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'pcgd_dismiss_client_mode_notice', '1' ),
			'pcgd_dismiss_client_mode_notice'
		);
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

			check_admin_referer( 'pcgd_enable_client_mode' );

			$settings = get_option( 'pcgd_settings', array() );
			$settings['client_mode'] = true;

			update_option( 'pcgd_settings', $settings );

			// Redirect to settings page
			wp_safe_redirect( admin_url( 'admin.php?page=plugiva-clientguard' ) );
			exit;
		}

		// Dismiss notice
		$dismiss = isset( $_GET['pcgd_dismiss_client_mode_notice'] )
			? sanitize_text_field( wp_unslash( $_GET['pcgd_dismiss_client_mode_notice'] ) )
			: '';

		if ( '1' === $dismiss ) {

			check_admin_referer( 'pcgd_dismiss_client_mode_notice' );

			update_user_meta( get_current_user_id(), 'pcgd_client_mode_notice_dismissed', 1 );

			// Stay on same page
			wp_safe_redirect( remove_query_arg( 'pcgd_dismiss_client_mode_notice' ) );
			exit;
		}
	}

}
