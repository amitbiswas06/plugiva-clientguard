<?php
/**
 * Plugin Name: Plugiva ClientGuard
 * Plugin URI:  https://plugiva.com
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
define( 'PCG_VERSION', '1.0.0' );

/**
 * Plugin file.
 */
define( 'PCG_PLUGIN_FILE', __FILE__ );

/**
 * Plugin path.
 */
define( 'PCG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL.
 */
define( 'PCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Boot the plugin.
 */
require_once PCG_PLUGIN_PATH . 'includes/Core/Plugin.php';

/**
 * Initialize plugin.
 */
function pcg_run_plugin() {
	$plugin = new PCG_Core_Plugin();
	$plugin->run();
}
pcg_run_plugin();
