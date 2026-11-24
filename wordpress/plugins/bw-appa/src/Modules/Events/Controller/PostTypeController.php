<?php
/**
 * Post type and fields.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFields;

/**
 * Registers the event post type, its meta fields and the admin list columns.
 */
final class PostTypeController {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'manage_' . EventRepository::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . EventRepository::POST_TYPE . '_posts_custom_column', array( $this, 'column_value' ), 10, 2 );
		add_filter( 'manage_edit-' . EventRepository::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * The event post type; its archive lives at /veranstaltungen/.
	 */
	public function register_post_type(): void {
		register_post_type(
			EventRepository::POST_TYPE,
			array(
				'labels'        => array(
					'name'               => __( 'Veranstaltungen', 'bw-appa' ),
					'singular_name'      => __( 'Veranstaltung', 'bw-appa' ),
					'add_new'            => __( 'Neue Veranstaltung', 'bw-appa' ),
					'add_new_item'       => __( 'Neue Veranstaltung anlegen', 'bw-appa' ),
					'edit_item'          => __( 'Veranstaltung bearbeiten', 'bw-appa' ),
					'view_item'          => __( 'Veranstaltung ansehen', 'bw-appa' ),
					'all_items'          => __( 'Alle Veranstaltungen', 'bw-appa' ),
					'search_items'       => __( 'Veranstaltungen durchsuchen', 'bw-appa' ),
					'not_found'          => __( 'Keine Veranstaltungen gefunden.', 'bw-appa' ),
					'not_found_in_trash' => __( 'Keine Veranstaltungen im Papierkorb.', 'bw-appa' ),
					'menu_name'          => __( 'Veranstaltungen', 'bw-appa' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 20,
				'has_archive'   => 'veranstaltungen',
				'rewrite'       => array(
					'slug'       => 'veranstaltungen',
					'with_front' => false,
				),
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'template'      => array(
					array( 'core/paragraph', array( 'placeholder' => __( 'Beschreibung der Veranstaltung …', 'bw-appa' ) ) ),
				),
			)
		);
	}

	/**
	 * Meta fields, exposed to the REST API for the editor panel.
	 */
	public function register_meta(): void {
		$fields = array(
			EventRepository::META_DATE   => array( EventFields::class, 'date' ),
			EventRepository::META_TIME   => array( EventFields::class, 'time' ),
			EventRepository::META_LINK   => array( EventFields::class, 'url' ),
			EventRepository::META_ACCESS => array( EventFields::class, 'text' ),
			EventRepository::META_AUDIENCE     => array( EventFields::class, 'audience' ),
			EventRepository::META_PRICE_MEMBER => array( EventFields::class, 'price' ),
			EventRepository::META_PRICE_PUBLIC => array( EventFields::class, 'price' ),
			EventRepository::META_CAPACITY     => array( EventFields::class, 'capacity' ),
			EventRepository::META_REGISTRATION => array( EventFields::class, 'flag' ),
		);
		foreach ( $fields as $key => $sanitize ) {
			register_post_meta(
				EventRepository::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => static fn( mixed $value ): string => $sanitize( is_scalar( $value ) ? (string) $value : '' ),
					'auth_callback'     => static fn( bool $allowed, string $meta_key, int $post_id ): bool => current_user_can( 'edit_post', $post_id ),
				)
			);
		}
	}

	/**
	 * Adds the date column after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['bw_appa_event_date'] = __( 'Termin', 'bw-appa' );
			}
		}
		unset( $out['date'] );
		return $out;
	}

	/**
	 * Prints the event date in the list.
	 */
	public function column_value( string $column, int $post_id ): void {
		if ( 'bw_appa_event_date' !== $column ) {
			return;
		}
		$date = EventFields::date( (string) get_post_meta( $post_id, EventRepository::META_DATE, true ) );
		$time = EventFields::time( (string) get_post_meta( $post_id, EventRepository::META_TIME, true ) );
		echo esc_html( '' === $date ? '–' : trim( wp_date( 'j. F Y', (int) strtotime( $date ) ) . ' ' . $time ) );
	}

	/**
	 * Makes the date column sortable.
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string>
	 */
	public function sortable_columns( array $columns ): array {
		$columns['bw_appa_event_date'] = EventRepository::META_DATE;
		return $columns;
	}
}
