<?php
/**
 * Plugin Name: Plugiva ClientGuard
 * Description: Gently protect WordPress sites from accidental client-side changes.
 * Version:     1.0.0
 * Author:      Plugiva
 * Author URI:  https://plugiva.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plugiva-clientguard
 * Domain Path: /languages
 *
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version.
 */
define( 'PCGD_VERSION', '1.0.0' );

/**
 * Plugin file.
 */
define( 'PCGD_PLUGIN_FILE', __FILE__ );

/**
 * Plugin path.
 */
define( 'PCGD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL.
 */
define( 'PCGD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Boot the plugin.
 */
require_once PCGD_PLUGIN_PATH . 'includes/Core/Plugin.php';

/**
 * Initialize plugin.
 */
function pcgd_run_plugin() {
	$plugin = new PCGD_Core_Plugin();
	$plugin->run();
}
pcgd_run_plugin();
