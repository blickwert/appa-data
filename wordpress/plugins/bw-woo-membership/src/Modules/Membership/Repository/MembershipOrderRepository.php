<?php
/**
 * Membership orders.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Repository;

use WC_Order;

/**
 * Finds WooCommerce orders and subscriptions that contain a membership product.
 */
final class MembershipOrderRepository {

	public const META_PRODUCT_TYPE = '_bw_wm_type';

	/**
	 * Membership type of a product (empty when it is no membership product).
	 */
	public function product_type( int $product_id ): string {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product ) {
			return '';
		}
		$type = (string) $product->get_meta( self::META_PRODUCT_TYPE );
		if ( '' === $type && $product->get_parent_id() ) {
			$type = $this->product_type( $product->get_parent_id() );
		}
		return $type;
	}

	/**
	 * First published product of a membership type (0 = none).
	 */
	public function product_for_type( string $type ): int {
		$ids = get_posts(
			array(
				'post_type'     => 'product',
				'post_status'   => 'publish',
				'meta_key'      => self::META_PRODUCT_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- few products.
				'meta_value'    => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'        => 'ids',
				'numberposts'   => 1,
				'no_found_rows' => true,
			)
		);
		return (int) ( $ids[0] ?? 0 );
	}

	/**
	 * Membership type contained in an order (empty when none).
	 */
	public function order_type( WC_Order $order ): string {
		foreach ( $order->get_items() as $item ) {
			$product_id = method_exists( $item, 'get_variation_id' ) && $item->get_variation_id() ? $item->get_variation_id() : ( method_exists( $item, 'get_product_id' ) ? $item->get_product_id() : 0 );
			$type       = $this->product_type( (int) $product_id );
			if ( '' !== $type ) {
				return $type;
			}
		}
		return '';
	}

	/**
	 * The user's latest membership order as plain data, newest first wins.
	 *
	 * @return array{type: string, status: string, paid: ?string}|null
	 */
	public function latest_order( int $user_id ): ?array {
		if ( ! function_exists( 'wc_get_orders' ) || $user_id <= 0 ) {
			return null;
		}
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'type'        => 'shop_order',
			)
		);
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$type = $this->order_type( $order );
			if ( '' === $type ) {
				continue;
			}
			$paid = $order->get_date_paid();
			return array(
				'type'   => $type,
				'status' => $order->get_status(),
				'paid'   => $paid ? $paid->date( 'Y-m-d' ) : null,
			);
		}
		return null;
	}

	/**
	 * Customers with a membership order that is not paid yet (they have no member number yet).
	 *
	 * @return int[]
	 */
	public function customers_with_open_orders(): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		$ids = array();
		foreach ( wc_get_orders( array( 'status' => array( 'wc-pending', 'wc-on-hold' ), 'limit' => 200, 'type' => 'shop_order' ) ) as $order ) {
			if ( $order instanceof WC_Order && $order->get_customer_id() > 0 && '' !== $this->order_type( $order ) ) {
				$ids[] = $order->get_customer_id();
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * The user's membership subscription as plain data (WooCommerce Subscriptions), or null.
	 *
	 * @return array{type: string, status: string, start: ?string, end: ?string, next: ?string, manual: bool}|null
	 */
	public function latest_subscription( int $user_id ): ?array {
		if ( ! function_exists( 'wcs_get_users_subscriptions' ) || $user_id <= 0 ) {
			return null;
		}
		foreach ( wcs_get_users_subscriptions( $user_id ) as $subscription ) {
			$type = $this->order_type( $subscription );
			if ( '' === $type ) {
				continue;
			}
			$date = static function ( string $key ) use ( $subscription ): ?string {
				$value = (string) $subscription->get_date( $key );
				return '' !== $value && '0' !== $value ? substr( $value, 0, 10 ) : null;
			};
			return array(
				'type'   => $type,
				'status' => $subscription->get_status(),
				'start'  => $date( 'start' ),
				'end'    => $date( 'end' ),
				'next'   => $date( 'next_payment' ),
				'manual' => (bool) $subscription->is_manual(),
			);
		}
		return null;
	}
}
