<?php
/**
 * Event registrations in cart and checkout.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\RegistrationService;
use WC_Cart;

/**
 * Sets the personal price in the cart (member or public), refuses events the person cannot book and records the
 * price basis on the order item. The price is set on every cart calculation, so a status change between cart and
 * checkout is picked up.
 */
final class CartController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly RegistrationService $registrations,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_prices' ), 20 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'record_basis' ), 10, 3 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'purchasable' ), 10, 2 );
		add_filter( 'woocommerce_checkout_registration_required', array( $this, 'require_account' ) );
	}

	/**
	 * Refuses adding an event the person cannot book.
	 */
	public function validate_add( bool $passed, int $product_id ): bool {
		$event_id = $this->events->event_id( $product_id );
		if ( ! $passed || ! $event_id ) {
			return $passed;
		}
		$refusal = $this->registrations->refusal( $event_id, get_current_user_id() );
		if ( '' !== $refusal ) {
			wc_add_notice( $refusal, 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Personal price per event item.
	 */
	public function set_prices( WC_Cart $cart ): void {
		foreach ( $cart->get_cart() as $item ) {
			$event_id = $this->events->event_id( (int) ( $item['product_id'] ?? 0 ) );
			if ( $event_id && isset( $item['data'] ) && is_object( $item['data'] ) ) {
				$price = $this->registrations->price( $event_id, get_current_user_id() );
				if ( null !== $price ) {
					$item['data']->set_price( $price );
				}
			}
		}
	}

	/**
	 * Re-checks every event in the cart before checkout.
	 */
	public function check_cart(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			$event_id = $this->events->event_id( (int) ( $item['product_id'] ?? 0 ) );
			if ( $event_id ) {
				$refusal = $this->registrations->refusal( $event_id, get_current_user_id() );
				if ( '' !== $refusal ) {
					/* translators: 1: event title, 2: reason */
					wc_add_notice( sprintf( __( '%1$s: %2$s', 'bw-woo-membership' ), get_the_title( $event_id ), $refusal ), 'error' );
				}
			}
		}
	}

	/**
	 * Stores whether the member or the public price was charged.
	 *
	 * @param \WC_Order_Item_Product $item     Order item.
	 * @param string                 $cart_key Cart item key.
	 * @param array<string, mixed>   $values   Cart item.
	 */
	public function record_basis( mixed $item, string $cart_key, array $values ): void {
		$event_id = $this->events->event_id( (int) ( $values['product_id'] ?? 0 ) );
		if ( $event_id ) {
			$item->add_meta_data( '_bw_wm_price_basis', $this->registrations->is_member( get_current_user_id() ) ? 'member' : 'public', true );
			$item->add_meta_data( '_bw_wm_event_id', $event_id, true );
		}
	}

	/**
	 * Past events cannot be bought anymore.
	 *
	 * @param \WC_Product $product Product.
	 */
	public function purchasable( bool $purchasable, mixed $product ): bool {
		$event_id = is_object( $product ) ? $this->events->event_id( (int) $product->get_id() ) : 0;
		return $event_id ? $purchasable && ! $this->registrations->is_closed( $event_id ) : $purchasable;
	}

	/**
	 * Registrations belong to an account (one per account, access data in the member area): no guest checkout.
	 */
	public function require_account( bool $required ): bool {
		if ( $required || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $required;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( $this->events->event_id( (int) ( $item['product_id'] ?? 0 ) ) ) {
				return true;
			}
		}
		return false;
	}
}
