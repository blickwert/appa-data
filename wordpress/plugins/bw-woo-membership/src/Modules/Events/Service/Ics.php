<?php
/**
 * Calendar file for an event.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

/**
 * Builds an iCalendar file (RFC 5545) with one appointment, attached to the confirmation mail.
 */
final class Ics {

	/**
	 * Calendar text. Times are local Vienna time; without a start time the appointment lasts all day(s).
	 *
	 * @param array{uid: string, title: string, date: string, time: string, end_date: string, end_time: string, location: string, url: string, description: string} $event Values.
	 */
	public static function build( array $event, string $stamp ): string {
		$end_date = '' !== $event['end_date'] && $event['end_date'] >= $event['date'] ? $event['end_date'] : $event['date'];
		if ( '' === $event['time'] ) {
			$start = 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $event['date'] );
			$end   = 'DTEND;VALUE=DATE:' . ( new \DateTimeImmutable( $end_date ) )->modify( '+1 day' )->format( 'Ymd' );
		} else {
			$end_time = '' !== $event['end_time'] ? $event['end_time'] : ( new \DateTimeImmutable( $event['date'] . ' ' . $event['time'] ) )->modify( '+1 hour' )->format( 'H:i' );
			$start    = 'DTSTART;TZID=Europe/Vienna:' . self::local( $event['date'], $event['time'] );
			$end      = 'DTEND;TZID=Europe/Vienna:' . self::local( $end_date, $end_time );
		}
		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//APPA//Veranstaltungen//DE',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:' . $event['uid'],
			'DTSTAMP:' . $stamp,
			$start,
			$end,
			'SUMMARY:' . self::text( $event['title'] ),
		);
		if ( '' !== $event['location'] ) {
			$lines[] = 'LOCATION:' . self::text( $event['location'] );
		}
		if ( '' !== $event['description'] ) {
			$lines[] = 'DESCRIPTION:' . self::text( $event['description'] );
		}
		if ( '' !== $event['url'] ) {
			$lines[] = 'URL:' . $event['url'];
		}
		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';
		return implode( "\r\n", array_map( array( self::class, 'fold' ), $lines ) ) . "\r\n";
	}

	/**
	 * "20271008T090000" from date and time.
	 */
	private static function local( string $date, string $time ): string {
		return str_replace( '-', '', $date ) . 'T' . str_replace( ':', '', substr( $time, 0, 5 ) ) . '00';
	}

	/**
	 * Escapes a text value.
	 */
	private static function text( string $value ): string {
		return str_replace( array( '\\', ';', ',', "\r\n", "\n" ), array( '\\\\', '\;', '\,', '\n', '\n' ), $value );
	}

	/**
	 * Folds lines longer than 75 octets.
	 */
	private static function fold( string $line ): string {
		$out   = '';
		$limit = 75;
		while ( strlen( $line ) > $limit ) {
			$cut = $limit;
			while ( $cut > 1 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
				--$cut; // Do not split a UTF-8 character.
			}
			$out  .= substr( $line, 0, $cut ) . "\r\n ";
			$line  = substr( $line, $cut );
			$limit = 74; // Continuation lines start with a space.
		}
		return $out . $line;
	}
}
