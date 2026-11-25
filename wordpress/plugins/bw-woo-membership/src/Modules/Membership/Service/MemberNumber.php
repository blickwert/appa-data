<?php
/**
 * Member numbers.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * Formats member numbers ("A-0001"). A number is assigned once and kept when a member leaves and rejoins.
 */
final class MemberNumber {

	/**
	 * Formatted number for a sequence value.
	 */
	public static function format( int $sequence ): string {
		/**
		 * Filters the prefix of member numbers.
		 *
		 * @param string $prefix Default "A-".
		 */
		$prefix = function_exists( 'apply_filters' ) ? (string) apply_filters( 'bw_wm_member_number_prefix', 'A-' ) : 'A-';
		return $prefix . str_pad( (string) max( 1, $sequence ), 4, '0', STR_PAD_LEFT );
	}
}
