<?php
/**
 * Plugin Name: APPA Funktionen
 * Description: Funktionen der APPA-Website: Veranstaltungen (Beitragstyp, Felder, Abfrage kommender Termine) und Formulare auf Basis von Contact Form 7 mit Spamschutz.
 * Version: 0.5.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Author: blickwert
 * Author URI: https://blickwert.at
 * License: GPL-2.0-or-later
 * Text Domain: bw-appa
 * Domain Path: /languages
 *
 * @package BW\Appa
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

const BW_APPA_VERSION = '0.5.0';
const BW_APPA_FILE    = __FILE__;
define( 'BW_APPA_DIR', __DIR__ );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'BW\\Appa\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$file = BW_APPA_DIR . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( BW\Appa\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( BW\Appa\Core\Plugin::class, 'deactivate' ) );

BW\Appa\Core\Plugin::instance()->boot();
