<?php
/**
 * Template rendering.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

/**
 * Renders plugin templates; the theme can override them in `bw-woo-membership/<area>/<name>.php`.
 */
final class View {

	/**
	 * Path of a template, theme override first.
	 */
	public static function locate( string $name ): string {
		$theme = locate_template( 'bw-woo-membership/' . $name . '.php' );
		$file  = '' !== $theme ? $theme : BW_WM_DIR . '/templates/' . $name . '.php';

		/**
		 * Filters the template file.
		 *
		 * @param string $file Absolute path.
		 * @param string $name Template name, e.g. "dashboard/overview".
		 */
		return (string) apply_filters( 'bw_wm_template', $file, $name );
	}

	/**
	 * Renders a template with prepared data and returns the HTML.
	 *
	 * @param array<string, mixed> $data Values for the template, available as `$data`.
	 */
	public static function render( string $name, array $data = array() ): string {
		$file = self::locate( $name );
		if ( ! is_readable( $file ) ) {
			return '';
		}
		ob_start();
		( static function ( string $bw_wm_file, array $data ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $data is used by the template.
			include $bw_wm_file;
		} )( $file, $data );
		return (string) ob_get_clean();
	}
}
