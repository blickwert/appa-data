<?php
/**
 * Rules for cancelling and reminding event registrations.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

/**
 * Self-service cancellation and reminder dates (concept 25 November 2026, docs/OFFENE-FRAGEN.md V7):
 * free registrations can be cancelled until the day of the event; paid ones with a full refund until a number of
 * days before (event field, default 14); a reminder goes out a number of days before (event field, default 2).
 */
final class RegistrationRules {

	public const CANCEL_DAYS   = 14;
	public const REMINDER_DAYS = 2;

	/**
	 * Last day (Y-m-d) on which the person can cancel on the website, '' when the event has no date.
	 */
	public static function cancel_until( string $event_date, string $cancel_days, bool $paid ): string {
		if ( '' === $event_date ) {
			return '';
		}
		if ( ! $paid ) {
			return $event_date;
		}
		$days = '' === $cancel_days ? self::CANCEL_DAYS : max( 0, (int) $cancel_days );
		return ( new \DateTimeImmutable( $event_date ) )->modify( '-' . $days . ' days' )->format( 'Y-m-d' );
	}

	/**
	 * Whether the person can still cancel on the website.
	 */
	public static function can_cancel( string $event_date, string $cancel_days, bool $paid, string $today ): bool {
		$until = self::cancel_until( $event_date, $cancel_days, $paid );
		return '' !== $until && $today <= $until;
	}

	/**
	 * Day (Y-m-d) of the reminder, '' when none (no date or 0 days).
	 */
	public static function reminder_day( string $event_date, string $reminder_days ): string {
		$days = '' === $reminder_days ? self::REMINDER_DAYS : (int) $reminder_days;
		if ( '' === $event_date || $days <= 0 ) {
			return '';
		}
		return ( new \DateTimeImmutable( $event_date ) )->modify( '-' . $days . ' days' )->format( 'Y-m-d' );
	}

	/**
	 * Whether a reminder is due today: from the reminder day until the day before the event (late registrations
	 * still get it once).
	 */
	public static function reminder_due( string $event_date, string $reminder_days, string $today ): bool {
		$day = self::reminder_day( $event_date, $reminder_days );
		return '' !== $day && $today >= $day && $today < $event_date;
	}
}
