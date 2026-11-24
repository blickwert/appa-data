<?php
/**
 * Event registrations.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Events\Repository\EventRepository;

/**
 * Decides price and bookability of an event for a user and prepares the registration box.
 */
final class RegistrationService {

	/**
	 * Builds the service.
	 */
	public function __construct( private readonly EventRepository $events ) {
	}

	/**
	 * Whether the user has an active membership (answered by the membership module).
	 */
	public function is_member( int $user_id ): bool {
		/** This filter is documented in src/Modules/Documents/Controller/DownloadController.php */
		return $user_id > 0 && (bool) apply_filters( 'bw_wm_user_has_access', false, $user_id );
	}

	/**
	 * Price for the user, or null when not bookable for them.
	 */
	public function price( int $event_id, int $user_id ): ?float {
		$f = $this->events->fields( $event_id );
		return EventPricing::price( $f['audience'], $this->is_member( $user_id ), $f['price_member'], $f['price_public'] );
	}

	/**
	 * Whether the event lies in the past.
	 */
	public function is_past( int $event_id ): bool {
		$date = $this->events->fields( $event_id )['date'];
		return '' !== $date && $date < current_time( 'Y-m-d' );
	}

	/**
	 * Reason why the user cannot book, or '' when bookable.
	 */
	public function refusal( int $event_id, int $user_id ): string {
		$f = $this->events->fields( $event_id );
		if ( ! $f['registration'] || 'publish' !== get_post_status( $event_id ) ) {
			return 'Für diese Veranstaltung ist keine Anmeldung über die Website möglich.';
		}
		if ( $this->is_past( $event_id ) ) {
			return 'Die Anmeldung ist geschlossen.';
		}
		if ( $user_id > 0 && $this->events->registration_of( $event_id, $user_id ) ) {
			return 'Du bist für diese Veranstaltung bereits angemeldet.';
		}
		if ( null === $this->price( $event_id, $user_id ) ) {
			return 'members' === $f['audience'] ? 'Diese Veranstaltung ist nur für Mitglieder. Bitte melde dich an oder werde Mitglied.' : 'Für diese Veranstaltung ist derzeit keine Anmeldung möglich.';
		}
		$left = EventPricing::places_left( $f['capacity'], $this->events->count_registrations( $event_id ) );
		if ( 0 === $left ) {
			return 'Die Veranstaltung ist ausgebucht.';
		}
		return '';
	}

	/**
	 * Values for the registration box on the event page.
	 *
	 * @return array<string, mixed>
	 */
	public function box( int $event_id, int $user_id ): array {
		$f            = $this->events->fields( $event_id );
		$registration = $this->events->registration_of( $event_id, $user_id );
		$product_id   = $this->events->product_id( $event_id );
		$member_price = EventPricing::price( $f['audience'], true, $f['price_member'], $f['price_public'] );
		$public_price = EventPricing::price( $f['audience'], false, $f['price_member'], $f['price_public'] );
		$refusal      = $this->refusal( $event_id, $user_id );
		return array(
			'enabled'      => $f['registration'],
			'audience'     => $f['audience'],
			'member_price' => EventPricing::label( $member_price ),
			'public_price' => EventPricing::label( $public_price ),
			'is_member'    => $this->is_member( $user_id ),
			'places_left'  => EventPricing::places_left( $f['capacity'], $this->events->count_registrations( $event_id ) ),
			'registered'   => null !== $registration,
			'paid'         => null !== $registration && $registration->is_paid(),
			'refusal'      => null !== $registration ? '' : $refusal,
			'book_url'     => '' === $refusal && $product_id && function_exists( 'wc_get_checkout_url' ) ? add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() ) : '',
			'login_url'    => $user_id > 0 ? '' : wp_login_url( (string) get_permalink( $event_id ) ),
			'join_url'     => home_url( '/mitgliedschaft/' ),
			'area_url'     => home_url( '/mitgliederbereich-uebersicht/#veranstaltungen' ),
		);
	}

	/**
	 * The user's registrations for the member area, upcoming first.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function registrations( int $user_id ): array {
		$items = array();
		foreach ( $this->events->registrations_of_customer( $user_id ) as $event_id => $order ) {
			if ( 'publish' !== get_post_status( $event_id ) ) {
				continue;
			}
			$f       = $this->events->fields( $event_id );
			$day     = \DateTimeImmutable::createFromFormat( '!Y-m-d', $f['date'] );
			$paid    = $order->is_paid();
			$items[] = array(
				'title'   => get_the_title( $event_id ),
				'url'     => (string) get_permalink( $event_id ),
				'date'    => $f['date'],
				'when'    => $day ? $day->format( 'd.m.Y' ) . ( '' !== $f['time'] ? ', ' . $f['time'] . ' Uhr' : '' ) : '',
				'past'    => '' !== $f['date'] && $f['date'] < current_time( 'Y-m-d' ),
				'paid'    => $paid,
				'status'  => $paid ? 'Angemeldet' : 'Angemeldet – Zahlung offen',
				'pay_url' => ! $paid && $order->needs_payment() ? $order->get_checkout_payment_url() : '',
				'zoom'    => $paid ? (string) get_post_meta( $event_id, 'bw_appa_event_zoom_link', true ) : '',
				'access'  => $paid ? (string) get_post_meta( $event_id, 'bw_appa_event_zoom_access', true ) : '',
				'invoice' => $order->get_view_order_url(),
			);
		}
		usort( $items, static fn( array $a, array $b ): int => array( $a['past'], $a['date'] ) <=> array( $b['past'], $b['date'] ) );
		return $items;
	}
}
