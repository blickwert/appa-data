<?php
/**
 * Plugin Name: APPA Mitgliedschaft
 * Description: Mitgliederbereich der APPA auf Basis von WooCommerce: Mitgliedsstatus, Mitgliedsnummer, geschützte Übersicht, Mitglieder-Dokumente und Profil fürs Mitgliederverzeichnis. Nutzt WooCommerce Subscriptions, sobald es aktiv ist.
 * Version:           0.8.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: blickwert
 * Author URI: https://blickwert.at
 * License: GPL-2.0-or-later
 * Text Domain: bw-woo-membership
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

const BW_WM_VERSION = '0.8.0';
const BW_WM_FILE    = __FILE__;
define( 'BW_WM_DIR', __DIR__ );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'BW\\WooMembership\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$file = BW_WM_DIR . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( BW\WooMembership\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( BW\WooMembership\Core\Plugin::class, 'deactivate' ) );

BW\WooMembership\Core\Plugin::instance()->boot();
