<?php
/**
 * Appearance protection guard.
 *
 * @package Plugiva_ClientGuard
 * @since 1.6.0 - New guard class created for future appearance-related protections.
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Appearance_Guard {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'pcgd_settings';

	/**
	 * Register hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 * @return void
	 */
	public function register( $loader ) {

        $loader->add_filter( 'user_has_cap', $this, 'block_appearance_caps', 10, 4 );

		$loader->add_filter( 'pre_update_option_sidebars_widgets', $this, 'protect_sidebars_widgets_update', 10, 3 );

		$loader->add_action( 'delete_option', $this, 'protect_sidebars_widgets_delete', 10, 1 );

		$loader->add_filter( 'pre_update_option_widget_block', $this, 'protect_widget_block_update', 10, 3 );

		$loader->add_action( 'delete_option', $this, 'protect_widget_block_delete', 10, 1 );

		// @since 1.7.0
		// Add any future general Appearance protection hooks above.
		// The hooks below are only for protected Site Identity options.
		$protected_options = $this->get_protected_site_identity_options();

		if ( empty( $protected_options ) ) {
			return;
		}

		foreach ( $protected_options as $option ) {
			$loader->add_filter( 'pre_update_option_' . $option, $this, 'protect_site_identity_option_update', 10, 3 );
		}

		$loader->add_action( 'delete_option', $this, 'protect_site_identity_option_delete', 10, 1 );

		$loader->add_action( 'admin_head', $this, 'hide_site_identity_fields_css' );

		// Don't add anything below.

	}

	/**
	 * Determine whether Appearance protection is enabled.
	 *
	 * This checks the effective protection state without considering
	 * whether the current user is exempt from ClientGuard restrictions.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function is_appearance_protection_enabled() {

		$settings = get_option( self::OPTION_NAME, array() );

		$lock = ! empty( $settings['lock_appearance_management'] );

		if ( PCGD_Core_Plugin::is_client_mode() ) {
			$lock = true;
		}

		return $lock;
	}

	/**
	 * Helper
	 * Determines whether Appearance protection is active.
	 *
	 * Appearance protection is active when Appearance management is locked
	 * or Client Mode is active. Protection can be bypassed for a trusted user
	 * when ClientGuard explicitly allows it.
	 *
	 * @param int $user_id Optional user ID to check for bypass protection.
	 * @return bool True when Appearance protection is active, otherwise false.
	 * @since 1.7.0
	 */
	public function is_appearance_protection_active( $user_id = 0 ) {

		if ( PCGD_Core_Plugin::should_bypass_protection( $user_id ) ) {
			return false;
		}

		return $this->is_appearance_protection_enabled();
	}

    /**
	 * Block appearance management capabilities.
	 *
	 * @param array   $allcaps All user capabilities.
	 * @param array   $caps    Required capabilities.
	 * @param array   $args    Capability arguments.
	 * @param WP_User $user    User object.
	 * @return array
	 */
	public function block_appearance_caps( $allcaps, $caps, $args, $user ) {

		if ( ! $this->is_appearance_protection_active( $user->ID ) ) {
			return $allcaps;
		}

		if ( isset( $allcaps['edit_theme_options'] ) ) {
			$allcaps['edit_theme_options'] = false;
		}

		return $allcaps;
	}

	/**
	 * Prevents protected sidebar widgets from being updated.
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Previous option value.
	 * @param string $option    Protected option name.
	 * @return mixed New value when protection is inactive, otherwise previous value.
	 */
	public function protect_sidebars_widgets_update( $value, $old_value, $option ) {

		if ( ! $this->is_appearance_protection_active() ) {

			if ( $value !== $old_value && $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected sidebar widget update was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'update', $option );
			}

			return $value;
		}

		// Notify ClientGuard Sentinel that a protected sidebar widget update was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'update', $option );

		return $old_value;
	}

	/**
	 * Prevents sidebar widget assignments from being deleted.
	 *
	 * WordPress does not provide a cancellable filter before an option is
	 * deleted. The `delete_option` action fires before the database row is
	 * removed, allowing ClientGuard to stop execution before the deletion
	 * occurs.
	 *
	 * @since 1.7.0
	 *
	 * @param string $option Name of the option being deleted.
	 * @return void
	 */
	public function protect_sidebars_widgets_delete( $option ) {

		$option = sanitize_key( $option );

		if ( 'sidebars_widgets' !== $option ) {
			return;
		}

		if ( ! $this->is_appearance_protection_active() ) {

			if ( $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected sidebar widget deletion was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'delete', $option );
			}

			return;
		}

		// Notify ClientGuard Sentinel that a protected sidebar widget deletion was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'delete', $option );

		PCGD_Core_Plugin::block_protected_option_deletion( $option );
	}

	/**
	 * Prevents protected block widgets from being updated.
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Previous option value.
	 * @param string $option    Protected option name.
	 * @return mixed New value when protection is inactive, otherwise previous value.
	 */
	public function protect_widget_block_update( $value, $old_value, $option ) {

		if ( ! $this->is_appearance_protection_active() ) {

			if ( $value !== $old_value && $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected block widget update was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'update', $option );
			}

			return $value;
		}

		// Notify ClientGuard Sentinel that a protected block widget update was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'update', $option );

		return $old_value;
	}

	/**
	 * Prevents block widget data from being deleted.
	 *
	 * WordPress does not provide a cancellable filter before an option is
	 * deleted. The `delete_option` action fires before the database row is
	 * removed, allowing ClientGuard to stop execution before the deletion
	 * occurs.
	 *
	 * @since 1.7.0
	 *
	 * @param string $option Name of the option being deleted.
	 * @return void
	 */
	public function protect_widget_block_delete( $option ) {

		$option = sanitize_key( $option );

		if ( 'widget_block' !== $option ) {
			return;
		}

		if ( ! $this->is_appearance_protection_active() ) {

			if ( $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected block widget deletion was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'delete', $option );
			}

			return;
		}

		// Notify ClientGuard Sentinel that a protected block widget deletion was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'delete', $option );

		PCGD_Core_Plugin::block_protected_option_deletion( $option );
	}

	/**
	 * Gets the Site Identity options protected by ClientGuard.
	 *
	 * By default, ClientGuard protects core Site Identity options for the
	 * site title, tagline, site logo, and site icon.
	 *
	 * Developers can use the `pcgd_protected_site_identity_options` filter
	 * to add or remove option names when themes or plugins store additional
	 * Site Identity settings in the WordPress options table.
	 *
	 * @since 1.7.0
	 *
	 * @return string[] Protected WordPress option names.
	 */
	public function get_protected_site_identity_options() {

		$options = apply_filters(
			'pcgd_protected_site_identity_options',
			array(
				'blogname',
				'blogdescription',
				'site_logo',
				'site_icon',
			)
		);

		if ( ! is_array( $options ) ) {
			return array();
		}

		$options = array_map( 'sanitize_key', $options );

		$options = array_filter( $options );

		return array_values( array_unique( $options ) );
	}

	/**
	 * Prevents protected Site Identity options from being updated.
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Previous option value.
	 * @param string $option    Protected Site Identity option name.
	 * @return mixed New value when protection is inactive, otherwise previous value.
	 */
	public function protect_site_identity_option_update( $value, $old_value, $option ) {

		if ( ! $this->is_appearance_protection_active() ) {

			$changed = $value !== $old_value;

			if ( in_array( $option, array( 'site_logo', 'site_icon' ), true ) ) {

				if ( false === $value || false === $old_value ) {
					$changed = $value !== $old_value;
				} else {
					$changed = (int) $value !== (int) $old_value;
				}
			}

			if ( $changed && $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected Site Identity update was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'update', $option );
			}

			return $value;
		}

		// Notify ClientGuard Sentinel that a protected Site Identity update was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'update', $option );

		return $old_value;
	}

	/**
	 * Prevents protected Site Identity options from being deleted.
	 *
	 * WordPress does not provide a cancellable filter before an option is
	 * deleted. The `delete_option` action fires before the database row is
	 * removed, allowing ClientGuard to stop execution before the deletion
	 * occurs.
	 *
	 * @since 1.7.0
	 *
	 * @param string $option Name of the option being deleted.
	 * @return void
	 */
	public function protect_site_identity_option_delete( $option ) {

		$option = sanitize_key( $option );

		if ( '' === $option ) {
			return;
		}

		$protected_options = $this->get_protected_site_identity_options();

		if ( ! in_array( $option, $protected_options, true ) ) {
			return;
		}

		if ( ! $this->is_appearance_protection_active() ) {

			if ( $this->is_appearance_protection_enabled() && PCGD_Core_Plugin::should_bypass_protection() ) {

				// Notify ClientGuard Sentinel that a protected Site Identity deletion was bypassed.
				// @since 1.7.0
				do_action( 'pcgd_protection_bypassed', 'appearance_guard', 'delete', $option );
			}

			return;
		}

		// Notify ClientGuard Sentinel that a protected Site Identity deletion was blocked.
		// @since 1.7.0
		do_action( 'pcgd_protection_blocked', 'appearance_guard', 'delete', $option );

		PCGD_Core_Plugin::block_protected_option_deletion( $option );
	}

	/**
	 * Hide Site Identity fields via CSS when protected.
	 *
	 * @return void
	 * @since 1.7.0
	 */
	public function hide_site_identity_fields_css() {

		// Only when protection is active.
		if ( ! $this->is_appearance_protection_active() ) {
			return;
		}

		// Strict screen targeting.
		$screen = get_current_screen();
		if ( ! $screen || 'options-general' !== $screen->id ) {
			return;
		}

		echo '<style>
			/* Hide Site Identity fields (ClientGuard) */
			tr:has(#blogname),
			tr:has(#blogdescription),
			tr.site-icon-section {
				display: none !important;
			}
		</style>';
	}

}