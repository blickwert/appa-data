<?php
/**
 * Participants added by the office.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;

/**
 * The office adds a person to an event: existing account by e-mail, otherwise a new customer account; free or with a
 * payment link; overbooking allowed.
 */
final class ParticipantService {

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
		private readonly RegistrationFlow $flow,
	) {
	}

	/**
	 * Adds a person and returns the message for the office.
	 */
	public function add( int $event_id, string $email, string $first, string $last, float $amount, bool $notify, string $by ): string {
		if ( ! is_email( $email ) ) {
			return 'Bitte eine gültige E-Mail-Adresse eintragen.';
		}
		if ( ! $this->events->product_id( $event_id ) || ! $this->events->fields( $event_id )['registration'] ) {
			return 'Für diese Veranstaltung ist die Anmeldung nicht eingeschaltet.';
		}
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			if ( '' === $first || '' === $last ) {
				return 'Für eine neue Person bitte Vor- und Nachname eintragen.';
			}
			// New customer account; WooCommerce mails the access (set a password) to the person.
			$user_id = wc_create_new_customer( $email, '', '', array( 'first_name' => $first, 'last_name' => $last ) );
			if ( is_wp_error( $user_id ) ) {
				return 'Das Konto konnte nicht angelegt werden: ' . $user_id->get_error_message();
			}
			$customer = new \WC_Customer( $user_id );
			$customer->set_billing_first_name( $first );
			$customer->set_billing_last_name( $last );
			$customer->set_billing_email( $email );
			$customer->save();
			$user = get_userdata( $user_id );
		} elseif ( '' !== $first && '' === $user->first_name ) {
			wp_update_user( array( 'ID' => $user->ID, 'first_name' => $first, 'last_name' => $last ) );
		}
		if ( ! $user ) {
			return 'Das Konto konnte nicht angelegt werden.';
		}
		$existing = $this->events->registration_of( $event_id, $user->ID );
		if ( null !== $existing && EventRepository::holds_place( $existing ) ) {
			return sprintf( '%s ist bereits angemeldet.', $email );
		}
		$order = $this->flow->create_order( $event_id, $user->ID, $amount, sprintf( 'Von %s im Backend angemeldet.', $by ), $notify );
		if ( ! $order ) {
			return 'Die Anmeldung konnte nicht angelegt werden.';
		}
		$this->waitlist->remove( $event_id, $user->ID );
		return $amount > 0
			? sprintf( '%s ist angemeldet (Zahlung offen%s).', $email, $notify ? ', Zahlungslink per E-Mail verschickt' : '' )
			: sprintf( '%s ist angemeldet%s.', $email, $notify ? ', die Bestätigung ist verschickt' : '' );
	}

	/**
	 * Figures and rows for the participants page.
	 *
	 * @return array<string, mixed>
	 */
	public function overview( int $event_id ): array {
		$f      = $this->events->fields( $event_id );
		$orders = $this->events->registration_orders( $event_id );
		$count  = $this->events->count_registrations( $event_id );
		$rows   = array();
		foreach ( $orders as $order ) {
			$item = $this->events->item_for( $order, $event_id );
			if ( ! $item ) {
				continue;
			}
			$rows[] = array(
				'name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'email'  => $order->get_billing_email(),
				'member' => 'member' === $item->get_meta( '_bw_wm_price_basis' ),
				'amount' => (float) $item->get_total(),
				'status' => wc_get_order_status_name( $order->get_status() ),
				'date'   => $order->get_date_created() ? $order->get_date_created()->date( 'd.m.Y' ) : '',
				'url'    => $order->get_edit_order_url(),
				'number' => (string) $order->get_order_number(),
			);
		}
		$waiting = array();
		foreach ( $this->waitlist->all( $event_id ) as $entry ) {
			$user = get_userdata( $entry['user'] );
			if ( $user ) {
				$waiting[] = array( 'name' => trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name, 'email' => $user->user_email, 'notified' => '' !== $entry['notified'] );
			}
		}
		$price = EventPricing::price( $f['audience'], true, $f['price_member'], $f['price_public'] );
		return array(
			'title'    => get_the_title( $event_id ),
			'meta'     => trim( EventLabels::when( $f ) . ' · ' . EventLabels::place( $f ), ' ·' ),
			'capacity' => $f['capacity'],
			'count'    => $count,
			'left'     => EventPricing::places_left( $f['capacity'], $count ),
			'open'     => count( array_filter( $orders, static fn( \WC_Order $o ): bool => ! $o->is_paid() ) ),
			'rows'     => $rows,
			'waiting'  => $waiting,
			'price'    => null !== $price && $price > 0 ? number_format( $price, 2, ',', '' ) : '',
		);
	}
}
