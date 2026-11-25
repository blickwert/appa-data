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

	/**
	 * Date and time, e.g. "8. Oktober 2027 · 9:00–17:00 Uhr" or "8.–9. Oktober 2027 · ab 9:00 Uhr".
	 */
	public function when( Event $event ): string {
		if ( ! $event->has_date() ) {
			return '';
		}
		[ $year, $month, $day ] = array_map( 'intval', explode( '-', $event->date ) );
		$label                  = sprintf( '%d. %s %d', $day, self::month( $month ), $year );
		if ( '' !== $event->end_date && $event->end_date > $event->date ) {
			[ $end_year, $end_month, $end_day ] = array_map( 'intval', explode( '-', $event->end_date ) );
			$label = match ( true ) {
				$end_year !== $year   => sprintf( '%d. %s %d – %d. %s %d', $day, self::month( $month ), $year, $end_day, self::month( $end_month ), $end_year ),
				$end_month !== $month => sprintf( '%d. %s – %d. %s %d', $day, self::month( $month ), $end_day, self::month( $end_month ), $year ),
				default               => sprintf( '%d.–%d. %s %d', $day, $end_day, self::month( $month ), $year ),
			};
			/* translators: 1: days, 2: start time */
			return '' !== $event->time ? sprintf( __( '%1$s · ab %2$s Uhr', 'bw-appa' ), $label, self::clock( $event->time ) ) : $label;
		}
		if ( '' !== $event->time ) {
			/* translators: 1: day, 2: time or time span */
			$label = sprintf( __( '%1$s · %2$s Uhr', 'bw-appa' ), $label, self::clock( $event->time ) . ( '' !== $event->end_time ? '–' . self::clock( $event->end_time ) : '' ) );
		}
		return $label;
	}

	/**
	 * Format and place, e.g. "Vor Ort · Universität Graz", "Online via Zoom", "Vor Ort und online · Universität Graz".
	 */
	public function place( Event $event ): string {
		return match ( $event->format ) {
			'online' => __( 'Online via Zoom', 'bw-appa' ),
			'onsite' => trim( __( 'Vor Ort', 'bw-appa' ) . ( '' !== $event->location ? ' · ' . $event->location : '' ) ),
			'hybrid' => trim( __( 'Vor Ort und online', 'bw-appa' ) . ( '' !== $event->location ? ' · ' . $event->location : '' ) ),
			default  => '' !== $event->location ? $event->location : ( '' !== $event->zoom_link ? __( 'Online via Zoom', 'bw-appa' ) : '' ),
		};
	}

	/**
	 * Label for events only for members ('' for open events).
	 */
	public function badge( Event $event ): string {
		return 'members' === $event->audience ? __( 'Exklusive Veranstaltung für Mitglieder', 'bw-appa' ) : '';
	}

	/**
	 * Text of the button in event lists.
	 */
	public function call_to_action( Event $event ): string {
		return $event->registration ? __( 'Jetzt anmelden', 'bw-appa' ) : __( 'Mehr erfahren', 'bw-appa' );
	}

	/**
	 * Austrian German month name (1–12).
	 */
	private static function month( int $month ): string {
		$names = array(
			1  => __( 'Jänner', 'bw-appa' ),
			2  => __( 'Februar', 'bw-appa' ),
			3  => __( 'März', 'bw-appa' ),
			4  => __( 'April', 'bw-appa' ),
			5  => __( 'Mai', 'bw-appa' ),
			6  => __( 'Juni', 'bw-appa' ),
			7  => __( 'Juli', 'bw-appa' ),
			8  => __( 'August', 'bw-appa' ),
			9  => __( 'September', 'bw-appa' ),
			10 => __( 'Oktober', 'bw-appa' ),
			11 => __( 'November', 'bw-appa' ),
			12 => __( 'Dezember', 'bw-appa' ),
		);
		return $names[ $month ] ?? '';
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
		/* translators: %s: Zoom access data */
		return '' === $event->zoom_access ? '' : sprintf( __( 'Zugangsdaten: %s', 'bw-appa' ), $event->zoom_access );
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
