<?php
/**
 * Month names.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * Austrian German month names ("Jänner"), translatable.
 */
final class Months {

	/**
	 * Full name of a month (1–12), '' when out of range.
	 */
	public static function name( int $month ): string {
		$names = array(
			1  => __( 'Jänner', 'bw-woo-membership' ),
			2  => __( 'Februar', 'bw-woo-membership' ),
			3  => __( 'März', 'bw-woo-membership' ),
			4  => __( 'April', 'bw-woo-membership' ),
			5  => __( 'Mai', 'bw-woo-membership' ),
			6  => __( 'Juni', 'bw-woo-membership' ),
			7  => __( 'Juli', 'bw-woo-membership' ),
			8  => __( 'August', 'bw-woo-membership' ),
			9  => __( 'September', 'bw-woo-membership' ),
			10 => __( 'Oktober', 'bw-woo-membership' ),
			11 => __( 'November', 'bw-woo-membership' ),
			12 => __( 'Dezember', 'bw-woo-membership' ),
		);
		return $names[ $month ] ?? '';
	}

	/**
	 * Short upper-case name of a month (1–12) for date badges, '' when out of range.
	 */
	public static function short( int $month ): string {
		$names = array(
			1  => _x( 'JÄN', 'short month', 'bw-woo-membership' ),
			2  => _x( 'FEB', 'short month', 'bw-woo-membership' ),
			3  => _x( 'MÄR', 'short month', 'bw-woo-membership' ),
			4  => _x( 'APR', 'short month', 'bw-woo-membership' ),
			5  => _x( 'MAI', 'short month', 'bw-woo-membership' ),
			6  => _x( 'JUN', 'short month', 'bw-woo-membership' ),
			7  => _x( 'JUL', 'short month', 'bw-woo-membership' ),
			8  => _x( 'AUG', 'short month', 'bw-woo-membership' ),
			9  => _x( 'SEP', 'short month', 'bw-woo-membership' ),
			10 => _x( 'OKT', 'short month', 'bw-woo-membership' ),
			11 => _x( 'NOV', 'short month', 'bw-woo-membership' ),
			12 => _x( 'DEZ', 'short month', 'bw-woo-membership' ),
		);
		return $names[ $month ] ?? '';
	}
}
