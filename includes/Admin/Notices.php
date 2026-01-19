<?php
/**
 * Gentle admin notices.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Admin_Notices {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcg_settings';

	/**
	 * Register hooks.
	 *
	 * @param PCG_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {
		$loader->add_action( 'admin_notices', $this, 'show_notices' );
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

			if ( empty( $settings[ $notice['setting'] ] ) ) {
				return;
			}
		}

		$message = apply_filters(
			'pcg_notice_message',
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
			'theme' => array(
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
}
