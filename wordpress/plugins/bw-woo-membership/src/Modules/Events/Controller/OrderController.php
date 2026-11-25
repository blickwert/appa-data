<?php
/**
 * Registration orders.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Support\Mailer;
use WC_Order;

/**
 * Completes paid registration orders automatically and cancels unpaid ones after a few days, which frees the place
 * again (WooCommerce restores the stock on cancellation).
 */
final class OrderController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly Mailer $mailer,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'complete_registrations' ), 10, 3 );
		add_action( 'bw_wm_daily_after_sync', array( $this, 'cancel_unpaid' ) );
		add_action( 'woocommerce_new_order_item', array( $this, 'index_item' ), 10, 3 );
	}

	/**
	 * Keeps the order index of an event (EventRepository::order_ids()) when an event is added to an order.
	 *
	 * @param int            $item_id  Item ID.
	 * @param \WC_Order_Item $item     Item.
	 * @param int            $order_id Order ID.
	 */
	public function index_item( $item_id, $item, $order_id ): void {
		$event_id = $item instanceof \WC_Order_Item_Product ? $this->events->event_id( (int) $item->get_product_id() ) : 0;
		if ( $event_id && (int) $order_id > 0 ) {
			$this->events->remember_order( $event_id, (int) $order_id );
		}
	}

	/**
	 * Orders that only contain event registrations are completed right after payment (also 0 € orders).
	 *
	 * @param string        $status   Status WooCommerce would set.
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order.
	 */
	public function complete_registrations( string $status, int $order_id, $order = null ): string {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		return $order instanceof WC_Order && $this->only_events( $order ) ? 'completed' : $status;
	}

	/**
	 * Cancels registration orders that are still unpaid after the waiting time (docs/OFFENE-FRAGEN.md, V2).
	 */
	public function cancel_unpaid(): int {
		/**
		 * Filters the number of days an unpaid event registration is kept.
		 *
		 * @param int $days Default 7.
		 */
		$days      = max( 1, (int) apply_filters( 'bw_wm_unpaid_registration_days', 7 ) );
		$cancelled = 0;
		$limit     = time() - $days * DAY_IN_SECONDS;
		$orders    = wc_get_orders(
			array(
				'status' => array( 'wc-on-hold', 'wc-pending' ),
				'limit'  => -1,
				'type'   => 'shop_order',
			)
		);
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order || ! $this->only_events( $order ) ) {
				continue;
			}
			$created = $order->get_date_created();
			$expired = $created && $created->getTimestamp() < $limit;
			// Unpaid registrations for events that are over are cancelled right away.
			if ( ! $expired && ! $this->all_past( $order ) ) {
				continue;
			}
			$order->update_status( 'cancelled', $expired ? sprintf( 'Anmeldung nach %d Tagen ohne Zahlung storniert.', $days ) : 'Anmeldung ohne Zahlung storniert: Die Veranstaltung ist vorbei.' );
			$this->mailer->send(
				$order->get_billing_email(),
				'Deine Anmeldung wurde storniert',
				sprintf( "Hallo %s,\n\nwir haben für deine Anmeldung (Bestellung %s) innerhalb von %d Tagen keine Zahlung erhalten und den Platz wieder freigegeben. Du kannst dich jederzeit neu anmelden, solange Plätze frei sind.", $order->get_billing_first_name(), $order->get_order_number(), $days )
			);
			++$cancelled;
		}
		return $cancelled;
	}

	/**
	 * Whether all events of an order lie in the past.
	 */
	private function all_past( WC_Order $order ): bool {
		$today = current_time( 'Y-m-d' );
		foreach ( $this->events->events_in_order( $order ) as $event_id ) {
			$date = $this->events->fields( $event_id )['date'];
			if ( '' === $date || $date >= $today ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether every item of an order is an event registration.
	 */
	private function only_events( WC_Order $order ): bool {
		$items = $order->get_items();
		if ( ! $items ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( ! method_exists( $item, 'get_product_id' ) || ! $this->events->event_id( (int) $item->get_product_id() ) ) {
				return false;
			}
		}
		return true;
	}
}
