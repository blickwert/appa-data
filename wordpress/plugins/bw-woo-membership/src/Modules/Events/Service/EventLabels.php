<?php
/**
 * Event labels for mails and the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

/**
 * Date and place of an event as text (the same wording as the event pages of bw-appa).
 */
final class EventLabels {

	private const MONTHS = array( 1 => 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );

	/**
	 * "8. Oktober 2027, 9:00–17:30 Uhr" (multi-day: "8.–9. Oktober 2027, ab 9:00 Uhr").
	 *
	 * @param array{date: string, time: string, end_date: string, end_time: string} $f Event fields.
	 */
	public static function when( array $f ): string {
		$day = self::day( $f['date'] );
		if ( '' === $day ) {
			return '';
		}
		$time = '' !== $f['time'] ? self::clock( $f['time'] ) : '';
		if ( '' !== $f['end_date'] && $f['end_date'] > $f['date'] ) {
			return self::day( $f['date'] ) . ' – ' . self::day( $f['end_date'] ) . ( '' !== $time ? ', ab ' . $time . ' Uhr' : '' );
		}
		if ( '' === $time ) {
			return $day;
		}
		return $day . ', ' . $time . ( '' !== $f['end_time'] ? '–' . self::clock( $f['end_time'] ) : '' ) . ' Uhr';
	}

	/**
	 * "Vor Ort: Universität Graz", "Online via Zoom", "Vor Ort und online: Universität Graz".
	 *
	 * @param array{format: string, location: string, zoom_link: string} $f Event fields.
	 */
	public static function place( array $f ): string {
		$location = '' !== $f['location'] ? ': ' . $f['location'] : '';
		return match ( $f['format'] ) {
			'online' => 'Online via Zoom',
			'onsite' => 'Vor Ort' . $location,
			'hybrid' => 'Vor Ort und online' . $location,
			default  => '' !== $f['location'] ? $f['location'] : ( '' !== $f['zoom_link'] ? 'Online via Zoom' : '' ),
		};
	}

	/**
	 * Whether people take part online (online, hybrid, or a Zoom link without format).
	 *
	 * @param array{format: string, zoom_link: string} $f Event fields.
	 */
	public static function is_online( array $f ): bool {
		return in_array( $f['format'], array( 'online', 'hybrid' ), true ) || ( '' === $f['format'] && '' !== $f['zoom_link'] );
	}

	/**
	 * "8. Oktober 2027" from Y-m-d ('' when invalid).
	 */
	public static function day( string $date ): string {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) ) {
			return '';
		}
		return sprintf( '%d. %s %d', (int) $m[3], self::MONTHS[ (int) $m[2] ], (int) $m[1] );
	}

	/**
	 * "9:00" from "09:00".
	 */
	private static function clock( string $time ): string {
		$parts = explode( ':', $time );
		return sprintf( '%d:%s', (int) $parts[0], $parts[1] ?? '00' );
	}
}
