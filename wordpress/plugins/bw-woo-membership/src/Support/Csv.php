<?php
/**
 * CSV helper.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * Prevents formula injection when a CSV export is opened in a spreadsheet program.
 */
final class Csv {

	/**
	 * Prefixes values that a spreadsheet would read as a formula.
	 */
	public static function safe( string $value ): string {
		return 1 === preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}
}
