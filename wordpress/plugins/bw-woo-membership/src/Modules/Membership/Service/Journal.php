<?php
/**
 * Pure helpers of the fee journal (period, amounts, totals).
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use DateTimeImmutable;
use DateTimeZone;

/**
 * The journal lists every paid order of a month for the bookkeeping, so that it can book sums (per Stripe payout,
 * per month) while the details per member stay in WooCommerce.
 */
final class Journal {

	public const HEADER = array( 'Zahlungsdatum', 'Bestellung', 'Art', 'Mitgliedsnummer', 'Vorname', 'Nachname', 'E-Mail', 'Betrag', 'Erstattet', 'Zahlungsart', 'Stripe-Gebühr', 'Netto', 'Transaktions-ID' );

	/**
	 * First and last second of a month ("Y-m"); an invalid value means the previous month.
	 *
	 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
	 */
	public static function period( string $month, DateTimeZone $zone, ?DateTimeImmutable $now = null ): array {
		$now   = $now ?? new DateTimeImmutable( 'now', $zone );
		$start = 1 === preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) ? DateTimeImmutable::createFromFormat( '!Y-m', $month, $zone ) : false;
		if ( ! $start ) {
			$start = $now->setTimezone( $zone )->modify( 'first day of previous month' )->setTime( 0, 0 );
		}
		return array( $start, $start->modify( 'last day of this month' )->setTime( 23, 59, 59 ) );
	}

	/**
	 * Any period from day to day ("Y-m-d", both inclusive); invalid or reversed values mean the previous month.
	 *
	 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
	 */
	public static function range( string $from, string $to, DateTimeZone $zone, ?DateTimeImmutable $now = null ): array {
		$start = self::day( $from, $zone );
		$end   = self::day( $to, $zone );
		if ( ! $start || ! $end || $start > $end ) {
			return self::period( '', $zone, $now );
		}
		return array( $start, $end->setTime( 23, 59, 59 ) );
	}

	/**
	 * A valid day "Y-m-d" at midnight, or null.
	 */
	private static function day( string $value, DateTimeZone $zone ): ?DateTimeImmutable {
		$day = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $zone ) : false;
		return $day && $day->format( 'Y-m-d' ) === $value ? $day : null;
	}

	/**
	 * Amount for spreadsheets in Austria: comma, two decimals, no thousands separator.
	 */
	public static function money( float $amount ): string {
		return number_format( $amount, 2, ',', '' );
	}

	/**
	 * Sums of amount, refunds, fees and net over journal entries.
	 *
	 * @param list<array{total: float, refunded: float, fee: float, net: float}> $entries Entries.
	 * @return array{total: float, refunded: float, fee: float, net: float}
	 */
	public static function totals( array $entries ): array {
		$sum = array( 'total' => 0.0, 'refunded' => 0.0, 'fee' => 0.0, 'net' => 0.0 );
		foreach ( $entries as $entry ) {
			foreach ( $sum as $key => $value ) {
				$sum[ $key ] = round( $value + (float) $entry[ $key ], 2 );
			}
		}
		return $sum;
	}

	/**
	 * Net amount: from Stripe when known, otherwise amount minus refunds minus fee.
	 */
	public static function net( float $total, float $refunded, float $fee, ?float $stripe_net ): float {
		return round( null !== $stripe_net ? $stripe_net - $refunded : $total - $refunded - $fee, 2 );
	}
}
