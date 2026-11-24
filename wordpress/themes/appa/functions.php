<?php
/**
 * APPA theme bootstrap.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'BW\\AppaTheme\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

BW\AppaTheme\Theme::instance()->boot();
