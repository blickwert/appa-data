<?php
/**
 * Plugin Name: APPA Veranstaltungen
 * Description: Veranstaltungen als eigener Beitragstyp mit ACF-Feldern und dem Query-Hook "appa_events" für den Elementor-Loop: zeigt nur laufende und künftige Termine.
 * Version: 1.0.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: blickwert
 * License: GPL-2.0-or-later
 * Text Domain: appa-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APPA_EVENTS_VERSION', '1.0.1' );
define( 'APPA_EVENTS_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-appa-events.php';

final class APPA_Events_Plugin {

	const POST_TYPE   = 'veranstaltung';
	const QUERY_ID    = 'appa_events';
	const META_END    = '_appa_ende';
	const META_SORT   = '_appa_sort';
	const OPT_VERSION = 'appa_events_version';

	public static function init() {
		// Beitragstyp und Feldgruppe liegen als ACF-JSON im Plugin (siehe acf-json/).
		add_filter( 'acf/settings/load_json', [ __CLASS__, 'acf_json_path' ] );

		add_action( 'init', [ __CLASS__, 'maybe_rebuild' ], 30 );
		add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'sync_post' ], 30 );
		add_action( 'added_post_meta', [ __CLASS__, 'on_meta_change' ], 10, 3 );
		add_action( 'updated_post_meta', [ __CLASS__, 'on_meta_change' ], 10, 3 );
		add_action( 'deleted_post_meta', [ __CLASS__, 'on_meta_change' ], 10, 3 );

		// Elementor-Loop mit Query-ID "appa_events".
		add_action( 'elementor/query/' . self::QUERY_ID, [ __CLASS__, 'filter_loop_query' ], 10, 2 );

		add_shortcode( 'appa_termin', [ __CLASS__, 'shortcode_termin' ] );

		add_filter( 'acf/validate_value/name=datum_bis', [ __CLASS__, 'validate_end' ], 10, 4 );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'admin_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'admin_column_value' ], 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', [ __CLASS__, 'admin_sortable' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'admin_order' ] );

		add_action( 'admin_notices', [ __CLASS__, 'dependency_notice' ] );

		// Elementor listet nur Beitragstypen mit show_in_nav_menus (für den Loop nötig).
		// Veranstaltungen haben keine eigene Seite, daher im Menü-Editor ausblenden.
		add_filter( 'nav_menu_meta_box_object', [ __CLASS__, 'hide_from_menu_editor' ] );
	}

	public static function hide_from_menu_editor( $post_type ) {
		return ( $post_type && self::POST_TYPE === $post_type->name ) ? false : $post_type;
	}

	public static function acf_json_path( $paths ) {
		$paths[] = APPA_EVENTS_DIR . '/acf-json';
		return $paths;
	}

	/* ------------------------------------------------------------------ */
	/* Abgeleitete Felder: Ende und Sortierwert                            */
	/* ------------------------------------------------------------------ */

	public static function sync_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || self::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		$von = get_post_meta( $post_id, 'datum_von', true );
		$bis = get_post_meta( $post_id, 'datum_bis', true );

		$end  = APPA_Events::end_key( $von, $bis );
		$sort = APPA_Events::sort_key( $von, $bis );

		if ( get_post_meta( $post_id, self::META_END, true ) !== $end ) {
			update_post_meta( $post_id, self::META_END, $end );
		}
		if ( get_post_meta( $post_id, self::META_SORT, true ) !== $sort ) {
			update_post_meta( $post_id, self::META_SORT, $sort );
		}
	}

	/** Greift auch, wenn Felder per REST/Bridge/Import gesetzt werden und save_post früher lief. */
	public static function on_meta_change( $meta_id, $post_id, $meta_key ) {
		if ( 'datum_von' === $meta_key || 'datum_bis' === $meta_key ) {
			self::sync_post( $post_id );
		}
	}

	/** Beim Aktivieren bzw. nach einem Update alle vorhandenen Veranstaltungen nachziehen. */
	public static function maybe_rebuild() {
		if ( get_option( self::OPT_VERSION ) === APPA_EVENTS_VERSION ) {
			return;
		}
		$ids = get_posts( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		foreach ( $ids as $id ) {
			self::sync_post( $id );
		}
		update_option( self::OPT_VERSION, APPA_EVENTS_VERSION, true );
	}

	/* ------------------------------------------------------------------ */
	/* Elementor-Loop                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Nur Veranstaltungen, die heute noch laufen oder in der Zukunft liegen;
	 * Termine ohne Datum ("in Vorbereitung") bleiben sichtbar. Sortiert nach Beginn.
	 *
	 * @param WP_Query $query
	 */
	public static function filter_loop_query( $query, $widget = null ) {
		if ( ! $query instanceof WP_Query ) {
			return;
		}
		$query->set( 'post_type', self::POST_TYPE );
		$query->set( 'post_status', 'publish' );
		$query->set( 'meta_key', self::META_SORT );
		$query->set( 'orderby', [ 'meta_value' => 'ASC', 'title' => 'ASC' ] );
		$query->set( 'order', 'ASC' );
		$query->set( 'meta_query', [
			[
				'key'     => self::META_END,
				'value'   => wp_date( 'Ymd' ),
				'compare' => '>=',
				'type'    => 'CHAR',
			],
		] );
	}

	/* ------------------------------------------------------------------ */
	/* Shortcode [appa_termin id=""] – Terminzeile für Dynamic Tag "Shortcode" */
	/* ------------------------------------------------------------------ */

	public static function shortcode_termin( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'appa_termin' );
		$id   = (int) $atts['id'] ?: (int) get_the_ID();
		if ( $id <= 0 ) {
			return '';
		}
		return esc_html( APPA_Events::label( [
			'datum_von'     => get_post_meta( $id, 'datum_von', true ),
			'datum_bis'     => get_post_meta( $id, 'datum_bis', true ),
			'uhrzeit'       => get_post_meta( $id, 'uhrzeit', true ),
			'datum_hinweis' => get_post_meta( $id, 'datum_hinweis', true ),
		] ) );
	}

	/* ------------------------------------------------------------------ */
	/* Eingabeprüfung                                                      */
	/* ------------------------------------------------------------------ */

	public static function validate_end( $valid, $value, $field, $input_name ) {
		if ( true !== $valid || '' === (string) $value ) {
			return $valid;
		}
		// ACF sendet die Felder unter $_POST['acf'][<Feldschlüssel>] (Format Ymd).
		$von = isset( $_POST['acf']['field_appa_ev_datum_von'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_text_field( wp_unslash( $_POST['acf']['field_appa_ev_datum_von'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
			: '';
		if ( '' !== $von && (string) $value < $von ) {
			return 'Das Ende darf nicht vor dem Beginn liegen.';
		}
		return $valid;
	}

	/* ------------------------------------------------------------------ */
	/* Admin-Liste                                                         */
	/* ------------------------------------------------------------------ */

	public static function admin_columns( $columns ) {
		$out = [];
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['appa_termin'] = 'Termin';
				$out['appa_status'] = 'Anzeige';
			}
		}
		return $out;
	}

	public static function admin_column_value( $column, $post_id ) {
		if ( 'appa_termin' === $column ) {
			echo do_shortcode( '[appa_termin id="' . (int) $post_id . '"]' );
		} elseif ( 'appa_status' === $column ) {
			$end = (string) get_post_meta( $post_id, self::META_END, true );
			echo ( '' !== $end && $end >= wp_date( 'Ymd' ) )
				? esc_html( 'Wird angezeigt' )
				: '<span style="color:#b32d2e">' . esc_html( 'Vorbei, nicht mehr sichtbar' ) . '</span>';
		}
	}

	public static function admin_sortable( $columns ) {
		$columns['appa_termin'] = 'appa_termin';
		return $columns;
	}

	public static function admin_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( 'appa_termin' === $query->get( 'orderby' ) || ! $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', self::META_SORT );
			$query->set( 'orderby', 'meta_value' );
			if ( ! isset( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$query->set( 'order', 'DESC' );
			}
		}
	}

	public static function dependency_notice() {
		if ( function_exists( 'acf_get_field_groups' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>APPA Veranstaltungen</strong> benötigt Advanced Custom Fields (ACF).</p></div>';
	}
}

APPA_Events_Plugin::init();
