<?php
/**
 * Uninstall cleanup for Plugiva ClientGuard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/Core/Sentinel.php';

/**
 * Clean up ClientGuard data for the current site.
 *
 * @return void
 */
function pcgd_uninstall_cleanup_site() {

	$sentinel = new PCGD_Core_Sentinel();

	/*
	 * Record the uninstall event before removing ClientGuard settings.
	 *
	 * Sentinel data is intentionally preserved by default.
	 */
	$sentinel->record_lifecycle_event( 'uninstall' );

	delete_option( 'pcgd_settings' );
	delete_option( 'pcgd_db_version' );

	/*
	 * Sentinel tables are preserved by default.
	 * Developers may explicitly opt into Sentinel cleanup.
	 *
	 * @since 1.7.0
	 */
	if ( apply_filters( 'pcgd_delete_sentinel_table_on_uninstall', false ) ) {
		$sentinel->delete_table();
	}
}

/*
 * ClientGuard settings are site-specific, so multisite installations
 * require explicit cleanup for every site.
 */
if ( is_multisite() ) {

	$site_ids = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	foreach ( $site_ids as $site_id ) {

		switch_to_blog( $site_id );

		pcgd_uninstall_cleanup_site();

		restore_current_blog();
	}

} else {

	pcgd_uninstall_cleanup_site();
}

/*
 * Remove the Client Mode notice dismissal user meta.
 *
 * This is user-level data rather than site-specific option data.
 */
global $wpdb;

$wpdb->delete(
	$wpdb->usermeta,
	array(
		'meta_key' => 'pcgd_client_mode_notice_dismissed',
	),
	array(
		'%s',
	)
);