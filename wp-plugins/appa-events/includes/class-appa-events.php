<?php
/**
 * Reine Logik der APPA-Veranstaltungen: Terminzeile und Sichtbarkeit.
 *
 * Bewusst ohne WordPress-Funktionen, damit sich alles in tests/test-events.php
 * ohne WordPress prüfen lässt. Daten sind Strings im ACF-Format "Ymd" (20271008).
 */

if ( ! defined( 'ABSPATH' ) && PHP_SAPI !== 'cli' ) {
	exit;
}

class APPA_Events {

	/** Wert für "kein Ende": Veranstaltungen ohne Datum bleiben immer sichtbar. */
	const NO_END = '99991231';

	/** Sortierwert ohne Datum: Termine "in Vorbereitung" stehen vor den datierten. */
	const NO_START = '00000000';

	/** Österreichische Monatsnamen (Jänner statt Januar). */
	const MONTHS = [
		1 => 'Jänner', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
		7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
	];

	/** Gültiges "Ymd" oder leerer String. */
	public static function clean_date( $value ): string {
		$value = is_string( $value ) || is_int( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $value, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
	}

	/**
	 * Datumsbereich als Text.
	 *
	 *  8. Oktober 2027                    (ein Tag)
	 *  8. und 9. Oktober 2027             (zwei aufeinanderfolgende Tage)
	 *  8.–12. Oktober 2027                (derselbe Monat)
	 *  30. Oktober – 2. November 2027     (gleiches Jahr)
	 *  30. Dezember 2027 – 2. Jänner 2028 (Jahreswechsel)
	 */
	public static function format_range( $von, $bis = '' ): string {
		$von = self::clean_date( $von );
		$bis = self::clean_date( $bis );
		if ( '' === $von ) {
			return '';
		}
		if ( '' === $bis || $bis <= $von ) {
			return self::format_day( $von, true, true );
		}

		list( $y1, $m1, $d1 ) = self::parts( $von );
		list( $y2, $m2, $d2 ) = self::parts( $bis );

		if ( $y1 === $y2 && $m1 === $m2 ) {
			$next_day = ( 1 === $d2 - $d1 );
			return $next_day
				? sprintf( '%d. und %d. %s %d', $d1, $d2, self::MONTHS[ $m1 ], $y1 )
				: sprintf( '%d.–%d. %s %d', $d1, $d2, self::MONTHS[ $m1 ], $y1 );
		}
		if ( $y1 === $y2 ) {
			return self::format_day( $von, true, false ) . ' – ' . self::format_day( $bis, true, true );
		}
		return self::format_day( $von, true, true ) . ' – ' . self::format_day( $bis, true, true );
	}

	/**
	 * Terminzeile der Karte: Datum · Uhrzeit · Hinweis.
	 *
	 * Fehlt das Datum, steht nur der Hinweis ("In Vorbereitung"); fehlt auch der,
	 * "Termin folgt".
	 */
	public static function label( array $f ): string {
		$parts = [];

		$range = self::format_range( $f['datum_von'] ?? '', $f['datum_bis'] ?? '' );
		if ( '' !== $range ) {
			$parts[] = $range;
			$time    = trim( (string) ( $f['uhrzeit'] ?? '' ) );
			if ( '' !== $time ) {
				$parts[] = $time;
			}
		}

		$hint = trim( (string) ( $f['datum_hinweis'] ?? '' ) );
		if ( '' !== $hint ) {
			$parts[] = $hint;
		}

		return $parts ? implode( ' · ', $parts ) : 'Termin folgt';
	}

	/** Letzter Tag der Veranstaltung ("Ymd"); ohne Datum NO_END. */
	public static function end_key( $von, $bis ): string {
		$von = self::clean_date( $von );
		$bis = self::clean_date( $bis );
		if ( '' === $von && '' === $bis ) {
			return self::NO_END;
		}
		// Ein Ende vor dem Beginn (Eingabefehler) zählt nicht.
		if ( '' !== $bis && ( '' === $von || $bis >= $von ) ) {
			return $bis;
		}
		return '' !== $von ? $von : $bis;
	}

	/** Sortierwert: Beginn, ohne Datum NO_START. */
	public static function sort_key( $von, $bis = '' ): string {
		$von = self::clean_date( $von );
		if ( '' !== $von ) {
			return $von;
		}
		$bis = self::clean_date( $bis );
		return '' !== $bis ? $bis : self::NO_START;
	}

	/** Läuft die Veranstaltung heute noch oder liegt sie in der Zukunft? */
	public static function is_current_or_future( $von, $bis, string $today ): bool {
		return self::end_key( $von, $bis ) >= $today;
	}

	/** @return array{0:int,1:int,2:int} Jahr, Monat, Tag */
	private static function parts( string $ymd ): array {
		return [ (int) substr( $ymd, 0, 4 ), (int) substr( $ymd, 4, 2 ), (int) substr( $ymd, 6, 2 ) ];
	}

	private static function format_day( string $ymd, bool $month, bool $year ): string {
		list( $y, $m, $d ) = self::parts( $ymd );
		return $d . '.' . ( $month ? ' ' . self::MONTHS[ $m ] : '' ) . ( $year ? ' ' . $y : '' );
	}
}
