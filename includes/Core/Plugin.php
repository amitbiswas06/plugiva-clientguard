<?php
/**
 * Main plugin class.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCG_Core_Plugin {

	/**
	 * Loader instance.
	 *
	 * @var PCG_Core_Loader
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

		require_once PCG_PLUGIN_PATH . 'includes/Core/Loader.php';

		require_once PCG_PLUGIN_PATH . 'includes/Admin/Settings.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Menu_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Theme_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Plugin_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Content_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Notices.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Ajax.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Assets.php';

		$this->loader = new PCG_Core_Loader();
	}

	/**
	 * Register admin hooks.
	 */
	private function define_admin_hooks() {

		if ( ! is_admin() ) {
			return;
		}

		$settings = new PCG_Admin_Settings();

		$this->loader->add_action( 'admin_menu', $settings, 'register_menu' );
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );

		// Guards and notices will be wired here in next steps.
        // Menu Guard.
        $menu_guard = new PCG_Admin_Menu_Guard();
        $menu_guard->register( $this->loader );

		// Theme Guard.
		$theme_guard = new PCG_Admin_Theme_Guard();
		$theme_guard->register( $this->loader );

		// Plugin Guard.
		$plugin_guard = new PCG_Admin_Plugin_Guard();
		$plugin_guard->register( $this->loader );

		// Content Guard.
		$content_guard = new PCG_Admin_Content_Guard();
		$content_guard->register( $this->loader );

		// Admin Notices.
		$notices = new PCG_Admin_Notices();
		$notices->register( $this->loader );

		// AJAX Handlers.
		$ajax = new PCG_Admin_Ajax();
		$ajax->register( $this->loader );

		// Admin Assets.
		$assets = new PCG_Admin_Assets();
		$assets->register( $this->loader );

		// Plugin settings link.
		$this->loader->add_filter(
			'plugin_action_links_' . plugin_basename( PCG_PLUGIN_FILE ),
			$settings,
			'add_plugin_settings_link'
		);

	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->loader->run();
	}
}
