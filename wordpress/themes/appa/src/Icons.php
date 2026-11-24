<?php
/**
 * SVG icons.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Reads icons from assets/icons and returns them inline, decorative by default.
 */
final class Icons {

	/**
	 * Names of all available icons (file names without extension).
	 *
	 * @return string[]
	 */
	public static function names(): array {
		$files = glob( get_theme_file_path( 'assets/icons/*.svg' ) ) ?: array();
		return array_map( static fn( string $file ): string => basename( $file, '.svg' ), $files );
	}

	/**
	 * Inline SVG markup for an icon, or an empty string for unknown names.
	 */
	public static function svg( string $name ): string {
		$name = sanitize_key( $name );
		$file = get_theme_file_path( "assets/icons/{$name}.svg" );
		if ( '' === $name || ! is_readable( $file ) ) {
			return '';
		}
		$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		return str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" ', trim( $svg ) );
	}
}
