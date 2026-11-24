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

	/**
	 * Kinds of events (meeting 9 October 2026).
	 */
	public const AUDIENCES = array(
		'open'         => 'Offen – Mitglieder ermäßigt',
		'members'      => 'Nur für Mitglieder',
		'free_members' => 'Für Mitglieder gratis',
	);

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
		);
	}
}
