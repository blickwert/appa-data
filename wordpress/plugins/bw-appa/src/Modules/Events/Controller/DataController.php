<?php
/**
 * Event data for other plugins.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFields;
use BW\Appa\Modules\Events\Service\EventFormatter;

/**
 * Single source of the event data: other plugins (registration in "APPA Mitgliedschaft") read the normalised fields
 * and the labels through the filter `bw_appa_event_fields` instead of reading and formatting the meta themselves.
 */
final class DataController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly EventFormatter $formatter,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'bw_appa_event_fields', array( $this, 'fields' ), 10, 2 );
	}

	/**
	 * Normalised fields of an event plus the labels "when" and "place" (same wording as on the website).
	 *
	 * @param mixed $fields  Fields (null when nobody answered yet).
	 * @param mixed $post_id Event.
	 * @return mixed
	 */
	public function fields( mixed $fields, mixed $post_id ): mixed {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || EventRepository::POST_TYPE !== get_post_type( $post_id ) ) {
			return $fields;
		}
		$event = $this->events->find( $post_id );
		$meta  = static fn( string $key ): string => (string) get_post_meta( $post_id, $key, true );
		return array(
			'date'          => $event->date,
			'time'          => $event->time,
			'end_date'      => $event->end_date,
			'end_time'      => $event->end_time,
			'format'        => $event->format,
			'location'      => $event->location,
			'zoom_link'     => $event->zoom_link,
			'zoom_access'   => $event->zoom_access,
			'audience'      => $event->audience,
			'registration'  => $event->registration,
			'price_member'  => EventFields::price( $meta( EventRepository::META_PRICE_MEMBER ) ),
			'price_public'  => EventFields::price( $meta( EventRepository::META_PRICE_PUBLIC ) ),
			'capacity'      => EventFields::capacity( $meta( EventRepository::META_CAPACITY ) ),
			'deadline'      => EventFields::date( $meta( EventRepository::META_DEADLINE ) ),
			'reminder_days' => EventFields::days( $meta( EventRepository::META_REMINDER ) ),
			'cancel_days'   => EventFields::days( $meta( EventRepository::META_CANCEL ) ),
			'when_label'    => $this->formatter->when( $event ),
			'place_label'   => $this->formatter->place( $event ),
		);
	}
}
