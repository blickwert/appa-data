<?php
/**
 * Event registrations.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\NoticeRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;

/**
 * Decides price and bookability of an event for a user and prepares the registration box.
 */
final class RegistrationService {

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
		private readonly NoticeRepository $notices,
	) {
	}

	/**
	 * Whether the user has an active membership (answered by the membership module).
	 */
	public function is_member( int $user_id ): bool {
		/**
		 * Whether a user has an active membership (answered by the membership module). Unlike
		 * `bw_wm_user_has_access`, site staff are not members here, so they pay the regular price.
		 *
		 * @param bool $is_member Default false.
		 * @param int  $user_id   User ID.
		 */
		return $user_id > 0 && (bool) apply_filters( 'bw_wm_user_is_member', false, $user_id );
	}

	/**
	 * Price for the user, or null when not bookable for them.
	 */
	public function price( int $event_id, int $user_id ): ?float {
		$f = $this->events->fields( $event_id );
		return EventPricing::price( $f['audience'], $this->is_member( $user_id ), $f['price_member'], $f['price_public'] );
	}

	/**
	 * Whether the event takes registrations on the website at all (the same for everyone).
	 */
	public function enabled( int $event_id ): bool {
		return $this->events->fields( $event_id )['registration'];
	}

	/**
	 * Whether registration is closed (event over or deadline passed).
	 */
	public function is_closed( int $event_id ): bool {
		$f = $this->events->fields( $event_id );
		return ! EventPricing::open( $f['date'], $f['deadline'], current_time( 'Y-m-d' ) );
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
		if ( $this->is_closed( $event_id ) ) {
			return 'Die Anmeldefrist ist abgelaufen.';
		}
		$existing = $user_id > 0 ? $this->events->registration_of( $event_id, $user_id ) : null;
		// A pending order (payment failed or aborted) does not block a new attempt.
		if ( null !== $existing && EventRepository::holds_place( $existing ) ) {
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
		$left         = EventPricing::places_left( $f['capacity'], $this->events->count_registrations( $event_id ) );
		$deadline     = '' !== $f['deadline'] ? \DateTimeImmutable::createFromFormat( '!Y-m-d', $f['deadline'] ) : false;
		// Waiting list: sold out, still open, and the person could book (logged in, not registered, eligible).
		$sold_out     = 0 === $left && ! $this->is_closed( $event_id ) && null === $registration;
		$eligible     = $user_id > 0 && null !== $this->price( $event_id, $user_id );
		$price        = $user_id > 0 ? $this->price( $event_id, $user_id ) : null;
		$item         = null !== $registration ? $this->events->item_for( $registration, $event_id ) : null;
		$paid_amount  = null !== $registration && null !== $item && $registration->is_paid() && (float) $item->get_total() > 0;
		$cancel_until = null !== $registration ? RegistrationRules::cancel_until( $f['date'], $f['cancel_days'], $paid_amount ) : '';
		$user         = $user_id > 0 ? get_userdata( $user_id ) : false;
		return array(
			'notice'       => $this->notices->take( $user_id ),
			'event'        => $event_id,
			'action'       => admin_url( 'admin-post.php' ),
			// Free for this person and logged in: register with one click under the own name.
			'one_click'    => '' === $refusal && 0.0 === $price ? wp_create_nonce( 'bw_wm_register_' . $event_id ) : '',
			'name'         => $user ? trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name : '',
			'email'        => $user ? $user->user_email : '',
			'cancel'       => null !== $registration && RegistrationRules::can_cancel( $f['date'], $f['cancel_days'], $paid_amount, current_time( 'Y-m-d' ) ) ? wp_create_nonce( 'bw_wm_cancel_registration_' . $event_id ) : '',
			'cancel_until' => '' !== $cancel_until ? EventLabels::day( $cancel_until ) : '',
			'refund'       => $paid_amount,
			'office'       => (string) apply_filters( 'bw_wm_office_email', (string) get_option( 'admin_email' ) ),
			'paid_until'   => EventLabels::day( RegistrationRules::cancel_until( $f['date'], $f['cancel_days'], true ) ),
			'deadline'     => $deadline ? $deadline->format( 'd.m.Y' ) : '',
			'waitlist'     => array(
				'open'     => $sold_out && ( $eligible || $user_id <= 0 ),
				'can_join' => $sold_out && $eligible,
				'position' => $user_id > 0 ? $this->waitlist->position( $event_id, $user_id ) : 0,
				'action'   => admin_url( 'admin-post.php' ),
				'nonce'    => wp_create_nonce( 'bw_wm_waitlist_' . $event_id ),
				'event'    => $event_id,
			),
			'enabled'      => $f['registration'],
			'audience'     => $f['audience'],
			'member_price' => EventPricing::label( $member_price ),
			'public_price' => EventPricing::label( $public_price ),
			'is_member'    => $this->is_member( $user_id ),
			'places_left'  => $left,
			'registered'   => null !== $registration,
			'paid'         => null !== $registration && $registration->is_paid(),
			'refusal'      => null !== $registration ? '' : $refusal,
			'book_url'     => '' === $refusal && $product_id && function_exists( 'wc_get_checkout_url' ) ? add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() ) : '',
			// Back to the event after the login (the member-area login page always leads to the dashboard).
			'login_url'    => $user_id > 0 ? '' : wp_login_url( (string) get_permalink( $event_id ) . '#anmeldung' ),
			'join_url'     => home_url( '/mitgliedschaft/' ),
			'area_url'     => home_url( '/mitgliederbereich-uebersicht/#veranstaltungen' ),
		);
	}

	/**
	 * Whether the person can cancel the registration in an order on the website.
	 */
	public function can_cancel( int $event_id, \WC_Order $order ): bool {
		$item = $this->events->item_for( $order, $event_id );
		$f    = $this->events->fields( $event_id );
		return null !== $item && RegistrationRules::can_cancel( $f['date'], $f['cancel_days'], $order->is_paid() && (float) $item->get_total() > 0, current_time( 'Y-m-d' ) );
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
			$paid    = $order->is_paid();
			$items[] = array(
				'title'   => get_the_title( $event_id ),
				'url'     => (string) get_permalink( $event_id ),
				'date'    => $f['date'],
				'when'    => EventLabels::when( $f ),
				'past'    => '' !== $f['date'] && $f['date'] < current_time( 'Y-m-d' ),
				'paid'    => $paid,
				'status'  => $paid ? 'Angemeldet' : 'Angemeldet – Zahlung offen',
				'pay_url' => ! $paid && ! $this->is_past( $event_id ) && $order->needs_payment() ? $order->get_checkout_payment_url() : '',
				'zoom'    => $paid ? $f['zoom_link'] : '',
				'access'  => $paid ? $f['zoom_access'] : '',
				'invoice' => $order->get_view_order_url(),
				'event'   => $event_id,
				'cancel'  => $this->can_cancel( $event_id, $order ) ? wp_create_nonce( 'bw_wm_cancel_registration_' . $event_id ) : '',
			);
		}
		usort( $items, static fn( array $a, array $b ): int => array( $a['past'], $a['date'] ) <=> array( $b['past'], $b['date'] ) );
		return $items;
	}
}
