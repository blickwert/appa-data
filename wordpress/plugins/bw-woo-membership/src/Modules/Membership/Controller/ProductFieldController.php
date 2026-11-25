<?php
/**
 * Product field "Mitgliedsart".
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\MembershipTypes;

/**
 * Marks WooCommerce products as membership products (field in the product data box).
 */
final class ProductFieldController {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_field' ) );
	}

	/**
	 * Select field in the "General" tab.
	 */
	public function render_field(): void {
		if ( ! function_exists( 'woocommerce_wp_select' ) ) {
			return;
		}
		echo '<div class="options_group">';
		woocommerce_wp_select(
			array(
				'id'          => MembershipOrderRepository::META_PRODUCT_TYPE,
				'label'       => __( 'Mitgliedsart', 'bw-woo-membership' ),
				'options'     => array( '' => __( '– kein Mitgliedschaftsprodukt –', 'bw-woo-membership' ) ) + MembershipTypes::all(),
				'desc_tip'    => true,
				'description' => __( 'Wer dieses Produkt bezahlt, wird Mitglied dieser Art.', 'bw-woo-membership' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Saves the field (WooCommerce has checked the nonce and capability before this hook).
	 */
	public function save_field( \WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WooCommerce for this hook.
		$type = isset( $_POST[ MembershipOrderRepository::META_PRODUCT_TYPE ] ) ? sanitize_key( wp_unslash( $_POST[ MembershipOrderRepository::META_PRODUCT_TYPE ] ) ) : '';
		if ( '' === $type || MembershipTypes::exists( $type ) ) {
			$product->update_meta_data( MembershipOrderRepository::META_PRODUCT_TYPE, $type );
		}
	}
}
