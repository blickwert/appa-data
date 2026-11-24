<?php
/**
 * Paid membership orders.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\MemberNumber;

/**
 * Gives a member a number and start date when a membership order is paid.
 */
final class OrderController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MemberRepository $members,
		private readonly MembershipOrderRepository $orders,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_paid' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_paid' ) );
	}

	/**
	 * Assigns number and start date (once) for the customer of a paid membership order.
	 */
	public function on_paid( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order || $order->get_customer_id() <= 0 ) {
			return;
		}
		$type = $this->orders->order_type( $order );
		if ( '' === $type ) {
			return;
		}
		$user_id = $order->get_customer_id();
		$this->members->ensure_number( $user_id, MemberNumber::format( $this->members->sequence() + 1 ), current_time( 'Y-m-d' ) );

		/**
		 * Fires when a membership order is paid.
		 *
		 * @param int       $user_id Member.
		 * @param string    $type    Membership type.
		 * @param \WC_Order $order   Order.
		 */
		do_action( 'bw_wm_membership_paid', $user_id, $type, $order );
	}
}
