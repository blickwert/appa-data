<?php
/**
 * Plugin Name: APPA Loop-Abfrage Veranstaltungen
 * Description: Query-ID "appa_events" für den Elementor-Loop: Beitragstyp "veranstaltungen", nur Termine ab heute (Feld "date"), sortiert nach Datum.
 * Version: 1.0.0
 * Author: blickwert
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor-Loop › Abfrage › Query-ID: appa_events
 *
 * Das ACF-Datumsfeld "date" wird als Ymd (z. B. 20271008) gespeichert; der Textvergleich
 * entspricht daher der zeitlichen Reihenfolge. Heute zählt noch als aktuell.
 */
add_action( 'elementor/query/appa_events', function ( $query ) {
	$query->set( 'post_type', 'veranstaltungen' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'meta_key', 'date' );
	$query->set( 'orderby', [ 'meta_value' => 'ASC', 'title' => 'ASC' ] );
	$query->set( 'meta_query', [
		[
			'key'     => 'date',
			'value'   => wp_date( 'Ymd' ),
			'compare' => '>=',
			'type'    => 'CHAR',
		],
	] );
} );

/**
 * Shortcode für Dynamic Tag "Shortcode" im Loop:
 *   [appa_termin]            →  8. Oktober 2027 · 18:00 Uhr
 *   [appa_termin feld="zugang"]  →  Zugangsdaten: …  (leer, wenn nichts eingetragen)
 */
add_shortcode( 'appa_termin', function ( $atts ) {
	$atts = shortcode_atts( [ 'feld' => 'termin', 'id' => 0 ], $atts, 'appa_termin' );
	$id   = (int) $atts['id'] ?: (int) get_the_ID();
	if ( $id <= 0 ) {
		return '';
	}

	if ( 'zugang' === $atts['feld'] ) {
		$access = trim( (string) get_post_meta( $id, 'zoom-access', true ) );
		return '' === $access ? '' : esc_html( 'Zugangsdaten: ' . $access );
	}

	$months = [ 1 => 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ];
	$date   = (string) get_post_meta( $id, 'date', true );
	$out    = '';
	if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		$out = (int) $m[3] . '. ' . $months[ (int) $m[2] ] . ' ' . (int) $m[1];
		$time = (string) get_post_meta( $id, 'time', true );
		if ( preg_match( '/^(\d{1,2}):(\d{2})/', $time, $t ) ) {
			$out .= ' · ' . (int) $t[1] . ':' . $t[2] . ' Uhr';
		}
	}
	return esc_html( $out );
} );
