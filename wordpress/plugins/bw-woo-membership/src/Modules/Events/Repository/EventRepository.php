<?php
/**
 * Events and their registration product.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Repository;

use WC_Order;

/**
 * Reads the event fields of the plugin "APPA Funktionen" (bw-appa, post type `appa_event`) and links each event with
 * a hidden WooCommerce product for the registration. The meta keys are the documented interface between both plugins.
 */
final class EventRepository {

	public const POST_TYPE          = 'appa_event';
	public const META_DATE          = 'bw_appa_event_date';
	public const META_TIME          = 'bw_appa_event_time';
	public const META_AUDIENCE      = 'bw_appa_event_audience';
	public const META_PRICE_MEMBER  = 'bw_appa_event_price_member';
	public const META_PRICE_PUBLIC  = 'bw_appa_event_price_public';
	public const META_CAPACITY      = 'bw_appa_event_capacity';
	public const META_REGISTRATION  = 'bw_appa_event_registration';
	public const META_DEADLINE      = 'bw_appa_event_deadline';
	public const META_PRODUCT       = '_bw_wm_product_id';
	public const META_EVENT         = '_bw_wm_event_id';
	public const REGISTERED_STATUSES = array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' );
	/** Statuses that hold a place: WooCommerce reduces the stock for these (not for pending). */
	public const HOLDING_STATUSES = array( 'processing', 'completed', 'on-hold' );

	/**
	 * Orders per event for this request.
	 *
	 * @var array<int, WC_Order[]>
	 */
	private array $cache = array();

	/**
	 * Event fields as plain strings.
	 *
	 * @return array{date: string, time: string, audience: string, price_member: string, price_public: string, capacity: string, registration: bool, deadline: string}
	 */
	public function fields( int $event_id ): array {
		$meta     = static fn( string $key ): string => (string) get_post_meta( $event_id, $key, true );
		$audience = $meta( self::META_AUDIENCE );
		return array(
			'date'         => $meta( self::META_DATE ),
			'time'         => $meta( self::META_TIME ),
			'audience'     => '' !== $audience ? $audience : 'open',
			'price_member' => $meta( self::META_PRICE_MEMBER ),
			'price_public' => $meta( self::META_PRICE_PUBLIC ),
			'capacity'     => $meta( self::META_CAPACITY ),
			'registration' => '1' === $meta( self::META_REGISTRATION ),
			'deadline'     => $meta( self::META_DEADLINE ),
		);
	}

	/**
	 * Product of an event (0 = none).
	 */
	public function product_id( int $event_id ): int {
		return (int) get_post_meta( $event_id, self::META_PRODUCT, true );
	}

	/**
	 * Event of a product (0 = not an event product).
	 */
	public function event_id( int $product_id ): int {
		return (int) get_post_meta( $product_id, self::META_EVENT, true );
	}

	/**
	 * Links event and product in both directions.
	 */
	public function link( int $event_id, int $product_id ): void {
		update_post_meta( $event_id, self::META_PRODUCT, $product_id );
		update_post_meta( $product_id, self::META_EVENT, $event_id );
	}

	/**
	 * Orders that contain the event product (paid and awaiting payment), newest first.
	 *
	 * @return WC_Order[]
	 */
	public function registration_orders( int $event_id, ?int $customer_id = null ): array {
		if ( ! isset( $this->cache[ $event_id ] ) ) {
			$product_id                 = $this->product_id( $event_id );
			$this->cache[ $event_id ] = $product_id && function_exists( 'wc_get_orders' ) ? array_values(
				array_filter(
					wc_get_orders( array( 'status' => self::REGISTERED_STATUSES, 'limit' => -1, 'type' => 'shop_order' ) ),
					fn( $order ): bool => $order instanceof WC_Order && $this->contains( $order, $product_id )
				)
			) : array();
		}
		if ( null === $customer_id ) {
			return $this->cache[ $event_id ];
		}
		return array_values( array_filter( $this->cache[ $event_id ], static fn( WC_Order $order ): bool => $order->get_customer_id() === $customer_id ) );
	}

	/**
	 * Forgets the cached orders (after an order changed).
	 */
	public function forget(): void {
		$this->cache = array();
	}

	/**
	 * Number of registrations that hold a place (pending orders do not, like the WooCommerce stock).
	 */
	public function count_registrations( int $event_id ): int {
		return count( array_filter( $this->registration_orders( $event_id ), array( self::class, 'holds_place' ) ) );
	}

	/**
	 * Whether an order holds a place.
	 */
	public static function holds_place( WC_Order $order ): bool {
		return in_array( $order->get_status(), self::HOLDING_STATUSES, true );
	}

	/**
	 * The customer's registration order for an event (also awaiting payment), or null.
	 */
	public function registration_of( int $event_id, int $customer_id ): ?WC_Order {
		if ( $customer_id <= 0 ) {
			return null;
		}
		return $this->registration_orders( $event_id, $customer_id )[0] ?? null;
	}

	/**
	 * Event IDs contained in an order.
	 *
	 * @return int[]
	 */
	public function events_in_order( WC_Order $order ): array {
		$ids = array();
		foreach ( $order->get_items() as $item ) {
			$event_id = method_exists( $item, 'get_product_id' ) ? $this->event_id( (int) $item->get_product_id() ) : 0;
			if ( $event_id ) {
				$ids[] = $event_id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * All event registrations of a customer: event ID => order.
	 *
	 * @return array<int, WC_Order>
	 */
	public function registrations_of_customer( int $customer_id ): array {
		if ( $customer_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		$out = array();
		foreach ( wc_get_orders( array( 'customer_id' => $customer_id, 'status' => self::REGISTERED_STATUSES, 'limit' => 50, 'type' => 'shop_order' ) ) as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				$event_id = method_exists( $item, 'get_product_id' ) ? $this->event_id( (int) $item->get_product_id() ) : 0;
				if ( $event_id && ! isset( $out[ $event_id ] ) ) {
					$out[ $event_id ] = $order;
				}
			}
		}
		return $out;
	}

	/**
	 * Whether an order contains a product.
	 */
	private function contains( WC_Order $order, int $product_id ): bool {
		foreach ( $order->get_items() as $item ) {
			if ( method_exists( $item, 'get_product_id' ) && (int) $item->get_product_id() === $product_id ) {
				return true;
			}
		}
		return false;
	}
}
