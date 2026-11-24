<?php
/**
 * Joining through WooCommerce: join links and the application fields at the checkout.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\JoinDiscount;
use BW\WooMembership\Modules\Membership\Service\MemberPages;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use BW\WooMembership\Modules\Membership\Service\ProfileFields;
use WC_Order;

/**
 * `?bw_wm_join=regular|student` puts the membership product into the cart and opens the checkout (the account is
 * created there). The former application form lives on as fields of the block checkout, shown only when a
 * membership is in the cart; the answers are copied to the member's account.
 */
final class JoinController {

	public const JOIN_TYPES     = array( 'regular', 'student' );
	public const FIELD_JOB      = 'bw-wm/occupation';
	public const FIELD_GROUP    = 'bw-wm/group';
	public const FIELD_STATUTES = 'bw-wm/statutes';
	public const META_JOB       = 'bw_wm_occupation';
	public const META_STATUTES  = 'bw_wm_statutes_accepted';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MembershipOrderRepository $orders,
		private readonly MemberRepository $members,
		private readonly MemberPages $pages,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'join' ) );
		add_action( 'woocommerce_init', array( $this, 'register_fields' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'apply_to_account' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'join_discount' ) );
	}

	/**
	 * Reduced first fee by month of joining (JoinDiscount) for people who were never members. A fee line, not a
	 * changed price, so that renewals (and a later subscription) keep the full yearly fee.
	 *
	 * @param \WC_Cart $cart Cart.
	 */
	public function join_discount( $cart ): void {
		if ( ! is_object( $cart ) || ! empty( $cart->recurring_cart_key ) ) {
			return; // WooCommerce Subscriptions: never on the recurring cart.
		}
		$user_id = get_current_user_id();
		if ( $user_id > 0 && '' !== $this->members->number( $user_id ) ) {
			return; // Has been a member before: no join discount.
		}
		$month = (int) current_time( 'n' );
		foreach ( $cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( ! $product instanceof \WC_Product || '' === $this->orders->product_type( (int) ( $item['product_id'] ?? 0 ) ) ) {
				continue;
			}
			$discount = JoinDiscount::discount( (float) $product->get_price() * (int) ( $item['quantity'] ?? 1 ), $month );
			if ( $discount > 0 ) {
				$cart->add_fee( JoinDiscount::label( $month ), -$discount, false );
			}
		}
	}

	/**
	 * Handles a join link: membership product into the cart (replacing another membership), then the checkout.
	 */
	public function join(): void {
		$type = isset( $_GET['bw_wm_join'] ) ? sanitize_key( wp_unslash( $_GET['bw_wm_join'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- plain link, only fills the cart.
		if ( '' === $type || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$product_id = in_array( $type, self::JOIN_TYPES, true ) ? $this->orders->product_for_type( $type ) : 0;
		if ( ! $product_id ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( $user_id > 0 && $this->memberships->for_user( $user_id )->grants_access() && '' !== $this->pages->dashboard_url() ) {
			// Already a member: renewal and status live in the member area.
			wp_safe_redirect( $this->pages->dashboard_url() );
			exit;
		}
		$in_cart = false;
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$id = (int) ( $item['product_id'] ?? 0 );
			if ( $id === $product_id ) {
				$in_cart = true;
			} elseif ( '' !== $this->orders->product_type( $id ) ) {
				WC()->cart->remove_cart_item( $key );
			}
		}
		$checkout = wc_get_checkout_url();
		wp_safe_redirect( $in_cart ? $checkout : add_query_arg( 'add-to-cart', $product_id, $checkout ) );
		exit;
	}

	/**
	 * Registers the application fields of the block checkout (WooCommerce additional checkout fields).
	 */
	public function register_fields(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		$products = array_values( array_filter( array_map( array( $this->orders, 'product_for_type' ), self::JOIN_TYPES ) ) );
		if ( ! $products ) {
			return;
		}
		$with_membership    = array( 'cart' => array( 'properties' => array( 'items' => array( 'contains' => array( 'enum' => $products ) ) ) ) );
		$without_membership = array( 'cart' => array( 'properties' => array( 'items' => array( 'not' => array( 'contains' => array( 'enum' => $products ) ) ) ) ) );

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::FIELD_JOB,
				'label'    => 'Beruf / Studienrichtung',
				'location' => 'order',
				'type'     => 'text',
				'required' => $with_membership,
				'hidden'   => $without_membership,
			)
		);
		$options = array( array( 'value' => 'none', 'label' => 'Noch offen' ) );
		foreach ( ProfileFields::GROUPS as $value => $label ) {
			$options[] = array( 'value' => $value, 'label' => $label );
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::FIELD_GROUP,
				'label'    => 'Welche Fachgruppe interessiert dich am meisten? (weitere im Profil)',
				'location' => 'order',
				'type'     => 'select',
				'options'  => $options,
				'required' => false,
				'hidden'   => $without_membership,
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::FIELD_STATUTES,
				'label'    => 'Ich habe die Statuten gelesen und trete der APPA bei.',
				'location' => 'order',
				'type'     => 'checkbox',
				'required' => $with_membership,
				'hidden'   => $without_membership,
			)
		);
	}

	/**
	 * Copies the answers of a membership order to the member's account (profile groups, occupation, consent).
	 */
	public function apply_to_account( WC_Order $order ): void {
		$user_id = $order->get_customer_id();
		if ( $user_id <= 0 || '' === $this->orders->order_type( $order ) ) {
			return;
		}
		$job = sanitize_text_field( (string) $order->get_meta( '_wc_other/' . self::FIELD_JOB ) );
		if ( '' !== $job ) {
			update_user_meta( $user_id, self::META_JOB, $job );
		}
		$group = sanitize_key( (string) $order->get_meta( '_wc_other/' . self::FIELD_GROUP ) );
		if ( isset( ProfileFields::GROUPS[ $group ] ) ) {
			$profile = $this->members->profile( $user_id );
			if ( ! in_array( $group, $profile['groups'], true ) ) {
				$profile['groups'][] = $group;
				$this->members->save_profile( $user_id, $profile );
			}
		}
		if ( $order->get_meta( '_wc_other/' . self::FIELD_STATUTES ) ) {
			update_user_meta( $user_id, self::META_STATUTES, current_time( 'Y-m-d' ) );
		}
	}
}
