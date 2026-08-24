<?php
/**
 * Main plugin class.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Core_Plugin {

	/**
	 * Loader instance.
	 *
	 * @var PCGD_Core_Loader
	 */
	protected $loader;

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {

		require_once PCGD_PLUGIN_PATH . 'includes/Core/Loader.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Core/Admin_Renderer.php'; // @since 1.1.0

		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Settings.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Menu_Guard.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Theme_Guard.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Theme_Switching_Sentinel.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Theme_Installation_Sentinel.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Theme_Filesystem_Proxy.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Theme_Deletion_Sentinel.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Appearance_Guard.php'; // @since 1.6.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Plugin_Guard.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Plugin_Deletion_Sentinel.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Plugin_Filesystem_Proxy.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Plugin_Installation_Sentinel.php'; // @since 1.7.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Content_Guard.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Dashboard_Guard.php'; // @since 1.5.2
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Settings_Guard.php'; // @since 1.2.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Settings_State.php'; // @since 1.3.0
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Notices.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Ajax.php';
		require_once PCGD_PLUGIN_PATH . 'includes/Admin/Assets.php';

		$this->loader = new PCGD_Core_Loader();
	}

	/**
	 * Register admin hooks.
	 */
	private function define_admin_hooks() {

		/**
		 * Operational guards.
		 * WordPress 7.0+
		 *
		 * Modern WordPress workflows may execute outside traditional
		 * wp-admin requests (REST, automation, provisioning, etc.).
		 *
		 * These guards therefore load globally so operational
		 * protections apply consistently across execution contexts.
		 *
		 * @since 1.5.0
		 */

		// Theme Guard.
		$theme_guard = new PCGD_Admin_Theme_Guard();
		$theme_guard->register( $this->loader );

		// Appearance Guard.
		// @since 1.6.0
		$appearance_guard = new PCGD_Admin_Appearance_Guard();
		$appearance_guard->register( $this->loader );

		// Plugin Guard.
		$plugin_guard = new PCGD_Admin_Plugin_Guard();
		$plugin_guard->register( $this->loader );

		// Settings Guard.
		// @since 1.2.0 - new guard for site URL protection and future settings-related protections.
		$settings_guard = new PCGD_Admin_Settings_Guard();
		$settings_guard->register( $this->loader );

		// reloacted outside admin check
		// @since 1.5.1
		$notices = new PCGD_Admin_Notices();
		$notices->register( $this->loader );

		// Content Guard.
		// Content protection must also apply to REST and other
		// non-traditional execution contexts.
		// relocated @since 1.7.0
		$content_guard = new PCGD_Admin_Content_Guard();
		$content_guard->register( $this->loader );

		// Stop here for non-admin requests.
		if ( ! is_admin() ) {
			return;
		}

		$settings = new PCGD_Admin_Settings();

		$this->loader->add_action( 'admin_menu', $settings, 'register_menu' );
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );

		// Menu Guard.
		$menu_guard = new PCGD_Admin_Menu_Guard();
		$menu_guard->register( $this->loader );

		// Dashboard Guard. @since 1.5.2
		$dashboard_guard = new PCGD_Admin_Dashboard_Guard();
		$dashboard_guard->register( $this->loader );

		// AJAX Handlers.
		$ajax = new PCGD_Admin_Ajax();
		$ajax->register( $this->loader );

		// Admin Assets.
		$assets = new PCGD_Admin_Assets();
		$assets->register( $this->loader );

		// Plugin settings link.
		$this->loader->add_filter(
			'plugin_action_links_' . plugin_basename( PCGD_PLUGIN_FILE ),
			$settings,
			'add_plugin_settings_link'
		);
	}

	/**
	 * Check if Client Mode is active.
	 *
	 * Client Mode can be enabled via:
	 * 1. Plugin settings (database)
	 * 2. Hard lock via wp-config.php constant:
	 *    define( 'PCGD_LOCK_CLIENT_MODE', true );
	 *
	 * When the constant is defined and true, Client Mode is forced ON
	 * and cannot be disabled from the admin UI.
	 *
	 * @return bool True if Client Mode is active.
	 * @since 1.1.0
	 */
	public static function is_client_mode() {

		// Hard lock via config (highest priority)
		// @since v1.3.0 - new constant to lock Client Mode on for critical use cases.
		if ( defined( 'PCGD_LOCK_CLIENT_MODE' ) && PCGD_LOCK_CLIENT_MODE ) {
			return true;
		}

		$settings = get_option( 'pcgd_settings', array() );

		return ! empty( $settings['client_mode'] );
	}

	/**
	 * Determine whether the current user should bypass ClientGuard protections.
	 *
	 * In multisite, Network Super Admins remain exempt from ClientGuard
	 * restrictions to preserve network-level administrative operations.
	 *
	 * @since 1.7.0
	 * @param int $user_id Optional user ID.
	 * @return bool
	 */
	public static function should_bypass_protection( $user_id = 0 ) {

		if ( ! is_multisite() ) {
			return false;
		}

		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			return false;
		}

		return is_super_admin( $user_id );
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->loader->run();
	}
}
