<?php
/**
 * Event storage.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Repository;

use BW\Appa\Modules\Events\Model\Event;
use BW\Appa\Modules\Events\Service\EventFields;

/**
 * Loads events from post meta.
 */
final class EventRepository {

	public const POST_TYPE = 'appa_event';

	public const META_DATE   = 'bw_appa_event_date';
	public const META_TIME   = 'bw_appa_event_time';
	public const META_LINK   = 'bw_appa_event_zoom_link';
	public const META_ACCESS = 'bw_appa_event_zoom_access';

	public const META_AUDIENCE     = 'bw_appa_event_audience';
	public const META_PRICE_MEMBER = 'bw_appa_event_price_member';
	public const META_PRICE_PUBLIC = 'bw_appa_event_price_public';
	public const META_CAPACITY     = 'bw_appa_event_capacity';
	public const META_REGISTRATION = 'bw_appa_event_registration';
	public const META_DEADLINE     = 'bw_appa_event_deadline';
	public const META_END_DATE     = 'bw_appa_event_end_date';
	public const META_END_TIME     = 'bw_appa_event_end_time';
	public const META_FORMAT       = 'bw_appa_event_format';
	public const META_LOCATION     = 'bw_appa_event_location';
	public const META_REMINDER     = 'bw_appa_event_reminder_days';
	public const META_CANCEL       = 'bw_appa_event_cancel_days';

	/**
	 * Formats of an event, with visible labels.
	 *
	 * @return array<string, string>
	 */
	public static function formats(): array {
		return array(
			'onsite' => __( 'Vor Ort', 'bw-appa' ),
			'online' => __( 'Online', 'bw-appa' ),
			'hybrid' => __( 'Vor Ort und online', 'bw-appa' ),
		);
	}

	/**
	 * Kinds of events (meeting 9 October 2026), with visible labels.
	 *
	 * @return array<string, string>
	 */
	public static function audiences(): array {
		return array(
			'open'         => __( 'Offen – Mitglieder ermäßigt', 'bw-appa' ),
			'members'      => __( 'Nur für Mitglieder', 'bw-appa' ),
			'free_members' => __( 'Für Mitglieder gratis', 'bw-appa' ),
		);
	}

	/**
	 * Loads the event fields of a post.
	 */
	public function find( int $post_id ): Event {
		$meta = static fn( string $key ): string => (string) get_post_meta( $post_id, $key, true );

		return new Event(
			EventFields::date( $meta( self::META_DATE ) ),
			EventFields::time( $meta( self::META_TIME ) ),
			EventFields::url( $meta( self::META_LINK ) ),
			EventFields::text( $meta( self::META_ACCESS ) ),
			EventFields::date( $meta( self::META_END_DATE ) ),
			EventFields::time( $meta( self::META_END_TIME ) ),
			EventFields::format( $meta( self::META_FORMAT ) ),
			EventFields::text( $meta( self::META_LOCATION ) ),
			EventFields::audience( $meta( self::META_AUDIENCE ) ),
			'1' === $meta( self::META_REGISTRATION ),
		);
	}
}
