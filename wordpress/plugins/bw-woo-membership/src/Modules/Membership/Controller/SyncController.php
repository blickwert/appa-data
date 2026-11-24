<?php
/**
 * Keeps the membership snapshot up to date.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\MembershipSync;

/**
 * Updates the snapshot when a membership order or subscription changes, and once a day for everyone.
 */
final class SyncController {

	public const CRON = 'bw_wm_daily';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipSync $sync,
		private readonly MembershipOrderRepository $orders,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status' ), 20, 1 );
		add_action( 'woocommerce_new_order', array( $this, 'on_order_status' ), 20, 1 );
		add_action( 'woocommerce_subscription_status_updated', array( $this, 'on_subscription' ), 20, 1 );
		add_action( self::CRON, array( $this, 'daily' ) );
	}

	/**
	 * A membership order changed status.
	 */
	public function on_order_status( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof \WC_Order && $order->get_customer_id() > 0 && '' !== $this->orders->order_type( $order ) ) {
			$this->sync->sync( $order->get_customer_id() );
		}
	}

	/**
	 * A subscription changed status (WooCommerce Subscriptions).
	 *
	 * @param \WC_Order $subscription Subscription object.
	 */
	public function on_subscription( $subscription ): void {
		if ( is_object( $subscription ) && method_exists( $subscription, 'get_customer_id' ) && $subscription->get_customer_id() > 0 ) {
			$this->sync->sync( (int) $subscription->get_customer_id() );
		}
	}

	/**
	 * Daily: expire periods, catch changes made outside the hooks.
	 */
	public function daily(): void {
		$this->sync->sync_all();

		/**
		 * Fires after the daily membership sync (reminders, proof checks).
		 */
		do_action( 'bw_wm_daily_after_sync' );
	}
}
