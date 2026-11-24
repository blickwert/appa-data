<?php
/**
 * Event prices.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

/**
 * Who pays what for an event (meeting 9 October 2026): members-only events are not open to non-members, open events
 * have a reduced member price, "free for members" events cost members nothing.
 */
final class EventPricing {

	/**
	 * Price for a participant, or null when the event cannot be booked by them.
	 *
	 * @param string $audience     open | members | free_members.
	 * @param bool   $is_member    Active membership.
	 * @param string $price_member Decimal string or ''.
	 * @param string $price_public Decimal string or ''.
	 */
	public static function price( string $audience, bool $is_member, string $price_member, string $price_public ): ?float {
		$member = '' !== $price_member ? (float) $price_member : null;
		$public = '' !== $price_public ? (float) $price_public : null;
		return match ( $audience ) {
			'members'      => $is_member ? ( $member ?? 0.0 ) : null,
			'free_members' => $is_member ? 0.0 : $public,
			default        => $is_member ? ( $member ?? $public ) : $public,
		};
	}

	/**
	 * Free places: capacity minus registrations; null when unlimited.
	 */
	public static function places_left( string $capacity, int $registrations ): ?int {
		return '' === $capacity ? null : max( 0, (int) $capacity - $registrations );
	}

	/**
	 * Display text of a price ("35,00 €", "kostenlos").
	 */
	public static function label( ?float $price ): string {
		if ( null === $price ) {
			return '';
		}
		return $price <= 0.0 ? 'kostenlos' : number_format( $price, 2, ',', '.' ) . ' €';
	}

	/**
	 * Whether registration is still open: not after the event date, not after the registration deadline.
	 */
	public static function open( string $date, string $deadline, string $today ): bool {
		return ! ( ( '' !== $date && $date < $today ) || ( '' !== $deadline && $deadline < $today ) );
	}

	/**
	 * People on the waiting list to inform about free places: the first ones not informed yet, as many as places.
	 *
	 * @param list<array{user: int, since: string, notified: string}> $list Waiting list.
	 * @return int[]
	 */
	public static function to_notify( array $list, ?int $places_left ): array {
		$free = null === $places_left ? PHP_INT_MAX : $places_left;
		$out  = array();
		foreach ( $list as $entry ) {
			if ( count( $out ) >= $free ) {
				break;
			}
			if ( '' === $entry['notified'] ) {
				$out[] = $entry['user'];
			}
		}
		return $out;
	}
}
