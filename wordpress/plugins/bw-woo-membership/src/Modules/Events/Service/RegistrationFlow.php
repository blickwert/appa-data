<?php
/**
 * Registration flow: register, cancel, confirm, remind.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Business rules of the registration flow (concept 25 November 2026, like the event pages of the WKO):
 * - free events: logged-in people register with one click (a 0 € WooCommerce order); the office can add people,
 *   free or with a payment link;
 * - confirmation with calendar file, reminder a few days before;
 * - cancelling: free until the event day, paid until a deadline with a full refund.
 */
final class RegistrationFlow {

	public const ITEM_CONFIRMED  = '_bw_wm_confirmed';
	public const ITEM_REMINDED   = '_bw_wm_reminded';
	public const ADDED_BY_OFFICE = '_bw_wm_added_by_office';
	private const LOCK           = 'bw_wm_event_lock_';

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly RegistrationService $registrations,
		private readonly ProductSync $sync,
		private readonly RegistrationMails $mails,
	) {
	}

	/**
	 * One-click registration for a free event; returns the message for the person.
	 */
	public function register_free( int $event_id, int $user_id ): string {
		if ( ! $this->lock( $event_id ) ) {
			return 'Gerade melden sich viele an. Bitte versuche es gleich noch einmal.';
		}
		try {
			// Checked inside the lock: two clicks at the same time must not take the last place twice.
			$this->events->forget();
			$refusal = $this->registrations->refusal( $event_id, $user_id );
			$price   = $this->registrations->price( $event_id, $user_id );
			if ( '' !== $refusal || null === $price || $price > 0 ) {
				return '' !== $refusal ? $refusal : 'Für diese Veranstaltung ist die Anmeldung über die Kasse nötig.';
			}
			$order = $this->create_order( $event_id, $user_id );
			return $order ? 'Du bist angemeldet. Die Bestätigung haben wir dir per E-Mail geschickt.' : 'Die Anmeldung hat nicht geklappt. Bitte versuche es noch einmal.';
		} finally {
			$this->unlock( $event_id );
		}
	}

	/**
	 * Creates a registration order: 0 € is completed right away (the confirmation follows), an amount leaves the order
	 * "on hold" with a payment link (registrations added by the office).
	 *
	 * @param bool $notify Send the confirmation (free) or the payment link (amount).
	 */
	public function create_order( int $event_id, int $user_id, float $amount = 0.0, string $note = 'Kostenlose Anmeldung über die Veranstaltungsseite.', bool $notify = true ): ?WC_Order {
		$product = wc_get_product( $this->events->product_id( $event_id ) );
		if ( ! $product ) {
			return null;
		}
		try {
			$order = wc_create_order( array( 'customer_id' => $user_id, 'created_via' => 'bw_wm_register' ) );
			if ( is_wp_error( $order ) ) {
				return null;
			}
			$item = $order->get_item( $order->add_product( $product, 1, array( 'subtotal' => $amount, 'total' => $amount ) ) );
			if ( $item ) {
				$item->add_meta_data( '_bw_wm_price_basis', $this->registrations->is_member( $user_id ) ? 'member' : 'public', true );
				$item->add_meta_data( '_bw_wm_event_id', $event_id, true );
				if ( ! $notify ) {
					$item->add_meta_data( self::ITEM_CONFIRMED, 'ohne E-Mail', true );
				}
				$item->save();
			}
			$customer = new \WC_Customer( $user_id );
			$user     = get_userdata( $user_id );
			// The name shown in the box ("Du meldest dich an als …") is the profile name.
			$order->set_billing_first_name( $user && '' !== $user->first_name ? $user->first_name : $customer->get_billing_first_name() );
			$order->set_billing_last_name( $user && '' !== $user->last_name ? $user->last_name : $customer->get_billing_last_name() );
			$order->set_billing_email( $customer->get_billing_email() ?: ( $user ? $user->user_email : '' ) );
			$order->set_billing_country( $customer->get_billing_country() );
			$order->calculate_totals();
			$order->add_order_note( $note );
			$order->save();
			if ( $amount <= 0 ) {
				$order->payment_complete(); // Completes the order (OrderController) and reduces the stock.
				return $order;
			}
			$order->update_meta_data( self::ADDED_BY_OFFICE, '1' );
			$order->set_status( 'on-hold', 'Zahlung offen.' ); // Holds the place; payable through the link.
			$order->save();
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( $notify ) {
				$this->mails->payment_link( $order, $event_id, $this->events->fields( $event_id ) );
			}
			return $order;
		} catch ( \Throwable $e ) {
			self::log( 'Anmeldung konnte nicht angelegt werden: ' . $e->getMessage(), array( 'event' => $event_id, 'user' => $user_id ) );
			return null;
		}
	}

	/**
	 * Cancels a registration and returns the message for the person.
	 */
	public function cancel( int $event_id, int $user_id ): string {
		$order = $this->events->registration_of( $event_id, $user_id );
		$item  = $order ? $this->events->item_for( $order, $event_id ) : null;
		if ( ! $order || ! $item ) {
			return 'Wir haben keine Anmeldung gefunden.';
		}
		$f      = $this->events->fields( $event_id );
		$amount = (float) $item->get_total();
		$paid   = $order->is_paid() && $amount > 0;
		if ( ! RegistrationRules::can_cancel( $f['date'], $f['cancel_days'], $paid, current_time( 'Y-m-d' ) ) ) {
			return sprintf( 'Eine Abmeldung über die Website ist nicht mehr möglich. Bitte schreib uns an %s.', $this->mails->office() );
		}
		$refunded = $paid ? $this->refund( $order, $item, $amount ) : '';
		if ( 1 === count( $order->get_items() ) ) {
			// The order is only this registration: cancel it (a full refund already set it to "refunded").
			if ( ! $paid && ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
				$order->update_status( 'cancelled', 'Von der Person selbst abgemeldet.' );
			}
		} else {
			$item->update_meta_data( EventRepository::ITEM_CANCELLED, current_time( 'mysql' ) );
			$item->save();
			$order->add_order_note( sprintf( 'Abmeldung von „%s“ durch die Person.', get_the_title( $event_id ) ) );
		}
		$this->events->forget();
		$this->sync->sync( $event_id );
		$this->mails->cancellation( $order, $event_id, $f, $refunded );
		return 'Du bist abgemeldet.' . ( '' !== $refunded ? ' ' . $refunded : '' );
	}

	/**
	 * Confirmation mail, once per registration, when the order is paid (or free).
	 */
	public function confirm( WC_Order $order ): void {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || $item->get_meta( self::ITEM_CONFIRMED ) || $item->get_meta( EventRepository::ITEM_CANCELLED ) ) {
				continue;
			}
			$event_id = $this->events->event_id( (int) $item->get_product_id() );
			if ( ! $event_id ) {
				continue;
			}
			$item->update_meta_data( self::ITEM_CONFIRMED, current_time( 'mysql' ) );
			$item->save();
			$this->mails->confirmation( $order, $event_id, $this->events->fields( $event_id ), (float) $item->get_total() );
		}
	}

	/**
	 * Reminder mails for upcoming events (daily); returns the number sent.
	 */
	public function send_reminders( string $today ): int {
		$sent = 0;
		foreach ( $this->events->upcoming_with_registration( $today ) as $event_id ) {
			$f = $this->events->fields( $event_id );
			if ( ! RegistrationRules::reminder_due( $f['date'], $f['reminder_days'], $today ) ) {
				continue;
			}
			foreach ( $this->events->registration_orders( $event_id ) as $order ) {
				$item = EventRepository::holds_place( $order ) && $order->is_paid() ? $this->events->item_for( $order, $event_id ) : null;
				if ( ! $item || $item->get_meta( self::ITEM_REMINDED ) ) {
					continue;
				}
				$item->update_meta_data( self::ITEM_REMINDED, current_time( 'mysql' ) );
				$item->save();
				$this->mails->reminder( $order, $event_id, $f );
				++$sent;
			}
		}
		return $sent;
	}

	/**
	 * Whether every item of an order is an event registration.
	 */
	public function only_events( WC_Order $order ): bool {
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || ! $this->events->event_id( (int) $item->get_product_id() ) ) {
				return false;
			}
		}
		return (bool) $items;
	}

	/**
	 * Refunds a paid registration in full; returns the sentence for the person.
	 */
	private function refund( WC_Order $order, WC_Order_Item_Product $item, float $amount ): string {
		$args    = array(
			'amount'     => $amount,
			'reason'     => 'Abmeldung durch die Person',
			'order_id'   => $order->get_id(),
			'line_items' => array( $item->get_id() => array( 'qty' => 1, 'refund_total' => $amount ) ),
		);
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway && $gateway->supports( 'refunds' ) ) {
			$refund = wc_create_refund( $args + array( 'refund_payment' => true ) );
			if ( ! is_wp_error( $refund ) ) {
				return sprintf( 'Den Betrag von %s erstatten wir auf dein Zahlungsmittel zurück.', wp_strip_all_tags( wc_price( $amount ) ) );
			}
			self::log( 'Automatische Erstattung fehlgeschlagen: ' . $refund->get_error_message(), array( 'order' => $order->get_id() ) );
		}
		// Bank transfer or a failed automatic refund: recorded, the office pays back.
		$refund = wc_create_refund( $args + array( 'refund_payment' => false ) );
		if ( is_wp_error( $refund ) ) {
			$order->add_order_note( 'Abmeldung: Erstattung konnte nicht angelegt werden – bitte manuell erstatten.' );
			self::log( 'Erstattung konnte nicht angelegt werden: ' . $refund->get_error_message(), array( 'order' => $order->get_id() ) );
		}
		$this->mails->manual_refund( $order, $amount );
		return sprintf( 'Den Betrag von %s überweisen wir dir in den nächsten Tagen zurück.', wp_strip_all_tags( wc_price( $amount ) ) );
	}

	/**
	 * Short lock per event (add_option only succeeds once; a lock older than 30 seconds is stale).
	 */
	private function lock( int $event_id ): bool {
		for ( $try = 0; $try < 30; $try++ ) {
			if ( add_option( self::LOCK . $event_id, (string) time(), '', false ) ) {
				return true;
			}
			wp_cache_delete( self::LOCK . $event_id, 'options' );
			if ( time() - (int) get_option( self::LOCK . $event_id, 0 ) > 30 ) {
				delete_option( self::LOCK . $event_id );
				continue;
			}
			usleep( 100000 );
		}
		return false;
	}

	/**
	 * Releases the lock.
	 */
	private function unlock( int $event_id ): void {
		delete_option( self::LOCK . $event_id );
	}

	/**
	 * Writes to the WooCommerce log (WooCommerce › Status › Protokolle, source "bw-woo-membership").
	 *
	 * @param array<string, mixed> $context Context.
	 */
	private static function log( string $message, array $context = array() ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $message, $context + array( 'source' => 'bw-woo-membership' ) );
		}
	}
}
