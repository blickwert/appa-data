<?php
/**
 * Purchase rules for membership products.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Model\Membership;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Service\MembershipPeriod;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use BW\WooMembership\Modules\Membership\Service\ProofRules;

/**
 * Existing members renew as students only with a valid proof; without it the regular membership applies
 * (meeting 9 October 2026). Checked when adding to the cart and again at checkout.
 */
final class PurchaseController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MembershipOrderRepository $orders,
		private readonly ProofRepository $proofs,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add' ), 10, 2 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart' ) );
	}

	/**
	 * Refuses adding the student product when not allowed.
	 */
	public function validate_add( bool $passed, int $product_id ): bool {
		if ( $passed && ! $this->allowed( $product_id ) ) {
			wc_add_notice( $this->message(), 'error' );
			return false;
		}
		return $passed;
	}

	/**
	 * Re-checks the cart (status or proof may have changed meanwhile).
	 */
	public function check_cart(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! $this->allowed( (int) ( $item['product_id'] ?? 0 ) ) ) {
				wc_add_notice( $this->message(), 'error' );
				return;
			}
		}
	}

	/**
	 * Whether the current user may buy a product.
	 */
	private function allowed( int $product_id ): bool {
		if ( 'student' !== $this->orders->product_type( $product_id ) ) {
			return true;
		}
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return true; // New members (account is created at checkout) may join as students and upload the proof afterwards.
		}
		$membership = $this->memberships->for_user( $user_id );
		$existing   = Membership::STATUS_NONE !== $membership->status;
		$next_start = null !== $membership->valid_until ? MembershipPeriod::next_renewal( $membership->valid_until ) : current_time( 'Y-m-d' );
		return ProofRules::may_buy_student( $existing, $this->proofs->get( $user_id ), $next_start );
	}

	/**
	 * Notice for a refused student membership.
	 */
	private function message(): string {
		return __( 'Die studentische Mitgliedschaft kannst du nur mit gültigem Studiennachweis verlängern. Bitte lade ihn im Mitgliederbereich hoch oder wähle die ordentliche Mitgliedschaft.', 'bw-woo-membership' );
	}
}
