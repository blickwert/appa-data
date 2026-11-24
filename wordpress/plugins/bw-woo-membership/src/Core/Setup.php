<?php
/**
 * Setup on activation and update.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

/**
 * Capability, cron and folders. Runs on activation and whenever the plugin version changes (an update by
 * overwriting the ZIP does not run the activation hook).
 */
final class Setup {

	public const CAPABILITY = 'bw_wm_manage_members';
	public const OPTION     = 'bw_wm_version';
	public const ROLES      = array( 'administrator', 'shop_manager' );

	/**
	 * Registers the version check.
	 */
	public static function register(): void {
		// wp_loaded: WooCommerce registers its order statuses on init, the snapshot sync needs them.
		add_action( 'wp_loaded', array( self::class, 'maybe_run' ) );
	}

	/**
	 * Runs the setup once per version.
	 */
	public static function maybe_run(): void {
		if ( BW_WM_VERSION !== get_option( self::OPTION ) ) {
			self::run();
		}
	}

	/**
	 * Grants the capability, schedules the daily job, lets modules prepare.
	 */
	public static function run(): void {
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
				$role->add_cap( self::CAPABILITY );
			}
		}
		if ( ! wp_next_scheduled( 'bw_wm_daily' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:15' ) ?: time(), 'daily', 'bw_wm_daily' );
		}

		/**
		 * Fires during setup (activation or new version).
		 */
		do_action( 'bw_wm_setup' );
		update_option( self::OPTION, BW_WM_VERSION, false );
	}

	/**
	 * Removes the scheduled job (deactivation).
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'bw_wm_daily' );
	}
}
