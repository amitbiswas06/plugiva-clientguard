<?php
/**
 * Plugin Name: Plugiva ClientGuard
 * Description: ClientGuard simplifies and protects the WordPress admin by reducing risky operations and helping prevent unintended changes.
 * Version:     1.8.0
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
define( 'PCGD_VERSION', '1.8.0' );

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
 * Record a deactivation event for Pro to process.
 *
 * The event is stored per site so Pro can invalidate its own
 * maintenance state even if it is not loaded during deactivation.
 *
 * @since 1.8.0
 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
 */
function pcgd_record_pro_deactivation_event( $network_wide = false ) {

	$record_event = static function () {

		update_option(
			'pcgd_free_deactivation_event',
			array(
				'event_id'       => wp_generate_uuid4(),
				'deactivated_at' => time(),
			),
			false
		);
	};

	// Single-site or individual site deactivation.
	if ( ! is_multisite() || ! $network_wide ) {
		$record_event();
		return;
	}

	$plugin_basename = plugin_basename( PCGD_PLUGIN_FILE );
	$offset          = 0;
	$batch_size      = 200;

	do {

		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $batch_size,
				'offset' => $offset,
			)
		);

		foreach ( $site_ids as $site_id ) {

			switch_to_blog( (int) $site_id );

			$active_plugins = get_option( 'active_plugins', array() );

			// Preserve sites where Free remains independently active.
			if (
				! is_array( $active_plugins )
				|| ! in_array( $plugin_basename, $active_plugins, true )
			) {
				$record_event();
			}

			restore_current_blog();
		}

		$offset += count( $site_ids );

	} while ( count( $site_ids ) === $batch_size );
}

register_deactivation_hook(
	__FILE__,
	'pcgd_record_pro_deactivation_event'
);

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
