<?php
/**
 * Membership subscriptions (WooCommerce Subscriptions): type at renewal, clearer cart texts.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Service\MembershipPeriod;
use BW\WooMembership\Modules\Membership\Service\MembershipTypes;
use BW\WooMembership\Modules\Membership\Service\ProofRules;

/**
 * Before a renewal order is created, the subscription switches between the student and the regular membership
 * depending on the student proof for the new period (answer M4: without proof the next renewal is regular).
 */
final class SubscriptionController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipOrderRepository $orders,
		private readonly ProofRepository $proofs,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		// Priority 0: before WooCommerce Subscriptions creates the renewal order (priority 1).
		add_action( 'woocommerce_scheduled_subscription_payment', array( $this, 'switch_type' ), 0, 1 );
		add_filter( 'gettext_woocommerce-subscriptions', array( $this, 'texts' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'renewal_line' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'script_texts' ) );
	}

	/**
	 * Same wording in the block cart and checkout, which build the price string in the browser.
	 */
	public function script_texts(): void {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
			return;
		}
		wp_add_inline_script(
			'wp-hooks',
			"wp.hooks.addFilter( 'i18n.gettext_woocommerce-subscriptions', 'bw-wm/texts', function ( translation, text ) { return 'every' === text ? 'pro' : ( 'every %s' === text ? 'pro %s' : translation ); } );"
		);
	}

	/**
	 * Clearer German than the language pack: "96,00 € pro Jahr" instead of "jede(n) Jahr".
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 */
	public function texts( $translation, $text ) {
		return 'every %s' === $text ? 'pro %s' : $translation;
	}

	/**
	 * Cart line of a membership synced to a fixed day: "Verlängerung: jährlich am 1. Jänner" (the plugin shows an
	 * English ordinal, "on Januar 1st each year").
	 *
	 * @param array<int, array<string, mixed>> $data Item data.
	 * @param array<string, mixed>             $item Cart item.
	 * @return array<int, array<string, mixed>>
	 */
	public function renewal_line( $data, $item ) {
		$product = $item['data'] ?? null;
		if ( ! is_array( $data ) || ! $product instanceof \WC_Product || '' === $this->orders->product_type( (int) ( $item['product_id'] ?? 0 ) ) ) {
			return $data;
		}
		$sync = $product->get_meta( '_subscription_payment_sync_date' );
		if ( ! is_array( $sync ) || 'year' !== $product->get_meta( '_subscription_period' ) ) {
			return $data;
		}
		$when = MembershipPeriod::day_month( (int) ( $sync['day'] ?? 0 ), (int) ( $sync['month'] ?? 0 ) );
		foreach ( $data as $key => $row ) {
			if ( '' !== $when && ! empty( $row['wcs_subscription_detail'] ) && in_array( $row['name'] ?? '', array( 'Renews', __( 'Renews', 'woocommerce-subscriptions' ) ), true ) ) { // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- the plugin's own label.
				$data[ $key ]['name']  = 'Verlängerung';
				$data[ $key ]['value'] = 'jährlich am ' . $when;
			}
		}
		return $data;
	}

	/**
	 * Swaps the membership product of a subscription that is due for renewal, when the proof requires it.
	 *
	 * @param int $subscription_id Subscription ID.
	 */
	public function switch_type( $subscription_id ): void {
		$subscription = function_exists( 'wcs_get_subscription' ) ? wcs_get_subscription( (int) $subscription_id ) : null;
		if ( ! $subscription instanceof \WC_Order || ! $subscription->has_status( 'active' ) ) {
			return;
		}
		$user_id = (int) $subscription->get_customer_id();
		$type    = $this->orders->order_type( $subscription );
		if ( $user_id <= 0 || '' === $type ) {
			return;
		}
		// The new period starts on the due date (1 January), even if the payment action runs a little earlier.
		$due    = substr( (string) $subscription->get_date( 'next_payment', 'site' ), 0, 10 );
		$day    = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due ) ? max( $due, current_time( 'Y-m-d' ) ) : current_time( 'Y-m-d' );
		$target = ProofRules::renewal_type( $type, $this->proofs->get( $user_id ), $day );
		$product_id = $target !== $type ? $this->orders->product_for_type( $target ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$price = (float) ( class_exists( 'WC_Subscriptions_Product' ) ? \WC_Subscriptions_Product::get_price( $product ) : $product->get_price() );
		foreach ( $subscription->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || '' === $this->orders->product_type( (int) $item->get_product_id() ) ) {
				continue;
			}
			$quantity = max( 1, (int) $item->get_quantity() );
			$item->set_product( $product );
			$item->set_subtotal( $price * $quantity );
			$item->set_total( $price * $quantity );
			$item->save();
		}
		$subscription->calculate_totals();
		$subscription->add_order_note(
			sprintf(
				'Mitgliedschaft zur Verlängerung umgestellt: %1$s → %2$s (Studiennachweis).',
				MembershipTypes::label( $type ),
				MembershipTypes::label( $target )
			)
		);
		$subscription->save();
	}
}
