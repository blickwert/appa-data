<?php
/**
 * Event structured data.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Seo\Service;

use BW\Appa\Modules\Events\Model\Event;

/**
 * Builds the schema.org "Event" of an event page (JSON-LD), so search engines can show date, place and prices.
 */
final class EventSchema {

	/**
	 * Schema as array (encoded by the controller).
	 *
	 * @param array{title: string, url: string, description: string, image: string, price_member: string, price_public: string, organizer: string, organizer_url: string} $page Page values.
	 * @return array<string, mixed>
	 */
	public function build( Event $event, array $page ): array {
		$schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => $page['title'],
			'url'                 => $page['url'],
			'startDate'           => self::moment( $event->date, $event->time ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/' . match ( $event->format ) {
				'online' => 'OnlineEventAttendanceMode',
				'hybrid' => 'MixedEventAttendanceMode',
				default  => '' !== $event->zoom_link && '' === $event->format ? 'OnlineEventAttendanceMode' : 'OfflineEventAttendanceMode',
			},
			'organizer'           => array(
				'@type' => 'Organization',
				'name'  => $page['organizer'],
				'url'   => $page['organizer_url'],
			),
		);
		$end = '' !== $event->end_date ? $event->end_date : $event->date;
		if ( '' !== $event->end_time || $end !== $event->date ) {
			$schema['endDate'] = self::moment( $end, '' !== $event->end_time ? $event->end_time : '' );
		}
		if ( '' !== $page['description'] ) {
			$schema['description'] = $page['description'];
		}
		if ( '' !== $page['image'] ) {
			$schema['image'] = array( $page['image'] );
		}
		$location = array();
		if ( $event->is_online() ) {
			// The Zoom link itself stays private; the event page is the virtual location.
			$location[] = array( '@type' => 'VirtualLocation', 'url' => $page['url'] );
		}
		if ( in_array( $event->format, array( 'onsite', 'hybrid' ), true ) ) {
			$location[] = array(
				'@type'   => 'Place',
				'name'    => '' !== $event->location ? $event->location : $page['organizer'],
				'address' => array( '@type' => 'PostalAddress', 'addressCountry' => 'AT' ) + ( '' !== $event->location ? array( 'name' => $event->location ) : array() ),
			);
		}
		if ( $location ) {
			$schema['location'] = 1 === count( $location ) ? $location[0] : $location;
		}
		if ( $event->registration ) {
			$offers = array();
			foreach ( array( __( 'Mitglieder', 'bw-appa' ) => $page['price_member'], __( 'Nichtmitglieder', 'bw-appa' ) => 'members' === $event->audience ? '' : $page['price_public'] ) as $name => $price ) {
				if ( '' !== $price ) {
					$offers[] = array(
						'@type'         => 'Offer',
						'name'          => $name,
						'price'         => $price,
						'priceCurrency' => 'EUR',
						'url'           => $page['url'] . '#anmeldung',
						'availability'  => 'https://schema.org/InStock',
					);
				}
			}
			if ( $offers ) {
				$schema['offers'] = $offers;
			}
		}
		return $schema;
	}

	/**
	 * ISO 8601 date or date-time in Vienna time ("2027-10-08T09:00:00+02:00").
	 */
	private static function moment( string $date, string $time ): string {
		if ( '' === $time ) {
			return $date;
		}
		return ( new \DateTimeImmutable( $date . ' ' . $time, new \DateTimeZone( 'Europe/Vienna' ) ) )->format( 'Y-m-d\TH:i:sP' );
	}
}
