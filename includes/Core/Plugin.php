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
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {

		require_once PCG_PLUGIN_PATH . 'includes/Core/Loader.php';
		require_once PCG_PLUGIN_PATH . 'includes/Core/I18n.php';

		require_once PCG_PLUGIN_PATH . 'includes/Admin/Settings.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Menu_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Theme_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Plugin_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Content_Guard.php';
		require_once PCG_PLUGIN_PATH . 'includes/Admin/Notices.php';

		require_once PCG_PLUGIN_PATH . 'includes/Utils/Capabilities.php';
		require_once PCG_PLUGIN_PATH . 'includes/Utils/Helpers.php';
		require_once PCG_PLUGIN_PATH . 'includes/Utils/Security.php';

		$this->loader = new PCG_Core_Loader();
	}

	/**
	 * Set plugin locale for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new PCG_Core_I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_textdomain' );
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
		// $theme_guard->register( $this->loader );

		// Plugin Guard.
		$plugin_guard = new PCG_Admin_Plugin_Guard();
		// $plugin_guard->register( $this->loader );

		// Content Guard.
		$content_guard = new PCG_Admin_Content_Guard();
		$content_guard->register( $this->loader );

	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->loader->run();
	}
}
