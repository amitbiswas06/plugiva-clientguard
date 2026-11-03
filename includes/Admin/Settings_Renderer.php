<?php
/**
 * Renders the ClientGuard settings interface.
 *
 * Contains the presentation methods used to display the plugin's
 * settings page, tabs, and individual settings fields.
 *
 * Refactored from Settings.php 
 * @since 1.8.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Settings_Renderer {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Render the introductory text for the Client Mode settings section.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render_client_mode_intro() {
		echo '<p>' . esc_html__(
			'Client Mode applies safe defaults to simplify the admin and help prevent unintended changes.',
			'plugiva-clientguard'
		) . '</p>';
	}

	/**
	 * Render a ClientGuard checkbox settings field.
	 *
	 * Uses the field key and label supplied by the registered settings
	 * field, and retrieves its effective state from the settings state
	 * handler. This accounts for Client Mode and other dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Checkbox field arguments containing 'key' and 'label'.
	 * @return void
	 */
	public function render_checkbox( $args ) {

		$settings = get_option( self::OPTION_NAME, array() );
		$key      = $args['key'];
		$label    = $args['label'];

		// Get effective state considering Client Mode and dependencies.
		// @since 1.3.0 - moved logic to separate class for better organization and future extensibility.
		$state     = PCGD_Admin_Settings_State::get( $key, $settings );
		$value     = $state['value'];
		$disabled  = $state['disabled'];
		$note      = $state['note'];

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

	/**
	 * Prepare content-type options for the Content Protection interface.
	 *
	 * Supported types determine availability; labels determine presentation.
	 * 
	 * @since 1.8.0
	 * @return array<string, array{label: string, is_pro: bool}>
	 */
	private function get_content_type_options_ui() {

		$supported_types = PCGD_Protected_Content_Types::get_supported_types();

		if ( ! is_array( $supported_types ) ) {
			return array();
		}

		$labels = array(
			'page' => __( 'Pages', 'plugiva-clientguard' ),
		);

		$pro_labels = array();

		if ( class_exists( 'PCGD_Pro_Plugin' ) ) {
			$pro_labels = PCGD_Pro_Plugin::get_content_type_labels();
		}

		if ( is_array( $pro_labels ) ) {
			$labels = array_merge( $labels, $pro_labels );
		}

		$options = array();

		foreach ( $supported_types as $post_type ) {
			if (
				! is_string( $post_type )
				|| ! isset( $labels[ $post_type ] )
				|| ! is_string( $labels[ $post_type ] )
				|| '' === $labels[ $post_type ]
			) {
				continue;
			}

			$options[ $post_type ] = array(
				'label'  => $labels[ $post_type ],
				'is_pro' => isset( $pro_labels[ $post_type ] ),
			);
		}

		return $options;
	}

	/**
	 * Render the Content Protection settings interface.
	 *
	 * Displays the available content types, content search interface,
	 * and currently protected content items.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
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

			// Group currently supported protected items by post type.
			$protected_by_type = array();

			$supported_types = PCGD_Protected_Content_Types::get_supported_types();

			foreach ( $protected as $post_id ) {

				$post_type = get_post_type( $post_id );

				if ( ! $post_type || ! in_array( $post_type, $supported_types, true ) ) {
					continue;
				}

				if ( ! isset( $protected_by_type[ $post_type ] ) ) {
					$protected_by_type[ $post_type ] = array();
				}

				$protected_by_type[ $post_type ][] = $post_id;
			}
		?>

		<!-- Content Protection interface — @since 1.8.0 -->
		<div id="pcgd-content-protection" class="pcgd-content-protection-wrap">

			<div class="pcgd-content-protection-header">
				<p class="description">
					<?php esc_html_e( 'Choose what clients can edit or remove.', 'plugiva-clientguard' ); ?>
				</p>
			</div>

			<div class="pcgd-content-protection-card">

				<h4 class="pcgd-section-heading">
					<?php esc_html_e( '1. Find content to protect', 'plugiva-clientguard' ); ?>
				</h4>

				<div class="pcgd-content-type-selector">

					<p class="pcgd-content-type-label">
						<?php esc_html_e( 'Select content type', 'plugiva-clientguard' ); ?>
					</p>

					<?php
					$content_type_options = $this->get_content_type_options_ui();

					if ( ! empty( $content_type_options ) ) : 
					?>
					<ul class="pcgd-content-type-list">
						<?php foreach ( $content_type_options as $content_type => $option ) : ?>
							<?php
							$is_selected = ( 'page' === $content_type );
							$is_pro      = $option['is_pro'];
							?>
							<li class="pcgd-content-type-item">
								<button
									type="button"
									class="pcgd-content-type-pill<?php echo $is_selected ? ' selected-pill' : ''; ?>"
									data-content-type="<?php echo esc_attr( $content_type ); ?>"
									aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>"
								>
									<?php echo esc_html( $option['label'] ); ?>
									<?php if ( $is_pro ) : ?>
										<span class="pcgd-pro-label">
											<?php esc_html_e( '[Pro]', 'plugiva-clientguard' ); ?>
										</span>
									<?php endif; ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>

				</div>

				<div class="pcgd-content-search-box">

					<label for="pcgd-content-search">
						<?php esc_html_e( 'Search by title', 'plugiva-clientguard' ); ?>
					</label>

					<input
						type="search"
						id="pcgd-content-search"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Enter at least 3 characters…', 'plugiva-clientguard' ); ?>"
						minlength="3"
						autocomplete="off"
					/>

					<button type="button" class="button pcgd-content-search-button">
						<?php esc_html_e( 'Search', 'plugiva-clientguard' ); ?>
					</button>

				</div>

				<div class="pcgd-content-search-results" aria-live="polite" aria-relevant="additions text" hidden>
					<p class="description pcgd-search-results-message"></p>

					<div class="pcgd-search-results-toolbar" hidden>

						<label class="pcgd-select-all">
							<input type="checkbox" class="pcgd-select-all-checkbox"/>
							<span><?php esc_html_e( 'Select all shown', 'plugiva-clientguard' ); ?></span>
						</label>

						<button type="button" class="button button-primary pcgd-protect-selected" disabled>
							<?php esc_html_e( 'Protect selected', 'plugiva-clientguard' ); ?>
						</button>

					</div>

					<ul class="pcgd-search-results-list"></ul>

					<p class="description pcgd-search-results-note">
						<?php esc_html_e( 'Select the content you want to protect.', 'plugiva-clientguard' ); ?>
					</p>

					<template class="pcgd-search-result-template">
						<li class="pcgd-search-result-item">

							<label class="pcgd-search-result-select">
								<input type="checkbox" class="pcgd-search-result-checkbox"/>
								<span class="pcgd-search-result-title"></span>
							</label>

							<span class="pcgd-publication-status"></span>

						</li>
					</template>

				</div><!-- hidden templates search results -->

			</div>

			<div class="pcgd-content-protection-card pcgd-protected-list-wrap">

				<h4 class="pcgd-section-heading">
					<?php esc_html_e( '2. Protected content', 'plugiva-clientguard' ); ?>
				</h4>

				<div class="pcgd-protected-list-header">
					<button type="button" class="button pcgd-remove-all">
						<?php esc_html_e( 'Remove all', 'plugiva-clientguard' ); ?>
					</button>
				</div>

				<p class="pcgd-protected-message" role="status" aria-live="polite" hidden></p>

				<div class="pcgd-protected-list-container">

					<ul class="pcgd-protected-list">

						<?php if ( empty( $protected_by_type ) ) : ?>

							<li class="pcgd-protected-empty description">
								<?php esc_html_e( 'No content is currently protected.', 'plugiva-clientguard' ); ?>
							</li>

						<?php else : ?>

							<?php foreach ( $protected_by_type as $post_type => $post_ids ) : ?>

								<?php
								$post_type_object = get_post_type_object( $post_type );

								$type_label = $post_type_object
									? $post_type_object->labels->name
									: $post_type;
								?>

								<li class="pcgd-content-category" data-post-type="<?php echo esc_attr( $post_type ); ?>">
									<strong class="pcgd-content-category-title">
										<?php echo esc_html( $type_label ); ?>
									</strong>

									<ul class="pcgd-protected-items">

										<?php foreach ( $post_ids as $post_id ) : ?>

											<li class="pcgd-protected-item" data-id="<?php echo esc_attr( $post_id ); ?>">
												<span class="pcgd-protected-item-title">
													<?php echo esc_html( get_the_title( $post_id ) ); ?>
												</span>

												<?php
												$front_page_id = absint( get_option( 'page_on_front' ) );

												if (
													PCGD_Core_Plugin::is_client_mode()
													&& $front_page_id > 0
													&& (int) $post_id === $front_page_id
												) :
												?>
													<span class="pcgd-client-mode-label">
														<?php esc_html_e( 'Automatically protected by Client Mode', 'plugiva-clientguard' ); ?>
													</span>
												<?php endif; ?>

												<a
													href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
													target="_blank"
													rel="noopener noreferrer"
													class="pcgd-view"
												>
													<?php esc_html_e( 'View', 'plugiva-clientguard' ); ?>
												</a>

												<button
													type="button"
													class="button-link pcgd-remove"
													data-id="<?php echo esc_attr( $post_id ); ?>"
												>
													<?php esc_html_e( 'Remove', 'plugiva-clientguard' ); ?>
												</button>

												<input
													type="hidden"
													name="<?php echo esc_attr( self::OPTION_NAME . '[protected_content][]' ); ?>"
													value="<?php echo esc_attr( $post_id ); ?>"
												/>
											</li>

										<?php endforeach; ?>

									</ul>
								</li>

							<?php endforeach; ?>

						<?php endif; ?>

					</ul>

				</div>

				<p class="description">
					<?php esc_html_e( 'Selected content is kept safe from editing or deletion.', 'plugiva-clientguard' ); ?>
				</p>

			</div>

		</div>

		<?php
	}

	/**
	 * Render the admin-menu visibility settings field.
	 *
	 * Displays the configurable admin menus and their effective
	 * checked/disabled states based on Client Mode and dependencies.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
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
				checked( $state['checked'], true, false ),
				disabled( $state['disabled'], true, false ),
				wp_kses( $display_label, array(
					'small' => array(
						'style' => array(),
					),
				) )
			);
		}

		echo '</fieldset>';

		printf(
			'<p class="description">%s</p>',
			esc_html__(
				'Hidden menus are removed to keep the admin simple and focused.',
				'plugiva-clientguard'
			)
		);

	}

	/**
	 * Render the ClientGuard settings page and its tab navigation.
	 *
	 * Selects the requested tab and delegates its content to the
	 * corresponding tab renderer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab is read-only navigation input; no state-changing operation is performed.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

		$allowed_tabs = array( 'general', 'content-protection', 'sentinel' );

		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = 'general';
		}
		?>
		<div class="wrap">

			<h1><?php echo esc_html__( 'Plugiva ClientGuard', 'plugiva-clientguard' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a
					href="<?php echo esc_url( admin_url( 'options-general.php?page=plugiva-clientguard&tab=general' ) ); ?>"
					class="nav-tab <?php echo 'general' === $tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'General', 'plugiva-clientguard' ); ?>
				</a>

				<a
					href="<?php echo esc_url( admin_url( 'options-general.php?page=plugiva-clientguard&tab=content-protection' ) ); ?>"
					class="nav-tab <?php echo 'content-protection' === $tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Content Protection', 'plugiva-clientguard' ); ?>
				</a>

				<a
					href="<?php echo esc_url( admin_url( 'options-general.php?page=plugiva-clientguard&tab=sentinel' ) ); ?>"
					class="nav-tab <?php echo 'sentinel' === $tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Sentinel', 'plugiva-clientguard' ); ?>
				</a>
			</nav>

			<?php
			if ( 'sentinel' === $tab ) :
				$this->render_sentinel_tab();
			elseif ( 'content-protection' === $tab ) :
				PCGD_Core_Admin_Renderer::render_section(
					'plugiva-clientguard',
					'pcgd_section_content'
				);
			else :
				$this->render_general_tab();
			endif;
			?>
		</div>
		<?php
	}

	/**
	 * Render the General settings tab.
	 *
	 * Displays the Client Mode, general protection, and menu visibility
	 * sections inside the settings form.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	private function render_general_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'pcgd_settings_group' );

			// Client Mode (highlighted).
			echo '<div class="pcgd-client-mode-box">';
			PCGD_Core_Admin_Renderer::render_section(
				'plugiva-clientguard',
				'pcgd_section_client_mode'
			);
			echo '</div>';

			// Other sections.
			echo '<div class="pcgd-general-box">';
			PCGD_Core_Admin_Renderer::render_section(
				'plugiva-clientguard',
				'pcgd_section_general'
			);
			PCGD_Core_Admin_Renderer::render_section(
				'plugiva-clientguard',
				'pcgd_section_menu'
			);
			echo '</div>';

			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render the Sentinel settings tab.
	 *
	 * Displays the site-scoped operational history, including the
	 * Sentinel event table and available log management actions.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
    private function render_sentinel_tab() {

        $table = new PCGD_Admin_Sentinel_Table();

        $table->prepare_items();
        ?>
        <div class="pcgd-sentinel-box">

            <h2><?php esc_html_e( 'Sentinel', 'plugiva-clientguard' ); ?></h2>

            <p>
                <?php esc_html_e(
                    'Sentinel records important ClientGuard configuration and operational events for this site.',
                    'plugiva-clientguard'
                ); ?>
            </p>

			<!-- LOG ACTIONS START -->
			<div class="pcgd-sentinel-actions">

				<form method="post" class="pcgd-sentinel-export" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'pcgd_export_sentinel_human', 'pcgd_sentinel_nonce' ); ?>

					<input type="hidden" name="action" value="pcgd_export_sentinel_human">

					<?php
					submit_button(
						esc_html__( 'Export Human-Readable CSV', 'plugiva-clientguard' ),
						'secondary',
						'submit',
						false
					);
					?>
				</form>

				<form method="post" class="pcgd-sentinel-export" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'pcgd_export_sentinel_raw', 'pcgd_sentinel_nonce' ); ?>

					<input type="hidden" name="action" value="pcgd_export_sentinel_raw">

					<?php
					submit_button(
						esc_html__( 'Export Raw CSV', 'plugiva-clientguard' ),
						'secondary',
						'submit',
						false
					);
					?>
				</form>

				<form method="post" class="pcgd-sentinel-clear-logs" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'pcgd_clear_sentinel_logs', 'pcgd_sentinel_nonce' ); ?>

					<input type="hidden" name="action" value="pcgd_clear_sentinel_logs">

					<?php
					submit_button(
						esc_html__( 'Clear All Logs', 'plugiva-clientguard' ),
						'secondary',
						'submit',
						false,
						array(
							'onclick' => 'return confirm("' . esc_js(
								esc_html__( 'Are you sure you want to clear all Sentinel logs for this site? This action cannot be undone.', 'plugiva-clientguard' )
							) . '");',
						)
					);
					?>
				</form>

			</div>
			<!-- LOG ACTIONS END -->

            <?php $table->display(); ?>

        </div>
        <?php
    }

}
