<?php
/**
 * Event labels.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Service;

use BW\Appa\Modules\Events\Model\Event;

/**
 * Builds the visible labels of an event (Austrian German month names).
 */
final class EventFormatter {

	private const MONTHS = array( 1 => 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );

	/**
	 * "8. Oktober 2027 · 18:30 Uhr" – date and time, or '' without a date.
	 */
	public function when( Event $event ): string {
		if ( ! $event->has_date() ) {
			return '';
		}
		[ $year, $month, $day ] = array_map( 'intval', explode( '-', $event->date ) );
		$label                  = sprintf( '%d. %s %d', $day, self::MONTHS[ $month ], $year );
		if ( '' !== $event->time ) {
			[ $hours, $minutes ] = explode( ':', $event->time );
			$label              .= sprintf( ' · %d:%s Uhr', (int) $hours, $minutes );
		}
		return $label;
	}

	/**
	 * "Zugangsdaten: …" or '' when no access data is set.
	 */
	public function access( Event $event ): string {
		return '' === $event->zoom_access ? '' : 'Zugangsdaten: ' . $event->zoom_access;
	}

	/**
	 * Whether the event takes place today or later.
	 *
	 * @param string $today Y-m-d in the site's time zone.
	 */
	public function is_upcoming( Event $event, string $today ): bool {
		return $event->has_date() && $event->date >= $today;
	}
}
