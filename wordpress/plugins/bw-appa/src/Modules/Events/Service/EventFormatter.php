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
	 * Date and time, e.g. "8. Oktober 2027 · 9:00–17:00 Uhr" or "8.–9. Oktober 2027 · ab 9:00 Uhr".
	 */
	public function when( Event $event ): string {
		if ( ! $event->has_date() ) {
			return '';
		}
		[ $year, $month, $day ] = array_map( 'intval', explode( '-', $event->date ) );
		$label                  = sprintf( '%d. %s %d', $day, self::MONTHS[ $month ], $year );
		if ( '' !== $event->end_date && $event->end_date > $event->date ) {
			[ $end_year, $end_month, $end_day ] = array_map( 'intval', explode( '-', $event->end_date ) );
			$label = match ( true ) {
				$end_year !== $year   => sprintf( '%d. %s %d – %d. %s %d', $day, self::MONTHS[ $month ], $year, $end_day, self::MONTHS[ $end_month ], $end_year ),
				$end_month !== $month => sprintf( '%d. %s – %d. %s %d', $day, self::MONTHS[ $month ], $end_day, self::MONTHS[ $end_month ], $year ),
				default               => sprintf( '%d.–%d. %s %d', $day, $end_day, self::MONTHS[ $month ], $year ),
			};
			return '' !== $event->time ? $label . ' · ab ' . self::clock( $event->time ) . ' Uhr' : $label;
		}
		if ( '' !== $event->time ) {
			$label .= ' · ' . self::clock( $event->time ) . ( '' !== $event->end_time ? '–' . self::clock( $event->end_time ) : '' ) . ' Uhr';
		}
		return $label;
	}

	/**
	 * Format and place, e.g. "Vor Ort · Universität Graz", "Online via Zoom", "Vor Ort und online · Universität Graz".
	 */
	public function place( Event $event ): string {
		return match ( $event->format ) {
			'online' => 'Online via Zoom',
			'onsite' => trim( 'Vor Ort' . ( '' !== $event->location ? ' · ' . $event->location : '' ) ),
			'hybrid' => trim( 'Vor Ort und online' . ( '' !== $event->location ? ' · ' . $event->location : '' ) ),
			default  => '' !== $event->location ? $event->location : ( '' !== $event->zoom_link ? 'Online via Zoom' : '' ),
		};
	}

	/**
	 * Label for events only for members ('' for open events).
	 */
	public function badge( Event $event ): string {
		return 'members' === $event->audience ? 'Exklusive Veranstaltung für Mitglieder' : '';
	}

	/**
	 * Text of the button in event lists.
	 */
	public function call_to_action( Event $event ): string {
		return $event->registration ? 'Jetzt anmelden' : 'Mehr erfahren';
	}

	/**
	 * "9:00" from "09:00".
	 */
	private static function clock( string $time ): string {
		[ $hours, $minutes ] = explode( ':', $time );
		return sprintf( '%d:%s', (int) $hours, $minutes );
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
