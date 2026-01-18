<?php
/**
 * Uninstall cleanup for Plugiva ClientGuard.
 *
 * @package Plugiva_ClientGuard
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Allow developers to prevent data deletion.
if ( false === apply_filters( 'pcg_allow_uninstall_cleanup', true ) ) {
	return;
}

// Main settings option.
delete_option( 'pcg_settings' );

// (Optional safety) Delete site option too, if ever used later.
delete_site_option( 'pcg_settings' );
