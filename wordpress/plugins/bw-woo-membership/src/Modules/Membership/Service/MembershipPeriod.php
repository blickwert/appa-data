<?php
/**
 * Membership periods.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * Calendar-year memberships: a payment covers the year it was made in; who joins in December pays nothing
 * for the rest of that year, so a December payment covers the following year (meeting 9 October 2026).
 */
final class MembershipPeriod {

	/**
	 * Last day (Y-m-d) covered by a payment on the given day.
	 */
	public static function valid_until( string $paid_date ): string {
		$paid = new \DateTimeImmutable( $paid_date );
		$year = (int) $paid->format( 'Y' ) + ( 12 === (int) $paid->format( 'n' ) ? 1 : 0 );
		return sprintf( '%04d-12-31', $year );
	}

	/**
	 * Day (Y-m-d) of the next renewal after a period.
	 */
	public static function next_renewal( string $valid_until ): string {
		return ( new \DateTimeImmutable( $valid_until ) )->modify( '+1 day' )->format( 'Y-m-d' );
	}

	/**
	 * Whether a period ending on `$valid_until` still covers `$today` (both Y-m-d).
	 */
	public static function covers( string $valid_until, string $today ): bool {
		return $today <= $valid_until;
	}

	/**
	 * German date for display, e.g. "01. Jänner 2027".
	 */
	public static function format( ?string $date ): string {
		if ( null === $date || '' === $date ) {
			return '';
		}
		$months = array( 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );
		$day    = new \DateTimeImmutable( $date );
		return sprintf( '%s. %s %s', $day->format( 'd' ), $months[ (int) $day->format( 'n' ) - 1 ], $day->format( 'Y' ) );
	}
}
