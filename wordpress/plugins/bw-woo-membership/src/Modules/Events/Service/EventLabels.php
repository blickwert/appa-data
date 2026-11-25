<?php
/**
 * Event labels for mails and the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Support\Months;

/**
 * Date and place of an event as text: the labels of bw-appa when available (EventRepository::fields()), otherwise a
 * fallback with the same rules.
 */
final class EventLabels {

	/**
	 * "8. Oktober 2027, 9:00–17:30 Uhr" (multi-day: "8.–9. Oktober 2027, ab 9:00 Uhr").
	 *
	 * @param array{date: string, time: string, end_date: string, end_time: string} $f Event fields.
	 */
	public static function when( array $f ): string {
		if ( '' !== ( $f['when_label'] ?? '' ) ) {
			return (string) $f['when_label']; // Wording of bw-appa (single source).
		}
		$day = self::day( $f['date'] );
		if ( '' === $day ) {
			return '';
		}
		$time = '' !== $f['time'] ? self::clock( $f['time'] ) : '';
		if ( '' !== $f['end_date'] && $f['end_date'] > $f['date'] ) {
			$days = self::day( $f['date'] ) . ' – ' . self::day( $f['end_date'] );
			/* translators: 1: first and last day, 2: start time */
			return '' !== $time ? sprintf( __( '%1$s, ab %2$s Uhr', 'bw-woo-membership' ), $days, $time ) : $days;
		}
		if ( '' === $time ) {
			return $day;
		}
		/* translators: 1: day, 2: time or time span */
		return sprintf( __( '%1$s, %2$s Uhr', 'bw-woo-membership' ), $day, $time . ( '' !== $f['end_time'] ? '–' . self::clock( $f['end_time'] ) : '' ) );
	}

	/**
	 * "Vor Ort: Universität Graz", "Online via Zoom", "Vor Ort und online: Universität Graz".
	 *
	 * @param array{format: string, location: string, zoom_link: string} $f Event fields.
	 */
	public static function place( array $f ): string {
		if ( '' !== ( $f['place_label'] ?? '' ) ) {
			return (string) $f['place_label'];
		}
		$location = '' !== $f['location'] ? ': ' . $f['location'] : '';
		return match ( $f['format'] ) {
			'online' => __( 'Online via Zoom', 'bw-woo-membership' ),
			'onsite' => __( 'Vor Ort', 'bw-woo-membership' ) . $location,
			'hybrid' => __( 'Vor Ort und online', 'bw-woo-membership' ) . $location,
			default  => '' !== $f['location'] ? $f['location'] : ( '' !== $f['zoom_link'] ? __( 'Online via Zoom', 'bw-woo-membership' ) : '' ),
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
		return sprintf( '%d. %s %d', (int) $m[3], Months::name( (int) $m[2] ), (int) $m[1] );
	}

	/**
	 * "9:00" from "09:00".
	 */
	private static function clock( string $time ): string {
		$parts = explode( ':', $time );
		return sprintf( '%d:%s', (int) $parts[0], $parts[1] ?? '00' );
	}
}
