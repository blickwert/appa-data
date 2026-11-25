<?php
/**
 * Uninstall: removes only technical leftovers (capability, version, locks, short-lived notices, cron).
 * Member numbers (and their sequence), start dates, profiles, proofs and documents are records of the association
 * and must survive a reinstall (member numbers stay for life, meeting 2 October 2026).
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'administrator', 'shop_manager' ) as $bw_wm_role_name ) {
	$bw_wm_role = get_role( $bw_wm_role_name );
	if ( $bw_wm_role ) {
		$bw_wm_role->remove_cap( 'bw_wm_manage_members' );
	}
}

delete_option( 'bw_wm_version' );
delete_option( 'bw_wm_number_lock' );
wp_clear_scheduled_hook( 'bw_wm_daily' );

global $wpdb;
// Locks per event and short-lived notices; transients live in the options table unless an object cache holds them (then they expire).
foreach ( array( 'bw_wm_event_lock_', '_transient_bw_wm_event_notice_', '_transient_timeout_bw_wm_event_notice_', '_transient_bw_wm_proof_error_', '_transient_timeout_bw_wm_proof_error_' ) as $bw_wm_prefix ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time cleanup by prefix.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $bw_wm_prefix ) . '%' ) );
}
