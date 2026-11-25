<?php
/**
 * Reduced first fee depending on the month of joining.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * First fee (meeting 9 October 2026, confirmed 24 November 2026): January to May the full fee, from June half,
 * from October a third. Joining in December costs the full fee but is valid until the end of the next year
 * (see MembershipPeriod), so December itself is free. Renewals always pay the full fee.
 */
final class JoinDiscount {

	/**
	 * Share of the yearly fee to pay when joining in a month (1–12).
	 */
	public static function share( int $month ): float {
		return match ( true ) {
			$month >= 6 && $month <= 9   => 0.5,
			$month >= 10 && $month <= 11 => 1 / 3,
			default                      => 1.0,
		};
	}

	/**
	 * Discount (positive amount, cents rounded) on a yearly fee for a month; 0 when the full fee applies.
	 */
	public static function discount( float $fee, int $month ): float {
		$share = self::share( $month );
		return $share < 1.0 ? round( $fee - round( $fee * $share, 2 ), 2 ) : 0.0;
	}

	/**
	 * Label of the discount line in the cart.
	 */
	public static function label( int $month ): string {
		return match ( self::share( $month ) ) {
			0.5     => __( 'Beitritt ab Juni: halber Jahresbeitrag', 'bw-woo-membership' ),
			1 / 3   => __( 'Beitritt ab Oktober: ein Drittel des Jahresbeitrags', 'bw-woo-membership' ),
			default => '',
		};
	}
}
