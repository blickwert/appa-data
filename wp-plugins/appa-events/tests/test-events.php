<?php
/**
 * Tests für die reine Logik (ohne WordPress):  php tests/test-events.php
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

require_once __DIR__ . '/../includes/class-appa-events.php';

$failed = 0;
$total  = 0;

function check( string $name, $expected, $actual ): void {
	global $failed, $total;
	$total++;
	if ( $expected === $actual ) {
		return;
	}
	$failed++;
	fwrite( STDERR, "FAIL $name\n  erwartet: " . var_export( $expected, true ) . "\n  erhalten: " . var_export( $actual, true ) . "\n" );
}

/* --- clean_date ----------------------------------------------------- */
check( 'clean ok', '20271008', APPA_Events::clean_date( '20271008' ) );
check( 'clean int', '20271008', APPA_Events::clean_date( 20271008 ) );
check( 'clean leer', '', APPA_Events::clean_date( '' ) );
check( 'clean null', '', APPA_Events::clean_date( null ) );
check( 'clean Format', '', APPA_Events::clean_date( '2027-10-08' ) );
check( 'clean 30. Februar', '', APPA_Events::clean_date( '20270230' ) );
check( 'clean Schaltjahr', '20280229', APPA_Events::clean_date( '20280229' ) );
check( 'clean kein Schaltjahr', '', APPA_Events::clean_date( '20270229' ) );

/* --- format_range --------------------------------------------------- */
check( 'ein Tag', '8. Oktober 2027', APPA_Events::format_range( '20271008' ) );
check( 'Ende gleich Beginn', '8. Oktober 2027', APPA_Events::format_range( '20271008', '20271008' ) );
check( 'Ende vor Beginn', '8. Oktober 2027', APPA_Events::format_range( '20271008', '20271001' ) );
check( 'zwei Tage (Briefing)', '8. und 9. Oktober 2027', APPA_Events::format_range( '20271008', '20271009' ) );
check( 'mehrere Tage', '8.–12. Oktober 2027', APPA_Events::format_range( '20271008', '20271012' ) );
check( 'Monatswechsel', '30. Oktober – 2. November 2027', APPA_Events::format_range( '20271030', '20271102' ) );
check( 'Jahreswechsel', '30. Dezember 2027 – 2. Jänner 2028', APPA_Events::format_range( '20271230', '20280102' ) );
check( 'Jänner', '1. Jänner 2028', APPA_Events::format_range( '20280101' ) );
check( 'kein Datum', '', APPA_Events::format_range( '' ) );
check( 'nur Ende ohne Beginn', '', APPA_Events::format_range( '', '20271009' ) );

/* --- label (Briefing: V2 und V3) ------------------------------------ */
check(
	'V2 in Vorbereitung',
	'In Vorbereitung',
	APPA_Events::label( [ 'datum_hinweis' => 'In Vorbereitung' ] )
);
check(
	'V3 Fachtagung',
	'8. und 9. Oktober 2027 · vorläufig geplant',
	APPA_Events::label( [ 'datum_von' => '20271008', 'datum_bis' => '20271009', 'datum_hinweis' => 'vorläufig geplant' ] )
);
check(
	'mit Uhrzeit',
	'14. Oktober 2026 · 18:00–19:30 Uhr',
	APPA_Events::label( [ 'datum_von' => '20261014', 'uhrzeit' => '18:00–19:30 Uhr' ] )
);
check(
	'Uhrzeit ohne Datum wird nicht gezeigt',
	'In Vorbereitung',
	APPA_Events::label( [ 'uhrzeit' => '18:00 Uhr', 'datum_hinweis' => 'In Vorbereitung' ] )
);
check( 'gar nichts', 'Termin folgt', APPA_Events::label( [] ) );
check( 'Leerzeichen', 'Termin folgt', APPA_Events::label( [ 'datum_hinweis' => '   ' ] ) );

/* --- end_key / sort_key / is_current_or_future ----------------------- */
check( 'end ohne Daten', '99991231', APPA_Events::end_key( '', '' ) );
check( 'end nur Beginn', '20271008', APPA_Events::end_key( '20271008', '' ) );
check( 'end mit Ende', '20271009', APPA_Events::end_key( '20271008', '20271009' ) );
check( 'end Ende vor Beginn zählt nicht', '20271008', APPA_Events::end_key( '20271008', '20271001' ) );
check( 'end nur Ende', '20271009', APPA_Events::end_key( '', '20271009' ) );
check( 'end ungültig', '99991231', APPA_Events::end_key( 'abc', '20270230' ) );
check( 'sort ohne Datum zuerst', '00000000', APPA_Events::sort_key( '', '' ) );
check( 'sort mit Beginn', '20271008', APPA_Events::sort_key( '20271008', '20271009' ) );

$today = '20261008';
check( 'vorbei', false, APPA_Events::is_current_or_future( '20261001', '', $today ) );
check( 'gestern beendet', false, APPA_Events::is_current_or_future( '20261005', '20261007', $today ) );
check( 'heute (ein Tag)', true, APPA_Events::is_current_or_future( '20261008', '', $today ) );
check( 'läuft gerade (mehrtägig)', true, APPA_Events::is_current_or_future( '20261006', '20261009', $today ) );
check( 'heute ist letzter Tag', true, APPA_Events::is_current_or_future( '20261006', '20261008', $today ) );
check( 'Zukunft', true, APPA_Events::is_current_or_future( '20271008', '20271009', $today ) );
check( 'ohne Datum bleibt sichtbar', true, APPA_Events::is_current_or_future( '', '', $today ) );
check( 'Jahreswechsel', true, APPA_Events::is_current_or_future( '20261230', '20270102', '20261231' ) );

echo "$total Prüfungen, $failed fehlgeschlagen\n";
exit( $failed ? 1 : 0 );
